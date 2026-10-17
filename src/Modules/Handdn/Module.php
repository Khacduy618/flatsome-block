<?php
/**
 * Module HANDDN: các tính năng riêng của site HANDDN, code sẵn trong plugin.
 *
 * - [handdn_product_attributes]: bảng thuộc tính sản phẩm (markup khớp CSS HANDDN), có element UX Builder.
 * - Nút "Customize this strap in 3D": hiện khi sản phẩm có meta link 3D.
 *
 * @package FBM
 */

namespace FBM\Modules\Handdn;

use FBM\Core\Container;
use FBM\Core\ModuleInterface;

defined( 'ABSPATH' ) || exit;

final class Module implements ModuleInterface {

	public function id() {
		return 'handdn';
	}

	public function register( Container $c ) {}

	public function boot( Container $c ) {
		( new ProductAttributes() )->boot();
		( new Product3DButton() )->boot();
	}
}
