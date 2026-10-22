<?php
/**
 * Product Showcase Slider: [fbm_product_showcase] (khung, dùng Slider ux_slider của Flatsome) chứa các [fbm_showcase_slide]
 * (cặp ảnh gốc → ảnh kết quả, dựng bằng row/col, image-cover, is-border của theme).
 */

if ( ! function_exists( 'fbm_qa_image' ) ) {
	function fbm_qa_image( $name ) {
		$upload = wp_upload_bits( $name . '.png', null, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==' ) );
		$id     = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'FBM QA ' . $name, 'post_status' => 'inherit' ), $upload['file'] );
		update_post_meta( $id, '_fbm_qa', 1 );
		return $id;
	}
}

function fbm_ps_slide( $left, $right, $extra = '' ) {
	return '[fbm_showcase_slide left_image="' . $left . '" right_image="' . $right . '"' . ( $extra ? ' ' . $extra : '' ) . ']';
}

function fbm_ps( $slides, $atts = '' ) {
	return do_shortcode( '[fbm_product_showcase' . ( $atts ? ' ' . $atts : '' ) . ']' . $slides . '[/fbm_product_showcase]' );
}

/** Số phần tử gốc của một đoạn HTML; chữ lạc ngoài phần tử gốc tính là lỗi. */
function fbm_ps_roots( $html ) {
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

/** Mọi option (kể cả trong group) của args builder. */
function fbm_ps_option_keys( array $options ) {
	$keys = array();
	foreach ( $options as $key => $opt ) {
		if ( isset( $opt['type'] ) && 'group' === $opt['type'] ) {
			$keys = array_merge( $keys, fbm_ps_option_keys( $opt['options'] ) );
		} else {
			$keys[ $key ] = $opt;
		}
	}
	return $keys;
}

/** Định nghĩa ux_slider giả, cùng cấu trúc với inc/builder/shortcodes/ux_slider.php của Flatsome. */
function fbm_ps_fake_slider() {
	$radio = array(
		'false' => array( 'title' => 'Off' ),
		'true'  => array( 'title' => 'On' ),
	);
	return array(
		'directives'           => array( 'ux-slider' ),
		'tools'                => 'shortcodes/ux_slider/ux-slider-tools.directive.html',
		'toolbar'              => array( 'show_children_selector' => true ),
		'children'             => array( 'inline' => true, 'addable_spots' => array( 'left', 'right' ) ),
		'template'             => '<div class="slider-wrapper relative"><div class="slider"><content></content></div></div>',
		'shortcode_controller' => 'UxSliderShortcodeController',
		'options'              => array(
			'label'            => array( 'type' => 'textfield' ),
			'type'             => array( 'type' => 'select', 'default' => 'slide', 'options' => array( 'slide' => 'Slide', 'fade' => 'Fade' ) ),
			'layout_options'   => array( 'type' => 'group', 'options' => array( 'style' => array( 'type' => 'select', 'default' => 'normal' ) ) ),
			'nav_options'      => array( 'type' => 'group', 'options' => array( 'nav_color' => array( 'type' => 'radio-buttons', 'default' => 'light' ) ) ),
			'slide_options'    => array(
				'type'    => 'group',
				'options' => array(
					'auto_slide'  => array( 'type' => 'radio-buttons', 'default' => 'true', 'options' => $radio ),
					'timer'       => array( 'type' => 'textfield', 'default' => 6000 ),
					'pause_hover' => array( 'type' => 'radio-buttons', 'default' => 'true', 'options' => $radio ),
				),
			),
			'advanced_options' => array( 'type' => 'group', 'options' => array() ),
		),
	);
}

function fbm_ps_builder( $slug, $slider = null ) {
	$commons = get_template_directory() . '/inc/builder/shortcodes/commons/';
	$cb      = function () use ( $slider ) {
		return $slider;
	};
	add_filter( 'fbm/product_showcase/ux_slider_definition', $cb );
	$args = ( function () use ( $commons, $slug ) {
		return include FBM_DIR . 'elements/' . $slug . '/builder.php';
	} )();
	remove_filter( 'fbm/product_showcase/ux_slider_definition', $cb );
	return $args;
}

fbm_test( 'test_ps_registered_in_media_group', function () {
	$all = FBM\Plugin::get( 'registry' )->all();
	fbm_assert_true( isset( $all['fbm_product_showcase'], $all['fbm_showcase_slide'] ), 'both tags registered' );
	fbm_assert_same( 'Product Showcase Slider', $all['fbm_product_showcase']['name'] );
	fbm_assert_same( 'Media', $all['fbm_product_showcase']['category'] );
	fbm_assert_true( $all['fbm_product_showcase']['container'], 'parent is container' );
	fbm_assert_same( 'editor.js', $all['fbm_product_showcase']['editor'] );
	fbm_assert_true( ! $all['fbm_showcase_slide']['container'], 'slide is not container' );
} );

fbm_test( 'test_ps_one_root_with_flatsome_slider', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$html = fbm_ps( fbm_ps_slide( $in, $out, 'left_label="Hộp quà" right_label="Ảnh phối cảnh"' ) . fbm_ps_slide( $in, $out ), 'title="Từ sản phẩm tới phối cảnh" description="Kéo ảnh vào, nhận ảnh phối cảnh."' );
	fbm_fixtures_down();
	fbm_assert_same( 1, fbm_ps_roots( $html ), 'exactly one root element' );
	fbm_assert_true( 1 === preg_match( '/^<section class="fbm-ps relative" style="--fbm-ps-speed: 600ms" aria-label="Từ sản phẩm tới phối cảnh">/', trim( $html ) ), 'section root' );
	fbm_assert_contains( 'class="slider-wrapper relative"', $html, 'Flatsome Slider markup' );
	fbm_assert_contains( 'slider-type-fade', $html, 'Fade by default' );
	fbm_assert_contains( '"autoPlay": 5000', $html, '5 s by default' );
	fbm_assert_contains( '"pauseAutoPlayOnHover" : true', $html );
	fbm_assert_contains( 'slider-nav-dark', $html, 'dark nav on light background' );
	fbm_assert_same( 2, substr_count( $html, 'class="fbm-ps__slide ' ) );
	fbm_assert_same( 4, substr_count( $html, '<img' ) );
	fbm_assert_contains( '<h2 class="section-title section-title-center"', $html );
	fbm_assert_contains( '<p>Kéo ảnh vào, nhận ảnh phối cảnh.</p>', $html );
	fbm_assert_contains( '<figcaption class="is-small op-7 text-center pt-half">Hộp quà</figcaption>', $html );
} );

