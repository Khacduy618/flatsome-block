<?php
fbm_test( 'test_element_counts', function () {
	fbm_qa_post( 'page', 'usage', '[map][map][ux_lottie]' );
	$counts = FBM\Plugin::get( 'usage' )->element_counts();
	fbm_fixtures_down();
	fbm_assert_true( isset( $counts['map'] ) && $counts['map'] >= 1, 'map counted' );
	fbm_assert_true( isset( $counts['ux_lottie'] ) && $counts['ux_lottie'] >= 1, 'lottie counted' );
} );

fbm_test( 'test_element_counts_one_per_post', function () {
	$before = FBM\Plugin::get( 'usage' )->element_counts();
	fbm_qa_post( 'page', 'usage', '[ux_countdown][ux_countdown][ux_countdown]' );
	$after = FBM\Plugin::get( 'usage' )->element_counts();
	fbm_fixtures_down();
	$b = isset( $before['ux_countdown'] ) ? $before['ux_countdown'] : 0;
	fbm_assert_same( $b + 1, $after['ux_countdown'] );
} );

fbm_test( 'test_block_counts', function () {
	$f      = fbm_fixtures_up();
	$counts = FBM\Plugin::get( 'usage' )->block_counts( array( $f['block_a'], $f['block_b'] ) );
	fbm_fixtures_down();
	fbm_assert_same( 1, $counts[ $f['block_a'] ] );
	fbm_assert_same( 0, $counts[ $f['block_b'] ] );
} );
