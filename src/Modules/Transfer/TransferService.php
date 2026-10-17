<?php
/**
 * Định dạng gói xuất:
 * {
 *   "format": "fbm-package", "format_version": 1, "plugin_version": "1.0.0",
 *   "exported_at": "...", "source": "https://site-cu.vn",
 *   "blocks":   [ { slug, title, status, content, menu_order, categories: [{slug,name,parent}], visibility: {enabled, from, until} } ],
 *   "elements": [ { tag, name, category, targets, sample, payload: {schema, template, css, js} } ]
 * }
 *
 * Ảnh không đi kèm: nội dung block giữ ID ảnh của site cũ.
 *
 * @package FBM
 */

namespace FBM\Modules\Transfer;

use FBM\Core\Capabilities;
use FBM\Modules\Blocks\BlockRepository;
use FBM\Modules\Blocks\Visibility;
use FBM\Modules\Elements\ElementService;
use FBM\Modules\Elements\Registry;

defined( 'ABSPATH' ) || exit;

final class TransferService {

	const FORMAT         = 'fbm-package';
	const FORMAT_VERSION = 1;
	const CONFLICTS      = array( 'rename', 'overwrite', 'skip' );

	/** @var BlockRepository */
	private $blocks;

	/** @var Visibility */
	private $visibility;

	/** @var ElementService|null */
	private $elements;

	/** @var Registry|null */
	private $registry;

	public function __construct( BlockRepository $blocks, Visibility $visibility, $elements, $registry ) {
		$this->blocks     = $blocks;
		$this->visibility = $visibility;
		$this->elements   = $elements;
		$this->registry   = $registry;
	}

	/* ---------- Xuất ---------- */

	/**
	 * @param int[] $block_ids
	 * @param int[] $element_ids
	 * @param bool  $with_deps Kèm block lồng bên trong và element đang được dùng trong nội dung.
	 */
	public function export( array $block_ids, array $element_ids, $with_deps ) {
		$block_ids   = array_values( array_unique( array_filter( array_map( 'intval', $block_ids ) ) ) );
		$element_ids = array_values( array_unique( array_filter( array_map( 'intval', $element_ids ) ) ) );

		if ( $with_deps ) {
			$queue = $block_ids;
			while ( $queue ) {
				$post = $this->blocks->get( array_shift( $queue ) );
				if ( ! $post ) {
					continue;
				}
				foreach ( $this->nested_block_ids( $post->post_content ) as $nested ) {
					if ( ! in_array( $nested, $block_ids, true ) ) {
						$block_ids[] = $nested;
						$queue[]     = $nested;
					}
				}
				foreach ( $this->element_tags_in( $post->post_content ) as $tag ) {
					$id = $this->elements ? $this->elements->find_id_by_tag( $tag ) : 0;
					if ( $id && ! in_array( $id, $element_ids, true ) ) {
						$element_ids[] = $id;
					}
				}
			}
		}

		$package = array(
			'format'         => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'plugin_version' => FBM_VERSION,
			'exported_at'    => gmdate( 'c' ),
			'source'         => home_url(),
			'blocks'         => array(),
			'elements'       => array(),
		);

		foreach ( $block_ids as $id ) {
			$post = $this->blocks->get( $id );
			if ( ! $post ) {
				continue;
			}
			$state               = $this->visibility->state( $post->ID );
			$package['blocks'][] = array(
				'slug'       => $post->post_name,
				'title'      => $post->post_title,
				'status'     => 'trash' === $post->post_status ? 'draft' : $post->post_status,
				'content'    => $post->post_content,
				'menu_order' => (int) $post->menu_order,
				'categories' => array_map(
					static function ( $term ) {
						$parent = $term->parent ? get_term( $term->parent ) : null;
						return array(
							'slug'   => $term->slug,
							'name'   => $term->name,
							'parent' => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
						);
					},
					$this->blocks->categories_of( $post->ID )
				),
				'visibility' => array(
					'enabled' => $state['enabled'],
					'from'    => $state['from'],
					'until'   => $state['until'],
				),
			);
		}

		if ( $this->elements && Capabilities::can_edit_code() ) {
			foreach ( $element_ids as $id ) {
				$el = $this->elements->export_element( $id );
				if ( $el ) {
					$package['elements'][] = $el;
				}
			}
		}

		return $package;
	}

	/* ---------- Nhập ---------- */