fbm_test( 'test_ps_slide_uses_theme_classes', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$html = fbm_ps( fbm_ps_slide( $in, $out ) . fbm_ps_slide( $in, $out ) );
	fbm_fixtures_down();
	fbm_assert_contains( 'class="fbm-ps__slide pb" role="group" aria-roledescription="slide" aria-label="1 / 2"><div class="row row-small align-middle align-center">', $html );
	fbm_assert_contains( 'aria-label="2 / 2"', $html );
	fbm_assert_same( 4, substr_count( $html, '<figure class="col medium-5 small-12 large-5"><div class="col-inner">' ) );
	fbm_assert_contains( 'fbm-ps__frame fbm-ps__frame--input img-inner image-cover" style="padding-top:100%"', $html );
	fbm_assert_contains( '<div class="is-border is-dashed"></div>', $html, 'dashed drop zone' );
	fbm_assert_contains( '<div class="is-border"></div>', $html );
	fbm_assert_contains( '<span class="button icon circle is-outline mb-0" aria-hidden="true"><i class="icon-angle-right hide-for-small"></i><i class="icon-angle-down show-for-small"></i></span>', $html );
	fbm_assert_not_contains( 'is-selected', $html, 'Flickity sets the active slide' );
} );

fbm_test( 'test_ps_step_titles_numbered', function () {
	$in  = fbm_qa_image( 'ps-in' );
	$out = fbm_qa_image( 'ps-out' );
	$def = fbm_ps( fbm_ps_slide( $in, $out ) );
	$own = fbm_ps( fbm_ps_slide( $in, $out, 'left_title="Đặt hộp quà vào đây" right_title=""' ) );
	fbm_fixtures_down();
	fbm_assert_contains( '<p class="uppercase mb-half"><strong>1.</strong> <span>Place your product here</span></p>', $def );
	fbm_assert_contains( '<p class="uppercase mb-half"><strong>2.</strong> <span>Generated images will appear here</span></p>', $def );
	fbm_assert_contains( '<span>Đặt hộp quà vào đây</span>', $own );
	fbm_assert_contains( '<strong>2.</strong></p>', $own, 'empty title keeps the number' );
	fbm_assert_not_contains( '<figcaption', $def, 'no empty caption' );
} );

