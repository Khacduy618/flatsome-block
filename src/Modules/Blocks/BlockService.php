<?php
/**
 * Luật nghiệp vụ cho block: validate, chặn xoá block đang dùng, cảnh báo đổi slug, nhân bản.
 *
 * Module khác cắm vào qua filter (không gọi thẳng repository của nhau):
 * - fbm/block_usages        ( array $usages, int $block_id )
 * - fbm/block_usage_counts  ( array|null $counts, int[] $ids )
 * Và lắng nghe action: fbm/block_saved, fbm/block_deleted.
 *
 * @package FBM
 */

namespace FBM\Modules\Blocks;

defined( 'ABSPATH' ) || exit;

final class BlockService {

	const STATUSES = array( 'publish', 'draft', 'pending', 'private' );

	const VISIBILITY_FIELDS = array( 'enabled', 'show_from', 'show_until' );

	/** @var BlockRepository */
	private $repo;

	/** @var Visibility */
	private $visibility;

	public function __construct( BlockRepository $repo, Visibility $visibility ) {
		$this->repo       = $repo;
		$this->visibility = $visibility;
	}

	public function list_blocks( array $args ) {
		if ( isset( $args['visibility'] ) && 'unused' === $args['visibility'] ) {
			// Module Usage trả về danh sách ID đang được dùng (null nếu module tắt).
			$used = apply_filters( 'fbm/used_block_ids', null );
			unset( $args['visibility'] );
			if ( is_array( $used ) ) {
				$args['exclude'] = $used;
			}
		}

		$result = $this->repo->query( $args );
		$ids    = wp_list_pluck( $result['posts'], 'ID' );
		$counts = apply_filters( 'fbm/block_usage_counts', null, $ids );

		$items = array();
		foreach ( $result['posts'] as $post ) {
			$item                = $this->format( $post, false );
			$item['usage_count'] = is_array( $counts ) ? (int) ( $counts[ $post->ID ] ?? 0 ) : null;
			$items[]             = $item;
		}

		$per_page = max( 1, (int) ( $args['per_page'] ?? 20 ) );
		return array(
			'items' => $items,
			'total' => $result['total'],
			'pages' => (int) ceil( $result['total'] / $per_page ),
		);
	}

	/**
	 * @return array|\WP_Error
	 */
	public function get( $id ) {
		$post = $this->repo->get( $id );
		if ( ! $post ) {
			return $this->not_found();
		}
		$item           = $this->format( $post, true );
		$item['usages'] = apply_filters( 'fbm/block_usages', null, $post->ID );
		return $item;
	}

