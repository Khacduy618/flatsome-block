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
	$html = trim( fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' ) );
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
	$look = array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	$html = fbm_shop( $look, 'pin_style="numbers" pin_tone="dark" ratio="4-5"' );
	$def  = fbm_shop( $look );
	$evil = fbm_shop( $look, 'ratio="evil"' );
	fbm_fixtures_down();
	foreach ( array( 'fbm-shop-look--numbers', 'fbm-shop-look--tone-dark', 'fbm-shop-look--ratio fbm-shop-look--r-4-5' ) as $c ) {
		fbm_assert_contains( $c, $html, $c );
	}
	fbm_assert_contains( 'fbm-shop-look--pulse', $def, 'pulse on by default' );
	fbm_assert_not_contains( 'fbm-shop-look--numbers', $def );
	fbm_assert_not_contains( 'fbm-shop-look--r-', $evil );
	fbm_assert_not_contains( 'evil', $evil );
} );

fbm_test( 'test_card_always_shows_product_image', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_shop( array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' ), 'card_image="false"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-stl__card-img', $html, 'image always shown, no toggle' );
	fbm_assert_not_contains( 'no-card-image', $html );
} );

fbm_test( 'test_css_card_image_fits_frame', function () {
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	fbm_assert_contains( 'max-height: 260px;', $css );
	fbm_assert_contains( '.fbm-shop-look--fixed-height .fbm-stl__cards {', $css );
	fbm_assert_contains( 'min-height: 100%;', $css );
} );

fbm_test( 'test_stl_button_text', function () {
	$f      = fbm_fixtures_up();
	$look   = array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' );
	$link   = get_permalink( $f['simple'] );
	$def    = fbm_shop( $look );
	$custom = fbm_shop( $look, 'button_text="Mua ngay"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'View product', $def );
	fbm_assert_contains( 'Mua ngay', $custom );
	fbm_assert_true( 1 === preg_match( '/<a[^>]*fbm-stl__card-btn[^>]*href="' . preg_quote( esc_url( $link ), '/' ) . '"|<a[^>]*href="' . preg_quote( esc_url( $link ), '/' ) . '"[^>]*fbm-stl__card-btn/', $def ), 'button links to product' );
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
	fbm_assert_same( array( 'image' ), array_keys( $o['image_options']['options'] ), 'look image group: image only' );
	foreach ( array( 'pin_style_options', 'card_options', 'layout_options', 'general_options' ) as $g ) {
		fbm_assert_true( ! isset( $o[ $g ] ), "look has no {$g}" );
	}
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
		fbm_assert_true( false !== strpos( $sel, '.fbm-stl' ) || false !== strpos( $sel, '.fbm-shop-look' ), 'namespaced: ' . $sel );
	}
} );

fbm_test( 'test_stl_css_has_ratio_rules', function () {
	$shared = require FBM_DIR . 'elements/look/options.php';
	$css    = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	foreach ( array_keys( $shared['ratios'] ) as $key ) {
		list( $w, $h ) = explode( '-', $key );
		fbm_assert_contains( ".fbm-shop-look--r-{$key} .fbm-stl__media { aspect-ratio: {$w} / {$h}; }", $css, $key );
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
	fbm_assert_contains( 'slider-wrapper', $html, 'rendered by Flatsome [ux_slider]' );
	fbm_assert_same( 2, substr_count( $html, '<section ' ) );
} );

fbm_test( 'test_shop_look_skips_empty_looks', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_as_user( 'editor', function () use ( $f, $img ) {
		return fbm_shop( array( 'image="' . $img . '"', 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' ) );
	} );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'slider-wrapper', $html );
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
	fbm_assert_contains( '<content></content>', $args['template'] );
} );

fbm_test( 'test_shop_look_title_and_description_on_parent', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_shop( array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"', 'image="' . $img . '" pin1_product="' . $f['sale'] . '"' ), 'title="Phối cùng dây da" description="Bấm từng điểm"' );
	fbm_fixtures_down();
	fbm_assert_same( 1, substr_count( $html, 'Phối Cùng Dây Da' ) + substr_count( $html, 'Phối cùng dây da' ), 'title once' );
	fbm_assert_same( 1, substr_count( $html, 'fbm-shop-look__desc' ) );
	fbm_assert_true( strpos( $html, 'fbm-shop-look__desc' ) < strpos( $html, '<section ' ), 'header above looks' );
} );

fbm_test( 'test_look_has_no_title_options', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$look    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/look/builder.php';
	} )();
	$parent  = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	fbm_assert_true( ! isset( $look['options']['general_options'] ), 'look has no Chung group' );
	fbm_assert_true( isset( $parent['options']['general_options']['options']['title'], $parent['options']['general_options']['options']['description'] ) );
	fbm_assert_contains( 'shortcode.options.title', $parent['template'] );
} );

