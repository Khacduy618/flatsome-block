<?php
/**
 * Gutenberg:
 * - fbm/ux-block: chèn một UX Block của Flatsome (chọn từ danh sách, có preview, tôn trọng bật/tắt).
 * - fbm/<tag>: mỗi element thành một dynamic block. Editor tự dựng từ schema, không cần build riêng.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Adapters;

use FBM\Modules\Elements\Compiler;
use FBM\Modules\Elements\Registry;
use FBM\Modules\Elements\Renderer;
use FBM\Modules\Elements\Schema;

defined( 'ABSPATH' ) || exit;

final class GutenbergAdapter {

	const CATEGORY = 'fbm-elements';
	const SCRIPT   = 'fbm-gutenberg';

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

	/**
	 * Tag shortcode -> tên block. Bỏ tiền tố "fbm_" để tránh "fbm/fbm-...".
	 * fbm_before_after -> fbm/before-after, hero_countdown -> fbm/hero-countdown.
	 */
	public static function block_name( $tag ) {
		return 'fbm/' . str_replace( '_', '-', (string) preg_replace( '/^fbm_/', '', (string) $tag ) );
	}

	public function boot() {
		add_action( 'init', array( $this, 'register' ), 30 );
		add_filter( 'block_categories_all', array( $this, 'category' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'editor_data' ) );
	}

	public function category( $categories ) {
		$categories[] = array(
			'slug'  => self::CATEGORY,
			'title' => __( 'Flatsome Elements', 'flatsome-block-manager' ),
			'icon'  => null,
		);
		return $categories;
	}

	public function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		$asset_file = FBM_DIR . 'build/gutenberg.asset.php';
		$asset      = is_readable( $asset_file ) ? require $asset_file : array(
			'dependencies' => array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-server-side-render', 'wp-api-fetch' ),
			'version'      => FBM_VERSION,
		);
		wp_register_script( self::SCRIPT, FBM_URL . 'build/gutenberg.js', $asset['dependencies'], $asset['version'], true );

		register_block_type(
			'fbm/ux-block',
			array(
				'api_version'           => 3,
				'title'                 => __( 'UX Block (Flatsome)', 'flatsome-block-manager' ),
				'category'              => self::CATEGORY,
				'editor_script_handles' => array( self::SCRIPT ),
				'attributes'            => array(
					'id'        => array(
						'type'    => 'string',
						'default' => '',
					),
					'className' => array( 'type' => 'string' ),
				),
				'render_callback'       => static function ( $attributes ) {
					if ( empty( $attributes['id'] ) ) {
						return '';
					}
					$html = do_shortcode( sprintf( '[block id="%s"]', esc_attr( $attributes['id'] ) ) );
					return empty( $attributes['className'] ) ? $html : sprintf( '<div class="%s">%s</div>', esc_attr( $attributes['className'] ), $html );
				},
			)
		);

		foreach ( $this->registry->all() as $tag => $entry ) {
			if ( ! in_array( 'gutenberg', (array) $entry['targets'], true ) ) {
				continue;
			}
			$options = isset( $entry['schema']['options'] ) ? (array) $entry['schema']['options'] : array();
			$args    = array(
				'api_version'           => 3,
				'title'                 => $entry['name'],
				'category'              => self::CATEGORY,
				'editor_script_handles' => array( self::SCRIPT ),
				'attributes'            => Schema::gutenberg_attributes( $options ),
				'render_callback'       => function ( $attributes, $content ) use ( $tag, $entry ) {
					$atts = (array) $attributes;
					if ( ! empty( $atts['className'] ) ) {
						$atts['class'] = trim( ( isset( $atts['class'] ) ? $atts['class'] : '' ) . ' ' . $atts['className'] );
					}
					unset( $atts['className'] );
					return $this->renderer->render( $tag, $atts, $entry['container'] ? $content : '' );
				},
			);

			// Đăng ký CSS/JS của element để Gutenberg nạp vào iframe editor và frontend.
			$handle = 'fbm-el-' . $tag;
			if ( ! empty( $entry['files']['css'] ) ) {
				wp_register_style( $handle, $this->compiler->url( $entry['files']['css'] ), array(), $entry['hash'] );
				$args['style_handles'] = array( $handle );
			}
			if ( ! empty( $entry['files']['js'] ) ) {
				wp_register_script( $handle, $this->compiler->url( $entry['files']['js'] ), array( 'fbm-runtime' ), $entry['hash'], true );
				$args['script_handles'] = array( $handle );
			}

			register_block_type( self::block_name( $tag ), apply_filters( 'fbm/gutenberg_args', $args, $entry ) );
		}
	}

	/**
	 * Dữ liệu cho build/gutenberg.js: mô tả control để dựng InspectorControls.
	 */
	public function editor_data() {
		$elements = array();
		foreach ( $this->registry->all() as $tag => $entry ) {
			if ( ! in_array( 'gutenberg', (array) $entry['targets'], true ) ) {
				continue;
			}
			$schema  = (array) $entry['schema'];
			$options = isset( $schema['options'] ) ? (array) $schema['options'] : array();
			$allow   = isset( $schema['allow'] ) ? array_map( array( __CLASS__, 'block_name' ), (array) $schema['allow'] ) : array();

			$elements[] = array(
				'name'       => self::block_name( $tag ),
				'tag'        => $tag,
				'title'      => $entry['name'],
				'state'      => $this->renderer->is_active( $entry ) ? 'active' : $entry['state'],
				'container'  => (bool) $entry['container'],
				'allow'      => $allow,
				'parent'     => isset( $schema['require'] ) ? array_map( array( __CLASS__, 'block_name' ), (array) $schema['require'] ) : array(),
				'controls'   => Schema::gutenberg_controls( $options ),
				'variations' => $this->variations( $tag, $schema, $allow ),
				'manageUrl'  => admin_url( 'admin.php?page=fbm#/elements/' . $entry['id'] ),
			);
		}

		wp_add_inline_script(
			self::SCRIPT,
			'window.fbmGutenberg = ' . wp_json_encode(
				array(
					'elements'  => $elements,
					'manageUrl' => admin_url( 'admin.php?page=fbm' ),
				)
			) . ';',
			'before'
		);
	}

	/**
	 * presets của UX Builder -> block variations của Gutenberg.
	 */
	private function variations( $tag, array $schema, array $allow ) {
		if ( empty( $schema['presets'] ) || ! is_array( $schema['presets'] ) ) {
			return array();
		}
		$entries = $this->registry->all();
		$out     = array();
		foreach ( $schema['presets'] as $i => $preset ) {
			if ( empty( $preset['content'] ) || ! is_string( $preset['content'] ) ) {
				continue;
			}
			foreach ( Schema::preset_tags( $preset['content'] ) as $t ) {
				if ( $t !== $tag && ! ( isset( $entries[ $t ] ) && in_array( 'gutenberg', (array) $entries[ $t ]['targets'], true ) ) ) {
					continue 2; // Block con chưa có trong Gutenberg.
				}
			}
			$attrs = array();
			if ( preg_match( '/\[' . preg_quote( $tag, '/' ) . '(\s[^\]]*)?\]/', $preset['content'], $m ) && ! empty( $m[1] ) ) {
				$attrs = (array) shortcode_parse_atts( trim( $m[1] ) );
			}
			$inner = array();
			if ( $allow && isset( $schema['allow'] ) ) {
				foreach ( (array) $schema['allow'] as $child ) {
					if ( preg_match_all( '/\[' . preg_quote( $child, '/' ) . '(\s[^\]]*)?\]/', $preset['content'], $cm, PREG_SET_ORDER ) ) {
						foreach ( $cm as $c ) {
							$inner[] = array( self::block_name( $child ), ! empty( $c[1] ) ? (array) shortcode_parse_atts( trim( $c[1] ) ) : (object) array() );
						}
					}
				}
			}
			$out[] = array(
				'name'        => 'preset-' . $i,
				'title'       => isset( $preset['name'] ) ? (string) $preset['name'] : 'Preset ' . ( $i + 1 ),
				'attributes'  => (object) $attrs,
				'innerBlocks' => $inner,
				'isDefault'   => 0 === $i,
				'scope'       => array( 'inserter' ),
			);
		}
		return $out;
	}
}
