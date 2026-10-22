<?php
/**
 * [fbm_animated_showcase]: stacked [fbm_showcase_step] items; view.js plays each step in turn (2 runs per step, looping forever).
 * One root node section.fbm-as; the heading uses Flatsome's [title].
 * Variables provided: $atts, $content, $tag.
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

// Pass 1: drop steps missing images (including their hint line). Pass 2: print valid steps, the first one visible.
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
	echo FBM\Placeholder::empty( __( 'Animated Showcase: add at least one step with left, right and third images.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

// Heading: Flatsome [title] markup (h2), stripping characters that could break the shortcode.
$fbm_title  = trim( str_replace( array( '"', '[', ']' ), '', (string) $a['title'] ) );
$fbm_header = '' !== $fbm_title ? do_shortcode( '[title text="' . $fbm_title . '" style="center" tag_name="h2"]' ) : '';
if ( '' !== trim( $a['description'] ) ) {
	$fbm_header .= '<p>' . esc_html( $a['description'] ) . '</p>';
}
$fbm_label = '' !== $fbm_title ? wp_strip_all_tags( $fbm_title ) : __( 'Animated Showcase', 'flatsome-block-manager' );
?>
<section class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>" style="--fbm-as-dur: <?php echo (int) $fbm_duration; ?>ms" data-duration="<?php echo (int) $fbm_duration; ?>" data-delay="<?php echo (int) $fbm_delay; ?>" aria-label="<?php echo esc_attr( $fbm_label ); ?>">
	<?php if ( '' !== $fbm_header ) : ?>
		<div class="text-center"><?php echo $fbm_header; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Flatsome [title] markup + escaped description. ?></div>
	<?php endif; ?>
	<div class="fbm-as__track"><?php echo $fbm_steps; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Showcase Step output is already escaped. ?></div>
</section>
