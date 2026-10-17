<?php
/**
 * Cài đặt plugin, lưu trong option `fbm_settings`.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'fbm_settings';

	/** Module luôn bật, không cho tắt. */
	const CORE_MODULES = array( 'blocks', 'settings', 'admin' );

	public static function defaults() {
		return array(
			'modules'           => array(
				'usage'    => true,
				'transfer' => true,
				'elements' => true,
				'handdn'   => true,
			),
			'safe_mode'         => false,
			'delete_on_uninstall' => false,
		);
	}

	public function all() {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();
		$out   = array_replace_recursive( self::defaults(), $saved );

		$out['modules']             = array_map( 'boolval', $out['modules'] );
		$out['safe_mode']           = (bool) $out['safe_mode'];
		$out['delete_on_uninstall'] = (bool) $out['delete_on_uninstall'];
		return $out;
	}

	public function update( array $input ) {
		$current = $this->all();
		if ( isset( $input['modules'] ) && is_array( $input['modules'] ) ) {
			foreach ( array_keys( $current['modules'] ) as $id ) {
				if ( array_key_exists( $id, $input['modules'] ) ) {
					$current['modules'][ $id ] = (bool) $input['modules'][ $id ];
				}
			}
		}
		foreach ( array( 'safe_mode', 'delete_on_uninstall' ) as $key ) {
			if ( array_key_exists( $key, $input ) ) {
				$current[ $key ] = (bool) $input[ $key ];
			}
		}
		update_option( self::OPTION, $current, true );
		return $current;
	}

	public function module_enabled( $id ) {
		if ( in_array( $id, self::CORE_MODULES, true ) ) {
			return true;
		}
		$all = $this->all();
		return ! empty( $all['modules'][ $id ] );
	}

	/**
	 * Safe mode: hằng số FBM_SAFE_MODE trong wp-config.php luôn thắng cài đặt.
	 */
	public function safe_mode() {
		if ( defined( 'FBM_SAFE_MODE' ) ) {
			return (bool) FBM_SAFE_MODE;
		}
		$all = $this->all();
		return $all['safe_mode'];
	}
}
