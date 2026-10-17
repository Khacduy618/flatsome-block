<?php
/**
 * Module Admin: menu "Block Manager" và nạp app React (build/admin.js).
 *
 * @package FBM
 */

namespace FBM\Modules\Admin;

use FBM\Core\Capabilities;
use FBM\Core\Container;
use FBM\Core\ModuleInterface;
use FBM\Plugin;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	const SLUG = 'fbm';

	/** @var string */
	private $hook = '';

	/** @var Container */
	private $c;

	public function id() {
		return 'admin';
	}

	public function register( Container $c ) {
	}

	public function boot( Container $c ) {
		$this->c = $c;
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( FBM_FILE ), array( $this, 'action_links' ) );
	}

	public function menu() {
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
		echo '<div class="wrap fbm-wrap"><div id="fbm-app" class="fbm-app"><p>' . esc_html__( 'Đang tải Block Manager…', 'flatsome-block-manager' ) . '</p></div>';
		echo '<noscript>' . esc_html__( 'Block Manager cần JavaScript.', 'flatsome-block-manager' ) . '</noscript></div>';
	}

	public function enqueue( $hook ) {
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

		$editors = array();
		foreach ( array(
			'php'  => 'application/x-httpd-php',
			'css'  => 'text/css',
			'js'   => 'text/javascript',
			'json' => 'application/json',
			'html' => 'text/html',
		) as $key => $type ) {
			$editors[ $key ] = wp_enqueue_code_editor(
				array(
					'type'       => $type,
					// Tắt linter của WP (HTMLHint báo nhầm "<?php" là lỗi): plugin có cổng kiểm tra riêng.
					'codemirror' => array(
						'indentUnit'   => 4,
						'tabSize'      => 4,
						'lineWrapping' => false,
						'lint'         => false,
						'gutters'      => array(),
					),
				)
			);
		}

		$settings = $this->c->get( 'settings' );
		$all      = $settings->all();
		$plugin   = Plugin::instance();

		wp_add_inline_script(
			'fbm-admin',
			'window.fbmAdmin = ' . wp_json_encode(
				array(
					'version'      => FBM_VERSION,
					'adminUrl'     => admin_url(),
					'siteUrl'      => home_url( '/' ),
					'restUrl'      => rest_url( 'fbm/v1/' ),
					'timezone'     => wp_timezone_string(),
					'uxBuilder'    => function_exists( 'ux_builder_edit_url' ),
					'blocksUrl'    => admin_url( 'edit.php?post_type=blocks' ),
					'safeMode'     => $settings->safe_mode(),
					'modules'      => array(
						'usage'    => $plugin->has_module( 'usage' ),
						'transfer' => $plugin->has_module( 'transfer' ),
						'elements' => $plugin->has_module( 'elements' ),
					),
					'user'         => array(
						'canEditCode'       => Capabilities::can_edit_code(),
						'canManageSettings' => Capabilities::can_manage_settings(),
					),
					'codeEditor'   => $editors,
					'runtimeUrl'   => FBM_URL . 'assets/runtime/fbm-runtime.js',
					'previewCss'   => is_readable( get_template_directory() . '/assets/css/flatsome.css' ) ? array( get_template_directory_uri() . '/assets/css/flatsome.css' ) : array(),
					'deleteOnUninstall' => $all['delete_on_uninstall'],
				)
			) . ';',
			'before'
		);
	}

	public function action_links( $links ) {
		array_unshift(
			$links,
			sprintf( '<a href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . self::SLUG ) ), esc_html__( 'Mở Block Manager', 'flatsome-block-manager' ) )
		);
		return $links;
	}
}
