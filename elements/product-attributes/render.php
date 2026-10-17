<?php
/**
 * [handdn_product_attributes]: markup giữ đúng class `handdn-product-attributes__*` để CSS sẵn có của site áp dụng.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'id'         => '',
		'class'      => '',
		'visibility' => '',
	),
	$atts,
	$tag
);

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

// Không ghi đè global $product (WordPress có thể đặt nó là slug dạng chuỗi).
$fbm_product = null;
if ( absint( $a['id'] ) ) {
	$fbm_found   = wc_get_product( absint( $a['id'] ) );
	$fbm_product = $fbm_found && 'publish' === $fbm_found->get_status() ? $fbm_found : null;
} elseif ( isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ) {
	$fbm_product = $GLOBALS['product'];
} else {
	$fbm_queried = get_queried_object_id();
	$fbm_product = $fbm_queried && 'product' === get_post_type( $fbm_queried ) ? wc_get_product( $fbm_queried ) : null;
}

$items = $fbm_product ? FBM\Products\Attributes::of( $fbm_product ) : array();
if ( ! $items ) {
	echo FBM\Placeholder::empty( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape trong Placeholder.
		$fbm_product
			? __( 'Product Attributes: sản phẩm này chưa có thuộc tính nào bật "Visible on the product page".', 'flatsome-block-manager' )
			: __( 'Product Attributes: chọn một sản phẩm, hoặc đặt element trong Custom Product Layout.', 'flatsome-block-manager' )
	);
	return;
}

$classes = array( 'handdn-product-attributes' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}
?>
<div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
	<?php foreach ( $items as $item ) : ?>
		<div class="handdn-product-attributes__item">
			<div class="handdn-product-attributes__label"><?php echo esc_html( $item['label'] ); ?></div>
			<div class="handdn-product-attributes__values">
				<?php foreach ( $item['values'] as $value ) : ?>
					<span class="handdn-product-attributes__value"><?php echo esc_html( $value ); ?></span>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endforeach; ?>
</div>
<?php
