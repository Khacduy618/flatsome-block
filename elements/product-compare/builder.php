<?php
defined( 'ABSPATH' ) || exit;

$fbm_row = static function ( $label ) {
	return array(
		'type'    => 'checkbox',
		'heading' => $label,
		'default' => 'true',
	);
};

return array(
	'info'    => '{{ title }}',
	'options' => array(
		'title'            => array(
			'type'    => 'textfield',
			'heading' => __( 'Tiêu đề bảng', 'flatsome-block-manager' ),
			'default' => '',
		),
		'source'           => array(
			'type'    => 'radio-buttons',
			'heading' => __( 'Sản phẩm', 'flatsome-block-manager' ),
			'default' => 'ids',
			'options' => array(
				'ids'            => array( 'title' => __( 'Chọn tay', 'flatsome-block-manager' ) ),
				'current_upsell' => array( 'title' => __( 'Đang xem + upsell', 'flatsome-block-manager' ) ),
			),
		),
		'ids'              => array(
			'type'        => 'select',
			'heading'     => __( 'Chọn 2–4 sản phẩm', 'flatsome-block-manager' ),
			'full_width'  => true,
			'conditions'  => 'source === "ids"',
			'config'      => array(
				'multiple'    => true,
				'placeholder' => 'Select..',
				'postSelect'  => array( 'post_type' => array( 'product' ) ),
			),
		),
		'rows_options'     => array(
			'type'    => 'group',
			'heading' => __( 'Dòng', 'flatsome-block-manager' ),
			'options' => array(
				'show_image'       => $fbm_row( __( 'Ảnh', 'flatsome-block-manager' ) ),
				'show_price'       => $fbm_row( __( 'Giá', 'flatsome-block-manager' ) ),
				'show_rating'      => $fbm_row( __( 'Đánh giá', 'flatsome-block-manager' ) ),
				'show_stock'       => $fbm_row( __( 'Tình trạng kho', 'flatsome-block-manager' ) ),
				'show_attributes'  => $fbm_row( __( 'Thuộc tính', 'flatsome-block-manager' ) ),
				'attributes'       => array(
					'type'        => 'textfield',
					'heading'     => __( 'Chỉ các thuộc tính (tên, cách nhau dấu phẩy)', 'flatsome-block-manager' ),
					'description' => __( 'Để trống = mọi thuộc tính hiển thị.', 'flatsome-block-manager' ),
					'default'     => '',
				),
				'show_add_to_cart' => $fbm_row( __( 'Nút mua', 'flatsome-block-manager' ) ),
				'only_diff'        => array(
					'type'    => 'checkbox',
					'heading' => __( 'Chỉ hiện dòng khác nhau', 'flatsome-block-manager' ),
					'default' => 'false',
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
