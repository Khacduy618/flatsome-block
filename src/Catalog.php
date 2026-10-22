<?php
/**
 * Danh sách element của UX Builder.
 *
 * Flatsome chỉ nạp UX Builder khi đang mở trình dựng, nên plugin ghi lại danh sách
 * mỗi lần UX Builder chạy (filter ux_builder_shortcode_data) và lưu vào option.
 * Element của plugin không nằm trong snapshot: chúng lấy từ Elements\Registry.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Catalog {

	const OPTION = 'fbm_catalog';

	/** Trạng thái ảo, không phải element. */
	const VIRTUAL = array();

	/** @var array<string, array{name: string, category: string}> */
	private $seen = array();

	public function boot() {
		add_filter( 'ux_builder_shortcode_data', array( $this, 'record' ), 5, 2 );
		add_action(
			'shutdown',
			function () {
				if ( function_exists( 'ux_builder_is_active' ) && ux_builder_is_active() ) {
					$this->persist();
				}
			}
		);
	}

	/**
	 * Ghi nhận element có trong danh sách Add elements (bỏ element con / ẩn sẵn của theme).
	 *
	 * @param array  $data
	 * @param string $tag
	 * @return array Không đổi.
	 */
	public function record( $data, $tag ) {
		if ( is_array( $data ) && empty( $data['hidden'] ) && ! $this->is_plugin_tag( $tag ) ) {
			$this->seen[ (string) $tag ] = array(
				'name'     => isset( $data['name'] ) ? wp_strip_all_tags( (string) $data['name'] ) : (string) $tag,
				'category' => isset( $data['category'] ) ? wp_strip_all_tags( (string) $data['category'] ) : '',
			);
		}
		return $data;
	}

	public function persist() {
		if ( ! $this->seen ) {
			return;
		}
		$list = $this->seen;
		uasort(
			$list,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);
		if ( get_option( self::OPTION ) !== $list ) {
			update_option( self::OPTION, $list, false );
		}
		$this->seen = array();
	}

	/**
	 * @return array<string, array{name: string, category: string}>
	 */
	public function flatsome() {
		$list = get_option( self::OPTION, array() );
		return is_array( $list ) ? $list : array();
	}

	public function has( $tag ) {
		$tag = (string) $tag;
		if ( in_array( $tag, self::VIRTUAL, true ) || $this->is_plugin_tag( $tag ) ) {
			return true;
		}
		return array_key_exists( $tag, $this->flatsome() );
	}

	/** Tên hiển thị, dùng cho placeholder. */
	public function name( $tag ) {
		$list = $this->flatsome();
		if ( isset( $list[ $tag ]['name'] ) ) {
			return $list[ $tag ]['name'];
		}
		$registry = Plugin::get( 'registry' );
		$all      = $registry ? $registry->all() : array();
		return isset( $all[ $tag ]['name'] ) ? $all[ $tag ]['name'] : (string) $tag;
	}

	private function is_plugin_tag( $tag ) {
		$registry = Plugin::get( 'registry' );
		return $registry && array_key_exists( (string) $tag, $registry->all() );
	}
}
