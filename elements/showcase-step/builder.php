<?php
defined( 'ABSPATH' ) || exit;

$fbm_advanced = require $commons . 'advanced.php';
unset( $fbm_advanced['options']['visibility'] ); // Bước bị ẩn theo màn hình sẽ để lại một lượt trống trong vòng lặp.

$fbm_o = 'shortcode.options.';

// Bảng tra tỉ lệ cho template canvas: {'1-1': 1, '4-3': 0.75, …}[tỉ lệ của element cha].
$fbm_map = array();
foreach ( require dirname( __DIR__ ) . '/animated-showcase/ratios.php' as $fbm_key => $fbm_r ) {
	$fbm_map[] = '\'' . $fbm_key . '\': ' . $fbm_r['value'];
}

$fbm_img = static function ( $key, $slot ) use ( $fbm_o ) {
	return '<img class="fbm-as__img fbm-as__img--' . $slot . '" draggable="false" ng-if="' . $fbm_o . $key . 'Image" wp-attachment="' . $fbm_o . $key . 'Image" wp-attachment-size="shortcode.parent.options.imageSize || \'large\'">';
};

// Cùng markup với render.php. Chữ tách từng ký tự bằng ng-repeat để hiệu ứng gõ chữ chạy được cả trong canvas;
// temp-image là ô "chưa có ảnh" của canvas Flatsome.
$fbm_template = '<div class="fbm-as__item {{ ' . $fbm_o . 'class }}" ng-class="{\'is-current\': shortcode.index === 0}">'
	. '<div class="fbm-as__stage" style="--fbm-as-ratio: {{ {' . implode( ', ', $fbm_map ) . '}[shortcode.parent.options.imageRatio] || 1 }}">'
	. '<div class="fbm-as__part fbm-as__part--left">'
	. $fbm_img( 'left', 'left' )
	. '<div class="temp-image fill" ng-if="!' . $fbm_o . 'leftImage">' . esc_html__( 'Select left image…', 'flatsome-block-manager' ) . '</div>'
	. '</div>'
	. '<div class="fbm-as__part fbm-as__part--right">'
	. '<p class="fbm-as__text" ng-if="' . $fbm_o . 'textContent"><span class="screen-reader-text">{{ ' . $fbm_o . 'textContent }}</span>'
	. '<span class="fbm-as__chars" aria-hidden="true"><span class="fbm-as__char" style="--i:{{ $index }}" ng-repeat="c in ' . $fbm_o . 'textContent.split(\'\') track by $index">{{ c }}</span></span></p>'
	. $fbm_img( 'right', 'right' )
	. $fbm_img( 'third', 'third' )
	. str_replace( '<img ', '<img alt="" aria-hidden="true" ', $fbm_img( 'left', 'again' ) )
	. '<div class="temp-image fill" ng-if="!' . $fbm_o . 'rightImage || !' . $fbm_o . 'thirdImage">' . esc_html__( 'Select right and third images…', 'flatsome-block-manager' ) . '</div>'
	. '</div>'
	. '</div>'
	. '</div>';

return array(
	'require'  => array( 'fbm_animated_showcase' ),
	'allow_in' => array( 'fbm_animated_showcase' ),
	'wrap'     => false, // Output có đúng 1 phần tử gốc: div.fbm-as__item.
	'info'     => '{{ text_content }}',
	'template' => $fbm_template,
	'options'  => array(
		'content_options'  => array(
			'type'    => 'group',
			'heading' => __( 'Content', 'flatsome-block-manager' ),
			'options' => array(
				'left_image'   => array( 'type' => 'image', 'heading' => __( 'Left image (slides in first)', 'flatsome-block-manager' ), 'default' => '' ),
				'text_content' => array( 'type' => 'textarea', 'heading' => __( 'Text (typed letter by letter)', 'flatsome-block-manager' ), 'default' => '' ),
				'right_image'  => array( 'type' => 'image', 'heading' => __( 'Right image (replaces the text)', 'flatsome-block-manager' ), 'default' => '' ),
				'third_image'  => array( 'type' => 'image', 'heading' => __( 'Third image (slides in from the right)', 'flatsome-block-manager' ), 'default' => '' ),
			),
		),
		'advanced_options' => $fbm_advanced,
	),
);
