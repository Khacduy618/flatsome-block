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
	// Khoá không phải số: UX Builder so sánh $ctrl.model === value, giá trị số 60 sẽ không khớp khoá chuỗi "60".
	'splits' => array(
		'1-1' => '50/50',
		'3-2' => '60/40',
		'2-1' => '66/33',
	),
);
