<?php
/**
 * In danh sách sản phẩm bằng khung repeater của Flatsome + template thẻ sản phẩm của WooCommerce
 * (content-product.php), nên mọi hook woocommerce_*_shop_loop_item và CSS của site vẫn áp dụng.
 *
 * @package FBM
 */

namespace FBM\Products;

defined( 'ABSPATH' ) || exit;

final class Loop {

	/**
	 * @param int[] $ids
	 * @param array $repeater Khoá theo ux_products.php của Flatsome (type, columns, columns__md, columns__sm, row_spacing, slider_*...).
	 * @return string
	 */
	public static function render( array $ids, array $repeater ) {
		if ( ! $ids || ! function_exists( 'get_flatsome_repeater_start' ) ) {
			return '';
		}
		$repeater = wp_parse_args( $repeater, array( 'type' => 'row', 'columns' => '4' ) );

		global $post, $product;
		$prev_post    = $post;
		$prev_product = $product;

		ob_start();
		get_flatsome_repeater_start( $repeater );
		foreach ( $ids as $id ) {
			$post = get_post( (int) $id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			if ( ! $post ) {
				continue;
			}
			setup_postdata( $post );
			wc_setup_product_data( $post );
			wc_get_template_part( 'content', 'product' );
		}
		get_flatsome_repeater_end( $repeater );
		$html = ob_get_clean();

		$post    = $prev_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product = $prev_product; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		if ( $post ) {
			setup_postdata( $post );
		} else {
			wp_reset_postdata();
		}
		return $html;
	}
}
