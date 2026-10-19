<?php
use FBM\Upgrade;

/** Dựng lại dữ liệu kiểu v1: block tắt / hẹn giờ, cron, element CPT, bảng phiên bản, option, file uploads. */
function fbm_v1_setup() {
	global $wpdb;
	$f = array();

	$f['disabled'] = fbm_qa_post( 'blocks', 'v1 disabled', 'x', 'fbm-qa-v1-disabled' );
	update_post_meta( $f['disabled'], '_fbm_enabled', '0' );

	$f['expired'] = fbm_qa_post( 'blocks', 'v1 expired', 'x', 'fbm-qa-v1-expired' );
	update_post_meta( $f['expired'], '_fbm_show_until', time() - DAY_IN_SECONDS );

	$f['future'] = fbm_qa_post( 'blocks', 'v1 future', 'x', 'fbm-qa-v1-future' );
	update_post_meta( $f['future'], '_fbm_show_from', time() + DAY_IN_SECONDS );

	$f['live'] = fbm_qa_post( 'blocks', 'v1 live', 'x', 'fbm-qa-v1-live' );
	update_post_meta( $f['live'], '_fbm_show_from', time() - DAY_IN_SECONDS );
	update_post_meta( $f['live'], '_fbm_show_until', time() + DAY_IN_SECONDS );

	wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'fbm_visibility_boundary', array( $f['future'] ) );
	wp_schedule_single_event( time() + HOUR_IN_SECONDS, 'fbm_cleanup_sandbox' );

	$f['element'] = wp_insert_post(
		array(
			'post_type'   => 'fbm_element',
			'post_status' => 'publish',
			'post_title'  => 'FBM QA v1 element',
		)
	);
	update_post_meta( $f['element'], '_fbm_tag', 'fbm_qa_v1tag' );
	update_post_meta( $f['element'], '_fbm_draft', '<?php echo 1;' );
	update_post_meta( $f['element'], '_fbm_qa', 1 );

	$wpdb->query( "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}fbm_element_versions ( id bigint(20) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (id) )" ); // phpcs:ignore
	update_option( 'fbm_settings', array( 'delete_on_uninstall' => false ) );
	update_option( 'fbm_elements_registry', array( 'fbm_qa_v1old' => array( 'tag' => 'fbm_qa_v1old' ) ) );

	$up  = wp_upload_dir( null, false );
	$dir = trailingslashit( $up['basedir'] ) . 'fbm-elements';
	wp_mkdir_p( $dir );
	file_put_contents( $dir . '/fbm_qa_v1tag-1.php', '<?php // v1' );
	file_put_contents( $dir . '/.htaccess', 'Deny from all' );
	$f['dir'] = $dir;

	$f['page'] = fbm_qa_post( 'page', 'v1 page', '[fbm_qa_v1tag][fbm_product_slider limit="2"]' );
	delete_transient( FBM\Usage\UsageIndex::ELEMENT_CACHE );

	return $f;
}

fbm_test( 'test_v1_disabled_block_becomes_off', function () {
	$f = fbm_v1_setup();
	Upgrade::run();
	$state = get_post_meta( $f['disabled'], '_fbm_state', true );
	$old   = get_post_meta( $f['disabled'], '_fbm_enabled', true );
	fbm_fixtures_down();
	fbm_assert_same( 'off', $state );
	fbm_assert_same( '', $old );
} );

fbm_test( 'test_v1_block_hidden_by_schedule_becomes_off', function () {
	$f = fbm_v1_setup();
	Upgrade::run();
	$got = array(
		'expired' => get_post_meta( $f['expired'], '_fbm_state', true ),
		'future'  => get_post_meta( $f['future'], '_fbm_state', true ),
		'live'    => get_post_meta( $f['live'], '_fbm_state', true ),
	);
	fbm_fixtures_down();
	fbm_assert_same( array( 'expired' => 'off', 'future' => 'off', 'live' => '' ), $got );
} );

