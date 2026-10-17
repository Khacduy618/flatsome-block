<?php
function fbm_compare( $atts ) {
	return do_shortcode( '[fbm_product_compare ' . $atts . ']' );
}

fbm_test( 'test_compare_needs_two_products', function () {
	$f = fbm_fixtures_up();
	wp_set_current_user( 0 );
	$one   = fbm_compare( 'ids="' . $f['simple'] . '"' );
	$draft = fbm_compare( 'ids="' . $f['simple'] . ',999999"' );
	fbm_fixtures_down();
	fbm_assert_same( '', $one );
	fbm_assert_same( '', $draft );
} );

fbm_test( 'test_compare_editor_note_when_too_few', function () {
	$html = fbm_as_user( 'editor', function () {
		return fbm_compare( 'ids=""' );
	} );
	fbm_assert_contains( 'fbm-empty', $html );
} );

fbm_test( 'test_compare_table_semantics', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['attrs'] . ',' . $f['sale'] . '" title="Chọn loại da"' );
	fbm_fixtures_down();
	fbm_assert_contains( '<caption', $html );
	fbm_assert_contains( 'Chọn loại da', $html );
	fbm_assert_same( 3, substr_count( $html, '<th scope="col"' ) );
	fbm_assert_true( substr_count( $html, '<th scope="row"' ) >= 4, 'row headers' );
	fbm_assert_contains( 'fbm-compare__scroll', $html );
} );

fbm_test( 'test_compare_missing_cell_dash', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'Bản rộng', $html );
	fbm_assert_contains( '<td>—</td>', $html );
} );

fbm_test( 'test_compare_only_diff_drops_equal_rows', function () {
	$f    = fbm_fixtures_up();
	$all  = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['attrs'] . '"' );
	$diff = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['attrs'] . '" only_diff="true"' );
	fbm_fixtures_down();
	fbm_assert_contains( '>Giá</th>', $all );
	fbm_assert_not_contains( '>Giá</th>', $diff, 'same price 50 dropped' );
	fbm_assert_contains( 'Bản rộng', $diff, 'different attribute kept' );
} );

fbm_test( 'test_compare_attribute_filter', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['attrs'] . '" attributes="Loại da"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'Loại da', $html );
	fbm_assert_not_contains( 'Bản rộng', $html );
} );

fbm_test( 'test_compare_add_to_cart_markup', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_compare( 'ids="' . $f['simple'] . ',' . $f['noimage'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'add_to_cart_button', $html );
	fbm_assert_contains( 'ajax_add_to_cart', $html );
} );

fbm_test( 'test_compare_current_upsell_source', function () {
	global $product;
	$f = fbm_fixtures_up();
	$p = wc_get_product( $f['simple'] );
	$p->set_upsell_ids( array( $f['sale'] ) );
	$p->save();
	$product = wc_get_product( $f['simple'] ); // phpcs:ignore
	$html    = fbm_compare( 'source="current_upsell"' );
	$product = null; // phpcs:ignore
	fbm_fixtures_down();
	fbm_assert_same( 2, substr_count( $html, '<th scope="col"' ) );
} );

fbm_test( 'test_compare_css_enqueued_only_when_used', function () {
	$f = fbm_fixtures_up();
	wp_dequeue_style( 'fbm-product-compare' );
	$before = wp_style_is( 'fbm-product-compare', 'enqueued' );
	fbm_compare( 'ids="' . $f['simple'] . ',' . $f['noimage'] . '"' );
	$after = wp_style_is( 'fbm-product-compare', 'enqueued' );
	fbm_fixtures_down();
	fbm_assert_same( false, $before );
	fbm_assert_same( true, $after );
} );

fbm_test( 'test_compare_css_rules', function () {
	$css = file_get_contents( FBM_DIR . 'elements/product-compare/compare.css' );
	$css = preg_replace( '/var\([^)]*\)/', 'var()', $css ); // giá trị dự phòng trong var() được phép
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|!important|font-family/i', $css ), 'no raw colors/important/font' );
	foreach ( preg_split( '/\}/', $css ) as $block ) {
		$sel = trim( preg_replace( '#/\*.*?\*/#s', '', strstr( $block, '{', true ) ?: '' ) );
		if ( '' !== $sel && 0 !== strpos( $sel, '@media' ) ) {
			fbm_assert_contains( '.fbm-compare', $sel, 'namespaced: ' . $sel );
		}
	}
} );
