<?php
if ( ! function_exists( 'fbm_qa_image' ) ) {
	/** Ảnh test tối thiểu (1x1 PNG), có marker để dọn. Trùng định nghĩa trong BeforeAfterTest khi chạy riêng file. */
	function fbm_qa_image( $name ) {
		$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
		update_post_meta( $id, '_fbm_qa', 1 );
		return $id;
	}
}

function fbm_stl( $atts ) {
	return do_shortcode( '[fbm_look ' . $atts . ']' );
}

fbm_test( 'test_stl_registered', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	fbm_assert_true( isset( $all['fbm_look'], $all['fbm_shop_look'] ) );
} );

/** [fbm_shop_look] chứa các Look; mỗi phần tử $looks là chuỗi thuộc tính của một [fbm_look]. */
function fbm_shop( array $looks, $atts = '' ) {
	$inner = '';
	foreach ( $looks as $look ) {
		$inner .= '[fbm_look ' . $look . ']';
	}
	return do_shortcode( '[fbm_shop_look ' . $atts . ']' . $inner . '[/fbm_shop_look]' );
}

fbm_test( 'test_stl_renders_pins_and_cards', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_stl( '_id="stl1" image="' . $img . '" pin1_product="' . $f['simple'] . '" pin1_x="30" pin1_y="40" pin2_product="' . $f['sale'] . '" pin3_product="' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_same( 3, substr_count( $html, '<button type="button" class="fbm-stl__pin' ) );
	fbm_assert_same( 3, substr_count( $html, 'fbm-stl__card"' ) );
	fbm_assert_contains( '--fbm-x:30%;--fbm-y:40%', $html );
	fbm_assert_contains( 'aria-controls="stl1-card-0"', $html );
	fbm_assert_contains( 'data-slot="1"', $html );
} );

fbm_test( 'test_stl_first_card_visible_others_hidden', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_stl( '_id="stl1" image="' . $img . '" pin1_product="' . $f['simple'] . '" pin2_product="' . $f['sale'] . '" pin3_product="' . $f['attrs'] . '"' );
	fbm_fixtures_down();
	fbm_assert_true( 1 === preg_match( '/<article[^>]*id="stl1-card-0"[^>]*>/', $html, $m0 ) );
	fbm_assert_not_contains( 'hidden', $m0[0], 'card 0 visible' );
	foreach ( array( 1, 2 ) as $i ) {
		fbm_assert_true( 1 === preg_match( '/<article[^>]*id="stl1-card-' . $i . '"[^>]*>/', $html, $m ) );
		fbm_assert_contains( 'hidden', $m[0], "card {$i} hidden" );
	}
} );

fbm_test( 'test_stl_skips_invalid_and_renumbers', function () {
	$f     = fbm_fixtures_up();
	$img   = fbm_qa_image( 'look' );
	$draft = fbm_qa_simple( 'draft', array( 'status' => 'draft' ) );
	$page  = fbm_qa_post( 'page', 'not product', '' );
	$html  = fbm_stl( '_id="stl1" image="' . $img . '" pin1_product="' . $f['hidden'] . '" pin2_product="' . $draft . '" pin3_product="' . $page . '" pin4_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_same( 1, substr_count( $html, '<button type="button" class="fbm-stl__pin' ) );
	fbm_assert_contains( 'data-slot="4"', $html );
	fbm_assert_contains( 'data-index="0"', $html );
	fbm_assert_contains( 'fbm-stl__pin-num">1<', $html );
} );

fbm_test( 'test_stl_empty_guest_blank', function () {
	$f = fbm_fixtures_up();
	wp_set_current_user( 0 );
	$no_image = fbm_stl( 'pin1_product="' . $f['simple'] . '"' );
	$no_pins  = fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '"' );
	fbm_fixtures_down();
	fbm_assert_same( '', trim( $no_image ) );
	fbm_assert_same( '', trim( $no_pins ) );
} );

fbm_test( 'test_stl_empty_editor_note', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_as_user( 'editor', function () use ( $f, $img ) {
		return array( fbm_stl( 'pin1_product="' . $f['simple'] . '"' ), fbm_stl( 'image="' . $img . '"' ) );
	} );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-empty', $html[0] );
	fbm_assert_contains( 'fbm-empty', $html[1] );
} );

fbm_test( 'test_stl_coordinates_clamped', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '" pin1_x="150" pin1_y="-5"' );
	fbm_fixtures_down();
	fbm_assert_contains( '--fbm-x:100%;--fbm-y:0%', $html );
} );

