<?php
/**
 * Module Transfer: xuất/nhập block + element ra file JSON.
 *
 * @package FBM
 */

namespace FBM\Modules\Transfer;

use FBM\Core\Container;
use FBM\Core\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	public function id() {
		return 'transfer';
	}

	public function register( Container $c ) {
		$c->set(
			'transfer.service',
			static function ( Container $c ) {
				return new TransferService(
					$c->get( 'blocks.repository' ),
					$c->get( 'blocks.visibility' ),
					$c->has( 'elements.service' ) ? $c->get( 'elements.service' ) : null,
					$c->has( 'elements.registry' ) ? $c->get( 'elements.registry' ) : null
				);
			}
		);
	}

	public function boot( Container $c ) {
		add_action(
			'rest_api_init',
			static function () use ( $c ) {
				( new TransferController( $c->get( 'transfer.service' ) ) )->register_routes();
			}
		);
	}
}
