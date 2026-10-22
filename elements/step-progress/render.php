<?php
/**
 * [ux_step_progress]: Step Progress Bar for Flatsome Slider
 * Variables provided: $atts, $content, $tag.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'          => 'ux-sp-' . wp_rand( 1000, 9999 ),
		'style'        => 'segmented',
		'labels'       => '',
		'target_class' => '.step-item',
		'active_color' => '',
		'max_width'    => '1330px',
		'margin_top'   => '30px',
		'class'        => '',
		'visibility'   => '',
	),
	$atts,
	$tag
);

$unique_id = ! empty( $a['_id'] ) ? sanitize_html_class( $a['_id'] ) : 'ux-sp-' . wp_rand( 1000, 9999 );
$color_var = ! empty( $a['active_color'] ) ? esc_attr( $a['active_color'] ) : 'var(--primary-color, var(--fs-color-primary, #1d1d1f))';
$style     = in_array( $a['style'], array( 'segmented', 'slider', 'fill_up', 'numbered', 'dot' ), true ) ? $a['style'] : 'segmented';

$inline_styles = sprintf(
	'--ux-sp-color: %s; max-width: %s; margin-top: %s;',
	$color_var,
	esc_attr( $a['max_width'] ),
	esc_attr( $a['margin_top'] )
);

$wrap_classes = array(
	'ux-step-progress',
	'ux-step-progress--' . $style,
);

if ( ! empty( $a['class'] ) ) {
	foreach ( preg_split( '/\s+/', (string) $a['class'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
		$wrap_classes[] = sanitize_html_class( $c );
	}
}
if ( ! empty( $a['visibility'] ) ) {
	foreach ( preg_split( '/\s+/', (string) $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $v ) {
		$wrap_classes[] = sanitize_html_class( $v );
	}
}
?>
<div id="<?php echo esc_attr( $unique_id ); ?>" 
	 class="<?php echo esc_attr( implode( ' ', array_filter( $wrap_classes ) ) ); ?>"
	 style="<?php echo esc_attr( $inline_styles ); ?>"
	 data-style="<?php echo esc_attr( $style ); ?>"
	 data-target="<?php echo esc_attr( $a['target_class'] ); ?>"
	 data-labels="<?php echo esc_attr( $a['labels'] ); ?>">
	
	<!-- Track line for numbered & dot styles -->
	<div class="ux-sp-track" aria-hidden="true">
		<div class="ux-sp-track-fill"></div>
	</div>

	<!-- Continuous slider track line (for slider style) -->
	<div class="ux-sp-slider-bar" aria-hidden="true">
		<div class="ux-sp-slider-thumb"></div>
	</div>

	<!-- Progressive fill-up track line (for fill_up style) -->
	<div class="ux-sp-fillup-track" aria-hidden="true">
		<div class="ux-sp-fillup-fill"></div>
	</div>

	<!-- Step nodes container -->
	<div class="ux-sp-items" role="tablist"></div>
</div>
