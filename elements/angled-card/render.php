<?php
/**
 * [fbm_angled_card]: Thẻ con vát góc hình học (nằm trong [fbm_angled_showcase]).
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'        => 'fbm-angled-' . wp_rand(),
		'tag'        => '01',
		'title'      => 'Motivational Speech',
		'title_tag'  => 'h3',
		'link'       => '',
		'target'     => '',
		'image'      => '',
		'id'         => '',
		'image_size' => 'large',
		'cut_x'      => '',
		'cut_y'      => '',
		'chamfer'    => '',
		'hover_zoom' => '',
		'class'      => '',
		'visibility' => '',
	),
	$atts,
	$tag
);

$fbm_title  = trim( $a['title'] );
$fbm_tag    = trim( $a['tag'] );
$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$raw_img_id = ! empty( $a['image'] ) ? $a['image'] : $a['id'];
$fbm_image  = $raw_img_id ? wp_get_attachment_image( absint( $raw_img_id ), $fbm_size, false, array( 'class' => 'fbm-angled-card__img', 'loading' => 'lazy' ) ) : '';
$fbm_ghost  = ! $fbm_image && FBM\Placeholder::visible();

if ( '' === $fbm_title && '' === $fbm_tag && ! $fbm_image ) {
	echo FBM\Placeholder::empty( __( 'Angled Card: enter title, tag, or select image.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_id   = sanitize_html_class( $a['_id'] );
$fbm_htag = in_array( $a['title_tag'], array( 'h2', 'h3', 'h4', 'p' ), true ) ? $a['title_tag'] : 'h3';

$classes = array( 'fbm-angled-card' );
if ( 'false' !== $a['hover_zoom'] ) {
	$classes[] = 'fbm-angled-card--zoom';
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}

$css_vars = array();
if ( '' !== trim( $a['cut_x'] ) ) {
	$css_vars[] = '--fbm-card-cut-x:' . max( 30, min( 85, absint( $a['cut_x'] ) ) ) . '%';
}
if ( '' !== trim( $a['cut_y'] ) ) {
	$css_vars[] = '--fbm-card-cut-y:' . max( 40, min( 300, absint( $a['cut_y'] ) ) ) . 'px';
}
if ( '' !== trim( $a['chamfer'] ) ) {
	$css_vars[] = '--fbm-card-chamfer:' . max( 0, min( 50, absint( $a['chamfer'] ) ) ) . 'px';
}
$inline_style = ! empty( $css_vars ) ? ' style="' . esc_attr( implode( ';', $css_vars ) ) . '"' : '';

$fbm_url  = '' !== trim( $a['link'] ) ? esc_url( $a['link'] ) : '';
$fbm_el   = '' !== $fbm_url ? 'a' : 'div';
$fbm_rel  = '_blank' === $a['target'] ? ' target="_blank" rel="noopener"' : '';
$fbm_href = '' !== $fbm_url ? ' href="' . $fbm_url . '"' . $fbm_rel : '';
?>
<div id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>"<?php echo $inline_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<<?php echo $fbm_el; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="fbm-angled-card__inner"<?php echo $fbm_href; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="fbm-angled-card__media">
			<?php if ( $fbm_image ) : ?>
				<?php echo $fbm_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php elseif ( $fbm_ghost ) : ?>
				<div class="fbm-angled-card__placeholder">
					<i class="dashicons dashicons-format-image" aria-hidden="true"></i>
					<span><?php esc_html_e( 'Select image', 'flatsome-block-manager' ); ?></span>
				</div>
			<?php endif; ?>
		</div>
		<div class="fbm-angled-card__content">
			<?php if ( '' !== $fbm_tag ) : ?>
				<span class="fbm-angled-card__tag"><?php echo esc_html( $fbm_tag ); ?></span>
			<?php endif; ?>
			<?php if ( '' !== $fbm_title ) : ?>
				<<?php echo $fbm_htag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> class="fbm-angled-card__title">
					<?php echo nl2br( esc_html( $fbm_title ) ); ?>
				</<?php echo $fbm_htag; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php endif; ?>
		</div>
	</<?php echo $fbm_el; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
</div>
