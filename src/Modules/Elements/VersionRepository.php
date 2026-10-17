<?php
/**
 * Bảng {prefix}fbm_element_versions: mỗi lần publish là một phiên bản bất biến.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

use FBM\Core\Installer;

defined( 'ABSPATH' ) || exit;

final class VersionRepository {

	private function table() {
		return Installer::versions_table();
	}

	/**
	 * @return int Số phiên bản mới.
	 */
	public function add( $element_id, array $payload, $hash, array $report ) {
		global $wpdb;
		$next = 1 + (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT MAX(version) FROM {$this->table()} WHERE element_id = %d", $element_id ) // phpcs:ignore
		);
		$wpdb->insert(
			$this->table(),
			array(
				'element_id'  => (int) $element_id,
				'version'     => $next,
				'payload'     => wp_json_encode( ElementRepository::normalize_payload( $payload ) ),
				'hash'        => $hash,
				'test_report' => wp_json_encode( $report ),
				'created_by'  => get_current_user_id(),
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
		);
		return $next;
	}

	/**
	 * @return array|null
	 */
	public function get( $element_id, $version ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE element_id = %d AND version = %d", $element_id, $version ), // phpcs:ignore
			ARRAY_A
		);
		return $row ? $this->format( $row, true ) : null;
	}

	/**
	 * Danh sách phiên bản (không kèm code), mới nhất trước.
	 */
	public function list_for( $element_id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT id, element_id, version, hash, test_report, created_by, created_at FROM {$this->table()} WHERE element_id = %d ORDER BY version DESC", $element_id ), // phpcs:ignore
			ARRAY_A
		);
		return array_map(
			function ( $row ) {
				return $this->format( $row, false );
			},
			(array) $rows
		);
	}

	public function delete_for( $element_id ) {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'element_id' => (int) $element_id ), array( '%d' ) );
	}

	private function format( array $row, $with_payload ) {
		$out = array(
			'version'    => (int) $row['version'],
			'hash'       => $row['hash'],
			'report'     => json_decode( (string) $row['test_report'], true ),
			'created_by' => get_the_author_meta( 'display_name', (int) $row['created_by'] ),
			'created_at' => gmdate( 'c', strtotime( $row['created_at'] . ' UTC' ) ), // Lưu theo UTC.
		);
		if ( $with_payload ) {
			$out['payload'] = ElementRepository::normalize_payload( (array) json_decode( $row['payload'], true ) );
		}
		return $out;
	}
}
