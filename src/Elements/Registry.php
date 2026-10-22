<?php
/**
 * Element viết sẵn trong plugin: mỗi element là một thư mục elements/<slug>/.
 *
 * - meta.php    → array( tag, name, category, container?, css?, js? ) — đọc ở mọi request.
 * - builder.php → args cho add_ux_builder_shortcode() — chỉ nạp khi UX Builder chạy; có sẵn $commons.
 * - render.php  → in HTML; nhận $atts, $content, $tag; tự shortcode_atts với $defaults của nó.
 *
 * Không có đường nào nạp code từ database hay từ admin.
 *
 * @package FBM
 */

namespace FBM\Elements;

defined( 'ABSPATH' ) || exit;

final class Registry {

	/** @var array<string, array>|null */
	private $all = null;

	public function boot() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'ux_builder_setup', array( $this, 'register_builder' ), 20 );
		add_action( 'ux_builder_enqueue_scripts', array( $this, 'enqueue_for_canvas' ) );
	}

	/**
	 * Canvas UX Builder chèn element qua AJAX nên không in được file nạp lúc render:
	 * nạp sẵn CSS/JS của mọi element vào iframe (context "content").
	 *
	 * @param string $context editor | content
	 */
	public function enqueue_for_canvas( $context ) {
		if ( 'content' !== $context ) {
			return;
		}
		foreach ( $this->all() as $el ) {
			$this->enqueue( $el );
			if ( $el['editor'] ) {
				// Script chỉ dành cho canvas; chạy sau script canvas của Flatsome (nơi đăng ký controller của element gốc).
				wp_enqueue_script(
					'fbm-' . $el['slug'] . '-editor',
					FBM_URL . 'elements/' . $el['slug'] . '/' . $el['editor'],
					wp_script_is( 'ux-builder-flatsome', 'registered' ) ? array( 'ux-builder-flatsome' ) : array(),
					FBM_VERSION,
					true
				);
			}
		}
	}

	/**
	 * @return array<string, array{tag:string, name:string, category:string, dir:string, slug:string, container:bool, css:string|null, js:string|null, editor:string|null, aliases:string[]}>
	 */
	public function all() {
		if ( null !== $this->all ) {
			return $this->all;
		}
		$this->all = array();
		foreach ( (array) glob( FBM_DIR . 'elements/*/meta.php' ) as $file ) {
			$meta = include $file;
			if ( ! is_array( $meta ) || empty( $meta['tag'] ) ) {
				continue;
			}
			$dir                       = dirname( $file );
			$this->all[ $meta['tag'] ] = array(
				'tag'       => (string) $meta['tag'],
				'name'      => isset( $meta['name'] ) ? (string) $meta['name'] : (string) $meta['tag'],
				'category'  => isset( $meta['category'] ) ? (string) $meta['category'] : 'Content',
				'dir'       => $dir,
				'slug'      => basename( $dir ),
				'container' => ! empty( $meta['container'] ),
				'css'       => isset( $meta['css'] ) ? (string) $meta['css'] : null,
				'js'        => isset( $meta['js'] ) ? (string) $meta['js'] : null,
				'editor'    => isset( $meta['editor'] ) ? (string) $meta['editor'] : null,
				'aliases'   => isset( $meta['aliases'] ) ? array_map( 'strval', (array) $meta['aliases'] ) : array(),
			);
		}
		return $this->all;
	}

	public function register_shortcodes() {
		foreach ( $this->all() as $tag => $el ) {
			// Old tags of a renamed element keep rendering saved content; only the new tag is in UX Builder.
			foreach ( array_merge( array( $tag ), $el['aliases'] ) as $shortcode ) {
				add_shortcode(
					$shortcode,
					function ( $atts, $content = '', $shortcode_tag = '' ) use ( $tag ) {
						return $this->render( $tag, $atts, (string) $content );
					}
				);
			}
		}
	}

	public function register_builder() {
		if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
			return;
		}
		$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
		foreach ( $this->all() as $tag => $el ) {
			$args = self::load_builder( $el['dir'] . '/builder.php', $commons );
			if ( ! is_array( $args ) ) {
				continue;
			}
			add_ux_builder_shortcode(
				$tag,
				$args + array(
					'name'      => $el['name'],
					'category'  => $el['category'],
					'thumbnail' => FBM_URL . 'assets/img/element.svg',
				)
			);
		}
	}

	/**
	 * @param array|string $atts
	 */
	public function render( $tag, $atts, $content = '' ) {
		$all = $this->all();
		if ( ! isset( $all[ $tag ] ) ) {
			return '';
		}
		$el   = $all[ $tag ];
		$atts = is_array( $atts ) ? $atts : array();
		$this->enqueue( $el );

		ob_start();
		self::load_render( $el['dir'] . '/render.php', $atts, $content, $tag );
		return (string) ob_get_clean();
	}

	private function enqueue( array $el ) {
		$handle = 'fbm-' . $el['slug'];
		if ( $el['css'] ) {
			wp_enqueue_style( $handle, FBM_URL . 'elements/' . $el['slug'] . '/' . $el['css'], array(), FBM_VERSION );
		}
		if ( $el['js'] ) {
			wp_enqueue_script(
				$handle,
				FBM_URL . 'elements/' . $el['slug'] . '/' . $el['js'],
				array( 'jquery' ),
				FBM_VERSION,
				array(
					'in_footer' => true,
					'strategy'  => 'defer',
				)
			);
		}
	}

	/** Phạm vi biến riêng cho builder.php: chỉ thấy $commons. */
	private static function load_builder( $file, $commons ) {
		return is_readable( $file ) ? include $file : null;
	}

	/** Phạm vi biến riêng cho render.php: chỉ thấy $atts, $content, $tag. */
	private static function load_render( $file, $atts, $content, $tag ) {
		include $file;
	}
}
