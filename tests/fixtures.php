<?php
/**
 * Dữ liệu test trên DDEV. Mọi post tạo ra có meta `_fbm_qa` để dọn.
 *
 * @package FBM
 */

const FBM_QA_ATTRS = array(
	'ban-rong' => array( 'Bản rộng', array( '20mm', '22mm' ) ),
	'loai-da'  => array( 'Loại da', array( 'Alligator', 'Calfskin' ) ),
	'mau-chi'  => array( 'Màu chỉ', array( 'Trắng', 'Đỏ' ) ),
);

function fbm_qa_attribute( $slug, array $terms, $visible = true ) {
	$tax = wc_attribute_taxonomy_name( $slug );
	$a   = new WC_Product_Attribute();
	$a->set_id( wc_attribute_taxonomy_id_by_name( $slug ) );
	$a->set_name( $tax );
	$ids = array();
	foreach ( $terms as $t ) {
		$ids[] = get_term_by( 'name', $t, $tax )->term_id;
	}
	$a->set_options( $ids );
	$a->set_visible( $visible );
	return $a;
}

function fbm_qa_simple( $name, array $props = array() ) {
	$p = new WC_Product_Simple();
	$p->set_name( 'FBM QA ' . $name );
	$p->set_status( 'publish' );
	$p->set_regular_price( isset( $props['price'] ) ? $props['price'] : '50' );
	foreach ( $props as $k => $v ) {
		if ( 'price' !== $k && is_callable( array( $p, 'set_' . $k ) ) ) {
			$p->{'set_' . $k}( $v );
		}
	}
	$id = $p->save();
	update_post_meta( $id, '_fbm_qa', 1 );
	return $id;
}

function fbm_qa_post( $type, $title, $content, $slug = '' ) {
	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => 'FBM QA ' . $title,
			'post_name'    => $slug,
			'post_content' => $content,
		)
	);
	update_post_meta( $id, '_fbm_qa', 1 );
	return $id;
}

/**
 * @return array{simple:int,variable:int,sale:int,outofstock:int,hidden:int,noimage:int,attrs:int,block_a:int,block_b:int}
 */
function fbm_fixtures_up() {
	fbm_fixtures_down();

	foreach ( FBM_QA_ATTRS as $slug => $def ) {
		$tax = wc_attribute_taxonomy_name( $slug );
		if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
			wc_create_attribute( array( 'name' => $def[0], 'slug' => $slug ) );
		}
		if ( ! taxonomy_exists( $tax ) ) {
			register_taxonomy( $tax, 'product' );
		}
		foreach ( $def[1] as $t ) {
			if ( ! term_exists( $t, $tax ) ) {
				wp_insert_term( $t, $tax );
			}
		}
	}

	$f = array();
	$f['simple']     = fbm_qa_simple( 'simple' );
	$f['sale']       = fbm_qa_simple( 'sale', array( 'price' => '100', 'sale_price' => '80' ) );
	$f['outofstock'] = fbm_qa_simple( 'outofstock', array( 'stock_status' => 'outofstock' ) );
	$f['hidden']     = fbm_qa_simple( 'hidden', array( 'catalog_visibility' => 'hidden' ) );
	$f['noimage']    = fbm_qa_simple( 'noimage' );

	$custom = new WC_Product_Attribute();
	$custom->set_name( 'Khoá' );
	$custom->set_options( array( 'Pin buckle', ' Deployant ', '' ) );
	$custom->set_visible( true );
	$hidden = new WC_Product_Attribute();
	$hidden->set_name( 'Ghi chú nội bộ' );
	$hidden->set_options( array( 'Không hiện' ) );
	$hidden->set_visible( false );
	$f['attrs'] = fbm_qa_simple(
		'attrs',
		array( 'attributes' => array( fbm_qa_attribute( 'ban-rong', array( '20mm', '22mm' ) ), fbm_qa_attribute( 'loai-da', array( 'Alligator' ) ), $custom, $hidden ) )
	);

	$v = new WC_Product_Variable();
	$v->set_name( 'FBM QA variable' );
	$v->set_status( 'publish' );
	$v->set_attributes( array( fbm_qa_attribute( 'ban-rong', array( '20mm', '22mm' ) ) ) );
	$vid = $v->save();
	foreach ( array( '20mm' => '60', '22mm' => '70' ) as $term => $price ) {
		$var = new WC_Product_Variation();
		$var->set_parent_id( $vid );
		$var->set_attributes( array( wc_attribute_taxonomy_name( 'ban-rong' ) => sanitize_title( $term ) ) );
		$var->set_regular_price( $price );
		$var->save();
	}
	WC_Product_Variable::sync( $vid );
	update_post_meta( $vid, '_fbm_qa', 1 );
	$f['variable'] = $vid;

	$f['block_a'] = fbm_qa_post( 'blocks', 'block a', '[map]', 'fbm-qa-a' );
	$f['block_b'] = fbm_qa_post( 'blocks', 'block b', '[block id="fbm-qa-a"]', 'fbm-qa-b' );

	return $f;
}

function fbm_fixtures_down() {
	$ids = get_posts(
		array(
			'post_type'   => array_values( get_post_types() ), // 'any' bỏ qua post type exclude_from_search như blocks
			'post_status' => 'any',
			'meta_key'    => '_fbm_qa',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $ids as $id ) {
		$product = function_exists( 'wc_get_product' ) ? wc_get_product( $id ) : null;
		if ( $product ) {
			$product->delete( true );
		} else {
			wp_delete_post( $id, true );
		}
	}
	foreach ( array_keys( FBM_QA_ATTRS ) as $slug ) {
		$aid = wc_attribute_taxonomy_id_by_name( $slug );
		if ( $aid ) {
			wc_delete_attribute( $aid );
		}
	}
}
