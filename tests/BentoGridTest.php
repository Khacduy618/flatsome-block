<?php
/**
 * Pill Badge, Bento Card, Avatar Stack và mẫu Frames Bento Grid Pro.
 */

if ( ! function_exists( 'fbm_qa_image' ) ) {
	/** Như BeforeAfterTest (khi chạy riêng file này). */
	function fbm_qa_image( $name ) {
		$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
		update_post_meta( $id, '_fbm_qa', 1 );
		return $id;
	}
}

/** Selector của file CSS phải nằm trong namespace; bỏ qua @keyframes và @media. */
function fbm_bento_css_namespaced( $file, $ns ) {
	$css = file_get_contents( FBM_DIR . $file );
	$css = preg_replace( '#/\*.*?\*/#s', '', $css );
	$css = preg_replace( '/@keyframes[^{]*\{(?:[^{}]*\{[^}]*\})*\s*\}/', '', $css );
	$css = preg_replace( '/@media[^{]*\{/', '', $css );
	$raw = preg_replace( '/var\([^)]*\)/', 'var()', $css );
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|!important|font-family/i', $raw ), $file . ': no raw colors/important/font' );
	foreach ( explode( '}', $css ) as $block ) {
		$sel = trim( (string) strstr( $block, '{', true ) );
		if ( '' !== $sel ) {
			foreach ( explode( ',', $sel ) as $one ) {
				fbm_assert_contains( $ns, $one, $file . ': ' . $one );
			}
		}
	}
	fbm_assert_contains( 'prefers-reduced-motion', file_get_contents( FBM_DIR . $file ), $file );
}

function fbm_bento_builder_args( $slug ) {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	return ( function () use ( $commons, $slug ) {
		return include FBM_DIR . 'elements/' . $slug . '/builder.php';
	} )();
}

/* ---------- Pill Badge ---------- */

fbm_test( 'test_pill_renders_text_dot_and_tone', function () {
	$html = do_shortcode( '[fbm_pill_badge text="Mới ra mắt" tone="success" align="center"]' );
	fbm_assert_contains( 'class="fbm-pill-wrap text-center', $html );
	fbm_assert_contains( 'fbm-pill fbm-pill--success fbm-pill--pulse', $html );
	fbm_assert_contains( 'class="fbm-pill__dot"', $html );
	fbm_assert_contains( '>Mới ra mắt</span>', $html );
} );

fbm_test( 'test_pill_no_dot_no_pulse_and_link', function () {
	$html = do_shortcode( '[fbm_pill_badge text="Xem" dot="false" link="https://example.com/a" target="_blank"]' );
	fbm_assert_not_contains( 'fbm-pill__dot', $html );
	fbm_assert_not_contains( 'fbm-pill--pulse', $html );
	fbm_assert_contains( '<a class="fbm-pill', $html );
	fbm_assert_contains( 'href="https://example.com/a"', $html );
	fbm_assert_contains( 'rel="noopener"', $html );
} );

fbm_test( 'test_pill_unknown_values_fall_back', function () {
	$html = do_shortcode( '[fbm_pill_badge text="x" tone="#ff0000" align="evil" size="huge"]' );
	fbm_assert_contains( 'fbm-pill--primary', $html );
	fbm_assert_contains( 'text-left', $html );
	fbm_assert_not_contains( 'ff0000', $html );
	fbm_assert_not_contains( 'evil', $html );
	fbm_assert_not_contains( 'huge', $html );
} );

fbm_test( 'test_pill_escapes_hostile_values', function () {
	$html = do_shortcode( '[fbm_pill_badge text="<script>alert(1)</script>" class="a&quot;onmouseover=x" link="javascript:alert(1)"]' );
	fbm_assert_not_contains( '<script', $html );
	fbm_assert_not_contains( 'onmouseover=x"', $html );
	fbm_assert_not_contains( 'javascript:', $html );
} );

fbm_test( 'test_pill_empty_guest_blank_editor_note', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', do_shortcode( '[fbm_pill_badge text=""]' ) );
	fbm_assert_contains( 'fbm-empty', fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_pill_badge text=" "]' );
	} ) );
} );

fbm_test( 'test_pill_css_rules', function () {
	fbm_bento_css_namespaced( 'elements/pill-badge/pill-badge.css', '.fbm-pill' );
} );

/* ---------- Bento Card ---------- */

fbm_test( 'test_bento_renders_parts_vertical_by_default', function () {
	$img  = fbm_qa_image( 'bento' );
	$html = do_shortcode( '[fbm_bento_card tag="01" title="Khung gỗ" text="Gỗ sồi tự nhiên." link_text="Xem thêm" link="/shop/" image="' . $img . '"]' );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="fbm-bento fbm-bento--vertical fbm-bento--light', $html );
	fbm_assert_contains( '<span class="fbm-bento__tag">01</span>', $html );
	fbm_assert_contains( '<h3 class="fbm-bento__title">Khung gỗ</h3>', $html );
	fbm_assert_contains( 'class="fbm-bento__text"', $html );
	fbm_assert_contains( 'class="button is-link is-small fbm-bento__link"', $html );
	fbm_assert_contains( 'href="/shop/"', $html );
	fbm_assert_contains( 'class="fbm-bento__media"', $html );
	fbm_assert_same( 1, substr_count( $html, '<img' ) );
} );

