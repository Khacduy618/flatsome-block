<?php
/**
 * AJAX Backend Handler for Smart Product Slider tabs
 * Handles diversified sources, sorting criteria, and product filters per tab.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Render native Flatsome product slider markup using Flickity.
 *
 * @param array $args Query and slider configuration options.
 * @return string HTML markup of the slider.
 */
function uxb_render_product_slider_markup( array $args ) {
	if ( ! function_exists( 'WC' ) ) {
		return '<div class="uxb-no-products"><p>' . esc_html__( 'WooCommerce is not active.', 'flatsome-block-manager' ) . '</p></div>';
	}

	$defaults = array(
		'cat'              => '',
		'cat_id'           => '',
		'posts_per_page'   => 8,
		'source'           => 'recent',
		'orderby'          => 'date',
		'order'            => 'DESC',
		'in_stock'         => 'false',
		'price_min'        => '',
		'price_max'        => '',
		'tags'             => '',
		'exclude'          => '',
		'columns'          => 4,
		'columns__md'      => 3,
		'columns__sm'      => 2,
		'autoplay_speed'   => 2000,
		'slider_nav_style' => 'reveal',
		'slider_bullets'   => 'false',
		'animate'          => 'none',
	);

	$p = wp_parse_args( $args, $defaults );

	$animate        = ! empty( $p['animate'] ) && 'none' !== $p['animate'] ? preg_replace( '/[^a-zA-Z0-9_-]/', '', $p['animate'] ) : '';
	$cat            = ! empty( $p['cat'] ) ? (string) $p['cat'] : ( ! empty( $p['cat_id'] ) ? (string) $p['cat_id'] : '' );
	$posts_per_page = max( 1, min( 48, absint( $p['posts_per_page'] ) ) );
	$columns        = max( 1, min( 6, absint( $p['columns'] ) ) );
	$columns_md     = max( 1, min( 4, absint( $p['columns__md'] ? $p['columns__md'] : 3 ) ) );
	$columns_sm     = max( 1, min( 3, absint( $p['columns__sm'] ? $p['columns__sm'] : 2 ) ) );
	$autoplay_speed = max( 500, absint( $p['autoplay_speed'] ? $p['autoplay_speed'] : 2000 ) );

	// Execute optimized product query using FBM\Products\Query
	$ids = \FBM\Products\Query::ids(
		array(
			'source'    => $p['source'],
			'cat'       => $cat,
			'tags'      => $p['tags'],
			'products'  => $posts_per_page,
			'orderby'   => $p['orderby'],
			'order'     => $p['order'],
			'price_min' => $p['price_min'],
			'price_max' => $p['price_max'],
			'in_stock'  => $p['in_stock'],
			'exclude'   => $p['exclude'],
		)
	);

	if ( empty( $ids ) ) {
		return '<div class="uxb-no-products"><p>' . esc_html__( 'No products found matching the criteria.', 'flatsome-block-manager' ) . '</p></div>';
	}

	$row_classes = array(
		'row',
		'row-small',
		'slider',
		'row-slider',
		'slider-nav-' . sanitize_html_class( $p['slider_nav_style'] ? $p['slider_nav_style'] : 'reveal' ),
		'slider-nav-push',
		'large-columns-' . $columns,
		'medium-columns-' . $columns_md,
		'small-columns-' . $columns_sm,
	);

	$slider_options = wp_json_encode(
		array(
			'imagesLoaded'    => true,
			'groupCells'      => false, // Move cell-by-cell (1 item at a time)
			'dragThreshold'   => 5,
			'cellAlign'       => 'left',
			'wrapAround'      => true,
			'prevNextButtons' => true,
			'percentPosition' => true,
			'pageDots'        => ( 'true' === (string) $p['slider_bullets'] ),
			'rightToLeft'     => is_rtl(),
			'autoPlay'        => $autoplay_speed,
		)
	);

	global $post, $product;
	$prev_post    = $post;
	$prev_product = $product;

	_prime_post_caches( array_map( 'intval', $ids ), true, true );

	ob_start();
	echo '<div class="' . esc_attr( implode( ' ', array_filter( $row_classes ) ) ) . '" data-flickity-options=\'' . esc_attr( $slider_options ) . '\'>';

	foreach ( $ids as $id ) {
		$post = get_post( (int) $id );
		if ( ! $post ) {
			continue;
		}
		setup_postdata( $post );
		wc_setup_product_data( $post );
		wc_get_template_part( 'content', 'product' );
	}

	echo '</div>';
	wp_reset_postdata();

	$post    = $prev_post;
	$product = $prev_product;
	if ( $post ) {
		setup_postdata( $post );
	}

	$output = ob_get_clean();

	return $output ? $output : '<div class="uxb-no-products"><p>' . esc_html__( 'No products available.', 'flatsome-block-manager' ) . '</p></div>';
}

