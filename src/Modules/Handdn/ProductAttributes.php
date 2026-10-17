<?php
/**
 * Shortcode [handdn_product_attributes] + element UX Builder "Product Attributes".
 *
 * Markup giữ nguyên class `handdn-product-attributes__*` để dùng CSS sẵn có của site.
 * Chỉ hiện thuộc tính có tick "Visible on the product page".
 *
 * @package FBM
 */

namespace FBM\Modules\Handdn;

defined( 'ABSPATH' ) || exit;

final class ProductAttributes {

	const TAG = 'handdn_product_attributes';

	public function boot() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_action( 'ux_builder_setup', array( $this, 'register_element' ), 20 );
	}

	public function register_element() {
		if ( ! function_exists( 'add_ux_builder_shortcode' ) ) {
			return;
		}
		add_ux_builder_shortcode(
			self::TAG,
			array(
				'name'      => __( 'Product Attributes', 'flatsome-block-manager' ),
				'category'  => __( 'Shop', 'flatsome-block-manager' ),
				'thumbnail' => FBM_URL . 'assets/img/element.svg',
				'options'   => array(
					'id'               => array(
						'type'        => 'select',
						'heading'     => __( 'Sản phẩm', 'flatsome-block-manager' ),
						'description' => __( 'Để trống = sản phẩm hiện tại (trang sản phẩm, Custom Product Layout).', 'flatsome-block-manager' ),
						'full_width'  => true,
						'config'      => array(
							'multiple'    => false,
							'placeholder' => __( 'Sản phẩm hiện tại', 'flatsome-block-manager' ),
							'postSelect'  => array(
								'post_type' => array( 'product' ),
							),
						),
					),
					'advanced_options' => require get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php',
				),
			)
		);
	}

	/**
	 * @param array|string $atts
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'id'         => '',
				'class'      => '',
				'visibility' => '',
			),
			$atts,
			self::TAG
		);

		if ( ! function_exists( 'wc_get_product' ) ) {
			return '';
		}

		$product = $this->resolve_product( absint( $atts['id'] ) );
		$items   = $product ? $this->items( $product ) : array();

		if ( ! $items ) {
			return $this->empty_state( $product );
		}

		$classes = array( 'handdn-product-attributes' );
		foreach ( array( $atts['class'], $atts['visibility'] ) as $extra ) {
			foreach ( preg_split( '/\s+/', (string) $extra, -1, PREG_SPLIT_NO_EMPTY ) as $class ) {
				$classes[] = sanitize_html_class( $class );
			}
		}

		ob_start();
		?>
		<div class="<?php echo esc_attr( implode( ' ', array_filter( $classes ) ) ); ?>">
			<?php foreach ( $items as $item ) : ?>
				<div class="handdn-product-attributes__item">
					<div class="handdn-product-attributes__label">
						<?php echo esc_html( $item['label'] ); ?>
					</div>
					<div class="handdn-product-attributes__values">
						<?php foreach ( $item['values'] as $value ) : ?>
							<span class="handdn-product-attributes__value"><?php echo esc_html( $value ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Không ghi đè global $product (code gốc gán lại global khi nó chưa phải WC_Product).
	 *
	 * @param int $id
	 * @return \WC_Product|null
	 */
	private function resolve_product( $id ) {
		if ( $id ) {
			$product = wc_get_product( $id );
			return $product && 'publish' === $product->get_status() ? $product : null;
		}

		global $product;
		if ( $product instanceof \WC_Product ) {
			return $product;
		}

		$queried = get_queried_object_id();
		$found   = $queried && 'product' === get_post_type( $queried ) ? wc_get_product( $queried ) : null;
		return $found ? $found : null;
	}

	/**
	 * @param \WC_Product $product
	 * @return array<int, array{label: string, values: string[]}>
	 */
	private function items( $product ) {
		$items = array();

		foreach ( $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof \WC_Product_Attribute || ! $attribute->get_visible() ) {
				continue;
			}

			$name = $attribute->get_name();

			if ( $attribute->is_taxonomy() ) {
				$values = wc_get_product_terms( $product->get_id(), $name, array( 'fields' => 'names' ) );
				if ( is_wp_error( $values ) ) {
					continue;
				}
			} else {
				$values = $attribute->get_options();
			}

			$values = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $values ) ), 'strlen' ) );
			if ( ! $values ) {
				continue;
			}

			$items[] = array(
				'label'  => wc_attribute_label( $name, $product ),
				'values' => $values,
			);
		}

		return $items;
	}

	/**
	 * Ngoài UX Builder: không in gì. Trong UX Builder: hiện ghi chú để biết element đang ở đó.
	 *
	 * @param \WC_Product|null $product
	 * @return string
	 */
	private function empty_state( $product ) {
		if ( ! function_exists( 'ux_builder_is_active' ) || ! ux_builder_is_active() ) {
			return '';
		}
		$text = $product
			? __( 'Product Attributes: sản phẩm này chưa có thuộc tính nào bật "Visible on the product page".', 'flatsome-block-manager' )
			: __( 'Product Attributes: chọn một sản phẩm, hoặc đặt element trong Custom Product Layout.', 'flatsome-block-manager' );
		return '<p class="handdn-product-attributes is-empty op-7">' . esc_html( $text ) . '</p>';
	}
}
