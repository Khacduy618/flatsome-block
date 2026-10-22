<?php
defined( 'ABSPATH' ) || exit;

$fbm_o = 'shortcode.options.';

// Canvas: the frame is an Angular template (option changes show instantly); each step is a child element with its own template.
// Same markup as render.php; view.js runs the sequence, editor.js links it to the step selected in UX Builder.
$fbm_template = '<section class="fbm-as relative {{ ' . $fbm_o . 'class }} {{ ' . $fbm_o . 'visibility }}"'
	. ' style="--fbm-as-dur: {{ ' . $fbm_o . 'duration || 700 }}ms" data-duration="{{ ' . $fbm_o . 'duration || 700 }}" data-delay="{{ ' . $fbm_o . 'stepDelay || 1200 }}"'
	. ' aria-label="{{ ' . $fbm_o . 'title || \'Animated Showcase\' }}">'
	. '<div class="text-center" ng-if="' . $fbm_o . 'title || ' . $fbm_o . 'description">'
	. '<div ng-if="' . $fbm_o . 'title" class="container section-title-container"><h2 class="section-title section-title-center"><b aria-hidden="true"></b><span class="section-title-main">{{ ' . $fbm_o . 'title }}</span><b aria-hidden="true"></b></h2></div>'
	. '<p ng-if="' . $fbm_o . 'description">{{ ' . $fbm_o . 'description }}</p>'
	. '</div>'
	. '<div class="fbm-as__track"><content></content></div>'
	. '</section>';

return array(
	'type'     => 'container',
	'allow'    => array( 'fbm_showcase_step' ),
	'wrap'     => false, // One root node: section.fbm-as.
	'info'     => '{{ shortcode.children.length }} steps',
	'template' => $fbm_template,
	'presets'  => array(
		array(
			'name'    => __( '2 steps', 'flatsome-block-manager' ),
			'content' => '[fbm_animated_showcase][fbm_showcase_step][fbm_showcase_step][/fbm_animated_showcase]',
		),
	),
	'options'  => array(
		'general_options'   => array(
			'type'    => 'group',
			'heading' => __( 'General', 'flatsome-block-manager' ),
			'options' => array(
				'title'       => array( 'type' => 'textfield', 'heading' => __( 'Title', 'flatsome-block-manager' ), 'default' => '' ),
				'description' => array( 'type' => 'textarea', 'heading' => __( 'Description', 'flatsome-block-manager' ), 'default' => '' ),
			),
		),
		'animation_options' => array(
			'type'    => 'group',
			'heading' => __( 'Animation', 'flatsome-block-manager' ),
			'options' => array(
				'duration'   => array(
					'type'    => 'slider',
					'heading' => __( 'Animation duration', 'flatsome-block-manager' ),
					'default' => '700',
					'unit'    => 'ms',
					'min'     => 300,
					'max'     => 2000,
					'step'    => 50,
				),
				'step_delay' => array(
					'type'    => 'slider',
					'heading' => __( 'Delay between steps', 'flatsome-block-manager' ),
					'default' => '1200',
					'unit'    => 'ms',
					'min'     => 200,
					'max'     => 4000,
					'step'    => 100,
				),
			),
		),
		'image_options'     => array(
			'type'    => 'group',
			'heading' => __( 'Images', 'flatsome-block-manager' ),
			'options' => array(
				'image_ratio' => array(
					'type'    => 'select',
					'heading' => __( 'Image ratio', 'flatsome-block-manager' ),
					'default' => '1-1',
					'options' => wp_list_pluck( require __DIR__ . '/ratios.php', 'label' ),
				),
				'image_size'  => array(
					'type'    => 'select',
					'heading' => __( 'Image size', 'flatsome-block-manager' ),
					'default' => 'large',
					'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
				),
			),
		),
		'advanced_options'  => require $commons . 'advanced.php',
	),
);
