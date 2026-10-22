<?php
/**
 * Preset UX Block for Light Slider (post type 'blocks'), embedded with [block id="light-slider-4-columns"]:
 * a 4-column row with one independent slider of 3 banners per column.
 *
 * Created once on the first admin request, then never touched again so edits made in UX Builder are kept.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

/**
 * Block slug => title and content.
 *
 * @return array<string, array{title:string, content:string}>
 */
function fbm_light_slider_blocks() {
	$presets = require FBM_DIR . 'elements/light-slider/preset.php';

	return array(
		'light-slider-4-columns' => array(
			'title'   => 'Light Slider - 4 Columns',
			'content' => '[section padding="40px" padding__sm="24px"]' . "\n\n"
				. $presets['columns'] . "\n\n"
				. '[/section]',
		),
	);
}

/**
 * Seeds missing blocks once. The option flag keeps every later request free of queries; add_option() is the
 * lock, since option_name is a unique key only one of two concurrent admin requests can create it.
 */
function fbm_seed_light_slider_blocks() {
	if ( get_option( 'fbm_light_slider_seeded' ) || ! post_type_exists( 'blocks' ) || ! current_user_can( 'edit_pages' ) ) {
		return;
	}
	if ( ! add_option( 'fbm_light_slider_seeded', 1, '', false ) ) {
		return;
	}

	foreach ( fbm_light_slider_blocks() as $slug => $block ) {
		$existing = get_posts(
			array(
				'name'           => $slug,
				'post_type'      => 'blocks',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);
		if ( $existing ) {
			continue;
		}

		$block_id = wp_insert_post(
			array(
				'post_title'   => $block['title'],
				'post_name'    => $slug,
				'post_type'    => 'blocks',
				'post_status'  => 'publish',
				'post_content' => $block['content'],
			)
		);
		if ( $block_id && ! is_wp_error( $block_id ) ) {
			update_post_meta( $block_id, '_fbm_preset', $slug );
		}
	}
}

add_action( 'admin_init', 'fbm_seed_light_slider_blocks' );
