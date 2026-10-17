<?php
/**
 * Schema của element: một định nghĩa, dùng cho cả UX Builder và Gutenberg.
 *
 * Hai dạng viết đều hợp lệ:
 *   Dạng đầy đủ: { "type": "container", "info": "{{ title }}", "options": { ... } }
 *   Dạng gọn:    { "title": { "type": "textfield" }, "advanced_options": "@commons/advanced" }
 *
 * "@commons/<tên>" nạp file inc/builder/shortcodes/commons/<tên>.php của Flatsome.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

defined( 'ABSPATH' ) || exit;

final class Schema {

	/** Khoá cấp element được chuyển sang add_ux_builder_shortcode(). */
	const ELEMENT_KEYS = array( 'type', 'info', 'presets', 'allow', 'allow_in', 'require', 'nested', 'wrap', 'priority', 'inline', 'overlay', 'hidden', 'options', 'builder_template', 'gutenberg' );

	const KNOWN_TYPES = array( 'textfield', 'textarea', 'text-editor', 'select', 'radio-buttons', 'slider', 'scrubfield', 'checkbox', 'colorpicker', 'image', 'gallery', 'file', 'margins', 'group', 'col-slider' );

	/** Tên biến đã có sẵn trong template, option không được trùng. */
	const RESERVED = array( 'atts', 'content', 'tag', 'fbm_id', 'this', 'globals' );

	/** @var array<string, array|null> */
	private static $commons_cache = array();

	/**
	 * @return array{ok: bool, data: array, errors: array, warnings: array}
	 */
	public static function parse( $json ) {
		$errors   = array();
		$warnings = array();

		$json = trim( (string) $json );
		if ( '' === $json ) {
			$json = '{}';
		}
		$data = json_decode( $json, true );
		if ( null === $data && JSON_ERROR_NONE !== json_last_error() ) {
			return array(
				'ok'       => false,
				'data'     => array(),
				'errors'   => array( self::issue( 'schema', self::json_error_line( $json ), 'JSON không hợp lệ: ' . json_last_error_msg() ) ),
				'warnings' => array(),
			);
		}
		if ( ! is_array( $data ) || ( $data && array_keys( $data ) === range( 0, count( $data ) - 1 ) ) ) {
			return array(
				'ok'       => false,
				'data'     => array(),
				'errors'   => array( self::issue( 'schema', 1, 'Schema phải là một object JSON { ... }.' ) ),
				'warnings' => array(),
			);
		}

		// Dạng gọn -> dạng đầy đủ.
		if ( ! array_key_exists( 'options', $data ) ) {
			$data = array( 'options' => $data );
		}
		if ( ! is_array( $data['options'] ) ) {
			$errors[] = self::issue( 'schema', 1, '"options" phải là object.' );
			$data['options'] = array();
		}

		foreach ( array_keys( $data ) as $key ) {
			if ( ! in_array( $key, self::ELEMENT_KEYS, true ) ) {
				$warnings[] = self::issue( 'schema', self::key_line( $json, $key ), sprintf( 'Khoá "%s" không được dùng, sẽ bị bỏ qua.', $key ) );
				unset( $data[ $key ] );
			}
		}

		if ( isset( $data['type'] ) ) {
			if ( 'container' !== $data['type'] ) {
				// Flatsome coi mọi element có khoá "type" là container (Collections/Elements.php).
				$warnings[] = self::issue( 'schema', self::key_line( $json, 'type' ), 'Chỉ dùng "type": "container". Element thường thì bỏ khoá "type".' );
				unset( $data['type'] );
			}
		}

		if ( isset( $data['builder_template'] ) ) {
			if ( ! self::is_container( $data ) ) {
				$warnings[] = self::issue( 'schema', self::key_line( $json, 'builder_template' ), '"builder_template" chỉ dùng cho element container. Element thường được UX Builder render bằng template PHP.' );
			} elseif ( false === strpos( (string) $data['builder_template'], '<content></content>' ) ) {
				$errors[] = self::issue( 'schema', self::key_line( $json, 'builder_template' ), '"builder_template" phải chứa <content></content> để UX Builder đặt element con vào.' );
			}
		}

		if ( isset( $data['presets'] ) ) {
			$line = self::key_line( $json, 'presets' );
			if ( ! is_array( $data['presets'] ) || ( $data['presets'] && array_keys( $data['presets'] ) !== range( 0, count( $data['presets'] ) - 1 ) ) ) {
				$errors[] = self::issue( 'schema', $line, '"presets" phải là mảng: [ { "name": "Tên", "content": "[tag]" } ].' );
			} else {
				foreach ( $data['presets'] as $i => $preset ) {
					if ( ! is_array( $preset ) || empty( $preset['name'] ) || ! is_string( $preset['name'] ) || empty( $preset['content'] ) || ! is_string( $preset['content'] ) ) {
						$errors[] = self::issue( 'schema', $line, sprintf( 'Preset #%d cần "name" và "content" (chuỗi shortcode).', $i + 1 ) );
					}
				}
			}
		}

		$seen = array();
		self::validate_options( $data['options'], $json, $errors, $warnings, $seen, self::is_container( $data ) );

		return array(
			'ok'       => ! $errors,
			'data'     => $data,
			'errors'   => $errors,
			'warnings' => $warnings,
		);
	}

	/**
	 * Các tag shortcode xuất hiện trong nội dung preset (không trùng lặp).
	 *
	 * @return string[]
	 */
	public static function preset_tags( $content ) {
		preg_match_all( '/\[\/?([A-Za-z0-9_-]+)/', (string) $content, $m );
		return array_values( array_unique( $m[1] ) );
	}

	public static function is_container( array $data ) {
		return isset( $data['type'] ) && 'container' === $data['type'];
	}

	/**
	 * Options đã giải "@commons/..." (dùng cho UX Builder).
	 */
	public static function resolved_options( array $options ) {
		$out = array();
		foreach ( $options as $key => $opt ) {
			if ( is_string( $opt ) ) {
				$opt = self::commons( $opt );
				if ( null === $opt ) {
					continue;
				}
			}
			if ( is_array( $opt ) && isset( $opt['type'] ) && 'group' === $opt['type'] && isset( $opt['options'] ) && is_array( $opt['options'] ) ) {
				$opt['options'] = self::resolved_options( $opt['options'] );
			}
			$out[ $key ] = $opt;
		}
		return $out;
	}

	/**
	 * Danh sách option phẳng (bỏ group), mỗi phần tử có thêm 'param' và 'group'.
	 *
	 * @return array<int, array>
	 */
	public static function flat_options( array $options, $group = '' ) {
		$out = array();
		foreach ( self::resolved_options( $options ) as $key => $opt ) {
			if ( ! is_array( $opt ) ) {
				continue;
			}
			if ( isset( $opt['type'] ) && 'group' === $opt['type'] ) {
				$heading = isset( $opt['heading'] ) ? (string) $opt['heading'] : (string) $key;
				$out     = array_merge( $out, self::flat_options( isset( $opt['options'] ) ? (array) $opt['options'] : array(), $heading ) );
				continue;
			}
			$opt['param'] = isset( $opt['param_name'] ) ? (string) $opt['param_name'] : (string) $key;
			$opt['group'] = $group;
			$out[]        = $opt;
		}
		return $out;
	}

	/**
	 * Giá trị mặc định cho shortcode_atts(). Option responsive có thêm key__sm, key__md.
	 */
	public static function defaults( array $options ) {
		$defaults = array();
		foreach ( self::flat_options( $options ) as $opt ) {
			if ( '$content' === $opt['param'] ) {
				continue;
			}
			$defaults[ $opt['param'] ] = isset( $opt['default'] ) && is_scalar( $opt['default'] ) ? (string) $opt['default'] : '';
			if ( ! empty( $opt['responsive'] ) ) {
				$defaults[ $opt['param'] . '__sm' ] = '';
				$defaults[ $opt['param'] . '__md' ] = '';
			}
		}
		return $defaults;
	}

	/**
	 * Mô tả control cho editor Gutenberg (JS tự dựng InspectorControls từ đây).
	 */
	public static function gutenberg_controls( array $options ) {
		$controls = array();
		foreach ( self::flat_options( $options ) as $opt ) {
			$type    = isset( $opt['type'] ) ? (string) $opt['type'] : 'textfield';
			$control = array(
				'name'    => '$content' === $opt['param'] ? 'content' : $opt['param'],
				'label'   => isset( $opt['heading'] ) ? (string) $opt['heading'] : $opt['param'],
				'help'    => isset( $opt['description'] ) ? (string) $opt['description'] : '',
				'group'   => $opt['group'],
				'default' => isset( $opt['default'] ) && is_scalar( $opt['default'] ) ? (string) $opt['default'] : '',
			);

			switch ( $type ) {
				case 'select':
				case 'radio-buttons':
					$choices = array();
					if ( isset( $opt['options'] ) && is_array( $opt['options'] ) ) {
						foreach ( $opt['options'] as $value => $label ) {
							if ( is_array( $label ) ) {
								$label = isset( $label['title'] ) ? $label['title'] : ( isset( $label['label'] ) ? $label['label'] : $value );
							}
							$choices[] = array(
								'value' => (string) $value,
								'label' => wp_strip_all_tags( (string) $label ),
							);
						}
					}
					$control['control'] = $choices ? 'select' : 'text';
					$control['choices'] = $choices;
					break;
				case 'slider':
					$control['control'] = 'range';
					$control['min']     = isset( $opt['min'] ) ? (float) $opt['min'] : 0;
					$control['max']     = isset( $opt['max'] ) ? (float) $opt['max'] : 100;
					$control['step']    = isset( $opt['step'] ) ? (float) $opt['step'] : 1;
					$control['unit']    = isset( $opt['unit'] ) ? (string) $opt['unit'] : '';
					break;
				case 'checkbox':
					$control['control'] = 'toggle';
					break;
				case 'colorpicker':
					$control['control'] = 'color';
					break;
				case 'image':
					$control['control'] = 'image';
					break;
				case 'file':
					$control['control'] = 'file';
					break;
				case 'textarea':
				case 'text-editor':
					$control['control'] = 'textarea';
					break;
				default:
					$control['control'] = 'text';
			}
			$controls[] = $control;
		}
		return $controls;
	}

	/**
	 * Attributes cho register_block_type(). Mọi giá trị là string, giống shortcode.
	 */
	public static function gutenberg_attributes( array $options ) {
		$attrs = array();
		foreach ( self::gutenberg_controls( $options ) as $control ) {
			$attrs[ $control['name'] ] = array(
				'type'    => 'string',
				'default' => $control['default'],
			);
		}
		$attrs['className'] = array( 'type' => 'string' );
		return $attrs;
	}

	/**
	 * @return array|null
	 */
	public static function commons( $ref ) {
		if ( ! preg_match( '#^@commons/([a-z0-9_-]+)$#', $ref, $m ) ) {
			return null;
		}
		if ( array_key_exists( $m[1], self::$commons_cache ) ) {
			return self::$commons_cache[ $m[1] ];
		}
		$file  = get_template_directory() . '/inc/builder/shortcodes/commons/' . $m[1] . '.php';
		$value = null;
		if ( is_readable( $file ) ) {
			try {
				$value = require $file;
			} catch ( \Throwable $e ) {
				$value = null;
			}
		}
		self::$commons_cache[ $m[1] ] = is_array( $value ) ? $value : null;
		return self::$commons_cache[ $m[1] ];
	}

	private static function validate_options( $options, $json, array &$errors, array &$warnings, array &$seen, $is_container = false ) {
		foreach ( $options as $key => $opt ) {
			$line = self::key_line( $json, (string) $key );

			if ( is_string( $opt ) ) {
				if ( 0 !== strpos( $opt, '@commons/' ) ) {
					$errors[] = self::issue( 'schema', $line, sprintf( 'Option "%s": chuỗi chỉ được là "@commons/<tên>".', $key ) );
				} elseif ( null === self::commons( $opt ) ) {
					$errors[] = self::issue( 'schema', $line, sprintf( 'Không tìm thấy %s trong theme Flatsome.', $opt ) );
				}
				continue;
			}
			if ( ! is_array( $opt ) ) {
				$errors[] = self::issue( 'schema', $line, sprintf( 'Option "%s" phải là object.', $key ) );
				continue;
			}
			if ( empty( $opt['type'] ) || ! is_string( $opt['type'] ) ) {
				$errors[] = self::issue( 'schema', $line, sprintf( 'Option "%s" thiếu "type".', $key ) );
				continue;
			}

			if ( 'group' === $opt['type'] ) {
				if ( empty( $opt['options'] ) || ! is_array( $opt['options'] ) ) {
					$errors[] = self::issue( 'schema', $line, sprintf( 'Group "%s" cần "options".', $key ) );
					continue;
				}
				self::validate_options( $opt['options'], $json, $errors, $warnings, $seen, $is_container );
				continue;
			}

			$param = isset( $opt['param_name'] ) ? (string) $opt['param_name'] : (string) $key;
			if ( '$content' === $param && ! $is_container ) {
				$errors[] = self::issue( 'schema', $line, 'Option "$content" chỉ dùng được khi element là container ("type": "container"). Element thường: dùng option kiểu "textarea".' );
			}
			if ( '$content' !== $param ) {
				if ( ! preg_match( '/^[a-z_][a-z0-9_]*$/', $param ) ) {
					$errors[] = self::issue( 'schema', $line, sprintf( 'Tên option "%s" chỉ gồm chữ thường, số và "_" (WordPress tự đổi tên thuộc tính shortcode thành chữ thường).', $param ) );
				} elseif ( in_array( $param, self::RESERVED, true ) ) {
					$errors[] = self::issue( 'schema', $line, sprintf( 'Tên option "%s" trùng biến có sẵn trong template. Hãy đổi tên.', $param ) );
				}
			}
			if ( isset( $seen[ $param ] ) ) {
				$errors[] = self::issue( 'schema', $line, sprintf( 'Option "%s" bị khai báo hai lần.', $param ) );
			}
			$seen[ $param ] = true;

			if ( ! in_array( $opt['type'], self::KNOWN_TYPES, true ) ) {
				$warnings[] = self::issue( 'schema', $line, sprintf( 'Kiểu "%s" không có trong danh sách quen thuộc. Gutenberg sẽ hiện ô nhập chữ.', $opt['type'] ) );
			}
		}
	}

	public static function issue( $field, $line, $message ) {
		return array(
			'field'   => $field,
			'line'    => (int) $line,
			'message' => $message,
		);
	}

	private static function key_line( $json, $key ) {
		$pos = strpos( $json, '"' . $key . '"' );
		return false === $pos ? 1 : substr_count( $json, "\n", 0, $pos ) + 1;
	}

	/**
	 * json_decode không trả vị trí lỗi; dò từng dòng để đoán dòng lỗi đầu tiên.
	 */
	private static function json_error_line( $json ) {
		$lines = explode( "\n", $json );
		$total = count( $lines );
		for ( $i = 1; $i <= $total; $i++ ) {
			$chunk = implode( "\n", array_slice( $lines, 0, $i ) );
			if ( preg_match( '/,\s*[}\]]\s*$/', $chunk ) ) {
				return $i;
			}
		}
		return 1;
	}
}
