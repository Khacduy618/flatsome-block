<?php
/**
 * [fbm_light_slider]: infinite right-to-left slider (view.js), zero library.
 * Variables provided: $atts, $content, $tag.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

$atts = is_array( $atts ) ? $atts : array();

// Attribute names of the previous [fbm_light_carousel] tag (alias) map onto the [ux_slider]-style names.
if ( isset( $atts['autoplay'] ) && ! isset( $atts['auto_slide'] ) ) {
	$atts['auto_slide'] = $atts['autoplay'];
}
if ( isset( $atts['delay'] ) && ! isset( $atts['timer'] ) ) {
	$atts['timer'] = $atts['delay'];
}
if ( isset( $atts['arrows'] ) && in_array( $atts['arrows'], array( 'hover', 'always', 'off' ), true ) ) {
	$atts['hide_nav'] = 'always' === $atts['arrows'] ? 'true' : '';
	$atts['arrows']   = 'off' === $atts['arrows'] ? 'false' : 'true';
}
// auto_slide="3000" turns autoplay on with that timer.
if ( isset( $atts['auto_slide'] ) && is_numeric( $atts['auto_slide'] ) ) {
	$atts['timer']      = $atts['auto_slide'];
	$atts['auto_slide'] = 'true';
}

// Defaults equal builder.php: UX Builder does not save values that match the option default.
$a = shortcode_atts(
	array(
		'columns'      => '4',
		'columns__md'  => null,
		'columns__sm'  => null,
		'gap'          => '16',
		'gap__md'      => null,
		'gap__sm'      => null,
		'step'         => '1',
		'equal_height' => 'false',
		'arrows'       => 'true',
		'hide_nav'     => '',
		'nav_style'    => 'circle',
		'nav_color'    => 'light',
		'bullets'      => 'true',
		'bullet_style' => 'circle',
		'auto_slide'   => 'true',
		'timer'        => '3000',
		'pause_hover'  => 'true',
		'speed'        => '500',
		'class'        => '',
		'visibility'   => '',
	),
	$atts,
	$tag
);

$inner = trim( do_shortcode( (string) $content ) );
if ( '' === $inner ) {
	if ( current_user_can( 'edit_pages' ) && ! is_feed() ) {
		echo '<p class="op-7">' . esc_html__( 'Light Slider: add banners or images inside this element.', 'flatsome-block-manager' ) . '</p>';
	}
	return;
}

// First allowed value is the fallback.
$fbm_pick = static function ( $value, array $allowed ) {
	return in_array( $value, $allowed, true ) ? $value : $allowed[0];
};
// Empty responsive values stay null so the breakpoint inherits the larger one.
$fbm_num = static function ( $value, $min, $max, $decimals = 0 ) {
	if ( null === $value || '' === $value ) {
		return null;
	}
	return (string) round( min( $max, max( $min, (float) $value ) ), $decimals );
};

$vars = array(
	'columns'     => $fbm_num( $a['columns'], 1, 6, 1 ) ?? '4',
	'columns__md' => $fbm_num( $a['columns__md'], 1, 6, 1 ),
	'columns__sm' => $fbm_num( $a['columns__sm'], 1, 6, 1 ),
	'gap'         => $fbm_num( $a['gap'], 0, 50 ) ?? '16',
	'gap__md'     => $fbm_num( $a['gap__md'], 0, 50 ),
	'gap__sm'     => $fbm_num( $a['gap__sm'], 0, 50 ),
);

$show_arrows  = 'false' !== $a['arrows'];
$show_bullets = 'false' !== $a['bullets'];
$speed        = (int) ( $fbm_num( $a['speed'], 200, 1500 ) ?? 500 );
$config       = array(
	'step'     => (int) ( $fbm_num( $a['step'], 1, 6 ) ?? 1 ),
	'autoplay' => 'false' !== $a['auto_slide'],
	'timer'    => (int) ( $fbm_num( $a['timer'], 1000, 10000 ) ?? 3000 ),
	'speed'    => $speed,
	'pause'    => 'false' !== $a['pause_hover'],
);

$id      = 'fbm-ls-' . wp_rand();
$classes = array(
	'fbm-ls',
	'slider-nav-' . $fbm_pick( $a['nav_style'], array( 'circle', 'simple', 'reveal' ) ),
	'slider-nav-' . $fbm_pick( $a['nav_color'], array( 'light', 'dark' ) ),
);
if ( $show_bullets ) {
	$classes[] = 'slider-nav-dots-' . $fbm_pick( $a['bullet_style'], array( 'circle', 'dashes', 'dashes-spaced', 'simple', 'square' ) );
}
if ( 'true' === $a['hide_nav'] ) {
	$classes[] = 'slider-show-nav';
}
if ( 'true' !== $a['equal_height'] ) {
	$classes[] = 'fbm-ls--top';
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_class ) {
	$classes[] = sanitize_html_class( $fbm_class );
}

$fbm_arrow = '<svg class="flickity-button-icon" viewBox="0 0 100 100"><path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" class="arrow"%s></path></svg>';
?>
<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>" data-fbm-ls="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
	<div class="fbm-ls__viewport">
		<div class="fbm-ls__track">
			<?php echo $inner; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- rendered child shortcodes. ?>
		</div>
	</div>
	<?php if ( $show_arrows ) : ?>
		<button type="button" class="flickity-button flickity-prev-next-button previous" aria-label="<?php esc_attr_e( 'Previous', 'flatsome-block-manager' ); ?>">
			<?php printf( $fbm_arrow, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		</button>
		<button type="button" class="flickity-button flickity-prev-next-button next" aria-label="<?php esc_attr_e( 'Next', 'flatsome-block-manager' ); ?>">
			<?php printf( $fbm_arrow, ' transform="translate(100, 100) rotate(180) "' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG. ?>
		</button>
	<?php endif; ?>
	<?php if ( $show_bullets ) : ?>
		<ol class="flickity-page-dots"></ol>
	<?php endif; ?>
	<?php
	if ( function_exists( 'ux_builder_element_style_tag' ) ) {
		echo ux_builder_element_style_tag( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Flatsome builds the CSS from sanitized numbers.
			$id,
			array(
				'columns' => array( 'property' => '--fbm-ls-cur' ),
				'gap'     => array(
					'property' => '--fbm-ls-gap',
					'unit'     => 'px',
				),
				'speed'   => array(
					'property' => '--fbm-ls-speed',
					'unit'     => 'ms',
				),
			),
			$vars + array( 'speed' => (string) $speed )
		);
	} else {
		printf( '<style>#%1$s { --fbm-ls-cur: %2$s; --fbm-ls-gap: %3$spx; --fbm-ls-speed: %4$dms; }</style>', esc_attr( $id ), esc_html( $vars['columns'] ), esc_html( $vars['gap'] ), (int) $speed );
	}
	?>
</div>
