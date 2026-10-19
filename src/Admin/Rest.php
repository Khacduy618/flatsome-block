<?php
/**
 * REST fbm/v2: danh sách 3 tab, đổi trạng thái, dựng chỉ mục nơi dùng.
 *
 * Chỉ nhận: tag đã biết (Catalog / Registry / trạng thái ảo) hoặc ID post type `blocks`,
 * cùng 1 trong 3 trạng thái. Mọi tham số khác bị bỏ qua. Element cần manage_options,
 * UX Block cần edit_pages. Nonce: cookie auth của WP (X-WP-Nonce).
 *
 * @package FBM
 */

namespace FBM\Admin;

use FBM\BlockStates;
use FBM\Catalog;
use FBM\Elements\Registry;
use FBM\States;
use FBM\Usage\UsageIndex;

defined( 'ABSPATH' ) || exit;

final class Rest {

	const NS = 'fbm/v2';

	const TYPES = array( 'ux_block', 'plugin', 'flatsome' );

	/** @var States */
	private $states;

	/** @var Catalog */
	private $catalog;

	/** @var BlockStates */
	private $blocks;

	/** @var UsageIndex */
	private $usage;

	/** @var Registry */
	private $registry;

	public function __construct( States $states, Catalog $catalog, BlockStates $blocks, UsageIndex $usage, Registry $registry ) {
		$this->states   = $states;
		$this->catalog  = $catalog;
		$this->blocks   = $blocks;
		$this->usage    = $usage;
		$this->registry = $registry;
	}

	public function boot() {
		add_action( 'rest_api_init', array( $this, 'routes' ) );
	}

	public function routes() {
		$type = array(
			'required' => true,
			'type'     => 'string',
			'enum'     => self::TYPES,
		);

		register_rest_route(
			self::NS,
			'/items',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'items' ),
				'permission_callback' => array( $this, 'can_type' ),
				'args'                => array( 'type' => $type ),
			)
		);

		register_rest_route(
			self::NS,
			'/state',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'set_state' ),
				'permission_callback' => array( $this, 'can_type' ),
				'args'                => array(
					'type'  => $type,
					'id'    => array(
						'required'          => true,
						'validate_callback' => array( $this, 'valid_id' ),
					),
					'state' => array(
						'required' => true,
						'type'     => 'string',
						'enum'     => States::VALID,
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/usage/rebuild',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rebuild' ),
				'permission_callback' => static function () {
					return current_user_can( 'edit_pages' );
				},
				'args'                => array(
					'offset' => array(
						'type'    => 'integer',
						'minimum' => 0,
						'default' => 0,
					),
				),
			)
		);
	}

	/** UX Block: edit_pages. Element plugin / Flatsome: manage_options. */
	public function can_type( \WP_REST_Request $request ) {
		return current_user_can( 'ux_block' === $request->get_param( 'type' ) ? 'edit_pages' : 'manage_options' );
	}

	public function valid_id( $value, \WP_REST_Request $request ) {
		if ( 'ux_block' === $request->get_param( 'type' ) ) {
			return is_numeric( $value ) && (int) $value > 0;
		}
		return is_string( $value ) && (bool) preg_match( '/^[a-z0-9_-]{1,64}$/i', $value );
	}

	public function items( \WP_REST_Request $request ) {
		$type = $request->get_param( 'type' );
		if ( 'ux_block' === $type ) {
			$items = $this->block_items();
		} elseif ( 'plugin' === $type ) {
			$items = $this->plugin_items();
		} else {
			$items = $this->flatsome_items();
		}
		return rest_ensure_response(
			array(
				'items'         => $items,
				'catalog_empty' => ! $this->catalog->flatsome(),
				'usage_built'   => $this->usage->is_built(),
			)
		);
	}

	public function set_state( \WP_REST_Request $request ) {
		$type  = $request->get_param( 'type' );
		$state = $request->get_param( 'state' );

		if ( 'ux_block' === $type ) {
			$id = (int) $request->get_param( 'id' );
			$r  = $this->blocks->set( $id, $state );
		} else {
			$id = (string) $request->get_param( 'id' );
			$r  = $this->belongs( $type, $id ) ? $this->states->set( $id, $state ) : new \WP_Error( 'fbm_unknown_tag', __( 'Không có element này.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( is_wp_error( $r ) ) {
			return $r;
		}
		return rest_ensure_response(
			array(
				'id'    => $id,
				'state' => $state,
			)
		);
	}

	public function rebuild( \WP_REST_Request $request ) {
		return rest_ensure_response( $this->usage->rebuild_batch( (int) $request->get_param( 'offset' ) ) );
	}

	/**
	 * Tag thuộc đúng tab. Tag khung trang luôn đi tiếp để States trả lỗi "khoá".
	 */
	private function belongs( $type, $tag ) {
		if ( in_array( $tag, States::LOCKED, true ) ) {
			return true;
		}
		if ( 'plugin' === $type ) {
			return in_array( $tag, Catalog::VIRTUAL, true ) || array_key_exists( $tag, $this->registry->all() );
		}
		return array_key_exists( $tag, $this->catalog->flatsome() );
	}

	private function block_items() {
		$items = array();
		foreach ( $this->blocks->rows() as $row ) {
			$items[] = array(
				'id'       => $row['id'],
				'name'     => html_entity_decode( $row['title'], ENT_QUOTES, 'UTF-8' ),
				'sub'      => '[block id="' . $row['slug'] . '"]',
				'category' => '',
				'state'    => $row['state'],
				'states'   => States::VALID,
				'locked'   => false,
				'usage'    => $row['usage'],
			);
		}
		return $items;
	}

	private function plugin_items() {
		$counts = $this->usage->element_counts();
		$items  = array();
		foreach ( $this->registry->all() as $tag => $el ) {
			$items[] = $this->element_item( $tag, $el['name'], $el['category'], $counts );
		}
		$items[] = array(
			'id'       => 'handdn_3d_button',
			'name'     => __( 'Nút "Customize this strap in 3D"', 'flatsome-block-manager' ),
			'sub'      => __( 'tự gắn', 'flatsome-block-manager' ),
			'category' => __( 'Trang sản phẩm có meta 3D-link · không phải element nên không có Ẩn', 'flatsome-block-manager' ),
			'state'    => $this->states->get( 'handdn_3d_button' ),
			'states'   => array( 'on', 'off' ),
			'locked'   => false,
			'usage'    => null,
		);
		return $items;
	}

	private function flatsome_items() {
		$counts = $this->usage->element_counts();
		$items  = array();
		foreach ( $this->catalog->flatsome() as $tag => $el ) {
			$items[] = $this->element_item( $tag, $el['name'], $el['category'], $counts );
		}
		return $items;
	}

	private function element_item( $tag, $name, $category, array $counts ) {
		return array(
			'id'       => (string) $tag,
			'name'     => (string) $name,
			'sub'      => '[' . $tag . ']',
			'category' => (string) $category,
			'state'    => $this->states->get( $tag ),
			'states'   => States::VALID,
			'locked'   => in_array( $tag, States::LOCKED, true ),
			'usage'    => isset( $counts[ $tag ] ) ? (int) $counts[ $tag ] : 0,
		);
	}
}