fbm_test( 'test_ps_slider_options_reach_ux_slider', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$two  = fbm_ps_slide( $in, $out ) . fbm_ps_slide( $in, $out );
	$html = fbm_ps( $two, 'type="slide" auto_slide="true" timer="3000" pause_hover="false" nav_color="light" bullets="false"' );
	$off  = fbm_ps( $two, 'auto_slide="false"' );
	fbm_fixtures_down();
	fbm_assert_not_contains( 'slider-type-fade', $html );
	fbm_assert_contains( '"autoPlay": 3000', $html );
	fbm_assert_contains( '"pauseAutoPlayOnHover" : false', $html );
	fbm_assert_contains( 'slider-nav-light', $html );
	fbm_assert_contains( '"pageDots": false', $html );
	fbm_assert_contains( '"autoPlay": false', $off );
} );

fbm_test( 'test_ps_dark_theme_and_speed', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$dark = fbm_ps( fbm_ps_slide( $in, $out ), 'theme="dark" speed="900"' );
	$bad  = fbm_ps( fbm_ps_slide( $in, $out ), 'theme="neon" speed="99999"' );
	fbm_fixtures_down();
	fbm_assert_contains( '<section class="fbm-ps relative dark bg-primary-color pt pb" style="--fbm-ps-speed: 900ms"', $dark );
	fbm_assert_contains( '<section class="fbm-ps relative" style="--fbm-ps-speed: 1500ms"', $bad );
	fbm_assert_not_contains( 'neon', $bad );
} );

fbm_test( 'test_ps_image_size_and_height', function () {
	$in    = fbm_qa_image( 'ps-in' );
	$out   = fbm_qa_image( 'ps-out' );
	$sizes = array();
	$spy   = function ( $attr, $attachment, $size ) use ( &$sizes ) {
		$sizes[] = $size;
		return $attr;
	};
	add_filter( 'wp_get_attachment_image_attributes', $spy, 10, 3 );
	$med       = fbm_ps( fbm_ps_slide( $in, $out ), 'image_size="medium" image_height="75%"' );
	$med_sizes = array_unique( $sizes );
	$sizes     = array();
	$bad       = fbm_ps( fbm_ps_slide( $in, $out ), 'image_size="huge" image_height="9999%"' );
	remove_filter( 'wp_get_attachment_image_attributes', $spy, 10 );
	fbm_fixtures_down();
	fbm_assert_same( array( 'medium' ), array_values( $med_sizes ), 'parent image size reaches both images' );
	fbm_assert_same( array( 'large' ), array_values( array_unique( $sizes ) ), 'unknown size falls back to large' );
	fbm_assert_same( 2, substr_count( $med, 'image-cover" style="padding-top:75%"' ) );
	fbm_assert_same( 2, substr_count( $bad, 'image-cover" style="padding-top:100%"' ) );
} );

fbm_test( 'test_ps_hostile_values_escaped', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$html = fbm_ps(
		fbm_ps_slide( $in, $out, 'left_label="<img src=x onerror=alert(1)>" right_title="<script>alert(2)</script>"' ),
		'class="a&quot;onmouseover=x" title="<script>alert(3)</script>" description="<b onclick=y>hi</b>" timer="1}\' onload=x"'
	);
	fbm_fixtures_down();
	fbm_assert_not_contains( '<script', $html );
	fbm_assert_not_contains( '<img src=x', $html );
	fbm_assert_not_contains( 'onmouseover=', $html );
	fbm_assert_not_contains( '<b onclick', $html );
	fbm_assert_not_contains( "' onload=x", $html );
	fbm_assert_contains( '&lt;img src=x', $html, 'label escaped, not stripped' );
} );

fbm_test( 'test_ps_class_and_visibility_on_root', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$html = fbm_ps( fbm_ps_slide( $in, $out ), 'class="mt" visibility="hide-for-small"' );
	fbm_fixtures_down();
	fbm_assert_true( 1 === preg_match( '/^<section class="fbm-ps relative mt hide-for-small"/', trim( $html ) ), 'advanced classes on root' );
	fbm_assert_not_contains( 'slider-wrapper relative mt', $html, 'not repeated on the inner slider' );
} );

