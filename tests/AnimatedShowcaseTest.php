<?php
/**
 * Animated Showcase UX Block: [fbm_animated_showcase] (khung, chạy kịch bản hoạt ảnh) chứa các [fbm_showcase_step]
 * (ảnh trái, chữ, ảnh phải, ảnh thứ ba).
 */

if ( ! function_exists( 'fbm_qa_image' ) ) {
	function fbm_qa_image( $name ) {
		$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
		update_post_meta( $id, '_fbm_qa', 1 );
		return $id;
	}
}

/** 3 ảnh test: trái, phải, thứ ba. */
function fbm_as_imgs() {
	return array( fbm_qa_image( 'as-left' ), fbm_qa_image( 'as-right' ), fbm_qa_image( 'as-third' ) );
}

function fbm_as_step( array $img, $extra = '' ) {
	return '[fbm_showcase_step left_image="' . $img[0] . '" right_image="' . $img[1] . '" third_image="' . $img[2] . '"' . ( $extra ? ' ' . $extra : '' ) . ']';
}

function fbm_as( $steps, $atts = '' ) {
	return do_shortcode( '[fbm_animated_showcase' . ( $atts ? ' ' . $atts : '' ) . ']' . $steps . '[/fbm_animated_showcase]' );
}

/** Số phần tử gốc của một đoạn HTML; chữ lạc ngoài phần tử gốc tính là lỗi. */
function fbm_as_roots( $html ) {
	$doc = new DOMDocument();
	libxml_use_internal_errors( true );
	$doc->loadHTML( '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>' );
	libxml_clear_errors();
	$n = 0;
	foreach ( $doc->getElementsByTagName( 'body' )->item( 0 )->childNodes as $node ) {
		if ( XML_ELEMENT_NODE === $node->nodeType ) {
			$n++;
		} elseif ( XML_TEXT_NODE === $node->nodeType && '' !== trim( $node->textContent ) ) {
			$n += 100;
		}
	}
	return $n;
}

function fbm_as_option_keys( array $options ) {
	$keys = array();
	foreach ( $options as $key => $opt ) {
		if ( isset( $opt['type'] ) && 'group' === $opt['type'] ) {
			$keys = array_merge( $keys, fbm_as_option_keys( $opt['options'] ) );
		} else {
			$keys[ $key ] = $opt;
		}
	}
	return $keys;
}

function fbm_as_builder( $slug ) {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	return ( function () use ( $commons, $slug ) {
		return include FBM_DIR . 'elements/' . $slug . '/builder.php';
	} )();
}

fbm_test( 'test_as_registered', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	fbm_assert_true( isset( $all['fbm_animated_showcase'], $all['fbm_showcase_step'] ), 'both tags registered' );
	fbm_assert_same( 'Animated Showcase UX Block', $all['fbm_animated_showcase']['name'] );
	fbm_assert_same( 'Media', $all['fbm_animated_showcase']['category'] );
	fbm_assert_true( $all['fbm_animated_showcase']['container'] );
	fbm_assert_same( 'view.js', $all['fbm_animated_showcase']['js'] );
	fbm_assert_same( 'editor.js', $all['fbm_animated_showcase']['editor'] );
} );

fbm_test( 'test_as_one_root_two_parts', function () {
	$img  = fbm_as_imgs();
	$html = fbm_as( fbm_as_step( $img, 'text_content="Da cá đuối"' ) . fbm_as_step( $img ), 'title="Quy trình làm dây" description="Từng bước thủ công."' );
	fbm_fixtures_down();
	fbm_assert_same( 1, fbm_as_roots( $html ), 'exactly one root element' );
	fbm_assert_true( 1 === preg_match( '/^<section class="fbm-as relative" style="--fbm-as-dur: 700ms" data-duration="700" data-delay="1200" aria-label="Quy trình làm dây">/', trim( $html ) ), 'section root' );
	fbm_assert_contains( '<h2 class="section-title section-title-center"', $html, 'Flatsome title' );
	fbm_assert_contains( '<p>Từng bước thủ công.</p>', $html );
	fbm_assert_same( 2, substr_count( $html, '<div class="fbm-as__item' ) );
	fbm_assert_same( 1, substr_count( $html, 'fbm-as__item is-current' ), 'first item shown' );
	fbm_assert_same( 2, substr_count( $html, 'class="fbm-as__part fbm-as__part--left"' ) );
	fbm_assert_same( 2, substr_count( $html, 'class="fbm-as__part fbm-as__part--right"' ) );
	fbm_assert_same( 8, substr_count( $html, '<img' ), '3 images + left image again for the second shift' );
	fbm_assert_same( 2, preg_match_all( '/<img[^>]*class="fbm-as__img fbm-as__img--again"[^>]*alt=""[^>]*aria-hidden="true"/', $html ), 'repeat copy hidden from screen readers' );
	foreach ( array( 'fbm-as__img--left', 'fbm-as__img--right', 'fbm-as__img--third', 'fbm-as__img--again' ) as $c ) {
		fbm_assert_same( 2, substr_count( $html, $c ), $c );
	}
} );

