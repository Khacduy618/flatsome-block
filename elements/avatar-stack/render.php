<?php
/**
 * [fbm_avatar_stack]: overlapping avatars + stars (Flatsome/WooCommerce .star-rating) + rating text.
 * No images picked: editors see 4 placeholder circles, guests see nothing.
 * Variables provided: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'         => 'fbm-avatars-' . wp_rand(),
		'ids'         => '',
		'show_rating' => 'true',
		'rating'      => '4.9',
		'text'        => '2,000+ happy customers',
		'size'        => '44',
		'overlap'     => '12',
		'align'       => 'left',
		'class'       => '',
		'visibility'  => '',
	),
	$atts,
	$tag
);

$fbm_imgs = array();
foreach ( array_slice( wp_parse_id_list( $a['ids'] ), 0, 8 ) as $fbm_aid ) {
	$fbm_img = wp_get_attachment_image( $fbm_aid, 'thumbnail', false, array( 'class' => 'fbm-avatars__img', 'loading' => 'lazy' ) );
	if ( $fbm_img ) {
		$fbm_imgs[] = $fbm_img;
	}
}
$fbm_text   = trim( $a['text'] );
$fbm_rating = 'false' !== $a['show_rating'];
$fbm_ghosts = ! $fbm_imgs && FBM\Placeholder::visible();

if ( ! $fbm_imgs && ! $fbm_rating && '' === $fbm_text ) {
	echo FBM\Placeholder::empty( __( 'Avatar Stack: pick images, turn on the rating or enter text.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_align = in_array( $a['align'], array( 'left', 'center', 'right' ), true ) ? $a['align'] : 'left';
$classes   = array( 'fbm-avatars', 'text-' . $fbm_align );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}

$fbm_size    = min( 96, max( 24, absint( $a['size'] ) ) );
$fbm_overlap = min( 40, absint( $a['overlap'] ) );
$fbm_score   = max( 0, min( 5, (float) $a['rating'] ) );
$fbm_score_s = rtrim( rtrim( number_format( $fbm_score, 1, '.', '' ), '0' ), '.' );
$fbm_pct     = rtrim( rtrim( number_format( $fbm_score / 5 * 100, 1, '.', '' ), '0' ), '.' );
?>
<div id="<?php echo esc_attr( sanitize_html_class( $a['_id'] ) ); ?>" class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>" style="--fbm-av-size:<?php echo (int) $fbm_size; ?>px;--fbm-av-overlap:<?php echo (int) $fbm_overlap; ?>px">
	<div class="fbm-avatars__inner">
		<?php if ( $fbm_imgs || $fbm_ghosts ) : ?>
			<div class="fbm-avatars__group" aria-hidden="true">
				<?php
				foreach ( $fbm_imgs as $fbm_img ) {
					echo $fbm_img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image.
				}
				if ( $fbm_ghosts ) {
					echo str_repeat( '<span class="fbm-avatars__img fbm-avatars__img--empty"><i class="icon-user"></i></span>', 4 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static markup.
				}
				?>
			</div>
		<?php endif; ?>
		<div class="fbm-avatars__meta">
			<?php if ( $fbm_rating ) : ?>
				<div class="fbm-avatars__rating">
					<div class="star-rating" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: rating */ __( 'Rated %s out of 5', 'flatsome-block-manager' ), $fbm_score_s ) ); ?>"><span style="width:<?php echo esc_attr( $fbm_pct ); ?>%"></span></div>
					<strong class="fbm-avatars__score"><?php echo esc_html( $fbm_score_s ); ?>/5</strong>
				</div>
			<?php endif; ?>
			<?php if ( '' !== $fbm_text ) : ?>
				<p class="fbm-avatars__text"><?php echo esc_html( $fbm_text ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
