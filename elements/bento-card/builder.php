<?php
defined( 'ABSPATH' ) || exit;

return array(
	'info'    => '{{ tag }} {{ title }}',
	'options' => array(
		'content'          => array(
			'type'    => 'group',
			'heading' => __( 'Content', 'flatsome-block-manager' ),
			'options' => array(
				'tag'       => array( 'type' => 'textfield', 'heading' => __( 'Number Tag', 'flatsome-block-manager' ), 'default' => '01' ),
				'title'     => array( 'type' => 'textfield', 'heading' => __( 'Title', 'flatsome-block-manager' ), 'default' => 'Natural oak frame' ),
				'title_tag' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Title Tag', 'flatsome-block-manager' ),
					'default' => 'h3',
					'options' => array(
						'h2' => array( 'title' => 'H2' ),
						'h3' => array( 'title' => 'H3' ),
						'h4' => array( 'title' => 'H4' ),
						'p'  => array( 'title' => 'P' ),
					),
				),
				'text'      => array( 'type' => 'textarea', 'heading' => __( 'Description', 'flatsome-block-manager' ), 'default' => 'Real wood grain, finished by hand, colorfast over time.' ),
				'link_text' => array( 'type' => 'textfield', 'heading' => __( 'Button Text', 'flatsome-block-manager' ), 'default' => 'Learn more' ),
				'link'      => array( 'type' => 'textfield', 'heading' => __( 'Link', 'flatsome-block-manager' ), 'default' => '', 'placeholder' => '/shop/' ),
				'target'    => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Open In', 'flatsome-block-manager' ),
					'default' => '',
					'options' => array(
						''       => array( 'title' => __( 'Same tab', 'flatsome-block-manager' ) ),
						'_blank' => array( 'title' => __( 'New tab', 'flatsome-block-manager' ) ),
					),
				),
			),
		),
		'media'            => array(
			'type'    => 'group',
			'heading' => __( 'Image', 'flatsome-block-manager' ),
			'options' => array(
				'image'      => array( 'type' => 'image', 'heading' => __( 'Image', 'flatsome-block-manager' ), 'default' => '' ),
				'image_size' => array(
					'type'    => 'select',
					'heading' => __( 'Image Size', 'flatsome-block-manager' ),
					'default' => 'large',
					'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
				),
				'layout'     => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Direction', 'flatsome-block-manager' ),
					'default' => 'vertical',
					'options' => array(
						'vertical'   => array( 'title' => __( 'Vertical (image at bottom)', 'flatsome-block-manager' ) ),
						'horizontal' => array( 'title' => __( 'Ngang', 'flatsome-block-manager' ) ),
					),
				),
				'media_side' => array(
					'type'       => 'radio-buttons',
					'heading'    => __( 'Image Side', 'flatsome-block-manager' ),
					'default'    => 'right',
					'conditions' => 'layout === "horizontal"',
					'options'    => array(
						'left'  => array( 'title' => __( 'Left', 'flatsome-block-manager' ) ),
						'right' => array( 'title' => __( 'Right', 'flatsome-block-manager' ) ),
					),
				),
			),
		),
		'style'            => array(
			'type'    => 'group',
			'heading' => __( 'Style', 'flatsome-block-manager' ),
			'options' => array(
				'style'      => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Card Background', 'flatsome-block-manager' ),
					'default' => 'light',
					'options' => array(
						'light' => array( 'title' => __( 'Border', 'flatsome-block-manager' ) ),
						'soft'  => array( 'title' => __( 'Soft', 'flatsome-block-manager' ) ),
						'dark'  => array( 'title' => __( 'Primary', 'flatsome-block-manager' ) ),
					),
				),
				'radius'     => array(
					'type'    => 'slider',
					'heading' => __( 'Radius', 'flatsome-block-manager' ),
					'default' => '',
					'unit'    => 'px',
					'min'     => 0,
					'max'     => 48,
				),
				'padding'    => array(
					'type'       => 'slider',
					'heading'    => __( 'Padding', 'flatsome-block-manager' ),
					'responsive' => true,
					'default'    => '',
					'unit'       => 'px',
					'min'        => 0,
					'max'        => 80,
				),
				'min_height' => array(
					'type'       => 'scrubfield',
					'heading'    => __( 'Min Height', 'flatsome-block-manager' ),
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
