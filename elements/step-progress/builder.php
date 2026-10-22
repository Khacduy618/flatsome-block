<?php
defined( 'ABSPATH' ) || exit;

return array(
	'info'    => '{{ style }}',
	'options' => array(
		'progress_options' => array(
			'type'    => 'group',
			'heading' => __( 'Progress & Style', 'flatsome-block-manager' ),
			'options' => array(
				'style'        => array(
					'type'    => 'select',
					'heading' => __( 'Display Style', 'flatsome-block-manager' ),
					'default' => 'segmented',
					'options' => array(
						'segmented' => __( 'Segmented Bar', 'flatsome-block-manager' ),
						'slider'    => __( 'Continuous Slider Bar', 'flatsome-block-manager' ),
						'fill_up'   => __( 'Fill Up Bar', 'flatsome-block-manager' ),
						'numbered'  => __( 'Numbered Circle Nodes', 'flatsome-block-manager' ),
						'dot'       => __( 'Minimal Dots', 'flatsome-block-manager' ),
					),
				),
				'height'       => array(
					'type'    => 'scrubfield',
					'heading' => __( 'Height', 'flatsome-block-manager' ),
					'default' => '6px',
					'min'     => 1,
					'max'     => 40,
					'step'    => 1,
				),
				'labels'       => array(
					'type'        => 'textfield',
					'heading'     => __( 'Step Labels', 'flatsome-block-manager' ),
					'description' => __( 'Comma-separated step titles. Leave empty to hide labels.', 'flatsome-block-manager' ),
					'placeholder' => 'Template, Goal, Details',
					'default'     => '',
				),
				'target_class' => array(
					'type'        => 'textfield',
					'heading'     => __( 'Target Slide Selector', 'flatsome-block-manager' ),
					'description' => __( 'Custom class of slide items (leave default or empty to auto-detect adjacent slider)', 'flatsome-block-manager' ),
					'default'     => '.step-item',
				),
				'active_color' => array(
					'type'    => 'colorpicker',
					'heading' => __( 'Active Color', 'flatsome-block-manager' ),
					'default' => '',
					'alpha'   => true,
				),
				'max_width'    => array(
					'type'    => 'textfield',
					'heading' => __( 'Max Width', 'flatsome-block-manager' ),
					'default' => '1330px',
				),
				'margin_top'   => array(
					'type'    => 'scrubfield',
					'heading' => __( 'Margin Top', 'flatsome-block-manager' ),
					'default' => '30px',
					'min'     => 0,
					'max'     => 150,
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
