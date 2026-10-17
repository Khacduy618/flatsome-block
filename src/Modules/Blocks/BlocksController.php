<?php
/**
 * REST: /fbm/v1/blocks, /fbm/v1/block-categories
 *
 * @package FBM
 */

namespace FBM\Modules\Blocks;

use FBM\Core\RestController;

defined( 'ABSPATH' ) || exit;

final class BlocksController extends RestController {

	/** @var BlockService */
	private $service;

	public function __construct( BlockService $service ) {
		$this->service = $service;
	}

	public function register_routes() {
		$perm = array( $this, 'perm_blocks' );

		$this->route(
			'/blocks',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond(
							$this->service->list_blocks(
								array(
									'search'     => $r['search'],
									'category'   => $r['category'],
									'visibility' => $r['visibility'],
									'status'   => $r['status'],
									'page'     => $r['page'],
									'per_page' => $r['per_page'],
									'orderby'  => $r['orderby'],
									'order'    => $r['order'],
									'include'  => $r['include'],
								)
							)
						);
					},
					'permission_callback' => $perm,
					'args'                => array(
						'search'   => array( 'type' => 'string' ),
						'category' => array( 'type' => 'string' ),
						'visibility' => array( 'type' => 'string', 'enum' => array( '', 'on', 'off', 'scheduled', 'unused' ) ),
						'status'   => array( 'type' => 'string', 'enum' => array( '', 'any', 'publish', 'draft', 'pending', 'private', 'trash' ) ),
						'page'     => array( 'type' => 'integer', 'default' => 1 ),
						'per_page' => array( 'type' => 'integer', 'default' => 20 ),
						'orderby'  => array( 'type' => 'string', 'default' => 'modified' ),
						'order'    => array( 'type' => 'string', 'default' => 'desc' ),
						'include'  => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ),
					),
				),
				array(
					'methods'             => 'POST',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->create( $r->get_json_params() ?: $r->get_params() ), 201 );
					},
					'permission_callback' => $perm,
				),
			)
		);

		$this->route(
			'/blocks/(?P<id>\d+)',
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
						$data = $r->get_json_params() ?: $r->get_body_params();
						return $this->respond( $this->service->update( (int) $r['id'], (array) $data ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond(
							$this->service->delete( (int) $r['id'], (bool) $r['force'], (bool) $r['ignore_usage'] )
						);
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg() + array(
						'force'        => array( 'type' => 'boolean', 'default' => false ),
						'ignore_usage' => array( 'type' => 'boolean', 'default' => false ),
					),
				),
			)
		);

		$this->route(
			'/blocks/(?P<id>\d+)/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->duplicate( (int) $r['id'] ), 201 );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);

		$this->route(
			'/blocks/(?P<id>\d+)/restore',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->service->restore( (int) $r['id'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);

		$this->route(
			'/blocks/bulk',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond(
						$this->service->bulk(
							(string) $r['action'],
							(array) $r['ids'],
							array(
								'categories'   => (array) $r['categories'],
								'ignore_usage' => (bool) $r['ignore_usage'],
							)
						)
					);
				},
				'permission_callback' => $perm,
				'args'                => array(
					'action'       => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'trash', 'restore', 'delete', 'enable', 'disable', 'set_categories', 'add_categories' ),
					),
					'ids'          => array(
						'type'     => 'array',
						'required' => true,
						'items'    => array( 'type' => 'integer' ),
					),
					'categories'   => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'ignore_usage' => array( 'type' => 'boolean', 'default' => false ),
				),
			)
		);

		$this->route(
			'/block-categories',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => function () {
						return $this->respond( $this->service->categories() );
					},
					'permission_callback' => $perm,
				),
				array(
					'methods'             => 'POST',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->save_category( $r->get_params() ), 201 );
					},
					'permission_callback' => $perm,
				),
			)
		);

		$this->route(
			'/block-categories/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT, PATCH',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->save_category( $r->get_params(), (int) $r['id'] ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => function ( \WP_REST_Request $r ) {
						return $this->respond( $this->service->delete_category( (int) $r['id'] ) );
					},
					'permission_callback' => $perm,
					'args'                => $this->id_arg(),
				),
			)
		);
	}
}
