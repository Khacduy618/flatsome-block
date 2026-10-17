<?php
/**
 * REST: /fbm/v1/blocks/{id}/usage, /fbm/v1/usage/rebuild, /fbm/v1/usage/status
 *
 * @package FBM
 */

namespace FBM\Modules\Usage;

use FBM\Core\RestController;

defined( 'ABSPATH' ) || exit;

final class UsageController extends RestController {

	/** @var UsageScanner */
	private $scanner;

	/** @var UsageRepository */
	private $repo;

	public function __construct( UsageScanner $scanner, UsageRepository $repo ) {
		$this->scanner = $scanner;
		$this->repo    = $repo;
	}

	public function register_routes() {
		$perm = array( $this, 'perm_blocks' );

		$this->route(
			'/blocks/(?P<id>\d+)/usage',
			array(
				'methods'             => 'GET',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->repo->for_block( (int) $r['id'] ) );
				},
				'permission_callback' => $perm,
				'args'                => $this->id_arg(),
			)
		);

		$this->route(
			'/usage/status',
			array(
				'methods'             => 'GET',
				'callback'            => function () {
					$built = (int) get_option( UsageRepository::BUILT_OPTION, 0 );
					return $this->respond(
						array(
							'built'    => $built > 0,
							'built_at' => $built ? gmdate( 'c', $built ) : null,
							'rows'     => $this->repo->total(),
						)
					);
				},
				'permission_callback' => $perm,
			)
		);

		$this->route(
			'/usage/rebuild',
			array(
				'methods'             => 'POST',
				'callback'            => function ( \WP_REST_Request $r ) {
					return $this->respond( $this->scanner->rebuild( (int) $r['offset'] ) );
				},
				'permission_callback' => $perm,
				'args'                => array(
					'offset' => array(
						'type'    => 'integer',
						'default' => 0,
					),
				),
			)
		);
	}
}
