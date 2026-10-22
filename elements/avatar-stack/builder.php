<?php
defined( 'ABSPATH' ) || exit;

return array(
	'info'    => '{{ text }}',
	'options' => array(
		'content'          => array(
			'type'    => 'group',
			'heading' => __( 'Nội dung', 'flatsome-block-manager' ),
			'options' => array(
				'ids'         => array( 'type' => 'gallery', 'heading' => __( 'Ảnh đại diện (tối đa 8)', 'flatsome-block-manager' ) ),
				'show_rating' => array( 'type' => 'checkbox', 'heading' => __( 'Hiện số sao', 'flatsome-block-manager' ), 'default' => 'true' ),
				'rating'      => array(
					'type'       => 'slider',
					'heading'    => __( 'Số sao', 'flatsome-block-manager' ),
					'default'    => '4.9',
					'min'        => 0,
					'max'        => 5,
					'step'       => 0.1,
					'conditions' => 'show_rating === "true"',
				),
				'text'        => array( 'type' => 'textfield', 'heading' => __( 'Chữ đánh giá', 'flatsome-block-manager' ), 'default' => 'Hơn 2.000 khách hàng hài lòng' ),
			),
		),
		'style'            => array(
			'type'    => 'group',
			'heading' => __( 'Kiểu', 'flatsome-block-manager' ),
			'options' => array(
				'size'    => array(
					'type'    => 'slider',
					'heading' => __( 'Cỡ ảnh', 'flatsome-block-manager' ),
					'default' => '44',
					'unit'    => 'px',
					'min'     => 24,
					'max'     => 96,
				),
				'overlap' => array(
					'type'    => 'slider',
					'heading' => __( 'Độ chồng', 'flatsome-block-manager' ),
					'default' => '12',
					'unit'    => 'px',
					'min'     => 0,
					'max'     => 40,
				),
			),
		),
		'layout'           => array(
			'type'    => 'group',
			'heading' => __( 'Bố cục', 'flatsome-block-manager' ),
			'options' => array(
				'align' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Căn', 'flatsome-block-manager' ),
					'default' => 'left',
					'options' => array(
						'left'   => array( 'title' => '', 'icon' => 'dashicons-editor-alignleft' ),
						'center' => array( 'title' => '', 'icon' => 'dashicons-editor-aligncenter' ),
						'right'  => array( 'title' => '', 'icon' => 'dashicons-editor-alignright' ),
					),
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
