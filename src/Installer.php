<?php
/**
 * Tạo / nâng cấp bảng DB của plugin.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Installer {

	const OPTION = 'fbm_db_version';

	public static function activate() {
		self::install_tables();
	}

	public static function maybe_upgrade() {
		if ( (int) get_option( self::OPTION, 0 ) < FBM_DB_VERSION ) {
			self::install_tables();
		}
	}

	public static function usage_table() {
		global $wpdb;
		return $wpdb->prefix . 'fbm_block_usage';
	}

	private static function install_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$usage   = self::usage_table();

		dbDelta(
			"CREATE TABLE {$usage} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  block_id bigint(20) unsigned NOT NULL,
  source_type varchar(20) NOT NULL,
  source_id varchar(191) NOT NULL,
  source_key varchar(191) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY block_id (block_id),
  KEY source (source_type,source_id)
) {$charset};"
		);

		update_option( self::OPTION, FBM_DB_VERSION, false );
	}
}
