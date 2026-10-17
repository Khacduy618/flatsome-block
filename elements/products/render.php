<?php
/**
 * [fbm_products]: thẻ sản phẩm của WooCommerce (content-product.php) trong khung repeater của Flatsome.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$defaults = array(
	'_id'                 => 'fbm-products-' . wp_rand(),
	'source'              => 'recent',
	'ids'                 => '',
	'cat'                 => '',
	'tags'                => '',
	'products'            => '8',
	'offset'              => '',
	'orderby'             => 'date',
	'order'               => 'DESC',
	'price_min'           => '',
	'price_max'           => '',
	'in_stock'            => 'false',
	'exclude'             => '',
	'type'                => 'slider',
	'width'               => '',
	'col_spacing'         => 'small',
	'columns'             => '4',
	'columns__md'         => '3',
	'columns__sm'         => '2',
	'depth'               => '',
	'depth_hover'         => '',
	'slider_nav_style'    => 'reveal',
	'slider_nav_color'    => '',
	'slider_nav_position' => '',
	'slider_bullets'      => 'false',
	'auto_slide'          => 'false',
	'infinitive'          => 'true',
	'class'               => '',
	'visibility'          => '',
);
$a = shortcode_atts( $defaults, $atts, $tag );

// Chọn tay sản phẩm (ô Posts của Flatsome) thì bỏ qua nguồn, trừ nguồn quan hệ.
$source = $a['source'];
if ( '' !== trim( $a['ids'] ) && ! in_array( $source, array( 'cross_sell', 'upsell', 'related', 'auto' ), true ) ) {
	$source = 'ids';
}

$ids = FBM\Products\Query::ids(
	array(
		'source'    => $source,
		'ids'       => $a['ids'],
		'cat'       => $a['cat'],
		'tags'      => $a['tags'],
		'products'  => $a['products'],
		'offset'    => $a['offset'],
		'orderby'   => $a['orderby'],
		'order'     => $a['order'],
		'price_min' => $a['price_min'],
		'price_max' => $a['price_max'],
		'in_stock'  => $a['in_stock'],
		'exclude'   => $a['exclude'],
	)
);

if ( ! $ids ) {
	echo FBM\Placeholder::empty( __( 'Products: không có sản phẩm phù hợp với bộ lọc.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã escape trong Placeholder.
	return;
}

$classes = array( 'fbm-products' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}

// Khoá giống ux_products.php của Flatsome.
echo FBM\Products\Loop::render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup của theme/WooCommerce.
	$ids,
	array(
		'id'                  => sanitize_html_class( $a['_id'] ),
		'class'               => implode( ' ', array_filter( $classes ) ),
		'type'                => $a['type'],
		'style'               => '',
		'slider_style'        => $a['slider_nav_style'],
		'slider_nav_color'    => $a['slider_nav_color'],
		'slider_nav_position' => $a['slider_nav_position'],
		'slider_bullets'      => $a['slider_bullets'],
		'auto_slide'          => $a['auto_slide'],
		'infinitive'          => $a['infinitive'],
		'row_spacing'         => $a['col_spacing'],
		'row_width'           => $a['width'],
		'columns'             => $a['columns'],
		'columns__md'         => $a['columns__md'],
		'columns__sm'         => $a['columns__sm'],
		'depth'               => $a['depth'],
		'depth_hover'         => $a['depth_hover'],
	)
);
