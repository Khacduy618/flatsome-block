<?php
/**
 * Module Blocks: CRUD trên CPT `blocks` + taxonomy `block_categories` của Flatsome.
 *
 * @package FBM
 */

namespace FBM\Modules\Blocks;

use FBM\Core\Container;
use FBM\Core\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	public function id() {
		return 'blocks';
	}

	public function register( Container $c ) {
		$c->set(
			'blocks.repository',
			static function () {
				return new BlockRepository();
			}
		);
		$c->set(
			'blocks.visibility',
			static function () {
				return new Visibility();
			}
		);
		$c->set(
			'blocks.service',
			static function ( Container $c ) {
				return new BlockService( $c->get( 'blocks.repository' ), $c->get( 'blocks.visibility' ) );
			}
		);
	}

	public function boot( Container $c ) {
		$c->get( 'blocks.visibility' )->boot();

		add_action(
			'rest_api_init',
			static function () use ( $c ) {
				( new BlocksController( $c->get( 'blocks.service' ) ) )->register_routes();
			}
		);
	}
}
