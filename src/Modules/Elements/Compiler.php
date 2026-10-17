<?php
/**
 * Biến bản code của element thành file chạy được trong uploads/fbm-elements/.
 *
 * File PHP sinh ra trả về một closure. Phần bọc nằm hết trên dòng 1 nên
 * "dòng N của template" = "dòng N của file" -> báo lỗi đúng dòng.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

defined( 'ABSPATH' ) || exit;

final class Compiler {

	const DIR = 'fbm-elements';

	const PREFIX = '<?php return static function ( array $__fbm_atts, $content, $tag, $fbm_id ) { extract( $__fbm_atts, EXTR_SKIP ); $atts = $__fbm_atts; ?>';

	public function base_dir() {
		$uploads = wp_upload_dir( null, false );
		return trailingslashit( $uploads['basedir'] ) . self::DIR;
	}

	public function base_url() {
		$uploads = wp_upload_dir( null, false );
		return set_url_scheme( trailingslashit( $uploads['baseurl'] ) . self::DIR );
	}

	/**
	 * @return string Mã nguồn PHP hoàn chỉnh.
	 */
	public static function php_source( $template ) {
		$template = (string) $template;
		if ( self::ends_in_php_mode( $template ) ) {
			$template .= "\n?>";
		}
		return self::PREFIX . $template . "\n<?php };\n";
	}

	/**
	 * Ghi bản Live. Trả về tên file (tương đối trong thư mục fbm-elements).
	 *
	 * @return array{php: string, css: string, js: string}|\WP_Error
	 */
	public function compile( $tag, $version, array $payload, $hash ) {
		$dir = $this->ensure_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}

		$files = self::file_names( $tag, $version, $payload, $hash );

		$ok = $this->write( $dir . '/' . $files['php'], self::php_source( $payload['template'] ) );
		if ( $ok && $files['css'] ) {
			$ok = $this->write( $dir . '/' . $files['css'], $payload['css'] );
		}
		if ( $ok && $files['js'] ) {
			$ok = $this->write( $dir . '/' . $files['js'], $payload['js'] );
		}
		if ( ! $ok ) {
			return new \WP_Error( 'fbm_write_failed', sprintf( __( 'Không ghi được file vào %s. Kiểm tra quyền ghi thư mục uploads.', 'flatsome-block-manager' ), $dir ), array( 'status' => 500 ) );
		}

		$this->remove_old( $tag, $files );
		return $files;
	}

	/**
	 * Tên file suy ra từ tag + phiên bản + hash, nên không cần lưu vào DB.
	 *
	 * @return array{php: string, css: string, js: string}
	 */
	public static function file_names( $tag, $version, array $payload, $hash ) {
		$base = sprintf( '%s-v%d-%s', $tag, (int) $version, substr( $hash, 0, 8 ) );
		return array(
			'php' => $base . '.php',
			'css' => '' !== trim( (string) $payload['css'] ) ? $base . '.css' : '',
			'js'  => '' !== trim( (string) $payload['js'] ) ? $base . '.js' : '',
		);
	}

	/**
	 * File tạm cho sandbox.
	 *
	 * @return string|\WP_Error Đường dẫn tuyệt đối.
	 */
	public function compile_temp( array $payload ) {
		$dir = $this->ensure_dir();
		if ( is_wp_error( $dir ) ) {
			return $dir;
		}
		$path = $dir . '/sandbox-' . wp_generate_password( 20, false, false ) . '.php';
		if ( ! $this->write( $path, self::php_source( $payload['template'] ) ) ) {
			return new \WP_Error( 'fbm_write_failed', __( 'Không ghi được file chạy thử.', 'flatsome-block-manager' ), array( 'status' => 500 ) );
		}
		return $path;
	}

	public function path( $file ) {
		return $file ? $this->base_dir() . '/' . $file : '';
	}

	public function url( $file ) {
		return $file ? $this->base_url() . '/' . $file : '';
	}

	public function delete_files_for( $tag ) {
		foreach ( (array) glob( $this->base_dir() . '/' . $tag . '-v*' ) as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/** Dọn file sandbox bị bỏ lại (request chạy thử chết giữa chừng). */
	public function cleanup_sandbox_files() {
		foreach ( (array) glob( $this->base_dir() . '/sandbox-*.php' ) as $file ) {
			if ( is_file( $file ) && filemtime( $file ) < time() - HOUR_IN_SECONDS ) {
				wp_delete_file( $file );
			}
		}
	}

	public static function ends_in_php_mode( $code ) {
		$in_php = false;
		foreach ( token_get_all( $code ) as $token ) {
			if ( ! is_array( $token ) ) {
				continue;
			}
			if ( T_OPEN_TAG === $token[0] || T_OPEN_TAG_WITH_ECHO === $token[0] ) {
				$in_php = true;
			} elseif ( T_CLOSE_TAG === $token[0] ) {
				$in_php = false;
			}
		}
		return $in_php;
	}

	private function remove_old( $tag, array $keep ) {
		$keep = array_filter( $keep );
		foreach ( (array) glob( $this->base_dir() . '/' . $tag . '-v*' ) as $file ) {
			if ( is_file( $file ) && ! in_array( basename( $file ), $keep, true ) ) {
				wp_delete_file( $file );
			}
		}
	}

	/**
	 * @return string|\WP_Error
	 */
	private function ensure_dir() {
		$dir = $this->base_dir();
		if ( ! wp_mkdir_p( $dir ) ) {
			return new \WP_Error( 'fbm_mkdir_failed', sprintf( __( 'Không tạo được thư mục %s.', 'flatsome-block-manager' ), $dir ), array( 'status' => 500 ) );
		}
		if ( ! file_exists( $dir . '/index.php' ) ) {
			$this->write( $dir . '/index.php', "<?php\n// Silence is golden.\n" );
		}
		if ( ! file_exists( $dir . '/.htaccess' ) ) {
			// Chặn gọi trực tiếp file .php; CSS/JS vẫn tải được.
			$this->write( $dir . '/.htaccess', "<FilesMatch \"\\.php$\">\n  <IfModule mod_authz_core.c>\n    Require all denied\n  </IfModule>\n  <IfModule !mod_authz_core.c>\n    Deny from all\n  </IfModule>\n</FilesMatch>\n" );
		}
		return $dir;
	}

	private function write( $path, $contents ) {
		$ok = false !== file_put_contents( $path, $contents, LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( $ok && function_exists( 'opcache_invalidate' ) && '.php' === substr( $path, -4 ) ) {
			@opcache_invalidate( $path, true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
		}
		return $ok;
	}
}
