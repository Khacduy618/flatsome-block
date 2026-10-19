<?php
defined( 'ABSPATH' ) || exit;

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
				'heading'    => __( 'Product', 'flatsome-block-manager' ),
				'full_width' => true,
				'default'    => '',
				'config'     => array(
					'multiple'    => false,
					'placeholder' => __( 'Select product…', 'flatsome-block-manager' ),
					'postSelect'  => array( 'post_type' => array( 'product' ) ),
				),
			),
			"pin{$n}_x"       => $axis( "pin{$n}_x", __( 'Horizontal (X)', 'flatsome-block-manager' ), '--fbm-x' ),
			"pin{$n}_y"       => $axis( "pin{$n}_y", __( 'Vertical (Y)', 'flatsome-block-manager' ), '--fbm-y' ),
		),
	);
	if ( $n > 1 ) {
		// Chỉ hiện khi pin trước đã chọn sản phẩm.
		$group['conditions'] = 'pin' . ( $n - 1 ) . '_product';
	}
	return $group;
};

$fbm_options = array(
	'image_options'   => array(
		'type'    => 'group',
		'heading' => __( 'Image', 'flatsome-block-manager' ),
		'options' => array(
			'image' => array( 'type' => 'image', 'heading' => __( 'Lookbook image', 'flatsome-block-manager' ), 'default' => '' ),
		),
	),
);

for ( $fbm_n = 1; $fbm_n <= 8; $fbm_n++ ) {
	$fbm_options[ "pin{$fbm_n}_options" ] = $fbm_pin_group( $fbm_n );
}
$fbm_options['advanced_options'] = require $commons . 'advanced.php';

return array(
	'require'  => array( 'fbm_shop_look' ),
	'allow_in' => array( 'fbm_shop_look' ),
	'wrap'    => false, // Như ux_image: on_change áp lên chính element; output có đúng 1 phần tử gốc.
	'info'    => '{{ title }}',
	'options' => $fbm_options,
);
