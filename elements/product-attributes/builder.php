<?php
defined( 'ABSPATH' ) || exit;

return array(
	'options' => array(
		'id'               => array(
			'type'        => 'select',
			'heading'     => __( 'Sản phẩm', 'flatsome-block-manager' ),
			'description' => __( 'Để trống = sản phẩm hiện tại (trang sản phẩm, Custom Product Layout).', 'flatsome-block-manager' ),
			'full_width'  => true,
			'config'      => array(
				'multiple'    => false,
				'placeholder' => __( 'Sản phẩm hiện tại', 'flatsome-block-manager' ),
				'postSelect'  => array( 'post_type' => array( 'product' ) ),
			),
		),
		'advanced_options' => require $commons . 'advanced.php',
	),
);
