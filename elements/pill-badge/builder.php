<?php
defined( 'ABSPATH' ) || exit;

$fbm_tones = require __DIR__ . '/tones.php';

return array(
	'info'    => '{{ text }}',
	'options' => array(
		'content'          => array(
			'type'    => 'group',
			'heading' => __( 'Nội dung', 'flatsome-block-manager' ),
			'options' => array(
				'text'   => array( 'type' => 'textfield', 'heading' => __( 'Chữ', 'flatsome-block-manager' ), 'default' => 'Mới ra mắt' ),
				'link'   => array( 'type' => 'textfield', 'heading' => __( 'Liên kết', 'flatsome-block-manager' ), 'default' => '', 'placeholder' => '/shop/' ),
				'target' => array(
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
		'style'            => array(
			'type'    => 'group',
			'heading' => __( 'Kiểu', 'flatsome-block-manager' ),
			'options' => array(
				'tone'  => array(
					'type'    => 'select',
					'heading' => __( 'Màu nhấn', 'flatsome-block-manager' ),
					'default' => 'primary',
					'options' => $fbm_tones,
				),
				'dot'   => array( 'type' => 'checkbox', 'heading' => __( 'Chấm tròn', 'flatsome-block-manager' ), 'default' => 'true' ),
				'pulse' => array( 'type' => 'checkbox', 'heading' => __( 'Chấm nhấp nháy', 'flatsome-block-manager' ), 'default' => 'true', 'conditions' => 'dot === "true"' ),
				'size'  => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Cỡ', 'flatsome-block-manager' ),
					'default' => '',
					'options' => array(
						'small' => array( 'title' => 'S' ),
						''      => array( 'title' => 'M' ),
						'large' => array( 'title' => 'L' ),
					),
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
