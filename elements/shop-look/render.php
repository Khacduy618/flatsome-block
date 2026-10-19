<?php
/**
 * [fbm_shop_look]: khung chứa các [fbm_look]. 1 Look thì in thẳng; từ 2 Look là slider Flickity của Flatsome.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'                 => 'shop-look-' . wp_rand(),
		'slider_nav_style'    => 'circle',
		'slider_nav_color'    => '',
		'slider_nav_position' => '',
		'slider_bullets'      => 'true',
		'auto_slide'          => 'false',
		'infinitive'          => 'false',
		'class'               => '',
		'visibility'          => '',
	),
	$atts,
	$tag
);

// Render từng Look; Look rỗng (thiếu ảnh / sản phẩm) bị bỏ.
$fbm_looks = array();
if ( preg_match_all( '/' . get_shortcode_regex( array( 'fbm_look' ) ) . '/s', (string) $content, $fbm_m ) ) {
	foreach ( $fbm_m[0] as $fbm_child ) {
		$fbm_html = trim( do_shortcode( $fbm_child ) );
		if ( '' !== $fbm_html && false === strpos( $fbm_html, 'fbm-empty' ) ) {
			$fbm_looks[] = $fbm_html;
		}
	}
}
if ( ! $fbm_looks ) {
	echo FBM\Placeholder::empty( __( 'Shop The Look: thêm ít nhất một Look có ảnh và sản phẩm.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_classes = array( 'fbm-shop-look' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}
$fbm_id = sanitize_html_class( $a['_id'] );

if ( 1 === count( $fbm_looks ) ) {
	echo '<div id="' . esc_attr( $fbm_id ) . '" class="' . esc_attr( implode( ' ', $fbm_classes ) ) . '">' . $fbm_looks[0] . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Look đã escape.
	return;
}

echo '<div class="' . esc_attr( implode( ' ', $fbm_classes ) ) . '">';
get_flatsome_repeater_start(
	array(
		'id'                  => $fbm_id,
		'type'                => 'slider',
		'slider_style'        => $a['slider_nav_style'],
		'slider_nav_color'    => $a['slider_nav_color'],
		'slider_nav_position' => $a['slider_nav_position'],
		'slider_bullets'      => $a['slider_bullets'],
		'auto_slide'          => $a['auto_slide'],
		'infinitive'          => $a['infinitive'],
		'row_spacing'         => 'small',
		'columns'             => 1,
		'columns__md'         => 1,
		'columns__sm'         => 1,
	)
);
foreach ( $fbm_looks as $fbm_html ) {
	echo '<div class="col fbm-shop-look__slide"><div class="col-inner">' . $fbm_html . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Look đã escape.
}
get_flatsome_repeater_end( array( 'type' => 'slider' ) );
echo '</div>';
