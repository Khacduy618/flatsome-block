<?php
/**
 * [fbm_product_showcase]: các [fbm_showcase_slide] (ảnh gốc → ảnh kết quả) trong element Slider của Flatsome ([ux_slider]):
 * tự chạy, Timer, Pause on Hover, Fade / Slide, nút và chấm đều là của theme. Một phần tử gốc section.fbm-ps.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'title'        => '',
		'description'  => '',
		'theme'        => 'light',
		'speed'        => '600',
		'image_height' => '100%',
		'image_size'   => 'large',
		'class'        => '',
		'visibility'   => '',
	),
	$atts,
	$tag
);

$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_height = array_key_exists( $a['image_height'], require __DIR__ . '/heights.php' ) ? $a['image_height'] : '100%';
$fbm_pass   = ' image_size="' . $fbm_size . '" image_height="' . $fbm_height . '"';

// Lượt 1: bỏ slide thiếu ảnh (kể cả dòng nhắc của nó). Lượt 2: in slide hợp lệ kèm số thứ tự "2 / 3".
$fbm_codes = array();
if ( preg_match_all( '/' . get_shortcode_regex( array( 'fbm_showcase_slide' ) ) . '/s', (string) $content, $fbm_m ) ) {
	foreach ( $fbm_m[0] as $fbm_child ) {
		$fbm_code = preg_replace( '/^\[fbm_showcase_slide\b/', '[fbm_showcase_slide' . $fbm_pass, $fbm_child );
		$fbm_html = trim( do_shortcode( $fbm_code ) );
		if ( '' !== $fbm_html && false === strpos( $fbm_html, 'fbm-empty' ) ) {
			$fbm_codes[] = $fbm_code;
		}
	}
}
if ( ! $fbm_codes ) {
	echo FBM\Placeholder::empty( __( 'Product Showcase Slider: thêm ít nhất một slide có đủ ảnh gốc và ảnh kết quả.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$fbm_total  = count( $fbm_codes );
$fbm_slides = '';
foreach ( $fbm_codes as $fbm_i => $fbm_code ) {
	$fbm_slides .= preg_replace( '/^\[fbm_showcase_slide\b/', '[fbm_showcase_slide slide_index="' . ( $fbm_i + 1 ) . '" slide_total="' . $fbm_total . '"', $fbm_code );
}

// Thuộc tính của element Slider: chỉ các khoá ux_slider hiểu; mặc định riêng của element (Fade, 5 giây, nút tối) nếu chưa đặt.
$fbm_slider          = ( is_array( $atts ) ? $atts : array() ) + ( require __DIR__ . '/slider-defaults.php' );
$fbm_slider['timer'] = (string) max( 1000, absint( $fbm_slider['timer'] ) ); // Vào thẳng JSON options của Flickity.
$fbm_slider_keys = array( 'type', 'style', 'slide_width', 'slide_width__md', 'slide_width__sm', 'slide_align', 'bg_color', 'margin', 'margin__md', 'margin__sm', 'infinitive', 'freescroll', 'draggable', 'parallax', 'mobile', 'hide_nav', 'nav_pos', 'nav_size', 'arrows', 'nav_style', 'nav_color', 'bullets', 'bullet_style', 'auto_slide', 'timer', 'pause_hover' );
$fbm_slider_atts = '';
foreach ( $fbm_slider_keys as $fbm_key ) {
	if ( isset( $fbm_slider[ $fbm_key ] ) ) {
		$fbm_slider_atts .= ' ' . $fbm_key . '="' . esc_attr( str_replace( array( '"', '[', ']' ), '', (string) $fbm_slider[ $fbm_key ] ) ) . '"';
	}
}

$fbm_classes = array( 'fbm-ps', 'relative' );
if ( 'dark' === $a['theme'] ) {
	array_push( $fbm_classes, 'dark', 'bg-primary-color', 'pt', 'pb' ); // Nền màu chính, chữ sáng: class của Flatsome.
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}
$fbm_speed = (int) max( 200, min( 1500, (float) $a['speed'] ) );

// Tiêu đề: markup [title] của Flatsome (h2), bỏ ký tự có thể phá shortcode.
$fbm_title  = trim( str_replace( array( '"', '[', ']' ), '', (string) $a['title'] ) );
$fbm_header = '' !== $fbm_title ? do_shortcode( '[title text="' . $fbm_title . '" style="center" tag_name="h2"]' ) : '';
if ( '' !== trim( $a['description'] ) ) {
	$fbm_header .= '<p>' . esc_html( $a['description'] ) . '</p>';
}
$fbm_label = '' !== $fbm_title ? wp_strip_all_tags( $fbm_title ) : __( 'Product Showcase', 'flatsome-block-manager' );
?>
<section class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>" style="--fbm-ps-speed: <?php echo (int) $fbm_speed; ?>ms" aria-label="<?php echo esc_attr( $fbm_label ); ?>">
	<?php if ( '' !== $fbm_header ) : ?>
		<div class="text-center"><?php echo $fbm_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup [title] của Flatsome + mô tả đã escape. ?></div>
	<?php endif; ?>
	<?php echo do_shortcode( '[ux_slider' . $fbm_slider_atts . ']' . $fbm_slides . '[/ux_slider]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Flatsome + Showcase Slide đã escape. ?>
</section>
