<?php
defined( 'ABSPATH' ) || exit;

return array(
	'info'    => '{{ tag }} {{ title }}',
	'options' => array(
		'content'          => array(
			'type'    => 'group',
			'heading' => __( 'Nội dung', 'flatsome-block-manager' ),
			'options' => array(
				'tag'       => array( 'type' => 'textfield', 'heading' => __( 'Nhãn số thứ tự', 'flatsome-block-manager' ), 'default' => '01' ),
				'title'     => array( 'type' => 'textfield', 'heading' => __( 'Tiêu đề', 'flatsome-block-manager' ), 'default' => 'Khung gỗ sồi tự nhiên' ),
				'title_tag' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Thẻ tiêu đề', 'flatsome-block-manager' ),
					'default' => 'h3',
					'options' => array(
						'h2' => array( 'title' => 'H2' ),
						'h3' => array( 'title' => 'H3' ),
						'h4' => array( 'title' => 'H4' ),
						'p'  => array( 'title' => 'P' ),
					),
				),
				'text'      => array( 'type' => 'textarea', 'heading' => __( 'Mô tả', 'flatsome-block-manager' ), 'default' => 'Vân gỗ thật, hoàn thiện thủ công, bền màu theo thời gian.' ),
				'link_text' => array( 'type' => 'textfield', 'heading' => __( 'Chữ nút', 'flatsome-block-manager' ), 'default' => 'Xem thêm' ),
				'link'      => array( 'type' => 'textfield', 'heading' => __( 'Liên kết', 'flatsome-block-manager' ), 'default' => '', 'placeholder' => '/shop/' ),
				'target'    => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Mở', 'flatsome-block-manager' ),
					'default' => '',
					'options' => array(
						''       => array( 'title' => __( 'Cùng tab', 'flatsome-block-manager' ) ),
						'_blank' => array( 'title' => __( 'Tab mới', 'flatsome-block-manager' ) ),
					),
				),
			),
		),
		'media'            => array(
			'type'    => 'group',
			'heading' => __( 'Ảnh', 'flatsome-block-manager' ),
			'options' => array(
				'image'      => array( 'type' => 'image', 'heading' => __( 'Ảnh', 'flatsome-block-manager' ), 'default' => '' ),
				'image_size' => array(
					'type'    => 'select',
					'heading' => __( 'Kích thước ảnh', 'flatsome-block-manager' ),
					'default' => 'large',
					'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
				),
				'layout'     => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Hướng xếp', 'flatsome-block-manager' ),
					'default' => 'vertical',
					'options' => array(
						'vertical'   => array( 'title' => __( 'Dọc (ảnh ở đáy)', 'flatsome-block-manager' ) ),
						'horizontal' => array( 'title' => __( 'Ngang', 'flatsome-block-manager' ) ),
					),
				),
				'media_side' => array(
					'type'       => 'radio-buttons',
					'heading'    => __( 'Ảnh nằm bên', 'flatsome-block-manager' ),
					'default'    => 'right',
					'conditions' => 'layout === "horizontal"',
					'options'    => array(
						'left'  => array( 'title' => __( 'Trái', 'flatsome-block-manager' ) ),
						'right' => array( 'title' => __( 'Phải', 'flatsome-block-manager' ) ),
					),
				),
			),
		),
		'style'            => array(
			'type'    => 'group',
			'heading' => __( 'Kiểu', 'flatsome-block-manager' ),
			'options' => array(
				'style'      => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Nền thẻ', 'flatsome-block-manager' ),
					'default' => 'light',
					'options' => array(
						'light' => array( 'title' => __( 'Viền', 'flatsome-block-manager' ) ),
						'soft'  => array( 'title' => __( 'Nền phụ', 'flatsome-block-manager' ) ),
						'dark'  => array( 'title' => __( 'Màu chính', 'flatsome-block-manager' ) ),
					),
				),
				'radius'     => array(
					'type'    => 'slider',
					'heading' => __( 'Bo góc', 'flatsome-block-manager' ),
					'default' => '',
					'unit'    => 'px',
					'min'     => 0,
					'max'     => 48,
				),
				'padding'    => array(
					'type'       => 'slider',
					'heading'    => __( 'Khoảng đệm', 'flatsome-block-manager' ),
					'responsive' => true,
					'default'    => '',
					'unit'       => 'px',
					'min'        => 0,
					'max'        => 80,
				),
				'min_height' => array(
					'type'       => 'scrubfield',
					'heading'    => __( 'Chiều cao tối thiểu', 'flatsome-block-manager' ),
					'responsive' => true,
					'default'    => '',
					'unit'       => 'px',
					'min'        => 0,
					'max'        => 900,
					'step'       => 10,
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
