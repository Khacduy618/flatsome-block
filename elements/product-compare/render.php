<?php
/**
 * [fbm_product_compare]: bảng so sánh 2–4 sản phẩm. <table> có caption, th scope; mobile cuộn ngang trong khung riêng.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'_id'              => 'fbm-compare-' . wp_rand(),
		'title'            => '',
		'source'           => 'ids',
		'ids'              => '',
		'show_image'       => 'true',
		'show_price'       => 'true',
		'show_rating'      => 'true',
		'show_stock'       => 'true',
		'show_attributes'  => 'true',
		'attributes'       => '',
		'show_add_to_cart' => 'true',
		'only_diff'        => 'false',
		'class'            => '',
		'visibility'       => '',
	),
	$atts,
	$tag
);

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

// Danh sách sản phẩm hợp lệ (đã đăng, hiển thị), tối đa 4.
$fbm_ids = array();
if ( 'current_upsell' === $a['source'] ) {
	$fbm_base = isset( $GLOBALS['product'] ) && $GLOBALS['product'] instanceof WC_Product ? $GLOBALS['product'] : null;
	if ( ! $fbm_base && function_exists( 'is_product' ) && is_product() ) {
		$fbm_base = wc_get_product( get_queried_object_id() );
	}
	if ( $fbm_base ) {
		$fbm_ids = array_merge( array( $fbm_base->get_id() ), $fbm_base->get_upsell_ids() );
	}
} else {
	$fbm_ids = array_map( 'intval', array_filter( array_map( 'trim', explode( ',', $a['ids'] ) ), 'is_numeric' ) );
}
$products = array();
foreach ( array_unique( $fbm_ids ) as $fbm_id ) {
	$fbm_p = wc_get_product( $fbm_id );
	if ( $fbm_p && 'publish' === $fbm_p->get_status() && $fbm_p->is_visible() ) {
		$products[] = $fbm_p;
	}
	if ( count( $products ) >= 4 ) {
		break;
	}
}

if ( count( $products ) < 2 ) {
	echo FBM\Placeholder::empty( __( 'Product Compare: cần ít nhất 2 sản phẩm đang bán để so sánh.', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

$fbm_dash = '—';
$rows     = array(); // [ label, cells[], comparable ]

if ( 'true' === $a['show_image'] ) {
	$rows[] = array( __( 'Ảnh', 'flatsome-block-manager' ), array_map( static function ( $p ) {
		return '<a href="' . esc_url( $p->get_permalink() ) . '" tabindex="-1" aria-hidden="true">' . $p->get_image( 'woocommerce_thumbnail' ) . '</a>';
	}, $products ), false );
}
if ( 'true' === $a['show_price'] ) {
	$rows[] = array( __( 'Giá', 'flatsome-block-manager' ), array_map( static function ( $p ) use ( $fbm_dash ) {
		$html = $p->get_price_html();
		return $html ? wp_kses_post( $html ) : $fbm_dash;
	}, $products ), true );
}
if ( 'true' === $a['show_rating'] ) {
	$rows[] = array( __( 'Đánh giá', 'flatsome-block-manager' ), array_map( static function ( $p ) use ( $fbm_dash ) {
		$avg = (float) $p->get_average_rating();
		return $avg > 0 ? wc_get_rating_html( $avg, $p->get_rating_count() ) : $fbm_dash;
	}, $products ), true );
}
if ( 'true' === $a['show_stock'] ) {
	$rows[] = array( __( 'Tình trạng', 'flatsome-block-manager' ), array_map( static function ( $p ) {
		return $p->is_in_stock() ? esc_html__( 'Còn hàng', 'flatsome-block-manager' ) : esc_html__( 'Hết hàng', 'flatsome-block-manager' );
	}, $products ), true );
}
if ( 'true' === $a['show_attributes'] ) {
	$fbm_wanted = array_filter( array_map( 'trim', explode( ',', mb_strtolower( $a['attributes'] ) ) ), 'strlen' );
	$fbm_by     = array(); // label => [ product index => "a, b" ]
	foreach ( $products as $i => $p ) {
		foreach ( FBM\Products\Attributes::of( $p ) as $item ) {
			if ( $fbm_wanted && ! in_array( mb_strtolower( $item['label'] ), $fbm_wanted, true ) && ! in_array( mb_strtolower( $item['name'] ), $fbm_wanted, true ) ) {
				continue;
			}
			$fbm_by[ $item['label'] ][ $i ] = esc_html( implode( ', ', $item['values'] ) );
		}
	}
	foreach ( $fbm_by as $label => $cells ) {
		$row = array();
		foreach ( array_keys( $products ) as $i ) {
			$row[] = isset( $cells[ $i ] ) ? $cells[ $i ] : $fbm_dash;
		}
		$rows[] = array( $label, $row, true );
	}
}
if ( 'true' === $a['only_diff'] ) {
	$rows = array_values( array_filter( $rows, static function ( $r ) {
		return ! $r[2] || count( array_unique( array_map( 'wp_strip_all_tags', $r[1] ) ) ) > 1;
	} ) );
}
if ( 'true' === $a['show_add_to_cart'] ) {
	$fbm_buttons = array();
	global $post, $product;
	$fbm_prev = array( $post, $product );
	foreach ( $products as $p ) {
		$post    = get_post( $p->get_id() ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$product = $p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		ob_start();
		woocommerce_template_loop_add_to_cart();
		$fbm_buttons[] = ob_get_clean();
	}
	list( $post, $product ) = $fbm_prev; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$rows[] = array( '<span class="screen-reader-text">' . esc_html__( 'Mua', 'flatsome-block-manager' ) . '</span>', $fbm_buttons, false );
}

$classes = array( 'fbm-compare' );
foreach ( preg_split( '/\s+/', $a['class'] . ' ' . $a['visibility'], -1, PREG_SPLIT_NO_EMPTY ) as $c ) {
	$classes[] = sanitize_html_class( $c );
}
$caption = '' !== trim( $a['title'] ) ? $a['title'] : __( 'So sánh sản phẩm', 'flatsome-block-manager' );
?>
<div id="<?php echo esc_attr( sanitize_html_class( $a['_id'] ) ); ?>" class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<div class="fbm-compare__scroll" role="region" tabindex="0" aria-label="<?php echo esc_attr( $caption ); ?>">
		<table class="fbm-compare__table">
			<caption class="fbm-compare__caption<?php echo '' === trim( $a['title'] ) ? ' screen-reader-text' : ''; ?>"><?php echo esc_html( $caption ); ?></caption>
			<thead>
				<tr>
					<td class="fbm-compare__corner"></td>
					<?php foreach ( $products as $p ) : ?>
						<th scope="col" class="fbm-compare__product"><a href="<?php echo esc_url( $p->get_permalink() ); ?>"><?php echo esc_html( $p->get_name() ); ?></a></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<tr>
						<th scope="row" class="fbm-compare__label"><?php echo wp_kses_post( $r[0] ); ?></th>
						<?php foreach ( $r[1] as $cell ) : ?>
							<td><?php echo $cell; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- mỗi ô đã escape ở trên hoặc là markup của WooCommerce. ?></td>
						<?php endforeach; ?>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
<?php
