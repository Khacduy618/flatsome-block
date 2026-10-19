<?php
use FBM\Catalog;
use FBM\States;

// Giữ snapshot thật của site; khôi phục ở test cuối của file.
$GLOBALS['fbm_rest_saved'] = array(
	Catalog::OPTION => get_option( Catalog::OPTION, null ),
	States::OPTION  => get_option( States::OPTION, null ),
);

function fbm_rest_setup() {
	update_option(
		Catalog::OPTION,
		array(
			'map'       => array( 'name' => 'Map', 'category' => 'Content' ),
			'section'   => array( 'name' => 'Section', 'category' => 'Layout' ),
			'ux_lottie' => array( 'name' => 'Lottie', 'category' => 'Content' ),
		),
		false
	);
	delete_option( States::OPTION );
}

/** Gọi REST trong process với user hiện tại; trả [status, data]. */
function fbm_rest( $method, $route, array $params = array() ) {
	$req = new WP_REST_Request( $method, '/fbm/v2' . $route );
	if ( 'GET' === $method ) {
		$req->set_query_params( $params );
	} else {
		$req->set_header( 'content-type', 'application/json' );
		$req->set_body( wp_json_encode( $params ) );
	}
	$res = rest_do_request( $req );
	return array( $res->get_status(), $res->get_data() );
}

function fbm_rest_as_admin( callable $fn ) {
	return fbm_as_user( 'administrator', $fn );
}

fbm_test( 'test_rest_routes_registered', function () {
	$routes = rest_get_server()->get_routes();
	foreach ( array( '/fbm/v2/items', '/fbm/v2/state', '/fbm/v2/usage/rebuild' ) as $r ) {
		fbm_assert_true( isset( $routes[ $r ] ), $r );
	}
} );

fbm_test( 'test_rest_rejects_locked', function () {
	fbm_rest_setup();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'flatsome', 'id' => 'section', 'state' => 'off' ) );
	} );
	fbm_assert_same( 400, $status );
	fbm_assert_same( 'on', FBM\Plugin::get( 'states' )->get( 'section' ) );
} );

fbm_test( 'test_rest_rejects_unknown_tag', function () {
	fbm_rest_setup();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'flatsome', 'id' => 'evil_tag', 'state' => 'off' ) );
	} );
	fbm_assert_same( 400, $status );
	fbm_assert_same( array(), FBM\Plugin::get( 'states' )->all() );
} );

fbm_test( 'test_rest_rejects_tag_of_other_type', function () {
	fbm_rest_setup();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'plugin', 'id' => 'map', 'state' => 'off' ) );
	} );
	fbm_assert_same( 400, $status );
	fbm_assert_same( 'on', FBM\Plugin::get( 'states' )->get( 'map' ) );
} );

fbm_test( 'test_rest_rejects_bad_state', function () {
	fbm_rest_setup();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'flatsome', 'id' => 'map', 'state' => 'delete' ) );
	} );
	fbm_assert_same( 400, $status );
	fbm_assert_same( 'on', FBM\Plugin::get( 'states' )->get( 'map' ) );
} );

fbm_test( 'test_rest_rejects_bad_type', function () {
	fbm_rest_setup();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'option', 'id' => 'map', 'state' => 'off' ) );
	} );
	fbm_assert_same( 400, $status );
} );

fbm_test( 'test_rest_rejects_page_id_as_block', function () {
	$page = fbm_qa_post( 'page', 'rest page', '' );
	list( $status ) = fbm_rest_as_admin( function () use ( $page ) {
		return fbm_rest( 'POST', '/state', array( 'type' => 'ux_block', 'id' => $page, 'state' => 'off' ) );
	} );
	$meta = get_post_meta( $page, FBM\BlockStates::META, true );
	fbm_fixtures_down();
	fbm_assert_same( 400, $status );
	fbm_assert_same( '', $meta );
} );

fbm_test( 'test_rest_editor_cannot_change_element', function () {
	fbm_rest_setup();
	list( $status ) = fbm_as_user( 'editor', function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'flatsome', 'id' => 'map', 'state' => 'off' ) );
	} );
	fbm_assert_same( 403, $status );
	fbm_assert_same( 'on', FBM\Plugin::get( 'states' )->get( 'map' ) );
} );

fbm_test( 'test_rest_editor_cannot_list_elements', function () {
	fbm_rest_setup();
	list( $status ) = fbm_as_user( 'editor', function () {
		return fbm_rest( 'GET', '/items', array( 'type' => 'plugin' ) );
	} );
	fbm_assert_same( 403, $status );
} );

fbm_test( 'test_rest_guest_rejected', function () {
	wp_set_current_user( 0 );
	list( $status ) = fbm_rest( 'GET', '/items', array( 'type' => 'ux_block' ) );
	fbm_assert_same( 401, $status );
} );

fbm_test( 'test_rest_editor_can_change_block', function () {
	$f = fbm_fixtures_up();
	list( $status, $data ) = fbm_as_user( 'editor', function () use ( $f ) {
		return fbm_rest( 'POST', '/state', array( 'type' => 'ux_block', 'id' => $f['block_a'], 'state' => 'hidden' ) );
	} );
	$state = FBM\Plugin::get( 'block_states' )->get( $f['block_a'] );
	fbm_fixtures_down();
	fbm_assert_same( 200, $status );
	fbm_assert_same( array( 'id' => $f['block_a'], 'state' => 'hidden' ), $data );
	fbm_assert_same( 'hidden', $state );
} );

