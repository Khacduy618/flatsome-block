<?php
/**
 * [fbm_angled_showcase]: Container cha dạng lưới, điều khiển layout và vóc dáng hàng loạt cho các thẻ con.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'          => 'fbm-showcase-' . wp_rand(),
		'columns'      => '3',
		'columns__md'  => '2',
		'columns__sm'  => '1',
		'col_gap'      => '30',
		'col_gap__sm'  => '20',
		'aspect_ratio' => '3/4',
		'cut_x'        => '55',
		'cut_y'        => '120',
		'chamfer'      => '16',
		'hover_zoom'   => 'true',
		'class'        => '',
		'visibility'   => '',
	),
	$atts,
	$tag
);

$fbm_id      = sanitize_html_class( $a['_id'] );
$fbm_cols    = max( 1, min( 6, absint( $a['columns'] ) ) );
$fbm_cols_md = ! empty( $a['columns__md'] ) ? max( 1, min( 6, absint( $a['columns__md'] ) ) ) : 2;
$fbm_cols_sm = ! empty( $a['columns__sm'] ) ? max( 1, min( 6, absint( $a['columns__sm'] ) ) ) : 1;
$fbm_gap     = absint( $a['col_gap'] );
$fbm_gap_sm  = ! empty( $a['col_gap__sm'] ) ? absint( $a['col_gap__sm'] ) : $fbm_gap;

$fbm_ratio   = in_array( $a['aspect_ratio'], array( '3/4', '4/5', '1/1', '2/3' ), true ) ? $a['aspect_ratio'] : '3/4';
$fbm_cut_x   = max( 30, min( 85, absint( $a['cut_x'] ) ) );
$fbm_cut_y   = max( 40, min( 300, absint( $a['cut_y'] ) ) );
$fbm_chamfer = max( 0, min( 50, absint( $a['chamfer'] ) ) );

$classes = array( 'fbm-angled-showcase' );
if ( 'false' !== $a['hover_zoom'] ) {
	$classes[] = 'fbm-angled-showcase--zoom';
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}

$css_vars = sprintf(
	'--fbm-showcase-cols:%d;--fbm-showcase-cols-md:%d;--fbm-showcase-cols-sm:%d;--fbm-showcase-gap:%dpx;--fbm-showcase-gap-sm:%dpx;--fbm-card-ratio:%s;--fbm-card-cut-x:%d%%;--fbm-card-cut-y:%dpx;--fbm-card-chamfer:%dpx;',
	$fbm_cols,
	$fbm_cols_md,
	$fbm_cols_sm,
	$fbm_gap,
	$fbm_gap_sm,
	esc_attr( $fbm_ratio ),
	$fbm_cut_x,
	$fbm_cut_y,
	$fbm_chamfer
);
?>
<div id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>" style="<?php echo esc_attr( $css_vars ); ?>">
	<div class="fbm-angled-showcase__grid">
		<?php echo do_shortcode( $content ); ?>
	</div>
</div>