fbm_test( 'test_look_ignores_title_att', function () {
	$f    = fbm_fixtures_up();
	$html = fbm_stl( 'image="' . fbm_qa_image( 'look' ) . '" title="Cũ" description="Cũ mô tả" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'section-title', $html );
	fbm_assert_not_contains( 'Cũ mô tả', $html );
} );



fbm_test( 'test_layout_on_parent_applies_to_all_looks', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_shop( array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"', 'image="' . $img . '" pin1_product="' . $f['sale'] . '"' ), 'image_side="right" split="3-2"' );
	$def  = fbm_shop( array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' ) );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-shop-look fbm-shop-look--right fbm-shop-look--split-3-2', $html );
	fbm_assert_not_contains( 'fbm-shop-look--right', $def );
	fbm_assert_not_contains( 'fbm-shop-look--split', $def );
} );

fbm_test( 'test_layout_options_moved_to_parent', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$look    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/look/builder.php';
	} )();
	$parent  = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	fbm_assert_true( ! isset( $look['options']['look_layout_options'] ), 'look has no layout group' );
	fbm_assert_true( isset( $parent['options']['look_layout_options']['options']['image_side'], $parent['options']['look_layout_options']['options']['split'] ) );
	fbm_assert_contains( "'fbm-shop-look--right': shortcode.options.imageSide == 'right'", $parent['template'] );
} );

fbm_test( 'test_css_layout_keyed_on_parent', function () {
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	fbm_assert_contains( '.fbm-shop-look--split-3-2 .fbm-stl__grid', $css );
	fbm_assert_contains( '.fbm-shop-look--right .fbm-stl__media', $css );
	fbm_assert_not_contains( '.fbm-stl--', $css, 'all style modifiers live on .fbm-shop-look' );
} );

fbm_test( 'test_parent_style_options', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	$o      = $args['options'];
	$shared = require FBM_DIR . 'elements/look/options.php';
	fbm_assert_same( array( 'image_size', 'ratio' ), array_keys( $o['image_options']['options'] ) );
	fbm_assert_same( array_merge( array( '' ), array_keys( $shared['ratios'] ) ), array_map( 'strval', array_keys( $o['image_options']['options']['ratio']['options'] ) ) );
	fbm_assert_same( array( 'pin_style', 'pulse', 'pin_tone' ), array_keys( $o['pin_style_options']['options'] ) );
	fbm_assert_same( array( 'button_text' ), array_keys( $o['card_options']['options'] ) );
	fbm_assert_contains( "'fbm-shop-look--numbers': shortcode.options.pinStyle == 'numbers'", $args['template'] );
	fbm_assert_contains( 'data-button-text="{{ shortcode.options.buttonText }}"', $args['template'] );
} );

