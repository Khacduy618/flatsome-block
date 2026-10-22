<?php
/**
 * Frame ratio of each part (left / right): key → label + height / width. Keys are not numeric (UX Builder compares
 * with ===). Shared by builder.php (labels, canvas template lookup) and the step's render.php.
 */
defined( 'ABSPATH' ) || exit;

return array(
	'1-1'  => array( 'label' => '1:1', 'value' => '1' ),
	'4-5'  => array( 'label' => '4:5', 'value' => '1.25' ),
	'3-4'  => array( 'label' => '3:4', 'value' => '1.3333' ),
	'4-3'  => array( 'label' => '4:3', 'value' => '0.75' ),
	'16-9' => array( 'label' => '16:9', 'value' => '0.5625' ),
);
