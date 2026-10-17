<?php
fbm_test( 'test_v1_routes_gone', function () {
	$v1 = array_filter( array_keys( rest_get_server()->get_routes() ), function ( $r ) {
		return 0 === strpos( $r, '/fbm/v1' );
	} );
	fbm_assert_same( array(), array_values( $v1 ), 'v1 REST routes' );
} );

fbm_test( 'test_no_settings_or_code_modules', function () {
	foreach ( array( 'FBM\\Core\\Settings', 'FBM\\Modules\\Elements\\Module', 'FBM\\Modules\\Transfer\\Module' ) as $cls ) {
		fbm_assert_same( false, class_exists( $cls ), $cls );
	}
} );

fbm_test( 'test_placeholder_guest_empty', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', FBM\Placeholder::off( 'X' ) );
	fbm_assert_same( '', FBM\Placeholder::empty( 'X' ) );
} );

fbm_test( 'test_placeholder_editor_sees_box', function () {
	fbm_as_user( 'editor', function () {
		fbm_assert_contains( 'fbm-off', FBM\Placeholder::off( 'Map' ) );
		fbm_assert_contains( 'fbm-empty', FBM\Placeholder::empty( 'Không có sản phẩm' ) );
	} );
} );
