<?php
use FBM\Products\Loop;
use FBM\Products\Query;

fbm_test( 'test_query_excludes_hidden_from_catalog', function () {
	$f   = fbm_fixtures_up();
	$ids = Query::ids( array( 'source' => 'recent', 'products' => 48 ) );
	fbm_fixtures_down();
	fbm_assert_true( in_array( $f['simple'], $ids, true ), 'simple listed' );
	fbm_assert_true( ! in_array( $f['hidden'], $ids, true ), 'hidden excluded' );
} );

fbm_test( 'test_query_respects_hide_out_of_stock', function () {
	$f    = fbm_fixtures_up();
	$prev = get_option( 'woocommerce_hide_out_of_stock_items' );
	update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
	$shown = Query::ids( array( 'source' => 'recent', 'products' => 48 ) );
	update_option( 'woocommerce_hide_out_of_stock_items', 'yes' );
	$hidden = Query::ids( array( 'source' => 'recent', 'products' => 48 ) );
	update_option( 'woocommerce_hide_out_of_stock_items', $prev );
	fbm_fixtures_down();
	fbm_assert_true( in_array( $f['outofstock'], $shown, true ), 'shown when setting off' );
	fbm_assert_true( ! in_array( $f['outofstock'], $hidden, true ), 'hidden when setting on' );
} );

fbm_test( 'test_query_in_stock_filter', function () {
	$f   = fbm_fixtures_up();
	$ids = Query::ids( array( 'source' => 'recent', 'products' => 48, 'in_stock' => 'true' ) );
	fbm_fixtures_down();
	fbm_assert_true( ! in_array( $f['outofstock'], $ids, true ) );
} );

fbm_test( 'test_query_sale_source_empty_returns_empty_array', function () {
	$f   = fbm_fixtures_up();
	$cat = term_exists( 'FBM QA no sale', 'product_cat' );
	$cat = $cat ? $cat : wp_insert_term( 'FBM QA no sale', 'product_cat' );
	$cat = array( 'term_id' => (int) $cat['term_id'] );
	wp_set_object_terms( $f['simple'], array( $cat['term_id'] ), 'product_cat' );
	$ids = Query::ids( array( 'source' => 'sale', 'cat' => (string) $cat['term_id'] ) );
	wp_delete_term( $cat['term_id'], 'product_cat' );
	fbm_fixtures_down();
	fbm_assert_same( array(), $ids );
} );

fbm_test( 'test_query_sale_source_finds_sale', function () {
	$f   = fbm_fixtures_up();
	$ids = Query::ids( array( 'source' => 'sale', 'products' => 48 ) );
	fbm_fixtures_down();
	fbm_assert_true( in_array( $f['sale'], $ids, true ) );
} );

fbm_test( 'test_query_related_off_product_page_empty_cart_returns_empty', function () {
	foreach ( array( 'related', 'cross_sell', 'upsell', 'auto' ) as $source ) {
		fbm_assert_same( array(), Query::ids( array( 'source' => $source ) ), $source );
	}
} );

fbm_test( 'test_query_cross_sell_of_given_product', function () {
	$f = fbm_fixtures_up();
	$p = wc_get_product( $f['simple'] );
	$p->set_cross_sell_ids( array( $f['sale'], $f['noimage'] ) );
	$p->save();
	$ids  = Query::ids( array( 'source' => 'cross_sell', 'product' => $f['simple'] ) );
	$auto = Query::ids( array( 'source' => 'auto', 'product' => $f['simple'] ) );
	fbm_fixtures_down();
	sort( $ids );
	$exp = array( $f['sale'], $f['noimage'] );
	sort( $exp );
	fbm_assert_same( $exp, $ids );
	fbm_assert_same( count( $exp ), count( $auto ), 'auto picks cross-sell first' );
} );

fbm_test( 'test_query_ids_keeps_order', function () {
	$f   = fbm_fixtures_up();
	$ids = Query::ids( array( 'source' => 'ids', 'ids' => $f['noimage'] . ',' . $f['simple'] ) );
	fbm_fixtures_down();
	fbm_assert_same( array( $f['noimage'], $f['simple'] ), $ids );
} );

fbm_test( 'test_query_unknown_category_returns_empty', function () {
	fbm_assert_same( array(), Query::ids( array( 'source' => 'recent', 'cat' => 'no-such-cat-xyz' ) ) );
} );

fbm_test( 'test_query_products_capped_48', function () {
	fbm_assert_true( count( Query::ids( array( 'source' => 'recent', 'products' => 500 ) ) ) <= 48 );
} );

fbm_test( 'test_loop_uses_content_product', function () {
	$f    = fbm_fixtures_up();
	$html = Loop::render( array( $f['simple'], $f['sale'] ), array( 'type' => 'row', 'columns' => '4', 'columns__md' => '3', 'columns__sm' => '2' ) );
	$link = get_permalink( $f['sale'] );
	fbm_fixtures_down();
	fbm_assert_same( 2, substr_count( $html, 'product-small box' ) );
	fbm_assert_contains( $link, $html );
	fbm_assert_contains( 'large-columns-4', $html );
} );

fbm_test( 'test_loop_restores_global_post', function () {
	global $post;
	$f      = fbm_fixtures_up();
	$before = $post;
	Loop::render( array( $f['simple'] ), array( 'type' => 'row', 'columns' => '4' ) );
	$after = $post;
	fbm_fixtures_down();
	fbm_assert_same( $before, $after );
} );
