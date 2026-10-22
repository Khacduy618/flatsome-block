<?php
defined( 'ABSPATH' ) || exit;

$image_size_options = function_exists( 'flatsome_ux_builder_image_sizes' )
	? flatsome_ux_builder_image_sizes()
	: array(
		'medium'       => 'Medium',
		'medium_large' => 'Medium Large',
		'large'        => 'Large',
		'full'         => 'Full',
	);

return array(
	'name'      => __( 'Angled Card', 'flatsome-block-manager' ),
	'category'  => __( 'Content', 'flatsome' ),
	'require'   => array( 'fbm_angled_showcase' ),
	'allow_in'  => array( 'fbm_angled_showcase' ),
	'info'      => '{{ tag }} {{ title }}',
	'options'   => array(
		'content_options'  => array(
			'type'    => 'group',
			'heading' => __( 'Content', 'flatsome' ),
			'options' => array(
				'tag'       => array(
					'type'    => 'textfield',
					'heading' => __( 'Tag / Index', 'flatsome-block-manager' ),
					'default' => '01',
				),
				'title'     => array(
					'type'    => 'textfield',
					'heading' => __( 'Title', 'flatsome' ),
					'default' => 'Motivational Speech',
				),
				'title_tag' => array(
					'type'    => 'select',
					'heading' => __( 'Title Tag', 'flatsome-block-manager' ),
					'default' => 'h3',
					'options' => array(
						'h2' => 'H2',
						'h3' => 'H3',
						'h4' => 'H4',
						'p'  => 'P',
					),
				),
				'link'      => array(
					'type'        => 'textfield',
					'heading'     => __( 'Card Link', 'flatsome' ),
					'default'     => '',
					'placeholder' => 'https://...',
				),
				'target'    => array(
					'type'    => 'select',
					'heading' => __( 'Target', 'flatsome' ),
					'default' => '',
					'options' => array(
						''       => __( 'Same window', 'flatsome' ),
						'_blank' => __( 'New window', 'flatsome' ),
					),
				),
			),
		),
		'image_options'    => array(
			'type'    => 'group',
			'heading' => __( 'Image', 'flatsome' ),
			'options' => array(
				'image'      => array(
					'type'    => 'image',
					'heading' => __( 'Image', 'flatsome' ),
					'default' => '',
				),
				'image_size' => array(
					'type'    => 'select',
					'heading' => __( 'Image Size', 'flatsome' ),
					'default' => 'large',
					'options' => $image_size_options,
				),
			),
		),
		'override_options' => array(
			'type'    => 'group',
			'heading' => __( 'Override Bulk Style (Optional)', 'flatsome-block-manager' ),
			'options' => array(
				'cut_x'   => array(
					'type'    => 'slider',
					'heading' => __( 'Override Cut Width (%)', 'flatsome-block-manager' ),
					'default' => '',
					'unit'    => '%',
					'min'     => 35,
					'max'     => 80,
					'step'    => 1,
				),
				'cut_y'   => array(
					'type'    => 'slider',
					'heading' => __( 'Override Cut Height (px)', 'flatsome-block-manager' ),
					'default' => '',
					'unit'    => 'px',
					'min'     => 60,
					'max'     => 220,
					'step'    => 5,
				),
				'chamfer' => array(
					'type'    => 'slider',
					'heading' => __( 'Override Chamfer (px)', 'flatsome-block-manager' ),
					'default' => '',
					'unit'    => 'px',
					'min'     => 0,
					'max'     => 40,
					'step'    => 2,
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
