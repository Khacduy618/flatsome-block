<?php
/**
 * Tỉ lệ khung của mỗi phần (trái / phải): khoá → nhãn + chiều cao / chiều rộng. Khoá không phải số (UX Builder so sánh
 * bằng ===). Dùng chung cho builder.php (nhãn, bảng tra trong template canvas) và render.php của bước.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'1-1'  => array( 'label' => '1:1', 'value' => '1' ),
	'4-5'  => array( 'label' => '4:5', 'value' => '1.25' ),
	'3-4'  => array( 'label' => '3:4', 'value' => '1.3333' ),
	'4-3'  => array( 'label' => '4:3', 'value' => '0.75' ),
	'16-9' => array( 'label' => '16:9', 'value' => '0.5625' ),
);
