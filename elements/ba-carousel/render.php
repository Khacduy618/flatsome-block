<?php
/**
 * [fbm_ba_carousel]: các [fbm_before_after] con trong slider Flickity của Flatsome (get_flatsome_repeater_start).
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'                 => 'fbm-bac-' . wp_rand(),
		'columns'             => '1',
		'columns__md'         => '',
		'columns__sm'         => '1',
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

// Render từng con; con rỗng (chưa chọn ảnh) với khách cho chuỗi rỗng và bị bỏ.
$items = array();
if ( preg_match_all( '/' . get_shortcode_regex( array( 'fbm_before_after' ) ) . '/s', (string) $content, $m ) ) {
	foreach ( $m[0] as $child ) {
		$html = trim( do_shortcode( $child ) );
		if ( '' !== $html && false === strpos( $html, 'fbm-empty' ) ) {
			$items[] = $html;
		}
	}
}
if ( ! $items ) {
	echo FBM\Placeholder::empty( __( 'Before / After Carousel: thêm ít nhất một Before / After có đủ 2 ảnh.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$classes = array( 'fbm-bac' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}

get_flatsome_repeater_start(
	array(
		'id'                  => sanitize_html_class( $a['_id'] ),
		'class'               => implode( ' ', $classes ),
		'type'                => 'slider',
		'slider_style'        => $a['slider_nav_style'],
		'slider_nav_color'    => $a['slider_nav_color'],
		'slider_nav_position' => $a['slider_nav_position'],
		'slider_bullets'      => $a['slider_bullets'],
		'auto_slide'          => $a['auto_slide'],
		'infinitive'          => $a['infinitive'],
		'row_spacing'         => 'small',
		'columns'             => max( 1, min( 4, (int) $a['columns'] ) ),
		'columns__md'         => max( 1, min( 4, (int) ( $a['columns__md'] ? $a['columns__md'] : $a['columns'] ) ) ),
		'columns__sm'         => max( 1, min( 2, (int) $a['columns__sm'] ) ),
	)
);
foreach ( $items as $html ) {
	echo '<div class="col fbm-bac__item"><div class="col-inner">' . $html . '</div></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của element Before / After đã escape.
}
get_flatsome_repeater_end( array( 'type' => 'slider' ) );
