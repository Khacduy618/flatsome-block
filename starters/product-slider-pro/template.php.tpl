<?php
/**
 * Product Slider Pro.
 *
 * Lấy sản phẩm bằng WP_Query + taxonomy product_visibility (giống shortcode [products] của WooCommerce),
 * nên tôn trọng "Ẩn khỏi danh mục" và cài đặt "Ẩn hàng hết" của shop.
 * Danh mục / tag nhận ID (từ ô chọn của UX Builder) hoặc slug (gõ tay trong Gutenberg).
 */
if ( ! function_exists( 'wc_get_product' ) ) {
	if ( current_user_can( 'edit_pages' ) ) {
		echo '<p class="fbm-ps-notice">Product Slider Pro cần plugin WooCommerce.</p>';
	}
	return;
}

// "1, 2, ao-thun" -> array( '1', '2', 'ao-thun' ).
$split = static function ( $value ) {
	return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
};
// ID hoặc slug -> slug của term.
$term_slugs = static function ( $value, $taxonomy ) use ( $split ) {
	$slugs = array();
	foreach ( $split( $value ) as $item ) {
		$term = is_numeric( $item ) ? get_term( (int) $item, $taxonomy ) : get_term_by( 'slug', sanitize_title( $item ), $taxonomy );
		if ( $term && ! is_wp_error( $term ) ) {
			$slugs[] = $term->slug;
		}
	}
	return $slugs;
};

/* ---------- Truy vấn ---------- */

$vis_terms  = wc_get_product_visibility_term_ids(); // Không dùng $visibility: đó là option của @commons/advanced.
$tax_query  = array( 'relation' => 'AND' );
$hidden     = array_filter( array( $vis_terms['exclude-from-catalog'] ?? 0 ) );
if ( 'true' === $in_stock || 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ) {
	$hidden[] = $vis_terms['outofstock'] ?? 0;
}
$hidden = array_filter( $hidden );
if ( $hidden ) {
	$tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => $hidden, 'operator' => 'NOT IN' );
}
if ( 'featured' === $source ) {
	$tax_query[] = array( 'taxonomy' => 'product_visibility', 'field' => 'term_taxonomy_id', 'terms' => array( (int) ( $vis_terms['featured'] ?? 0 ) ) );
}
// Đã chọn danh mục / tag mà không tìm thấy (bị xoá, gõ sai slug) -> không hiện sản phẩm nào,
// thay vì bỏ qua bộ lọc và hiện toàn bộ shop.
$no_match  = false;
$cat_slugs = $term_slugs( $cat, 'product_cat' );
if ( $cat_slugs ) {
	$tax_query[] = array( 'taxonomy' => 'product_cat', 'field' => 'slug', 'terms' => $cat_slugs );
} elseif ( $split( $cat ) ) {
	$no_match = true;
}
$tag_slugs = $term_slugs( $tags, 'product_tag' );
if ( $tag_slugs ) {
	$tax_query[] = array( 'taxonomy' => 'product_tag', 'field' => 'slug', 'terms' => $tag_slugs );
} elseif ( $split( $tags ) ) {
	$no_match = true;
}

$meta_query = array();
$min        = '' !== trim( $price_min ) ? (float) $price_min : null;
$max        = '' !== trim( $price_max ) ? (float) $price_max : null;
if ( null !== $min && null !== $max ) {
	$meta_query[] = array( 'key' => '_price', 'value' => array( $min, $max ), 'compare' => 'BETWEEN', 'type' => 'DECIMAL(15,4)' );
} elseif ( null !== $min ) {
	$meta_query[] = array( 'key' => '_price', 'value' => $min, 'compare' => '>=', 'type' => 'DECIMAL(15,4)' );
} elseif ( null !== $max ) {
	$meta_query[] = array( 'key' => '_price', 'value' => $max, 'compare' => '<=', 'type' => 'DECIMAL(15,4)' );
}

$sort_map = array(
	'date'       => array( 'orderby' => 'date' ),
	'title'      => array( 'orderby' => 'title' ),
	'menu_order' => array( 'orderby' => 'menu_order title' ),
	'rand'       => array( 'orderby' => 'rand' ),
	'price'      => array( 'orderby' => 'meta_value_num', 'meta_key' => '_price' ),
	'popularity' => array( 'orderby' => 'meta_value_num', 'meta_key' => 'total_sales' ),
	'rating'     => array( 'orderby' => 'meta_value_num', 'meta_key' => '_wc_average_rating' ),
);
$sort = $sort_map[ $orderby ] ?? $sort_map['date'];
if ( 'best_selling' === $source ) {
	$sort  = $sort_map['popularity'];
	$order = 'DESC';
} elseif ( 'top_rated' === $source ) {
	$sort  = $sort_map['rating'];
	$order = 'DESC';
}

