<?php
/**
 * Service container tối giản: đăng ký factory, tạo lười, dùng chung một instance.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

final class Container {

	/** @var array<string, callable> */
	private $factories = array();

	/** @var array<string, mixed> */
	private $instances = array();

	/**
	 * @param string         $id
	 * @param callable|mixed $value Factory nhận Container, hoặc instance sẵn.
	 */
	public function set( $id, $value ) {
		if ( is_callable( $value ) && ! is_object( $value ) || $value instanceof \Closure ) {
			$this->factories[ $id ] = $value;
			unset( $this->instances[ $id ] );
			return;
		}
		$this->instances[ $id ] = $value;
	}

	public function has( $id ) {
		return isset( $this->instances[ $id ] ) || isset( $this->factories[ $id ] );
	}

	public function get( $id ) {
		if ( ! array_key_exists( $id, $this->instances ) ) {
			if ( ! isset( $this->factories[ $id ] ) ) {
				throw new \RuntimeException( sprintf( 'FBM service "%s" chưa được đăng ký.', $id ) );
			}
			$this->instances[ $id ] = call_user_func( $this->factories[ $id ], $this );
		}
		return $this->instances[ $id ];
	}
}
