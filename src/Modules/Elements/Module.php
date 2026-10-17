<?php
/**
 * Module Elements: viết UX Builder element / Gutenberg block bằng code, có lint, sandbox, phiên bản, cầu dao.
 *
 * @package FBM
 */

namespace FBM\Modules\Elements;

use FBM\Core\Capabilities;
use FBM\Core\Container;
use FBM\Core\ModuleInterface;
use FBM\Modules\Elements\Adapters\GutenbergAdapter;
use FBM\Modules\Elements\Adapters\UxBuilderAdapter;
use FBM\Modules\Elements\Sandbox\CircuitBreaker;
use FBM\Modules\Elements\Sandbox\Linter;
use FBM\Modules\Elements\Sandbox\SandboxEndpoint;
use FBM\Modules\Elements\Sandbox\SandboxRunner;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	const CLEANUP_HOOK = 'fbm_cleanup_sandbox';

	public function id() {
		return 'elements';
	}

	public function register( Container $c ) {
		$c->set( 'elements.repository', static function () { return new ElementRepository(); } );
		$c->set( 'elements.versions', static function () { return new VersionRepository(); } );
		$c->set( 'elements.compiler', static function () { return new Compiler(); } );
		$c->set( 'elements.registry', static function () { return new Registry(); } );
		$c->set( 'elements.linter', static function () { return new Linter(); } );
		$c->set(
			'elements.runner',
			static function ( Container $c ) {
				return new SandboxRunner( $c->get( 'elements.compiler' ) );
			}
		);
		$c->set(
			'elements.service',
			static function ( Container $c ) {
				return new ElementService(
					$c->get( 'elements.repository' ),
					$c->get( 'elements.versions' ),
					$c->get( 'elements.compiler' ),
					$c->get( 'elements.linter' ),
					$c->get( 'elements.runner' ),
					$c->get( 'elements.registry' )
				);
			}
		);
		$c->set(
			'elements.renderer',
			static function ( Container $c ) {
				return new Renderer(
					$c->get( 'elements.registry' ),
					$c->get( 'elements.compiler' ),
					$c->get( 'settings' ),
					static function ( $id ) use ( $c ) {
						return $c->get( 'elements.service' )->recompile( $id );
					}
				);
			}
		);
	}

	public function boot( Container $c ) {
		CircuitBreaker::boot(
			static function ( $id, array $info ) use ( $c ) {
				$c->get( 'elements.service' )->trip( $id, $info );
			}
		);

		add_action(
			'init',
			static function () use ( $c ) {
				ElementRepository::register_post_type();
				wp_register_script( 'fbm-runtime', FBM_URL . 'assets/runtime/fbm-runtime.js', array(), FBM_VERSION, true );

				$renderer = $c->get( 'elements.renderer' );
				foreach ( $c->get( 'elements.registry' )->all() as $tag => $entry ) {
					if ( ! shortcode_exists( $tag ) ) {
						add_shortcode(
							$tag,
							static function ( $atts, $content = '', $shortcode_tag = '' ) use ( $renderer, $tag ) {
								return $renderer->render( $tag, $atts, $content );
							}
						);
					}
				}
			},
			20
		);

		( new UxBuilderAdapter( $c->get( 'elements.registry' ), $c->get( 'elements.renderer' ), $c->get( 'elements.compiler' ) ) )->boot();
		( new GutenbergAdapter( $c->get( 'elements.registry' ), $c->get( 'elements.renderer' ), $c->get( 'elements.compiler' ) ) )->boot();

		add_action(
			'rest_api_init',
			static function () use ( $c ) {
				( new ElementsController( $c->get( 'elements.service' ) ) )->register_routes();
				( new SandboxEndpoint() )->register_routes();
			}
		);

		// Thông báo element bị ngắt ở mọi trang admin.
		add_action(
			'admin_notices',
			static function () use ( $c ) {
				if ( ! Capabilities::can_edit_code() ) {
					return;
				}
				foreach ( $c->get( 'elements.service' )->tripped() as $el ) {
					$trip = is_array( $el['trip'] ) ? $el['trip'] : array();
					printf(
						'<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <code>%3$s</code>%4$s. %5$s <a href="%6$s">%7$s</a></p></div>',
						esc_html( sprintf( __( 'Element "%s" đã bị ngắt.', 'flatsome-block-manager' ), $el['name'] ) ),
						esc_html__( 'Lỗi:', 'flatsome-block-manager' ),
						esc_html( isset( $trip['message'] ) ? $trip['message'] : '' ),
						! empty( $trip['line'] ) ? esc_html( sprintf( __( ' (template dòng %d)', 'flatsome-block-manager' ), $trip['line'] ) ) : '',
						esc_html__( 'Element đã tự tắt để trang không lỗi.', 'flatsome-block-manager' ),
						esc_url( admin_url( 'admin.php?page=fbm#/elements/' . $el['id'] ) ),
						esc_html__( 'Mở element', 'flatsome-block-manager' )
					);
				}
			}
		);

		// Dọn file chạy thử bị bỏ lại.
		add_action(
			self::CLEANUP_HOOK,
			static function () use ( $c ) {
				$c->get( 'elements.compiler' )->cleanup_sandbox_files();
			}
		);
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CLEANUP_HOOK );
		}
	}
}
