<?php
/**
 * Environment check: the Flatsome theme (or a child theme) must be active.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Requirements {

	/**
	 * @return string[] Missing requirements (empty = all met).
	 */
	public static function missing() {
		$missing = array();
		if ( ! function_exists( 'flatsome_get_block_id' ) || ! post_type_exists( 'blocks' ) ) {
			$missing[] = __( 'The Flatsome theme (or a Flatsome child theme) must be active.', 'flatsome-block-manager' );
		}
		return $missing;
	}

	public static function show_notice( array $missing ) {
		add_action(
			'admin_notices',
			static function () use ( $missing ) {
				if ( ! current_user_can( 'activate_plugins' ) ) {
					return;
				}
				echo '<div class="notice notice-error"><p><strong>Flatsome Block Manager</strong> is not running:</p><ul style="list-style:disc;padding-left:20px">';
				foreach ( $missing as $item ) {
					echo '<li>' . esc_html( $item ) . '</li>';
				}
				echo '</ul></div>';
			}
		);
	}
}