fbm_test( 'test_ps_empty_guest_blank_editor_note', function () {
	wp_set_current_user( 0 );
	fbm_assert_same( '', fbm_ps( '' ) );
	$in = fbm_qa_image( 'ps-in' );
	fbm_assert_same( '', trim( fbm_ps( fbm_ps_slide( $in, '' ) ) ), 'slide missing result image is dropped' );
	$note = fbm_as_user(
		'editor',
		function () {
			return fbm_ps( '' );
		}
	);
	fbm_fixtures_down();
	fbm_assert_contains( 'fbm-empty', $note );
} );

fbm_test( 'test_ps_incomplete_slides_dropped', function () {
	$in   = fbm_qa_image( 'ps-in' );
	$out  = fbm_qa_image( 'ps-out' );
	$html = fbm_as_user(
		'editor',
		function () use ( $in, $out ) {
			return fbm_ps( fbm_ps_slide( $in, '' ) . fbm_ps_slide( $in, $out ) . fbm_ps_slide( '999999', $out ) );
		}
	);
	fbm_fixtures_down();
	fbm_assert_same( 1, substr_count( $html, 'class="fbm-ps__slide ' ) );
	fbm_assert_not_contains( 'fbm-empty', $html, 'notes of dropped slides not printed' );
	fbm_assert_contains( 'aria-label="1 / 1"', $html );
} );

fbm_test( 'test_ps_assets_enqueued', function () {
	wp_dequeue_script( 'fbm-product-showcase-editor' ); // Test canvas khác có thể đã nạp.
	$in  = fbm_qa_image( 'ps-in' );
	$out = fbm_qa_image( 'ps-out' );
	fbm_ps( fbm_ps_slide( $in, $out ) );
	fbm_fixtures_down();
	fbm_assert_true( wp_style_is( 'fbm-product-showcase', 'enqueued' ), 'css' );
	fbm_assert_true( wp_script_is( 'fbm-product-showcase', 'enqueued' ), 'view.js' );
	fbm_assert_true( ! wp_script_is( 'fbm-product-showcase-editor', 'enqueued' ), 'editor.js only in canvas' );
} );

fbm_test( 'test_ps_canvas_enqueues_editor_script', function () {
	wp_dequeue_script( 'fbm-product-showcase-editor' );
	do_action( 'ux_builder_enqueue_scripts', 'editor' );
	fbm_assert_true( ! wp_script_is( 'fbm-product-showcase-editor', 'enqueued' ), 'not in editor frame' );
	do_action( 'ux_builder_enqueue_scripts', 'content' );
	fbm_assert_true( wp_script_is( 'fbm-product-showcase-editor', 'enqueued' ), 'editor.js in canvas' );
	fbm_assert_true( wp_style_is( 'fbm-product-showcase', 'enqueued' ), 'css in canvas' );
} );

fbm_test( 'test_ps_css_only_motion_no_new_look', function () {
	$src = file_get_contents( FBM_DIR . 'elements/product-showcase/product-showcase.css' );
	$css = preg_replace( '#/\*.*?\*/#s', '', $src );
	$css = preg_replace( '/@media[^{]*\{/', '', $css );
	fbm_assert_same( 0, preg_match( '/#[0-9a-f]{3,8}\b|rgba?\(|hsla?\(|!important|font-family/i', $css ), 'no raw colors/important/font' );
	fbm_assert_same( 0, preg_match( '/[{;\s](color|background(-color)?|border(-[a-z]+)?|font-size|font-weight|padding|margin|box-shadow)\s*:/i', $css ), 'look comes from Flatsome classes' );
	foreach ( explode( '}', $css ) as $block ) {
		$sel = trim( (string) strstr( $block, '{', true ) );
		if ( '' !== $sel ) {
			foreach ( explode( ',', $sel ) as $one ) {
				fbm_assert_contains( '.fbm-ps', $one, 'namespaced: ' . trim( $one ) );
			}
		}
	}
	fbm_assert_contains( 'prefers-reduced-motion', $src );
	fbm_assert_contains( 'scale(1.02)', $src );
	fbm_assert_contains( 'all var(--fbm-ps-speed) ease-in-out', $src );
} );

