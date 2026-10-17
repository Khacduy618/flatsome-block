<?php
/**
 * Nút "Customize this strap in 3D" trên trang sản phẩm (Flatsome + WooCommerce + APF).
 *
 * - Nút trong form: in ở woocommerce_before_add_to_cart_form, JS chuyển xuống ngay sau `.apf-fields-wrap` (nếu có).
 * - Nút trên ảnh: in thẳng vào hook flatsome_product_image_tools_top (mọi layout gallery của Flatsome đều gọi hook này),
 *   không cần JS/template như bản snippet cũ.
 *
 * Link lấy từ meta sản phẩm, chỉ nhận http/https.
 *
 * @package FBM
 */

namespace FBM\Handdn;

defined( 'ABSPATH' ) || exit;

final class Product3DButton {

	const SCRIPT = 'fbm-handdn-3d-button';

	/** Khoá trạng thái trong States (chỉ Bật / Tắt, không phải element nên không có Ẩn). */
	const STATE_KEY = 'handdn_3d_button';

	/** @var \FBM\States|null */
	private $states;

	public function __construct( $states = null ) {
		$this->states = $states;
	}

	private function enabled() {
		return ! $this->states || 'off' !== $this->states->get( self::STATE_KEY );
	}

	/** @var array<int, string> Cache link theo product id trong 1 request. */
	private $urls = array();

	public function boot() {
		// Snippet cũ (functions.php / Code Snippets) còn chạy thì không in thêm, tránh nút bị nhân đôi.
		if ( function_exists( 'handdn_3d_get_product_url' ) ) {
			return;
		}
		add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'form_button' ), 1 );
		add_action( 'flatsome_product_image_tools_top', array( $this, 'gallery_button' ), 100 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * @param int $product_id
	 * @return string URL đã kiểm tra, hoặc ''.
	 */
	public function url( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $product_id ) {
			return '';
		}
		if ( isset( $this->urls[ $product_id ] ) ) {
			return $this->urls[ $product_id ];
		}

		$keys = (array) apply_filters(
			'fbm/handdn/3d_meta_keys',
			array( '3D-link', '3D link', '3d-link', '3d link', '3D_Link', '3d_link', '3DLink', '3dlink', '3D_link', '3d_url', '3D URL' )
		);

		$found = '';
		foreach ( $keys as $key ) {
			$value = get_post_meta( $product_id, (string) $key, true );
			if ( ! is_string( $value ) || '' === trim( $value ) ) {
				continue;
			}
			$url    = esc_url_raw( trim( $value ), array( 'http', 'https' ) );
			$scheme = strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) );
			if ( $url && filter_var( $url, FILTER_VALIDATE_URL ) && in_array( $scheme, array( 'http', 'https' ), true ) ) {
				$found = $url;
				break;
			}
		}

		$this->urls[ $product_id ] = $found;
		return $found;
	}

	private function current_id() {
		return function_exists( 'is_product' ) && is_product() ? (int) get_queried_object_id() : 0;
	}

	public function form_button() {
		echo $this->form_html( $this->current_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape trong form_html.
	}

	public function gallery_button() {
		echo $this->gallery_html( $this->current_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escape trong gallery_html.
	}

	/** Nút trong form, đặt trước form thêm vào giỏ (JS chuyển xuống sau field APF). */
	public function form_html( $product_id ) {
		$url = $this->enabled() ? $this->url( $product_id ) : '';
		if ( ! $url ) {
			return '';
		}
		return '<a class="plain" href="' . esc_url( $url ) . '" rel="nofollow" data-handdn-3d="form">'
			. '<div class="icon-box featured-box featured-box_button featured-box_center icon-box-right text-right">'
			. '<div class="icon-box-img" style="width: 20px"><div class="icon"><div class="icon-inner">'
			. $this->svg( 'form' ) // SVG tĩnh trong plugin.
			. '</div></div></div>'
			. '<div class="icon-box-text last-reset"><p><strong>' . esc_html__( 'Customize this strap in 3D', 'flatsome-block-manager' ) . '</strong></p></div>'
			. '</div></a>';
	}

	/** Nút ở góc trên phải ảnh sản phẩm (hook image tools của Flatsome). */
	public function gallery_html( $product_id ) {
		$url = $this->enabled() ? $this->url( $product_id ) : '';
		if ( ! $url ) {
			return '';
		}
		return '<a class="plain" href="' . esc_url( $url ) . '" rel="nofollow" data-handdn-3d="gallery">'
			. '<div class="icon-box featured-box bagde-whitebg items-center icon-box-left text-left is-small shrink">'
			. '<div class="icon-box-img" style="width: 20px"><div class="icon"><div class="icon-inner">'
			. $this->svg( 'gallery' ) // SVG tĩnh trong plugin.
			. '</div></div></div>'
			. '<div class="icon-box-text last-reset"><div class="text"><strong>' . esc_html__( 'CUSTOMIZE THIS STRAP IN 3D', 'flatsome-block-manager' ) . '</strong></div></div>'
			. '</div></a>';
	}

	/** JS chỉ để đưa nút form xuống sau phần field APF; chỉ nạp khi sản phẩm có link 3D. */
	public function enqueue() {
		$id = $this->current_id();
		if ( ! $this->enabled() || ! $id || ! $this->url( $id ) ) {
			return;
		}
		wp_enqueue_style( self::SCRIPT, FBM_URL . 'assets/handdn/3d-button.css', array(), FBM_VERSION );
		wp_enqueue_script(
			self::SCRIPT,
			FBM_URL . 'assets/handdn/3d-button.js',
			array(),
			FBM_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
	}

	/**
	 * Icon gốc; ID clipPath gắn tiền tố riêng cho mỗi vị trí để không trùng ID trong trang.
	 *
	 * @param string $prefix form|gallery
	 * @return string
	 */
	private function svg( $prefix ) {
		static $raw = null;
		if ( null === $raw ) {
			$file = FBM_DIR . 'assets/handdn/3d-icon.svg';
			$raw  = is_readable( $file ) ? (string) file_get_contents( $file ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}
		if ( '' === $raw ) {
			return '';
		}
		$svg = preg_replace( '/wall-cardclip-path(-[23])?/', 'handdn-' . $prefix . '-wall-cardclip-path$1', $raw );
		return preg_replace( '/^<svg /', '<svg aria-hidden="true" focusable="false" ', $svg, 1 );
	}
}
