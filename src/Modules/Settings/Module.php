<?php
/**
 * Module Settings: REST đọc/ghi cài đặt + thông tin môi trường cho UI.
 *
 * @package FBM
 */

namespace FBM\Modules\Settings;

use FBM\Core\Capabilities;
use FBM\Core\Container;
use FBM\Core\ModuleInterface;
use FBM\Core\RestController;
use FBM\Core\Settings;

defined( 'ABSPATH' ) || exit;

final class Module extends RestController implements ModuleInterface {

	/** @var Settings */
	private $settings;

	public function id() {
		return 'settings';
	}

	public function register( Container $c ) {
	}

	public function boot( Container $c ) {
		$this->settings = $c->get( 'settings' );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		$this->route(
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						return $this->respond( $this->payload() );
					},
					'permission_callback' => array( $this, 'perm_settings' ),
				),
				array(
					'methods'             => 'POST, PUT, PATCH',
					'callback'            => function ( \WP_REST_Request $r ) {
						$this->settings->update( (array) $r->get_json_params() );
						return $this->respond( $this->payload() );
					},
					'permission_callback' => array( $this, 'perm_settings' ),
				),
			)
		);
	}

	private function payload() {
		$theme = wp_get_theme( get_template() );
		return array(
			'settings' => $this->settings->all(),
			'info'     => array(
				'safe_mode_constant' => defined( 'FBM_SAFE_MODE' ),
				'safe_mode'          => $this->settings->safe_mode(),
				'disallow_file_edit' => defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT,
				'can_edit_code'      => Capabilities::can_edit_code(),
				'plugin_version'     => FBM_VERSION,
				'flatsome_version'   => $theme->get( 'Version' ),
				'wp_version'         => get_bloginfo( 'version' ),
				'php_version'        => PHP_VERSION,
				'rest_url'           => rest_url( RestController::NS . '/' ),
			),
		);
	}
}
