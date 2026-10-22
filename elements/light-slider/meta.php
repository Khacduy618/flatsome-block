<?php
/**
 * Light Slider Element Metadata
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

return array(
	'tag'       => 'fbm_light_slider',
	'name'      => __( 'Light Slider', 'flatsome-block-manager' ),
	'category'  => __( 'Layout', 'flatsome-block-manager' ),
	'container' => true,
	'css'       => 'light-slider.css',
	'js'        => 'view.js',
	'aliases'   => array( 'fbm_light_carousel' ), // Previous name; saved content keeps rendering.
);
