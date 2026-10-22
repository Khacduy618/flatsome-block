<?php
/**
 * On / Hidden / Off for UX Builder elements.
 *
 * - Hidden: not listed in Add elements (UX Builder `hidden` flag); existing uses still render.
 * - Off: like Hidden, and the shortcode renders nothing on the site (editors see a dashed placeholder).
 *
 * The option stores only tags that are not "on".
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class States {

	const OPTION = 'fbm_element_states';

	const VALID = array( 'on', 'hidden', 'off' );

	/** Layout elements: turning them off would break every page layout. */
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
			return new \WP_Error( 'fbm_bad_state', __( 'Invalid state.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( in_array( $tag, self::LOCKED, true ) ) {
			return new \WP_Error( 'fbm_locked', __( 'Layout elements are always on.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( 'hidden' === $state && in_array( $tag, Catalog::VIRTUAL, true ) ) {
			return new \WP_Error( 'fbm_bad_state', __( 'This item is not an element, so it can only be On / Off.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( ! $this->catalog->has( $tag ) ) {
			return new \WP_Error( 'fbm_unknown_tag', __( 'Unknown element.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
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