/**
 * Handle AJAX request when user clicks on an unpopulated tab.
 */
function uxb_ajax_load_tab_products() {
	$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'uxb_smart_slider_nonce' ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Security check failed. Please refresh the page.', 'flatsome-block-manager' ),
			),
			403
		);
	}

	$cat = isset( $_POST['cat'] ) ? sanitize_text_field( wp_unslash( $_POST['cat'] ) ) : '';
	if ( empty( $cat ) && isset( $_POST['cat_id'] ) ) {
		$cat = sanitize_text_field( wp_unslash( $_POST['cat_id'] ) );
	}

	$params = array(
		'cat'              => $cat,
		'posts_per_page'   => isset( $_POST['posts_per_page'] ) ? absint( $_POST['posts_per_page'] ) : 8,
		'source'           => isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'recent',
		'orderby'          => isset( $_POST['orderby'] ) ? sanitize_key( wp_unslash( $_POST['orderby'] ) ) : 'date',
		'order'            => isset( $_POST['order'] ) ? sanitize_key( wp_unslash( $_POST['order'] ) ) : 'DESC',
		'in_stock'         => isset( $_POST['in_stock'] ) ? sanitize_text_field( wp_unslash( $_POST['in_stock'] ) ) : 'false',
		'price_min'        => isset( $_POST['price_min'] ) ? sanitize_text_field( wp_unslash( $_POST['price_min'] ) ) : '',
		'price_max'        => isset( $_POST['price_max'] ) ? sanitize_text_field( wp_unslash( $_POST['price_max'] ) ) : '',
		'tags'             => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
		'exclude'          => isset( $_POST['exclude'] ) ? sanitize_text_field( wp_unslash( $_POST['exclude'] ) ) : '',
		'columns'          => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 4,
		'columns__md'      => isset( $_POST['columns__md'] ) ? absint( $_POST['columns__md'] ) : 3,
		'columns__sm'      => isset( $_POST['columns__sm'] ) ? absint( $_POST['columns__sm'] ) : 2,
		'autoplay_speed'   => isset( $_POST['autoplay_speed'] ) ? absint( $_POST['autoplay_speed'] ) : 2000,
		'slider_nav_style' => isset( $_POST['slider_nav_style'] ) ? sanitize_key( wp_unslash( $_POST['slider_nav_style'] ) ) : 'reveal',
		'slider_bullets'   => isset( $_POST['slider_bullets'] ) ? sanitize_text_field( wp_unslash( $_POST['slider_bullets'] ) ) : 'false',
		'animate'          => isset( $_POST['animate'] ) ? preg_replace( '/[^a-zA-Z0-9_-]/', '', wp_unslash( $_POST['animate'] ) ) : 'none',
		'animate_speed'    => isset( $_POST['animate_speed'] ) ? floatval( wp_unslash( $_POST['animate_speed'] ) ) : 0.7,
	);

	$html = uxb_render_product_slider_markup( $params );

	wp_send_json_success(
		array(
			'html' => $html,
			'cat'  => $cat,
		)
	);
}

add_action( 'wp_ajax_uxb_load_tab_products', 'uxb_ajax_load_tab_products' );
add_action( 'wp_ajax_nopriv_uxb_load_tab_products', 'uxb_ajax_load_tab_products' );
