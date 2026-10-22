<?php
/**
 * Layout templates (templates/<slug>/) for UX Builder › Add Content › Import ("Insert a template", Flatsome tab),
 * also registered as presets of the Section element (Add elements › Section).
 * category = HANDDN is stored, but Flatsome 3.20 does not split tabs by category.
 * No button or tab in the admin.
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
		add_filter( 'ux_builder_shortcode_data_section', array( $this, 'add_section_presets' ) );
	}

	/**
	 * Every template is also a preset of Flatsome's Section element (Add elements › Section), so it can be
	 * inserted anywhere, any number of times; "Insert a template" only shows on an empty page.
	 * Only templates whose content is a single root [section] qualify: Flatsome keeps the first root node.
	 *
	 * @param array $data Section element data.
	 * @return array
	 */
	public function add_section_presets( $data ) {
		$data['presets'] = isset( $data['presets'] ) ? (array) $data['presets'] : array();
		foreach ( $this->all() as $t ) {
			$content = trim( $t['content'] );
			if ( 0 !== strpos( $content, '[section' ) || 1 !== preg_match_all( '/\[section[\s\]]/', $content ) ) {
				continue;
			}
			$data['presets'][] = array(
				'name'      => $t['name'],
				'thumbnail' => $t['thumb'],
				'content'   => $content,
			);
		}
		return $data;
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
				'post_types' => isset( $meta['post_types'] ) ? (array) $meta['post_types'] : array( 'page', 'blocks', 'product' ),
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
