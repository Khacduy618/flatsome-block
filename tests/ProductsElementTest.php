<?php
fbm_test( 'test_registry_finds_products', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	fbm_assert_true( isset( $all['fbm_products'] ), 'fbm_products registered' );
	fbm_assert_same( 'Products', $all['fbm_products']['name'] );
	fbm_assert_true( shortcode_exists( 'fbm_products' ), 'shortcode exists' );
} );

fbm_test( 'test_products_renders_slider_markup', function () {
	fbm_fixtures_up();
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_products source="recent" type="slider" products="4"]' );
	fbm_fixtures_down();
	fbm_assert_contains( 'slider', $html );
	fbm_assert_same( 4, substr_count( $html, 'product-small box' ) );
} );

fbm_test( 'test_products_picked_ids_override_source', function () {
	$f = fbm_fixtures_up();
	$html = do_shortcode( '[fbm_products source="recent" ids="' . $f['noimage'] . '" type="row"]' );
	$link = get_permalink( $f['noimage'] );
	fbm_fixtures_down();
	fbm_assert_same( 1, substr_count( $html, 'product-small box' ) );
	fbm_assert_contains( $link, $html );
} );

fbm_test( 'test_products_empty_guest_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', trim( do_shortcode( '[fbm_products cat="no-such-cat-xyz"]' ) ) );
} );

fbm_test( 'test_products_empty_editor_note', function () {
	$html = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_products cat="no-such-cat-xyz"]' );
	} );
	fbm_assert_contains( 'fbm-empty', $html );
} );

fbm_test( 'test_products_off_guest_blank', function () {
	$states = FBM\Plugin::get( 'states' );
	$states->set( 'fbm_products', 'off' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_products]' );
	$states->set( 'fbm_products', 'on' );
	fbm_assert_same( '', $html );
} );

fbm_test( 'test_products_escapes_class', function () {
	fbm_fixtures_up();
	$html = do_shortcode( '[fbm_products type="row" products="1" class="a&quot;onmouseover=x"]' );
	fbm_fixtures_down();
	fbm_assert_not_contains( '"onmouseover', $html );
} );
