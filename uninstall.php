<?php
/**
 * Gỡ plugin. Block của Flatsome (post type `blocks`) luôn được giữ nguyên.
 * Dữ liệu riêng của plugin chỉ bị xoá khi bật "Xoá dữ liệu khi gỡ plugin" trong Cài đặt.
 *
 * @package FBM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

wp_unschedule_hook( 'fbm_cleanup_sandbox' );
wp_unschedule_hook( 'fbm_visibility_boundary' );

$fbm_settings = get_option( 'fbm_settings', array() );
if ( empty( $fbm_settings['delete_on_uninstall'] ) ) {
	return;
}

global $wpdb;

// Bảng riêng.
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fbm_block_usage" ); // phpcs:ignore
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}fbm_element_versions" ); // phpcs:ignore

// Element (CPT fbm_element) và meta của chúng.
$fbm_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s", 'fbm_element' ) );
foreach ( $fbm_ids as $fbm_id ) {
	wp_delete_post( (int) $fbm_id, true );
}

// Meta bật/tắt trên block (block vẫn giữ nguyên).
foreach ( array( '_fbm_enabled', '_fbm_show_from', '_fbm_show_until' ) as $fbm_key ) {
	delete_post_meta_by_key( $fbm_key );
}

foreach ( array( 'fbm_settings', 'fbm_db_version', 'fbm_usage_built_at', 'fbm_elements_registry' ) as $fbm_option ) {
	delete_option( $fbm_option );
}

// File đã biên dịch trong uploads.
$fbm_uploads = wp_upload_dir( null, false );
$fbm_dir     = trailingslashit( $fbm_uploads['basedir'] ) . 'fbm-elements';
if ( is_dir( $fbm_dir ) ) {
	foreach ( array_merge( (array) glob( $fbm_dir . '/*' ), array( $fbm_dir . '/.htaccess' ) ) as $fbm_file ) {
		if ( is_file( $fbm_file ) ) {
			wp_delete_file( $fbm_file );
		}
	}
	@rmdir( $fbm_dir ); // phpcs:ignore
}
