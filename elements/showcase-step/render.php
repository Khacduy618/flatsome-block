<?php
/**
 * [fbm_showcase_step]: one step of Animated Showcase. The stage has two parts: left (left image) and right (text, right image,
 * third image, a copy of the left image for the second push). Text is printed per character (--i = index) for the typing effect, plus a full copy for screen readers.
 * image_size, image_ratio, is_first are passed down by [fbm_animated_showcase].
 * Variables provided: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'left_image'   => '',
		'text_content' => '',
		'right_image'  => '',
		'third_image'  => '',
		'image_size'   => 'large',
		'image_ratio'  => '1-1',
		'is_first'     => '',
		'class'        => '',
	),
	$atts,
	$tag
);

$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_ratios = require dirname( __DIR__ ) . '/animated-showcase/ratios.php';
$fbm_ratio  = isset( $fbm_ratios[ $a['image_ratio'] ] ) ? $fbm_ratios[ $a['image_ratio'] ]['value'] : '1';
$fbm_first  = 'true' === $a['is_first'];

$fbm_img = static function ( $id, $slot ) use ( $fbm_size, $fbm_first ) {
	$id   = absint( $id );
	$attr = array(
		'class'     => 'fbm-as__img fbm-as__img--' . $slot,
		'draggable' => 'false',
	);
	if ( ! $fbm_first || 'third' === $slot ) {
		$attr['loading'] = 'lazy';
	}
	return $id ? wp_get_attachment_image( $id, $fbm_size, false, $attr ) : '';
};
$fbm_left  = $fbm_img( $a['left_image'], 'left' );
$fbm_right = $fbm_img( $a['right_image'], 'right' );
$fbm_third = $fbm_img( $a['third_image'], 'third' );
// Second push: the original left image comes back from the right. Decorative copy, hidden from screen readers.
$fbm_again = $fbm_left ? wp_get_attachment_image(
	absint( $a['left_image'] ),
	$fbm_size,
	false,
	array(
		'class'       => 'fbm-as__img fbm-as__img--again',
		'alt'         => '',
		'aria-hidden' => 'true',
		'draggable'   => 'false',
		'loading'     => 'lazy',
	)
) : '';
if ( ! $fbm_left || ! $fbm_right || ! $fbm_third ) {
	echo FBM\Placeholder::empty( __( 'Showcase Step: pick the left, right and third images.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_classes = array( 'fbm-as__item' );
if ( $fbm_first ) {
	$fbm_classes[] = 'is-current';
}
foreach ( preg_split( '/\s+/', $a['class'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}

$fbm_text  = trim( (string) $a['text_content'] );
$fbm_chars = '';
foreach ( '' !== $fbm_text ? mb_str_split( $fbm_text ) : array() as $fbm_i => $fbm_ch ) {
	$fbm_chars .= '<span class="fbm-as__char" style="--i:' . (int) $fbm_i . '">' . esc_html( $fbm_ch ) . '</span>';
}
?>
<div class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>">
	<div class="fbm-as__stage" style="--fbm-as-ratio: <?php echo esc_attr( $fbm_ratio ); ?>">
		<div class="fbm-as__part fbm-as__part--left"><?php echo $fbm_left; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?></div>
		<div class="fbm-as__part fbm-as__part--right">
			<?php if ( '' !== $fbm_text ) : ?>
				<p class="fbm-as__text"><span class="screen-reader-text"><?php echo esc_html( $fbm_text ); ?></span><span class="fbm-as__chars" aria-hidden="true"><?php echo $fbm_chars; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every character is already esc_html'd.?></span></p>
			<?php endif; ?>
			<?php echo $fbm_right . $fbm_third . $fbm_again; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?>
		</div>
	</div>
</div>
