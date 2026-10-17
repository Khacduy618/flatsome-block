<?php
/**
 * REST: /fbm/v1/transfer/export, /fbm/v1/transfer/import
 *
 * @package FBM
 */

namespace FBM\Modules\Transfer;

use FBM\Core\RestController;

defined( 'ABSPATH' ) || exit;

final class TransferController extends RestController {

	/** @var TransferService */
	private $service;

	public function __construct( TransferService $service ) {
		$this->service = $service;
	}

	public function register_routes() {
		$perm = array( $this, 'perm_blocks' );

		$this->route(
			'/transfer/export',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond(
						$this->service->export( (array) $r['block_ids'], (array) $r['element_ids'], (bool) $r['with_deps'] )
					);
				},
				'permission_callback' => $perm,
				'args'                => array(
					'block_ids'   => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'element_ids' => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
					'with_deps'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);

		$this->route(
			'/transfer/import',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					$body = (array) $r->get_json_params();
					return $this->respond(
						$this->service->import(
							$body['package'] ?? null,
							(string) ( $body['conflict'] ?? 'rename' ),
							! empty( $body['dry_run'] )
						)
					);
				},
				'permission_callback' => $perm,
			)
		);
	}
}
