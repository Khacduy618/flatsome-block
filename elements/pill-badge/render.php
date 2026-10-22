<?php
/**
 * [fbm_pill_badge]: nhãn bo tròn có chấm màu (tuỳ chọn nhấp nháy). Màu chỉ qua token (tones.php).
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'        => 'fbm-pill-' . wp_rand(),
		'text'       => 'Mới ra mắt',
		'link'       => '',
		'target'     => '',
		'tone'       => 'primary',
		'dot'        => 'true',
		'pulse'      => 'true',
		'size'       => '',
		'align'      => 'left',
		'class'      => '',
		'visibility' => '',
	),
	$atts,
	$tag
);

$fbm_text = trim( $a['text'] );
if ( '' === $fbm_text ) {
	echo FBM\Placeholder::empty( __( 'Pill Badge: chưa nhập chữ.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_tones = require __DIR__ . '/tones.php';
$fbm_tone  = isset( $fbm_tones[ $a['tone'] ] ) ? $a['tone'] : 'primary';
$fbm_align = in_array( $a['align'], array( 'left', 'center', 'right' ), true ) ? $a['align'] : 'left';
$fbm_dot   = 'false' !== $a['dot'];

$wrap = array( 'fbm-pill-wrap', 'text-' . $fbm_align );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$wrap[] = sanitize_html_class( $c );
}

$pill = array( 'fbm-pill', 'fbm-pill--' . $fbm_tone );
if ( $fbm_dot && 'false' !== $a['pulse'] ) {
	$pill[] = 'fbm-pill--pulse';
}
if ( in_array( $a['size'], array( 'small', 'large' ), true ) ) {
	$pill[] = 'fbm-pill--' . $a['size'];
}

$fbm_url = '' !== trim( $a['link'] ) ? esc_url( $a['link'] ) : '';
$fbm_el  = '' !== $fbm_url ? 'a' : 'span';
$fbm_rel = '_blank' === $a['target'] ? ' target="_blank" rel="noopener"' : '';
?>
<div id="<?php echo esc_attr( sanitize_html_class( $a['_id'] ) ); ?>" class="<?php echo esc_attr( implode( ' ', array_filter( $wrap ) ) ); ?>">
	<<?php echo $fbm_el; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a | span. ?> class="<?php echo esc_attr( implode( ' ', $pill ) ); ?>"<?php echo '' !== $fbm_url ? ' href="' . $fbm_url . '"' . $fbm_rel : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_url ở trên. ?>>
		<?php if ( $fbm_dot ) : ?>
			<span class="fbm-pill__dot" aria-hidden="true"></span>
		<?php endif; ?>
		<span class="fbm-pill__text"><?php echo esc_html( $fbm_text ); ?></span>
	</<?php echo $fbm_el; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
</div>
