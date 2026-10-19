<?php
/**
 * Dữ liệu QA thủ công (spec §7.2) trên DDEV. Không phải test tự động.
 *
 * ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/qa-seed.php up
 * ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/qa-seed.php down
 *
 * Mọi post tạo ra có meta `_fbm_qa`; `down` dọn bằng fbm_fixtures_down().
 *
 * @package FBM
 */

require_once __DIR__ . '/fixtures.php';

$fbm_action = isset( $args[0] ) ? (string) $args[0] : 'up';

if ( 'down' === $fbm_action ) {
	fbm_fixtures_down();
	echo "down\n";
	return;
}

$f = fbm_fixtures_up();

// Ảnh cho các sản phẩm (trừ noimage) để trang QA giống thật.
$fbm_images = array( 22, 24, 26, 28, 30 );
$i          = 0;
foreach ( array( 'simple', 'sale', 'outofstock', 'attrs', 'variable', 'hidden' ) as $key ) {
	$p = wc_get_product( $f[ $key ] );
	$p->set_image_id( $fbm_images[ $i++ % count( $fbm_images ) ] );
	$p->save();
}

// Biến thể thứ hai, giá khác theo bản rộng.
$v = new WC_Product_Variable();
$v->set_name( 'FBM QA variable 2' );
$v->set_status( 'publish' );
$v->set_image_id( 24 );
$v->set_attributes( array( fbm_qa_attribute( 'ban-rong', array( '20mm', '22mm' ) ) ) );
$vid = $v->save();
foreach ( array( '20mm' => '85', '22mm' => '95' ) as $term => $price ) {
	$var = new WC_Product_Variation();
	$var->set_parent_id( $vid );
	$var->set_attributes( array( wc_attribute_taxonomy_name( 'ban-rong' ) => sanitize_title( $term ) ) );
	$var->set_regular_price( $price );
	$var->save();
}
WC_Product_Variable::sync( $vid );
update_post_meta( $vid, '_fbm_qa', 1 );
$f['variable2'] = $vid;

// Cross-sell / upsell cho 3 sản phẩm.
$links = array(
	'simple' => array( array( $f['sale'], $f['variable'] ), array( $f['noimage'], $f['attrs'] ) ),
	'sale'   => array( array( $f['simple'], $f['variable2'] ), array( $f['attrs'] ) ),
	'attrs'  => array( array( $f['variable'] ), array( $f['simple'], $f['sale'] ) ),
);
foreach ( $links as $key => $pair ) {
	$p = wc_get_product( $f[ $key ] );
	$p->set_upsell_ids( $pair[0] );
	$p->set_cross_sell_ids( $pair[1] );
	$p->save();
}

// 10 đánh giá, 1–5 sao, có / không verified.
$ratings = array( 5, 4, 5, 3, 1, 5, 4, 2, 5, 4 );
$targets = array( 'simple', 'simple', 'sale', 'sale', 'sale', 'attrs', 'attrs', 'variable', 'variable', 'simple' );
foreach ( $ratings as $n => $rating ) {
	$cid = wp_insert_comment(
		array(
			'comment_post_ID'  => $f[ $targets[ $n ] ],
			'comment_author'   => 'QA ' . ( $n + 1 ),
			'comment_content'  => 'Đánh giá QA số ' . ( $n + 1 ),
			'comment_type'     => 'review',
			'comment_approved' => 1,
		)
	);
	update_comment_meta( $cid, 'rating', $rating );
	update_comment_meta( $cid, 'verified', $n % 2 ? 0 : 1 );
}
foreach ( array_unique( $targets ) as $key ) {
	WC_Comments::clear_transients( $f[ $key ] );
}

// Trang chứa 6 element để kiểm ở 375 / 768 / 1280.
$content = '[section][row][col span__sm="12"]'
	. '[title text="Products slider"][fbm_products source="recent" products="6" type="slider"]'
	. '[title text="Products cross-sell (giỏ hàng)"][fbm_products source="cross_sell" type="row" columns="4"]'
	. '[title text="Products rỗng"][fbm_products source="ids" ids="999999"]'
	. '[title text="Attributes"][handdn_product_attributes id="' . $f['attrs'] . '"]'
	. '[title text="Compare"][fbm_product_compare ids="' . $f['simple'] . ',' . $f['sale'] . ',' . $f['variable'] . ',' . $f['attrs'] . '"]'
	. '[title text="Reviews"][fbm_reviews widget="jgm-all-reviews"]'
	. '[title text="Before / After"][fbm_before_after before="30" after="28" label_before="Mới" label_after="6 tháng"]'
	. '[fbm_ba_carousel columns="2" columns__sm="1"][fbm_before_after before="30" after="28"][fbm_before_after before="28" after="30"][fbm_before_after before="26" after="22"][/fbm_ba_carousel]'
	. '[/col][/row][/section]';
$f['page'] = fbm_qa_post( 'page', 'QA all elements', $content, 'fbm-qa-all' );

echo wp_json_encode( $f ) . "\n";
