<?php
/**
 * Cầu dao: lỗi fatal không bắt được bằng try/catch (hết bộ nhớ, quá thời gian chạy)
 * vẫn chạy hàm shutdown. Nếu lỗi xảy ra khi đang render element, đánh dấu element
 * "tripped" để từ request sau site chạy bình thường.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Sandbox;

defined( 'ABSPATH' ) || exit;

final class CircuitBreaker {

	const FATALS = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR );

	/** @var array<int, array{id:int, tag:string}> */
	private static $stack = array();

	/** @var string|null Bộ nhớ dự phòng, giải phóng khi shutdown để còn chỗ ghi DB. */
	private static $reserve;

	/** @var callable|null */
	private static $on_trip;

	private static $booted = false;

	/**
	 * @param callable $on_trip function ( int $element_id, array $info ): void
	 */
	public static function boot( callable $on_trip ) {
		if ( self::$booted ) {
			return;
		}
		self::$booted  = true;
		self::$on_trip = $on_trip;
		self::$reserve = str_repeat( ' ', 256 * 1024 );
		register_shutdown_function( array( __CLASS__, 'on_shutdown' ) );
	}

	public static function enter( $id, $tag ) {
		self::$stack[] = array(
			'id'  => (int) $id,
			'tag' => (string) $tag,
		);
	}

	public static function leave() {
		array_pop( self::$stack );
	}

	public static function on_shutdown() {
		self::$reserve = null;
		if ( ! self::$stack || defined( 'FBM_IN_SANDBOX' ) ) {
			return;
		}
		$error = error_get_last();
		if ( ! $error || ! in_array( $error['type'], self::FATALS, true ) ) {
			return;
		}

		$current = end( self::$stack );
		$file    = basename( (string) $error['file'] );
		$is_ours = 0 === strpos( $file, $current['tag'] . '-v' );

		$info = array(
			'message' => self::clean_message( $error['message'] ),
			'line'    => $is_ours ? (int) $error['line'] : 0,
			'file'    => $is_ours ? 'template.php' : $file,
			'url'     => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '', // phpcs:ignore
			'at'      => time(),
		);

		try {
			call_user_func( self::$on_trip, $current['id'], $info );
		} catch ( \Throwable $e ) {
			// Đang shutdown, không còn gì làm được.
			unset( $e );
		}
	}

	/** Bỏ đường dẫn tuyệt đối khỏi thông báo lỗi. */
	public static function clean_message( $message ) {
		return (string) preg_replace( '#\s+in\s+/\S+(?: on line \d+)?#', '', (string) $message );
	}
}
