<?php
/**
 * Bộ test tối giản chạy trong WordPress thật (ddev wp eval-file). Không dùng trong plugin khi chạy.
 *
 * @package FBM
 */

$GLOBALS['fbm_tests'] = array( 'pass' => 0, 'fail' => 0, 'failures' => array() );

class FBM_Assert_Failed extends Exception {}

function fbm_test( $name, callable $fn ) {
	$t = &$GLOBALS['fbm_tests'];
	if ( ! empty( $GLOBALS['fbm_test_filter_name'] ) && false === strpos( $name, $GLOBALS['fbm_test_filter_name'] ) ) {
		return;
	}
	$user = get_current_user_id();
	try {
		$fn();
		$t['pass']++;
		echo "PASS {$name}\n";
	} catch ( Throwable $e ) {
		$t['fail']++;
		$t['failures'][] = $name;
		echo "FAIL {$name}: " . $e->getMessage() . ( $e instanceof FBM_Assert_Failed ? '' : ' @ ' . basename( $e->getFile() ) . ':' . $e->getLine() ) . "\n";
	}
	wp_set_current_user( $user );
}

function fbm_assert_true( $v, $msg = '' ) {
	if ( true !== $v ) {
		throw new FBM_Assert_Failed( 'expected true' . ( $msg ? " ({$msg})" : '' ) . ', got ' . var_export( $v, true ) );
	}
}

function fbm_assert_same( $expected, $actual, $msg = '' ) {
	if ( $expected !== $actual ) {
		throw new FBM_Assert_Failed( ( $msg ? "{$msg}: " : '' ) . 'expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) );
	}
}

function fbm_assert_contains( $needle, $haystack, $msg = '' ) {
	if ( false === strpos( (string) $haystack, (string) $needle ) ) {
		throw new FBM_Assert_Failed( ( $msg ? "{$msg}: " : '' ) . 'missing ' . var_export( $needle, true ) . ' in ' . var_export( substr( (string) $haystack, 0, 300 ), true ) );
	}
}

function fbm_assert_not_contains( $needle, $haystack, $msg = '' ) {
	if ( false !== strpos( (string) $haystack, (string) $needle ) ) {
		throw new FBM_Assert_Failed( ( $msg ? "{$msg}: " : '' ) . 'unexpected ' . var_export( $needle, true ) );
	}
}

/** Chạy $fn với một user tạm có vai trò $role, xoá user sau đó. */
function fbm_as_user( $role, callable $fn ) {
	$id = wp_insert_user(
		array(
			'user_login' => 'fbmqa_' . $role . '_' . wp_rand(),
			'user_pass'  => wp_generate_password(),
			'role'       => $role,
		)
	);
	$prev = get_current_user_id();
	wp_set_current_user( $id );
	try {
		return $fn( $id );
	} finally {
		wp_set_current_user( $prev );
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $id );
	}
}
