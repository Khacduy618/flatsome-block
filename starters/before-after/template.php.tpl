<?php
/**
 * Before / After.
 * Ảnh "trước" nằm dưới và quyết định chiều cao; ảnh "sau" nằm trên, bị cắt theo biến CSS --fbm-pos.
 * Mỗi option là một biến cùng tên ($before, $after, $orientation...). $class, $visibility từ @commons/advanced.
 */
$is_vertical = 'vertical' === $orientation;
$position    = max( 0, min( 100, (float) $offset ) );

$classes = array( 'fbm-ba', $is_vertical ? 'fbm-ba--v' : 'fbm-ba--h', 'fbm-ba--handle-' . sanitize_html_class( $handle ) );
if ( 'true' === $overlay ) {
	$classes[] = 'fbm-ba--overlay';
}
if ( $class ) {
	$classes[] = $class;
}
if ( $visibility ) {
	$classes[] = $visibility;
}

$image = static function ( $id, $placeholder ) use ( $image_size ) {
	if ( ! $id ) {
		return '<span class="fbm-ba__empty">' . esc_html( sprintf( 'Chưa chọn ảnh "%s"', $placeholder ) ) . '</span>';
	}
	// wp_get_attachment_image tự thêm alt (từ Media Library), srcset và loading="lazy".
	return wp_get_attachment_image( (int) $id, $image_size ? $image_size : 'large', false, array( 'class' => 'fbm-ba__img', 'draggable' => 'false' ) );
};

$style = sprintf(
	'--fbm-pos:%s%%;--fbm-line:%s;--fbm-line-w:%dpx;--fbm-label:%s;--fbm-label-bg:%s',
	$position,
	$line_color,
	max( 1, (int) $line_width ),
	$label_color,
	$label_bg
);
?>
<figure id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>" data-trigger="<?php echo esc_attr( $trigger ); ?>" data-auto="<?php echo esc_attr( $auto ); ?>" data-start="<?php echo esc_attr( $position ); ?>">
	<div class="fbm-ba__stage">
		<div class="fbm-ba__before"><?php echo $image( $before, $label_before ); // phpcs:ignore ?></div>
		<div class="fbm-ba__after"><?php echo $image( $after, $label_after ); // phpcs:ignore ?></div>
		<?php if ( 'true' === $show_labels ) : ?>
			<span class="fbm-ba__label fbm-ba__label--before"><?php echo esc_html( $label_before ); ?></span>
			<span class="fbm-ba__label fbm-ba__label--after"><?php echo esc_html( $label_after ); ?></span>
		<?php endif; ?>
		<?php if ( $badge ) : ?>
			<span class="fbm-ba__badge"><?php echo esc_html( $badge ); ?></span>
		<?php endif; ?>
		<input class="fbm-ba__range" type="range" min="0" max="100" step="0.5" value="<?php echo esc_attr( $position ); ?>" aria-label="<?php echo esc_attr( $label_before . ' / ' . $label_after ); ?>">
		<span class="fbm-ba__divider" aria-hidden="true"><span class="fbm-ba__handle"></span></span>
	</div>
	<?php if ( $caption ) : ?>
		<figcaption class="fbm-ba__caption"><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
</figure>