fbm_test( 'test_as_text_split_per_letter_and_readable', function () {
	$img  = fbm_as_imgs();
	$html = fbm_as( fbm_as_step( $img, 'text_content="Da ốc"' ) );
	fbm_fixtures_down();
	fbm_assert_contains( '<span class="screen-reader-text">Da ốc</span>', $html, 'whole text for screen readers / SEO' );
	fbm_assert_contains( '<span class="fbm-as__chars" aria-hidden="true">', $html );
	fbm_assert_contains( '<span class="fbm-as__char" style="--i:0">D</span>', $html );
	fbm_assert_contains( '<span class="fbm-as__char" style="--i:3">ố</span>', $html, 'multibyte letter kept whole' );
	fbm_assert_same( 5, substr_count( $html, 'class="fbm-as__char"' ) );
} );

fbm_test( 'test_as_settings_clamped', function () {
	$img  = fbm_as_imgs();
	$sizes = array();
	$spy   = function ( $attr, $attachment, $size ) use ( &$sizes ) {
		$sizes[] = $size;
		return $attr;
	};
	$own = fbm_as( fbm_as_step( $img ), 'duration="1000" step_delay="500" image_ratio="4-3" image_size="medium"' );
	add_filter( 'wp_get_attachment_image_attributes', $spy, 10, 3 );
	$bad = fbm_as( fbm_as_step( $img ), 'duration="99999" step_delay="-5" image_ratio="7-1" image_size="huge"' );
	remove_filter( 'wp_get_attachment_image_attributes', $spy, 10 );
	fbm_fixtures_down();
	fbm_assert_same( array( 'large' ), array_values( array_unique( $sizes ) ), 'unknown size falls back to large' );
	fbm_assert_contains( '--fbm-as-dur: 1000ms" data-duration="1000" data-delay="500"', $own );
	fbm_assert_contains( 'style="--fbm-as-ratio: 0.75"', $own );
	fbm_assert_contains( '--fbm-as-dur: 2000ms" data-duration="2000" data-delay="200"', $bad );
	fbm_assert_contains( 'style="--fbm-as-ratio: 1"', $bad );
} );

fbm_test( 'test_as_hostile_values_escaped', function () {
	$img  = fbm_as_imgs();
	$html = fbm_as(
		fbm_as_step( $img, 'text_content="<script>alert(1)</script>" class="b&quot;onclick=y"' ),
		'class="a&quot;onmouseover=x" title="<script>alert(2)</script>" description="<b onclick=z>hi</b>" duration="1&quot;onload=x"'
	);
	fbm_fixtures_down();
	fbm_assert_not_contains( '<script', $html );
	fbm_assert_not_contains( 'onmouseover=', $html );
	fbm_assert_not_contains( 'onclick=y', $html );
	fbm_assert_not_contains( '<b onclick', $html );
	fbm_assert_not_contains( 'onload=', $html );
	fbm_assert_contains( '&lt;script&gt;alert(1)&lt;/script&gt;', $html, 'text escaped, not stripped' );
} );

fbm_test( 'test_as_class_and_visibility_on_root', function () {
	$img  = fbm_as_imgs();
	$html = fbm_as( fbm_as_step( $img ), 'class="mt" visibility="hide-for-small"' );
	fbm_fixtures_down();
	fbm_assert_true( 1 === preg_match( '/^<section class="fbm-as relative mt hide-for-small"/', trim( $html ) ) );
} );

fbm_test( 'test_as_empty_and_incomplete', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', fbm_as( '' ) );
	$img = fbm_as_imgs();
	fbm_assert_same( '', trim( fbm_as( fbm_as_step( array( $img[0], $img[1], '' ) ) ) ), 'step without third image dropped' );
	$editor = fbm_as_user(
		'editor',
		function () use ( $img ) {
			return array( fbm_as( '' ), fbm_as( fbm_as_step( array( $img[0], '', $img[2] ) ) . fbm_as_step( $img ) ) );
		}
	);
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-empty', $editor[0] );
	fbm_assert_same( 1, substr_count( $editor[1], '<div class="fbm-as__item' ) );
	fbm_assert_not_contains( 'fbm-empty', $editor[1], 'notes of dropped steps not printed' );
} );

