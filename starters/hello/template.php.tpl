<?php
/**
 * Mỗi option trong schema là một biến cùng tên: $title, $bg, $align.
 * Biến có sẵn: $atts (mảng), $content, $tag, $fbm_id (id duy nhất cho mỗi lần render).
 * $content chỉ có giá trị với element container (xem tab Hướng dẫn › Element cha / con).
 * $class và $visibility đến từ "@commons/advanced".
 */
$classes = trim( 'fbm-hello ' . $class . ' ' . $visibility );
?>
<div id="<?php echo esc_attr( $fbm_id ); ?>" class="<?php echo esc_attr( $classes ); ?>" style="background: <?php echo esc_attr( $bg ); ?>; text-align: <?php echo esc_attr( $align ); ?>">
	<h3 class="fbm-hello__title"><?php echo esc_html( $title ); ?></h3>
	<?php if ( $text ) : ?>
		<div class="fbm-hello__body"><?php echo wp_kses_post( wpautop( $text ) ); ?></div>
	<?php endif; ?>
</div>
