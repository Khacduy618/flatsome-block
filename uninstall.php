<?php
/**
 * Gỡ plugin: xoá trạng thái Bật / Ẩn / Tắt, danh sách element đã ghi, chỉ mục nơi dùng.
 * UX Block của Flatsome (post type `blocks`) và nội dung trang luôn được giữ nguyên.
 *
 * @package FBM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

foreach ( array( 'fbm_element_states', 'fbm_catalog', 'fbm_version', 'fbm_v1_report', 'fbm_db_version', 'fbm_usage_built_at' ) as $fbm_option ) {
	delete_option( $fbm_option );
}
delete_transient( 'fbm_element_usage' );
delete_post_meta_by_key( '_fbm_state' );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fbm_block_usage" ); // phpcs:ignore

// Cron của v1 nếu gỡ khi chưa từng chạy bản nâng cấp.
wp_unschedule_hook( 'fbm_cleanup_sandbox' );
wp_unschedule_hook( 'fbm_visibility_boundary' );
