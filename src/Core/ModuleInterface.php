<?php
/**
 * Hợp đồng chung của mọi module.
 *
 * - register(): chỉ khai báo service vào container, KHÔNG add hook.
 * - boot(): add hook, đăng ký REST route... Module bị tắt thì không được gọi.
 *
 * @package FBM
 */

namespace FBM\Core;

defined( 'ABSPATH' ) || exit;

interface ModuleInterface {

	/** Mã module, dùng trong Settings để bật/tắt. */
	public function id();

	public function register( Container $c );

	public function boot( Container $c );
}
