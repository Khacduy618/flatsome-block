<?php
defined( 'ABSPATH' ) || exit;

$fbm_shared = require __DIR__ . '/options.php';

$fbm_pin_group = static function ( $n ) {
	$axis  = static function ( $key, $heading, $var ) use ( $n ) {
		return array(
			'type'      => 'slider',
			'heading'   => $heading,
			'default'   => '50',
			'unit'      => '%',
			'min'       => 0,
			'max'       => 100,
			'step'      => 1,
			'on_change' => array(
				'selector' => '.fbm-stl__pin[data-slot="' . $n . '"]',
				'style'    => $var . ': {{ value }}%',
			),
		);
	};
	$group = array(
		'type'    => 'group',
		/* translators: %d: pin number */
		'heading' => sprintf( __( 'Pin %d', 'flatsome-block-manager' ), $n ),
		'options' => array(
			"pin{$n}_product" => array(
				'type'       => 'select',
				'heading'    => __( 'Sản phẩm', 'flatsome-block-manager' ),
				'full_width' => true,
				'default'    => '',
				'config'     => array(
					'multiple'    => false,
					'placeholder' => __( 'Chọn sản phẩm…', 'flatsome-block-manager' ),
					'postSelect'  => array( 'post_type' => array( 'product' ) ),
				),
			),
			"pin{$n}_x"       => $axis( "pin{$n}_x", __( 'Ngang (X)', 'flatsome-block-manager' ), '--fbm-x' ),
			"pin{$n}_y"       => $axis( "pin{$n}_y", __( 'Dọc (Y)', 'flatsome-block-manager' ), '--fbm-y' ),
		),
	);
	if ( $n > 1 ) {
		// Chỉ hiện khi pin trước đã chọn sản phẩm.
		$group['conditions'] = 'pin' . ( $n - 1 ) . '_product';
	}
	return $group;
};

$fbm_options = array(
	'general_options' => array(
		'type'    => 'group',
		'heading' => __( 'Chung', 'flatsome-block-manager' ),
		'options' => array(
			'title'       => array( 'type' => 'textfield', 'heading' => __( 'Tiêu đề', 'flatsome-block-manager' ), 'default' => '' ),
			'description' => array( 'type' => 'textarea', 'heading' => __( 'Mô tả ngắn', 'flatsome-block-manager' ), 'default' => '' ),
		),
	),
	'image_options'   => array(
		'type'    => 'group',
		'heading' => __( 'Ảnh', 'flatsome-block-manager' ),
		'options' => array(
			'image'      => array( 'type' => 'image', 'heading' => __( 'Ảnh lookbook', 'flatsome-block-manager' ), 'default' => '' ),
			'image_size' => array(
				'type'    => 'select',
				'heading' => __( 'Kích thước ảnh', 'flatsome-block-manager' ),
				'default' => 'large',
				'options' => array( 'medium' => 'Medium', 'medium_large' => 'Medium Large', 'large' => 'Large', 'full' => 'Full' ),
			),
			'ratio'      => array(
				'type'    => 'select',
				'heading' => __( 'Tỉ lệ khung', 'flatsome-block-manager' ),
				'default' => '',
				'options' => array( '' => __( 'Theo ảnh', 'flatsome-block-manager' ) ) + $fbm_shared['ratios'],
			),
		),
	),
	'layout_options'  => array(
		'type'    => 'group',
		'heading' => __( 'Bố cục', 'flatsome-block-manager' ),
		'options' => array(
			'image_side' => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Vị trí ảnh', 'flatsome-block-manager' ),
				'default' => 'left',
				'options' => array(
					'left'  => array( 'title' => __( 'Trái', 'flatsome-block-manager' ) ),
					'right' => array( 'title' => __( 'Phải', 'flatsome-block-manager' ) ),
				),
			),
			'split'      => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Chia cột', 'flatsome-block-manager' ),
				'default' => '50',
				'options' => array_map(
					static function ( $label ) {
						return array( 'title' => $label );
					},
					$fbm_shared['splits']
				),
			),
		),
	),
	'pin_style_options' => array(
		'type'    => 'group',
		'heading' => __( 'Kiểu pin', 'flatsome-block-manager' ),
		'options' => array(
			'pin_style' => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Kiểu', 'flatsome-block-manager' ),
				'default' => 'dot',
				'options' => array(
					'dot'     => array( 'title' => __( 'Chấm', 'flatsome-block-manager' ) ),
					'numbers' => array( 'title' => __( 'Số thứ tự', 'flatsome-block-manager' ) ),
				),
			),
			'pulse'     => array( 'type' => 'checkbox', 'heading' => __( 'Vòng lan toả', 'flatsome-block-manager' ), 'default' => 'true' ),
			'pin_tone'  => array(
				'type'    => 'radio-buttons',
				'heading' => __( 'Tông', 'flatsome-block-manager' ),
				'default' => 'light',
				'options' => array(
					'light' => array( 'title' => __( 'Sáng', 'flatsome-block-manager' ) ),
					'dark'  => array( 'title' => __( 'Tối', 'flatsome-block-manager' ) ),
				),
			),
		),
	),
	'card_options'    => array(
		'type'    => 'group',
		'heading' => __( 'Thẻ sản phẩm', 'flatsome-block-manager' ),
		'options' => array(
			'card_image'  => array( 'type' => 'checkbox', 'heading' => __( 'Hiện ảnh sản phẩm', 'flatsome-block-manager' ), 'default' => 'true' ),
			'button_text' => array( 'type' => 'textfield', 'heading' => __( 'Chữ trên nút', 'flatsome-block-manager' ), 'default' => __( 'Xem sản phẩm', 'flatsome-block-manager' ) ),
		),
	),
);

for ( $fbm_n = 1; $fbm_n <= 8; $fbm_n++ ) {
	$fbm_options[ "pin{$fbm_n}_options" ] = $fbm_pin_group( $fbm_n );
}
$fbm_options['advanced_options'] = require $commons . 'advanced.php';

return array(
	'wrap'    => false, // Như ux_image: on_change áp lên chính element; output có đúng 1 phần tử gốc.
	'info'    => '{{ title }}',
	'options' => $fbm_options,
);
