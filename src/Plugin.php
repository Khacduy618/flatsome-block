<?php
/**
 * Bootstrap v2: kiểm tra yêu cầu, rồi khởi động các service nhỏ.
 * Không có module, cài đặt hay chỗ nhập code.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @var array<string, object> */
	private static $services = array();

	/** @var bool */
	private static $started = false;

	public static function start() {
		if ( self::$started ) {
			return;
		}
		$missing = Requirements::missing();
		if ( $missing ) {
			Requirements::show_notice( $missing );
			return;
		}
		self::$started = true;

		Installer::maybe_upgrade();

		self::$services['registry'] = new Elements\Registry();
		$catalog                    = new Catalog();
		self::$services['catalog'] = $catalog;
		self::$services['states']  = new States( $catalog );
		$usage                      = new Usage\UsageIndex();
		self::$services['usage']        = $usage;
		self::$services['block_states'] = new BlockStates( $usage );
		self::$services['templates']    = new Templates();
		self::$services['rest']         = new Admin\Rest( self::$services['states'], $catalog, self::$services['block_states'], $usage, self::$services['registry'] );
		self::$services['admin']        = new Admin\Page();

		foreach ( self::$services as $service ) {
			if ( method_exists( $service, 'boot' ) ) {
				$service->boot();
			}
		}

		( new Handdn\Product3DButton( self::$services['states'] ) )->boot();

		Upgrade::boot();
		Upgrade::maybe_run();

		do_action( 'fbm/booted' );
	}

	/**
	 * @param string $id states | catalog | block_states | usage | registry
	 * @return object|null
	 */
	public static function get( $id ) {
		return isset( self::$services[ $id ] ) ? self::$services[ $id ] : null;
	}
}
