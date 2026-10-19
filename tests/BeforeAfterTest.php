<?php
/** Ảnh test tối thiểu (1x1 PNG) trong Media Library, có marker để dọn. */
function fbm_qa_image( $name ) {
	$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
	$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
	update_post_meta( $id, '_fbm_qa', 1 );
	return $id;
}

function fbm_ba( $atts ) {
	return do_shortcode( '[fbm_before_after ' . $atts . ']' );
}

fbm_test( 'test_ba_renders_two_images_and_handle', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '" label_before="Da mới" label_after="Sau 6 tháng"' );
	fbm_fixtures_down();
	fbm_assert_same( 2, substr_count( $html, '<img' ) );
	fbm_assert_contains( 'class="fbm-ba fbm-ba--h', $html );
	fbm_assert_contains( '--fbm-pos:50%', $html );
	fbm_assert_contains( 'Da mới', $html );
} );

fbm_test( 'test_ba_handle_is_button_with_label', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '"' );
	fbm_fixtures_down();
	fbm_assert_true( 1 === preg_match( '/<button type="button" class="fbm-ba__handle[^"]*"[^>]*role="slider"[^>]*aria-label="[^"]+"/', $html ), 'button role=slider with label' );
	fbm_assert_contains( 'aria-valuenow="50"', $html );
	fbm_assert_contains( 'flickity-button-icon', $html );
} );

fbm_test( 'test_ba_labels_hidden_by_default', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'fbm-ba__label', $html );
} );

fbm_test( 'test_ba_labels_shown_when_enabled', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '" show_labels="true" label_before="Da mới"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-ba__label--before badge-inner', $html, 'labels reuse theme badge' );
	fbm_assert_contains( '>Da mới</span>', $html );
} );

fbm_test( 'test_ba_no_inline_color', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '" line_color="#ff0000" label_bg="rgba(0,0,0,.5)" label_color="#fff" line_width="5" overlay="true"' );
	fbm_fixtures_down();
	foreach ( array( '--fbm-line', '--fbm-label', '#ff0000', 'rgba(', 'fbm-ba--overlay' ) as $bad ) {
		fbm_assert_not_contains( $bad, $html, $bad );
	}
} );

fbm_test( 'test_ba_offset_clamped', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$hi   = fbm_ba( 'before="' . $b . '" after="' . $a . '" offset="150"' );
	$lo   = fbm_ba( 'before="' . $b . '" after="' . $a . '" offset="-20"' );
	fbm_fixtures_down();
	fbm_assert_contains( '--fbm-pos:100%', $hi );
	fbm_assert_contains( '--fbm-pos:0%', $lo );
} );

fbm_test( 'test_ba_vertical_class', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$html = fbm_ba( 'before="' . $b . '" after="' . $a . '" orientation="vertical"' );
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-ba--v', $html );
	fbm_assert_contains( 'aria-orientation="vertical"', $html );
} );

fbm_test( 'test_ba_empty_images_guest_blank_editor_note', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', fbm_ba( '' ) );
	fbm_assert_contains( 'fbm-empty', fbm_as_user( 'editor', function () {
		return fbm_ba( 'before="999999"' );
	} ) );
} );

fbm_test( 'test_ba_assets_enqueued', function () {
	$b = fbm_qa_image( 'before' );
	$a = fbm_qa_image( 'after' );
	fbm_ba( 'before="' . $b . '" after="' . $a . '"' );
	fbm_fixtures_down();
	fbm_assert_true( wp_style_is( 'fbm-before-after', 'enqueued' ) && wp_script_is( 'fbm-before-after', 'enqueued' ) );
} );

fbm_test( 'test_ba_css_rules', function () {
	$css = file_get_contents( FBM_DIR . 'elements/before-after/before-after.css' );
	$css = preg_replace( '/var\([^)]*\)/', 'var()', $css );
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|!important|font-family/i', $css ), 'no raw colors/important/font' );
	foreach ( preg_split( '/\}/', $css ) as $block ) {
		$sel = trim( preg_replace( '#/\*.*?\*/#s', '', strstr( $block, '{', true ) ?: '' ) );
		if ( '' !== $sel && 0 !== strpos( $sel, '@media' ) ) {
			fbm_assert_contains( '.fbm-ba', $sel, 'namespaced: ' . $sel );
		}
	}
} );

fbm_test( 'test_carousel_wraps_children_in_flatsome_slider', function () {
	$b    = fbm_qa_image( 'before' );
	$a    = fbm_qa_image( 'after' );
	$one  = '[fbm_before_after before="' . $b . '" after="' . $a . '"]';
	$html = do_shortcode( '[fbm_ba_carousel columns="2"]' . $one . $one . '[fbm_before_after][/fbm_ba_carousel]' );
	fbm_fixtures_down();
	fbm_assert_contains( 'slider', $html );
	fbm_assert_contains( 'large-columns-2', $html );
	fbm_assert_same( 2, substr_count( $html, 'class="col fbm-bac__item' ), 'empty child dropped' );
} );

fbm_test( 'test_carousel_empty_guest_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', do_shortcode( '[fbm_ba_carousel][/fbm_ba_carousel]' ) );
} );

fbm_test( 'test_builder_canvas_enqueues_element_assets', function () {
	// Canvas UX Builder render shortcode qua AJAX: CSS/JS phải có sẵn trong iframe từ đầu.
	foreach ( array( 'fbm-before-after', 'fbm-product-compare' ) as $h ) {
		wp_dequeue_style( $h );
		wp_dequeue_script( $h );
	}
	do_action( 'ux_builder_enqueue_scripts', 'content' );
	fbm_assert_true( wp_style_is( 'fbm-before-after', 'enqueued' ), 'before-after.css' );
	fbm_assert_true( wp_script_is( 'fbm-before-after', 'enqueued' ), 'view.js' );
	fbm_assert_true( wp_style_is( 'fbm-product-compare', 'enqueued' ), 'compare.css' );
} );

fbm_test( 'test_builder_editor_context_does_not_enqueue', function () {
	wp_dequeue_style( 'fbm-before-after' );
	do_action( 'ux_builder_enqueue_scripts', 'editor' );
	fbm_assert_true( ! wp_style_is( 'fbm-before-after', 'enqueued' ) );
} );
