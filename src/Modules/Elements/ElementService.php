<?php
/**
 * Luồng của element: nháp -> kiểm tra (cổng 1) -> chạy thử (cổng 2) -> publish -> rollback.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

use FBM\Modules\Elements\Sandbox\Linter;
use FBM\Modules\Elements\Sandbox\SandboxRunner;

defined( 'ABSPATH' ) || exit;

final class ElementService {

	const TAG_PATTERN = '/^[a-z][a-z0-9_]{2,39}$/';

	/** @var ElementRepository */
	private $repo;

	/** @var VersionRepository */
	private $versions;

	/** @var Compiler */
	private $compiler;

	/** @var Linter */
	private $linter;

	/** @var SandboxRunner */
	private $runner;

	/** @var Registry */
	private $registry;

	public function __construct( ElementRepository $repo, VersionRepository $versions, Compiler $compiler, Linter $linter, SandboxRunner $runner, Registry $registry ) {
		$this->repo     = $repo;
		$this->versions = $versions;
		$this->compiler = $compiler;
		$this->linter   = $linter;
		$this->runner   = $runner;
		$this->registry = $registry;
	}

	/* ---------- Đọc ---------- */

	public function list_elements() {
		return array_map( array( $this, 'summary' ), $this->repo->all_ids() );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function get( $id ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		$out             = $this->summary( $id );
		$out['draft']    = $this->repo->draft( $id );
		$out['sample']   = (string) $this->repo->meta( $id, ElementRepository::META_SAMPLE, '{}' );
		$out['check']    = $this->current_check( $id );
		$out['versions'] = $this->versions->list_for( $id );
		return $out;
	}

	/**
	 * @return array|\WP_Error
	 */
	public function version( $id, $version ) {
		$row = $this->versions->get( $id, $version );
		return $row ? $row : new \WP_Error( 'fbm_not_found', __( 'Không tìm thấy phiên bản.', 'flatsome-block-manager' ), array( 'status' => 404 ) );
	}

	/* ---------- Tạo / sửa ---------- */

	/**
	 * @param array $data name, tag, category, targets, starter
	 * @return array|\WP_Error
	 */
	public function create( array $data ) {
		$notes   = array();
		$starter = null;
		if ( ! empty( $data['starter'] ) ) {
			$starter = Starters::get( (string) $data['starter'] );
			if ( ! $starter ) {
				return new \WP_Error( 'fbm_invalid', __( 'Không tìm thấy mẫu.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
			}
			// Mẫu cần element khác (ví dụ carousel cần element con) -> tạo kèm nếu chưa có.
			if ( $starter['requires'] ) {
				$required = Starters::get( $starter['requires'] );
				if ( $required && ! $this->repo->find_by_tag( $required['tag'] ) ) {
					$child = $this->create( array( 'starter' => $starter['requires'] ) );
					if ( ! is_wp_error( $child ) ) {
						/* translators: 1: element name, 2: tag */
						$notes[] = sprintf( __( 'Đã tạo kèm element "%1$s" [%2$s]. Hãy chạy thử và publish element này trước.', 'flatsome-block-manager' ), $required['name'], $required['tag'] );
					}
				}
			}
		}

		$name = trim( (string) ( $data['name'] ?? ( $starter ? $starter['name'] : '' ) ) );
		$tag  = strtolower( trim( (string) ( $data['tag'] ?? ( $starter ? $starter['tag'] : '' ) ) ) );
		if ( '' === $name ) {
			return new \WP_Error( 'fbm_invalid', __( 'Tên element không được để trống.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		$tag_error = $this->validate_tag( $tag );
		if ( $tag_error ) {
			return $tag_error;
		}

		$id = $this->repo->create( $name );
		if ( is_wp_error( $id ) ) {
			return $id;
		}

		$payload = $starter ? $starter['payload'] : array(
			'schema'   => "{\n  \"title\": { \"type\": \"textfield\", \"heading\": \"Tiêu đề\", \"default\": \"\" },\n  \"advanced_options\": \"@commons/advanced\"\n}\n",
			'template' => "<div id=\"<?php echo esc_attr( \$fbm_id ); ?>\" class=\"<?php echo esc_attr( trim( '{$tag} ' . \$class . ' ' . \$visibility ) ); ?>\">\n\t<?php echo esc_html( \$title ); ?>\n</div>\n",
			'css'      => '',
			'js'       => '',
		);

		$this->repo->set_meta( $id, ElementRepository::META_TAG, $tag );
		$this->repo->set_meta( $id, ElementRepository::META_CATEGORY, (string) ( $data['category'] ?? ( $starter ? $starter['category'] : 'Content' ) ) );
		$this->repo->set_meta( $id, ElementRepository::META_TARGETS, $this->sanitize_targets( $data['targets'] ?? null ) );
		$this->repo->set_meta( $id, ElementRepository::META_STATE, 'active' );
		$this->repo->set_meta( $id, ElementRepository::META_DRAFT, ElementRepository::normalize_payload( $payload ) );
		$this->repo->set_meta( $id, ElementRepository::META_SAMPLE, $starter ? $starter['sample'] : '{}' );

		$out          = $this->get( $id );
		$out['notes'] = $notes;
		return $out;
	}

	/**
	 * Lưu nháp + thông tin. Tự chạy cổng 1 (nhanh) và trả kết quả.
	 *
	 * @return array|\WP_Error
	 */
	public function update( $id, array $data ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		if ( isset( $data['name'] ) && '' !== trim( (string) $data['name'] ) ) {
			$this->repo->rename( $id, trim( (string) $data['name'] ) );
		}
		if ( isset( $data['category'] ) ) {
			$this->repo->set_meta( $id, ElementRepository::META_CATEGORY, sanitize_text_field( (string) $data['category'] ) );
		}
		if ( array_key_exists( 'targets', $data ) ) {
			$this->repo->set_meta( $id, ElementRepository::META_TARGETS, $this->sanitize_targets( $data['targets'] ) );
		}
		if ( isset( $data['sample'] ) ) {
			$this->repo->set_meta( $id, ElementRepository::META_SAMPLE, (string) $data['sample'] );
		}

		$draft_fields = array_intersect_key( $data, array_flip( array( 'schema', 'template', 'css', 'js' ) ) );
		if ( $draft_fields ) {
			$draft = ElementRepository::normalize_payload( array_merge( $this->repo->draft( $id ), array_map( 'strval', $draft_fields ) ) );
			$this->repo->set_meta( $id, ElementRepository::META_DRAFT, $draft );
			// Chỉ chạy lại cổng 1 khi code đổi, để không xoá kết quả chạy thử còn hiệu lực.
			$check = $this->repo->meta( $id, ElementRepository::META_CHECK, array() );
			if ( empty( $check['hash'] ) || ElementRepository::hash( $draft ) !== $check['hash'] ) {
				$this->store_check( $id, $draft, $this->strip_schema( $this->linter->lint( $draft, (string) $this->repo->meta( $id, ElementRepository::META_TAG ) ) ), null );
			}
		}

		$this->rebuild_registry();
		return $this->get( $id );
	}

	/* ---------- Cổng 1 + 2 ---------- */

	/**
	 * Kiểm tra code đang gõ (chưa lưu) hoặc bản nháp đã lưu.
	 *
	 * @return array|\WP_Error
	 */
	public function lint( $id, $payload = null ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		$payload = is_array( $payload ) ? ElementRepository::normalize_payload( $payload ) : $this->repo->draft( $id );
		return $this->strip_schema( $this->linter->lint( $payload, (string) $this->repo->meta( $id, ElementRepository::META_TAG ) ) );
	}

	/**
	 * @return array|\WP_Error check: {hash, lint, test, at}
	 */
	public function test( $id, $sample = null ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		if ( null !== $sample ) {
			$this->repo->set_meta( $id, ElementRepository::META_SAMPLE, (string) $sample );
		}
		$draft = $this->repo->draft( $id );
		$lint  = $this->linter->lint( $draft, (string) $this->repo->meta( $id, ElementRepository::META_TAG ) );
		if ( ! $lint['ok'] ) {
			return $this->store_check( $id, $draft, $this->strip_schema( $lint ), null );
		}

		$report = $this->runner->run(
			(string) $this->repo->meta( $id, ElementRepository::META_TAG ),
			$draft,
			$lint['schema'],
			(string) $this->repo->meta( $id, ElementRepository::META_SAMPLE, '{}' )
		);
		return $this->store_check( $id, $draft, $this->strip_schema( $lint ), $report );
	}

	/* ---------- Publish / rollback / trạng thái ---------- */

	/**
	 * @return array|\WP_Error
	 */
	public function publish( $id, $force_untested = false ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		$draft = $this->repo->draft( $id );
		$hash  = ElementRepository::hash( $draft );
		$check = $this->repo->meta( $id, ElementRepository::META_CHECK, array() );

		if ( empty( $check['hash'] ) || $check['hash'] !== $hash || empty( $check['lint']['ok'] ) ) {
			return $this->conflict( __( 'Bản nháp đã đổi hoặc chưa qua kiểm tra cú pháp. Bấm "Chạy thử" trước khi publish.', 'flatsome-block-manager' ) );
		}
		$status = isset( $check['test']['status'] ) ? $check['test']['status'] : '';
		if ( 'passed' !== $status && ! ( 'unavailable' === $status && $force_untested ) ) {
			if ( 'unavailable' === $status ) {
				return $this->conflict( __( 'Hosting chặn chạy thử. Gửi lại với force_untested=true nếu bạn chấp nhận publish khi chỉ qua kiểm tra cú pháp.', 'flatsome-block-manager' ) );
			}
			return $this->conflict( __( 'Lần chạy thử gần nhất chưa đạt.', 'flatsome-block-manager' ) );
		}

		$version = $this->versions->add( $id, $draft, $hash, (array) $check['test'] );
		$result  = $this->go_live( $id, $version, $draft, $hash );
		return is_wp_error( $result ) ? $result : $this->get( $id );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function rollback( $id, $version, $copy_to_draft = false ) {
		$row = $this->versions->get( $id, (int) $version );
		if ( ! $row ) {
			return new \WP_Error( 'fbm_not_found', __( 'Không tìm thấy phiên bản.', 'flatsome-block-manager' ), array( 'status' => 404 ) );
		}
		$result = $this->go_live( $id, $row['version'], $row['payload'], $row['hash'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		if ( $copy_to_draft ) {
			$this->repo->set_meta( $id, ElementRepository::META_DRAFT, $row['payload'] );
		}
		return $this->get( $id );
	}

	/**
	 * @return array|\WP_Error
	 */
	public function set_state( $id, $state ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		if ( ! in_array( $state, array( 'active', 'disabled' ), true ) ) {
			return new \WP_Error( 'fbm_invalid', 'state: active | disabled', array( 'status' => 400 ) );
		}
		$this->repo->set_meta( $id, ElementRepository::META_STATE, $state );
		if ( 'active' === $state ) {
			$this->repo->set_meta( $id, ElementRepository::META_TRIP, null );
		}
		$this->rebuild_registry();
		return $this->get( $id );
	}

	/**
	 * Gọi từ CircuitBreaker khi có lỗi fatal lúc render thật.
	 */
	public function trip( $id, array $info ) {
		$this->repo->set_meta( $id, ElementRepository::META_STATE, 'tripped' );
		$this->repo->set_meta( $id, ElementRepository::META_TRIP, $info );
		$this->rebuild_registry();
	}

	/**
	 * @return array|\WP_Error
	 */
	public function delete( $id ) {
		if ( ! $this->repo->get( $id ) ) {
			return $this->not_found();
		}
		$tag = (string) $this->repo->meta( $id, ElementRepository::META_TAG );
		if ( $tag ) {
			$this->compiler->delete_files_for( $tag );
		}
		$this->versions->delete_for( $id );
		$this->repo->delete( $id );
		$this->rebuild_registry();
		return array(
			'id'      => (int) $id,
			'deleted' => true,
		);
	}

	/** Biên dịch lại bản Live (khi file trong uploads bị mất). */
	public function recompile( $id ) {
		$live = (int) $this->repo->meta( $id, ElementRepository::META_LIVE, 0 );
		$row  = $live ? $this->versions->get( $id, $live ) : null;
		if ( ! $row ) {
			return false;
		}
		$tag = (string) $this->repo->meta( $id, ElementRepository::META_TAG );
		return ! is_wp_error( $this->compiler->compile( $tag, $row['version'], $row['payload'], $row['hash'] ) );
	}

	public function rebuild_registry() {
		$this->registry->rebuild( $this->repo, $this->versions );
	}

	/**
	 * Element bị ngắt (cho thông báo admin). Đọc từ registry để không query trên mọi trang admin.
	 *
	 * @return array<int, array{id: int, name: string, trip: mixed}>
	 */
	public function tripped() {
		$out = array();
		foreach ( $this->registry->all() as $entry ) {
			if ( 'tripped' === $entry['state'] ) {
				$out[] = array(
					'id'   => (int) $entry['id'],
					'name' => $entry['name'],
					'trip' => $this->repo->meta( $entry['id'], ElementRepository::META_TRIP, null ),
				);
			}
		}
		return $out;
	}

	/* ---------- Xuất / nhập (dùng bởi module Transfer) ---------- */

	public function export_element( $id ) {
		if ( ! $this->repo->get( $id ) ) {
			return null;
		}
		$live    = (int) $this->repo->meta( $id, ElementRepository::META_LIVE, 0 );
		$row     = $live ? $this->versions->get( $id, $live ) : null;
		$payload = $row ? $row['payload'] : $this->repo->draft( $id );
		return array(
			'tag'      => (string) $this->repo->meta( $id, ElementRepository::META_TAG ),
			'name'     => get_the_title( $id ),
			'category' => (string) $this->repo->meta( $id, ElementRepository::META_CATEGORY, 'Content' ),
			'targets'  => $this->repo->targets( $id ),
			'sample'   => (string) $this->repo->meta( $id, ElementRepository::META_SAMPLE, '{}' ),
			'payload'  => $payload,
		);
	}

	public function find_id_by_tag( $tag ) {
		return $this->repo->find_by_tag( $tag );
	}

	/**
	 * Element nhập vào luôn ở dạng nháp: phải chạy thử trên site mới trước khi publish.
	 *
	 * @param string $conflict skip | overwrite | rename
	 * @return array{tag: string, action: string, id: int, message?: string}
	 */
	public function import_element( array $data, $conflict, $dry_run ) {
		$tag      = strtolower( (string) ( $data['tag'] ?? '' ) );
		$existing = $this->repo->find_by_tag( $tag );
		$action   = $existing ? $conflict : 'create';

		if ( 'rename' === $action ) {
			$base = $tag;
			$i    = 2;
			do {
				$tag = substr( $base, 0, 36 ) . '_' . $i++;
			} while ( $this->repo->find_by_tag( $tag ) || shortcode_exists( $tag ) );
		}
		$result = array(
			'tag'    => $tag,
			'action' => $action,
			'id'     => (int) $existing,
		);
		if ( $dry_run || 'skip' === $action ) {
			return $result;
		}

		$payload = ElementRepository::normalize_payload( (array) ( $data['payload'] ?? array() ) );
		if ( 'overwrite' === $action ) {
			$updated = $this->update(
				$existing,
				$payload + array(
					'name'     => (string) ( $data['name'] ?? '' ),
					'category' => (string) ( $data['category'] ?? 'Content' ),
					'sample'   => (string) ( $data['sample'] ?? '{}' ),
				)
			);
			if ( is_wp_error( $updated ) ) {
				$result['action']  = 'error';
				$result['message'] = $updated->get_error_message();
			}
			return $result;
		}

		$created = $this->create(
			array(
				'name'     => (string) ( $data['name'] ?? $tag ),
				'tag'      => $tag,
				'category' => (string) ( $data['category'] ?? 'Content' ),
				'targets'  => $data['targets'] ?? null,
			)
		);
		if ( is_wp_error( $created ) ) {
			$result['action']  = 'error';
			$result['message'] = $created->get_error_message();
			return $result;
		}
		$this->update( $created['id'], $payload + array( 'sample' => (string) ( $data['sample'] ?? '{}' ) ) );
		$result['id'] = (int) $created['id'];
		return $result;
	}

	/* ---------- Nội bộ ---------- */

	private function go_live( $id, $version, array $payload, $hash ) {
		$tag   = (string) $this->repo->meta( $id, ElementRepository::META_TAG );
		$files = $this->compiler->compile( $tag, $version, $payload, $hash );
		if ( is_wp_error( $files ) ) {
			return $files;
		}
		$this->repo->set_meta( $id, ElementRepository::META_LIVE, (int) $version );
		$this->repo->set_meta( $id, ElementRepository::META_STATE, 'active' );
		$this->repo->set_meta( $id, ElementRepository::META_TRIP, null );
		$this->repo->set_meta( $id, ElementRepository::META_LAST_ERROR, null );
		$this->rebuild_registry();
		return true;
	}

	private function summary( $id ) {
		$live      = (int) $this->repo->meta( $id, ElementRepository::META_LIVE, 0 );
		$live_row  = $live ? $this->versions->get( $id, $live ) : null;
		$draft_sha = ElementRepository::hash( $this->repo->draft( $id ) );
		$check     = $this->current_check( $id );
		$tag       = (string) $this->repo->meta( $id, ElementRepository::META_TAG );

		return array(
			'id'           => (int) $id,
			'name'         => get_the_title( $id ),
			'tag'          => $tag,
			'shortcode'    => '[' . $tag . ']',
			'block_name'   => Adapters\GutenbergAdapter::block_name( $tag ),
			'category'     => (string) $this->repo->meta( $id, ElementRepository::META_CATEGORY, 'Content' ),
			'targets'      => $this->repo->targets( $id ),
			'state'        => $this->repo->state( $id ),
			'live_version' => $live,
			'dirty'        => ! $live_row || $live_row['hash'] !== $draft_sha,
			'check_status' => $this->check_status( $check ),
			'trip'         => $this->repo->meta( $id, ElementRepository::META_TRIP, null ),
			'last_error'   => $this->repo->meta( $id, ElementRepository::META_LAST_ERROR, null ),
			'modified'     => get_post_modified_time( 'c', true, $id ),
		);
	}

	/**
	 * Kết quả kiểm tra chỉ còn giá trị khi khớp hash bản nháp hiện tại.
	 */
	private function current_check( $id ) {
		$check = $this->repo->meta( $id, ElementRepository::META_CHECK, null );
		if ( ! is_array( $check ) ) {
			return null;
		}
		$check['stale'] = $check['hash'] !== ElementRepository::hash( $this->repo->draft( $id ) );
		return $check;
	}

	/** none | lint_failed | untested | passed | failed | unavailable | stale */
	private function check_status( $check ) {
		if ( ! $check ) {
			return 'none';
		}
		if ( ! empty( $check['stale'] ) ) {
			return 'stale';
		}
		if ( empty( $check['lint']['ok'] ) ) {
			return 'lint_failed';
		}
		return isset( $check['test']['status'] ) ? $check['test']['status'] : 'untested';
	}

	private function store_check( $id, array $draft, array $lint, $test ) {
		$check = array(
			'hash' => ElementRepository::hash( $draft ),
			'lint' => $lint,
			'test' => $test,
			'at'   => gmdate( 'c' ),
		);
		$this->repo->set_meta( $id, ElementRepository::META_CHECK, $check );
		$check['stale'] = false;
		return $check;
	}

	private function strip_schema( array $lint ) {
		unset( $lint['schema'] );
		return $lint;
	}

	private function validate_tag( $tag ) {
		if ( ! preg_match( self::TAG_PATTERN, $tag ) ) {
			return new \WP_Error( 'fbm_invalid_tag', __( 'Tag gồm 3–40 ký tự: chữ thường, số, "_", bắt đầu bằng chữ. Ví dụ: hero_countdown.', 'flatsome-block-manager' ), array( 'status' => 400 ) );
		}
		if ( $this->repo->find_by_tag( $tag ) ) {
			return new \WP_Error( 'fbm_tag_exists', sprintf( __( 'Đã có element dùng tag [%s].', 'flatsome-block-manager' ), $tag ), array( 'status' => 409 ) );
		}
		if ( shortcode_exists( $tag ) && ! $this->registry->get( $tag ) ) {
			return new \WP_Error( 'fbm_tag_taken', sprintf( __( 'Shortcode [%s] đã được theme hoặc plugin khác dùng.', 'flatsome-block-manager' ), $tag ), array( 'status' => 409 ) );
		}
		return null;
	}

	private function sanitize_targets( $targets ) {
		$targets = array_values( array_intersect( ElementRepository::TARGETS, (array) $targets ) );
		return $targets ? $targets : ElementRepository::TARGETS;
	}

	private function conflict( $message ) {
		return new \WP_Error( 'fbm_publish_blocked', $message, array( 'status' => 409 ) );
	}

	private function not_found() {
		return new \WP_Error( 'fbm_not_found', __( 'Không tìm thấy element.', 'flatsome-block-manager' ), array( 'status' => 404 ) );
	}
}
