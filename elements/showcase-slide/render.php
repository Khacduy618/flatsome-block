<?php
/**
 * [fbm_showcase_slide]: một slide của Product Showcase Slider, dựng bằng class của Flatsome: row/col (2 cột trên desktop,
 * xếp dọc trên điện thoại), khung .image-cover như element Image, viền .is-border (.is-dashed cho "drop zone" bên trái).
 * image_size, image_height, slide_index, slide_total do [fbm_product_showcase] truyền xuống; Flickity của theme đặt
 * .is-selected / aria-hidden cho slide đang hiện.
 * Biến có sẵn: $atts, $content, $tag.
 */
defined( 'ABSPATH' ) || exit;

$a = shortcode_atts(
	array(
		'left_title'   => __( 'Place your product here', 'flatsome-block-manager' ),
		'left_image'   => '',
		'left_label'   => '',
		'right_title'  => __( 'Generated images will appear here', 'flatsome-block-manager' ),
		'right_image'  => '',
		'right_label'  => '',
		'image_size'   => 'large',
		'image_height' => '100%',
		'slide_index'  => '1',
		'slide_total'  => '1',
		'class'        => '',
	),
	$atts,
	$tag
);

$fbm_size   = in_array( $a['image_size'], array( 'medium', 'medium_large', 'large', 'full' ), true ) ? $a['image_size'] : 'large';
$fbm_height = array_key_exists( $a['image_height'], require dirname( __DIR__ ) . '/product-showcase/heights.php' ) ? $a['image_height'] : '100%';
$fbm_index  = max( 1, absint( $a['slide_index'] ) );
$fbm_total  = max( $fbm_index, absint( $a['slide_total'] ) );
$fbm_first  = 1 === $fbm_index;

// Ảnh không có alt trong Media Library thì lấy nhãn chú thích làm alt. Slide sau slide đầu tải lười.
$fbm_image = static function ( $id, $label ) use ( $fbm_size, $fbm_first ) {
	$id   = absint( $id );
	$attr = array( 'draggable' => 'false' );
	if ( ! $fbm_first ) {
		$attr['loading'] = 'lazy';
	}
	if ( $id && '' === trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) && '' !== trim( $label ) ) {
		$attr['alt'] = $label;
	}
	return $id ? wp_get_attachment_image( $id, $fbm_size, false, $attr ) : '';
};

$fbm_left  = $fbm_image( $a['left_image'], $a['left_label'] );
$fbm_right = $fbm_image( $a['right_image'], $a['right_label'] );
if ( ! $fbm_left || ! $fbm_right ) {
	echo FBM\Placeholder::empty( __( 'Showcase Slide: chọn đủ ảnh gốc (trái) và ảnh kết quả (phải).', 'flatsome-block-manager' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	return;
}

// Ô slide của Flickity là div bọc ngoài: .align-middle (align-self: center) đặt trên ô position: absolute sẽ đẩy ô lệch lên nửa chiều cao.
$fbm_classes = array( 'fbm-ps__slide', 'pb' ); // pb: chỗ cho chấm của slider.
foreach ( preg_split( '/\s+/', $a['class'], -1, PREG_SPLIT_NO_EMPTY ) as $fbm_c ) {
	$fbm_classes[] = sanitize_html_class( $fbm_c );
}

$fbm_panel = static function ( $side, $num, $title, $img, $label, $border ) use ( $fbm_height ) {
	?>
	<figure class="col medium-5 small-12 large-5"><div class="col-inner">
		<p class="uppercase mb-half"><strong><?php echo (int) $num; ?>.</strong><?php if ( '' !== trim( $title ) ) : ?> <span><?php echo esc_html( $title ); ?></span><?php endif; ?></p>
		<div class="fbm-ps__frame fbm-ps__frame--<?php echo esc_attr( $side ); ?> img-inner image-cover" style="padding-top:<?php echo esc_attr( $fbm_height ); ?>">
			<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_get_attachment_image. ?>
			<div class="<?php echo esc_attr( $border ); ?>"></div>
		</div>
		<?php if ( '' !== trim( $label ) ) : ?>
			<figcaption class="is-small op-7 text-center pt-half"><?php echo esc_html( $label ); ?></figcaption>
		<?php endif; ?>
	</div></figure>
	<?php
};
?>
<div class="<?php echo esc_attr( implode( ' ', $fbm_classes ) ); ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( $fbm_index . ' / ' . $fbm_total ); ?>"><div class="row row-small align-middle align-center">
	<?php $fbm_panel( 'input', 1, $a['left_title'], $fbm_left, $a['left_label'], 'is-border is-dashed' ); ?>
	<div class="col medium-2 small-12 large-2 text-center"><div class="col-inner">
		<span class="button icon circle is-outline mb-0" aria-hidden="true"><i class="icon-angle-right hide-for-small"></i><i class="icon-angle-down show-for-small"></i></span>
	</div></div>
	<?php $fbm_panel( 'output', 2, $a['right_title'], $fbm_right, $a['right_label'], 'is-border' ); ?>
</div></div>
