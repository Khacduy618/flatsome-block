<?php
/**
 * 10 bố cục mẫu (templates/<slug>/) cho UX Builder › Add Content › Import ("Insert a template", tab Flatsome).
 * category = HANDDN được lưu nhưng Flatsome 3.20 không tách tab theo category.
 * Không có nút hay tab trong admin.
 *
 * @package FBM
 */

namespace FBM;

defined( 'ABSPATH' ) || exit;

final class Templates {

	const CATEGORY = 'HANDDN';

	/** @var array|null */
	private $all = null;

	public function boot() {
		add_action( 'ux_builder_setup', array( $this, 'register' ), 20 );
	}

	/**
	 * @return array<string, array{name: string, order: int, post_types: string[], content: string, thumb: string}>
	 */
	public function all() {
		if ( null !== $this->all ) {
			return $this->all;
		}
		$this->all = array();
		foreach ( (array) glob( FBM_DIR . 'templates/*/meta.php' ) as $file ) {
			$dir     = dirname( $file );
			$slug    = basename( $dir );
			$meta    = include $file;
			$content = is_readable( $dir . '/content.txt' ) ? (string) file_get_contents( $dir . '/content.txt' ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( ! is_array( $meta ) || '' === trim( $content ) ) {
				continue;
			}
			$this->all[ $slug ] = array(
				'name'       => isset( $meta['name'] ) ? (string) $meta['name'] : $slug,
				'order'      => isset( $meta['order'] ) ? (int) $meta['order'] : 99,
				'post_types' => isset( $meta['post_types'] ) ? (array) $meta['post_types'] : array( 'page', 'blocks' ),
				'content'    => $content,
				'thumb'      => FBM_URL . 'templates/' . $slug . '/thumb.svg',
			);
		}
		uasort(
			$this->all,
			static function ( $a, $b ) {
				return $a['order'] - $b['order'];
			}
		);
		return $this->all;
	}

	public function register() {
		if ( ! function_exists( 'ux_builder_add_template' ) ) {
			return;
		}
		foreach ( $this->all() as $slug => $t ) {
			ux_builder_add_template(
				'fbm_' . $slug,
				array(
					'post_types' => $t['post_types'],
					'category'   => self::CATEGORY,
					'name'       => $t['name'],
					'thumbnail'  => $t['thumb'],
					'content'    => $t['content'],
				)
			);
		}
	}
}
