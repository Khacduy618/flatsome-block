<?php
/** Shortcode giả thay Judge.me trong test; trả về thuộc tính nhận được để kiểm tra. */
function fbm_fake_judgeme( $tags ) {
	foreach ( $tags as $t ) {
		add_shortcode( $t, function ( $atts, $content, $tag ) {
			return '<div class="fake-jgm" data-tag="' . esc_attr( $tag ) . '">' . esc_html( wp_json_encode( (array) $atts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ) . '</div>';
		} );
	}
}
function fbm_unfake_judgeme( $tags ) {
	foreach ( $tags as $t ) {
		remove_shortcode( $t );
	}
}

fbm_test( 'test_reviews_unknown_widget_blank', function () {
	fbm_fake_judgeme( array( 'evil' ) );
	$html = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_reviews widget="evil"]' );
	} );
	fbm_unfake_judgeme( array( 'evil' ) );
	fbm_assert_not_contains( 'fake-jgm', $html );
} );

fbm_test( 'test_reviews_no_judgeme_guest_blank', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', do_shortcode( '[fbm_reviews widget="jgm-featured-carousel" title="Khách nói gì"]' ) );
} );

fbm_test( 'test_reviews_no_judgeme_editor_note', function () {
	$html = fbm_as_user( 'editor', function () {
		return do_shortcode( '[fbm_reviews widget="jgm-featured-carousel"]' );
	} );
	fbm_assert_contains( 'Cần plugin Judge.me', $html );
} );

fbm_test( 'test_reviews_renders_whitelisted_with_title', function () {
	fbm_fake_judgeme( array( 'jgm-featured-carousel' ) );
	$html = do_shortcode( '[fbm_reviews widget="jgm-featured-carousel" title="Khách nói gì" carousel_title="Đánh giá thật" all_reviews_url="https://example.com/reviews"]' );
	fbm_unfake_judgeme( array( 'jgm-featured-carousel' ) );
	fbm_assert_contains( 'section-title-container', $html );
	fbm_assert_contains( 'Khách nói gì', $html );
	fbm_assert_contains( 'data-tag="jgm-featured-carousel"', $html );
	fbm_assert_contains( 'Đánh giá thật', $html );
	fbm_assert_contains( 'https://example.com/reviews', $html );
} );

fbm_test( 'test_reviews_product_id_passed', function () {
	fbm_fake_judgeme( array( 'jgm-review-widget' ) );
	$html = do_shortcode( '[fbm_reviews widget="jgm-review-widget" product="123abc"]' );
	fbm_unfake_judgeme( array( 'jgm-review-widget' ) );
	fbm_assert_contains( '&quot;id&quot;:&quot;123&quot;', $html );
} );

fbm_test( 'test_reviews_carousel_title_cannot_inject_shortcode', function () {
	fbm_fake_judgeme( array( 'jgm-featured-carousel' ) );
	// Gọi render trực tiếp: giá trị độc hại đi thẳng vào element, không qua parser shortcode của WordPress.
	$html = FBM\Plugin::get( 'registry' )->render( 'fbm_reviews', array( 'widget' => 'jgm-featured-carousel', 'carousel_title' => 'x"] [evil a="', 'all_reviews_url' => 'javascript:alert(1)' ) );
	fbm_unfake_judgeme( array( 'jgm-featured-carousel' ) );
	fbm_assert_same( 1, substr_count( $html, 'fake-jgm' ) );
	fbm_assert_not_contains( '[evil', $html );
	fbm_assert_not_contains( 'javascript', $html );
} );

fbm_test( 'test_reviews_off_state_blank', function () {
	fbm_fake_judgeme( array( 'jgm-all-reviews' ) );
	$states = FBM\Plugin::get( 'states' );
	$states->set( 'fbm_reviews', 'off' );
	wp_set_current_user( 0 );
	$html = do_shortcode( '[fbm_reviews widget="jgm-all-reviews"]' );
	$states->set( 'fbm_reviews', 'on' );
	fbm_unfake_judgeme( array( 'jgm-all-reviews' ) );
	fbm_assert_same( '', $html );
} );
