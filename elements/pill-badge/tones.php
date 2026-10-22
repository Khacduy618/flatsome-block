<?php
/**
 * Màu nhấn dùng chung cho Pill Badge: khoá => nhãn. Mỗi khoá ánh xạ sang một token của theme/HANDDN
 * trong pill-badge.css (`.fbm-pill--<khoá>`), không có ô chọn màu tự do (spec mục 4.2).
 */
defined( 'ABSPATH' ) || exit;

return array(
	'primary' => __( 'Màu chính', 'flatsome-block-manager' ),
	'success' => __( 'Thành công', 'flatsome-block-manager' ),
	'alert'   => __( 'Cảnh báo', 'flatsome-block-manager' ),
	'muted'   => __( 'Trung tính', 'flatsome-block-manager' ),
);
