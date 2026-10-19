<?php
defined( 'ABSPATH' ) || exit;

$fbm_shared = require dirname( __DIR__ ) . '/look/options.php';

// Slider: dùng lại đúng element Slider (ux_slider) của Flatsome — các nhóm option, template canvas, directive
// và tools (UX Builder chỉnh được từng slide). Flatsome đăng ký ux_slider ở ux_builder_setup priority 10, trước Registry (20).
$fbm_ux_slider = function_exists( 'ux_builder' ) && class_exists( 'UxBuilder\\Application', false ) ? ux_builder( 'elements' )->get( 'ux_slider' ) : false;
/** Cho phép thay định nghĩa (test chạy ngoài UX Builder). */
$fbm_ux_slider     = apply_filters( 'fbm/shop_look/ux_slider_definition', $fbm_ux_slider );
$fbm_slider_groups = array();
if ( is_array( $fbm_ux_slider ) && ! empty( $fbm_ux_slider['options'] ) ) {
	$fbm_slider_groups = $fbm_ux_slider['options'];
	unset( $fbm_slider_groups['label'], $fbm_slider_groups['advanced_options'] );
}
$fbm_slider_tpl = is_array( $fbm_ux_slider ) && ! empty( $fbm_ux_slider['template'] ) ? (string) $fbm_ux_slider['template'] : '<content></content>';

$fbm_options = array(
	'general_options'     => array(
		'type'    => 'group',
		'heading' => __( 'General', 'flatsome-block-manager' ),
		'options' => array(
			'title'       => array( 'type' => 'textfield', 'heading' => __( 'Title', 'flatsome-block-manager' ), 'default' => '' ),
			'description' => array( 'type' => 'textarea', 'heading' => __( 'Short description', 'flatsome-block-manager' ), 'default' => '' ),
			'text_align'  => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Text align', 'flatsome-block-manager' ),
				'default' => 'left',
				'options' => require dirname( $commons ) . '/values/align-radios.php',
			),
			'gap'         => array(
				'type'       => 'slider',
				'heading'    => __( 'Spacing (title, description, looks)', 'flatsome-block-manager' ),
				'responsive' => true,
				'default'    => '20',
				'unit'       => 'px',
				'min'        => 0,
				'max'        => 100,
			),
		),
	),
	// Khoá khác "layout_options" vì nhóm Layout của ux_slider đã dùng khoá đó.
	'look_layout_options' => array(
		'type'    => 'group',
		'heading' => __( 'Look layout', 'flatsome-block-manager' ),
		'options' => array(
			'image_side' => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Image position', 'flatsome-block-manager' ),
				'default' => 'left',
				'options' => array(
					'left'  => array( 'title' => __( 'Left', 'flatsome-block-manager' ) ),
					'right' => array( 'title' => __( 'Right', 'flatsome-block-manager' ) ),
				),
			),
			'split'      => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Columns', 'flatsome-block-manager' ),
				'default' => '1-1',
				'options' => array_map(
					static function ( $label ) {
						return array( 'title' => $label );
					},
					$fbm_shared['splits']
				),
			),
			// Khung chung cho mọi Look: rộng theo % cột, cao cố định (ảnh phủ kín, cắt từ giữa); chỉnh riêng từng màn hình.
			'width'      => array(
				'type'       => 'slider',
				'heading'    => __( 'Frame width', 'flatsome-block-manager' ),
				'responsive' => true,
				'default'    => '100',
				'unit'       => '%',
				'min'        => 30,
				'max'        => 100,
			),
			'height'     => array(
				'type'        => 'scrubfield',
				'heading'     => __( 'Frame height', 'flatsome-block-manager' ),
				'responsive'  => true,
				'default'     => '',
				'placeholder' => __( 'Auto', 'flatsome-block-manager' ),
				'min'         => 0,
				'max'         => 1200,
				'step'        => 1,
			),
		),
	),
	'image_options'       => array(
		'type'    => 'group',
		'heading' => __( 'Image style', 'flatsome-block-manager' ),
		'options' => array(
			'image_size' => array(
				'type'    => 'select',
				'heading' => __( 'Image size', 'flatsome-block-manager' ),
				'default' => 'large',
				'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
			),
			'ratio'      => array(
				'type'    => 'select',
				'heading' => __( 'Frame ratio', 'flatsome-block-manager' ),
				'default' => '',
				'options' => array( '' => __( 'Original', 'flatsome-block-manager' ) ) + $fbm_shared['ratios'],
			),
		),
	),
	'pin_style_options'   => array(
		'type'    => 'group',
		'heading' => __( 'Pin style', 'flatsome-block-manager' ),
		'options' => array(
			'pin_style' => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Style', 'flatsome-block-manager' ),
				'default' => 'dot',
				'options' => array(
					'dot'     => array( 'title' => __( 'Dot', 'flatsome-block-manager' ) ),
					'numbers' => array( 'title' => __( 'Number', 'flatsome-block-manager' ) ),
				),
			),
			'pulse'     => array( 'type' => 'checkbox', 'heading' => __( 'Pulse', 'flatsome-block-manager' ), 'default' => 'true' ),
			'pin_tone'  => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Tone', 'flatsome-block-manager' ),
				'default' => 'light',
				'options' => array(
					'light' => array( 'title' => __( 'Light', 'flatsome-block-manager' ) ),
					'dark'  => array( 'title' => __( 'Dark', 'flatsome-block-manager' ) ),
				),
			),
		),
	),
	'card_options'        => array(
		'type'    => 'group',
		'heading' => __( 'Product card', 'flatsome-block-manager' ),
		'options' => array(
			'button_text' => array( 'type' => 'textfield', 'heading' => __( 'Button text', 'flatsome-block-manager' ), 'default' => __( 'View product', 'flatsome-block-manager' ) ),
		),
	),
);
$fbm_options                     = $fbm_options + $fbm_slider_groups;
$fbm_options['advanced_options'] = require $commons . 'advanced.php';

