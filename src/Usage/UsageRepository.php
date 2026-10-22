<?php
/**
 * Table {prefix}fbm_block_usage.
 *
 * source_type: post | postmeta | theme_mod | widget
 *
 * @package FBM
 */

namespace FBM\Usage;

use FBM\Installer;

defined( 'ABSPATH' ) || exit;

final class UsageRepository {

	const BUILT_OPTION = 'fbm_usage_built_at';

	private function table() {
		return Installer::usage_table();
	}

	/**
	 * @param array<int, array{block_id:int, source_key:string}> $rows
	 */
	public function replace_source( $type, $source_id, array $rows ) {
		$this->delete_source( $type, $source_id );
		$this->insert_rows( $type, $source_id, $rows );
	}

	public function insert_rows( $type, $source_id, array $rows ) {
		global $wpdb;
		$now  = current_time( 'mysql', true );
		$seen = array();
		foreach ( $rows as $row ) {
			$key = $row['block_id'] . '|' . $row['source_key'];
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$wpdb->insert(
				$this->table(),
				array(
					'block_id'    => (int) $row['block_id'],
					'source_type' => $type,
					'source_id'   => (string) $source_id,
					'source_key'  => (string) $row['source_key'],
					'created_at'  => $now,
				),
				array( '%d', '%s', '%s', '%s', '%s' )
			);
		}
	}

	public function delete_source( $type, $source_id = null ) {
		global $wpdb;
		$where = array( 'source_type' => $type );
		if ( null !== $source_id ) {
			$where['source_id'] = (string) $source_id;
		}
		$wpdb->delete( $this->table(), $where );
	}

	public function delete_block( $block_id ) {
		global $wpdb;
		$wpdb->delete( $this->table(), array( 'block_id' => (int) $block_id ), array( '%d' ) );
	}

	public function truncate() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$this->table()}" ); // phpcs:ignore
	}

	/**
	 * @param int[] $ids
	 * @return array<int, int>
	 */
	public function counts( array $ids ) {
		global $wpdb;
		$ids = array_filter( array_map( 'intval', $ids ) );
		if ( ! $ids ) {
			return array();
		}
		$in   = implode( ',', $ids );
		$rows = $wpdb->get_results( "SELECT block_id, COUNT(*) AS c FROM {$this->table()} WHERE block_id IN ({$in}) GROUP BY block_id", ARRAY_A ); // phpcs:ignore
		$out  = array_fill_keys( $ids, 0 );
		foreach ( (array) $rows as $row ) {
			$out[ (int) $row['block_id'] ] = (int) $row['c'];
		}
		return $out;
	}

	/**
	 * @return int[]
	 */
	public function used_ids() {
		global $wpdb;
		return array_map( 'intval', (array) $wpdb->get_col( "SELECT DISTINCT block_id FROM {$this->table()}" ) ); // phpcs:ignore
	}

	/**
	 * Usage list, with labels and links ready to display.
	 */
	public function for_block( $block_id ) {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table()} WHERE block_id = %d ORDER BY source_type, source_id", $block_id ), // phpcs:ignore
			ARRAY_A
		);
		return array_values( array_filter( array_map( array( $this, 'describe' ), (array) $rows ) ) );
	}

	public function total() {
		global $wpdb;
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table()}" ); // phpcs:ignore
	}

	private function describe( array $row ) {
		$out = array(
			'type'  => $row['source_type'],
			'id'    => $row['source_id'],
			'key'   => $row['source_key'],
			'label' => '',
			'where' => '',
			'edit'  => '',
			'view'  => '',
		);

		switch ( $row['source_type'] ) {
			case 'post':
			case 'postmeta':
				$post = get_post( (int) $row['source_id'] );
				if ( ! $post ) {
					return null;
				}
				$type_obj     = get_post_type_object( $post->post_type );
				$out['label'] = $post->post_title ? $post->post_title : sprintf( '#%d', $post->ID );
				$out['where'] = ( $type_obj ? $type_obj->labels->singular_name : $post->post_type )
					. ( 'postmeta' === $row['source_type'] ? ' · ' . $row['source_key'] : '' )
					. ( 'publish' !== $post->post_status ? ' · ' . $post->post_status : '' );
				$out['edit']  = 'nav_menu_item' === $post->post_type ? admin_url( 'nav-menus.php' ) : (string) get_edit_post_link( $post->ID, 'raw' );
				$out['view']  = is_post_type_viewable( $post->post_type ) && 'nav_menu_item' !== $post->post_type ? (string) get_permalink( $post ) : '';
				break;

			case 'theme_mod':
				$out['label'] = $row['source_id'];
				$out['where'] = __( 'Theme Options', 'flatsome-block-manager' );
				$out['edit']  = admin_url( 'customize.php' );
				break;

			case 'widget':
				$out['label'] = $row['source_id'] . ( '' !== $row['source_key'] ? ' #' . $row['source_key'] : '' );
				$out['where'] = __( 'Widget', 'flatsome-block-manager' );
				$out['edit']  = admin_url( 'widgets.php' );
				break;
		}

		return $out;
	}
}
