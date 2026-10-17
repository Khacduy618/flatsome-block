<?php
use FBM\Catalog;
use FBM\States;

// Giữ snapshot thật của site; khôi phục ở test cuối.
$GLOBALS['fbm_saved_catalog'] = get_option( Catalog::OPTION, null );
$GLOBALS['fbm_saved_states']  = get_option( States::OPTION, null );

function fbm_states_test_setup() {
	update_option(
		Catalog::OPTION,
		array(
			'ux_lottie' => array( 'name' => 'Lottie', 'category' => 'Content' ),
			'map'       => array( 'name' => 'Map', 'category' => 'Content' ),
			'section'   => array( 'name' => 'Section', 'category' => 'Layout' ),
		),
		false
	);
	delete_option( States::OPTION );
	return FBM\Plugin::get( 'states' );
}

fbm_test( 'test_default_state_on', function () {
	fbm_assert_same( 'on', fbm_states_test_setup()->get( 'ux_lottie' ) );
} );

fbm_test( 'test_locked_cannot_change', function () {
	$r = fbm_states_test_setup()->set( 'section', 'off' );
	fbm_assert_true( is_wp_error( $r ) && 'fbm_locked' === $r->get_error_code() );
} );

fbm_test( 'test_unknown_tag_rejected', function () {
	$r = fbm_states_test_setup()->set( 'evil_tag', 'off' );
	fbm_assert_true( is_wp_error( $r ) && 'fbm_unknown_tag' === $r->get_error_code() );
} );

fbm_test( 'test_bad_state_rejected', function () {
	$r = fbm_states_test_setup()->set( 'ux_lottie', 'delete' );
	fbm_assert_true( is_wp_error( $r ) && 'fbm_bad_state' === $r->get_error_code() );
} );

fbm_test( 'test_set_and_get_persist', function () {
	$s = fbm_states_test_setup();
	fbm_assert_true( $s->set( 'ux_lottie', 'hidden' ) );
	fbm_assert_same( array( 'ux_lottie' => 'hidden' ), get_option( States::OPTION ) );
	fbm_assert_true( $s->set( 'ux_lottie', 'on' ) );
	fbm_assert_same( array(), get_option( States::OPTION ), 'on removes the record' );
} );

fbm_test( 'test_builder_data_hidden', function () {
	$s = fbm_states_test_setup();
	$s->set( 'ux_lottie', 'hidden' );
	$data = apply_filters( 'ux_builder_shortcode_data', array( 'hidden' => false, 'name' => 'Lottie' ), 'ux_lottie' );
	fbm_assert_same( true, $data['hidden'] );
	$other = apply_filters( 'ux_builder_shortcode_data', array( 'hidden' => false, 'name' => 'Map' ), 'map' );
	fbm_assert_same( false, $other['hidden'] );
} );

fbm_test( 'test_off_renders_empty_for_guest', function () {
	$s = fbm_states_test_setup();
	wp_set_current_user( 0 );
	fbm_assert_contains( 'map-height', do_shortcode( '[map]' ), 'on renders' );
	$s->set( 'map', 'off' );
	fbm_assert_same( '', do_shortcode( '[map]' ) );
	$s->set( 'map', 'on' );
} );

fbm_test( 'test_off_editor_sees_placeholder', function () {
	$s = fbm_states_test_setup();
	$s->set( 'map', 'off' );
	fbm_as_user( 'editor', function () {
		fbm_assert_contains( 'fbm-off', do_shortcode( '[map]' ) );
	} );
	$s->set( 'map', 'on' );
} );

fbm_test( 'test_off_element_inside_block_renders_empty_for_guest', function () {
	$s = fbm_states_test_setup();
	fbm_fixtures_up();
	$s->set( 'map', 'off' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[block id="fbm-qa-b"]' );
	$s->set( 'map', 'on' );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'map-height', $html );
	fbm_assert_not_contains( 'fbm-off', $html );
} );

fbm_test( 'test_catalog_empty_flatsome_list', function () {
	delete_option( Catalog::OPTION );
	$c = FBM\Plugin::get( 'catalog' );
	fbm_assert_same( array(), $c->flatsome() );
	fbm_assert_same( false, $c->has( 'ux_lottie' ) );
	fbm_assert_same( true, $c->has( 'handdn_3d_button' ) );
} );

fbm_test( 'test_catalog_records_builder_elements', function () {
	$c = FBM\Plugin::get( 'catalog' );
	$c->persist(); // bỏ phần các test trước đã ghi nhận
	delete_option( Catalog::OPTION );
	apply_filters( 'ux_builder_shortcode_data', array( 'hidden' => false, 'name' => 'Lottie', 'category' => 'Content' ), 'ux_lottie' );
	apply_filters( 'ux_builder_shortcode_data', array( 'hidden' => true, 'name' => 'Col', 'category' => 'Layout' ), 'col_inner' );
	$c->persist();
	fbm_assert_same( array( 'ux_lottie' => array( 'name' => 'Lottie', 'category' => 'Content' ) ), get_option( Catalog::OPTION ) );
} );

fbm_test( 'zz_restore_site_options', function () {
	foreach ( array( Catalog::OPTION => 'fbm_saved_catalog', States::OPTION => 'fbm_saved_states' ) as $opt => $key ) {
		null === $GLOBALS[ $key ] ? delete_option( $opt ) : update_option( $opt, $GLOBALS[ $key ], false );
	}
	fbm_assert_true( true );
} );
