<?php
defined( 'ABSPATH' ) || exit;

$fbm_tones = require __DIR__ . '/tones.php';

return array(
	'info'    => '{{ text }}',
	'options' => array(
		'content'          => array(
			'type'    => 'group',
			'heading' => __( 'Content', 'flatsome-block-manager' ),
			'options' => array(
				'text'   => array( 'type' => 'textfield', 'heading' => __( 'Text', 'flatsome-block-manager' ), 'default' => 'New arrival' ),
				'link'   => array( 'type' => 'textfield', 'heading' => __( 'Link', 'flatsome-block-manager' ), 'default' => '', 'placeholder' => '/shop/' ),
				'target' => array(
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
		'style'            => array(
			'type'    => 'group',
			'heading' => __( 'Style', 'flatsome-block-manager' ),
			'options' => array(
				'tone'  => array(
					'type'    => 'select',
					'heading' => __( 'Accent Color', 'flatsome-block-manager' ),
					'default' => 'primary',
					'options' => $fbm_tones,
				),
				'dot'   => array( 'type' => 'checkbox', 'heading' => __( 'Dot', 'flatsome-block-manager' ), 'default' => 'true' ),
				'pulse' => array( 'type' => 'checkbox', 'heading' => __( 'Pulsing Dot', 'flatsome-block-manager' ), 'default' => 'true', 'conditions' => 'dot === "true"' ),
				'size'  => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Size', 'flatsome-block-manager' ),
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
			'heading' => __( 'Layout', 'flatsome-block-manager' ),
			'options' => array(
				'align' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Align', 'flatsome-block-manager' ),
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