fbm_test( 'test_parent_passes_image_size_and_button_text_to_looks', function () {
	$f    = fbm_fixtures_up();
	$seen = array();
	$cb   = function ( $out ) use ( &$seen ) {
		$seen[] = array( $out['image_size'], $out['button_text'] );
		return $out;
	};
	add_filter( 'shortcode_atts_fbm_look', $cb );
	fbm_shop( array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' ), 'image_size="medium" button_text="Mua ngay"' );
	remove_filter( 'shortcode_atts_fbm_look', $cb );
	fbm_fixtures_down();
	fbm_assert_same( array( array( 'medium', 'Mua ngay' ) ), $seen );
} );

fbm_test( 'test_stl_card_dots_match_pins', function () {
	$f     = fbm_fixtures_up();
	$img   = fbm_qa_image( 'look' );
	$three = fbm_stl( '_id="stl1" image="' . $img . '" pin1_product="' . $f['simple'] . '" pin2_product="' . $f['sale'] . '" pin3_product="' . $f['attrs'] . '"' );
	$one   = fbm_stl( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	fbm_fixtures_down();
	fbm_assert_same( 3, preg_match_all( '/class="fbm-stl__dot[ "]/', $three ) );
	fbm_assert_contains( 'class="fbm-stl__dot is-active" data-index="0" aria-controls="stl1-card-0" aria-pressed="true"', $three );
	fbm_assert_not_contains( 'fbm-stl__dots', $one, 'no dots for a single product' );
} );

fbm_test( 'test_css_panel_layout', function () {
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	fbm_assert_contains( 'align-items: stretch;', $css );
	fbm_assert_contains( '.fbm-stl__cards {', $css );
	fbm_assert_contains( '.fbm-stl .fbm-stl__dot', $css );
} );

fbm_test( 'test_split_option_keys_not_numeric', function () {
	// UX Builder đánh dấu nút đang chọn bằng $ctrl.model === value; giá trị dạng số (60) không bao giờ === khoá chuỗi "60".
	$shared = require FBM_DIR . 'elements/look/options.php';
	fbm_assert_same( array( '1-1', '3-2', '2-1' ), array_keys( $shared['splits'] ) );
	foreach ( array_keys( $shared['splits'] ) as $k ) {
		fbm_assert_true( ! is_numeric( $k ), "non-numeric key {$k}" );
	}
} );

fbm_test( 'test_frame_width_height', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$look = array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"' );
	$html = fbm_shop( $look, '_id="sl1" width="80" height="500px" height__sm="300px"' );
	$def  = fbm_shop( $look, '_id="sl2"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-shop-look--fixed-height', $html );
	fbm_assert_contains( '#sl1 {', $html );
	fbm_assert_contains( 'width: 80%;', $html );
	fbm_assert_contains( '#sl1 .fbm-stl__media {', $html );
	fbm_assert_contains( 'height: 500px;', $html );
	fbm_assert_contains( 'height: 300px;', $html );
	fbm_assert_true( strrpos( $html, '</style>' ) < strrpos( $html, '</div>' ), 'style inside root' );
	fbm_assert_not_contains( 'fbm-shop-look--fixed-height', $def );
	fbm_assert_not_contains( '<style', $def );
} );

fbm_test( 'test_frame_options_in_builder', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	$l = $args['options']['look_layout_options']['options'];
	fbm_assert_same( true, $l['width']['responsive'] );
	fbm_assert_same( 'scrubfield', $l['height']['type'] );
	fbm_assert_same( true, $l['height']['responsive'] );
	fbm_assert_contains( '{{ shortcode.options.height }}', $args['template'] );
	fbm_assert_contains( "'fbm-shop-look--fixed-height': shortcode.options.height", $args['template'] );
} );

fbm_test( 'test_css_fixed_height_covers_image', function () {
	fbm_assert_contains( '.fbm-shop-look--fixed-height .fbm-stl__img', file_get_contents( FBM_DIR . 'elements/look/shop-look.css' ) );
} );

fbm_test( 'test_header_text_align', function () {
	$f      = fbm_fixtures_up();
	$look   = array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' );
	$center = fbm_shop( $look, 'title="Phối đồ" description="Mô tả" text_align="center"' );
	$right  = fbm_shop( $look, 'title="Phối đồ" description="Mô tả" text_align="right"' );
	$def    = fbm_shop( $look, 'title="Phối đồ" description="Mô tả"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-shop-look__head text-center', $center );
	fbm_assert_contains( 'section-title-center', $center );
	fbm_assert_contains( 'fbm-shop-look__head text-right', $right );
	fbm_assert_contains( 'fbm-shop-look__head text-left', $def );
	fbm_assert_contains( 'section-title-normal', $def );
} );

