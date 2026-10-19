<?php
/**
 * Tỉ lệ khung của Before / After: khoá "rộng-cao" => nhãn. Dùng chung cho builder.php, render.php và test CSS.
 * Mỗi khoá cần một dòng `.fbm-ba--r-<khoá> .fbm-ba__stage { aspect-ratio: rộng / cao; }` trong before-after.css.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'1-1'  => '1:1',
	'4-3'  => '4:3',
	'3-4'  => '3:4',
	'3-2'  => '3:2',
	'2-3'  => '2:3',
	'16-9' => '16:9',
	'9-16' => '9:16',
	'21-9' => '21:9',
);