fbm_test( 'test_bento_horizontal_dark_and_title_tag', function () {
	$html = do_shortcode( '[fbm_bento_card title="A" layout="horizontal" style="dark" media_side="left" title_tag="h2"]' );
	fbm_assert_contains( 'fbm-bento--horizontal', $html );
	fbm_assert_contains( 'fbm-bento--media-left', $html );
	fbm_assert_contains( 'fbm-bento--dark dark', $html, 'reuses Flatsome .dark text colors' );
	fbm_assert_contains( '<h2 class="fbm-bento__title">', $html );
} );

fbm_test( 'test_bento_unknown_values_fall_back', function () {
	$html = do_shortcode( '[fbm_bento_card title="A" layout="diag" style="#000" title_tag="script" image_size="huge"]' );
	fbm_assert_contains( 'fbm-bento--vertical fbm-bento--light', $html );
	fbm_assert_contains( '<h3 class="fbm-bento__title">', $html );
	fbm_assert_not_contains( '#000', $html );
	fbm_assert_not_contains( '<script', $html );
} );

fbm_test( 'test_bento_radius_and_spacing_from_options', function () {
	$html = do_shortcode( '[fbm_bento_card _id="fbm-qa-bento" title="A" radius="24" padding="40" padding__sm="20" min_height="420"]' );
	fbm_assert_contains( '--fbm-bento-radius:24px', $html );
	fbm_assert_contains( '<style', $html );
	fbm_assert_contains( 'padding: 40px;', $html );
	fbm_assert_contains( 'padding: 20px;', $html );
	fbm_assert_contains( 'min-height: 420px;', $html );
} );

fbm_test( 'test_bento_defaults_no_inline_style', function () {
	$html = do_shortcode( '[fbm_bento_card title="A"]' );
	fbm_assert_not_contains( 'style=', $html );
	fbm_assert_not_contains( '<style', $html );
} );

fbm_test( 'test_bento_hostile_values_escaped', function () {
	$html = do_shortcode( '[fbm_bento_card title="<img src=x onerror=alert(1)>" text="<script>x</script><strong>ok</strong>" link="javascript:alert(1)" link_text="Go" class="a&quot;onmouseover=x" radius="5;color:red"]' );
	fbm_assert_not_contains( '<img src=x', $html );
	fbm_assert_not_contains( '<script', $html );
	fbm_assert_contains( '<strong>ok</strong>', $html, 'text allows basic rich text' );
	fbm_assert_not_contains( 'javascript:', $html );
	fbm_assert_not_contains( 'onmouseover=x"', $html );
	fbm_assert_not_contains( 'color:red', $html );
} );

fbm_test( 'test_bento_missing_image_note_only_for_editor', function () {
	wp_set_current_user( 0 );
	$guest = do_shortcode( '[fbm_bento_card title="A" image="999999"]' );
	fbm_assert_not_contains( 'fbm-bento__media', $guest );
	$editor = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_bento_card title="A"]' );
	} );
	fbm_assert_contains( 'fbm-bento__media fbm-bento__media--empty', $editor );
} );

fbm_test( 'test_bento_empty_guest_blank_editor_note', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', do_shortcode( '[fbm_bento_card title="" text="" tag="01"]' ) );
	fbm_assert_contains( 'fbm-empty', fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_bento_card title="" text=""]' );
	} ) );
} );

fbm_test( 'test_bento_css_rules', function () {
	fbm_bento_css_namespaced( 'elements/bento-card/bento-card.css', '.fbm-bento' );
	$css = file_get_contents( FBM_DIR . 'elements/bento-card/bento-card.css' );
	fbm_assert_contains( ':focus-visible', $css );
	fbm_assert_contains( 'max-width: 549px', $css );
} );

fbm_test( 'test_bento_builder_options', function () {
	$args = fbm_bento_builder_args( 'bento-card' );
	fbm_assert_true( ! isset( $args['type'] ), 'not a container' );
	$o = $args['options'];
	foreach ( array( 'content', 'media', 'style', 'advanced_options' ) as $g ) {
		fbm_assert_true( isset( $o[ $g ] ), $g );
	}
	fbm_assert_same( true, $o['style']['options']['padding']['responsive'] );
	fbm_assert_same( true, $o['style']['options']['min_height']['responsive'] );
	fbm_assert_same( 'image', $o['media']['options']['image']['type'] );
} );

/* ---------- Avatar Stack ---------- */

