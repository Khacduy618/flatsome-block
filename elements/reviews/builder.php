<?php
defined( 'ABSPATH' ) || exit;

$fbm_widgets = array();
foreach ( require __DIR__ . '/widgets.php' as $fbm_tag => $fbm_def ) {
	$fbm_widgets[ $fbm_tag ] = $fbm_def[0];
}

return array(
	'info'    => '{{ widget }}',
	'options' => array(
		'widget'           => array(
			'type'    => 'select',
			'heading' => __( 'Widget Judge.me', 'flatsome-block-manager' ),
			'default' => 'jgm-featured-carousel',
			'options' => $fbm_widgets,
		),
		'title'            => array(
			'type'    => 'textfield',
			'heading' => __( 'Tiêu đề section (element Title của Flatsome)', 'flatsome-block-manager' ),
			'default' => '',
		),
		'product'          => array(
			'type'        => 'select',
			'heading'     => __( 'Sản phẩm', 'flatsome-block-manager' ),
			'description' => __( 'Để trống = sản phẩm đang xem.', 'flatsome-block-manager' ),
			'conditions'  => 'widget === "jgm-review-widget" || widget === "jgm-preview-badge"',
			'config'      => array(
				'multiple'    => false,
				'placeholder' => __( 'Sản phẩm đang xem', 'flatsome-block-manager' ),
				'postSelect'  => array( 'post_type' => array( 'product' ) ),
			),
		),
		'carousel_title'   => array(
			'type'       => 'textfield',
			'heading'    => __( 'Tiêu đề trong carousel', 'flatsome-block-manager' ),
			'conditions' => 'widget === "jgm-featured-carousel"',
			'default'    => '',
		),
		'all_reviews_url'  => array(
			'type'       => 'textfield',
			'heading'    => __( 'Link trang tất cả đánh giá', 'flatsome-block-manager' ),
			'conditions' => 'widget === "jgm-featured-carousel"',
			'default'    => '',
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