$fbm_args = array(
	'type'     => 'container',
	'allow'    => array( 'fbm_look' ),
	'info'     => '{{ shortcode.children.length }} look',
	'template' => '<div id="fbm-sl-{{:: shortcode.$id }}" class="fbm-shop-look {{ shortcode.options.class }}"'
		. ' data-button-text="{{ shortcode.options.buttonText }}"'
		. ' ng-class="{\'fbm-shop-look--right\': shortcode.options.imageSide == \'right\','
		. ' \'fbm-shop-look--split-3-2\': shortcode.options.split == \'3-2\', \'fbm-shop-look--split-2-1\': shortcode.options.split == \'2-1\', \'fbm-shop-look--fixed-height\': shortcode.options.height,'
		. ' \'fbm-shop-look--numbers\': shortcode.options.pinStyle == \'numbers\', \'fbm-shop-look--pulse\': shortcode.options.pulse == \'true\','
		. ' \'fbm-shop-look--tone-dark\': shortcode.options.pinTone == \'dark\','
		. ' \'fbm-shop-look--ratio\': shortcode.options.ratio, [\'fbm-shop-look--r-\' + shortcode.options.ratio]: shortcode.options.ratio}">'
		. '<div class="fbm-shop-look__head text-{{ shortcode.options.textAlign || \'left\' }}">'
		. '<div ng-if="shortcode.options.title" class="container section-title-container"><h3 class="section-title section-title-{{ shortcode.options.textAlign == \'center\' ? \'center\' : \'normal\' }}"><b></b><span class="section-title-main">{{ shortcode.options.title }}</span><b></b></h3></div>'
		. '<p ng-if="shortcode.options.description" class="fbm-shop-look__desc">{{ shortcode.options.description }}</p>'
		. '</div>'
		. $fbm_slider_tpl
		. '<style>#fbm-sl-{{:: shortcode.$id }} { width: {{ shortcode.options.width }}%; } #fbm-sl-{{:: shortcode.$id }} .fbm-stl__media { height: {{ shortcode.options.height }}; } #fbm-sl-{{:: shortcode.$id }} .fbm-shop-look__head { gap: {{ shortcode.options.gap }}px; margin-bottom: {{ shortcode.options.gap }}px; }</style>'
		. '</div>',
	'presets'  => array(
		array(
			'name'    => __( '1 look', 'flatsome-block-manager' ),
			'content' => '[fbm_shop_look][fbm_look][/fbm_shop_look]',
		),
		array(
			'name'    => __( '2 looks (slider)', 'flatsome-block-manager' ),
			'content' => '[fbm_shop_look][fbm_look][fbm_look][/fbm_shop_look]',
		),
	),
	'options'  => $fbm_options,
);
// Hành vi canvas của element Slider: directive khởi tạo Flickity an toàn với UX Builder, tools chuyển slide, thêm slide hai bên.
if ( is_array( $fbm_ux_slider ) ) {
	foreach ( array( 'directives', 'tools', 'toolbar', 'children', 'shortcode_controller', 'tools_controller' ) as $fbm_key ) {
		if ( ! empty( $fbm_ux_slider[ $fbm_key ] ) ) {
			$fbm_args[ $fbm_key ] = $fbm_ux_slider[ $fbm_key ];
		}
	}
}
return $fbm_args;