fbm_test( 'test_as_assets', function () {
	wp_dequeue_script( 'fbm-animated-showcase-editor' );
	$img = fbm_as_imgs();
	fbm_as( fbm_as_step( $img ) );
	fbm_fixtures_down();
	fbm_assert_true( wp_style_is( 'fbm-animated-showcase', 'enqueued' ), 'css' );
	fbm_assert_true( wp_script_is( 'fbm-animated-showcase', 'enqueued' ), 'view.js' );
	fbm_assert_true( ! wp_script_is( 'fbm-animated-showcase-editor', 'enqueued' ), 'editor.js only in canvas' );
	do_action( 'ux_builder_enqueue_scripts', 'content' );
	fbm_assert_true( wp_script_is( 'fbm-animated-showcase-editor', 'enqueued' ), 'editor.js in canvas' );
} );

fbm_test( 'test_as_css_rules', function () {
	$src = file_get_contents( FBM_DIR . 'elements/animated-showcase/animated-showcase.css' );
	$css = preg_replace( '#/\*.*?\*/#s', '', $src );
	$css = preg_replace( '/@keyframes[^{]*\{(?:[^{}]*\{[^}]*\})*\s*\}/', '', $css );
	$css = preg_replace( '/@media[^{]*\{/', '', $css );
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|!important|font-family/i', $css ), 'no raw colors/important/font' );
	preg_match_all( '/var\(--([a-z0-9-]+)/', $css, $vars );
	foreach ( array_unique( $vars[1] ) as $v ) {
		fbm_assert_true( in_array( $v, array( 'primary-color', 'secondary-color', 'text-color', 'color-grey', 'global-radius', 'fbm-as-dur', 'fbm-as-char', 'fbm-as-gap', 'fbm-as-ratio', 'i' ), true ), 'only theme tokens or own timing vars: ' . $v );
	}
	foreach ( explode( '}', $css ) as $block ) {
		$sel = trim( (string) strstr( $block, '{', true ) );
		if ( '' !== $sel ) {
			foreach ( explode( ',', $sel ) as $one ) {
				fbm_assert_contains( '.fbm-as', $one, 'namespaced: ' . trim( $one ) );
			}
		}
	}
	foreach ( array( 'prefers-reduced-motion', 'is-s1', 'is-s2', 'is-s3', 'is-s4', 'is-s5' ) as $need ) {
		fbm_assert_contains( $need, $src, $need );
	}
} );

fbm_test( 'test_as_builder_parent', function () {
	$args = fbm_as_builder( 'animated-showcase' );
	$keys = fbm_as_option_keys( $args['options'] );
	fbm_assert_same( 'container', $args['type'] );
	fbm_assert_same( array( 'fbm_showcase_step' ), $args['allow'] );
	fbm_assert_same( false, $args['wrap'] );
	fbm_assert_contains( '<content></content>', $args['template'] );
	fbm_assert_same( 1, fbm_as_roots( $args['template'] ), 'template has one root' );
	foreach ( array( 'title', 'description', 'duration', 'step_delay', 'image_ratio', 'image_size', 'class', 'visibility' ) as $k ) {
		fbm_assert_true( isset( $keys[ $k ] ), 'option ' . $k );
	}
	// Mặc định trong bảng = mặc định khi render.
	fbm_assert_same( '700', $keys['duration']['default'] );
	fbm_assert_same( '1200', $keys['step_delay']['default'] );
	fbm_assert_same( '1-1', $keys['image_ratio']['default'] );
	fbm_assert_contains( 'Animation duration', wp_json_encode( $args['options'] ), 'English labels' );
	$all = FBM\Plugin::get( 'registry' )->all();
	foreach ( $args['presets'] as $preset ) {
		preg_match_all( '/\[([a-z_]+)/', $preset['content'], $tags );
		foreach ( $tags[1] as $t ) {
			fbm_assert_true( isset( $all[ $t ] ), 'preset uses registered tag ' . $t );
		}
	}
} );

fbm_test( 'test_as_builder_step', function () {
	$args = fbm_as_builder( 'showcase-step' );
	$keys = fbm_as_option_keys( $args['options'] );
	fbm_assert_same( false, $args['wrap'] );
	fbm_assert_true( ! isset( $args['type'] ), 'not a container' );
	fbm_assert_same( array( 'fbm_animated_showcase' ), $args['require'] );
	fbm_assert_same( 1, fbm_as_roots( $args['template'] ), 'template has one root' );
	foreach ( array( 'leftImage', 'rightImage', 'thirdImage' ) as $k ) {
		fbm_assert_contains( 'wp-attachment="shortcode.options.' . $k . '"', $args['template'] );
	}
	fbm_assert_contains( 'fbm-as__img--again', $args['template'], 'second shift in canvas too' );
	fbm_assert_contains( 'ng-repeat', $args['template'], 'letters in canvas' );
	foreach ( array( 'left_image', 'text_content', 'right_image', 'third_image', 'class' ) as $k ) {
		fbm_assert_true( isset( $keys[ $k ] ), 'option ' . $k );
	}
	fbm_assert_true( ! isset( $keys['visibility'] ), 'a hidden step would leave a gap in the loop' );
} );
