<?php
/*
Plugin Name: Flatsome Block Manager
Description: Lists UX Blocks, plugin elements and Flatsome elements, each with On / Hidden / Off. Ships custom UX Builder elements, preset UX Blocks and Import templates.
Version: 2.0.0
Author: khacduy
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.4
Requires PHP: 7.4
Text Domain: flatsome-block-manager
*/

// Plugin bootstrap below.

defined( 'ABSPATH' ) || exit;

define( 'FBM_VERSION', '2.0.0' );
define( 'FBM_DB_VERSION', 1 );
define( 'FBM_FILE', __FILE__ );
define( 'FBM_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBM_URL', plugin_dir_url( __FILE__ ) );

require_once FBM_DIR . 'src/autoload.php';

// Load Custom UX Builder Element & Preset Block: Smart Product Slider.
require_once FBM_DIR . 'inc/ajax-product-tabs.php';
require_once FBM_DIR . 'shortcodes/smart-product-slider.php';
require_once FBM_DIR . 'inc/Elements/smart-product-slider.php';
require_once FBM_DIR . 'inc/Blocks/smart-product-slider-block.php';
require_once FBM_DIR . 'inc/Blocks/light-slider-block.php';

register_activation_hook( __FILE__, array( 'FBM\\Installer', 'activate' ) );

// Flatsome registers the `blocks` CPT when the theme loads, so start after after_setup_theme.
add_action( 'after_setup_theme', array( 'FBM\\Plugin', 'start' ), 20 );
