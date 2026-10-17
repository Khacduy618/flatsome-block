<?php
/**
 * Kiểm tra môi trường: cần theme Flatsome (hoặc child theme) đang bật.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

final class Requirements {

	/**
	 * @return string[] Danh sách yêu cầu còn thiếu (rỗng = đủ).
	 */
	public static function missing() {
		$missing = array();
		if ( ! function_exists( 'flatsome_get_block_id' ) || ! post_type_exists( 'blocks' ) ) {
			$missing[] = __( 'Theme Flatsome (hoặc child theme của Flatsome) phải đang được kích hoạt.', 'flatsome-block-manager' );
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
				echo '<div class="notice notice-error"><p><strong>Flatsome Block Manager</strong> chưa chạy:</p><ul style="list-style:disc;padding-left:20px">';
				foreach ( $missing as $item ) {
					echo '<li>' . esc_html( $item ) . '</li>';
				}
				echo '</ul></div>';
			}
		);
	}
}