fbm_test( 'test_rest_admin_changes_element', function () {
	fbm_rest_setup();
	list( $status, $data ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'flatsome', 'id' => 'map', 'state' => 'off' ) );
	} );
	fbm_assert_same( 200, $status );
	fbm_assert_same( array( 'id' => 'map', 'state' => 'off' ), $data );
	fbm_assert_same( 'off', FBM\Plugin::get( 'states' )->get( 'map' ) );
} );

fbm_test( 'test_rest_list_flatsome_catalog_empty_flag', function () {
	fbm_rest_setup();
	delete_option( Catalog::OPTION );
	list( $status, $data ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'GET', '/items', array( 'type' => 'flatsome' ) );
	} );
	fbm_assert_same( 200, $status );
	fbm_assert_same( true, $data['catalog_empty'] );
	fbm_assert_same( array(), $data['items'] );
} );

fbm_test( 'test_rest_list_flatsome_rows', function () {
	fbm_rest_setup();
	FBM\Plugin::get( 'states' )->set( 'ux_lottie', 'hidden' );
	list( $status, $data ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'GET', '/items', array( 'type' => 'flatsome' ) );
	} );
	$by = array_column( $data['items'], null, 'id' );
	fbm_assert_same( 200, $status );
	fbm_assert_same( false, $data['catalog_empty'] );
	fbm_assert_same( true, $by['section']['locked'] );
	fbm_assert_same( false, $by['map']['locked'] );
	fbm_assert_same( 'hidden', $by['ux_lottie']['state'] );
	fbm_assert_same( '[map]', $by['map']['sub'] );
	fbm_assert_same( array( 'on', 'hidden', 'off' ), $by['map']['states'] );
	fbm_assert_true( is_int( $by['map']['usage'] ) );
	fbm_assert_true( is_bool( $data['usage_built'] ) );
} );

fbm_test( 'test_rest_list_plugin_rows', function () {
	fbm_rest_setup();
	list( , $data ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'GET', '/items', array( 'type' => 'plugin' ) );
	} );
	$by = array_column( $data['items'], null, 'id' );
	foreach ( array( 'fbm_products', 'handdn_product_attributes', 'fbm_product_compare', 'fbm_reviews', 'fbm_before_after', 'fbm_ba_carousel', 'handdn_3d_button' ) as $tag ) {
		fbm_assert_true( isset( $by[ $tag ] ), $tag );
	}
	fbm_assert_same( array( 'on', 'off' ), $by['handdn_3d_button']['states'] );
	fbm_assert_true( ! isset( $by['map'] ), 'flatsome tag in plugin tab' );
} );

fbm_test( 'test_rest_list_ux_blocks', function () {
	$f = fbm_fixtures_up();
	FBM\Plugin::get( 'block_states' )->set( $f['block_b'], 'off' );
	list( $status, $data ) = fbm_as_user( 'editor', function () {
		return fbm_rest( 'GET', '/items', array( 'type' => 'ux_block' ) );
	} );
	fbm_fixtures_down();
	$by = array_column( $data['items'], null, 'id' );
	fbm_assert_same( 200, $status );
	fbm_assert_same( '[block id="fbm-qa-a"]', $by[ $f['block_a'] ]['sub'] );
	fbm_assert_same( 'FBM QA block a', $by[ $f['block_a'] ]['name'] );
	fbm_assert_same( 'off', $by[ $f['block_b'] ]['state'] );
	fbm_assert_same( false, $by[ $f['block_a'] ]['locked'] );
} );

fbm_test( 'test_rest_rebuild_batch', function () {
	list( $status, $data ) = fbm_as_user( 'editor', function () {
		return fbm_rest( 'POST', '/usage/rebuild', array( 'offset' => 0 ) );
	} );
	fbm_assert_same( 200, $status );
	fbm_assert_true( array_key_exists( 'next', $data ) && is_int( $data['total'] ) );
} );

fbm_test( 'test_rest_no_code_fields', function () {
	global $wpdb;
	fbm_rest_setup();
	// Đếm trước/sau: site DDEV còn dữ liệu v1 (post fbm_element có _fbm_draft) cho tới Task nâng cấp.
	$count = function () use ( $wpdb ) {
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'fbm%' AND option_value LIKE '%<?php%'" )
			+ (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key LIKE '\\_fbm%' AND meta_value LIKE '%<?php%'" );
	};
	$before = $count();
	list( $status ) = fbm_rest_as_admin( function () {
		return fbm_rest( 'POST', '/state', array( 'type' => 'plugin', 'id' => 'fbm_products', 'state' => 'on', 'code' => '<?php echo 1;' ) );
	} );
	fbm_assert_same( 200, $status );
	fbm_assert_same( $before, $count() );
} );

fbm_test( 'zz_rest_restore_site_options', function () {
	foreach ( $GLOBALS['fbm_rest_saved'] as $opt => $value ) {
		null === $value ? delete_option( $opt ) : update_option( $opt, $value, false );
	}
	fbm_assert_true( true );
} );
