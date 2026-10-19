<?php
/**
 * Danh sách dùng chung cho builder.php, render.php và test CSS của Shop The Look.
 * Mỗi khoá tỉ lệ "rộng-cao" cần một dòng `.fbm-stl--r-<khoá> .fbm-stl__media { aspect-ratio: rộng / cao; }` trong shop-look.css.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'ratios' => array(
		'1-1'  => '1:1',
		'4-5'  => '4:5',
		'3-4'  => '3:4',
		'2-3'  => '2:3',
		'3-2'  => '3:2',
		'16-9' => '16:9',
	),
	'splits' => array(
		'50' => '50/50',
		'60' => '60/40',
		'66' => '66/33',
	),
);
