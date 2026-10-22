<?php
/**
 * "Block Manager" menu and the 3-tab React app (build/admin.js).
 *
 * @package FBM
 */

namespace FBM\Admin;

defined( 'ABSPATH' ) || exit;

final class Page {

	const SLUG = 'fbm';

	/** @var string */
	private $hook = '';

	public function boot() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FBM_FILE ), array( $this, 'action_links' ) );
	}

	public function menu() {
		// Position 59: right below the Flatsome menu, as in v1.
		$this->hook = (string) add_menu_page(
			__( 'Block Manager', 'flatsome-block-manager' ),
			__( 'Block Manager', 'flatsome-block-manager' ),
			'edit_pages',
			self::SLUG,
			array( $this, 'render' ),
			'dashicons-screenoptions',
			59
		);
	}

	public function render() {
		echo '<div class="wrap fbm-wrap"><div id="fbm-app" class="fbm-app"><p>' . esc_html__( 'Loading Block Manager…', 'flatsome-block-manager' ) . '</p></div>';
		echo '<noscript>' . esc_html__( 'Block Manager requires JavaScript.', 'flatsome-block-manager' ) . '</noscript></div>';
	}

	public function enqueue( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( $screen && 'blocks' === $screen->post_type ) {
			wp_add_inline_script(
				'common',
				'jQuery(function($){
					$(document).on("dblclick", "textarea, code", function(e){
						var val = $(this).val() || $(this).text() || "";
						if (val.indexOf("[block id=") !== -1) {
							var text = val.trim();
							var el = $(this);
							var copy = function(){
								var orig = el.css("background-color");
								el.css({"background-color":"#c6e1c6","transition":"background-color 0.2s"});
								setTimeout(function(){ el.css("background-color", orig); }, 1000);
							};
							if (navigator.clipboard && navigator.clipboard.writeText) {
								navigator.clipboard.writeText(text).then(copy).catch(copy);
							} else {
								var ta = document.createElement("textarea");
								ta.value = text;
								document.body.appendChild(ta);
								ta.select();
								document.execCommand("copy");
								document.body.removeChild(ta);
								copy();
							}
						}
					});
				});'
			);
		}

		if ( $hook !== $this->hook ) {
			return;
		}
		$asset_file = FBM_DIR . 'build/admin.asset.php';
		if ( ! is_readable( $asset_file ) ) {
			return;
		}
		$asset = require $asset_file;

		wp_enqueue_script( 'fbm-admin', FBM_URL . 'build/admin.js', $asset['dependencies'], $asset['version'], true );
		wp_enqueue_style( 'wp-components' );
		$css = is_rtl() ? 'build/style-admin-rtl.css' : 'build/style-admin.css';
		if ( is_readable( FBM_DIR . $css ) ) {
			wp_enqueue_style( 'fbm-admin', FBM_URL . $css, array( 'wp-components' ), $asset['version'] );
		}

		wp_add_inline_script(
			'fbm-admin',
			'window.fbmAdmin = ' . wp_json_encode(
				array(
					'version'      => FBM_VERSION,
					'canManage'    => current_user_can( 'manage_options' ),
					'uxBuilderUrl' => self::ux_builder_url(),
					'blocksUrl'    => admin_url( 'edit.php?post_type=blocks' ),
				)
			) . ';',
			'before'
		);
	}

	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ), esc_html__( 'Open Block Manager', 'flatsome-block-manager' ) )
		);
		return $links;
	}

	/**
	 * Link that opens UX Builder on an existing page, so Catalog can record the element list.
	 */
	public static function ux_builder_url() {
		$ids = get_posts(
			array(
				'post_type'   => 'page',
				'post_status' => array( 'publish', 'draft' ),
				'numberposts' => 1,
				'fields'      => 'ids',
			)
		);
		if ( ! $ids ) {
			return admin_url( 'edit.php?post_type=page' );
		}
		return admin_url( 'post.php?post=' . (int) $ids[0] . '&action=edit&app=uxbuilder&type=editor' );
	}
}
