<?php
function fbm_known_tags() {
	$tags = array( 'row_inner', 'col_inner', 'row_inner_1', 'col_inner_1', 'tab' );
	foreach ( glob( get_template_directory() . '/inc/builder/shortcodes/*.php' ) as $f ) {
		if ( preg_match_all( "/add_ux_builder_shortcode\(\s*'([a-z0-9_-]+)'/", file_get_contents( $f ), $m ) ) {
			$tags = array_merge( $tags, $m[1] );
		}
	}
	return array_merge( $tags, array_keys( FBM\Plugin::get( 'registry' )->all() ) );
}

fbm_test( 'test_ten_templates', function () {
	$all = FBM\Plugin::get( 'templates' )->all();
	fbm_assert_same( 10, count( $all ) );
	foreach ( $all as $slug => $t ) {
		fbm_assert_true( '' !== trim( $t['name'] ) && '' !== trim( $t['content'] ), $slug );
		fbm_assert_contains( 'templates/' . $slug . '/thumb.svg', $t['thumb'] );
	}
} );

fbm_test( 'test_templates_only_known_tags', function () {
	$known = fbm_known_tags();
	foreach ( FBM\Plugin::get( 'templates' )->all() as $slug => $t ) {
		preg_match_all( '/\[\/?([a-z0-9_-]+)/i', $t['content'], $m );
		foreach ( array_unique( $m[1] ) as $tag ) {
			fbm_assert_true( in_array( $tag, $known, true ), "$slug: [$tag]" );
		}
		fbm_assert_not_contains( 'fbm_product_attributes', $t['content'], $slug );
	}
} );

fbm_test( 'test_templates_no_inline_style_or_colors', function () {
	foreach ( FBM\Plugin::get( 'templates' )->all() as $slug => $t ) {
		fbm_assert_same( 0, preg_match( '/style\s*=\s*"[^"]*:|#[0-9a-f]{3,8}\b|rgba?\(/i', $t['content'] ), $slug );
	}
} );

fbm_test( 'test_templates_render_without_warning', function () {
	fbm_fixtures_up();
	$errors = array();
	set_error_handler( function ( $no, $str, $file, $line ) use ( &$errors ) {
		$errors[] = basename( $file ) . ":$line $str";
		return true;
	} );
	foreach ( FBM\Plugin::get( 'templates' )->all() as $slug => $t ) {
		do_shortcode( $t['content'] );
	}
	restore_error_handler();
	fbm_fixtures_down();
	fbm_assert_same( array(), $errors );
} );

fbm_test( 'test_faq_has_no_faqpage_schema', function () {
	$all = FBM\Plugin::get( 'templates' )->all();
	fbm_assert_not_contains( 'faq_schema', $all['faq']['content'] );
	fbm_assert_not_contains( 'FAQPage', do_shortcode( $all['faq']['content'] ) );
} );
