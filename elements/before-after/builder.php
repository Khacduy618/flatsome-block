<?php
defined( 'ABSPATH' ) || exit;

return array(
	'info'    => '{{ label_before }} / {{ label_after }}',
	'options' => array(
		'images'           => array(
			'type'    => 'group',
			'heading' => __( 'Ảnh', 'flatsome-block-manager' ),
			'options' => array(
				'before'     => array( 'type' => 'image', 'heading' => __( 'Trước', 'flatsome-block-manager' ), 'default' => '' ),
				'after'      => array( 'type' => 'image', 'heading' => __( 'Sau', 'flatsome-block-manager' ), 'default' => '' ),
				'image_size' => array(
					'type'    => 'select',
					'heading' => __( 'Kích thước ảnh', 'flatsome-block-manager' ),
					'default' => 'large',
					'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
				),
			),
		),
		'behaviour'        => array(
			'type'    => 'group',
			'heading' => __( 'Cách so sánh', 'flatsome-block-manager' ),
			'options' => array(
				'orientation' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Hướng', 'flatsome-block-manager' ),
					'default' => 'horizontal',
					'options' => array(
						'horizontal' => array( 'title' => __( 'Ngang', 'flatsome-block-manager' ) ),
						'vertical'   => array( 'title' => __( 'Dọc', 'flatsome-block-manager' ) ),
					),
				),
				'trigger'     => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Điều khiển', 'flatsome-block-manager' ),
					'default' => 'drag',
					'options' => array(
						'drag'  => array( 'title' => __( 'Kéo', 'flatsome-block-manager' ) ),
						'hover' => array( 'title' => __( 'Rê chuột', 'flatsome-block-manager' ) ),
					),
				),
				'offset'      => array( 'type' => 'slider', 'heading' => __( 'Vị trí ban đầu', 'flatsome-block-manager' ), 'default' => '50', 'min' => 0, 'max' => 100, 'unit' => '%' ),
				'auto'        => array( 'type' => 'checkbox', 'heading' => __( 'Tự chạy gợi ý một lần khi cuộn tới', 'flatsome-block-manager' ), 'default' => 'false' ),
				'handle'      => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Tay nắm', 'flatsome-block-manager' ),
					'default' => 'circle',
					'options' => array(
						'circle' => array( 'title' => __( 'Hiện', 'flatsome-block-manager' ) ),
						'none'   => array( 'title' => __( 'Chỉ đường kẻ', 'flatsome-block-manager' ) ),
					),
				),
			),
		),
		'labels'           => array(
			'type'    => 'group',
			'heading' => __( 'Nhãn', 'flatsome-block-manager' ),
			'options' => array(
				'show_labels'  => array( 'type' => 'checkbox', 'heading' => __( 'Hiện nhãn', 'flatsome-block-manager' ), 'default' => 'false' ),
				'label_before' => array( 'type' => 'textfield', 'heading' => __( 'Nhãn trước', 'flatsome-block-manager' ), 'default' => 'Trước' ),
				'label_after'  => array( 'type' => 'textfield', 'heading' => __( 'Nhãn sau', 'flatsome-block-manager' ), 'default' => 'Sau' ),
				'caption'      => array( 'type' => 'textfield', 'heading' => __( 'Chú thích dưới ảnh', 'flatsome-block-manager' ), 'default' => '' ),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
