<?php
/**
 * Quyền truy cập. Dùng capability có sẵn của WP, không tạo role mới.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

final class Capabilities {

	/** Blocks dùng capability_type 'page' trong Flatsome. */
	public static function can_manage_blocks() {
		return current_user_can( 'edit_pages' );
	}

	/**
	 * Viết code PHP chạy trên server: chỉ admin có unfiltered_html,
	 * và tôn trọng DISALLOW_FILE_EDIT trong wp-config.php.
	 */
	public static function can_edit_code() {
		if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
			return false;
		}
		return current_user_can( 'manage_options' ) && current_user_can( 'unfiltered_html' );
	}

	public static function can_manage_settings() {
		return current_user_can( 'manage_options' );
	}
}
