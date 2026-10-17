<?php
/**
 * PSR-4 autoloader: FBM\Foo\Bar -> src/Foo/Bar.php
 *
 * @package FBM
 */

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( $class ) {
		if ( strpos( $class, 'FBM\\' ) !== 0 ) {
			return;
		}
		$file = __DIR__ . '/' . str_replace( '\\', '/', substr( $class, 4 ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);
