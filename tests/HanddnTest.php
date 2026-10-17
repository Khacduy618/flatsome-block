<?php
use FBM\Handdn\Product3DButton;

function fbm_attr_html( $atts = '' ) {
	return do_shortcode( '[handdn_product_attributes ' . $atts . ']' );
}

fbm_test( 'test_attrs_by_id_lists_visible_attributes', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_attr_html( 'id="' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( '<div class="handdn-product-attributes">', $html );
	fbm_assert_same( 3, substr_count( $html, 'handdn-product-attributes__item' ), 'Bản rộng, Loại da, Khoá' );
	fbm_assert_contains( 'Bản rộng', $html );
	fbm_assert_contains( '<span class="handdn-product-attributes__value">22mm</span>', $html );
	fbm_assert_not_contains( 'Ghi chú nội bộ', $html, 'hidden attribute' );
} );

fbm_test( 'test_attrs_trims_and_drops_empty_values', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_attr_html( 'id="' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( '>Deployant</span>', $html );
	fbm_assert_same( 2, substr_count( preg_replace( '/.*Khoá/s', '', $html ), 'handdn-product-attributes__value"' ) );
} );

fbm_test( 'test_attrs_escapes_values', function () {
	$f = fbm_fixtures_up();
	$p = wc_get_product( $f['noimage'] );
	$a = new WC_Product_Attribute();
	$a->set_name( 'Màu chỉ' );
	$a->set_options( array( '<script>alert(1)</script>Đỏ' ) );
	$a->set_visible( true );
	$p->set_attributes( array( $a ) );
	$p->save();
	$html = fbm_attr_html( 'id="' . $f['noimage'] . '"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( '<script>', $html );
} );

fbm_test( 'test_attrs_draft_product_blank', function () {
	$f = fbm_fixtures_up();
	wp_update_post( array( 'ID' => $f['attrs'], 'post_status' => 'draft' ) );
	wp_set_current_user( 0 );
	$html = fbm_attr_html( 'id="' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_same( '', $html );
} );

fbm_test( 'test_attrs_non_product_id_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', fbm_attr_html( 'id="1"' ) );
} );

fbm_test( 'test_attrs_no_context_guest_blank_editor_note', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', fbm_attr_html() );
	fbm_assert_contains( 'op-7', fbm_as_user( 'editor', function () {
		return fbm_attr_html();
	} ) );
} );

fbm_test( 'test_attrs_global_product_string_untouched', function () {
	global $product;
	$product = 'some-slug'; // phpcs:ignore
	$html    = fbm_attr_html();
	$after   = $product;
	$product = null; // phpcs:ignore
	fbm_assert_same( 'some-slug', $after );
	fbm_assert_true( is_string( $html ) );
} );

fbm_test( 'test_attrs_global_wc_product_used', function () {
	global $product;
	$f       = fbm_fixtures_up();
	$product = wc_get_product( $f['attrs'] ); // phpcs:ignore
	$html    = fbm_attr_html();
	$product = null; // phpcs:ignore
	fbm_fixtures_down();
	fbm_assert_contains( 'handdn-product-attributes__item', $html );
} );

fbm_test( 'test_attrs_class_and_visibility_sanitized', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_attr_html( 'id="' . $f['attrs'] . '" class="mb-0 bad#cl@ss" visibility="hide-for-small"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="handdn-product-attributes mb-0 badclss hide-for-small"', $html );
} );

fbm_test( 'test_attributes_helper_shared', function () {
	$f     = fbm_fixtures_up();
	$items = FBM\Products\Attributes::of( wc_get_product( $f['attrs'] ) );
	fbm_fixtures_down();
	fbm_assert_same( array( 'Bản rộng', 'Loại da', 'Khoá' ), wp_list_pluck( $items, 'label' ) );
	fbm_assert_same( array( '20mm', '22mm' ), $items[0]['values'] );
} );

fbm_test( 'test_3d_javascript_link_no_button', function () {
	$f = fbm_fixtures_up();
	update_post_meta( $f['simple'], '3D-link', 'javascript:alert(1)' );
	$html = ( new Product3DButton( FBM\Plugin::get( 'states' ) ) )->form_html( $f['simple'] );
	fbm_fixtures_down();
	fbm_assert_same( '', $html );
} );

fbm_test( 'test_3d_valid_link_buttons', function () {
	$f = fbm_fixtures_up();
	update_post_meta( $f['simple'], '3D link', 'https://example.com/3d?a=1&b=2' );
	$b    = new Product3DButton( FBM\Plugin::get( 'states' ) );
	$form = $b->form_html( $f['simple'] );
	$gal  = $b->gallery_html( $f['simple'] );
	fbm_fixtures_down();
	fbm_assert_contains( 'data-handdn-3d="form"', $form );
	fbm_assert_contains( 'href="https://example.com/3d?a=1&#038;b=2"', $form );
	fbm_assert_contains( 'data-handdn-3d="gallery"', $gal );
	fbm_assert_contains( 'handdn-gallery-wall-cardclip-path', $gal );
	fbm_assert_contains( 'aria-hidden="true"', $gal );
} );

fbm_test( 'test_3d_off_state_no_button', function () {
	$f      = fbm_fixtures_up();
	$states = FBM\Plugin::get( 'states' );
	update_post_meta( $f['simple'], '3D-link', 'https://example.com/3d' );
	$states->set( 'handdn_3d_button', 'off' );
	$b    = new Product3DButton( $states );
	$html = $b->form_html( $f['simple'] ) . $b->gallery_html( $f['simple'] );
	$states->set( 'handdn_3d_button', 'on' );
	fbm_fixtures_down();
	fbm_assert_same( '', $html );
} );

fbm_test( 'test_3d_hidden_state_rejected', function () {
	$r = FBM\Plugin::get( 'states' )->set( 'handdn_3d_button', 'hidden' );
	fbm_assert_true( is_wp_error( $r ) && 'fbm_bad_state' === $r->get_error_code() );
} );
