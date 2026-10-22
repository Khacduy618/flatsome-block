<?php
/**
 * Light Slider UX Builder Definition
 *
 * Option names follow Flatsome's [ux_slider] (arrows, hide_nav, nav_style, nav_color, bullets, bullet_style,
 * auto_slide, timer, pause_hover). Canvas: the Angular template sets the per-breakpoint CSS vars (like
 * ux_stack); no motion, the viewport scrolls sideways so every slide stays editable. Template names are camelCase.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

$fbm_o       = 'shortcode.options.';
$fbm_presets = require __DIR__ . '/preset.php';

$fbm_template = '<div id="{{:: shortcode.$id }}" class="fbm-ls is-canvas'
	. ' slider-nav-{{ ' . $fbm_o . 'navStyle }} slider-nav-{{ ' . $fbm_o . 'navColor }}'
	. ' slider-nav-dots-{{ ' . $fbm_o . 'bulletStyle }}'
	. ' {{ ' . $fbm_o . 'class }} {{ ' . $fbm_o . 'visibility }}"'
	. ' ng-class="{ \'fbm-ls--top\': ' . $fbm_o . 'equalHeight !== \'true\', \'slider-show-nav\': ' . $fbm_o . 'hideNav === \'true\' }">'
	. '<div class="fbm-ls__viewport"><div class="fbm-ls__track"><content></content></div></div>'
	. '<button type="button" class="flickity-button flickity-prev-next-button previous" aria-label="Previous" ng-if="' . $fbm_o . 'arrows !== \'false\'">'
	. '<svg class="flickity-button-icon" viewBox="0 0 100 100"><path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" class="arrow"></path></svg>'
	. '</button>'
	. '<button type="button" class="flickity-button flickity-prev-next-button next" aria-label="Next" ng-if="' . $fbm_o . 'arrows !== \'false\'">'
	. '<svg class="flickity-button-icon" viewBox="0 0 100 100"><path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" class="arrow" transform="translate(100, 100) rotate(180) "></path></svg>'
	. '</button>'
	. '<ol class="flickity-page-dots" ng-if="' . $fbm_o . 'bullets !== \'false\'"><li class="dot is-selected"></li><li class="dot"></li><li class="dot"></li></ol>'
	. '<style>#{{:: shortcode.$id }} { --fbm-ls-cur: {{ ' . $fbm_o . 'columns }}; --fbm-ls-gap: {{ ' . $fbm_o . 'gap }}px; }</style>'
	. '</div>';

$fbm_on_off = array(
	'false' => array( 'title' => __( 'Off', 'flatsome-block-manager' ) ),
	'true'  => array( 'title' => __( 'On', 'flatsome-block-manager' ) ),
);

return array(
	'type'     => 'container',
	'allow'    => array( 'ux_banner', 'ux_image' ),
	'info'     => '{{ shortcode.children.length }} slides',
	'template' => $fbm_template,
	'presets'  => array(
		array(
			'name'    => __( 'Banners (4 / 3 / 2)', 'flatsome-block-manager' ),
			'content' => $fbm_presets['banners'],
		),
		array(
			'name'    => __( 'Single Banner Column', 'flatsome-block-manager' ),
			'content' => $fbm_presets['single'],
		),
	),
	'options'  => array(
		'layout_group'     => array(
			'type'    => 'group',
			'heading' => __( 'Layout', 'flatsome-block-manager' ),
			'options' => array(
				'columns'      => array(
					'type'        => 'slider',
					'heading'     => __( 'Columns', 'flatsome-block-manager' ),
					'description' => __( 'Decimals show part of the next slide, e.g. 1.2 or 2.2.', 'flatsome-block-manager' ),
					'responsive'  => true,
					'default'     => '4',
					'min'         => '1',
					'max'         => '6',
					'step'        => '0.1',
				),
				'gap'          => array(
					'type'       => 'slider',
					'heading'    => __( 'Gap', 'flatsome-block-manager' ),
					'responsive' => true,
					'default'    => '16',
					'unit'       => 'px',
					'min'        => '0',
					'max'        => '50',
					'step'       => '1',
				),
				'step'         => array(
					'type'    => 'slider',
					'heading' => __( 'Slides per Move', 'flatsome-block-manager' ),
					'default' => '1',
					'min'     => '1',
					'max'     => '6',
					'step'    => '1',
				),
				'equal_height' => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Equal Height', 'flatsome-block-manager' ),
					'default' => 'false',
					'options' => $fbm_on_off,
				),
			),
		),
		'navigation_group' => array(
			'type'    => 'group',
			'heading' => __( 'Navigation', 'flatsome-block-manager' ),
			'options' => array(
				'arrows'       => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Arrows', 'flatsome-block-manager' ),
					'default' => 'true',
					'options' => $fbm_on_off,
				),
				'hide_nav'     => array(
					'type'       => 'radio-buttons',
					'heading'    => __( 'Always Visible', 'flatsome-block-manager' ),
					'default'    => '',
					'options'    => array(
						''     => array( 'title' => __( 'Off', 'flatsome-block-manager' ) ),
						'true' => array( 'title' => __( 'On', 'flatsome-block-manager' ) ),
					),
					'conditions' => 'arrows !== "false"',
				),
				'nav_style'    => array(
					'type'       => 'select',
					'heading'    => __( 'Arrow Style', 'flatsome-block-manager' ),
					'default'    => 'circle',
					'options'    => array(
						'circle' => __( 'Circle', 'flatsome-block-manager' ),
						'simple' => __( 'Simple', 'flatsome-block-manager' ),
						'reveal' => __( 'Reveal', 'flatsome-block-manager' ),
					),
					'conditions' => 'arrows !== "false"',
				),
				'nav_color'    => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Arrow & Dot Color', 'flatsome-block-manager' ),
					'default' => 'light',
					'options' => array(
						'dark'  => array( 'title' => __( 'Dark', 'flatsome-block-manager' ) ),
						'light' => array( 'title' => __( 'Light', 'flatsome-block-manager' ) ),
					),
				),
				'bullets'      => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Bullets', 'flatsome-block-manager' ),
					'default' => 'true',
					'options' => $fbm_on_off,
				),
				'bullet_style' => array(
					'type'       => 'select',
					'heading'    => __( 'Bullet Style', 'flatsome-block-manager' ),
					'default'    => 'circle',
					'options'    => array(
						'circle'        => __( 'Circle', 'flatsome-block-manager' ),
						'dashes'        => __( 'Dashes', 'flatsome-block-manager' ),
						'dashes-spaced' => __( 'Dashes (Spaced)', 'flatsome-block-manager' ),
						'simple'        => __( 'Simple', 'flatsome-block-manager' ),
						'square'        => __( 'Square', 'flatsome-block-manager' ),
					),
					'conditions' => 'bullets !== "false"',
				),
			),
		),
		'slide_group'      => array(
			'type'    => 'group',
			'heading' => __( 'Auto Slide', 'flatsome-block-manager' ),
			'options' => array(
				'auto_slide'  => array(
					'type'    => 'radio-buttons',
					'heading' => __( 'Auto Slide', 'flatsome-block-manager' ),
					'default' => 'true',
					'options' => $fbm_on_off,
				),
				'timer'       => array(
					'type'       => 'slider',
					'heading'    => __( 'Timer', 'flatsome-block-manager' ),
					'default'    => '3000',
					'unit'       => 'ms',
					'min'        => '1000',
					'max'        => '10000',
					'step'       => '500',
					'conditions' => 'auto_slide !== "false"',
				),
				'pause_hover' => array(
					'type'       => 'radio-buttons',
					'heading'    => __( 'Pause on Hover / Touch', 'flatsome-block-manager' ),
					'default'    => 'true',
					'options'    => $fbm_on_off,
					'conditions' => 'auto_slide !== "false"',
				),
				'speed'       => array(
					'type'    => 'slider',
					'heading' => __( 'Transition Speed', 'flatsome-block-manager' ),
					'default' => '500',
					'unit'    => 'ms',
					'min'     => '200',
					'max'     => '1500',
					'step'    => '50',
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
