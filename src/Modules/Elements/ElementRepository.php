<?php
/**
 * Element lưu ở CPT ẩn `fbm_element` + post meta.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

defined( 'ABSPATH' ) || exit;

final class ElementRepository {

	const POST_TYPE = 'fbm_element';

	const META_TAG        = '_fbm_tag';
	const META_CATEGORY   = '_fbm_category';
	const META_TARGETS    = '_fbm_targets';
	const META_DRAFT      = '_fbm_draft';
	const META_CHECK      = '_fbm_check';
	const META_LIVE       = '_fbm_live_version';
	const META_STATE      = '_fbm_state';
	const META_TRIP       = '_fbm_trip';
	const META_LAST_ERROR = '_fbm_last_error';
	const META_SAMPLE     = '_fbm_sample';

	const TARGETS = array( 'uxbuilder', 'gutenberg' );
	const STATES  = array( 'active', 'disabled', 'tripped' );

	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'label'               => 'FBM Elements',
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'supports'            => array( 'title' ),
				'rewrite'             => false,
				'query_var'           => false,
			)
		);
	}

	/**
	 * @return int[]
	 */
	public function all_ids() {
		return array_map(
			'intval',
			get_posts(
				array(
					'post_type'      => self::POST_TYPE,
					'post_status'    => 'publish',
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'orderby'        => 'title',
					'order'          => 'ASC',
				)
			)
		);
	}

	/**
	 * @return \WP_Post|null
	 */
	public function get( $id ) {
		$post = get_post( (int) $id );
		return ( $post && self::POST_TYPE === $post->post_type ) ? $post : null;
	}

	public function find_by_tag( $tag ) {
		$ids = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_TAG, // phpcs:ignore
				'meta_value'     => $tag, // phpcs:ignore
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * @return int|\WP_Error
	 */
	public function create( $name ) {
		return wp_insert_post(
			array(
				'post_type'   => self::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => wp_slash( $name ),
			),
			true
		);
	}

	public function rename( $id, $name ) {
		wp_update_post(
			array(
				'ID'         => (int) $id,
				'post_title' => wp_slash( $name ),
			)
		);
	}

	public function delete( $id ) {
		wp_delete_post( (int) $id, true );
	}

	public function meta( $id, $key, $default = null ) {
		$value = get_post_meta( (int) $id, $key, true );
		return '' === $value || null === $value ? $default : $value;
	}

	/**
	 * update_post_meta() tự bỏ dấu "\" -> phải wp_slash() để code không bị hỏng.
	 */
	public function set_meta( $id, $key, $value ) {
		if ( null === $value ) {
			delete_post_meta( (int) $id, $key );
			return;
		}
		update_post_meta( (int) $id, $key, wp_slash( $value ) );
	}

	/**
	 * @return array{schema: string, template: string, css: string, js: string}
	 */
	public function draft( $id ) {
		return self::normalize_payload( (array) $this->meta( $id, self::META_DRAFT, array() ) );
	}

	public function targets( $id ) {
		$targets = $this->meta( $id, self::META_TARGETS, self::TARGETS );
		$targets = array_values( array_intersect( self::TARGETS, (array) $targets ) );
		return $targets ? $targets : self::TARGETS;
	}

	public function state( $id ) {
		$state = (string) $this->meta( $id, self::META_STATE, 'active' );
		return in_array( $state, self::STATES, true ) ? $state : 'active';
	}

	public static function normalize_payload( array $payload ) {
		return array(
			'schema'   => isset( $payload['schema'] ) ? (string) $payload['schema'] : '',
			'template' => isset( $payload['template'] ) ? (string) $payload['template'] : '',
			'css'      => isset( $payload['css'] ) ? (string) $payload['css'] : '',
			'js'       => isset( $payload['js'] ) ? (string) $payload['js'] : '',
		);
	}

	public static function hash( array $payload ) {
		return sha1( (string) wp_json_encode( self::normalize_payload( $payload ) ) );
	}
}