	/**
	 * @param array  $package
	 * @param string $conflict rename | overwrite | skip
	 * @param bool   $dry_run  Chỉ báo trước sẽ làm gì.
	 * @return array|\WP_Error
	 */
	public function import( $package, $conflict, $dry_run ) {
		if ( ! is_array( $package ) || ( $package['format'] ?? '' ) !== self::FORMAT ) {
			return new \WP_Error( 'fbm_invalid_package', __( 'File không phải gói xuất của Flatsome Block Manager.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( (int) ( $package['format_version'] ?? 0 ) > self::FORMAT_VERSION ) {
			return new \WP_Error( 'fbm_invalid_package', __( 'Gói được xuất từ phiên bản plugin mới hơn. Hãy cập nhật plugin.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		$conflict = in_array( $conflict, self::CONFLICTS, true ) ? $conflict : 'rename';
		$report   = array(
			'dry_run'  => (bool) $dry_run,
			'blocks'   => array(),
			'elements' => array(),
			'notes'    => array(),
		);

		// 1. Element trước, để biết tag nào bị đổi tên.
		$tag_map = array();
		$els     = isset( $package['elements'] ) && is_array( $package['elements'] ) ? $package['elements'] : array();
		if ( $els && ( ! $this->elements || ! Capabilities::can_edit_code() ) ) {
			$report['notes'][] = __( 'Bỏ qua element: module Code Elements đang tắt hoặc tài khoản không có quyền viết code.', 'flatsome-block-manager' );
			$els               = array();
		}
		foreach ( $els as $el ) {
			if ( empty( $el['tag'] ) ) {
				continue;
			}
			$result               = $this->elements->import_element( $el, $conflict, $dry_run );
			$report['elements'][] = $result;
			if ( $result['tag'] !== $el['tag'] ) {
				$tag_map[ $el['tag'] ] = $result['tag'];
			}
		}
		if ( $report['elements'] && ! $dry_run ) {
			$report['notes'][] = __( 'Element nhập vào nằm ở bản nháp. Mở từng element, bấm Chạy thử rồi Publish.', 'flatsome-block-manager' );
		}

		// 2. Tính slug mới cho block trước khi ghi, để sửa được [block id] lồng nhau.
		$blocks   = isset( $package['blocks'] ) && is_array( $package['blocks'] ) ? $package['blocks'] : array();
		$slug_map = array();
		$plans    = array();
		$taken    = array();
		foreach ( $blocks as $b ) {
			$slug = sanitize_title( (string) ( $b['slug'] ?? '' ) );
			if ( '' === $slug ) {
				continue;
			}
			$existing = $this->blocks->find_by_slug( $slug );
			$action   = $existing ? $conflict : 'create';
			$new_slug = $slug;
			if ( 'rename' === $action ) {
				$i = 2;
				do {
					$new_slug = $slug . '-' . $i++;
				} while ( $this->blocks->find_by_slug( $new_slug ) || isset( $taken[ $new_slug ] ) );
				$slug_map[ $slug ] = $new_slug;
			}
			$taken[ $new_slug ] = true;
			$plans[]            = array( $b, $slug, $new_slug, $action, $existing );
		}

		foreach ( $plans as $plan ) {
			list( $b, $slug, $new_slug, $action, $existing ) = $plan;
			$row = array(
				'slug'     => $slug,
				'new_slug' => $new_slug,
				'title'    => (string) ( $b['title'] ?? $slug ),
				'action'   => $action,
				'id'       => $existing ? (int) $existing->ID : 0,
			);
			if ( $dry_run || 'skip' === $action ) {
				$report['blocks'][] = $row;
				continue;
			}

			$content = $this->rewrite_refs( (string) ( $b['content'] ?? '' ), $slug_map, $tag_map );
			$postarr = array(
				'post_title'   => $row['title'],
				'post_content' => $content,
				'post_status'  => in_array( $b['status'] ?? '', array( 'publish', 'draft', 'pending', 'private' ), true ) ? $b['status'] : 'publish',
				'menu_order'   => (int) ( $b['menu_order'] ?? 0 ),
			);

			if ( 'overwrite' === $action ) {
				$id = $this->blocks->update( $existing->ID, $postarr );
			} else {
				$postarr['post_name'] = $new_slug;
				$id                   = $this->blocks->insert( $postarr );
			}
			if ( is_wp_error( $id ) ) {
				$row['action']      = 'error';
				$row['message']     = $id->get_error_message();
				$report['blocks'][] = $row;
				continue;
			}

			$this->blocks->set_categories( $id, $this->ensure_categories( (array) ( $b['categories'] ?? array() ) ) );
			if ( isset( $b['visibility'] ) && is_array( $b['visibility'] ) ) {
				$this->visibility->update(
					$id,
					array(
						'enabled'    => ! empty( $b['visibility']['enabled'] ),
						'show_from'  => $b['visibility']['from'] ?? '',
						'show_until' => $b['visibility']['until'] ?? '',
					)
				);
			}
			$row['id']          = (int) $id;
			$report['blocks'][] = $row;
		}

		// Báo sau khi nhập xong tất cả: block cha có thể được tạo trước block con mà nó nhúng.
		foreach ( $report['blocks'] as $row ) {
			if ( $row['id'] && in_array( $row['action'], array( 'create', 'rename', 'overwrite' ), true ) ) {
				do_action( 'fbm/block_saved', $row['id'] );
			}
		}

		if ( $this->has_images( $blocks ) ) {
			$report['notes'][] = __( 'Một số block có ảnh. Ảnh không đi kèm file xuất: hãy chọn lại ảnh hoặc dùng plugin chuyển media.', 'flatsome-block-manager' );
		}
		return $report;
	}

	/* ---------- Nội bộ ---------- */

	private function rewrite_refs( $content, array $slug_map, array $tag_map ) {
		if ( $slug_map && false !== strpos( $content, '[block' ) ) {
			$content = preg_replace_callback(
				'/\[block(\s[^\]]*)\]/',
				static function ( $m ) use ( $slug_map ) {
					return preg_replace_callback(
						'/\bid\s*=\s*(["\']?)([^"\'\s\]]+)\1/',
						static function ( $a ) use ( $slug_map ) {
							return isset( $slug_map[ $a[2] ] ) ? 'id="' . $slug_map[ $a[2] ] . '"' : $a[0];
						},
						$m[0]
					);
				},
				$content
			);
		}
		foreach ( $tag_map as $old => $new ) {
			$content = preg_replace( '/\[(\/?)' . preg_quote( $old, '/' ) . '(?=[\s\]\/])/', '[$1' . $new, $content );
		}
		return $content;
	}

	/**
	 * @return int[] term_id
	 */
	private function ensure_categories( array $cats ) {
		$ids = array();
		foreach ( $cats as $cat ) {
			if ( empty( $cat['slug'] ) ) {
				continue;
			}
			$term = get_term_by( 'slug', $cat['slug'], BlockRepository::TAXONOMY );
			if ( ! $term ) {
				$parent = 0;
				if ( ! empty( $cat['parent'] ) ) {
					$p      = get_term_by( 'slug', $cat['parent'], BlockRepository::TAXONOMY );
					$parent = $p ? (int) $p->term_id : 0;
				}
				$created = wp_insert_term(
					(string) ( $cat['name'] ?? $cat['slug'] ),
					BlockRepository::TAXONOMY,
					array(
						'slug'   => $cat['slug'],
						'parent' => $parent,
					)
				);
				if ( is_wp_error( $created ) ) {
					continue;
				}
				$ids[] = (int) $created['term_id'];
			} else {
				$ids[] = (int) $term->term_id;
			}
		}
		return $ids;
	}

	private function nested_block_ids( $content ) {
		$ids = array();
		if ( false === strpos( (string) $content, '[block' ) ) {
			return $ids;
		}
		if ( preg_match_all( '/' . get_shortcode_regex( array( 'block' ) ) . '/s', $content, $m ) ) {
			foreach ( $m[3] as $raw ) {
				$atts = shortcode_parse_atts( $raw );
				if ( is_array( $atts ) && ! empty( $atts['id'] ) && function_exists( 'flatsome_get_block_id' ) ) {
					$id = (int) flatsome_get_block_id( $atts['id'] );
					if ( $id ) {
						$ids[] = $id;
					}
				}
			}
		}
		return $ids;
	}

	private function element_tags_in( $content ) {
		if ( ! $this->registry ) {
			return array();
		}
		$found = array();
		foreach ( array_keys( $this->registry->all() ) as $tag ) {
			if ( false !== strpos( (string) $content, '[' . $tag ) ) {
				$found[] = $tag;
			}
		}
		return $found;
	}

	private function has_images( array $blocks ) {
		foreach ( $blocks as $b ) {
			if ( preg_match( '/\b(img|image|bg|ids)\s*=\s*["\']?\d+/', (string) ( $b['content'] ?? '' ) ) ) {
				return true;
			}
		}
		return false;
	}
}
