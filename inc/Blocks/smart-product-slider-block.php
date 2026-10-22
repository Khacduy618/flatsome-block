<?php
/**
 * Preset UX Block: Smart Product Slider Showcase
 *
 * Automatically registers and creates a Preset UX Block (post_type 'blocks')
 * following Element-First Architecture.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns standard shortcode content of the Preset UX Block composed of:
 * - Parent Grid Container: [section], [row], [col]
 * - Child UI Elements: [fbm_pill_badge], [title], [fbm_avatar_stack]
 * - Core Custom Element: [uxb_smart_product_slider]
 *
 * @return string
 */
function uxb_get_smart_product_slider_block_content() {
	return '[section padding="60px" padding__sm="40px"]' . "\n\n"
		. '[row h_align="center"]' . "\n\n"
		. '[col span="8" span__sm="12" align="center"]' . "\n\n"
		. '[fbm_pill_badge text="Trending Collection" tone="primary" align="center"]' . "\n\n"
		. '[title style="bold-center" text="Featured Products by Category" tag_name="h2"]' . "\n\n"
		. '[/col]' . "\n\n"
		. '[/row]' . "\n\n"
		. '[row]' . "\n\n"
		. '[col span="12" span__sm="12"]' . "\n\n"
		. '[uxb_smart_product_slider posts_per_page="8" autoplay_speed="2000" columns="4" animate="fadeInUp" animate_speed="0.7"]' . "\n\n"
		. '[/col]' . "\n\n"
		. '[/row]' . "\n\n"
		. '[row h_align="center"]' . "\n\n"
		. '[col span="12" align="center"]' . "\n\n"
		. '[fbm_avatar_stack rating="4.9" text="Over 10,000+ satisfied customers nationwide" align="center"]' . "\n\n"
		. '[/col]' . "\n\n"
		. '[/row]' . "\n\n"
		. '[/section]';
}

/**
 * Automatically creates Preset UX Block in database if not already present
 */
function uxb_auto_create_preset_ux_block() {
	if ( ! post_type_exists( 'blocks' ) ) {
		return;
	}

	$slug = 'smart-product-slider-block';

	$existing = get_posts(
		array(
			'name'           => $slug,
			'post_type'      => 'blocks',
			'post_status'    => 'any',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	if ( empty( $existing ) ) {
		$block_id = wp_insert_post(
			array(
				'post_title'   => 'Smart Product Slider Showcase',
				'post_name'    => $slug,
				'post_type'    => 'blocks',
				'post_status'  => 'publish',
				'post_content' => uxb_get_smart_product_slider_block_content(),
			)
		);

		if ( $block_id && ! is_wp_error( $block_id ) ) {
			update_post_meta( $block_id, '_fbm_preset', 'smart_product_slider' );
		}
	}
}

add_action( 'init', 'uxb_auto_create_preset_ux_block', 30 );
