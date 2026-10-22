<?php
/**
 * UX Builder element catalog.
 *
 * Flatsome only loads UX Builder while the builder is open, so the plugin records the list
 * every time UX Builder runs (filter ux_builder_shortcode_data) and stores it in an option.
 * Plugin elements are not in the snapshot: they come from Elements\Registry.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Catalog {

	const OPTION = 'fbm_catalog';

	/** Virtual states, not elements. */
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
	 * Records elements listed in Add elements (skips child elements and ones the theme hides).
	 *
	 * @param array  $data
	 * @param string $tag
	 * @return array Unchanged.
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

	/** Display name, used by the placeholder. */
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