fbm_test( 'test_ps_builder_borrows_ux_slider', function () {
	$args = fbm_ps_builder( 'product-showcase', fbm_ps_fake_slider() );
	$keys = fbm_ps_option_keys( $args['options'] );
	fbm_assert_same( 'container', $args['type'] );
	fbm_assert_same( array( 'fbm_showcase_slide' ), $args['allow'] );
	fbm_assert_same( false, $args['wrap'] );
	fbm_assert_same( 1, fbm_ps_roots( $args['template'] ), 'template has one root' );
	fbm_assert_contains( '<div class="slider-wrapper relative"><div class="slider"><content></content></div></div>', $args['template'] );
	fbm_assert_same( array( 'ux-slider' ), $args['directives'] );
	fbm_assert_same( 'UxSliderShortcodeController', $args['shortcode_controller'] );
	fbm_assert_true( ! empty( $args['toolbar']['show_children_selector'] ), 'slide selector in toolbar' );
	fbm_assert_true( ! isset( $args['options']['label'] ), 'admin label dropped' );
	foreach ( array( 'title', 'description', 'theme', 'speed', 'image_height', 'image_size', 'type', 'auto_slide', 'timer', 'pause_hover', 'class', 'visibility' ) as $k ) {
		fbm_assert_true( isset( $keys[ $k ] ), 'option ' . $k );
	}
	// Mặc định trong bảng = mặc định khi render (UX Builder không lưu giá trị trùng mặc định).
	$defaults = require FBM_DIR . 'elements/product-showcase/slider-defaults.php';
	fbm_assert_same( $defaults['type'], $keys['type']['default'] );
	fbm_assert_same( $defaults['timer'], $keys['timer']['default'] );
	fbm_assert_same( $defaults['nav_color'], $keys['nav_color']['default'] );
	fbm_assert_same( '600', $keys['speed']['default'] );
	fbm_assert_same( '100%', $keys['image_height']['default'] );
	foreach ( array( 'Title', 'Theme', 'Transition speed', 'Image ratio' ) as $label ) {
		fbm_assert_contains( $label, wp_json_encode( $args['options'] ), 'English label ' . $label );
	}
	$all = FBM\Plugin::get( 'registry' )->all();
	foreach ( $args['presets'] as $preset ) {
		preg_match_all( '/\[([a-z_]+)/', $preset['content'], $tags );
		foreach ( $tags[1] as $t ) {
			fbm_assert_true( isset( $all[ $t ] ), 'preset uses registered tag ' . $t );
		}
	}
} );

fbm_test( 'test_ps_builder_without_ux_slider_still_valid', function () {
	$args = fbm_ps_builder( 'product-showcase', false );
	fbm_assert_contains( '<content></content>', $args['template'] );
	fbm_assert_same( 1, fbm_ps_roots( $args['template'] ) );
	fbm_assert_true( isset( $args['options']['general_options'], $args['options']['advanced_options'] ) );
} );

fbm_test( 'test_ps_builder_slide', function () {
	$args = fbm_ps_builder( 'showcase-slide' );
	$keys = fbm_ps_option_keys( $args['options'] );
	fbm_assert_same( false, $args['wrap'] );
	fbm_assert_true( ! isset( $args['type'] ), 'not a container' );
	fbm_assert_same( array( 'fbm_product_showcase' ), $args['require'] );
	fbm_assert_same( array( 'fbm_product_showcase' ), $args['allow_in'] );
	fbm_assert_same( 1, fbm_ps_roots( $args['template'] ), 'template has one root' );
	fbm_assert_contains( 'wp-attachment="shortcode.options.leftImage"', $args['template'] );
	fbm_assert_contains( 'wp-attachment="shortcode.options.rightImage"', $args['template'] );
	fbm_assert_contains( 'class="fbm-ps__slide pb {{ shortcode.options.class }}" role="group" aria-roledescription="slide" aria-label="{{ shortcode.index + 1 }} / {{ shortcode.parent.children.length }}"><div class="row row-small align-middle align-center">', $args['template'], 'same markup as render' );
	fbm_assert_contains( 'padding-top:{{ shortcode.parent.options.imageHeight', $args['template'] );
	fbm_assert_contains( 'temp-image', $args['template'], 'Flatsome empty-image box in canvas' );
	foreach ( array( 'left_title', 'left_image', 'left_label', 'right_title', 'right_image', 'right_label', 'class' ) as $k ) {
		fbm_assert_true( isset( $keys[ $k ] ), 'option ' . $k );
	}
	fbm_assert_true( ! isset( $keys['visibility'] ), 'a hidden slide would leave a gap in the slider' );
	fbm_assert_same( 'Place your product here', $keys['left_title']['default'] );
	fbm_assert_same( 'Generated images will appear here', $keys['right_title']['default'] );
} );
