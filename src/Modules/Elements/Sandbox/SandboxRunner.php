<?php
/**
 * Phía gửi của cổng 2: biên dịch bản nháp ra file tạm, gọi request loopback tới
 * /fbm/v1/sandbox/run và diễn giải kết quả.
 *
 * status: passed | failed | unavailable (hosting chặn loopback)
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Sandbox;

use FBM\Modules\Elements\Compiler;
use FBM\Modules\Elements\Schema;

defined( 'ABSPATH' ) || exit;

final class SandboxRunner {

	const TIMEOUT = 20;

	/** @var Compiler */
	private $compiler;

	public function __construct( Compiler $compiler ) {
		$this->compiler = $compiler;
	}

	/**
	 * @param array  $payload schema, template, css, js (đã qua Linter).
	 * @param array  $schema  Schema đã parse.
	 * @param string $sample  JSON dữ liệu thử, ví dụ {"title": "Xin chào", "$content": "..."}.
	 */
	public function run( $tag, array $payload, array $schema, $sample ) {
		$started = microtime( true );
		$file    = $this->compiler->compile_temp( $payload );
		if ( is_wp_error( $file ) ) {
			return $this->report( 'failed', $file->get_error_message() );
		}

		$sample  = json_decode( (string) $sample, true );
		$sample  = is_array( $sample ) ? $sample : array();
		$content = isset( $sample['$content'] ) ? (string) $sample['$content'] : '';
		unset( $sample['$content'] );
		$atts = shortcode_atts( Schema::defaults( isset( $schema['options'] ) ? $schema['options'] : array() ), array_map( 'strval', array_filter( $sample, 'is_scalar' ) ), $tag );

		$token = wp_generate_password( 32, false, false );
		set_transient(
			SandboxEndpoint::TRANSIENT . $token,
			array(
				'file'    => $file,
				'tag'     => $tag,
				'atts'    => $atts,
				'content' => $content,
				'user'    => get_current_user_id(),
			),
			2 * MINUTE_IN_SECONDS
		);

		$response = wp_remote_post(
			rest_url( 'fbm/v1/sandbox/run' ),
			array(
				'timeout'   => self::TIMEOUT,
				'blocking'  => true,
				'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
				'headers'   => array( 'Cache-Control' => 'no-cache' ),
				'body'      => array( 'token' => $token ),
			)
		);

		delete_transient( SandboxEndpoint::TRANSIENT . $token );
		wp_delete_file( $file );

		if ( is_wp_error( $response ) ) {
			$msg = $response->get_error_message();
			if ( false !== stripos( $msg, 'timed out' ) || false !== strpos( $msg, 'cURL error 28' ) ) {
				return $this->report( 'failed', sprintf( __( 'Chạy thử quá %d giây. Có thể template có vòng lặp vô hạn.', 'flatsome-block-manager' ), self::TIMEOUT ) );
			}
			return $this->report( 'unavailable', sprintf( __( 'Không gọi được request chạy thử về chính site (loopback): %s', 'flatsome-block-manager' ), $msg ) );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( is_array( $data ) && ! empty( $data['fbm_sandbox'] ) ) {
			$failed = ! empty( $data['fatal'] ) || ! empty( $data['exception'] ) || empty( $data['ok'] );
			$msg    = '';
			if ( ! empty( $data['fatal'] ) ) {
				$msg = $data['fatal']['message'];
			} elseif ( ! empty( $data['exception'] ) ) {
				$msg = $data['exception']['class'] . ': ' . $data['exception']['message'];
			}
			return $this->report( $failed ? 'failed' : 'passed', $msg, $data );
		}

		if ( in_array( $code, array( 401, 403, 404 ), true ) ) {
			return $this->report( 'unavailable', sprintf( __( 'Request chạy thử bị chặn (HTTP %d). Có thể do plugin bảo mật hoặc mật khẩu bảo vệ site.', 'flatsome-block-manager' ), $code ) );
		}

		return $this->report(
			'failed',
			sprintf( 'HTTP %d: %s', $code, wp_trim_words( wp_strip_all_tags( $body ), 60 ) )
		);
	}

	private function report( $status, $message, array $data = array() ) {
		return array(
			'status'    => $status,
			'message'   => $message,
			'fatal'     => isset( $data['fatal'] ) ? $data['fatal'] : null,
			'exception' => isset( $data['exception'] ) ? $data['exception'] : null,
			'warnings'  => isset( $data['warnings'] ) ? $data['warnings'] : array(),
			'html'      => isset( $data['html'] ) ? $data['html'] : '',
			'renders'   => isset( $data['renders'] ) ? (int) $data['renders'] : 0,
			'time_ms'   => isset( $data['time_ms'] ) ? (int) $data['time_ms'] : 0,
			'memory_kb' => isset( $data['memory_kb'] ) ? (int) $data['memory_kb'] : 0,
			'at'        => gmdate( 'c' ),
		);
	}
}
