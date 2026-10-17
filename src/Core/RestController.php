<?php
/**
 * Base cho REST controller: namespace chung + helper chuyển WP_Error.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

abstract class RestController {

	const NS = 'fbm/v1';

	abstract public function register_routes();

	protected function route( $path, array $endpoints ) {
		register_rest_route( self::NS, $path, $endpoints );
	}

	/**
	 * @param mixed $result
	 * @param int   $status
	 * @return \WP_REST_Response|\WP_Error
	 */
	protected function respond( $result, $status = 200 ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new \WP_REST_Response( $result, $status );
	}

	protected function id_arg() {
		return array(
			'id' => array(
				'type'     => 'integer',
				'required' => true,
			),
		);
	}

	public function perm_blocks() {
		return Capabilities::can_manage_blocks();
	}

	public function perm_code() {
		return Capabilities::can_edit_code();
	}

	public function perm_settings() {
		return Capabilities::can_manage_settings();
	}
}
