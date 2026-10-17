<?php
/**
 * Bật / Ẩn / Tắt cho UX Block (post type `blocks` của Flatsome).
 *
 * - Ẩn: không có trong ô tìm của element Block trong UX Builder; chỗ đã dùng vẫn hiển thị.
 * - Tắt: như Ẩn, và [block] không hiển thị ngoài site (người biên tập thấy khung nét đứt).
 *
 * @package FBM
 */

namespace FBM;

use FBM\Usage\UsageIndex;

defined( 'ABSPATH' ) || exit;

final class BlockStates {

	const META = '_fbm_state';

	/** @var UsageIndex */
	private $usage;

	/** @var array<int, string> */
	private $cache = array();

	public function __construct( UsageIndex $usage ) {
		$this->usage = $usage;
	}

	public function boot() {
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_render' ), 10, 3 );
		add_filter( 'ux_builder_ajax_posts_search_posts_get_posts_args', array( $this, 'filter_builder_search' ) );
	}

	public function get( $id ) {
		$id = (int) $id;
		if ( ! isset( $this->cache[ $id ] ) ) {
			$state              = get_post_meta( $id, self::META, true );
			$this->cache[ $id ] = in_array( $state, array( 'hidden', 'off' ), true ) ? $state : 'on';
		}
		return $this->cache[ $id ];
	}

	/**
	 * @return true|\WP_Error
	 */
	public function set( $id, $state ) {
		$id    = (int) $id;
		$state = (string) $state;
		if ( ! in_array( $state, States::VALID, true ) ) {
			return new \WP_Error( 'fbm_bad_state', __( 'Trạng thái không hợp lệ.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( ! $id || 'blocks' !== get_post_type( $id ) ) {
			return new \WP_Error( 'fbm_not_block', __( 'Không phải UX Block.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( 'on' === $state ) {
			delete_post_meta( $id, self::META );
		} else {
			update_post_meta( $id, self::META, $state );
		}
		unset( $this->cache[ $id ] );
		return true;
	}

	/**
	 * @return int[] ID các UX Block đang Ẩn hoặc Tắt.
	 */
	public function not_on_ids() {
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'   => 'blocks',
					'post_status' => 'any',
					'numberposts' => -1,
					'fields'      => 'ids',
					'meta_query'  => array(
						array(
							'key'     => self::META,
							'value'   => array( 'hidden', 'off' ),
							'compare' => 'IN',
						),
					),
				)
			)
		);
	}

	/**
	 * @return array<int, array{id:int, title:string, slug:string, state:string, usage:int}>
	 */
	public function rows() {
		$posts  = get_posts(
			array(
				'post_type'   => 'blocks',
				'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ),
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
		$counts = $this->usage->block_counts( wp_list_pluck( $posts, 'ID' ) );
		$rows   = array();
		foreach ( $posts as $post ) {
			$rows[] = array(
				'id'    => (int) $post->ID,
				'title' => get_the_title( $post ),
				'slug'  => $post->post_name,
				'state' => $this->get( $post->ID ),
				'usage' => isset( $counts[ $post->ID ] ) ? (int) $counts[ $post->ID ] : 0,
			);
		}
		return $rows;
	}

	/**
	 * @param false|string $output
	 * @return false|string
	 */
	public function filter_render( $output, $tag, $attr ) {
		if ( false !== $output || 'block' !== $tag || ! is_array( $attr ) || empty( $attr['id'] ) ) {
			return $output;
		}
		$id = function_exists( 'flatsome_get_block_id' ) ? (int) flatsome_get_block_id( $attr['id'] ) : 0;
		if ( ! $id || 'off' !== $this->get( $id ) ) {
			return $output;
		}
		return Placeholder::off( get_the_title( $id ) );
	}

	public function filter_builder_search( $args ) {
		if ( ! is_array( $args ) || empty( $args['post_type'] ) || ! in_array( 'blocks', (array) $args['post_type'], true ) ) {
			return $args;
		}
		$ids = $this->not_on_ids();
		if ( $ids ) {
			$existing             = isset( $args['post__not_in'] ) ? array_map( 'intval', (array) $args['post__not_in'] ) : array();
			$args['post__not_in'] = array_values( array_unique( array_merge( $existing, $ids ) ) );
		}
		return $args;
	}
}
