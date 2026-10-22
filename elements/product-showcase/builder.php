<?php
defined( 'ABSPATH' ) || exit;

// Slider: dùng lại đúng element Slider (ux_slider) của Flatsome — nhóm option (Type Fade/Slide, Auto Slide, Timer,
// Pause on Hover, Navigation), template canvas, directive, tools. Flatsome đăng ký ux_slider ở ux_builder_setup priority 10.
$fbm_ux_slider = function_exists( 'ux_builder' ) && class_exists( 'UxBuilder\\Application', false ) ? ux_builder( 'elements' )->get( 'ux_slider' ) : false;
/** Cho phép thay định nghĩa (test chạy ngoài UX Builder). */
$fbm_ux_slider     = apply_filters( 'fbm/product_showcase/ux_slider_definition', $fbm_ux_slider );
$fbm_slider_groups = array();
if ( is_array( $fbm_ux_slider ) && ! empty( $fbm_ux_slider['options'] ) ) {
	$fbm_slider_groups = $fbm_ux_slider['options'];
	unset( $fbm_slider_groups['label'], $fbm_slider_groups['advanced_options'] );
	// Mặc định riêng của element (render.php dùng cùng giá trị): Fade, 5 giây, nút tối trên nền sáng.
	$fbm_defaults = require __DIR__ . '/slider-defaults.php';
	if ( isset( $fbm_slider_groups['type'] ) ) {
		$fbm_slider_groups['type']['default'] = $fbm_defaults['type'];
	}
	foreach ( $fbm_slider_groups as $fbm_key => $fbm_group ) {
		if ( isset( $fbm_group['options'] ) && is_array( $fbm_group['options'] ) ) {
			foreach ( array_intersect_key( $fbm_defaults, $fbm_group['options'] ) as $fbm_opt => $fbm_value ) {
				$fbm_slider_groups[ $fbm_key ]['options'][ $fbm_opt ]['default'] = $fbm_value;
			}
		}
	}
}
$fbm_slider_tpl = is_array( $fbm_ux_slider ) && ! empty( $fbm_ux_slider['template'] ) ? (string) $fbm_ux_slider['template'] : '<content></content>';
$fbm_o          = 'shortcode.options.';

$fbm_options = array(
	'general_options'  => array(
		'type'    => 'group',
		'heading' => __( 'General', 'flatsome-block-manager' ),
		'options' => array(
			'title'       => array( 'type' => 'textfield', 'heading' => __( 'Title', 'flatsome-block-manager' ), 'default' => '' ),
			'description' => array( 'type' => 'textarea', 'heading' => __( 'Description', 'flatsome-block-manager' ), 'default' => '' ),
		),
	),
	// Khoá khác "layout_options" vì nhóm Layout của ux_slider đã dùng khoá đó.
	'showcase_options' => array(
		'type'    => 'group',
		'heading' => __( 'Showcase style', 'flatsome-block-manager' ),
		'options' => array(
			'theme'        => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Theme', 'flatsome-block-manager' ),
				'default' => 'light',
				'options' => array(
					'light' => array( 'title' => __( 'Light', 'flatsome-block-manager' ) ),
					'dark'  => array( 'title' => __( 'Dark', 'flatsome-block-manager' ) ),
				),
			),
			'speed'        => array(
				'type'    => 'slider',
				'heading' => __( 'Transition speed', 'flatsome-block-manager' ),
				'default' => '600',
				'unit'    => 'ms',
				'min'     => 200,
				'max'     => 1500,
				'step'    => 50,
			),
			'image_height' => array(
				'type'    => 'select',
				'heading' => __( 'Image ratio', 'flatsome-block-manager' ),
				'default' => '100%',
				'options' => require __DIR__ . '/heights.php',
			),
			'image_size'   => array(
				'type'    => 'select',
				'heading' => __( 'Image size', 'flatsome-block-manager' ),
				'default' => 'large',
				'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
			),
		),
	),
);
$fbm_options                     = $fbm_options + $fbm_slider_groups;
$fbm_options['advanced_options'] = require $commons . 'advanced.php';

$fbm_args = array(
	'type'     => 'container',
	'allow'    => array( 'fbm_showcase_slide' ),
	'wrap'     => false, // Một phần tử gốc section.fbm-ps; slider của Flatsome nằm bên trong.
	'info'     => '{{ shortcode.children.length }} slide',
	'template' => '<section class="fbm-ps relative {{ ' . $fbm_o . 'theme == \'dark\' ? \'dark bg-primary-color pt pb\' : \'\' }}"' // Class / Visibility: template ux_slider bên trong đã gắn.
		. ' style="--fbm-ps-speed: {{ ' . $fbm_o . 'speed || 600 }}ms" aria-label="{{ ' . $fbm_o . 'title || \'Product Showcase\' }}">'
		. '<div class="text-center" ng-if="' . $fbm_o . 'title || ' . $fbm_o . 'description">'
		. '<div ng-if="' . $fbm_o . 'title" class="container section-title-container"><h2 class="section-title section-title-center"><b aria-hidden="true"></b><span class="section-title-main">{{ ' . $fbm_o . 'title }}</span><b aria-hidden="true"></b></h2></div>'
		. '<p ng-if="' . $fbm_o . 'description">{{ ' . $fbm_o . 'description }}</p>'
		. '</div>'
		. $fbm_slider_tpl
		. '</section>',
	'presets'  => array(
		array(
			'name'    => __( '2 slides', 'flatsome-block-manager' ),
			'content' => '[fbm_product_showcase][fbm_showcase_slide][fbm_showcase_slide][/fbm_product_showcase]',
		),
		array(
			'name'    => __( '3 slides, dark', 'flatsome-block-manager' ),
			'content' => '[fbm_product_showcase theme="dark" nav_color="light"][fbm_showcase_slide][fbm_showcase_slide][fbm_showcase_slide][/fbm_product_showcase]',
		),
	),
	'options'  => $fbm_options,
);
// Hành vi canvas của element Slider: directive khởi tạo Flickity an toàn với UX Builder, chọn slide trên thanh công cụ, thêm slide hai bên.
if ( is_array( $fbm_ux_slider ) ) {
	foreach ( array( 'directives', 'tools', 'toolbar', 'children', 'shortcode_controller', 'tools_controller' ) as $fbm_key ) {
		if ( ! empty( $fbm_ux_slider[ $fbm_key ] ) ) {
			$fbm_args[ $fbm_key ] = $fbm_ux_slider[ $fbm_key ];
		}
	}
}
return $fbm_args;
