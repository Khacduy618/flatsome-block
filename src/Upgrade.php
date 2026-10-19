<?php
/**
 * Nâng cấp từ v1: chuyển trạng thái UX Block, dọn dữ liệu của trình viết code, giữ nội dung cũ không vỡ.
 *
 * - Block v1 đang tắt (hoặc đang bị hẹn giờ giấu) → Tắt của v2. Hẹn giờ bị bỏ.
 * - Element v1 (CPT fbm_element, bảng phiên bản, file trong uploads) bị xoá.
 * - Tag v1 không còn: khách thấy rỗng, người biên tập thấy khung nét đứt.
 * - [fbm_product_slider] chạy tiếp bằng fbm_products (elements/products/alias-v1.php).
 *
 * @package FBM
 */

namespace FBM;

use FBM\Usage\UsageIndex;

defined( 'ABSPATH' ) || exit;

final class Upgrade {

	const VERSION_OPTION = 'fbm_version';

	/** array{tags: string[], used: array<string,int>, dismissed: bool} */
	const REPORT = 'fbm_v1_report';

	/** Tag mẫu của v1 không đi kèm element nào sau nâng cấp. */
	const V1_STARTER_TAGS = array( 'fbm_hello' );

	const SLIDER_TAG = 'fbm_product_slider';

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 20 );
		add_action( 'admin_notices', array( __CLASS__, 'notice' ) );
		add_action( 'admin_post_fbm_v1_dismiss', array( __CLASS__, 'dismiss' ) );
	}

	public static function maybe_run() {
		if ( version_compare( (string) get_option( self::VERSION_OPTION, '0' ), '2.0.0', '<' ) ) {
			self::run();
		}
	}

	public static function run() {
		$v1_tags = self::v1_tags();
		self::record_report( $v1_tags );
		self::migrate_block_states();
		self::remove_v1_elements();
		update_option( self::VERSION_OPTION, FBM_VERSION, true );
		self::register_shortcodes();
	}

	/**
	 * Tag của element v1: registry (bản Live) và meta _fbm_tag (cả bản nháp).
	 *
	 * @return string[]
	 */
	private static function v1_tags() {
		global $wpdb;
		$registry = get_option( 'fbm_elements_registry', array() );
		$tags     = is_array( $registry ) ? array_keys( $registry ) : array();
		$meta     = $wpdb->get_col( "SELECT pm.meta_value FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'fbm_element' AND pm.meta_key = '_fbm_tag'" ); // phpcs:ignore
		$tags     = array_merge( $tags, $meta, array( self::SLIDER_TAG ) );
		return array_values( array_unique( array_filter( array_map( 'strval', $tags ), array( __CLASS__, 'is_tag' ) ) ) );
	}

	private static function is_tag( $tag ) {
		return (bool) preg_match( '/^[a-z0-9_-]{1,64}$/i', $tag );
	}

	/**
	 * Tag nào còn được v2 cung cấp thì không cần khung "đang tắt".
	 *
	 * @param string[] $v1_tags
	 */
	private static function record_report( array $v1_tags ) {
		$registry = Plugin::get( 'registry' );
		$v2_tags  = $registry ? array_keys( $registry->all() ) : array();
		$v2_tags[] = self::SLIDER_TAG;

		delete_transient( UsageIndex::ELEMENT_CACHE );
		$usage  = Plugin::get( 'usage' );
		$counts = $usage ? $usage->element_counts() : array();

		$used = array();
		foreach ( $v1_tags as $tag ) {
			if ( ! empty( $counts[ $tag ] ) ) {
				$used[ $tag ] = (int) $counts[ $tag ];
			}
		}

		$old  = get_option( self::REPORT, array() );
		$prev = isset( $old['tags'] ) && is_array( $old['tags'] ) ? $old['tags'] : array();
		update_option(
			self::REPORT,
			array(
				'tags'      => array_values( array_unique( array_merge( $prev, array_diff( $v1_tags, $v2_tags ) ) ) ),
				'used'      => $used,
				'dismissed' => ! $used,
			),
			true
		);
	}

	private static function migrate_block_states() {
		global $wpdb;
		$now = time();
		$ids = $wpdb->get_col( "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm JOIN {$wpdb->posts} p ON p.ID = pm.post_id WHERE p.post_type = 'blocks' AND pm.meta_key IN ('_fbm_enabled','_fbm_show_from','_fbm_show_until')" ); // phpcs:ignore
		foreach ( $ids as $id ) {
			$id      = (int) $id;
			$enabled = '0' !== get_post_meta( $id, '_fbm_enabled', true );
			$from    = (int) get_post_meta( $id, '_fbm_show_from', true );
			$until   = (int) get_post_meta( $id, '_fbm_show_until', true );
			// Giữ đúng thứ khách đang thấy lúc nâng cấp: block đang bị giấu (tắt hoặc ngoài khung giờ) thành Tắt.
			$hidden_now = ! $enabled || ( $from && $from > $now ) || ( $until && $until <= $now );
			if ( $hidden_now ) {
				update_post_meta( $id, BlockStates::META, 'off' );
			}
		}
		foreach ( array( '_fbm_enabled', '_fbm_show_from', '_fbm_show_until' ) as $key ) {
			delete_post_meta_by_key( $key );
		}
		wp_unschedule_hook( 'fbm_visibility_boundary' );
		wp_unschedule_hook( 'fbm_cleanup_sandbox' );
	}

	private static function remove_v1_elements() {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'fbm_element' ) );
		foreach ( $ids as $id ) {
			wp_delete_post( (int) $id, true );
		}
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fbm_element_versions" ); // phpcs:ignore
		delete_option( 'fbm_settings' );
		delete_option( 'fbm_elements_registry' );

		$uploads = wp_upload_dir( null, false );
		$dir     = trailingslashit( $uploads['basedir'] ) . 'fbm-elements';
		if ( is_dir( $dir ) ) {
			foreach ( array_merge( (array) glob( $dir . '/*' ), array( $dir . '/.htaccess' ) ) as $file ) {
				if ( is_file( $file ) ) {
					wp_delete_file( $file );
				}
			}
			@rmdir( $dir ); // phpcs:ignore
		}
	}

	public static function register_shortcodes() {
		if ( ! shortcode_exists( self::SLIDER_TAG ) ) {
			add_shortcode(
				self::SLIDER_TAG,
				static function ( $atts ) {
					return (string) include FBM_DIR . 'elements/products/alias-v1.php';
				}
			);
		}
		$report = get_option( self::REPORT, array() );
		$tags   = array_merge( self::V1_STARTER_TAGS, isset( $report['tags'] ) && is_array( $report['tags'] ) ? $report['tags'] : array() );
		foreach ( $tags as $tag ) {
			if ( self::is_tag( $tag ) && ! shortcode_exists( $tag ) ) {
				add_shortcode(
					$tag,
					static function () use ( $tag ) {
						return Placeholder::off( $tag . ' (v1)' );
					}
				);
			}
		}
	}

	/**
	 * Bảng ánh xạ att của [fbm_product_slider] v1 sang [fbm_products].
	 * Bỏ: kiểu thẻ, màu, bo góc, tỷ lệ ảnh, show_*; title/link hiển thị bằng [title] ở alias.
	 *
	 * @param array $v1
	 * @return array<string, string>
	 */
	public static function map_v1_slider_atts( array $v1 ) {
		$rename = array(
			'limit'       => 'products',
			'per_view'    => 'columns',
			'per_view_md' => 'columns__md',
			'per_view_sm' => 'columns__sm',
			'dots'        => 'slider_bullets',
			'loop'        => 'infinitive',
		);
		$keep   = array( 'source', 'ids', 'cat', 'tags', 'offset', 'orderby', 'order', 'exclude', 'price_min', 'price_max', 'in_stock' );

		$out = array();
		foreach ( $v1 as $key => $value ) {
			$value = (string) $value;
			if ( isset( $rename[ $key ] ) ) {
				$out[ $rename[ $key ] ] = $value;
			} elseif ( in_array( $key, $keep, true ) ) {
				$out[ $key ] = $value;
			} elseif ( 'autoplay' === $key ) {
				$ms                = isset( $v1['autoplay_ms'] ) && (int) $v1['autoplay_ms'] > 0 ? (string) (int) $v1['autoplay_ms'] : '4000';
				$out['auto_slide'] = 'true' === $value ? $ms : 'false';
			}
		}
		$out['type'] = 'slider';
		return $out;
	}

	public static function notice_html() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return '';
		}
		$report = get_option( self::REPORT, array() );
		if ( empty( $report['used'] ) || ! empty( $report['dismissed'] ) ) {
			return '';
		}
		$items = array();
		foreach ( $report['used'] as $tag => $count ) {
			if ( self::SLIDER_TAG === $tag ) {
				/* translators: 1: v1 tag, 2: number of places */
				$text = __( '[%1$s] (%2$d nơi) đã chuyển sang element Products [fbm_products]. Kiểu thẻ, màu và bo góc của v1 không còn.', 'flatsome-block-manager' );
			} elseif ( in_array( $tag, (array) $report['tags'], true ) ) {
				/* translators: 1: v1 tag, 2: number of places */
				$text = __( '[%1$s] (%2$d nơi) không còn trong v2: khách thấy trống, người biên tập thấy khung nét đứt.', 'flatsome-block-manager' );
			} else {
				/* translators: 1: v1 tag, 2: number of places */
				$text = __( '[%1$s] (%2$d nơi) vẫn chạy bằng element v2 cùng tên; màu tuỳ chỉnh của v1 bị bỏ qua.', 'flatsome-block-manager' );
			}
			$items[] = '<li>' . esc_html( sprintf( $text, $tag, $count ) ) . '</li>';
		}
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=fbm_v1_dismiss' ), 'fbm_v1_dismiss' );
		return '<div class="notice notice-warning"><p><strong>' . esc_html__( 'Block Manager 2.0: nội dung còn dùng element của v1', 'flatsome-block-manager' ) . '</strong></p><ul style="list-style:disc;padding-left:20px">' . implode( '', $items ) . '</ul><p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Đã hiểu, ẩn thông báo', 'flatsome-block-manager' ) . '</a></p></div>';
	}

	public static function notice() {
		echo self::notice_html(); // phpcs:ignore WordPress.Security.EscapeOutput -- đã escape trong notice_html().
	}

	public static function dismiss() {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'Không đủ quyền.', 'flatsome-block-manager' ), 403 );
		}
		check_admin_referer( 'fbm_v1_dismiss' );
		$report              = get_option( self::REPORT, array() );
		$report              = is_array( $report ) ? $report : array();
		$report['dismissed'] = true;
		update_option( self::REPORT, $report, true );
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
}
