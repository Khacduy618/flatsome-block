<?php
/**
 * Cổng 1: kiểm tra tĩnh, không chạy code.
 *
 * - Cú pháp PHP bằng token_get_all(TOKEN_PARSE): thuần PHP, không cần exec().
 * - Luật riêng cho template (sẽ chạy nhiều lần trong một request):
 *   cấm exit/die, khai báo hàm/class có tên, namespace, declare, __halt_compiler.
 * - Schema JSON (Schema::parse).
 *
 * JS được kiểm tra ở trình duyệt bằng new Function() (không chạy code).
 *
 * @package FBM
 */

namespace FBM\Modules\Elements\Sandbox;

use FBM\Modules\Elements\Schema;

defined( 'ABSPATH' ) || exit;

final class Linter {

	const RISKY_FUNCTIONS = array( 'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'pcntl_exec', 'ob_end_clean', 'ob_end_flush', 'ob_get_clean', 'set_time_limit', 'ini_set', 'header' );

	/**
	 * @param array  $payload schema, template, css, js
	 * @param string $tag     Tag của element (để kiểm tra presets).
	 * @return array{ok: bool, errors: array, warnings: array, schema: array}
	 */
	public function lint( array $payload, $tag = '' ) {
		$schema   = Schema::parse( $payload['schema'] ?? '' );
		$errors   = $schema['errors'];
		$warnings = $schema['warnings'];

		if ( $schema['ok'] && $tag && ! empty( $schema['data']['presets'] ) ) {
			list( $p_errors, $p_warnings ) = $this->lint_presets( $schema['data'], $tag );
			$errors   = array_merge( $errors, $p_errors );
			$warnings = array_merge( $warnings, $p_warnings );
		}

		list( $tpl_errors, $tpl_warnings ) = $this->lint_template( (string) ( $payload['template'] ?? '' ) );
		$errors   = array_merge( $errors, $tpl_errors );
		$warnings = array_merge( $warnings, $tpl_warnings, $this->lint_css( (string) ( $payload['css'] ?? '' ) ) );

		return array(
			'ok'       => ! $errors,
			'errors'   => $errors,
			'warnings' => $warnings,
			'schema'   => $schema['ok'] ? $schema['data'] : array(),
		);
	}

	/**
	 * @return array{0: array, 1: array}
	 */
	public function lint_template( $code ) {
		$errors   = array();
		$warnings = array();

		if ( '' === trim( $code ) ) {
			return array( array( Schema::issue( 'template', 1, 'Template đang trống.' ) ), array() );
		}

		try {
			$tokens = token_get_all( $code, TOKEN_PARSE );
		} catch ( \ParseError $e ) {
			return array( array( Schema::issue( 'template', $e->getLine(), $e->getMessage() ) ), array() );
		} catch ( \Throwable $e ) {
			return array( array( Schema::issue( 'template', 1, $e->getMessage() ) ), array() );
		}

		// Chỉ giữ token có nghĩa (bỏ khoảng trắng, comment) để nhìn trước/sau.
		$sig = array();
		foreach ( $tokens as $t ) {
			if ( is_array( $t ) && in_array( $t[0], array( T_WHITESPACE, T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$sig[] = $t;
		}

		$line = 1;
		$n    = count( $sig );
		for ( $i = 0; $i < $n; $i++ ) {
			$t = $sig[ $i ];
			if ( ! is_array( $t ) ) {
				continue;
			}
			$line = $t[2];
			$prev = $i > 0 ? $sig[ $i - 1 ] : null;
			$next = $i + 1 < $n ? $sig[ $i + 1 ] : null;

			switch ( $t[0] ) {
				case T_EXIT:
					$errors[] = Schema::issue( 'template', $line, 'Không dùng exit/die: lệnh này dừng cả trang. Dùng return để thoát khỏi template.' );
					break;

				case T_HALT_COMPILER:
				case T_DECLARE:
					$errors[] = Schema::issue( 'template', $line, sprintf( 'Không dùng %s trong template.', $t[1] ) );
					break;

				case T_NAMESPACE:
					if ( ! self::is( $next, T_NS_SEPARATOR ) ) {
						$errors[] = Schema::issue( 'template', $line, 'Không khai báo namespace trong template.' );
					}
					break;

				case T_FUNCTION:
					// "function ten(" là khai báo có tên -> lỗi "Cannot redeclare" ở lần render thứ 2.
					$j = $i + 1;
					if ( $j < $n && '&' === $sig[ $j ] ) {
						$j++;
					}
					if ( $j < $n && self::is( $sig[ $j ], T_STRING ) && ! self::is( $prev, array( T_DOUBLE_COLON, T_OBJECT_OPERATOR, T_USE ) ) ) {
						$errors[] = Schema::issue( 'template', $line, sprintf( 'Không khai báo hàm có tên "%s" trong template: element render lần 2 sẽ lỗi "Cannot redeclare". Dùng closure: $%s = function () { ... };', $sig[ $j ][1], $sig[ $j ][1] ) );
					}
					break;

				case T_CLASS:
				case T_INTERFACE:
				case T_TRAIT:
					if ( self::is( $prev, array( T_DOUBLE_COLON, T_NEW ) ) ) {
						break; // Foo::class hoặc new class {}.
					}
					$errors[] = Schema::issue( 'template', $line, sprintf( 'Không khai báo %s trong template (sẽ lỗi khi render lần 2).', $t[1] ) );
					break;

				case T_EVAL:
					$warnings[] = Schema::issue( 'template', $line, 'eval() khiến lỗi khó tìm. Nên tránh.' );
					break;

				case T_STRING:
					if ( '(' === $next && in_array( strtolower( $t[1] ), self::RISKY_FUNCTIONS, true ) && ! self::is( $prev, array( T_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION ) ) ) {
						$warnings[] = Schema::issue( 'template', $line, sprintf( 'Hàm %s() ảnh hưởng tới cả request, không chỉ element này.', $t[1] ) );
					}
					break;
			}

			if ( defined( 'T_ENUM' ) && T_ENUM === $t[0] ) {
				$errors[] = Schema::issue( 'template', $line, 'Không khai báo enum trong template.' );
			}
		}

		return array( $errors, $warnings );
	}

	/**
	 * Preset theo chuẩn Flatsome: { name, content }, content bắt đầu bằng [tag-của-element].
	 * UX Builder parse mọi tag trong preset khi mở, nên tag con phải là element đã đăng ký.
	 */
	private function lint_presets( array $schema, $tag ) {
		$errors   = array();
		$warnings = array();
		$allow    = isset( $schema['allow'] ) ? (array) $schema['allow'] : array();
		foreach ( $schema['presets'] as $preset ) {
			$content = trim( (string) $preset['content'] );
			if ( ! preg_match( '/^\[' . preg_quote( $tag, '/' ) . '[\s\]\/]/', $content ) ) {
				$errors[] = Schema::issue( 'schema', 1, sprintf( 'Preset "%1$s" phải bắt đầu bằng [%2$s].', $preset['name'], $tag ) );
				continue;
			}
			foreach ( Schema::preset_tags( $content ) as $child ) {
				if ( $child === $tag ) {
					continue;
				}
				if ( ! Schema::is_container( $schema ) ) {
					$warnings[] = Schema::issue( 'schema', 1, sprintf( 'Preset "%1$s" chứa [%2$s] nhưng element không phải container.', $preset['name'], $child ) );
				} elseif ( $allow && ! in_array( $child, $allow, true ) ) {
					$warnings[] = Schema::issue( 'schema', 1, sprintf( 'Preset "%1$s" dùng [%2$s] nhưng tag này không có trong "allow".', $preset['name'], $child ) );
				}
			}
		}
		return array( $errors, $warnings );
	}

	private function lint_css( $css ) {
		$warnings = array();
		$clean    = preg_replace( '#/\*.*?\*/#s', '', $css );
		$open     = substr_count( $clean, '{' );
		$close    = substr_count( $clean, '}' );
		if ( $open !== $close ) {
			$warnings[] = Schema::issue( 'css', 1, sprintf( 'CSS có %d dấu { nhưng %d dấu }.', $open, $close ) );
		}
		return $warnings;
	}

	/**
	 * @param mixed     $token
	 * @param int|int[] $types
	 */
	private static function is( $token, $types ) {
		return is_array( $token ) && in_array( $token[0], (array) $types, true );
	}
}
