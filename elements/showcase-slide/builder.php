<?php
defined( 'ABSPATH' ) || exit;

$fbm_advanced = require $commons . 'advanced.php';
unset( $fbm_advanced['options']['visibility'] ); // Slide ẩn theo màn hình sẽ để lại một lượt trống trong slider.

$fbm_o = 'shortcode.options.';

// Một cột của slide trong canvas, cùng markup với render.php. wp-attachment là directive ảnh của UX Builder (như ux_image)
// nên chọn ảnh là thấy ngay; temp-image là ô "chưa có ảnh" của canvas Flatsome.
$fbm_panel = static function ( $side, $num, $key, $border, $empty ) use ( $fbm_o ) {
	return '<figure class="col medium-5 small-12 large-5"><div class="col-inner">'
		. '<p class="uppercase mb-half"><strong>' . $num . '.</strong> <span ng-if="' . $fbm_o . $key . 'Title">{{ ' . $fbm_o . $key . 'Title }}</span></p>'
		. '<div class="fbm-ps__frame fbm-ps__frame--' . $side . ' img-inner image-cover" style="padding-top:{{ shortcode.parent.options.imageHeight || \'100%\' }}">'
		. '<img draggable="false" ng-if="' . $fbm_o . $key . 'Image" wp-attachment="' . $fbm_o . $key . 'Image" wp-attachment-size="shortcode.parent.options.imageSize || \'large\'">'
		. '<div class="temp-image fill" ng-if="!' . $fbm_o . $key . 'Image">' . esc_html( $empty ) . '</div>'
		. '<div class="' . $border . '"></div>'
		. '</div>'
		. '<figcaption class="is-small op-7 text-center pt-half" ng-if="' . $fbm_o . $key . 'Label">{{ ' . $fbm_o . $key . 'Label }}</figcaption>'
		. '</div></figure>';
};

$fbm_side = static function ( $key, $heading, $title ) {
	return array(
		'type'    => 'group',
		'heading' => $heading,
		'options' => array(
			$key . '_title' => array( 'type' => 'textfield', 'heading' => __( 'Step title', 'flatsome-block-manager' ), 'default' => $title ),
			$key . '_image' => array( 'type' => 'image', 'heading' => __( 'Image', 'flatsome-block-manager' ), 'default' => '' ),
			$key . '_label' => array( 'type' => 'textfield', 'heading' => __( 'Caption', 'flatsome-block-manager' ), 'default' => '' ),
		),
	);
};

return array(
	'require'  => array( 'fbm_product_showcase' ),
	'allow_in' => array( 'fbm_product_showcase' ),
	'wrap'     => false, // Output có đúng 1 phần tử gốc: div.fbm-ps__slide.
	'info'     => '{{ left_label || right_label }}',
	'template' => '<div class="fbm-ps__slide pb {{ ' . $fbm_o . 'class }}" role="group" aria-roledescription="slide" aria-label="{{ shortcode.index + 1 }} / {{ shortcode.parent.children.length }}"><div class="row row-small align-middle align-center">'
		. $fbm_panel( 'input', 1, 'left', 'is-border is-dashed', __( 'Select product image…', 'flatsome-block-manager' ) )
		. '<div class="col medium-2 small-12 large-2 text-center"><div class="col-inner"><span class="button icon circle is-outline mb-0" aria-hidden="true"><i class="icon-angle-right hide-for-small"></i><i class="icon-angle-down show-for-small"></i></span></div></div>'
		. $fbm_panel( 'output', 2, 'right', 'is-border', __( 'Select generated image…', 'flatsome-block-manager' ) )
		. '</div></div>',
	'options'  => array(
		'input_options'    => $fbm_side( 'left', __( 'Left: original image', 'flatsome-block-manager' ), __( 'Place your product here', 'flatsome-block-manager' ) ),
		'output_options'   => $fbm_side( 'right', __( 'Right: generated image', 'flatsome-block-manager' ), __( 'Generated images will appear here', 'flatsome-block-manager' ) ),
		'advanced_options' => $fbm_advanced,
	),
);
