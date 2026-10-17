<?php
/**
 * Plugin bootstrap: kiểm tra yêu cầu, nạp module, gọi register() rồi boot().
 *
 * @package FBM
 */

namespace FBM;

use FBM\Core\Container;
use FBM\Core\Installer;
use FBM\Core\ModuleInterface;
use FBM\Core\Requirements;
use FBM\Core\Settings;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	/** @var Plugin|null */
	private static $instance;

	/** @var Container */
	private $container;

	/** @var ModuleInterface[] */
	private $modules = array();

	public static function start() {
		self::instance()->boot();
	}

	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function container() {
		return self::instance()->container;
	}

	private function __construct() {
		$this->container = new Container();
	}

	public function boot() {
		$missing = Requirements::missing();
		if ( $missing ) {
			Requirements::show_notice( $missing );
			return;
		}

		Installer::maybe_upgrade();

		$settings = new Settings();
		$this->container->set( 'settings', $settings );

		foreach ( $this->module_classes() as $class ) {
			/** @var ModuleInterface $module */
			$module = new $class();
			if ( $settings->module_enabled( $module->id() ) ) {
				$this->modules[ $module->id() ] = $module;
			}
		}

		foreach ( $this->modules as $module ) {
			$module->register( $this->container );
		}
		foreach ( $this->modules as $module ) {
			$module->boot( $this->container );
		}

		// Build của @wordpress/scripts cần "react-jsx-runtime" (WP 6.6+). Shim cho WP 6.4–6.5.
		add_action(
			'init',
			static function () {
				if ( ! wp_script_is( 'react-jsx-runtime', 'registered' ) ) {
					wp_register_script( 'react-jsx-runtime', FBM_URL . 'assets/runtime/react-jsx-runtime.js', array( 'react' ), FBM_VERSION, true );
				}
			},
			1
		);

		do_action( 'fbm/booted', $this->container );
	}

	public function has_module( $id ) {
		return isset( $this->modules[ $id ] );
	}

	/**
	 * Thứ tự quan trọng: Blocks trước, các module phụ thuộc sau.
	 *
	 * @return string[]
	 */
	private function module_classes() {
		return apply_filters(
			'fbm/modules',
			array(
				Modules\Blocks\Module::class,
				Modules\Usage\Module::class,
				Modules\Elements\Module::class,
				Modules\Transfer\Module::class,
				Modules\Handdn\Module::class,
				Modules\Settings\Module::class,
				Modules\Admin\Module::class,
			)
		);
	}
}
