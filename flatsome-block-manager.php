<?php
/*
Plugin Name: Flatsome Block Manager
Description: Quản lý UX Blocks của Flatsome: CRUD, theo dõi nơi dùng block, xuất/nhập giữa các site, REST API và tạo UX Builder element bằng code có kiểm tra cú pháp + chạy thử chống crash.
Version: 1.0.0
Author: khacduy
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.4
Requires PHP: 7.4
Text Domain: flatsome-block-manager
*/

//bắt đầu cho các đoạn code này ở bên dưới

defined( 'ABSPATH' ) || exit;

define( 'FBM_VERSION', '1.0.0' );
define( 'FBM_DB_VERSION', 1 );
define( 'FBM_FILE', __FILE__ );
define( 'FBM_DIR', plugin_dir_path( __FILE__ ) );
define( 'FBM_URL', plugin_dir_url( __FILE__ ) );

require_once FBM_DIR . 'src/autoload.php';

register_activation_hook( __FILE__, array( 'FBM\\Core\\Installer', 'activate' ) );

// Flatsome đăng ký CPT `blocks` khi theme được nạp, nên khởi động sau after_setup_theme.
add_action( 'after_setup_theme', array( 'FBM\\Plugin', 'start' ), 20 );
