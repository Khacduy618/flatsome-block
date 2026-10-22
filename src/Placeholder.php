<?php
/**
 * Editor-only hints: a dashed box for things that are Off, a muted line for empty elements.
 * Guests, feeds and REST always get an empty string.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Placeholder {

	public static function visible() {
		if ( is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return false;
		}
		if ( function_exists( 'ux_builder_is_active' ) && ux_builder_is_active() ) {
			return true;
		}
		return current_user_can( 'edit_pages' );
	}

	/**
	 * @param string $label Name of the element / UX Block that is Off.
	 */
	public static function off( $label ) {
		if ( ! self::visible() ) {
			return '';
		}
		return sprintf(
			'<div class="fbm-off" style="border:1px dashed #c3c4c7;padding:10px 14px;margin:6px 0;font:13px/1.4 -apple-system,Segoe UI,Roboto,sans-serif;color:#50575e">%1$s: <strong>%2$s</strong> <small style="opacity:.7">(%3$s)</small></div>',
			esc_html__( 'Off', 'flatsome-block-manager' ),
			esc_html( $label ),
			esc_html__( 'only editors see this box', 'flatsome-block-manager' )
		);
	}

	/**
	 * @param string $text Why the element has nothing to show.
	 */
	public static function empty( $text ) {
		if ( ! self::visible() ) {
			return '';
		}
		return '<p class="fbm-empty op-7">' . esc_html( $text ) . '</p>';
	}
}