fbm_test( 'test_header_text_align_option', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	$o = $args['options']['general_options']['options']['text_align'];
	fbm_assert_same( 'radio-buttons', $o['type'] );
	fbm_assert_same( 'left', $o['default'] );
	fbm_assert_same( array( 'left', 'center', 'right' ), array_keys( $o['options'] ) );
	fbm_assert_contains( 'shortcode.options.textAlign', $args['template'] );
} );

fbm_test( 'test_css_title_right_align', function () {
	// .section-title của Flatsome là flex: text-align không đẩy được tiêu đề sang phải.
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	fbm_assert_contains( '.fbm-shop-look__head.text-right .section-title {', $css );
	fbm_assert_contains( 'justify-content: flex-end;', $css );
} );

fbm_test( 'test_header_gap', function () {
	$f    = fbm_fixtures_up();
	$look = array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' );
	$html = fbm_shop( $look, '_id="sl3" title="Phối đồ" description="Mô tả" gap="40" gap__sm="16"' );
	fbm_fixtures_down();
	fbm_assert_contains( '#sl3 .fbm-shop-look__head {', $html );
	fbm_assert_contains( 'gap: 40px;', $html );
	fbm_assert_contains( 'margin-bottom: 40px;', $html );
	fbm_assert_contains( 'gap: 16px;', $html );
} );

fbm_test( 'test_header_gap_option_and_css', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	$g = $args['options']['general_options']['options']['gap'];
	fbm_assert_same( array( 'slider', true, 'px', '20' ), array( $g['type'], $g['responsive'], $g['unit'], $g['default'] ) );
	fbm_assert_contains( 'gap: {{ shortcode.options.gap }}px', $args['template'] );
	$css = file_get_contents( FBM_DIR . 'elements/look/shop-look.css' );
	fbm_assert_contains( 'flex-direction: column;', $css );
	fbm_assert_contains( '.fbm-shop-look__head > *', $css );
} );

fbm_test( 'test_shop_look_reuses_ux_slider', function () {
	$fake = array(
		'ux_slider' => array(
			'options'    => array(
				'label'            => array( 'type' => 'textfield' ),
				'type'             => array( 'type' => 'select' ),
				'layout_options'   => array( 'type' => 'group' ),
				'nav_options'      => array( 'type' => 'group' ),
				'slide_options'    => array( 'type' => 'group' ),
				'advanced_options' => array( 'type' => 'group' ),
			),
			'directives' => array( 'ux-slider' ),
			'tools'      => 'shortcodes/ux_slider/ux-slider-tools.directive.html',
			'toolbar'    => array( 'x' ),
			'children'   => array( 'addable_spots' => array( 'left', 'right' ) ),
			'template'   => '<div class="slider-wrapper relative"><div class="slider"><content></content></div></div>',
			'shortcode_controller' => 'UxSliderShortcodeController',
			'tools_controller'     => 'UxSliderToolsController',
		),
	);
	$cb = function () use ( $fake ) {
		return $fake['ux_slider'];
	};
	add_filter( 'fbm/shop_look/ux_slider_definition', $cb );
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$args    = ( function () use ( $commons ) {
		return include FBM_DIR . 'elements/shop-look/builder.php';
	} )();
	remove_filter( 'fbm/shop_look/ux_slider_definition', $cb );
	foreach ( array( 'type', 'layout_options', 'nav_options', 'slide_options' ) as $k ) {
		fbm_assert_true( isset( $args['options'][ $k ] ), "ux_slider option {$k}" );
	}
	fbm_assert_true( ! isset( $args['options']['label'] ), 'admin label dropped' );
	fbm_assert_same( array( 'ux-slider' ), $args['directives'] );
	// Controller của Slider tìm .slider và bật Flickity trong canvas (UX Builder chọn controller theo tên, không theo directive).
	fbm_assert_same( 'UxSliderShortcodeController', $args['shortcode_controller'] );
	fbm_assert_same( 'UxSliderToolsController', $args['tools_controller'] );
	fbm_assert_same( 'shortcodes/ux_slider/ux-slider-tools.directive.html', $args['tools'] );
	fbm_assert_contains( '<div class="slider-wrapper relative"><div class="slider"><content></content></div></div>', $args['template'] );
	fbm_assert_true( isset( $args['options']['general_options'], $args['options']['advanced_options'] ) );
} );

