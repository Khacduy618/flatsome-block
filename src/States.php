<?php
/**
 * Bật / Ẩn / Tắt cho element UX Builder.
 *
 * - Ẩn: element không có trong Add elements (cờ `hidden` của UX Builder); chỗ đã dùng vẫn hiển thị.
 * - Tắt: như Ẩn, và shortcode không hiển thị ngoài site (người biên tập thấy khung nét đứt).
 *
 * Option chỉ lưu tag khác "on".
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class States {

	const OPTION = 'fbm_element_states';

	const VALID = array( 'on', 'hidden', 'off' );

	/** Element khung trang: tắt sẽ làm vỡ bố cục mọi trang. */
	const LOCKED = array( 'section', 'row', 'row_inner', 'col', 'col_inner', 'col_grid', 'ux_stack', 'text', 'block', '_root' );

	/** @var Catalog */
	private $catalog;

	public function __construct( Catalog $catalog ) {
		$this->catalog = $catalog;
	}

	public function boot() {
		add_filter( 'ux_builder_shortcode_data', array( $this, 'filter_builder_data' ), 10, 2 );
		add_filter( 'pre_do_shortcode_tag', array( $this, 'filter_render' ), 9, 3 );
	}

	/**
	 * @return array<string, string> tag => hidden|off
	 */
	public function all() {
		$all = get_option( self::OPTION, array() );
		return is_array( $all ) ? $all : array();
	}

	public function get( $tag ) {
		$all = $this->all();
		return isset( $all[ $tag ] ) && in_array( $all[ $tag ], self::VALID, true ) ? $all[ $tag ] : 'on';
	}

	/**
	 * @return true|\WP_Error
	 */
	public function set( $tag, $state ) {
		$tag   = (string) $tag;
		$state = (string) $state;

		if ( ! in_array( $state, self::VALID, true ) ) {
			return new \WP_Error( 'fbm_bad_state', __( 'Trạng thái không hợp lệ.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( in_array( $tag, self::LOCKED, true ) ) {
			return new \WP_Error( 'fbm_locked', __( 'Element khung trang luôn bật.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( 'hidden' === $state && in_array( $tag, Catalog::VIRTUAL, true ) ) {
			return new \WP_Error( 'fbm_bad_state', __( 'Mục này không phải element nên chỉ có Bật / Tắt.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( ! $this->catalog->has( $tag ) ) {
			return new \WP_Error( 'fbm_unknown_tag', __( 'Không có element này.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}

		$all = $this->all();
		if ( 'on' === $state ) {
			unset( $all[ $tag ] );
		} else {
			$all[ $tag ] = $state;
		}
		update_option( self::OPTION, $all, true );
		return true;
	}

	public function filter_builder_data( $data, $tag ) {
		if ( is_array( $data ) && 'on' !== $this->get( $tag ) ) {
			$data['hidden'] = true;
		}
		return $data;
	}

	/**
	 * @param false|string $output
	 * @param string       $tag
	 * @return false|string
	 */
	public function filter_render( $output, $tag, $attr ) {
		if ( false !== $output || 'off' !== $this->get( $tag ) ) {
			return $output;
		}
		return Placeholder::off( $this->catalog->name( $tag ) );
	}
}
