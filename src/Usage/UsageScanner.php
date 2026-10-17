<?php
/**
 * Quét các nơi Flatsome có thể nhúng block:
 * - post_content của mọi post type (kể cả block lồng block): [block id="..."]
 * - post meta: _product_block, _menu_item_block (ID trực tiếp), _top_content, _bottom_content (text)
 * - theme mods: header-block-1..4, footer_block, 404_block... và text có [block]
 * - widgets: "Flatsome UX Blocks" widget + text/html/block widget có [block]
 *
 * @package FBM
 */

namespace FBM\Usage;

defined( 'ABSPATH' ) || exit;

final class UsageScanner {

	/** Meta chứa ID block trực tiếp. */
	const META_ID_KEYS = array( '_product_block', '_menu_item_block' );

	/** Meta chứa text có thể có shortcode. */
	const META_TEXT_KEYS = array( '_top_content', '_bottom_content' );

	const META_KEYS = array( '_product_block', '_menu_item_block', '_top_content', '_bottom_content' );

	const SKIP_POST_TYPES = array( 'revision', 'attachment', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles' );

	const BATCH = 100;

	/** @var UsageRepository */
	private $repo;

	/** @var array<string, int|null> */
	private $resolved = array();

	public function __construct( UsageRepository $repo ) {
		$this->repo = $repo;
	}

	public function scan_post( $post_id ) {
		$post = get_post( (int) $post_id );
		if ( ! $post || in_array( $post->post_type, self::SKIP_POST_TYPES, true ) ) {
			return;
		}

		$rows = array();
		foreach ( $this->refs_in_text( $post->post_content ) as $block_id ) {
			if ( (int) $block_id !== (int) $post->ID ) {
				$rows[] = array(
					'block_id'   => $block_id,
					'source_key' => '',
				);
			}
		}
		$this->repo->replace_source( 'post', $post->ID, $rows );

		$meta_rows = array();
		foreach ( self::META_ID_KEYS as $key ) {
			$block_id = $this->resolve( get_post_meta( $post->ID, $key, true ) );
			if ( $block_id ) {
				$meta_rows[] = array(
					'block_id'   => $block_id,
					'source_key' => $key,
				);
			}
		}
		foreach ( self::META_TEXT_KEYS as $key ) {
			$value = get_post_meta( $post->ID, $key, true );
			foreach ( $this->refs_in_text( is_string( $value ) ? $value : '' ) as $block_id ) {
				$meta_rows[] = array(
					'block_id'   => $block_id,
					'source_key' => $key,
				);
			}
		}
		$this->repo->replace_source( 'postmeta', $post->ID, $meta_rows );
	}

	public function scan_globals() {
		// Theme mods của theme đang chạy (child theme lưu riêng theme_mods_{stylesheet}).
		$this->repo->delete_source( 'theme_mod' );
		foreach ( (array) get_theme_mods() as $key => $value ) {
			if ( ! is_string( $value ) || '' === $value ) {
				continue;
			}
			$ids = $this->refs_in_text( $value );
			if ( false !== strpos( (string) $key, 'block' ) ) {
				$direct = $this->resolve( $value );
				if ( $direct ) {
					$ids[] = $direct;
				}
			}
			$rows = array();
			foreach ( array_unique( $ids ) as $id ) {
				$rows[] = array(
					'block_id'   => $id,
					'source_key' => '',
				);
			}
			$this->repo->insert_rows( 'theme_mod', $key, $rows );
		}

		// Widgets.
		$this->repo->delete_source( 'widget' );
		$sources = array(
			'widget_block_widget' => array( 'id' => 'block' ),
			'widget_text'         => array( 'text' => 'text' ),
			'widget_custom_html'  => array( 'text' => 'content' ),
			'widget_block'        => array( 'text' => 'content' ),
		);
		foreach ( $sources as $option => $field ) {
			$instances = get_option( $option, array() );
			if ( ! is_array( $instances ) ) {
				continue;
			}
			$rows = array();
			foreach ( $instances as $number => $instance ) {
				if ( ! is_array( $instance ) ) {
					continue;
				}
				if ( isset( $field['id'] ) && ! empty( $instance[ $field['id'] ] ) ) {
					$id = $this->resolve( $instance[ $field['id'] ] );
					if ( $id ) {
						$rows[] = array(
							'block_id'   => $id,
							'source_key' => (string) $number,
						);
					}
				}
				if ( isset( $field['text'] ) && ! empty( $instance[ $field['text'] ] ) && is_string( $instance[ $field['text'] ] ) ) {
					foreach ( $this->refs_in_text( $instance[ $field['text'] ] ) as $id ) {
						$rows[] = array(
							'block_id'   => $id,
							'source_key' => (string) $number,
						);
					}
				}
			}
			$this->repo->insert_rows( 'widget', $option, $rows );
		}
	}

	/**
	 * Quét lại toàn bộ theo lô (UI gọi lặp đến khi done=true).
	 *
	 * @return array{processed:int, next_offset:int, total:int, done:bool}
	 */
	public function rebuild( $offset ) {
		global $wpdb;

		$offset = max( 0, (int) $offset );
		if ( 0 === $offset ) {
			$this->repo->truncate();
			$this->scan_globals();
		}

		$skip  = "'" . implode( "','", array_map( 'esc_sql', self::SKIP_POST_TYPES ) ) . "'";
		$where = "post_type NOT IN ({$skip}) AND post_status NOT IN ('auto-draft','inherit')";
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE {$where}" ); // phpcs:ignore
		$ids   = $wpdb->get_col(
			$wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE {$where} ORDER BY ID ASC LIMIT %d OFFSET %d", self::BATCH, $offset ) // phpcs:ignore
		);

		foreach ( $ids as $id ) {
			$this->scan_post( (int) $id );
		}

		$next = $offset + count( $ids );
		$done = $next >= $total || count( $ids ) < self::BATCH;
		if ( $done ) {
			update_option( UsageRepository::BUILT_OPTION, time(), false );
		}

		return array(
			'processed'   => count( $ids ),
			'next_offset' => $next,
			'total'       => $total,
			'done'        => $done,
		);
	}

	/**
	 * @return int[] ID các block được nhắc tới trong text.
	 */
	public function refs_in_text( $text ) {
		if ( ! is_string( $text ) || false === strpos( $text, '[block' ) ) {
			return array();
		}
		$ids = array();
		if ( preg_match_all( '/' . get_shortcode_regex( array( 'block' ) ) . '/s', $text, $matches ) ) {
			foreach ( $matches[3] as $raw_atts ) {
				$atts = shortcode_parse_atts( $raw_atts );
				if ( is_array( $atts ) && ! empty( $atts['id'] ) ) {
					$id = $this->resolve( $atts['id'] );
					if ( $id ) {
						$ids[] = $id;
					}
				}
			}
		}
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Slug hoặc ID -> ID block (dùng chính hàm của Flatsome, có hỗ trợ WPML/Polylang).
	 */
	private function resolve( $ref ) {
		if ( ! is_scalar( $ref ) ) {
			return null;
		}
		$ref = trim( (string) $ref );
		if ( '' === $ref || preg_match( '/\s/', $ref ) ) {
			return null;
		}
		// Kiểm lại ID đã cache: block có thể bị xoá rồi tạo lại cùng slug trong một request.
		if ( isset( $this->resolved[ $ref ] ) && 'blocks' === get_post_type( $this->resolved[ $ref ] ) ) {
			return $this->resolved[ $ref ];
		}
		$id = function_exists( 'flatsome_get_block_id' ) ? flatsome_get_block_id( $ref ) : null;
		$id = $id && get_post_type( (int) $id ) === 'blocks' ? (int) $id : null;
		// Chỉ cache khi tìm thấy: block có thể được tạo sau trong cùng request (ví dụ khi nhập).
		if ( $id ) {
			$this->resolved[ $ref ] = $id;
		}
		return $id;
	}
}
