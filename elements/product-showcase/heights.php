<?php
/**
 * Chiều cao khung ảnh theo % chiều rộng, như option "Height" của element Image (image_height): khung là .image-cover
 * của Flatsome với padding-top = giá trị. Dùng chung cho builder.php và render.php.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'100%'    => '1:1',
	'125%'    => '4:5',
	'133.33%' => '3:4',
	'75%'     => '4:3',
	'56.25%'  => '16:9',
);