fbm_test( 'test_stl_single_root', function () {
	$f    = fbm_fixtures_up();
	$html = trim( fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" title="Phối đồ" description="Mô tả" pin1_product="' . $f['simple'] . '"' ) );
	fbm_fixtures_down();
	fbm_assert_same( 0, strpos( $html, '<section' ), 'starts with section' );
	fbm_assert_same( strlen( $html ) - strlen( '</section>' ), strrpos( $html, '</section>' ), 'ends with section' );
	fbm_assert_same( 1, substr_count( $html, '<section' ) );
} );

fbm_test( 'test_stl_escapes_product_name', function () {
	$f = fbm_fixtures_up();
	$p = wc_get_product( $f['simple'] );
	$p->set_name( 'A <script>x</script>' );
	$p->save();
	$html = fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( '<script>x', $html );
} );

fbm_test( 'test_stl_same_product_twice_distinct_cards', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_stl( '_id="stl1" image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '" pin2_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'id="stl1-card-0"', $html );
	fbm_assert_contains( 'id="stl1-card-1"', $html );
	fbm_assert_same( 2, substr_count( $html, '<button type="button" class="fbm-stl__pin' ) );
} );

fbm_test( 'test_stl_two_instances_unique_ids', function () {
	$f   = fbm_fixtures_up();
	$img = fbm_qa_image( 'look' );
	$a   = fbm_stl( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	$b   = fbm_stl( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	preg_match( '/<section id="([^"]+)"/', $a, $ma );
	preg_match( '/<section id="([^"]+)"/', $b, $mb );
	fbm_assert_true( ! empty( $ma[1] ) && ! empty( $mb[1] ) && $ma[1] !== $mb[1] );
} );

fbm_test( 'test_stl_no_price_no_empty_price_block', function () {
	$f       = fbm_fixtures_up();
	$noprice = fbm_qa_simple( 'noprice', array( 'price' => '' ) );
	$html    = fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $noprice . '"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'fbm-stl__card-price', $html );
	fbm_assert_contains( 'fbm-stl__card-btn', $html );
} );

fbm_test( 'test_stl_classes_from_options', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_stl( 'image="' . $img . '" image_side="right" split="60" pin_style="numbers" pin_tone="dark" ratio="4-5" pin1_product="' . $f['simple'] . '"' );
	$evil = fbm_stl( 'image="' . $img . '" ratio="evil" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	foreach ( array( 'fbm-stl--right', 'fbm-stl--split-60', 'fbm-stl--numbers', 'fbm-stl--tone-dark', 'fbm-stl--ratio fbm-stl--r-4-5' ) as $c ) {
		fbm_assert_contains( $c, $html, $c );
	}
	fbm_assert_not_contains( 'fbm-stl--r-', $evil );
	fbm_assert_not_contains( 'evil', $evil );
} );

fbm_test( 'test_stl_card_image_toggle', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$on   = fbm_stl( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	$off  = fbm_stl( 'image="' . $img . '" card_image="false" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-stl__card-img', $on );
	fbm_assert_not_contains( 'fbm-stl__card-img', $off );
} );

fbm_test( 'test_stl_button_text', function () {
	$f      = fbm_fixtures_up();
	$img    = fbm_qa_image( 'look' );
	$link   = get_permalink( $f['simple'] );
	$def    = fbm_stl( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	$custom = fbm_stl( 'image="' . $img . '" button_text="Mua ngay" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'Xem sản phẩm', $def );
	fbm_assert_contains( 'Mua ngay', $custom );
	fbm_assert_true( 1 === preg_match( '/<a[^>]*href="' . preg_quote( esc_url( $link ), '/' ) . '"[^>]*fbm-stl__card-btn|<a[^>]*fbm-stl__card-btn[^>]*href="' . preg_quote( esc_url( $link ), '/' ) . '"/', $def ), 'button links to product' );
} );

fbm_test( 'test_stl_builder_options', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/look/builder.php';
	} )();
	$o = $args['options'];
	fbm_assert_same( false, $args['wrap'] );
	fbm_assert_same( array( 'fbm_shop_look' ), $args['allow_in'] );
	for ( $n = 1; $n <= 8; $n++ ) {
		fbm_assert_true( isset( $o[ "pin{$n}_options" ] ), "pin{$n}_options" );
	}
	fbm_assert_true( ! isset( $o['pin1_options']['conditions'] ) );
	for ( $n = 2; $n <= 8; $n++ ) {
		fbm_assert_same( 'pin' . ( $n - 1 ) . '_product', $o[ "pin{$n}_options" ]['conditions'] );
	}
	$x = $o['pin3_options']['options']['pin3_x'];
	fbm_assert_same( array( 1, 0, 100 ), array( $x['step'], $x['min'], $x['max'] ) );
	$shared = require FBM_DIR . 'elements/look/options.php';
	$ratio  = null;
	foreach ( $o as $group ) {
		if ( isset( $group['options']['ratio'] ) ) {
			$ratio = $group['options']['ratio'];
		}
	}
	fbm_assert_same( array_merge( array( '' ), array_keys( $shared['ratios'] ) ), array_map( 'strval', array_keys( $ratio['options'] ) ) );
} );

fbm_test( 'test_stl_css_rules', function () {
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	$css = preg_replace( '/var\([^)]*\)/', 'var()', $css );
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|!important|font-family/i', $css ), 'no raw colors/important/font' );
	foreach ( preg_split( '/\}/', $css ) as $block ) {
		$sel = trim( preg_replace( '#/\*.*?\*/#s', '', strstr( $block, '{', true ) ?: '' ) );
		// Bỏ qua @media/@keyframes và bước keyframe (0%, 100%, from, to).
		if ( '' === $sel || 0 === strpos( $sel, '@' ) || preg_match( '/^(\d+%|from|to)(\s*,\s*(\d+%|from|to))*$/', $sel ) ) {
			continue;
		}
		fbm_assert_contains( '.fbm-stl', $sel, 'namespaced: ' . $sel );
	}
} );

fbm_test( 'test_stl_css_has_ratio_rules', function () {
	$shared = require FBM_DIR . 'elements/look/options.php';
	$css    = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	foreach ( array_keys( $shared['ratios'] ) as $key ) {
		list( $w, $h ) = explode( '-', $key );
		fbm_assert_contains( ".fbm-stl--r-{$key} .fbm-stl__media { aspect-ratio: {$w} / {$h}; }", $css, $key );
	}
} );

fbm_test( 'test_stl_css_reduced_motion', function () {
	fbm_assert_contains( '@media (prefers-reduced-motion: reduce)', file_get_contents( FBM_DIR . 'elements/look/shop-look.css' ) );
} );

fbm_test( 'test_stl_assets_enqueued_only_when_used', function () {
	$f = fbm_fixtures_up();
	wp_dequeue_style( 'fbm-look' );
	wp_dequeue_script( 'fbm-look' );
	do_shortcode( '[fbm_products source="recent" products="1"]' );
	$before = wp_style_is( 'fbm-look', 'enqueued' );
	fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' );
	$after = array( wp_style_is( 'fbm-look', 'enqueued' ), wp_script_is( 'fbm-look', 'enqueued' ) );
	fbm_fixtures_down();
	fbm_assert_same( false, $before );
	fbm_assert_same( array( true, true ), $after );
} );

fbm_test( 'test_shop_look_single_look_no_slider', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_shop( array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' ) );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="fbm-stl', $html );
	fbm_assert_contains( 'fbm-shop-look', $html );
	fbm_assert_not_contains( 'class="slider', $html );
} );

