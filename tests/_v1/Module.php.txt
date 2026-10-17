<?php
/**
 * Module Usage: chỉ mục "block nào đang được dùng ở đâu".
 *
 * @package FBM
 */

namespace FBM\Modules\Usage;

use FBM\Core\Container;
use FBM\Core\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	public function id() {
		return 'usage';
	}

	public function register( Container $c ) {
		$c->set(
			'usage.repository',
			static function () {
				return new UsageRepository();
			}
		);
		$c->set(
			'usage.scanner',
			static function ( Container $c ) {
				return new UsageScanner( $c->get( 'usage.repository' ) );
			}
		);
	}

	public function boot( Container $c ) {
		$scanner = static function () use ( $c ) {
			return $c->get( 'usage.scanner' );
		};
		$repo    = static function () use ( $c ) {
			return $c->get( 'usage.repository' );
		};

		// Cập nhật chỉ mục khi nội dung thay đổi.
		add_action(
			'save_post',
			static function ( $post_id, $post ) use ( $scanner ) {
				if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || 'auto-draft' === $post->post_status ) {
					return;
				}
				$scanner()->scan_post( $post_id );
			},
			20,
			2
		);
		$on_meta = static function ( $meta_id, $post_id, $meta_key ) use ( $scanner ) {
			if ( in_array( $meta_key, UsageScanner::META_KEYS, true ) ) {
				$scanner()->scan_post( $post_id );
			}
		};
		add_action( 'added_post_meta', $on_meta, 20, 3 );
		add_action( 'updated_post_meta', $on_meta, 20, 3 );
		add_action( 'deleted_post_meta', $on_meta, 20, 3 );
		// Module khác báo block vừa đổi (ví dụ sau khi nhập) -> quét lại.
		add_action(
			'fbm/block_saved',
			static function ( $post_id ) use ( $scanner ) {
				$scanner()->scan_post( $post_id );
			}
		);
		add_action(
			'deleted_post',
			static function ( $post_id ) use ( $repo ) {
				$repo()->delete_source( 'post', $post_id );
				$repo()->delete_source( 'postmeta', $post_id );
				$repo()->delete_block( $post_id );
			}
		);
		add_action(
			'updated_option',
			static function ( $option ) use ( $scanner ) {
				if ( 0 === strpos( $option, 'theme_mods_' ) || 0 === strpos( $option, 'widget_' ) ) {
					$scanner()->scan_globals();
				}
			}
		);

		// Cung cấp dữ liệu cho module Blocks qua filter.
		add_filter(
			'fbm/block_usages',
			static function ( $usages, $block_id ) use ( $repo ) {
				return $repo()->for_block( (int) $block_id );
			},
			10,
			2
		);
		add_filter(
			'fbm/block_usage_counts',
			static function ( $counts, $ids ) use ( $repo ) {
				return $repo()->counts( (array) $ids );
			},
			10,
			2
		);
		add_filter(
			'fbm/used_block_ids',
			static function () use ( $repo ) {
				return $repo()->used_ids();
			}
		);

		add_action(
			'rest_api_init',
			static function () use ( $c ) {
				( new UsageController( $c->get( 'usage.scanner' ), $c->get( 'usage.repository' ) ) )->register_routes();
			}
		);
	}
}