fbm_test( 'test_v1_schedule_meta_removed', function () {
	global $wpdb;
	$f = fbm_v1_setup();
	Upgrade::run();
	$left = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key IN ('_fbm_enabled','_fbm_show_from','_fbm_show_until')" );
	$cron = array( wp_next_scheduled( 'fbm_visibility_boundary', array( $f['future'] ) ), wp_next_scheduled( 'fbm_cleanup_sandbox' ) );
	fbm_fixtures_down();
	fbm_assert_same( 0, $left );
	fbm_assert_same( array( false, false ), $cron );
} );

fbm_test( 'test_v1_elements_removed', function () {
	global $wpdb;
	$f = fbm_v1_setup();
	Upgrade::run();
	$got = array(
		'posts'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'fbm_element'" ),
		'meta'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE post_id = %d", $f['element'] ) ),
		'table'    => (string) $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}fbm_element_versions'" ),
		'settings' => get_option( 'fbm_settings', 'gone' ),
		'registry' => get_option( 'fbm_elements_registry', 'gone' ),
		'dir'      => is_dir( $f['dir'] ),
	);
	fbm_fixtures_down();
	fbm_assert_same( array( 'posts' => 0, 'meta' => 0, 'table' => '', 'settings' => 'gone', 'registry' => 'gone', 'dir' => false ), $got );
} );

fbm_test( 'test_v1_report_lists_used_tags', function () {
	fbm_v1_setup();
	Upgrade::run();
	$report = get_option( Upgrade::REPORT );
	fbm_fixtures_down();
	fbm_assert_true( isset( $report['used']['fbm_qa_v1tag'] ), 'v1 element tag in use' );
	fbm_assert_true( isset( $report['used']['fbm_product_slider'] ), 'slider alias in use' );
	fbm_assert_true( in_array( 'fbm_qa_v1tag', $report['tags'], true ) && in_array( 'fbm_qa_v1old', $report['tags'], true ) );
	fbm_assert_true( ! in_array( 'fbm_product_slider', $report['tags'], true ) && ! in_array( 'fbm_before_after', $report['tags'], true ), 'tags still provided by v2' );
} );

fbm_test( 'test_v1_report_notice', function () {
	fbm_v1_setup();
	Upgrade::run();
	$html = fbm_as_user( 'editor', function () {
		return Upgrade::notice_html();
	} );
	wp_set_current_user( 0 );
	$guest = Upgrade::notice_html();
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm_product_slider', $html );
	fbm_assert_contains( 'fbm_products', $html );
	fbm_assert_contains( 'fbm_qa_v1tag', $html );
	fbm_assert_contains( 'action=fbm_v1_dismiss', $html );
	fbm_assert_same( '', $guest );
} );

fbm_test( 'test_v1_slider_alias_maps_atts', function () {
	$got = Upgrade::map_v1_slider_atts(
		array( 'source' => 'best_selling', 'limit' => '9', 'cat' => '150,18', 'per_view' => '5', 'autoplay' => 'true', 'autoplay_ms' => '2000', 'radius' => '3', 'title' => 'abc' )
	);
	fbm_assert_same( array( 'source' => 'best_selling', 'products' => '9', 'cat' => '150,18', 'columns' => '5', 'auto_slide' => '2000', 'type' => 'slider' ), $got );
} );

fbm_test( 'test_v1_slider_alias_maps_rest', function () {
	$got = Upgrade::map_v1_slider_atts(
		array( 'per_view_md' => '2', 'per_view_sm' => '1', 'dots' => 'false', 'loop' => 'false', 'autoplay' => 'false', 'orderby' => 'popularity', 'order' => 'ASC', 'card_style' => 'shadow', 'show_price' => 'false', 'accent' => '#f00', 'image_ratio' => '4/3', 'in_stock' => 'true' )
	);
	fbm_assert_same( array( 'columns__md' => '2', 'columns__sm' => '1', 'slider_bullets' => 'false', 'infinitive' => 'false', 'auto_slide' => 'false', 'orderby' => 'popularity', 'order' => 'ASC', 'in_stock' => 'true', 'type' => 'slider' ), $got );
} );