	/**
	 * @return array|\WP_Error
	 */
	public function create( array $data ) {
		$title = trim( (string) ( $data['title'] ?? '' ) );
		if ( '' === $title ) {
			return new \WP_Error( 'fbm_invalid', __( 'Tên block không được để trống.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}

		$postarr = array(
			'post_title'   => $title,
			'post_name'    => isset( $data['slug'] ) && '' !== $data['slug'] ? sanitize_title( $data['slug'] ) : sanitize_title( $title ),
			'post_content' => (string) ( $data['content'] ?? '' ),
			'post_status'  => $this->status( $data['status'] ?? 'publish' ),
		);

		$id = $this->repo->insert( $postarr );
		if ( is_wp_error( $id ) ) {
			return $id;
		}
		if ( isset( $data['categories'] ) ) {
			$this->repo->set_categories( $id, (array) $data['categories'] );
		}
		$visibility = array_intersect_key( $data, array_flip( self::VISIBILITY_FIELDS ) );
		if ( $visibility ) {
			$result = $this->visibility->update( $id, $visibility );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		do_action( 'fbm/block_saved', $id );
		return $this->get( $id );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function update( $id, array $data ) {
		$post = $this->repo->get( $id );
		if ( ! $post ) {
			return $this->not_found();
		}

		$postarr = array();
		if ( array_key_exists( 'title', $data ) ) {
			$title = trim( (string) $data['title'] );
			if ( '' === $title ) {
				return new \WP_Error( 'fbm_invalid', __( 'Tên block không được để trống.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
			}
			$postarr['post_title'] = $title;
		}
		if ( array_key_exists( 'content', $data ) ) {
			$postarr['post_content'] = (string) $data['content'];
		}
		if ( array_key_exists( 'status', $data ) ) {
			$postarr['post_status'] = $this->status( $data['status'] );
		}
		if ( array_key_exists( 'slug', $data ) ) {
			$slug = sanitize_title( (string) $data['slug'] );
			if ( '' !== $slug && $slug !== $post->post_name ) {
				// [block id="slug-cu"] sẽ không tìm thấy block nữa.
				$usages = apply_filters( 'fbm/block_usages', null, $post->ID );
				if ( ! empty( $usages ) && empty( $data['confirm_slug_change'] ) ) {
					return new \WP_Error(
						'fbm_slug_in_use',
						__( 'Block đang được dùng. Đổi slug sẽ làm hỏng các shortcode [block id="slug-cũ"]. Gửi lại với confirm_slug_change=true nếu chắc chắn.', 'flatsome-block-manager' ),
						array(
							'status' => 409,
							'usages' => $usages,
						)
					);
				}
				$postarr['post_name'] = $slug;
			}
		}

		if ( $postarr ) {
			$result = $this->repo->update( $post->ID, $postarr );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}
		if ( array_key_exists( 'categories', $data ) ) {
			$this->repo->set_categories( $post->ID, (array) $data['categories'] );
		}
		$visibility = array_intersect_key( $data, array_flip( self::VISIBILITY_FIELDS ) );
		if ( $visibility ) {
			$result = $this->visibility->update( $post->ID, $visibility );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		do_action( 'fbm/block_saved', $post->ID );
		return $this->get( $post->ID );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function duplicate( $id ) {
		$post = $this->repo->get( $id );
		if ( ! $post ) {
			return $this->not_found();
		}

		$new_id = $this->repo->insert(
			array(
				'post_title'   => sprintf( __( '%s (bản sao)', 'flatsome-block-manager' ), $post->post_title ),
				'post_name'    => $post->post_name . '-copy',
				'post_content' => $post->post_content,
				'post_status'  => 'trash' === $post->post_status ? 'draft' : $post->post_status,
				'menu_order'   => $post->menu_order,
			)
		);
		if ( is_wp_error( $new_id ) ) {
			return $new_id;
		}

		$skip = array( '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_trash_meta_status', '_wp_trash_meta_time' );
		foreach ( get_post_meta( $post->ID ) as $key => $values ) {
			if ( in_array( $key, $skip, true ) ) {
				continue;
			}
			foreach ( $values as $value ) {
				add_post_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}
		$this->repo->set_categories( $new_id, wp_list_pluck( $this->repo->categories_of( $post->ID ), 'term_id' ) );

		do_action( 'fbm/block_saved', $new_id );
		return $this->get( $new_id );
	}

	/**
	 * @param bool $permanent    Xoá vĩnh viễn thay vì cho vào thùng rác.
	 * @param bool $ignore_usage Bỏ qua cảnh báo block đang được dùng.
	 * @return array|\WP_Error
	 */
	public function delete( $id, $permanent = false, $ignore_usage = false ) {
		$post = $this->repo->get( $id );
		if ( ! $post ) {
			return $this->not_found();
		}

		if ( ! $ignore_usage ) {
			$usages = apply_filters( 'fbm/block_usages', null, $post->ID );
			if ( ! empty( $usages ) ) {
				return new \WP_Error(
					'fbm_block_in_use',
					sprintf( __( 'Block "%1$s" đang được dùng ở %2$d nơi.', 'flatsome-block-manager' ), $post->post_title, count( $usages ) ),
					array(
						'status' => 409,
						'usages' => $usages,
					)
				);
			}
		}

		if ( $permanent ) {
			$ok = $this->repo->delete( $post->ID );
			if ( $ok ) {
				do_action( 'fbm/block_deleted', $post->ID );
			}
		} else {
			$ok = $this->repo->trash( $post->ID );
		}

		if ( ! $ok ) {
			return new \WP_Error( 'fbm_delete_failed', __( 'Không xoá được block.', 'flatsome-block-manager' ), array( 'status' => 500 ) );
		}
		return array(
			'id'        => $post->ID,
			'deleted'   => (bool) $permanent,
			'trashed'   => ! $permanent,
		);
	}

	/**
	 * @return array|\WP_Error
	 */
	public function restore( $id ) {
		$post = $this->repo->get( $id );
		if ( ! $post ) {
			return $this->not_found();
		}
		if ( ! $this->repo->untrash( $post->ID ) ) {
			return new \WP_Error( 'fbm_restore_failed', __( 'Không khôi phục được block.', 'flatsome-block-manager' ), array( 'status' => 500 ) );
		}
		return $this->get( $post->ID );
	}

	/**
	 * @param string $action trash|restore|delete|enable|disable|set_categories|add_categories
	 * @return array{done: int[], failed: array}
	 */
	public function bulk( $action, array $ids, array $args = array() ) {
		$done   = array();
		$failed = array();
		$ignore = ! empty( $args['ignore_usage'] );

		foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
			switch ( $action ) {
				case 'trash':
					$result = $this->delete( $id, false, $ignore );
					break;
				case 'delete':
					$result = $this->delete( $id, true, $ignore );
					break;
				case 'restore':
					$result = $this->restore( $id );
					break;
				case 'enable':
				case 'disable':
					$result = $this->repo->get( $id )
						? $this->visibility->update( $id, array( 'enabled' => 'enable' === $action ) )
						: $this->not_found();
					break;
				case 'set_categories':
				case 'add_categories':
					if ( ! $this->repo->get( $id ) ) {
						$result = $this->not_found();
						break;
					}
					$this->repo->set_categories( $id, (array) ( $args['categories'] ?? array() ), 'add_categories' === $action );
					$result = true;
					break;
				default:
					return array(
						'done'   => array(),
						'failed' => array( array( 'id' => 0, 'message' => 'Unknown action' ) ),
					);
			}

			if ( is_wp_error( $result ) ) {
				$failed[] = array(
					'id'      => $id,
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
				);
			} else {
				$done[] = $id;
			}
		}

		return array(
			'done'   => $done,
			'failed' => $failed,
		);
	}

	/* ---------- Categories ---------- */

	public function categories() {
		return array_map( array( $this, 'format_term' ), $this->repo->all_categories() );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function save_category( array $data, $id = 0 ) {
		$name = trim( (string) ( $data['name'] ?? '' ) );
		if ( '' === $name ) {
			return new \WP_Error( 'fbm_invalid', __( 'Tên danh mục không được để trống.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		$args = array( 'parent' => (int) ( $data['parent'] ?? 0 ) );

		if ( $id ) {
			$args['name'] = $name;
			$result       = wp_update_term( (int) $id, BlockRepository::TAXONOMY, $args );
		} else {
			$result = wp_insert_term( $name, BlockRepository::TAXONOMY, $args );
		}
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}
		return $this->format_term( get_term( $result['term_id'], BlockRepository::TAXONOMY ) );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function delete_category( $id ) {
		$result = wp_delete_term( (int) $id, BlockRepository::TAXONOMY );
		if ( ! $result || is_wp_error( $result ) ) {
			return new \WP_Error( 'fbm_delete_failed', __( 'Không xoá được danh mục.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		return array( 'id' => (int) $id, 'deleted' => true );
	}

	/* ---------- Format ---------- */

	public function format( \WP_Post $post, $with_content ) {
		$item = array(
			'id'         => $post->ID,
			'title'      => $post->post_title,
			'slug'       => $post->post_name,
			'status'     => $post->post_status,
			'shortcode'  => sprintf( '[block id="%s"]', $post->post_name ),
			'categories' => array_map( array( $this, 'format_term' ), $this->repo->categories_of( $post->ID ) ),
			'date'       => get_post_time( 'c', true, $post ),
			'modified'   => get_post_modified_time( 'c', true, $post ),
			'author'     => get_the_author_meta( 'display_name', $post->post_author ),
			'visibility' => $this->visibility->state( $post->ID ),
			'links'      => array(
				'backend'   => admin_url( 'post.php?post=' . $post->ID . '&action=edit' ),
				'uxbuilder' => function_exists( 'ux_builder_edit_url' ) ? ux_builder_edit_url( $post->ID ) : '',
				'preview'   => get_permalink( $post ),
			),
		);
		if ( $with_content ) {
			$item['content'] = $post->post_content;
		}
		return $item;
	}

	public function format_term( $term ) {
		return array(
			'id'     => (int) $term->term_id,
			'name'   => $term->name,
			'slug'   => $term->slug,
			'parent' => (int) $term->parent,
			'count'  => (int) $term->count,
		);
	}

	private function status( $status ) {
		return in_array( $status, self::STATUSES, true ) ? $status : 'publish';
	}

	private function not_found() {
		return new \WP_Error( 'fbm_not_found', __( 'Không tìm thấy block.', 'flatsome-block-manager' ), array( 'status' => 404 ) );
	}
}