fbm_test( 'test_avatars_render_images_stars_and_text', function () {
	$ids  = array( fbm_qa_image( 'av1' ), fbm_qa_image( 'av2' ), fbm_qa_image( 'av3' ) );
	$html = do_shortcode( '[fbm_avatar_stack ids="' . implode( ',', $ids ) . ',999999" rating="4.5" text="2.000+ khách hàng" align="center"]' );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="fbm-avatars text-center', $html );
	fbm_assert_same( 3, substr_count( $html, 'class="fbm-avatars__img' ), 'missing attachment skipped' );
	fbm_assert_contains( 'class="star-rating"', $html, 'reuses Flatsome stars' );
	fbm_assert_contains( 'width:90%', $html );
	fbm_assert_contains( '4.5', $html );
	fbm_assert_contains( '2.000+ khách hàng', $html );
} );

fbm_test( 'test_avatars_rating_clamped_and_hidden', function () {
	fbm_assert_contains( 'width:100%', do_shortcode( '[fbm_avatar_stack rating="9" text="x"]' ) );
	fbm_assert_contains( 'width:0%', do_shortcode( '[fbm_avatar_stack rating="-3" text="x"]' ) );
	fbm_assert_not_contains( 'star-rating', do_shortcode( '[fbm_avatar_stack show_rating="false" text="x"]' ) );
} );

fbm_test( 'test_avatars_size_overlap_vars', function () {
	$html = do_shortcode( '[fbm_avatar_stack size="56" overlap="16" text="x"]' );
	fbm_assert_contains( '--fbm-av-size:56px', $html );
	fbm_assert_contains( '--fbm-av-overlap:16px', $html );
	$bad = do_shortcode( '[fbm_avatar_stack size="56;color:red" overlap="999" text="x"]' );
	fbm_assert_not_contains( 'color:red', $bad );
} );

fbm_test( 'test_avatars_placeholders_only_for_editor', function () {
	wp_set_current_user( 0 );
	fbm_assert_not_contains( 'fbm-avatars__img--empty', do_shortcode( '[fbm_avatar_stack text="x"]' ) );
	$editor = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_avatar_stack text="x"]' );
	} );
	fbm_assert_same( 4, substr_count( $editor, 'fbm-avatars__img--empty' ) );
} );

fbm_test( 'test_avatars_hostile_values_escaped', function () {
	$html = do_shortcode( '[fbm_avatar_stack text="<script>x</script>" class="a&quot;onmouseover=x" ids="1 or 1=1"]' );
	fbm_assert_not_contains( '<script', $html );
	fbm_assert_not_contains( 'onmouseover=x"', $html );
} );

fbm_test( 'test_avatars_empty_guest_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', do_shortcode( '[fbm_avatar_stack text="" show_rating="false"]' ) );
} );

fbm_test( 'test_avatars_css_rules', function () {
	fbm_bento_css_namespaced( 'elements/avatar-stack/avatar-stack.css', '.fbm-avatars' );
} );

fbm_test( 'test_avatars_builder_gallery_option', function () {
	$args = fbm_bento_builder_args( 'avatar-stack' );
	fbm_assert_same( 'gallery', $args['options']['content']['options']['ids']['type'] );
	fbm_assert_true( isset( $args['options']['advanced_options'] ) );
} );

/* ---------- Đăng ký & mẫu ---------- */

fbm_test( 'test_bento_elements_registered_in_content_group', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	foreach ( array( 'fbm_pill_badge', 'fbm_bento_card', 'fbm_avatar_stack' ) as $tag ) {
		fbm_assert_true( isset( $all[ $tag ] ), $tag );
		fbm_assert_same( 'Content', $all[ $tag ]['category'], $tag );
	}
} );

fbm_test( 'test_bento_assets_enqueued_on_render', function () {
	foreach ( array( 'fbm-pill-badge', 'fbm-bento-card', 'fbm-avatar-stack' ) as $h ) {
		wp_dequeue_style( $h );
	}
	do_shortcode( '[fbm_pill_badge text="x"][fbm_bento_card title="x"][fbm_avatar_stack text="x"]' );
	foreach ( array( 'fbm-pill-badge', 'fbm-bento-card', 'fbm-avatar-stack' ) as $h ) {
		fbm_assert_true( wp_style_is( $h, 'enqueued' ), $h );
	}
} );

fbm_test( 'test_bento_grid_template_uses_new_elements', function () {
	$all = FBM\Plugin::get( 'templates' )->all();
	fbm_assert_true( isset( $all['bento-grid'] ), 'template exists' );
	$t = $all['bento-grid'];
	fbm_assert_same( 'Frames Bento Grid Pro', $t['name'] );
	fbm_assert_same( 1, substr_count( $t['content'], '[fbm_pill_badge' ) );
	fbm_assert_same( 4, substr_count( $t['content'], '[fbm_bento_card' ) );
	fbm_assert_same( 1, substr_count( $t['content'], '[fbm_avatar_stack' ) );
	fbm_assert_contains( 'v_align="equal"', $t['content'] );
	fbm_assert_not_contains( '[ux_text', $t['content'] );
	$html = do_shortcode( $t['content'] );
	fbm_assert_same( 4, substr_count( $html, 'class="fbm-bento ' ) );
} );
