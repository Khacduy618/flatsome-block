<?php
/**
 * [fbm_shop_look]: khung chứa các [fbm_look]. 1 Look thì in thẳng; từ 2 Look là slider Flickity của Flatsome.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'                 => 'shop-look-' . wp_rand(),
		'title'               => '',
		'image_side'          => 'left',
		'image_size'          => 'large',
		'ratio'               => '',
		'pin_style'           => 'dot',
		'pulse'               => 'true',
		'pin_tone'            => 'light',
		'button_text'         => __( 'View product', 'flatsome-block-manager' ),
		'split'               => '1-1',
		'width'               => '100',
		'height'              => '',
		'description'         => '',
		'text_align'          => 'left',
		'class'               => '',
		'visibility'          => '',
	),
	$atts,
	$tag
);

// Cài đặt chung cần server xử lý được truyền xuống từng Look (đặt trước thuộc tính của Look).
$fbm_size = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_pass = ' image_size="' . $fbm_size . '" button_text="' . str_replace( array( '"', '[', ']' ), '', (string) $a['button_text'] ) . '"';

// Render từng Look; Look rỗng (thiếu ảnh / sản phẩm) bị bỏ.
$fbm_looks      = array();
$fbm_look_codes = array();
if ( preg_match_all( '/' . get_shortcode_regex( array( 'fbm_look' ) ) . '/s', (string) $content, $fbm_m ) ) {
	foreach ( $fbm_m[0] as $fbm_child ) {
		$fbm_code = preg_replace( '/^\[fbm_look\b/', '[fbm_look' . $fbm_pass, $fbm_child );
		$fbm_html = trim( do_shortcode( $fbm_code ) );
		if ( '' !== $fbm_html && false === strpos( $fbm_html, 'fbm-empty' ) ) {
			$fbm_looks[]      = $fbm_html;
			$fbm_look_codes[] = $fbm_code;
		}
	}
}
if ( ! $fbm_looks ) {
	echo FBM\Placeholder::empty( __( 'Shop The Look: thêm ít nhất một Look có ảnh và sản phẩm.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_classes = array( 'fbm-shop-look' );
if ( 'right' === $a['image_side'] ) {
	$fbm_classes[] = 'fbm-shop-look--right';
}
if ( in_array( $a['split'], array( '3-2', '2-1' ), true ) ) {
	$fbm_classes[] = 'fbm-shop-look--split-' . $a['split'];
}
if ( 'numbers' === $a['pin_style'] ) {
	$fbm_classes[] = 'fbm-shop-look--numbers';
}
if ( 'true' === $a['pulse'] ) {
	$fbm_classes[] = 'fbm-shop-look--pulse';
}
if ( 'dark' === $a['pin_tone'] ) {
	$fbm_classes[] = 'fbm-shop-look--tone-dark';
}
$fbm_raw = is_array( $atts ) ? $atts : array();
if ( '' !== trim( implode( '', array_intersect_key( $fbm_raw, array_flip( array( 'height', 'height__md', 'height__sm' ) ) ) ) ) ) {
	$fbm_classes[] = 'fbm-shop-look--fixed-height';
}
$fbm_ratios = ( require dirname( __DIR__ ) . '/look/options.php' )['ratios'];
if ( isset( $fbm_ratios[ $a['ratio'] ] ) ) {
	$fbm_classes[] = 'fbm-shop-look--ratio';
	$fbm_classes[] = 'fbm-shop-look--r-' . $a['ratio'];
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}
$fbm_id = sanitize_html_class( $a['_id'] );

// Chiều rộng / chiều cao khung theo từng màn hình: cùng cách element Image của Flatsome, thẻ <style> nằm trong phần tử gốc.
$fbm_style = function_exists( 'ux_builder_element_style_tag' ) ? ux_builder_element_style_tag(
	$fbm_id,
	array(
		'width'  => array(
			'selector' => '',
			'property' => 'width',
			'unit'     => '%',
		),
		'height' => array(
			'selector' => '.fbm-stl__media',
			'property' => 'height',
		),
		'gap'    => array(
			'selector' => '.fbm-shop-look__head',
			'property' => 'gap, margin-bottom',
			'unit'     => 'px',
		),
	),
	is_array( $atts ) ? $atts : array()
) : '';

// Tiêu đề (markup [title] của Flatsome, bỏ ký tự có thể phá shortcode) + mô tả: một lần, phía trên các Look.
$fbm_align  = in_array( $a['text_align'], array( 'left', 'center', 'right' ), true ) ? $a['text_align'] : 'left';
$fbm_title  = trim( str_replace( array( '"', '[', ']' ), '', (string) $a['title'] ) );
$fbm_header = '' !== $fbm_title ? do_shortcode( '[title text="' . $fbm_title . '" style="' . ( 'center' === $fbm_align ? 'center' : 'normal' ) . '"]' ) : '';
if ( '' !== trim( $a['description'] ) ) {
	$fbm_header .= '<p class="fbm-shop-look__desc">' . esc_html( $a['description'] ) . '</p>';
}
if ( '' !== $fbm_header ) {
	$fbm_header = '<div class="fbm-shop-look__head text-' . $fbm_align . '">' . $fbm_header . '</div>';
}

if ( 1 === count( $fbm_looks ) ) {
	echo '<div id="' . esc_attr( $fbm_id ) . '" class="' . esc_attr( implode( ' ', $fbm_classes ) ) . '">' . $fbm_header . $fbm_looks[0] . $fbm_style . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Look đã escape.
	return;
}

// Từ 2 Look: element Slider của Flatsome ([ux_slider]), chỉ truyền các thuộc tính của nó; mỗi Look là một slide.
$fbm_slider_keys = array( 'type', 'style', 'slide_width', 'slide_width__md', 'slide_width__sm', 'slide_align', 'bg_color', 'margin', 'margin__md', 'margin__sm', 'infinitive', 'freescroll', 'draggable', 'parallax', 'mobile', 'hide_nav', 'nav_pos', 'nav_size', 'arrows', 'nav_style', 'nav_color', 'bullets', 'bullet_style', 'auto_slide', 'timer', 'pause_hover' );
$fbm_slider_atts = '';
foreach ( $fbm_slider_keys as $fbm_key ) {
	if ( is_array( $atts ) && isset( $atts[ $fbm_key ] ) ) {
		$fbm_slider_atts .= ' ' . $fbm_key . '="' . esc_attr( str_replace( array( '"', '[', ']' ), '', (string) $atts[ $fbm_key ] ) ) . '"';
	}
}
echo '<div id="' . esc_attr( $fbm_id ) . '" class="' . esc_attr( implode( ' ', $fbm_classes ) ) . '">' . $fbm_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup [title] của Flatsome + mô tả đã escape.
echo do_shortcode( '[ux_slider' . $fbm_slider_atts . ']' . implode( '', $fbm_look_codes ) . '[/ux_slider]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Flatsome + Look đã escape.
echo $fbm_style . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS do Flatsome sinh.