fbm_test( 'test_shop_look_multiple_looks_slider', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_shop( array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"', 'image="' . $img . '" pin1_product="' . $f['sale'] . '"' ) );
	fbm_fixtures_down();
	fbm_assert_contains( 'slider', $html );
	fbm_assert_same( 2, substr_count( $html, 'fbm-shop-look__slide' ) );
	fbm_assert_same( 2, substr_count( $html, '<section ' ) );
} );

fbm_test( 'test_shop_look_skips_empty_looks', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_as_user( 'editor', function () use ( $f, $img ) {
		return fbm_shop( array( 'image="' . $img . '"', 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' ) );
	} );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'fbm-shop-look__slide', $html );
	fbm_assert_same( 1, substr_count( $html, '<section ' ) );
} );

fbm_test( 'test_shop_look_empty_guest_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', trim( fbm_shop( array( 'image=""' ) ) ) );
	fbm_assert_same( '', trim( do_shortcode( '[fbm_shop_look][/fbm_shop_look]' ) ) );
} );

fbm_test( 'test_shop_look_empty_editor_note', function () {
	$html = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_shop_look][/fbm_shop_look]' );
	} );
	fbm_assert_contains( 'fbm-empty', $html );
} );

fbm_test( 'test_shop_look_builder', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	fbm_assert_same( 'container', $args['type'] );
	fbm_assert_same( array( 'fbm_look' ), $args['allow'] );
	fbm_assert_true( isset( $args['options']['slider_options'] ) );
	fbm_assert_contains( '<content></content>', $args['template'] );
} );