fbm_test( 'test_render_passes_ux_slider_atts', function () {
	$f    = fbm_fixtures_up();
	$img  = fbm_qa_image( 'look' );
	$html = fbm_shop( array( 'image="' . $img . '" pin1_product="' . $f['simple'] . '"', 'image="' . $img . '" pin1_product="' . $f['sale'] . '"' ), 'nav_style="simple" bullets="false" auto_slide="false" class="evil\" onload=x"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'slider-nav-simple', $html );
	fbm_assert_same( 2, substr_count( $html, '<section ' ) );
	fbm_assert_not_contains( 'onload=x', $html );
} );

fbm_test( 'test_builder_labels_english', function () {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$texts   = array();
	$walk    = function ( $node ) use ( &$walk, &$texts ) {
		if ( ! is_array( $node ) ) {
			return;
		}
		foreach ( $node as $k => $v ) {
			if ( in_array( $k, array( 'heading', 'title', 'placeholder', 'name', 'description' ), true ) && is_string( $v ) ) {
				$texts[] = $v;
			} elseif ( 'options' === $k && is_array( $v ) ) {
				foreach ( $v as $ov ) {
					if ( is_string( $ov ) ) {
						$texts[] = $ov;
					}
				}
			}
			if ( is_array( $v ) ) {
				$walk( $v );
			}
		}
	};
	foreach ( array( 'look', 'shop-look' ) as $el ) {
		$args = ( function () use ( $commons, $el ) {
			return include FBM_DIR . 'elements/' . $el . '/builder.php';
		} )();
		$walk( $args );
		$texts[] = isset( $args['template'] ) ? wp_strip_all_tags( preg_replace( '/\{\{[^}]*\}\}/', '', (string) $args['template'] ) ) : '';
	}
	foreach ( array( 'look', 'shop-look' ) as $el ) {
		$meta    = include FBM_DIR . 'elements/' . $el . '/meta.php';
		$texts[] = $meta['name'];
	}
	$vi = array_filter( $texts, function ( $t ) {
		return preg_match( '/[àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ]/iu', $t );
	} );
	fbm_assert_same( array(), array_values( $vi ) );
} );

fbm_test( 'test_registry_reads_editor_meta', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	fbm_assert_same( 'canvas.js', $all['fbm_shop_look']['editor'] );
	fbm_assert_same( null, $all['fbm_products']['editor'] );
} );

fbm_test( 'test_canvas_script_only_in_canvas', function () {
	wp_dequeue_script( 'fbm-shop-look-editor' );
	$f = fbm_fixtures_up();
	fbm_shop( array( 'image="' . fbm_qa_image( 'look' ) . '" pin1_product="' . $f['simple'] . '"' ) );
	fbm_fixtures_down();
	$front = wp_script_is( 'fbm-shop-look-editor', 'enqueued' );
	do_action( 'ux_builder_enqueue_scripts', 'content' );
	fbm_assert_same( false, $front, 'not on the frontend' );
	fbm_assert_true( wp_script_is( 'fbm-shop-look-editor', 'enqueued' ), 'enqueued in canvas' );
	fbm_assert_contains( "UxBuilder.controller( 'fbm_shop_look'", file_get_contents( FBM_DIR . 'elements/shop-look/canvas.js' ) );
} );

fbm_test( 'test_css_look_full_width_slide', function () {
	// Flickity đặt slide position:absolute; không có width thì Look co về 0.
	fbm_assert_contains( '.fbm-shop-look .flickity-slider > .fbm-stl', file_get_contents( FBM_DIR . 'elements/look/shop-look.css' ) );
} );
