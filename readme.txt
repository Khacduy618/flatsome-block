=== Flatsome Block Manager ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later

Danh sách UX Blocks, element của plugin và element của Flatsome; mỗi dòng Bật / Ẩn / Tắt. Kèm 6 element WooCommerce viết sẵn và 10 block mẫu trong UX Builder › Import.

== Cài đặt ==

1. Plugins > Add New > Upload Plugin > chọn flatsome-block-manager.zip > Install Now > Activate.
2. Theme Flatsome (hoặc child theme) phải đang bật.
3. Mở menu "Block Manager" (ngay dưới menu Flatsome).
4. Tab "Element Flatsome" cần mở UX Builder một lần để plugin đọc danh sách element của theme.

== Bật / Ẩn / Tắt ==

* Bật: dùng bình thường.
* Ẩn: không có trong Add elements của UX Builder (với UX Block: không có trong ô chọn của element Block). Chỗ đã dùng vẫn hiển thị.
* Tắt: như Ẩn, và không hiển thị ngoài site. Người biên tập thấy khung nét đứt ở chỗ đó.

Element khung trang (Section, Row, Column, Grid, Stack, Text, Block) luôn Bật.
Tắt một thứ đang được dùng thì plugin hỏi lại trước.
Tạo, sửa, xoá UX Block vẫn làm ở menu UX Blocks của Flatsome.

== Element viết sẵn ==

* Products [fbm_products]: slider / lưới sản phẩm WooCommerce; nguồn mới, nổi bật, giảm giá, bán chạy, đánh giá cao, chọn tay, cross-sell, upsell, liên quan.
* Product Attributes [handdn_product_attributes]: bảng thuộc tính của sản phẩm.
* Product Compare [fbm_product_compare]: bảng so sánh 2–4 sản phẩm.
* Reviews [fbm_reviews]: khung bọc widget Judge.me (cần plugin Judge.me).
* Before / After [fbm_before_after] và Before / After Carousel [fbm_ba_carousel].
* Nút "Customize this strap in 3D": tự gắn vào trang sản phẩm có meta 3D-link.

Element dùng markup và class của Flatsome / WooCommerce nên tự theo màu và CSS của site. CSS/JS riêng chỉ nạp khi element có trên trang.

== Nâng cấp từ 1.x ==

* UX Block đang tắt (hoặc đang bị hẹn giờ giấu) chuyển thành Tắt. Hẹn giờ bị bỏ.
* Trình viết element bằng code của 1.x bị gỡ cùng dữ liệu của nó (element tự viết, phiên bản, file trong uploads/fbm-elements).
* [fbm_product_slider] chạy tiếp bằng Products. Kiểu thẻ, màu, bo góc của 1.x không còn.
* Tag 1.x không còn: khách thấy trống, người biên tập thấy khung nét đứt. Admin hiện một thông báo liệt kê các tag này.

== Gỡ plugin ==

Xoá trạng thái Bật / Ẩn / Tắt, danh sách element đã ghi và chỉ mục nơi dùng. UX Block và nội dung trang được giữ nguyên.

== Changelog ==

= 2.0.0 =
* Viết lại: admin còn 3 tab danh sách (UX Blocks · Element plugin · Element Flatsome), mỗi dòng Bật / Ẩn / Tắt.
* Thêm element Products, Product Compare, Reviews (Judge.me); chuyển Product Attributes, nút 3D, Before / After sang cấu trúc mới.
* Thêm 10 block mẫu HANDDN trong UX Builder › Import.
* Bỏ trình viết code, sandbox, phiên bản, xuất/nhập, Gutenberg, trang Cài đặt, hẹn giờ.

= 1.0.0 =
* Phát hành đầu tiên.
