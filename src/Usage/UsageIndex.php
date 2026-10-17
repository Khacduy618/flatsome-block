<?php
/**
 * Đếm nơi dùng: UX Block (bảng chỉ mục của v1, cập nhật khi lưu) và element (đếm theo tag, có cache).
 * Chỉ để hiển thị cột "N nơi" và hỏi lại trước khi Tắt.
 *
 * @package FBM
 */

namespace FBM\Usage;

defined( 'ABSPATH' ) || exit;

final class UsageIndex {

	const ELEMENT_CACHE = 'fbm_element_usage';

	/** @var UsageRepository */
	private $repo;

	/** @var UsageScanner */
	private $scanner;

	public function __construct() {
		$this->repo    = new UsageRepository();
		$this->scanner = new UsageScanner( $this->repo );
	}

	public function boot() {
		add_action(
			'save_post',
			function ( $post_id, $post ) {
				delete_transient( self::ELEMENT_CACHE );
				if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
					return;
				}
				$this->scanner->scan_post( $post_id );
			},
			20,
			2
		);
		$on_meta = function ( $meta_id, $post_id, $meta_key ) {
			if ( in_array( $meta_key, UsageScanner::META_KEYS, true ) ) {
				$this->scanner->scan_post( $post_id );
			}
		};
		add_action( 'added_post_meta', $on_meta, 20, 3 );
		add_action( 'updated_post_meta', $on_meta, 20, 3 );
		add_action( 'deleted_post_meta', $on_meta, 20, 3 );
		add_action(
			'deleted_post',
			function ( $post_id ) {
				delete_transient( self::ELEMENT_CACHE );
				$this->repo->delete_source( 'post', $post_id );
				$this->repo->delete_source( 'postmeta', $post_id );
				$this->repo->delete_block( $post_id );
			}
		);
		add_action(
			'updated_option',
			function ( $option ) {
				if ( 0 === strpos( $option, 'theme_mods_' ) || 0 === strpos( $option, 'widget_' ) ) {
					$this->scanner->scan_globals();
				}
			}
		);
	}

	/**
	 * @param int[] $ids
	 * @return array<int, int>
	 */
	public function block_counts( array $ids ) {
		return $this->repo->counts( $ids );
	}

	public function is_built() {
		return (bool) get_option( UsageRepository::BUILT_OPTION );
	}

	/**
	 * @return array{next: int|null, total: int}
	 */
	public function rebuild_batch( $offset ) {
		$r = $this->scanner->rebuild( (int) $offset );
		return array(
			'next'  => $r['done'] ? null : (int) $r['next_offset'],
			'total' => (int) $r['total'],
		);
	}

	/**
	 * Số bài viết / trang / UX Block có chứa mỗi shortcode (mỗi bài đếm 1 lần cho mỗi tag).
	 *
	 * @return array<string, int>
	 */
	public function element_counts() {
		$cached = get_transient( self::ELEMENT_CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$skip  = "'" . implode( "','", array_map( 'esc_sql', UsageScanner::SKIP_POST_TYPES ) ) . "'";
		$texts = $wpdb->get_col( "SELECT post_content FROM {$wpdb->posts} WHERE post_type NOT IN ({$skip}) AND post_status IN ('publish','private','draft','future','pending') AND post_content LIKE '%[%'" ); // phpcs:ignore

		$counts = array();
		foreach ( $texts as $text ) {
			if ( preg_match_all( '/\[([a-z0-9_-]+)[\s\]\/]/i', (string) $text, $m ) ) {
				foreach ( array_unique( array_map( 'strtolower', $m[1] ) ) as $tag ) {
					$counts[ $tag ] = isset( $counts[ $tag ] ) ? $counts[ $tag ] + 1 : 1;
				}
			}
		}

		set_transient( self::ELEMENT_CACHE, $counts, HOUR_IN_SECONDS );
		return $counts;
	}
}
