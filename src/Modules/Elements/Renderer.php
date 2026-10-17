<?php
/**
 * Render element. Dùng chung cho shortcode (frontend + UX Builder) và Gutenberg.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

use FBM\Core\Settings;
use FBM\Modules\Elements\Sandbox\CircuitBreaker;

defined( 'ABSPATH' ) || exit;

final class Renderer {

	/** @var Registry */
	private $registry;

	/** @var Compiler */
	private $compiler;

	/** @var Settings */
	private $settings;

	/** @var callable function ( int $id ): bool  Tự biên dịch lại khi thiếu file. */
	private $recompile;

	/** @var array<string, \Closure> */
	private $loaded = array();

	private $counter = 0;

	/** @var array<int, string> Lỗi đã ghi trong request này (tránh ghi DB lặp). */
	private $logged = array();

	public function __construct( Registry $registry, Compiler $compiler, Settings $settings, callable $recompile ) {
		$this->registry  = $registry;
		$this->compiler  = $compiler;
		$this->settings  = $settings;
		$this->recompile = $recompile;
	}

	public function is_active( array $entry ) {
		return 'active' === $entry['state'] && ! $this->settings->safe_mode();
	}

	/**
	 * @param array|string $atts
	 */
	public function render( $tag, $atts, $content = '' ) {
		$entry = $this->registry->get( $tag );
		if ( ! $entry ) {
			return '';
		}
		if ( ! $this->is_active( $entry ) ) {
			return $this->inactive_notice( $entry );
		}

		$defaults = Schema::defaults( isset( $entry['schema']['options'] ) ? $entry['schema']['options'] : array() );
		$atts     = shortcode_atts( $defaults, is_array( $atts ) ? $atts : array(), $tag );
		$content  = null === $content ? '' : (string) $content;

		$this->enqueue_assets( $entry );

		$fn = $this->load( $entry );
		if ( ! $fn ) {
			return $this->error_box( $entry, __( 'Không tìm thấy file đã biên dịch.', 'flatsome-block-manager' ) );
		}

		$this->counter++;
		$fbm_id = 'fbm-' . str_replace( '_', '-', $tag ) . '-' . $this->counter;
		$level  = ob_get_level();
		$html   = '';

		CircuitBreaker::enter( $entry['id'], $tag );
		ob_start();
		try {
			$fn( $atts, $content, $tag, $fbm_id );
			$html = self::collect( $level );
		} catch ( \Throwable $e ) {
			self::collect( $level );
			$this->log_error( $entry, $e );
			$html = $this->error_box( $entry, CircuitBreaker::clean_message( $e->getMessage() ), $this->template_line( $entry, $e ) );
		} finally {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			CircuitBreaker::leave();
		}

		return $html;
	}

	public function enqueue_assets( array $entry ) {
		$handle = 'fbm-el-' . $entry['tag'];
		if ( ! empty( $entry['files']['css'] ) ) {
			wp_enqueue_style( $handle, $this->compiler->url( $entry['files']['css'] ), array(), $entry['hash'] );
		}
		if ( ! empty( $entry['files']['js'] ) ) {
			wp_enqueue_script( 'fbm-runtime' );
			wp_enqueue_script( $handle, $this->compiler->url( $entry['files']['js'] ), array( 'fbm-runtime' ), $entry['hash'], true );
		}
	}

	/**
	 * Gom output từ các buffer mở sau $level (template có thể tự mở buffer).
	 */
	private static function collect( $level ) {
		$html = '';
		while ( ob_get_level() > $level ) {
			$html = ob_get_clean() . $html;
		}
		return $html;
	}

	/**
	 * @return \Closure|null
	 */
	private function load( array $entry ) {
		$file = $entry['files']['php'];
		if ( isset( $this->loaded[ $file ] ) ) {
			return $this->loaded[ $file ];
		}
		$path = $this->compiler->path( $file );
		if ( ! is_readable( $path ) ) {
			// Ví dụ: chuyển host chỉ copy DB, hoặc thư mục uploads bị dọn.
			call_user_func( $this->recompile, $entry['id'] );
			clearstatcache( true, $path );
			if ( ! is_readable( $path ) ) {
				return null;
			}
		}
		$fn                   = include $path;
		$this->loaded[ $file ] = $fn instanceof \Closure ? $fn : null;
		return $this->loaded[ $file ];
	}

	private function template_line( array $entry, \Throwable $e ) {
		return basename( $e->getFile() ) === $entry['files']['php'] ? $e->getLine() : 0;
	}

	private function log_error( array $entry, \Throwable $e ) {
		$message = CircuitBreaker::clean_message( $e->getMessage() );
		if ( isset( $this->logged[ $entry['id'] ] ) ) {
			return;
		}
		$this->logged[ $entry['id'] ] = $message;

		$last = get_post_meta( $entry['id'], ElementRepository::META_LAST_ERROR, true );
		if ( is_array( $last ) && $last['message'] === $message && time() - (int) $last['at'] < MINUTE_IN_SECONDS ) {
			return;
		}
		update_post_meta(
			$entry['id'],
			ElementRepository::META_LAST_ERROR,
			array(
				'message' => $message,
				'class'   => get_class( $e ),
				'line'    => $this->template_line( $entry, $e ),
				'url'     => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', // phpcs:ignore
				'at'      => time(),
			)
		);
	}

	private function can_see_debug() {
		return current_user_can( 'edit_pages' );
	}

	private function inactive_notice( array $entry ) {
		if ( ! $this->can_see_debug() || is_feed() ) {
			return '';
		}
		if ( $this->settings->safe_mode() ) {
			$msg = __( 'Safe mode đang bật', 'flatsome-block-manager' );
		} elseif ( 'tripped' === $entry['state'] ) {
			$msg = __( 'Element đã bị ngắt do lỗi', 'flatsome-block-manager' );
		} else {
			$msg = __( 'Element đang tắt', 'flatsome-block-manager' );
		}
		return $this->box( '#c3c4c7', '#50575e', $msg, $entry, '' );
	}

	private function error_box( array $entry, $message, $line = 0 ) {
		if ( ! $this->can_see_debug() ) {
			return '<!-- fbm: element ' . esc_html( $entry['tag'] ) . ' error -->';
		}
		$detail = $message . ( $line ? sprintf( ' (template dòng %d)', $line ) : '' );
		return $this->box( '#d63638', '#8a1f20', __( 'Element lỗi', 'flatsome-block-manager' ), $entry, $detail );
	}

	private function box( $border, $color, $title, array $entry, $detail ) {
		$link = admin_url( 'admin.php?page=fbm#/elements/' . $entry['id'] );
		return sprintf(
			'<div class="fbm-element-notice" style="border:1px dashed %1$s;color:%2$s;padding:10px 14px;margin:6px 0;font:13px/1.45 -apple-system,Segoe UI,Roboto,sans-serif;background:#fff">%3$s: <strong>%4$s</strong> <code>[%5$s]</code>%6$s · <a href="%7$s">%8$s</a> <small style="opacity:.7">(%9$s)</small></div>',
			esc_attr( $border ),
			esc_attr( $color ),
			esc_html( $title ),
			esc_html( $entry['name'] ),
			esc_html( $entry['tag'] ),
			$detail ? '<br><code style="white-space:pre-wrap">' . esc_html( $detail ) . '</code>' : '',
			esc_url( $link ),
			esc_html__( 'Mở element', 'flatsome-block-manager' ),
			esc_html__( 'chỉ admin thấy khung này', 'flatsome-block-manager' )
		);
	}
}
