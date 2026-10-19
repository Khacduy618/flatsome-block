<?php
/**
 * [fbm_look] (bên trong [fbm_shop_look]): ảnh lookbook có tối đa 8 pin + một thẻ sản phẩm; bấm pin thì đổi thẻ (view.js).
 * Output có đúng 1 phần tử gốc <section> để UX Builder (wrap => false) theo dõi được sau khi vẽ lại.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$fbm_defaults = array(
	'_id'         => 'look-' . wp_rand(),
	'title'       => '',
	'description' => '',
	'image'       => '',
	'image_size'  => 'large',
	'ratio'       => '',
	'image_side'  => 'left',
	'split'       => '50',
	'pin_style'   => 'dot',
	'pulse'       => 'true',
	'pin_tone'    => 'light',
	'card_image'  => 'true',
	'button_text' => __( 'Xem sản phẩm', 'flatsome-block-manager' ),
	'class'       => '',
	'visibility'  => '',
);
for ( $fbm_n = 1; $fbm_n <= 8; $fbm_n++ ) {
	$fbm_defaults[ "pin{$fbm_n}_product" ] = '';
	$fbm_defaults[ "pin{$fbm_n}_x" ]       = '50';
	$fbm_defaults[ "pin{$fbm_n}_y" ]       = '50';
}
$a = shortcode_atts( $fbm_defaults, $atts, $tag );

$fbm_shared = require __DIR__ . '/options.php';
$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_image  = wp_get_attachment_image( absint( $a['image'] ), $fbm_size, false, array( 'class' => 'fbm-stl__img', 'draggable' => 'false' ) );

// Pin hợp lệ: sản phẩm đã đăng và không bị ẩn khỏi danh mục.
$fbm_pins = array();
for ( $fbm_n = 1; $fbm_n <= 8; $fbm_n++ ) {
	$fbm_product = function_exists( 'wc_get_product' ) ? wc_get_product( absint( $a[ "pin{$fbm_n}_product" ] ) ) : null;
	if ( ! $fbm_product || 'publish' !== $fbm_product->get_status() || ! in_array( $fbm_product->get_catalog_visibility(), array( 'visible', 'catalog' ), true ) ) {
		continue;
	}
	$fbm_pins[] = array(
		'slot'    => $fbm_n,
		'product' => $fbm_product,
		'x'       => max( 0, min( 100, (int) round( (float) $a[ "pin{$fbm_n}_x" ] ) ) ),
		'y'       => max( 0, min( 100, (int) round( (float) $a[ "pin{$fbm_n}_y" ] ) ) ),
	);
}

if ( ! $fbm_image || ! $fbm_pins ) {
	echo FBM\Placeholder::empty( __( 'Shop The Look: chọn ảnh và ít nhất 1 sản phẩm cho pin.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_id      = sanitize_html_class( $a['_id'] );
$fbm_classes = array( 'fbm-stl', 'numbers' === $a['pin_style'] ? 'fbm-stl--numbers' : 'fbm-stl--dots' );
if ( 'right' === $a['image_side'] ) {
	$fbm_classes[] = 'fbm-stl--right';
}
if ( isset( $fbm_shared['splits'][ $a['split'] ] ) && '50' !== $a['split'] ) {
	$fbm_classes[] = 'fbm-stl--split-' . $a['split'];
}
if ( 'true' === $a['pulse'] ) {
	$fbm_classes[] = 'fbm-stl--pulse';
}
if ( 'dark' === $a['pin_tone'] ) {
	$fbm_classes[] = 'fbm-stl--tone-dark';
}
if ( isset( $fbm_shared['ratios'][ $a['ratio'] ] ) ) {
	$fbm_classes[] = 'fbm-stl--ratio';
	$fbm_classes[] = 'fbm-stl--r-' . $a['ratio'];
}
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}

// Tiêu đề in bằng [title] của Flatsome; bỏ ký tự có thể phá shortcode.
$fbm_title = trim( str_replace( array( '"', '[', ']' ), '', (string) $a['title'] ) );
?>
<section id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>">
	<?php if ( '' !== $fbm_title ) : ?>
		<?php echo do_shortcode( '[title text="' . $fbm_title . '"]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup của Flatsome. ?>
	<?php endif; ?>
	<?php if ( '' !== trim( $a['description'] ) ) : ?>
		<p class="fbm-stl__desc"><?php echo esc_html( $a['description'] ); ?></p>
	<?php endif; ?>
	<div class="fbm-stl__grid">
		<div class="fbm-stl__media">
			<?php echo $fbm_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?>
			<?php foreach ( $fbm_pins as $fbm_i => $fbm_pin ) : ?>
				<button type="button" class="fbm-stl__pin<?php echo 0 === $fbm_i ? ' is-active' : ''; ?>" data-slot="<?php echo esc_attr( $fbm_pin['slot'] ); ?>" data-index="<?php echo esc_attr( $fbm_i ); ?>" aria-controls="<?php echo esc_attr( $fbm_id . '-card-' . $fbm_i ); ?>" aria-pressed="<?php echo 0 === $fbm_i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'Xem %s', 'flatsome-block-manager' ), wp_strip_all_tags( $fbm_pin['product']->get_name() ) ) ); ?>" style="--fbm-x:<?php echo esc_attr( $fbm_pin['x'] ); ?>%;--fbm-y:<?php echo esc_attr( $fbm_pin['y'] ); ?>%"><span class="fbm-stl__pin-num"><?php echo esc_html( $fbm_i + 1 ); ?></span></button>
			<?php endforeach; ?>
		</div>
		<div class="fbm-stl__cards" aria-live="polite">
			<?php
			foreach ( $fbm_pins as $fbm_i => $fbm_pin ) :
				$fbm_p     = $fbm_pin['product'];
				$fbm_link  = get_permalink( $fbm_p->get_id() );
				$fbm_price = $fbm_p->get_price_html();
				?>
				<article class="fbm-stl__card" id="<?php echo esc_attr( $fbm_id . '-card-' . $fbm_i ); ?>" data-index="<?php echo esc_attr( $fbm_i ); ?>"<?php echo 0 === $fbm_i ? '' : ' hidden'; ?>>
					<?php if ( 'true' === $a['card_image'] ) : ?>
						<a class="fbm-stl__card-img" href="<?php echo esc_url( $fbm_link ); ?>" tabindex="-1" aria-hidden="true"><?php echo $fbm_p->get_image( 'woocommerce_thumbnail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce. ?></a>
					<?php endif; ?>
					<a class="fbm-stl__card-name" href="<?php echo esc_url( $fbm_link ); ?>"><?php echo esc_html( wp_strip_all_tags( $fbm_p->get_name() ) ); ?></a>
					<?php if ( '' !== trim( (string) $fbm_price ) ) : ?>
						<div class="fbm-stl__card-price price-wrapper"><span class="price"><?php echo wp_kses_post( $fbm_price ); ?></span></div>
					<?php endif; ?>
					<a class="button primary is-small fbm-stl__card-btn" href="<?php echo esc_url( $fbm_link ); ?>"><?php echo esc_html( $a['button_text'] ); ?></a>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
