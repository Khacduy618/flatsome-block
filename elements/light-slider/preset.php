<?php
/**
 * Light Slider default content, shared by the UX Builder presets (builder.php) and the seeded UX Blocks.
 * Backgrounds follow the theme color scheme (Theme Options › Style › Colors) through Flatsome's own classes.
 *
 * @package FlatsomeBlockManager
 */

defined( 'ABSPATH' ) || exit;

$fbm_ls_scheme = array( 'primary', 'secondary', 'success' );

// 1. One slider, 6 banners: 4 / 3 / 2 per view.
$fbm_ls_banners = '';
for ( $fbm_ls_i = 0; $fbm_ls_i < 6; $fbm_ls_i++ ) {
	$fbm_ls_banners .= '[ux_banner height="360px" class="bg-' . $fbm_ls_scheme[ $fbm_ls_i % 3 ] . '-color"]' . "\n"
		. '[text_box width="85" position_x="10" position_y="85" text_align="left"]' . "\n"
		. '<h3>Banner ' . ( $fbm_ls_i + 1 ) . '</h3>' . "\n"
		. '<p>Short supporting text for this banner.</p>' . "\n"
		. '[button text="Shop now" color="white" style="outline" size="small" radius="99" link="#"]' . "\n"
		. '[/text_box]' . "\n"
		. '[/ux_banner]' . "\n\n";
}

// 2. One-column slider of 3 banners, meant to sit inside a narrow col. Timer stays at the option default.
$fbm_ls_single = static function ( $offset ) use ( $fbm_ls_scheme ) {
	$out = '[fbm_light_slider columns="1" gap="0" arrows="false"]' . "\n\n";
	for ( $b = 0; $b < 3; $b++ ) {
		$out .= '[ux_banner height="300px" class="bg-' . $fbm_ls_scheme[ ( $offset + $b ) % 3 ] . '-color"]' . "\n"
			. '[text_box width="80" position_x="50" position_y="50" text_align="center"]' . "\n"
			. '<h3>Collection ' . ( $offset + 1 ) . '.' . ( $b + 1 ) . '</h3>' . "\n"
			. '[button text="Discover" color="white" style="outline" size="small" radius="99" link="#"]' . "\n"
			. '[/text_box]' . "\n"
			. '[/ux_banner]' . "\n\n";
	}
	return $out . '[/fbm_light_slider]';
};

// 3. Four independent sliders in a 4-column row.
$fbm_ls_columns = '';
for ( $fbm_ls_c = 0; $fbm_ls_c < 4; $fbm_ls_c++ ) {
	$fbm_ls_columns .= '[col span="3" span__md="6" span__sm="12"]' . "\n\n"
		. $fbm_ls_single( $fbm_ls_c ) . "\n\n"
		. '[/col]' . "\n\n";
}

return array(
	'banners' => '[fbm_light_slider columns="4" columns__md="3" columns__sm="2" gap="16"]' . "\n\n"
		. $fbm_ls_banners
		. '[/fbm_light_slider]',
	'single'  => $fbm_ls_single( 0 ),
	'columns' => '[row style="small" width="full-width" v_align="equal"]' . "\n\n"
		. $fbm_ls_columns
		. '[/row]',
);
