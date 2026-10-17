<?php
/**
 * Truy vấn ID sản phẩm cho element (dùng chung: Products, Cross-sell, Compare).
 *
 * WP_Query + taxonomy product_visibility giống shortcode [products] của WooCommerce,
 * nên tôn trọng "Ẩn khỏi danh mục" và cài đặt "Ẩn hàng hết" của shop.
 *
 * @package FBM
 */

namespace FBM\Products;

defined( 'ABSPATH' ) || exit;

final class Query {

	const MAX = 48;

	const SOURCES = array( 'recent', 'featured', 'sale', 'best_selling', 'top_rated', 'ids', 'cross_sell', 'upsell', 'related', 'auto' );

	const RELATION_SOURCES = array( 'cross_sell', 'upsell', 'related' );

	/**
	 * @param array $atts source, ids, cat, tags, exclude, products, offset, orderby, order,
	 *                    price_min, price_max, in_stock, product (ID gốc cho nguồn quan hệ).
	 * @return int[]
	 */
	public static function ids( array $atts ) {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}
		$a = wp_parse_args(
			$atts,
			array(
				'source'    => 'recent',
				'ids'       => '',
				'cat'       => '',
				'tags'      => '',
				'exclude'   => '',
				'products'  => 8,
				'offset'    => 0,
				'orderby'   => 'date',
				'order'     => 'DESC',
				'price_min' => '',
				'price_max' => '',
				'in_stock'  => 'false',
				'product'   => 0,
			)
		);
		$source = in_array( $a['source'], self::SOURCES, true ) ? $a['source'] : 'recent';
		$limit  = max( 1, min( self::MAX, (int) $a['products'] ) );

		$args = self::base_args( $a, $limit );
		if ( null === $args ) {
			return array(); // Danh mục / tag đã chọn nhưng không tồn tại.
		}

