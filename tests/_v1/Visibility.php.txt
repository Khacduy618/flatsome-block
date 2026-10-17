<?php
/**
 * Bật / tắt / hẹn giờ hiển thị block.
 *
 * Flatsome render [block] mà không kiểm tra post_status, nên plugin chặn ở
 * pre_do_shortcode_tag. Gỡ plugin thì hook mất và mọi block hiện lại.
 *
 * Meta:
 * - _fbm_enabled    '0' = tắt. Không có meta = bật.
 * - _fbm_show_from  unix timestamp (UTC), rỗng = không giới hạn.
 * - _fbm_show_until unix timestamp (UTC), rỗng = không giới hạn.
 *
 * @package FBM
 */

namespace FBM\Modules\Blocks;

defined( 'ABSPATH' ) || exit;

final class Visibility {

	const META_ENABLED = '_fbm_enabled';
	const META_FROM    = '_fbm_show_from';
	const META_UNTIL   = '_fbm_show_until';
	const CRON_HOOK    = 'fbm_visibility_boundary';

	/** @var array<int, bool> */
	private $visible_cache = array();

	public function boot() {
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_block_shortcode' ), 10, 3 );
		add_action( self::CRON_HOOK, array( $this, 'purge_page_cache' ) );
	}

	/**
	 * @return array{enabled: bool, from: int|null, until: int|null, state: string, visible: bool}
	 *         state: on | off | pending | running | expired
	 */
	public function state( $block_id ) {
		$enabled = get_post_meta( $block_id, self::META_ENABLED, true ) !== '0';
		$from    = (int) get_post_meta( $block_id, self::META_FROM, true );
		$until   = (int) get_post_meta( $block_id, self::META_UNTIL, true );
		$now     = time();

		if ( ! $enabled ) {
			$state = 'off';
		} elseif ( $from && $now < $from ) {
			$state = 'pending';
		} elseif ( $until && $now > $until ) {
			$state = 'expired';
		} elseif ( $from || $until ) {
			$state = 'running';
		} else {
			$state = 'on';
		}

		return array(
			'enabled' => $enabled,
			'from'    => $from ? gmdate( 'c', $from ) : null,
			'until'   => $until ? gmdate( 'c', $until ) : null,
			'state'   => $state,
			'visible' => in_array( $state, array( 'on', 'running' ), true ),
		);
	}

	public function is_visible( $block_id ) {
		$block_id = (int) $block_id;
		if ( ! isset( $this->visible_cache[ $block_id ] ) ) {
			$state                            = $this->state( $block_id );
			$this->visible_cache[ $block_id ] = $state['visible'];
		}
		return $this->visible_cache[ $block_id ];
	}

