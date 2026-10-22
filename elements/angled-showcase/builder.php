<?php
defined( 'ABSPATH' ) || exit;

return array(
	'type'      => 'container',
	'name'      => __( 'Angled Showcase', 'flatsome-block-manager' ),
	'category'  => __( 'Content', 'flatsome' ),
	'allow'     => array( 'fbm_angled_card' ),
	'info'      => '{{ shortcode.children.length }} cards',
	'template'  => '<div id="fbm-sc-{{:: shortcode.$id }}" class="fbm-angled-showcase"><div class="fbm-angled-showcase__grid" style="--fbm-showcase-cols: {{ shortcode.options.columns || 3 }}; --fbm-showcase-gap: {{ shortcode.options.colGap || 30 }}px;"><content></content></div></div>',
	'presets'   => array(
		array(
			'name'    => __( 'Default (3 Cards)', 'flatsome-block-manager' ),
			'content' => '[fbm_angled_showcase columns="3" columns__sm="1" aspect_ratio="3/4" cut_x="55" cut_y="120" chamfer="16"][fbm_angled_card tag="01" title="Motivational Speech" image="24"][/fbm_angled_card][fbm_angled_card tag="02" title="Motivational Interview" image="26"][/fbm_angled_card][fbm_angled_card tag="03" title="Pretty Interview NewYork" image="30"][/fbm_angled_card][/fbm_angled_showcase]',
		),
	),
	'options'   => array(
		'layout_options'   => array(
			'type'    => 'group',
			'heading' => __( 'Layout & Grid', 'flatsome-block-manager' ),
			'options' => array(
				'columns' => array(
					'type'       => 'slider',
					'heading'    => __( 'Columns', 'flatsome' ),
					'default'    => 3,
					'min'        => 1,
					'max'        => 4,
					'step'       => 1,
					'responsive' => true,
				),
				'col_gap' => array(
					'type'       => 'slider',
					'heading'    => __( 'Column Spacing (px)', 'flatsome-block-manager' ),
					'default'    => 30,
					'unit'       => 'px',
					'min'        => 0,
					'max'        => 60,
					'step'       => 5,
					'responsive' => true,
				),
			),
		),
		'card_options'     => array(
			'type'    => 'group',
			'heading' => __( 'Cards Bulk Style', 'flatsome-block-manager' ),
			'options' => array(
				'aspect_ratio' => array(
					'type'    => 'select',
					'heading' => __( 'Image Aspect Ratio', 'flatsome-block-manager' ),
					'default' => '3/4',
					'options' => array(
						'3/4' => '3:4 (Portrait)',
						'4/5' => '4:5 (Standard)',
						'1/1' => '1:1 (Square)',
						'2/3' => '2:3 (Tall)',
					),
				),
				'cut_x'        => array(
					'type'       => 'slider',
					'heading'    => __( 'Bottom Cut Width (%)', 'flatsome-block-manager' ),
					'default'    => 55,
					'unit'       => '%',
					'min'        => 35,
					'max'        => 80,
					'step'       => 1,
					'responsive' => true,
				),
				'cut_y'        => array(
					'type'       => 'slider',
					'heading'    => __( 'Left Cut Height (px)', 'flatsome-block-manager' ),
					'default'    => 120,
					'unit'       => 'px',
					'min'        => 60,
					'max'        => 220,
					'step'       => 5,
					'responsive' => true,
				),
				'chamfer'      => array(
					'type'    => 'slider',
					'heading' => __( 'Bottom-Right Chamfer (px)', 'flatsome-block-manager' ),
					'default' => 16,
					'unit'    => 'px',
					'min'     => 0,
					'max'     => 40,
					'step'    => 2,
				),
				'hover_zoom'   => array(
					'type'    => 'checkbox',
					'heading' => __( 'Hover Zoom Effect', 'flatsome-block-manager' ),
					'default' => 'true',
				),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
