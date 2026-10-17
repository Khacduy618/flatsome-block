<?php
/**
 * [fbm_reviews]: khung Flatsome bọc 1 widget Judge.me trong danh sách cho phép.
 * Đánh giá nằm trên máy chủ Judge.me; plugin chỉ in shortcode của Judge.me.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'widget'          => 'jgm-featured-carousel',
		'title'           => '',
		'product'         => '',
		'carousel_title'  => '',
		'all_reviews_url' => '',
		'class'           => '',
		'visibility'      => '',
	),
	$atts,
	$tag
);

$widgets = require __DIR__ . '/widgets.php';
if ( ! isset( $widgets[ $a['widget'] ] ) ) {
	return; // Không nằm trong danh sách cho phép.
}
if ( ! shortcode_exists( $a['widget'] ) ) {
	echo FBM\Placeholder::empty( __( 'Reviews: Cần plugin Judge.me đang bật.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

// Giá trị đưa vào shortcode của Judge.me: bỏ ký tự có thể đóng thuộc tính / mở shortcode mới.
$fbm_clean = static function ( $value ) {
	return trim( str_replace( array( '[', ']', '"', "'" ), '', wp_strip_all_tags( (string) $value ) ) );
};
$fbm_args = array();
foreach ( $widgets[ $a['widget'] ][1] as $fbm_key ) {
	if ( 'id' === $fbm_key && absint( $a['product'] ) ) {
		$fbm_args['id'] = absint( $a['product'] );
	} elseif ( 'title' === $fbm_key && '' !== $fbm_clean( $a['carousel_title'] ) ) {
		$fbm_args['title'] = $fbm_clean( $a['carousel_title'] );
	} elseif ( 'all-reviews-page' === $fbm_key && esc_url_raw( $a['all_reviews_url'] ) ) {
		$fbm_args['all-reviews-page'] = $fbm_clean( esc_url_raw( $a['all_reviews_url'] ) );
	}
}
$fbm_shortcode = '[' . $a['widget'];
foreach ( $fbm_args as $fbm_key => $fbm_value ) {
	$fbm_shortcode .= ' ' . $fbm_key . '="' . $fbm_value . '"';
}
$fbm_shortcode .= ']';

$fbm_widget_html = do_shortcode( $fbm_shortcode );
if ( '' === trim( $fbm_widget_html ) ) {
	echo FBM\Placeholder::empty( __( 'Reviews: Judge.me chưa có đánh giá để hiện ở widget này.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$classes = array( 'fbm-reviews' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}
?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php
	if ( '' !== $fbm_clean( $a['title'] ) ) {
		echo do_shortcode( '[title text="' . esc_attr( $fbm_clean( $a['title'] ) ) . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- element Title của Flatsome.
	}
	echo $fbm_widget_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của plugin Judge.me.
	?>
</div>
<?php
