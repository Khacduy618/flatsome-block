<?php
defined( 'ABSPATH' ) || exit;

return array(
	'type'     => 'container',
	'allow'    => array( 'fbm_before_after' ),
	'info'     => '{{ columns }} / khung',
	'template' => '<div class="row fbm-bac {{ shortcode.options.class }}"><content></content></div>',
	'presets'  => array(
		array(
			'name'    => __( '2 so sánh', 'flatsome-block-manager' ),
			'content' => '[fbm_ba_carousel][fbm_before_after][fbm_before_after][/fbm_ba_carousel]',
		),
	),
	'options'  => array(
		'columns'          => array(
			'type'       => 'slider',
			'heading'    => __( 'Số khung mỗi lượt', 'flatsome-block-manager' ),
			'responsive' => true,
			'default'    => '1',
			'min'        => 1,
			'max'        => 4,
		),
		'slider_options'   => require $commons . 'repeater-slider.php',
		'advanced_options' => require $commons . 'advanced.php',
	),
);
