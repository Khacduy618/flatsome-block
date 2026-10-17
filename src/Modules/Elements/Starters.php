<?php
/**
 * Mẫu element dựng sẵn trong thư mục starters/<key>/.
 * Template đặt tên .php.tpl để web server không chạy được file khi gọi trực tiếp.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

defined( 'ABSPATH' ) || exit;

final class Starters {

	public static function dir() {
		return FBM_DIR . 'starters';
	}

	/**
	 * @return array<int, array{key: string, name: string, tag: string, category: string, description: string, requires: string}>
	 */
	public static function all() {
		$out = array();
		foreach ( (array) glob( self::dir() . '/*/meta.json' ) as $file ) {
			$key  = basename( dirname( $file ) );
			$meta = self::meta( $key );
			if ( $meta ) {
				$out[] = array(
					'key'         => $key,
					'name'        => $meta['name'],
					'tag'         => $meta['tag'],
					'category'    => $meta['category'],
					'description' => $meta['description'],
					'requires'    => $meta['requires'],
				);
			}
		}
		return $out;
	}

	/**
	 * @return array|null meta + payload + sample
	 */
	public static function get( $key ) {
		$meta = self::meta( $key );
		if ( ! $meta ) {
			return null;
		}
		$dir             = self::dir() . '/' . $key . '/';
		$meta['payload'] = array(
			'schema'   => self::read( $dir . 'schema.json' ),
			'template' => self::read( $dir . 'template.php.tpl' ),
			'css'      => self::read( $dir . 'style.css' ),
			'js'       => self::read( $dir . 'script.js' ),
		);
		return $meta;
	}

	private static function meta( $key ) {
		if ( ! preg_match( '/^[a-z0-9-]+$/', (string) $key ) ) {
			return null;
		}
		$data = json_decode( self::read( self::dir() . '/' . $key . '/meta.json' ), true );
		if ( ! is_array( $data ) || empty( $data['tag'] ) ) {
			return null;
		}
		return array(
			'key'         => $key,
			'name'        => (string) ( $data['name'] ?? $key ),
			'tag'         => (string) $data['tag'],
			'category'    => (string) ( $data['category'] ?? 'Content' ),
			'description' => (string) ( $data['description'] ?? '' ),
			'requires'    => (string) ( $data['requires'] ?? '' ),
			'sample'      => (string) ( $data['sample'] ?? '{}' ),
		);
	}

	private static function read( $file ) {
		return is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore
	}
}
