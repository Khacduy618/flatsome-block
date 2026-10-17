<?php
/**
 * Option trong UX Builder: nhóm commons của Flatsome (giống ux_products) + nguồn và bộ lọc của plugin.
 * $commons do Registry cấp.
 */
defined( 'ABSPATH' ) || exit;

$repeater_type      = 'slider';
$repeater_columns   = '4';
$repeater_col_spacing = 'small';
$repeater_post_type = 'product';
$repeater_post_cat  = 'product_cat';
$repeater_posts     = 'products';

$post_options = require $commons . 'repeater-posts.php';
// Thứ tự sắp xếp theo WooCommerce (bộ mặc định của Flatsome dành cho bài viết).
$post_options['options']['orderby']['options'] = array(
	'date'       => __( 'Mới nhất', 'flatsome-block-manager' ),
	'price'      => __( 'Giá', 'flatsome-block-manager' ),
	'popularity' => __( 'Bán chạy', 'flatsome-block-manager' ),
	'rating'     => __( 'Đánh giá', 'flatsome-block-manager' ),
	'title'      => __( 'Tên', 'flatsome-block-manager' ),
	'menu_order' => __( 'Thứ tự menu', 'flatsome-block-manager' ),
	'rand'       => __( 'Ngẫu nhiên', 'flatsome-block-manager' ),
);
$post_options['options']['tags'] = array(
	'type'       => 'select',
	'heading'    => __( 'Tag', 'flatsome-block-manager' ),
	'param_name' => 'tags',
	'full_width' => true,
	'conditions' => 'ids == ""',
	'default'    => '',
	'config'     => array(
		'multiple'    => true,
		'placeholder' => 'Select...',
		'termSelect'  => array(
			'post_type'  => 'product_tag',
			'taxonomies' => 'product_tag',
		),
	),
);

return array(
	'info'    => '{{ source }}',
	'options' => array(
		'source'                => array(
			'type'    => 'select',
			'heading' => __( 'Nguồn', 'flatsome-block-manager' ),
			'default' => 'recent',
			'options' => array(
				'recent'       => __( 'Mới nhất', 'flatsome-block-manager' ),
				'featured'     => __( 'Nổi bật', 'flatsome-block-manager' ),
				'sale'         => __( 'Đang giảm giá', 'flatsome-block-manager' ),
				'best_selling' => __( 'Bán chạy', 'flatsome-block-manager' ),
				'top_rated'    => __( 'Đánh giá cao', 'flatsome-block-manager' ),
				'cross_sell'   => __( 'Cross-sell của sản phẩm / giỏ hàng', 'flatsome-block-manager' ),
				'upsell'       => __( 'Upsell của sản phẩm', 'flatsome-block-manager' ),
				'related'      => __( 'Sản phẩm liên quan', 'flatsome-block-manager' ),
				'auto'         => __( 'Tự động: cross-sell → upsell → liên quan', 'flatsome-block-manager' ),
			),
		),
		'post_options'          => $post_options,
		'filter_options'        => array(
			'type'    => 'group',
			'heading' => __( 'Lọc', 'flatsome-block-manager' ),
			'options' => array(
				'price_min' => array( 'type' => 'textfield', 'heading' => __( 'Giá từ', 'flatsome-block-manager' ), 'default' => '' ),
				'price_max' => array( 'type' => 'textfield', 'heading' => __( 'Giá đến', 'flatsome-block-manager' ), 'default' => '' ),
				'in_stock'  => array( 'type' => 'checkbox', 'heading' => __( 'Chỉ hàng còn', 'flatsome-block-manager' ), 'default' => 'false' ),
				'exclude'   => array( 'type' => 'textfield', 'heading' => __( 'Loại trừ ID (cách nhau bằng dấu phẩy)', 'flatsome-block-manager' ), 'default' => '' ),
			),
		),
		'layout_options'        => require $commons . 'repeater-options.php',
		'layout_options_slider' => require $commons . 'repeater-slider.php',
		'advanced_options'      => require $commons . 'advanced.php',
	),
);
