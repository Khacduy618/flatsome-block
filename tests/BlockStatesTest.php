<?php
use FBM\BlockStates;

fbm_test( 'test_block_default_on', function () {
	$f = fbm_fixtures_up();
	$on = FBM\Plugin::get( 'block_states' )->get( $f['block_a'] );
	fbm_fixtures_down();
	fbm_assert_same( 'on', $on );
} );

fbm_test( 'test_set_page_id_rejected', function () {
	$page = fbm_qa_post( 'page', 'not a block', '' );
	$r    = FBM\Plugin::get( 'block_states' )->set( $page, 'off' );
	fbm_fixtures_down();
	fbm_assert_true( is_wp_error( $r ) && 'fbm_not_block' === $r->get_error_code() );
} );

fbm_test( 'test_block_bad_state_rejected', function () {
	$f = fbm_fixtures_up();
	$r = FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'delete' );
	fbm_fixtures_down();
	fbm_assert_true( is_wp_error( $r ) && 'fbm_bad_state' === $r->get_error_code() );
} );

fbm_test( 'test_block_off_guest_empty', function () {
	$f = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'off' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[block id="fbm-qa-a"]' );
	fbm_fixtures_down();
	fbm_assert_same( '', trim( $html ) );
} );

fbm_test( 'test_block_off_editor_placeholder', function () {
	$f = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'off' );
	$html = fbm_as_user( 'editor', function () {
		return do_shortcode( '[block id="fbm-qa-a"]' );
	} );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-off', $html );
} );

fbm_test( 'test_block_hidden_still_renders', function () {
	$f = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'hidden' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[block id="fbm-qa-a"]' );
	fbm_fixtures_down();
	fbm_assert_contains( 'map-height', $html );
} );

fbm_test( 'test_hidden_block_excluded_from_builder_search', function () {
	$f  = fbm_fixtures_up();
	$bs = FBM\Plugin::get( 'block_states' );
	$bs->set( $f['block_a'], 'hidden' );
	$bs->set( $f['block_b'], 'off' );
	$on   = fbm_qa_post( 'blocks', 'block on', 'x', 'fbm-qa-on' );
	$args = apply_filters( 'ux_builder_ajax_posts_search_posts_get_posts_args', array( 'post_type' => array( 'blocks' ), 'post__not_in' => array( 7 ) ) );
	fbm_fixtures_down();
	sort( $args['post__not_in'] );
	$expected = array( 7, $f['block_a'], $f['block_b'] );
	sort( $expected );
	fbm_assert_same( $expected, $args['post__not_in'] );
	fbm_assert_true( ! in_array( $on, $args['post__not_in'], true ) );
} );

fbm_test( 'test_search_args_untouched_for_pages', function () {
	$f = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'hidden' );
	$args = apply_filters( 'ux_builder_ajax_posts_search_posts_get_posts_args', array( 'post_type' => array( 'page' ) ) );
	fbm_fixtures_down();
	fbm_assert_true( ! isset( $args['post__not_in'] ) );
} );

fbm_test( 'test_block_rows_include_state_and_usage', function () {
	$f    = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_a'], 'hidden' );
	$rows = array_column( FBM\Plugin::get( 'block_states' )->rows(), null, 'id' );
	fbm_fixtures_down();
	fbm_assert_same( 'hidden', $rows[ $f['block_a'] ]['state'] );
	fbm_assert_same( 'fbm-qa-a', $rows[ $f['block_a'] ]['slug'] );
	fbm_assert_same( 1, $rows[ $f['block_a'] ]['usage'], 'block_b references block_a' );
} );
