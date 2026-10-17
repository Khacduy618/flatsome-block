<?php
/**
 * Thuộc tính hiển thị của sản phẩm (đã tick "Visible on the product page"), dùng chung cho
 * Product Attributes và Product Compare.
 *
 * @package FBM
 */

namespace FBM\Products;

defined( 'ABSPATH' ) || exit;

final class Attributes {

	/**
	 * @param \WC_Product $product
	 * @return array<int, array{name: string, label: string, values: string[]}>
	 */
	public static function of( $product ) {
		$items = array();
		if ( ! $product instanceof \WC_Product ) {
			return $items;
		}
		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}
			$name = $attribute->get_name();
			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) ); // Giữ thứ tự term.
				if ( is_wp_error( $values ) ) {
					continue;
				}
			} else {
				$values = $attribute->get_options();
			}
			$values = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $values ) ), 'strlen' ) );
			if ( ! $values ) {
				continue;
			}
			$items[] = array(
				'name'   => $name,
				'label'  => wc_attribute_label( $name, $product ),
				'values' => $values,
			);
		}
		return $items;
	}
}
