<?php
/**
 * Element container: $content chứa shortcode của các element con ([fbm_before_after]...).
 * Canvas UX Builder dùng "builder_template" trong schema (có thẻ <content></content>),
 * còn frontend và Gutenberg dùng template này.
 */
$per     = max( 1, min( 4, (int) $per_view ) );
$classes = trim( 'fbm-bac ' . $class . ' ' . $visibility );
?>
<div id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( $classes ); ?>" style="--fbm-per: <?php echo esc_attr( $per ); ?>; --fbm-gap: <?php echo esc_attr( (int) $gap ); ?>px">
	<div class="fbm-bac__track" tabindex="0"><?php echo do_shortcode( $content ); // phpcs:ignore ?></div>
	<?php if ( 'true' === $arrows ) : ?>
		<button type="button" class="fbm-bac__nav fbm-bac__nav--prev" aria-label="<?php esc_attr_e( 'Trước', 'flatsome-block-manager' ); ?>">&#8249;</button>
		<button type="button" class="fbm-bac__nav fbm-bac__nav--next" aria-label="<?php esc_attr_e( 'Sau', 'flatsome-block-manager' ); ?>">&#8250;</button>
	<?php endif; ?>
</div>