$query_args = array(
	'post_type'           => 'product',
	'post_status'         => 'publish',
	'posts_per_page'      => max( 1, min( 48, (int) $limit ) ),
	'offset'              => max( 0, (int) $offset ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	'fields'              => 'ids',
	'order'               => 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC',
	'tax_query'           => $tax_query, // phpcs:ignore
	'meta_query'          => $meta_query, // phpcs:ignore
) + $sort;

$exclude = array_map( 'intval', array_filter( $split( $exclude ), 'is_numeric' ) );
if ( $exclude ) {
	$query_args['post__not_in'] = $exclude;
}
if ( 'ids' === $source ) {
	$picked                    = array_map( 'intval', array_filter( $split( $ids ), 'is_numeric' ) );
	$query_args['post__in']    = $picked ? $picked : array( 0 );
	$query_args['orderby']     = 'post__in';
	$query_args['offset']      = 0;
	unset( $query_args['meta_key'] );
} elseif ( 'sale' === $source ) {
	$on_sale                = wc_get_product_ids_on_sale();
	$query_args['post__in'] = $on_sale ? $on_sale : array( 0 );
}

$product_ids = $no_match ? array() : ( new WP_Query( $query_args ) )->posts;

/* ---------- Hiển thị ---------- */

$discount = static function ( $product ) {
	if ( ! $product->is_on_sale() ) {
		return 0;
	}
	$best = 0;
	if ( $product->is_type( 'variable' ) ) {
		$prices = $product->get_variation_prices( true );
		foreach ( $prices['regular_price'] as $variation_id => $regular ) {
			$sale = (float) $prices['sale_price'][ $variation_id ];
			if ( (float) $regular > 0 && $sale < (float) $regular ) {
				$best = max( $best, ( (float) $regular - $sale ) / (float) $regular );
			}
		}
	} else {
		$regular = (float) $product->get_regular_price();
		$sale    = (float) $product->get_sale_price();
		if ( $regular > 0 && $sale > 0 && $sale < $regular ) {
			$best = ( $regular - $sale ) / $regular;
		}
	}
	return (int) round( $best * 100 );
};

$cart_button = static function ( $product ) use ( $button_text ) {
	$ready   = $product->is_purchasable() && $product->is_in_stock();
	$ajax    = $ready && $product->supports( 'ajax_add_to_cart' );
	$classes = array( 'fbm-ps__btn', 'button', 'primary', 'is-small', 'product_type_' . $product->get_type() );
	if ( $ready ) {
		$classes[] = 'add_to_cart_button';
	}
	if ( $ajax ) {
		$classes[] = 'ajax_add_to_cart';
	}
	$label = $ajax && '' !== trim( $button_text ) ? $button_text : $product->add_to_cart_text();
	return sprintf(
		'<a href="%1$s" data-quantity="1" data-product_id="%2$d" data-product_sku="%3$s" class="%4$s" aria-label="%5$s" rel="nofollow">%6$s</a>',
		esc_url( $product->add_to_cart_url() ),
		$product->get_id(),
		esc_attr( $product->get_sku() ),
		esc_attr( implode( ' ', $classes ) ),
		esc_attr( wp_strip_all_tags( $product->add_to_cart_description() ) ),
		esc_html( $label )
	);
};

$size    = in_array( $image_size, array( 'woocommerce_thumbnail', 'medium', 'large' ), true ) ? $image_size : 'woocommerce_thumbnail';
$ratio   = preg_match( '#^\d+/\d+$#', $image_ratio ) ? str_replace( '/', ' / ', $image_ratio ) : 'auto';
$classes = array( 'fbm-ps', 'fbm-ps--' . sanitize_html_class( $card_style ), 'fbm-ps--' . ( 'center' === $text_align ? 'center' : 'left' ) );
if ( $class ) {
	$classes[] = $class;
}
if ( $visibility ) {
	$classes[] = $visibility;
}
$style = sprintf(
	'--ps-pv:%d;--ps-pv-md:%d;--ps-pv-sm:%d;--ps-gap:%dpx;--ps-ratio:%s;--ps-radius:%dpx;--ps-lines:%d;%s',
	max( 1, min( 6, (int) $per_view ) ),
	max( 1, min( 4, (int) $per_view_md ) ),
	max( 1, min( 3, (int) $per_view_sm ) ),
	max( 0, (int) $gap ),
	$ratio,
	max( 0, (int) $radius ),
	max( 1, min( 3, (int) $title_lines ) ),
	$accent ? '--ps-accent:' . $accent . ';' : ''
);
$total = count( $product_ids );
?>
<section id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" style="<?php echo esc_attr( $style ); ?>"
	data-loop="<?php echo esc_attr( $loop ); ?>" data-slide-by="<?php echo esc_attr( 'page' === $slide_by ? 'page' : '1' ); ?>"
	data-autoplay="<?php echo esc_attr( 'true' === $autoplay ? max( 2000, (int) $autoplay_ms ) : 0 ); ?>"
	aria-roledescription="carousel" aria-label="<?php echo esc_attr( $title ? $title : __( 'Sản phẩm', 'flatsome-block-manager' ) ); ?>">

	<?php if ( $title || ( $link_text && $link_url ) ) : ?>
		<header class="fbm-ps__head">
			<?php if ( $title ) : ?>
				<h3 class="fbm-ps__title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>
			<?php if ( $link_text && $link_url ) : ?>
				<a class="fbm-ps__more" href="<?php echo esc_url( $link_url ); ?>"><?php echo esc_html( $link_text ); ?> &rarr;</a>
			<?php endif; ?>
		</header>
	<?php endif; ?>

	<?php if ( ! $total ) : ?>
		<?php if ( $empty_text ) : ?>
			<p class="fbm-ps__empty"><?php echo esc_html( $empty_text ); ?></p>
		<?php endif; ?>
	<?php else : ?>
		<div class="fbm-ps__viewport">
			<ul class="fbm-ps__track" tabindex="0">
				<?php foreach ( $product_ids as $index => $product_id ) : ?>
					<?php
					$product = wc_get_product( $product_id );
					if ( ! $product ) {
						continue;
					}
					$link    = $product->get_permalink();
					$percent = 'true' === $show_badge ? $discount( $product ) : 0;
					$gallery = 'true' === $hover_image ? $product->get_gallery_image_ids() : array();
					$terms   = 'true' === $show_cat ? get_the_terms( $product_id, 'product_cat' ) : array();
					?>
					<li class="fbm-ps__slide" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $index + 1 ) . ' / ' . $total ); ?>">
						<article class="fbm-ps__card">
							<a class="fbm-ps__media<?php echo $gallery ? ' has-hover' : ''; ?>" href="<?php echo esc_url( $link ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo $product->get_image( $size, array( 'class' => 'fbm-ps__img', 'loading' => 'lazy' ) ); // phpcs:ignore ?>
								<?php if ( $gallery ) : ?>
									<?php echo wp_get_attachment_image( (int) $gallery[0], $size, false, array( 'class' => 'fbm-ps__img fbm-ps__img--hover', 'loading' => 'lazy', 'alt' => '' ) ); // phpcs:ignore ?>
								<?php endif; ?>
								<?php if ( 'true' === $show_badge && ! $product->is_in_stock() ) : ?>
									<span class="fbm-ps__badge fbm-ps__badge--out"><?php esc_html_e( 'Hết hàng', 'flatsome-block-manager' ); ?></span>
								<?php elseif ( $percent > 0 ) : ?>
									<span class="fbm-ps__badge">-<?php echo (int) $percent; ?>%</span>
								<?php elseif ( 'true' === $show_badge && $product->is_on_sale() ) : ?>
									<span class="fbm-ps__badge"><?php esc_html_e( 'Giảm giá', 'flatsome-block-manager' ); ?></span>
								<?php endif; ?>
							</a>
							<div class="fbm-ps__body">
								<?php if ( $terms && ! is_wp_error( $terms ) ) : ?>
									<span class="fbm-ps__cat"><?php echo esc_html( $terms[0]->name ); ?></span>
								<?php endif; ?>
								<h4 class="fbm-ps__name"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h4>
								<?php if ( 'true' === $show_rating && $product->get_rating_count() ) : ?>
									<div class="fbm-ps__rating"><?php echo wc_get_rating_html( $product->get_average_rating(), $product->get_rating_count() ); // phpcs:ignore ?></div>
								<?php endif; ?>
								<?php if ( 'true' === $show_price ) : ?>
									<div class="fbm-ps__price price"><?php echo $product->get_price_html(); // phpcs:ignore ?></div>
								<?php endif; ?>
								<?php if ( 'true' === $show_button ) : ?>
									<?php echo $cart_button( $product ); // phpcs:ignore ?>
								<?php endif; ?>
							</div>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( 'true' === $arrows ) : ?>
				<button type="button" class="fbm-ps__arrow fbm-ps__arrow--prev" aria-label="<?php esc_attr_e( 'Sản phẩm trước', 'flatsome-block-manager' ); ?>">&#8249;</button>
				<button type="button" class="fbm-ps__arrow fbm-ps__arrow--next" aria-label="<?php esc_attr_e( 'Sản phẩm sau', 'flatsome-block-manager' ); ?>">&#8250;</button>
			<?php endif; ?>
		</div>
		<?php if ( 'true' === $dots ) : ?>
			<div class="fbm-ps__dots" aria-label="<?php esc_attr_e( 'Chọn trang', 'flatsome-block-manager' ); ?>"></div>
		<?php endif; ?>
	<?php endif; ?>
</section>
