<?php
/**
 * Widget Judge.me được phép bọc (đọc từ plugin judgeme-product-reviews-woocommerce 1.3.29, class-jgm-widget.php).
 * shortcode => [ nhãn, thuộc tính nhận ]. Không có ô nhập shortcode tự do.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'jgm-featured-carousel' => array( __( 'Carousel đánh giá nổi bật', 'flatsome-block-manager' ), array( 'title', 'all-reviews-page' ) ),
	'jgm-all-reviews'       => array( __( 'Tất cả đánh giá của shop', 'flatsome-block-manager' ), array() ),
	'jgm-review-widget'     => array( __( 'Đánh giá của một sản phẩm', 'flatsome-block-manager' ), array( 'id' ) ),
	'jgm-preview-badge'     => array( __( 'Số sao + số đánh giá', 'flatsome-block-manager' ), array( 'id' ) ),
	'jgm-ugc-media-grid'    => array( __( 'Lưới ảnh / video khách gửi', 'flatsome-block-manager' ), array() ),
	'jgm-verified-badge'    => array( __( 'Huy hiệu shop đã xác minh', 'flatsome-block-manager' ), array() ),
);
