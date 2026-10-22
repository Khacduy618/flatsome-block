<?php
/**
 * Test cho Angled Showcase (cha) và Angled Card (con) cùng template.
 */

if ( ! function_exists( 'fbm_qa_image' ) ) {
	function fbm_qa_image( $name ) {
		$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
		update_post_meta( $id, '_fbm_qa', 1 );
		return $id;
	}
}

if ( ! function_exists( 'fbm_angled_css_namespaced' ) ) {
	function fbm_angled_css_namespaced( $file, $ns ) {
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
}

fbm_test( 'test_angled_showcase_parent_renders_grid_and_vars', function () {
	$html = do_shortcode( '[fbm_angled_showcase columns="3" col_gap="24" aspect_ratio="4/5" cut_x="60" cut_y="140" chamfer="20"]<div class="test-child">Child</div>[/fbm_angled_showcase]' );
	fbm_assert_contains( 'class="fbm-angled-showcase fbm-angled-showcase--zoom"', $html );
	fbm_assert_contains( 'class="fbm-angled-showcase__grid"', $html );
	fbm_assert_contains( '--fbm-showcase-cols:3', $html );
	fbm_assert_contains( '--fbm-showcase-gap:24px', $html );
	fbm_assert_contains( '--fbm-card-ratio:4/5', $html );
	fbm_assert_contains( '--fbm-card-cut-x:60%', $html );
	fbm_assert_contains( '--fbm-card-cut-y:140px', $html );
	fbm_assert_contains( '--fbm-card-chamfer:20px', $html );
	fbm_assert_contains( 'class="test-child">Child</div>', $html );
} );

fbm_test( 'test_angled_card_renders_tag_title_image_link', function () {
	$img  = fbm_qa_image( 'angled_test' );
	$code = '[fbm_angled_card tag="01" title="Speech" link="/event/" image="' . $img . '"][/fbm_angled_card]';
	$html = do_shortcode( $code );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="fbm-angled-card fbm-angled-card--zoom"', $html );
	fbm_assert_contains( '<a class="fbm-angled-card__inner" href="/event/"', $html );
	fbm_assert_contains( '<span class="fbm-angled-card__tag">01</span>', $html );
	fbm_assert_contains( 'Speech', $html );
} );

fbm_test( 'test_angled_css_rules', function () {
	fbm_angled_css_namespaced( 'elements/angled-showcase/angled-showcase.css', '.fbm-angled-showcase' );
	fbm_angled_css_namespaced( 'elements/angled-card/angled-card.css', '.fbm-angled-card' );
} );

fbm_test( 'test_angled_showcase_template_valid', function () {
	$all = FBM\Plugin::get( 'templates' )->all();
	fbm_assert_true( isset( $all['angled-showcase'] ), 'template angled-showcase exists' );
	$t = $all['angled-showcase'];
	fbm_assert_same( 'Inspiration Angled Cards Pro', $t['name'] );
	fbm_assert_same( 1, substr_count( $t['content'], '[fbm_angled_showcase' ) );
	fbm_assert_same( 3, substr_count( $t['content'], '[fbm_angled_card' ) );
	$html = do_shortcode( $t['content'] );
	fbm_assert_contains( 'It inspires you to achieve something great', $html );
	fbm_assert_same( 1, substr_count( $html, 'class="fbm-angled-showcase ' ) );
	fbm_assert_same( 3, substr_count( $html, 'class="fbm-angled-card ' ) );
} );
