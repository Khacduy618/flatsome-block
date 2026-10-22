<?php
/**
 * [fbm_animated_showcase]: các [fbm_showcase_step] chồng lên nhau, view.js chạy lần lượt từng bước (mỗi bước 2 lượt, lặp mãi).
 * Một phần tử gốc section.fbm-as; tiêu đề dùng [title] của Flatsome.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'title'       => '',
		'description' => '',
		'duration'    => '700',
		'step_delay'  => '1200',
		'image_ratio' => '1-1',
		'image_size'  => 'large',
		'class'       => '',
		'visibility'  => '',
	),
	$atts,
	$tag
);

$fbm_size  = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_ratio = array_key_exists( $a['image_ratio'], require __DIR__ . '/ratios.php' ) ? $a['image_ratio'] : '1-1';
$fbm_pass  = ' image_size="' . $fbm_size . '" image_ratio="' . $fbm_ratio . '"';

// Lượt 1: bỏ bước thiếu ảnh (kể cả dòng nhắc của nó). Lượt 2: in bước hợp lệ, bước đầu đang hiện.
$fbm_codes = array();
if ( preg_match_all( '/' . get_shortcode_regex( array( 'fbm_showcase_step' ) ) . '/s', (string) $content, $fbm_m ) ) {
	foreach ( $fbm_m[0] as $fbm_child ) {
		$fbm_code = preg_replace( '/^\[fbm_showcase_step\b/', '[fbm_showcase_step' . $fbm_pass, $fbm_child );
		$fbm_html = trim( do_shortcode( $fbm_code ) );
		if ( '' !== $fbm_html && false === strpos( $fbm_html, 'fbm-empty' ) ) {
			$fbm_codes[] = $fbm_code;
		}
	}
}
if ( ! $fbm_codes ) {
	echo FBM\Placeholder::empty( __( 'Animated Showcase: thêm ít nhất một bước có đủ ảnh trái, ảnh phải và ảnh thứ ba.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}
$fbm_steps = '';
foreach ( $fbm_codes as $fbm_i => $fbm_code ) {
	$fbm_steps .= do_shortcode( 0 === $fbm_i ? preg_replace( '/^\[fbm_showcase_step\b/', '[fbm_showcase_step is_first="true"', $fbm_code ) : $fbm_code );
}

$fbm_duration = (int) max( 300, min( 2000, (float) $a['duration'] ) );
$fbm_delay    = (int) max( 200, min( 4000, (float) $a['step_delay'] ) );

$fbm_classes = array( 'fbm-as', 'relative' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}

// Tiêu đề: markup [title] của Flatsome (h2), bỏ ký tự có thể phá shortcode.
$fbm_title  = trim( str_replace( array( '"', '[', ']' ), '', (string) $a['title'] ) );
$fbm_header = '' !== $fbm_title ? do_shortcode( '[title text="' . $fbm_title . '" style="center" tag_name="h2"]' ) : '';
if ( '' !== trim( $a['description'] ) ) {
	$fbm_header .= '<p>' . esc_html( $a['description'] ) . '</p>';
}
$fbm_label = '' !== $fbm_title ? wp_strip_all_tags( $fbm_title ) : __( 'Animated Showcase', 'flatsome-block-manager' );
?>
<section class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>" style="--fbm-as-dur: <?php echo (int) $fbm_duration; ?>ms" data-duration="<?php echo (int) $fbm_duration; ?>" data-delay="<?php echo (int) $fbm_delay; ?>" aria-label="<?php echo esc_attr( $fbm_label ); ?>">
	<?php if ( '' !== $fbm_header ) : ?>
		<div class="text-center"><?php echo $fbm_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup [title] của Flatsome + mô tả đã escape. ?></div>
	<?php endif; ?>
	<div class="fbm-as__track"><?php echo $fbm_steps; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- output của Showcase Step đã escape. ?></div>
</section>
