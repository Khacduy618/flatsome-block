<?php
/**
 * Shared accent colors for Pill Badge: key => label. Each key maps to a theme/HANDDN token
 * in pill-badge.css (`.fbm-pill--<key>`); there is no free color picker (spec section 4.2).
 */
defined( 'ABSPATH' ) || exit;

return array(
	'primary' => __( 'Primary', 'flatsome-block-manager' ),
	'success' => __( 'Success', 'flatsome-block-manager' ),
	'alert'   => __( 'Alert', 'flatsome-block-manager' ),
	'muted'   => __( 'Muted', 'flatsome-block-manager' ),
);
