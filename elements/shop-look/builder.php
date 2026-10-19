<?php
defined( 'ABSPATH' ) || exit;

return array(
	'type'     => 'container',
	'allow'    => array( 'fbm_look' ),
	'info'     => '{{ shortcode.children.length }} look',
	// Canvas: các Look xếp dọc để dễ chỉnh; ngoài site từ 2 Look là slider.
	'template' => '<div class="fbm-shop-look {{ shortcode.options.class }}"><content></content></div>',
	'presets'  => array(
		array(
			'name'    => __( '1 look', 'flatsome-block-manager' ),
			'content' => '[fbm_shop_look][fbm_look][/fbm_shop_look]',
		),
		array(
			'name'    => __( '2 look (slider)', 'flatsome-block-manager' ),
			'content' => '[fbm_shop_look][fbm_look][fbm_look][/fbm_shop_look]',
		),
	),
	'options'  => array(
		'slider_options'   => require $commons . 'repeater-slider.php',
		'advanced_options' => require $commons . 'advanced.php',
	),
);
