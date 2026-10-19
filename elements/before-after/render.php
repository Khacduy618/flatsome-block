<?php
/**
 * [fbm_before_after]: ảnh "trước" quyết định chiều cao; ảnh "sau" phủ lên, bị cắt theo biến --fbm-pos.
 * Tay nắm là nút role="slider" (phím mũi tên), dùng icon nút slider của theme; nhãn dùng .badge-inner của theme.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'          => 'fbm-ba-' . wp_rand(),
		'before'       => '',
		'after'        => '',
		'image_size'   => 'large',
		'ratio'        => '',
		'width'        => '100',
		'orientation'  => 'horizontal',
		'trigger'      => 'drag',
		'offset'       => '50',
		'auto'         => 'false',
		'handle'       => 'circle',
		'show_labels'  => 'false',
		'label_before' => __( 'Trước', 'flatsome-block-manager' ),
		'label_after'  => __( 'Sau', 'flatsome-block-manager' ),
		'caption'      => '',
		'class'        => '',
		'visibility'   => '',
	),
	$atts,
	$tag
);

$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_before = wp_get_attachment_image( absint( $a['before'] ), $fbm_size, false, array( 'class' => 'fbm-ba__img', 'draggable' => 'false' ) );
$fbm_after  = wp_get_attachment_image( absint( $a['after'] ), $fbm_size, false, array( 'class' => 'fbm-ba__img', 'draggable' => 'false', 'loading' => 'lazy' ) );

if ( ! $fbm_before || ! $fbm_after ) {
	echo FBM\Placeholder::empty( __( 'Before / After: chọn đủ ảnh "trước" và ảnh "sau".', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_vertical = 'vertical' === $a['orientation'];
$fbm_pos      = max( 0, min( 100, (float) $a['offset'] ) );
$fbm_pos      = rtrim( rtrim( number_format( $fbm_pos, 1, '.', '' ), '0' ), '.' );

$classes = array( 'fbm-ba', $fbm_vertical ? 'fbm-ba--v' : 'fbm-ba--h' );
if ( 'none' === $a['handle'] ) {
	$classes[] = 'fbm-ba--no-handle';
}
$fbm_ratios = require __DIR__ . '/ratios.php';
if ( isset( $fbm_ratios[ $a['ratio'] ] ) ) {
	$classes[] = 'fbm-ba--ratio';
	$classes[] = 'fbm-ba--r-' . $a['ratio'];
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}
$fbm_label = sprintf( /* translators: 1: before label, 2: after label */ __( 'So sánh %1$s / %2$s', 'flatsome-block-manager' ), $a['label_before'], $a['label_after'] );
?>
<figure id="<?php echo esc_attr( sanitize_html_class( $a['_id'] ) ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="--fbm-pos:<?php echo esc_attr( $fbm_pos ); ?>%" data-trigger="<?php echo esc_attr( 'hover' === $a['trigger'] ? 'hover' : 'drag' ); ?>" data-auto="<?php echo esc_attr( 'true' === $a['auto'] ? 'true' : 'false' ); ?>" data-start="<?php echo esc_attr( $fbm_pos ); ?>">
	<div class="fbm-ba__stage">
		<div class="fbm-ba__before"><?php echo $fbm_before; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?></div>
		<div class="fbm-ba__after"><?php echo $fbm_after; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?></div>
		<?php if ( 'true' === $a['show_labels'] ) : ?>
			<span class="fbm-ba__label fbm-ba__label--before badge-inner" aria-hidden="true"><?php echo esc_html( $a['label_before'] ); ?></span>
			<span class="fbm-ba__label fbm-ba__label--after badge-inner" aria-hidden="true"><?php echo esc_html( $a['label_after'] ); ?></span>
		<?php endif; ?>
		<span class="fbm-ba__divider" aria-hidden="true"></span>
		<button type="button" class="fbm-ba__handle" role="slider" aria-label="<?php echo esc_attr( $fbm_label ); ?>" aria-orientation="<?php echo $fbm_vertical ? 'vertical' : 'horizontal'; ?>" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo esc_attr( $fbm_pos ); ?>">
			<svg class="flickity-button-icon" viewBox="0 0 100 100" aria-hidden="true" focusable="false"><path class="arrow" d="M 38,20 L 8,50 L 38,80 L 44,74 L 20,50 L 44,26 Z M 62,20 L 92,50 L 62,80 L 56,74 L 80,50 L 56,26 Z"></path></svg>
		</button>
	</div>
	<?php if ( '' !== trim( $a['caption'] ) ) : ?>
		<figcaption class="fbm-ba__caption op-7"><?php echo esc_html( $a['caption'] ); ?></figcaption>
	<?php endif; ?>
</figure>
<?php
// Chiều rộng theo breakpoint: cùng cách element Image của Flatsome (thẻ <style> theo id, chỉ khi người dùng đã đặt).
if ( function_exists( 'ux_builder_element_style_tag' ) && is_array( $atts ) ) {
	echo ux_builder_element_style_tag( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS do Flatsome sinh, giá trị đã floatval.
		sanitize_html_class( $a['_id'] ),
		array(
			'width' => array(
				'selector' => '',
				'property' => 'width',
				'unit'     => '%',
			),
		),
		$atts
	);
}