		if ( 'auto' === $source || in_array( $source, self::RELATION_SOURCES, true ) ) {
			$related = self::related_ids( 'auto' === $source ? self::RELATION_SOURCES : array( $source ), (int) $a['product'], $limit );
			if ( ! $related ) {
				return array();
			}
			$args['post__in'] = $related;
			$args['orderby']  = 'post__in';
			$args['offset']   = 0;
			unset( $args['meta_key'] );
		} elseif ( 'ids' === $source ) {
			$picked           = array_map( 'intval', array_filter( self::split( $a['ids'] ), 'is_numeric' ) );
			$args['post__in'] = $picked ? $picked : array( 0 );
			$args['orderby']  = 'post__in';
			$args['offset']   = 0;
			unset( $args['meta_key'] );
		} elseif ( 'sale' === $source ) {
			$on_sale          = wc_get_product_ids_on_sale();
			$args['post__in'] = $on_sale ? array_map( 'intval', $on_sale ) : array( 0 );
		} elseif ( 'featured' === $source ) {
			$vis                 = wc_get_product_visibility_term_ids();
			$args['tax_query'][] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( (int) ( isset( $vis['featured'] ) ? $vis['featured'] : 0 ) ),
			);
		} elseif ( 'best_selling' === $source ) {
			$args = array_merge( $args, array( 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales', 'order' => 'DESC' ) ); // phpcs:ignore
		} elseif ( 'top_rated' === $source ) {
			$args = array_merge( $args, array( 'orderby' => 'meta_value_num', 'meta_key' => '_wc_average_rating', 'order' => 'DESC' ) ); // phpcs:ignore
		}

		return array_map( 'intval', ( new \WP_Query( $args ) )->posts );
	}

	/**
	 * @return array|null null khi danh mục / tag đã chọn không tìm thấy.
	 */
	private static function base_args( array $a, $limit ) {
		$vis       = wc_get_product_visibility_term_ids();
		$tax_query = array( 'relation' => 'AND' );
		$hidden    = array( isset( $vis['exclude-from-catalog'] ) ? (int) $vis['exclude-from-catalog'] : 0 );
		if ( 'true' === (string) $a['in_stock'] || 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
			$hidden[] = isset( $vis['outofstock'] ) ? (int) $vis['outofstock'] : 0;
		}
		$hidden = array_values( array_filter( $hidden ) );
		if ( $hidden ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $hidden,
				'operator' => 'NOT IN',
			);
		}

		foreach ( array( 'cat' => 'product_cat', 'tags' => 'product_tag' ) as $key => $taxonomy ) {
			$wanted = self::split( $a[ $key ] );
			if ( ! $wanted ) {
				continue;
			}
			$slugs = self::term_slugs( $wanted, $taxonomy );
			if ( ! $slugs ) {
				return null;
			}
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $slugs,
			);
		}

		$meta_query = array();
		$min        = '' !== trim( (string) $a['price_min'] ) ? (float) $a['price_min'] : null;
		$max        = '' !== trim( (string) $a['price_max'] ) ? (float) $a['price_max'] : null;
		if ( null !== $min && null !== $max ) {
			$meta_query[] = array( 'key' => '_price', 'value' => array( $min, $max ), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(15,4)' );
		} elseif ( null !== $min ) {
			$meta_query[] = array( 'key' => '_price', 'value' => $min, 'compare' => '>=', 'type' => 'DECIMAL(15,4)' );
		} elseif ( null !== $max ) {
			$meta_query[] = array( 'key' => '_price', 'value' => $max, 'compare' => '<=', 'type' => 'DECIMAL(15,4)' );
		}

		$sorts = array(
			'date'       => array( 'orderby' => 'date' ),
			'title'      => array( 'orderby' => 'title' ),
			'menu_order' => array( 'orderby' => 'menu_order title' ),
			'rand'       => array( 'orderby' => 'rand' ),
			'price'      => array( 'orderby' => 'meta_value_num', 'meta_key' => '_price' ), // phpcs:ignore
			'popularity' => array( 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales' ), // phpcs:ignore
			'rating'     => array( 'orderby' => 'meta_value_num', 'meta_key' => '_wc_average_rating' ), // phpcs:ignore
		);
		$sort = isset( $sorts[ $a['orderby'] ] ) ? $sorts[ $a['orderby'] ] : $sorts['date'];

		$args = array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'offset'              => max( 0, (int) $a['offset'] ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'fields'              => 'ids',
			'order'               => 'ASC' === strtoupper( (string) $a['order'] ) ? 'ASC' : 'DESC',
			'tax_query'           => $tax_query, // phpcs:ignore
			'meta_query'          => $meta_query, // phpcs:ignore
		) + $sort;

		$exclude = array_map( 'intval', array_filter( self::split( $a['exclude'] ), 'is_numeric' ) );
		if ( $exclude ) {
			$args['post__not_in'] = $exclude;
		}
		return $args;
	}

	/**
	 * Sản phẩm liên quan: của sản phẩm gốc, hoặc của giỏ hàng khi không có sản phẩm gốc.
	 * Lấy nguồn đầu tiên có dữ liệu theo thứ tự $sources.
	 *
	 * @return int[]
	 */
	private static function related_ids( array $sources, $product_id, $limit ) {
		$bases = array();
		if ( $product_id ) {
			$bases = array( $product_id );
		} elseif ( function_exists( 'is_product' ) && is_product() ) {
			$bases = array( (int) get_queried_object_id() );
		} elseif ( function_exists( 'WC' ) && WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				$bases[] = (int) $item['product_id'];
			}
		}
		$bases = array_values( array_unique( array_filter( $bases ) ) );
		if ( ! $bases ) {
			return array();
		}

		foreach ( $sources as $source ) {
			$ids = array();
			foreach ( $bases as $base ) {
				$product = wc_get_product( $base );
				if ( ! $product ) {
					continue;
				}
				if ( 'cross_sell' === $source ) {
					$ids = array_merge( $ids, $product->get_cross_sell_ids() );
				} elseif ( 'upsell' === $source ) {
					$ids = array_merge( $ids, $product->get_upsell_ids() );
				} else {
					$ids = array_merge( $ids, wc_get_related_products( $base, $limit, $bases ) );
				}
			}
			$ids = array_values( array_diff( array_unique( array_map( 'intval', $ids ) ), $bases ) );
			if ( $ids ) {
				return $ids;
			}
		}
		return array();
	}

	/** "1, 2, ao-thun" -> array( '1', '2', 'ao-thun' ). */
	private static function split( $value ) {
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
	}

	/** ID hoặc slug -> slug của term (bỏ term không tồn tại). */
	private static function term_slugs( array $items, $taxonomy ) {
		$slugs = array();
		foreach ( $items as $item ) {
			$term = is_numeric( $item ) ? get_term( (int) $item, $taxonomy ) : get_term_by( 'slug', sanitize_title( $item ), $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				$slugs[] = $term->slug;
			}
		}
		return $slugs;
	}
}