fbm_test( 'test_v1_slider_alias_renders_products_with_title', function () {
	fbm_fixtures_up();
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_product_slider source="recent" limit="2" title="Hàng mới" link_text="Xem" link_url="/shop/"]' );
	fbm_fixtures_down();
	fbm_assert_same( 2, substr_count( $html, 'product-small box' ) );
	fbm_assert_contains( 'section-title', $html );
	fbm_assert_contains( 'Hàng mới', $html );
	fbm_assert_contains( '/shop/', $html );
} );

fbm_test( 'test_v1_slider_alias_off_when_products_off', function () {
	$saved = get_option( FBM\States::OPTION, null );
	fbm_fixtures_up();
	FBM\Plugin::get( 'states' )->set( 'fbm_products', 'off' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_product_slider source="recent" limit="2" title="Hàng mới"]' );
	fbm_fixtures_down();
	null === $saved ? delete_option( FBM\States::OPTION ) : update_option( FBM\States::OPTION, $saved );
	fbm_assert_same( '', trim( $html ) );
} );

fbm_test( 'test_v1_unknown_tag_renders_empty', function () {
	wp_set_current_user( 0 );
	$guest  = do_shortcode( '[fbm_hello title="x"]' );
	$editor = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_hello title="x"]' );
	} );
	fbm_assert_same( '', $guest );
	fbm_assert_contains( 'fbm-off', $editor );
	fbm_assert_contains( 'fbm_hello (v1)', $editor );
} );

fbm_test( 'test_v1_element_tag_renders_empty_after_upgrade', function () {
	fbm_v1_setup();
	Upgrade::run();
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_qa_v1tag]' );
	fbm_fixtures_down();
	fbm_assert_same( '', $html );
} );

fbm_test( 'test_upgrade_runs_once', function () {
	$f = fbm_v1_setup();
	delete_option( Upgrade::VERSION_OPTION );
	Upgrade::maybe_run();
	$version = get_option( Upgrade::VERSION_OPTION );
	update_post_meta( $f['live'], '_fbm_enabled', '0' );
	Upgrade::maybe_run();
	$still = get_post_meta( $f['live'], '_fbm_enabled', true );
	fbm_fixtures_down();
	fbm_assert_same( FBM_VERSION, $version );
	fbm_assert_same( '0', $still );
} );

fbm_test( 'test_uninstall_removes_v2_data', function () {
	global $wpdb;
	$keep = array();
	foreach ( array( 'fbm_element_states', 'fbm_catalog', 'fbm_version', 'fbm_v1_report', 'fbm_db_version', 'fbm_usage_built_at' ) as $o ) {
		$keep[ $o ] = get_option( $o, null );
	}
	$f = fbm_fixtures_up();
	update_post_meta( $f['block_a'], '_fbm_state', 'off' );
	set_transient( 'fbm_element_usage', array( 'map' => 1 ), HOUR_IN_SECONDS );
	update_option( 'fbm_v1_report', array( 'tags' => array(), 'used' => array(), 'dismissed' => true ) );

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
		define( 'WP_UNINSTALL_PLUGIN', 'flatsome-block-manager/flatsome-block-manager.php' );
	}
	include FBM_DIR . 'uninstall.php';

	$got = array(
		'options'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name IN ('fbm_element_states','fbm_catalog','fbm_version','fbm_v1_report','fbm_db_version','fbm_usage_built_at')" ),
		'meta'      => get_post_meta( $f['block_a'], '_fbm_state', true ),
		'transient' => get_transient( 'fbm_element_usage' ),
		'table'     => (string) $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}fbm_block_usage'" ),
		'block'     => get_post_type( $f['block_a'] ),
	);

	// Khôi phục site test.
	FBM\Installer::activate();
	foreach ( $keep as $o => $v ) {
		null === $v ? delete_option( $o ) : update_option( $o, $v, false );
	}
	fbm_fixtures_down();

	fbm_assert_same( array( 'options' => 0, 'meta' => '', 'transient' => false, 'table' => '', 'block' => 'blocks' ), $got );
} );

fbm_test( 'zz_upgrade_cleanup', function () {
	// Bảng / option / thư mục v1 giả do test tạo đều đã bị Upgrade::run() xoá; chỉ dọn report.
	delete_option( Upgrade::REPORT );
	fbm_assert_true( true );
} );
