=== Flatsome Block Manager ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Quản lý UX Blocks của Flatsome: CRUD, bật/tắt + hẹn giờ, theo dõi nơi dùng, xuất/nhập, REST API, và tạo UX Builder element / Gutenberg block bằng code có kiểm tra an toàn.

== Cài đặt ==

1. Plugins > Add New > Upload Plugin > chọn flatsome-block-manager.zip > Install Now > Activate.
2. Theme Flatsome (hoặc child theme) phải đang bật.
3. Mở menu "Block Manager". Lần đầu, bấm "Quét ngay" để tạo chỉ mục nơi dùng.
4. Hướng dẫn chi tiết: tab "Hướng dẫn" trong plugin. Tài liệu kỹ thuật: DESIGN.md.

== Khẩn cấp ==

Nếu một element tự viết làm lỗi site và không vào được admin, thêm vào wp-config.php:
define( 'FBM_SAFE_MODE', true );

== Changelog ==

= 1.0.0 =
* Phát hành đầu tiên.
