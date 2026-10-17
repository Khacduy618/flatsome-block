<?php
/**
 * Đăng ký element vào UX Builder của Flatsome.
 *
 * - Element thường: không có template canvas -> UX Builder gọi server render bằng shortcode.
 * - Element container: cần template canvas có <content></content> để đặt element con.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Adapters;

use FBM\Modules\Elements\Compiler;
use FBM\Modules\Elements\Registry;
use FBM\Modules\Elements\Renderer;
use FBM\Modules\Elements\Schema;

defined( 'ABSPATH' ) || exit;

final class UxBuilderAdapter {

	const PASS_THROUGH = array( 'info', 'presets', 'allow', 'allow_in', 'require', 'nested', 'wrap', 'priority', 'inline', 'overlay', 'hidden' );

	/** @var Registry */
	private $registry;

	/** @var Renderer */
	private $renderer;

	/** @var Compiler */
	private $compiler;

	public function __construct( Registry $registry, Renderer $renderer, Compiler $compiler ) {
		$this->registry = $registry;
		$this->renderer = $renderer;
		$this->compiler = $compiler;
	}

	public function boot() {
		add_action( 'ux_builder_setup', array( $this, 'register' ), 20 );
	}

	public function register() {
		if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
			return;
		}
		foreach ( $this->registry->all() as $tag => $entry ) {
			if ( ! in_array( 'uxbuilder', (array) $entry['targets'], true ) ) {
				continue;
			}
			add_ux_builder_shortcode( $tag, $this->args( $entry ) );
		}
	}

	public function args( array $entry ) {
		$schema = (array) $entry['schema'];
		$args   = array_intersect_key( $schema, array_flip( self::PASS_THROUGH ) );

		$name = $entry['name'];
		if ( ! $this->renderer->is_active( $entry ) ) {
			// Vẫn đăng ký để nội dung cũ không vỡ, nhưng ghi rõ trạng thái.
			$name .= ' ' . __( '(đang tắt)', 'flatsome-block-manager' );
		}

		if ( isset( $args['presets'] ) ) {
			$args['presets'] = $this->usable_presets( $entry['tag'], (array) $args['presets'] );
			if ( ! $args['presets'] ) {
				unset( $args['presets'] ); // Flatsome tự tạo preset mặc định [tag].
			}
		}

		$args['name']      = $name;
		$args['category']  = $entry['category'] ? $entry['category'] : 'Content';
		$args['thumbnail'] = FBM_URL . 'assets/img/element.svg';
		$args['options']   = Schema::resolved_options( isset( $schema['options'] ) ? (array) $schema['options'] : array() );

		if ( $entry['container'] ) {
			// Chỉ khai báo "type" cho container (Flatsome coi mọi element có khoá type là container).
			$args['type']     = 'container';
			$args['template'] = ! empty( $schema['builder_template'] )
				? (string) $schema['builder_template']
				: sprintf( '<div class="fbm-container fbm-container--%s {{ shortcode.options.class }}"><content></content></div>', esc_attr( $entry['tag'] ) );
		}

		$args['styles']  = array();
		$args['scripts'] = array();
		if ( ! empty( $entry['files']['css'] ) ) {
			$args['styles'][ 'fbm-el-' . $entry['tag'] ] = add_query_arg( 'ver', substr( $entry['hash'], 0, 8 ), $this->compiler->url( $entry['files']['css'] ) );
		}
		if ( ! empty( $entry['files']['js'] ) ) {
			$args['scripts']['fbm-runtime']               = FBM_URL . 'assets/runtime/fbm-runtime.js';
			$args['scripts'][ 'fbm-el-' . $entry['tag'] ] = add_query_arg( 'ver', substr( $entry['hash'], 0, 8 ), $this->compiler->url( $entry['files']['js'] ) );
		}

		return apply_filters( 'fbm/uxbuilder_args', $args, $entry );
	}

	/**
	 * UX Builder parse mọi tag trong preset mỗi khi mở; tag chưa đăng ký sinh ra PHP warning
	 * trong dữ liệu khởi động. Bỏ preset nhắc tới element con chưa publish / không bật UX Builder.
	 */
	private function usable_presets( $tag, array $presets ) {
		$entries = $this->registry->all();
		$known   = static function ( $t ) use ( $tag, $entries ) {
			if ( $t === $tag ) {
				return true;
			}
			if ( isset( $entries[ $t ] ) ) {
				return in_array( 'uxbuilder', (array) $entries[ $t ]['targets'], true );
			}
			return function_exists( 'ux_builder_shortcodes' ) && ux_builder_shortcodes()->exists( $t );
		};

		$out = array();
		foreach ( $presets as $preset ) {
			if ( ! is_array( $preset ) || empty( $preset['content'] ) || ! is_string( $preset['content'] ) ) {
				continue;
			}
			$tags = Schema::preset_tags( $preset['content'] );
			if ( count( array_filter( $tags, $known ) ) === count( $tags ) ) {
				$out[] = array(
					'name'    => isset( $preset['name'] ) ? (string) $preset['name'] : __( 'Mặc định', 'flatsome-block-manager' ),
					'content' => $preset['content'],
				);
			}
		}
		return $out;
	}
}