	/**
	 * @param array $data enabled (bool), show_from / show_until (ISO 8601 hoặc '' / null để xoá).
	 * @return true|\WP_Error
	 */
	public function update( $block_id, array $data ) {
		// Tính và kiểm tra toàn bộ trước, chỉ ghi khi hợp lệ.
		$times = array(
			'show_from'  => (int) get_post_meta( $block_id, self::META_FROM, true ),
			'show_until' => (int) get_post_meta( $block_id, self::META_UNTIL, true ),
		);
		foreach ( array_keys( $times ) as $field ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			if ( null === $data[ $field ] || '' === $data[ $field ] ) {
				$times[ $field ] = 0;
				continue;
			}
			$ts = $this->parse_time( $data[ $field ] );
			if ( ! $ts ) {
				return new \WP_Error( 'fbm_invalid_date', sprintf( __( 'Thời gian không hợp lệ: %s', 'flatsome-block-manager' ), $data[ $field ] ), array( 'status' => 400 ) );
			}
			$times[ $field ] = $ts;
		}
		$from  = $times['show_from'];
		$until = $times['show_until'];
		if ( $from && $until && $until <= $from ) {
			return new \WP_Error( 'fbm_invalid_range', __( '"Tự tắt lúc" phải sau "Hiện từ".', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}

		$changed = false;
		if ( array_key_exists( 'enabled', $data ) ) {
			if ( rest_sanitize_boolean( $data['enabled'] ) ) {
				delete_post_meta( $block_id, self::META_ENABLED );
			} else {
				update_post_meta( $block_id, self::META_ENABLED, '0' );
			}
			$changed = true;
		}
		foreach ( array( 'show_from' => self::META_FROM, 'show_until' => self::META_UNTIL ) as $field => $meta ) {
			if ( ! array_key_exists( $field, $data ) ) {
				continue;
			}
			$changed = true;
			if ( $times[ $field ] ) {
				update_post_meta( $block_id, $meta, $times[ $field ] );
			} else {
				delete_post_meta( $block_id, $meta );
			}
		}

		if ( $changed ) {
			unset( $this->visible_cache[ (int) $block_id ] );
			$this->schedule_purges( $block_id, $from, $until );
			$this->purge_page_cache();
		}
		return true;
	}

	/**
	 * Chặn [block id="..."] khi block đang tắt / ngoài giờ.
	 *
	 * @param false|string $return
	 * @param string       $tag
	 * @param array|string $attr
	 * @return false|string
	 */
	public function filter_block_shortcode( $return, $tag, $attr ) {
		if ( 'block' !== $tag || false !== $return || ! is_array( $attr ) || empty( $attr['id'] ) ) {
			return $return;
		}
		$block_id = function_exists( 'flatsome_get_block_id' ) ? (int) flatsome_get_block_id( $attr['id'] ) : 0;
		if ( ! $block_id || $this->is_visible( $block_id ) ) {
			return $return;
		}

		if ( current_user_can( 'edit_pages' ) && ! is_feed() ) {
			return $this->placeholder( $block_id );
		}
		return '';
	}

	/**
	 * Xoá cache trang của các plugin cache phổ biến (block có thể nằm ở bất kỳ trang nào).
	 */
	public function purge_page_cache() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
		}
		do_action( 'litespeed_purge_all' );
		do_action( 'fbm/purge_page_cache' );
	}

	private function schedule_purges( $block_id, $from, $until ) {
		wp_clear_scheduled_hook( self::CRON_HOOK, array( (int) $block_id ) );
		foreach ( array( $from, $until ) as $ts ) {
			if ( $ts && $ts > time() ) {
				wp_schedule_single_event( $ts + 1, self::CRON_HOOK, array( (int) $block_id ) );
			}
		}
	}

	private function placeholder( $block_id ) {
		$state = $this->state( $block_id );
		$title = get_the_title( $block_id );
		$fmt   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		switch ( $state['state'] ) {
			case 'pending':
				$msg = sprintf( __( 'Sẽ hiện lúc %s', 'flatsome-block-manager' ), wp_date( $fmt, strtotime( $state['from'] ) ) );
				break;
			case 'expired':
				$msg = sprintf( __( 'Đã hết hạn lúc %s', 'flatsome-block-manager' ), wp_date( $fmt, strtotime( $state['until'] ) ) );
				break;
			default:
				$msg = __( 'Block đang tắt', 'flatsome-block-manager' );
		}

		$link = admin_url( 'admin.php?page=fbm#/blocks/' . $block_id );
		return sprintf(
			'<div class="fbm-block-off" style="border:1px dashed #c3c4c7;padding:10px 14px;margin:6px 0;font:13px/1.4 -apple-system,Segoe UI,Roboto,sans-serif;color:#50575e;background:repeating-linear-gradient(45deg,transparent,transparent 6px,rgba(0,0,0,.025) 6px,rgba(0,0,0,.025) 12px)">%1$s: <strong>%2$s</strong> · <a href="%3$s">%4$s</a> <small style="opacity:.7">(%5$s)</small></div>',
			esc_html( $msg ),
			esc_html( $title ),
			esc_url( $link ),
			esc_html__( 'Quản lý', 'flatsome-block-manager' ),
			esc_html__( 'chỉ admin thấy khung này', 'flatsome-block-manager' )
		);
	}

	/**
	 * Nhận ISO 8601 có múi giờ, hoặc "Y-m-d H:i" theo múi giờ site.
	 */
	private function parse_time( $value ) {
		if ( is_numeric( $value ) ) {
			return (int) $value;
		}
		try {
			$dt = new \DateTimeImmutable( (string) $value, wp_timezone() );
			return $dt->getTimestamp();
		} catch ( \Exception $e ) {
			return 0;
		}
	}
}
