<?php
/**
 * Danh sách element có bản Live, lưu trong một option (autoload) để mỗi request
 * không phải query. Được dựng lại mỗi khi element thay đổi.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

defined( 'ABSPATH' ) || exit;

final class Registry {

	const OPTION = 'fbm_elements_registry';

	/** @var array|null */
	private $entries;

	/**
	 * @return array<string, array> tag => entry
	 */
	public function all() {
		if ( null === $this->entries ) {
			$value         = get_option( self::OPTION, array() );
			$this->entries = is_array( $value ) ? $value : array();
		}
		return $this->entries;
	}

	public function get( $tag ) {
		$all = $this->all();
		return isset( $all[ $tag ] ) ? $all[ $tag ] : null;
	}

	public function rebuild( ElementRepository $elements, VersionRepository $versions ) {
		$entries = array();
		foreach ( $elements->all_ids() as $id ) {
			$live = (int) get_post_meta( $id, ElementRepository::META_LIVE, true );
			if ( ! $live ) {
				continue;
			}
			$version = $versions->get( $id, $live );
			if ( ! $version ) {
				continue;
			}
			$parsed = Schema::parse( $version['payload']['schema'] );
			$schema = $parsed['data'];
			$tag    = (string) get_post_meta( $id, ElementRepository::META_TAG, true );

			$entries[ $tag ] = array(
				'id'        => (int) $id,
				'tag'       => $tag,
				'name'      => get_the_title( $id ),
				'category'  => (string) get_post_meta( $id, ElementRepository::META_CATEGORY, true ),
				'targets'   => $elements->targets( $id ),
				'state'     => $elements->state( $id ),
				'version'   => $live,
				'hash'      => $version['hash'],
				'files'     => Compiler::file_names( $tag, $live, $version['payload'], $version['hash'] ),
				'schema'    => $schema,
				'container' => Schema::is_container( $schema ),
			);
		}
		update_option( self::OPTION, $entries, true );
		$this->entries = $entries;
	}

	public function forget() {
		$this->entries = null;
	}
}
