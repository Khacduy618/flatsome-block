<?php
/**
 * [fbm_product_slider] của v1 → fbm_products. Trả về HTML (được include trong callback shortcode).
 * Biến có sẵn: $atts.
 */
defined( 'ABSPATH' ) || exit;

$fbm_states = FBM\Plugin::get( 'states' );
if ( $fbm_states && 'off' === $fbm_states->get( 'fbm_products' ) ) {
	return FBM\Placeholder::off( 'Products (fbm_product_slider v1)' );
}
$fbm_registry = FBM\Plugin::get( 'registry' );
if ( ! $fbm_registry ) {
	return '';
}

$fbm_v1   = is_array( $atts ) ? $atts : array();
$fbm_html = $fbm_registry->render( 'fbm_products', FBM\Upgrade::map_v1_slider_atts( $fbm_v1 ) );

// Tiêu đề v1 hiển thị bằng [title] của Flatsome. Bỏ dấu nháy và ngoặc vuông để không phá shortcode.
$fbm_clean = static function ( $value ) {
	return trim( str_replace( array( '"', '[', ']' ), '', (string) $value ) );
};
$fbm_title = isset( $fbm_v1['title'] ) ? $fbm_clean( $fbm_v1['title'] ) : '';
if ( '' !== $fbm_title && '' !== trim( $fbm_html ) ) {
	$fbm_link      = isset( $fbm_v1['link_url'] ) ? esc_url_raw( $fbm_clean( $fbm_v1['link_url'] ) ) : '';
	$fbm_link_text = isset( $fbm_v1['link_text'] ) ? $fbm_clean( $fbm_v1['link_text'] ) : '';
	$fbm_short     = '[title text="' . $fbm_title . '"';
	if ( $fbm_link && $fbm_link_text ) {
		$fbm_short .= ' link="' . $fbm_link . '" link_text="' . $fbm_link_text . '"';
	}
	$fbm_html = do_shortcode( $fbm_short . ']' ) . $fbm_html;
}

return $fbm_html;
