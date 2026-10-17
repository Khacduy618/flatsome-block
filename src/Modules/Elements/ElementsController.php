<?php
/**
 * REST: /fbm/v1/elements...
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

use FBM\Core\RestController;

defined( 'ABSPATH' ) || exit;

final class ElementsController extends RestController {

	/** @var ElementService */
	private $service;

	public function __construct( ElementService $service ) {
		$this->service = $service;
	}

	public function register_routes() {
		$perm = array( $this, 'perm_code' );

		$this->route(
			'/elements',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						return $this->respond( $this->service->list_elements() );
					},
					'permission_callback' => $perm,
				),
				array(
					'methods'             => 'POST',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->create( (array) $r->get_json_params() + $r->get_params() ), 201 );
					},
					'permission_callback' => $perm,
				),
			)
		);

		$this->route(
			'/elements/starters',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					return $this->respond( Starters::all() );
				},
				'permission_callback' => $perm,
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->get( (int) $r['id'] ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => 'PUT, PATCH',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->update( (int) $r['id'], (array) $r->get_json_params() ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->delete( (int) $r['id'] ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/lint',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					$body    = (array) $r->get_json_params();
					$payload = isset( $body['payload'] ) && is_array( $body['payload'] ) ? $body['payload'] : null;
					return $this->respond( $this->service->lint( (int) $r['id'], $payload ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/test',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					$sample = $r['sample'];
					return $this->respond( $this->service->test( (int) $r['id'], null === $sample ? null : (string) $sample ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/publish',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->publish( (int) $r['id'], (bool) $r['force_untested'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg() + array(
					'force_untested' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/rollback',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->rollback( (int) $r['id'], (int) $r['version'], (bool) $r['copy_to_draft'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg() + array(
					'version'       => array(
						'type'     => 'integer',
						'required' => true,
					),
					'copy_to_draft' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/state',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->set_state( (int) $r['id'], (string) $r['state'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg() + array(
					'state' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'active', 'disabled' ),
					),
				),
			)
		);

		$this->route(
			'/elements/(?P<id>\d+)/versions/(?P<version>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->version( (int) $r['id'], (int) $r['version'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);
	}
}
