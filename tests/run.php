<?php
/**
 * ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/run.php [lọc-file] [lọc-tên-test]
 *
 * @package FBM
 */

require __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/fixtures.php';

$fbm_filter                        = isset( $args[0] ) ? (string) $args[0] : '';
$GLOBALS['fbm_test_filter_name'] = isset( $args[1] ) ? (string) $args[1] : '';

foreach ( glob( __DIR__ . '/*Test.php' ) as $fbm_file ) {
	if ( '' === $fbm_filter || false !== stripos( basename( $fbm_file ), $fbm_filter ) ) {
		require $fbm_file;
	}
}

$fbm_t = $GLOBALS['fbm_tests'];
echo "\n{$fbm_t['pass']} passed, {$fbm_t['fail']} failed\n";
if ( $fbm_t['fail'] ) {
	exit( 1 );
}
