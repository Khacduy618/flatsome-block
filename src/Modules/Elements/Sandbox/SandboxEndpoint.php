<?php
/**
 * Phía nhận của cổng 2: chạy template trong một request riêng.
 * Request này có chết (fatal, hết bộ nhớ, quá giờ) thì request của admin vẫn sống
 * và đọc được kết quả JSON do hàm shutdown in ra.
 *
 * Bảo vệ bằng token dùng 1 lần (transient 2 phút), không dùng cookie.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Sandbox;

use FBM\Core\RestController;

defined( 'ABSPATH' ) || exit;

final class SandboxEndpoint extends RestController {

	const TRANSIENT = 'fbm_sbx_';
	const RENDERS   = 2;

	/** @var array */
	private static $run = array();

	public function register_routes() {
		$this->route(
			'/sandbox/run',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true', // Kiểm tra token bên trong.
				'args'                => array(
					'token' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public function handle( \WP_REST_Request $request ) {
		$token = (string) $request['token'];
		$job   = preg_match( '/^[A-Za-z0-9]{32}$/', $token ) ? get_transient( self::TRANSIENT . $token ) : false;
		if ( ! is_array( $job ) || empty( $job['file'] ) || ! is_readable( $job['file'] ) ) {
			return new \WP_Error( 'fbm_sandbox_denied', 'Invalid token.', array( 'status' => 403 ) );
		}
		delete_transient( self::TRANSIENT . $token );

		if ( ! defined( 'FBM_IN_SANDBOX' ) ) {
			define( 'FBM_IN_SANDBOX', true );
		}
		if ( ! defined( 'WP_SANDBOX_SCRAPING' ) ) {
			// WP_Fatal_Error_Handler bỏ qua request này: không trang "critical error", không recovery mode.
			define( 'WP_SANDBOX_SCRAPING', true );
		}
		@ini_set( 'display_errors', '0' ); // phpcs:ignore
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 10 ); // phpcs:ignore
		}
		@ini_set( 'memory_limit', (string) ( memory_get_usage( true ) + 64 * MB_IN_BYTES ) ); // phpcs:ignore

		if ( ! empty( $job['user'] ) ) {
			wp_set_current_user( (int) $job['user'] );
		}

		self::$run = array(
			'done'     => false,
			'file'     => realpath( $job['file'] ),
			'warnings' => array(),
			'start'    => microtime( true ),
			'mem'      => memory_get_usage(),
		);
		register_shutdown_function( array( __CLASS__, 'on_shutdown' ) );
		set_error_handler( array( __CLASS__, 'on_error' ) ); // phpcs:ignore

		$result = array(
			'fbm_sandbox' => true,
			'ok'          => true,
			'fatal'       => null,
			'exception'   => null,
			'html'        => '',
			'renders'     => 0,
		);

		$level = ob_get_level();
		try {
			$fn = include $job['file'];
			if ( ! $fn instanceof \Closure ) {
				throw new \RuntimeException( 'File biên dịch không trả về closure.' );
			}
			for ( $i = 1; $i <= self::RENDERS; $i++ ) {
				ob_start();
				$fn( (array) $job['atts'], (string) $job['content'], (string) $job['tag'], 'fbm-sandbox-' . $i );
				$html = '';
				while ( ob_get_level() > $level ) {
					$html = ob_get_clean() . $html;
				}
				if ( 1 === $i ) {
					$result['html'] = substr( $html, 0, 200 * KB_IN_BYTES );
				}
				$result['renders'] = $i;
			}
		} catch ( \Throwable $e ) {
			while ( ob_get_level() > $level ) {
				ob_end_clean();
			}
			$result['ok']        = false;
			$result['exception'] = array(
				'class'   => get_class( $e ),
				'message' => CircuitBreaker::clean_message( $e->getMessage() ),
				'line'    => realpath( $e->getFile() ) === self::$run['file'] ? $e->getLine() : 0,
			);
		}

		restore_error_handler();
		self::$run['done'] = true;

		return new \WP_REST_Response( $result + self::metrics() );
	}

	public static function on_error( $errno, $message, $file, $line ) {
		if ( realpath( $file ) !== self::$run['file'] ) {
			return false; // Lỗi của code khác: để PHP/WP xử lý như thường.
		}
		// Template render 2 lần: gộp warning trùng (cùng nội dung + dòng).
		$key = $line . '|' . $message;
		if ( ! isset( self::$run['warnings'][ $key ] ) && count( self::$run['warnings'] ) < 50 ) {
			self::$run['warnings'][ $key ] = array(
				'type'    => self::error_name( $errno ),
				'message' => $message,
				'line'    => (int) $line,
			);
		}
		return true;
	}

	public static function on_shutdown() {
		if ( empty( self::$run ) || self::$run['done'] ) {
			return;
		}
		$error = error_get_last();
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}

		$fatal = array(
			'message' => 'Request dừng giữa chừng mà không báo lỗi (có thể do exit/die).',
			'line'    => 0,
		);
		if ( $error && in_array( $error['type'], CircuitBreaker::FATALS, true ) ) {
			$fatal = array(
				'message' => CircuitBreaker::clean_message( $error['message'] ),
				'line'    => realpath( $error['file'] ) === self::$run['file'] ? (int) $error['line'] : 0,
			);
		}

		if ( ! headers_sent() ) {
			status_header( 200 );
			header( 'Content-Type: application/json; charset=utf-8' );
		}
		echo wp_json_encode(
			array(
				'fbm_sandbox' => true,
				'ok'          => false,
				'fatal'       => $fatal,
				'exception'   => null,
				'html'        => '',
				'renders'     => 0,
			) + self::metrics()
		);
	}

	private static function metrics() {
		return array(
			'warnings'  => array_values( self::$run['warnings'] ),
			'time_ms'   => (int) round( ( microtime( true ) - self::$run['start'] ) * 1000 ),
			'memory_kb' => (int) round( max( 0, memory_get_peak_usage() - self::$run['mem'] ) / 1024 ),
		);
	}

	private static function error_name( $errno ) {
		$map = array(
			E_WARNING         => 'Warning',
			E_NOTICE          => 'Notice',
			E_DEPRECATED      => 'Deprecated',
			E_USER_WARNING    => 'Warning',
			E_USER_NOTICE     => 'Notice',
			E_USER_DEPRECATED => 'Deprecated',
		);
		return isset( $map[ $errno ] ) ? $map[ $errno ] : 'Error';
	}
}
