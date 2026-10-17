<?php
/**
 * Đọc/ghi dữ liệu block. Không chứa luật nghiệp vụ.
 *
 * @package FBM
 */

namespace FBM\Modules\Blocks;

defined( 'ABSPATH' ) || exit;

final class BlockRepository {

	const POST_TYPE = 'blocks';
	const TAXONOMY  = 'block_categories';

	/**
	 * @param array $args search, category, status, page, per_page, orderby, order.
	 * @return array{posts: \WP_Post[], total: int}
	 */
	public function query( array $args ) {
		$status = isset( $args['status'] ) && '' !== $args['status'] ? $args['status'] : 'any';
		$query  = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'any' === $status ? array( 'publish', 'draft', 'pending', 'private', 'future' ) : $status,
			'posts_per_page' => max( 1, min( 200, (int) ( $args['per_page'] ?? 20 ) ) ),
			'paged'          => max( 1, (int) ( $args['page'] ?? 1 ) ),
			'orderby'        => in_array( $args['orderby'] ?? '', array( 'title', 'date', 'modified', 'ID' ), true ) ? $args['orderby'] : 'modified',
			'order'          => strtoupper( $args['order'] ?? 'DESC' ) === 'ASC' ? 'ASC' : 'DESC',
			'no_found_rows'  => false,
		);

		if ( ! empty( $args['search'] ) ) {
			$query['s'] = $args['search'];
		}
		if ( ! empty( $args['include'] ) ) {
			$query['post__in'] = array_map( 'intval', (array) $args['include'] );
		}
		if ( ! empty( $args['exclude'] ) ) {
			$query['post__not_in'] = array_map( 'intval', (array) $args['exclude'] );
		}
		if ( ! empty( $args['visibility'] ) ) {
			$enabled = array(
				'relation' => 'OR',
				array(
					'key'     => Visibility::META_ENABLED,
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => Visibility::META_ENABLED,
					'value'   => '0',
					'compare' => '!=',
				),
			);
			switch ( $args['visibility'] ) {
				case 'off':
					$query['meta_query'] = array(
						array(
							'key'   => Visibility::META_ENABLED,
							'value' => '0',
						),
					);
					break;
				case 'on':
					$query['meta_query'] = array( $enabled );
					break;
				case 'scheduled':
					$query['meta_query'] = array(
						'relation' => 'AND',
						$enabled,
						array(
							'relation' => 'OR',
							array(
								'key'     => Visibility::META_FROM,
								'compare' => 'EXISTS',
							),
							array(
								'key'     => Visibility::META_UNTIL,
								'compare' => 'EXISTS',
							),
						),
					);
					break;
			}
		}
		if ( ! empty( $args['category'] ) ) {
			$query['tax_query'] = array(
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => is_numeric( $args['category'] ) ? 'term_id' : 'slug',
					'terms'    => $args['category'],
				),
			);
		}

		$q = new \WP_Query( $query );
		return array(
			'posts' => $q->posts,
			'total' => (int) $q->found_posts,
		);
	}

	/**
	 * @return \WP_Post|null
	 */
	public function get( $id ) {
		$post = get_post( (int) $id );
		return ( $post && self::POST_TYPE === $post->post_type ) ? $post : null;
	}

	/**
	 * @return \WP_Post|null
	 */
	public function find_by_slug( $slug ) {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'name'           => $slug,
				'post_status'    => 'any',
				'posts_per_page' => 1,
			)
		);
		return $posts ? $posts[0] : null;
	}

	/**
	 * @return int|\WP_Error
	 */
	public function insert( array $postarr ) {
		$postarr['post_type'] = self::POST_TYPE;
		return wp_insert_post( wp_slash( $postarr ), true );
	}

	/**
	 * @return int|\WP_Error
	 */
	public function update( $id, array $postarr ) {
		$postarr['ID'] = (int) $id;
		return wp_update_post( wp_slash( $postarr ), true );
	}

	public function trash( $id ) {
		return (bool) wp_trash_post( (int) $id );
	}

	public function untrash( $id ) {
		$ok = (bool) wp_untrash_post( (int) $id );
		// WP 5.6+ khôi phục về draft; block nên trở lại publish như trước.
		if ( $ok && get_post_status( $id ) === 'draft' ) {
			$prev = get_post_meta( $id, '_wp_trash_meta_status', true );
			wp_update_post(
				array(
					'ID'          => (int) $id,
					'post_status' => $prev ? $prev : 'publish',
				)
			);
		}
		return $ok;
	}

	public function delete( $id ) {
		return (bool) wp_delete_post( (int) $id, true );
	}

	/**
	 * @param int[] $term_ids
	 */
	public function set_categories( $id, array $term_ids, $append = false ) {
		return wp_set_object_terms( (int) $id, array_map( 'intval', $term_ids ), self::TAXONOMY, $append );
	}

	/**
	 * @return \WP_Term[]
	 */
	public function categories_of( $id ) {
		$terms = get_the_terms( (int) $id, self::TAXONOMY );
		return is_array( $terms ) ? $terms : array();
	}

	/**
	 * @return \WP_Term[]
	 */
	public function all_categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => self::TAXONOMY,
				'hide_empty' => false,
			)
		);
		return is_array( $terms ) ? $terms : array();
	}
}
