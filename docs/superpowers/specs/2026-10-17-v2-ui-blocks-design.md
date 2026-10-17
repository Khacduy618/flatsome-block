# Flatsome Block Manager v2 — 10 UI blocks (Phase 1 + Phase 2)

Ngày: 2026-10-17 · Trạng thái: **chờ duyệt** · Design page: https://claude.ai/artifact/PMKFjzxBTvU1mejw9DQryY

Tài liệu này gồm kết quả Phase 1 (Audit) và Phase 2 (Research & Specification) theo brief "Senior WordPress UI/UX Designer & Flatsome UX Builder Engineer". Chưa có code nào được viết cho v2. Phase 3 chỉ bắt đầu sau khi tài liệu này được duyệt.

---

## 0. Quyết định đã duyệt (từ các vòng trước)

> **Cập nhật lần 4 (2026-10-17, yêu cầu người dùng):** chỉ giữ danh sách. Admin có **3 tab theo loại**: UX Blocks · Element plugin · Element Flatsome. Mỗi dòng có Bật / Ẩn / Tắt, kể cả UX Block. Ẩn UX Block là loại nó khỏi ô tìm của element Block qua filter `ux_builder_ajax_posts_search_posts_get_posts_args`. Bỏ: trang Cài đặt, tab Thư viện, tab Hướng dẫn, hẹn giờ. 10 block mẫu chỉ có trong UX Builder › Import. Giữ cột "N nơi dùng" (chỉ đọc, dùng để hỏi lại trước khi Tắt). Các dòng bên dưới mâu thuẫn với ghi chú này thì theo ghi chú này. Ước tính: 6,25 ngày.

| Chủ đề | Quyết định |
|---|---|
| An toàn | Bỏ toàn bộ tính năng viết code từ admin (editor, sandbox, phiên bản, xuất/nhập, Gutenberg, CRUD UX Block). Element là file trong plugin. |
| Admin | 4 tab: Elements · UX Blocks · Thư viện · Hướng dẫn. |
| Element | Trạng thái Bật / Ẩn / Tắt. Element khung trang (Section, Row, Column, Grid, Stack, Text, Block, Root) bị khoá. |
| UX Block | Bật / Tắt + hẹn giờ (giữ từ v1). |
| WooCommerce | Thẻ sản phẩm render bằng `wc_get_template_part( 'content', 'product' )` + hook `woocommerce_*_shop_loop_item`. |
| Nơi dùng | Chỉ UX Builder. |
| Thư viện | Mỗi mẫu dùng được 2 cách: nút "Thêm vào UX Blocks" (tạo UX Block nháp) và UX Builder › Add Content › Import (`ux_builder_add_template()`). |
| CSS | Ưu tiên class và biến của Flatsome, sau đó class/biến của CSS HANDDN. Xem mục 4.2 cho phần CSS riêng được phép. |

---

## 1. Phase 1 — Audit

Môi trường kiểm tra: DDEV `handdn` (https://handdn.ddev.site), WordPress 7.1.x, PHP 8.2, MySQL 8.0, WooCommerce 11.2.1.

### 1.1 Kết quả

| Hạng mục | Thực tế (đã kiểm tra) | Ảnh hưởng |
|---|---|---|
| Theme | Flatsome 3.20.11, **không có child theme** | Không sửa theme. Mọi code nằm trong plugin. |
| CSS tuỳ biến | Flatsome › Advanced › Custom CSS: 38.627 ký tự (+ 1.882 tablet, 1.523 mobile). WP Additional CSS: trống. | Đây là CSS HANDDN. Block dùng lại class/biến của nó. |
| Biến màu | `--primary-color` và `--fs-color-primary` do Flatsome sinh từ Theme Options (`inc/functions/function-custom-css.php:8`). CSS HANDDN thêm `--secondary-color`, `--text-color`, `--color-notactive`, `--color-grey`, `--success-color`, `--global-radius`. | Token cho design system. |
| Sản phẩm | 50 sản phẩm, **tất cả là simple**. 0 thuộc tính, 0 biến thể, 0 đánh giá, 0 sản phẩm thiếu ảnh. 31 danh mục (Alligator, Apple Watch, Buckles, Calfskin, Care…). | Ngành hàng: dây đồng hồ & phụ kiện da thủ công. Cần tạo dữ liệu test cho thuộc tính, biến thể, đánh giá (mục 7.2). |
| `handdn-product-attributes` | CSS có style cho class này nhưng **không tìm thấy code sinh markup** trong site local. | Trên site thật có thể do snippet/plugin khác sinh ra. Element Attributes sẽ dùng đúng tên class này; cần xác nhận để không bị hiển thị trùng (Q1). |
| Đánh giá | CSS có selector của CusRev (`ivole-`) và Judge.me (`jdgm-`). Local không có plugin nào. | Judge.me lưu đánh giá ngoài WordPress, element không đọc được (Q2). |
| Plugin đang bật | woocommerce, woocommerce-paypal-payments, jetpack, mailpoet, google-listings-and-ads, pinterest/reddit/snapchat-for-woocommerce. Block Manager **chưa bật**. | Không thấy plugin nào khác đăng ký UX Builder element. |
| Accordion Flatsome | Có `aria-expanded`, `aria-controls`, `aria-labelledby` (`inc/shortcodes/accordion.php:126-134`). | Block FAQ dùng element gốc. |
| Bố cục mẫu Flatsome | API `ux_builder_add_template( $id, [ post_types, category, name, thumbnail, template, content ] )`, nội dung là file `.txt` shortcode (`inc/builder/templates/`). | Thư viện dùng đúng API này. |
| Quy ước id | `'_id' => 'tên-' . rand()` trong default của shortcode. | Element mới làm theo. |

### 1.2 Điểm có thể xung đột

1. CSS HANDDN có rule toàn cục ảnh hưởng thẻ sản phẩm: `.badge { height: 0 !important }`, `.badge-inner`, `.flickity-button-icon`, `a.woocommerce-LoopProduct-link` (giới hạn 3 dòng). Element dùng markup chuẩn nên **nhận đúng** các style này. Đây là chủ ý.
2. CSS HANDDN dòng `//12202024` không phải comment CSS hợp lệ, làm rule `.variation-Imagepreview p` phía sau mất hiệu lực. Không liên quan plugin; đã báo người dùng.
3. Element mới dùng tiền tố `fbm_` cho shortcode và `.fbm-` cho class riêng, nên không trùng tên với Flatsome và HANDDN.

---

## 2. Phase 2 — Research

### 2.1 Nguồn và kết luận áp dụng

| # | Nguồn | Kết luận | Áp dụng cho block |
|---|---|---|---|
| R1 | [Baymard — Product Spec Sheet](https://baymard.com/product-page/benchmark/page-designs/product-spec-sheet/) | Người dùng dựa vào bảng thông số; lỗi phổ biến: không nhóm thông số (38%), không nhất quán giữa sản phẩm cùng loại (32%), dùng thuật ngữ khó hiểu (50%). | 5 Attributes, 6 Comparison |
| R2 | [Baymard — Comparison Tool](https://baymard.com/ecommerce-product-lists/benchmark/page-types/comparison-tool) | Người dùng gặp khó với công cụ so sánh; nguyên nhân chính là thông số lưu không nhất quán và thiếu giải thích dễ hiểu. | 6 Comparison: so sánh số ít sản phẩm do người biên tập chọn, chỉ dùng thuộc tính chung |
| R3 | [Baymard — Ratings distribution](https://baymard.com/research-articles/user-ratings-distribution-summary) | Người dùng dựa vào phân bố sao nhiều hơn nội dung từng đánh giá; thiếu phân bố làm người dùng nghi đánh giá giả. | 8 Testimonials: có thanh phân bố sao, dùng đánh giá thật |
| R4 | [Baymard — Cart cross-sells](https://baymard.com/research-articles/product-recommendations-cart) | 52% site gợi ý sản phẩm không liên quan; nên ưu tiên phụ kiện tương thích; tránh ép người dùng quyết định. | 10 Cross-sell: nguồn cross-sell/upsell do người bán chọn, không popup |
| R5 | [Baymard — Homepage & category](https://baymard.com/homepage-and-category-usability) | Trang danh mục có ảnh đại diện test tốt nhưng 32% site không có. Trên mobile cần thay đổi bố cục, không chỉ thu nhỏ. | 2 Category Collection |
| R6 | [Baymard — Free shipping display](https://baymard.com/guidelines/836-where-to-display-free-shipping-information) | Thông tin miễn phí vận chuyển rất quan trọng nhưng hay bị bỏ qua nếu không nổi bật. | 7 Trust & Service |
| R7 | [Delugs — handcrafted leather straps](https://delugs.com/collections/handcrafted-leather-straps) | Kể quy trình thủ công theo bước, nêu thời gian làm, chất liệu, dải kích thước 16–24 mm, đổi trả 14 ngày, tư vấn chọn dây. | 4 Benefits, 7 Trust, 9 FAQ |
| R8 | [Watch Gecko — handmade strap](https://www.watchgecko.com/products/handmade-and-handstiched-leather-watch-strap-mocha) | Ghi chú về vân da tự nhiên, chỉ khâu cùng màu: nói trước khác biệt tự nhiên của da. | 4 Benefits, 9 FAQ |
| R9 | [Milano Straps](https://milanostraps.com/en-nz/collections/compatible-watch-straps) | Danh mục theo dòng đồng hồ tương thích và theo loại da. | 2 Category Collection |
| R10 | [StrategiQ — Bellroy](https://strategiq.co/hub/bellroy-bags-website-of-the-month/) | Ảnh chụp có chủ đích, chữ có serif tinh tế, CTA rõ, kể chuyện thủ công. Trang sản phẩm công bố chất liệu. | 3 Editorial Hero, 4 Benefits |
| R11 | [W3C APG — Accordion](https://www.w3.org/WAI/ARIA/apg/patterns/accordion/) (qua các bản mirror) | Header là nút trong heading, `aria-expanded`, `aria-controls`, Enter/Space để mở. | 9 FAQ (Flatsome đã đáp ứng) |
| R12 | [SEJ — Google drops FAQ rich results](https://www.searchenginejournal.com/google-drops-faq-rich-results/574429/) (nguồn thứ cấp) | Google đã bỏ FAQ rich result cho mọi site (5/2026). | 9 FAQ: không thêm schema FAQPage vì không còn lợi ích SEO |

Giới hạn của research: phần chi tiết của Baymard nằm sau paywall, nên chỉ dùng các kết luận công khai. Trang Delugs/Watch Gecko chỉ đọc qua kết quả tìm kiếm, không đo đạc giao diện. Không sao chép thiết kế hay mã nguồn của các site này.

### 2.2 Hướng thiết kế rút ra

1. **Thủ công + minh bạch:** nói rõ quy trình, chất liệu, khác biệt tự nhiên của da (R7, R8, R10).
2. **Thông tin mua hàng đặt gần quyết định:** vận chuyển, đổi trả, bảo hành nằm cạnh sản phẩm (R6).
3. **Dữ liệu thật, không phóng đại:** đánh giá lấy từ WooCommerce, có phân bố sao (R3); so sánh chỉ dùng thuộc tính có thật (R2).
4. **Thông số gọn, có nhóm, dễ hiểu** (R1).
5. **Điều hướng bằng hình theo danh mục / dòng đồng hồ** (R5, R9).
6. **Gợi ý liên quan do người bán chọn**, không ép (R4).

---

## 3. 10 block

Thứ tự ưu tiên của brief: dùng element có sẵn trước, chỉ viết element mới khi cần. Kết quả: **5 block chỉ dùng element Flatsome**, **5 block cần element của plugin**.

| # | Block | Mục tiêu UX | Vị trí đặt | Cách làm |
|---|---|---|---|---|
| 1 | Product Showcase | Cho xem nhanh sản phẩm mới / bán chạy / nổi bật | Trang chủ, landing danh mục | `fbm_products` (mới) |
| 2 | Category Collection | Điều hướng bằng hình theo loại da / dòng đồng hồ | Trang chủ, đầu shop | `ux_product_categories` (Flatsome) |
| 3 | Editorial Hero Banner | Nói câu chuyện thương hiệu + 1 CTA chính | Đầu trang chủ, landing | `ux_banner` + `text_box` + `button` (Flatsome) |
| 4 | Product Benefits Grid | Giải thích vì sao đáng mua: thủ công, chất liệu, độ bền | Trang chủ, mô tả sản phẩm | `row` + `featured_box` (Flatsome), biến thể có `fbm_before_after` (patina) |
| 5 | Product Attributes Panel | Đọc nhanh thông số: chất liệu, bản rộng, khoá, màu chỉ | Trang sản phẩm (custom product layout) | `fbm_product_attributes` (mới) |
| 6 | Product Comparison | Chọn giữa 2–4 lựa chọn (vd. loại da) | Landing so sánh, trang danh mục | `fbm_product_compare` (mới) |
| 7 | Trust & Service Bar | Giảm lo lắng: vận chuyển, đổi trả, bảo hành, thanh toán | Dưới hero, cạnh nút mua, giỏ hàng | `ux_stack` + `featured_box` (Flatsome) |
| 8 | Customer Testimonials | Bằng chứng từ khách thật, có phân bố sao | Trang chủ, trang sản phẩm | `fbm_reviews` (mới) |
| 9 | FAQ Accordion | Trả lời câu hỏi chặn mua: size, chăm sóc, giao hàng | Trang sản phẩm, trang hỗ trợ | `accordion` + `accordion-item` (Flatsome) |
| 10 | Cross-sell & Related | Gợi ý phụ kiện tương thích (khoá, dụng cụ, chăm sóc da) | Trang sản phẩm, giỏ hàng | `fbm_products` với nguồn cross-sell / upsell / liên quan |

**Thay đổi so với bản design trước:** gộp Product Slider và Product Grid thành **một** element `fbm_products`, vì Flatsome cũng làm vậy (`ux_products` có option kiểu slider / row / masonry trong `repeater-options`). Before / After và Before / After Carousel vẫn giữ là element, dùng trong biến thể của block 4.

Block 1 và 10 khác nhau ở **ngữ cảnh và nguồn dữ liệu**: block 1 lấy theo truy vấn toàn shop, block 10 lấy theo quan hệ với sản phẩm đang xem. Bố cục mẫu cũng khác (block 10 là lưới nhỏ 4 cột kèm tiêu đề phụ "Dùng kèm với dây này").

---

## 4. Design system

### 4.1 Token (không có giá trị mới)

| Vai trò | Token | Nguồn |
|---|---|---|
| Màu chính | `var(--primary-color)` | Flatsome Theme Options |
| Màu cảnh báo / giảm giá | `var(--fs-color-alert)` | Flatsome |
| Nền phụ | `var(--secondary-color, #f8f8f8)` | HANDDN |
| Chữ phụ | `var(--text-color, #6b818c)` | HANDDN |
| Không hoạt động | `var(--color-notactive, #abb8c3)` | HANDDN |
| Viền / đường kẻ | `var(--color-grey, #ececec)` | HANDDN |
| Thành công / còn hàng | `var(--success-color, #60b2b7)` | HANDDN |
| Bo góc | `var(--global-radius, 4px)` | HANDDN |
| Font | kế thừa (Flatsome › Typography; HANDDN dùng `title-handdn` cho tiêu đề, Montserrat cho chữ) | Không khai báo font trong plugin |
| Cỡ chữ | class Flatsome `is-xsmall`, `is-small`, `is-large`, `is-xlarge`, `uppercase` (hạn chế) | Flatsome |
| Khoảng cách | 4 · 8 · 12 · 16 · 24 · 32 px, trùng với khoảng HANDDN đang dùng (12/16/20) và gutter Flatsome (15/30) | |
| Breakpoint | 549px (mobile), 849px (tablet), giống Flatsome | Flatsome |

Giá trị sau dấu phẩy chỉ là dự phòng khi site không có CSS HANDDN.

### 4.2 Quy tắc CSS (cập nhật)

Bản trước chỉ cho phép CSS riêng cho Before / After. Ba element mới (Attributes, Comparison, Reviews) cần bố cục mà Flatsome không có (bảng so sánh, thanh phân bố sao), nên quy tắc được cập nhật như sau:

1. **Được phép:** CSS bố cục (grid, flex, position, overflow, khoảng cách theo thang 4.1) trong namespace riêng của từng element: `.fbm-compare__*`, `.fbm-reviews__*`, `.fbm-ba__*`.
2. **Bắt buộc:** mọi màu, viền, bo góc dùng token 4.1. Không hex/rgba mới, không `font-family`, không `!important`, không selector toàn cục.
3. **Attributes Panel không có CSS riêng:** element xuất đúng markup `handdn-product-attributes__*` để CSS HANDDN đang có style. Trên site khác sẽ dùng `.fbm-attributes` với CSS tối thiểu.
4. Mỗi file CSS chỉ nạp khi element có mặt trên trang.

### 4.3 Thành phần dùng chung

| Thành phần | Dùng | Ghi chú |
|---|---|---|
| Tiêu đề section | Element `title` của Flatsome (`section-title-container`, `section-title-main`) | HANDDN đã style `section-title-main` |
| Nút | `button primary`, `is-outline`, `is-link`, `is-small` | Không tạo kiểu nút mới |
| Thẻ sản phẩm | `content-product.php` (`product-small box`) | Mọi hook WooCommerce/plugin vẫn chạy |
| Thẻ phụ (review, cột so sánh) | Nền `#fff`, viền 1px `--color-grey`, bo `--global-radius`, padding 16/24 | Giống `.icon-box_bg` của HANDDN |
| Hover | Viền chuyển sang `--primary-color`, `transition: .25s` | Giống `.featured-box_button:hover` của HANDDN |
| Focus | `:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px }` | Áp dụng cho mọi phần tử tương tác của element mới |
| Trống | Ẩn cả block với khách; admin thấy dòng nhắc nhỏ | Không bao giờ hiện khung rỗng ngoài site |
| Chuyển động | Không có animation tự chạy, trừ slider khi bật autoplay; tôn trọng `prefers-reduced-motion` | |

---

## 5. Kiến trúc v2 (tóm tắt)

```
flatsome-block-manager/
├─ flatsome-block-manager.php · uninstall.php
├─ src/
│  ├─ Plugin.php · Catalog.php · ElementStates.php · BlockVisibility.php · UsageIndex.php · Library.php
│  ├─ Products/Query.php   (nguồn + bộ lọc dùng chung)
│  ├─ Products/Loop.php    (content-product.php)
│  └─ Admin/               (1 trang, 4 tab, REST /fbm/v2/*)
├─ elements/
│  ├─ products/            element.php · render.php · thumbnail.svg
│  ├─ product-attributes/  element.php · render.php
│  ├─ product-compare/     element.php · render.php · compare.css
│  ├─ reviews/             element.php · render.php · reviews.css
│  ├─ before-after/        element.php · render.php · before-after.css · view.js
│  └─ ba-carousel/         element.php · render.php
├─ library/
│  └─ <10 thư mục>/        meta.php · content.txt · thumb.svg
├─ assets/admin/           React (wp-scripts), 4 tab
└─ docs/
```

REST chỉ nhận: tag trong danh mục đã biết + `on|hidden|off`; ID UX Block + `enabled/show_from/show_until`; slug mẫu thư viện để tạo UX Block nháp. Mọi route có `permission_callback` (`manage_options` cho element, `edit_pages` cho UX Block/thư viện) và nonce REST của WordPress.

---

## 6. Đặc tả từng block

Ký hiệu: **[F]** option lấy từ Flatsome (`commons/*.php`), **[P]** option plugin thêm.

### Block 1 — Product Showcase

- **Element:** `[fbm_products]`
- **Option:** [F] `repeater-posts` (ids, danh mục, tag, số lượng, offset, orderby, order) · [P] nguồn: mới / nổi bật / giảm giá / bán chạy / đánh giá cao / chọn tay / cross-sell / upsell / liên quan · [P] khoảng giá, chỉ hàng còn, loại trừ ID · [F] `repeater-options` (kiểu slider / row / masonry, số cột desktop/tablet/mobile, khoảng cách) · [F] `repeater-slider` (nút, chấm, tự chạy, lặp) · [F] style thẻ (`box-styles`) · [F] `advanced`
- **Dữ liệu:** `WP_Query` + taxonomy `product_visibility` (tôn trọng "Ẩn khỏi danh mục" và "ẩn hàng hết"), `wc_get_product_ids_on_sale()`, `total_sales`, `_wc_average_rating`. Biến thể hiển thị bằng sản phẩm cha, như shop.
- **Render:** `get_flatsome_repeater_start()` → `wc_get_template_part( 'content', 'product' )` → `get_flatsome_repeater_end()`.
- **Trống:** khách không thấy gì; admin thấy "Không có sản phẩm phù hợp với bộ lọc".
- **Mẫu thư viện:** tiêu đề "Bán chạy" + link "Xem tất cả", slider 4/3/2 cột.

### Block 2 — Category Collection

- **Element:** `[ux_product_categories]` của Flatsome + `[title]`.
- **Mẫu thư viện:** 2 biến thể: (a) lưới ảnh vuông 6/3/2 cột theo loại da; (b) slider theo dòng đồng hồ. Gắn class `grid-banner` của HANDDN cho biến thể (a).
- **Trống / thiếu ảnh:** Flatsome dùng ảnh placeholder của WooCommerce.

### Block 3 — Editorial Hero Banner

- **Element:** `[ux_banner]` + `[text_box]` + `[button]` của Flatsome.
- **Mẫu thư viện:** 2 biến thể: (a) ảnh tràn, text box trái dưới, 1 CTA; (b) chia đôi ảnh / chữ. Tiêu đề dùng `h1`/`h2` với class `h1-title`/`h2-title` của HANDDN. Không dùng chữ in hoa toàn bộ.
- **Nội dung mẫu:** ảnh để trống (người biên tập chọn), chữ là gợi ý có thể sửa.

### Block 4 — Product Benefits Grid

- **Element:** `[row]` + `[featured_box]` (icon, tiêu đề, mô tả) của Flatsome, class `icon-box_bg` của HANDDN.
- **Mẫu thư viện:** (a) 3 lợi ích: "Khâu tay", "Da thuộc thảo mộc", "Bản 16–24 mm"; (b) biến thể "Patina theo thời gian": 1 cột chữ + `[fbm_before_after]` (da mới / da sau 6 tháng).

### Block 5 — Product Attributes Panel

> **Đã làm trước (2026-10-17, trả lời Q1):** site thật chưa có code sinh markup này. Người dùng gửi snippet; đã chuyển vào plugin thành module `handdn` (`src/Modules/Handdn/ProductAttributes.php`).

- **Element:** `[handdn_product_attributes]` (giữ tên tag của người dùng, thay cho `fbm_product_attributes`)
- **Option:** [P] sản phẩm: để trống = sản phẩm đang xem / chọn tay (postSelect) · [F] `advanced` (class, visibility). Các option khác trong bản nháp (chọn thuộc tính, kích thước/cân nặng, tiêu đề) để sau, khi có nhu cầu.
- **Dữ liệu:** `$product->get_attributes()`; chỉ thuộc tính có `get_visible()`; global attribute qua `wc_get_product_terms()` (giữ thứ tự term), custom attribute qua `get_options()`; nhãn qua `wc_attribute_label()`. Không ghi đè global `$product`.
- **Markup:** giữ đúng markup `div` của snippet người dùng (CSS HANDDN đang viết cho markup này): `div.handdn-product-attributes` › `div.__item` › `div.__label` + `div.__values` › `span.__value`.
- **Trống:** không xuất gì; riêng trong UX Builder hiện 1 dòng ghi chú.

#### Kèm theo: nút "Customize this strap in 3D" (module `handdn`, `src/Modules/Handdn/Product3DButton.php`)

- Link lấy từ meta sản phẩm (`3D-link`, `3d_url`… lọc được qua filter `fbm/handdn/3d_meta_keys`), chỉ nhận http/https.
- Nút trong form: hook `woocommerce_before_add_to_cart_form`; `assets/handdn/3d-button.js` chuyển xuống sau `.apf-fields-wrap` (chỉ nạp khi sản phẩm có link).
- Nút trên ảnh: hook `flatsome_product_image_tools_top` (mọi layout gallery của Flatsome đều gọi), không cần JS.
- Snippet cũ còn chạy (`handdn_3d_get_product_url` tồn tại) thì module không in thêm, tránh nút đôi.

### Block 6 — Product Comparison

- **Element:** `[fbm_product_compare]`
- **Option:** [P] sản phẩm: chọn 2–4 (postSelect) / sản phẩm đang xem + upsell · [P] dòng: ảnh, giá, đánh giá, tình trạng kho, thuộc tính (chọn), nút mua · [P] "chỉ hiện dòng khác nhau" · [P] tiêu đề · [F] `advanced`
- **Dữ liệu:** `wc_get_product()`; thuộc tính lấy hợp các thuộc tính hiển thị, ô thiếu ghi "—"; nút mua dùng markup add-to-cart của WooCommerce (AJAX như shop).
- **Markup:** `<table>` có `<caption>`, `<th scope="col">` cho sản phẩm, `<th scope="row">` cho tên dòng. Mobile: cuộn ngang trong khung riêng, cột tên dòng dính trái.
- **Trống:** dưới 2 sản phẩm hợp lệ thì không xuất gì.

### Block 7 — Trust & Service Bar

- **Element:** `[ux_stack]` / `[row]` + `[featured_box]` cỡ nhỏ của Flatsome, class `featured-box_button` + `featured-box_center` của HANDDN.
- **Mẫu thư viện:** 4 ô: vận chuyển miễn phí từ X, đổi trả N ngày, bảo hành, thanh toán an toàn. Biến thể dọc đặt cạnh nút mua trong custom product layout.
- **Lưu ý:** con số (ngưỡng miễn phí, số ngày đổi trả) là chữ người biên tập nhập. Plugin không tự đọc từ cài đặt vận chuyển.

### Block 8 — Customer Testimonials

> **Đổi hướng (2026-10-17, trả lời Q2: site dùng Judge.me).** Đánh giá nằm trên máy chủ Judge.me, WordPress không có dữ liệu để tự dựng thẻ. Block 8 chuyển thành khung Flatsome bọc widget của Judge.me.

- **Element:** `[fbm_reviews]` = element bọc, chỉ in ra 1 shortcode Judge.me nằm trong danh sách cho phép.
- **Option:** [P] widget: chọn trong whitelist (ứng viên: `jgm-featured-carousel`, `jgm-preview-badge`, `jgm-popup-widget`, `jgm-ugc-media-grid`; danh sách cuối đọc từ code plugin Judge.me ở bước B7) · [P] tiêu đề (dùng `[title]` style của Flatsome) · [F] `advanced`. Không có ô nhập shortcode tự do.
- **Lưu ý:** carousel của Judge.me chỉ hiện đánh giá được đánh dấu "featured" (tự động: tối đa 50 đánh giá 5 sao mới nhất).
- **Trống:** plugin Judge.me không bật thì không xuất gì; trong UX Builder hiện ghi chú "Cần plugin Judge.me".
- **Không làm:** lọc sao, "đã mua hàng", phân bố sao. Những phần này do Judge.me quản lý.

### Block 9 — FAQ Accordion

- **Element:** `[accordion]` + `[accordion-item]` + `[title]` của Flatsome.
- **Mẫu thư viện:** 6 câu về chọn bản rộng, đo cổ tay, chăm sóc da, thời gian làm và giao hàng, đổi trả, thay khoá. Nội dung là gợi ý, cần người bán duyệt lại.
- **Không thêm** schema FAQPage (R12).

### Block 10 — Cross-sell & Related

- **Element:** `[fbm_products]` với nguồn `cross_sell` → `upsell` → `related` (lấy nguồn đầu tiên có dữ liệu nếu chọn "tự động").
- **Dữ liệu:** `$product->get_cross_sell_ids()`, `get_upsell_ids()`, `wc_get_related_products()`; ngoài trang sản phẩm thì lấy theo giỏ hàng (`WC()->cart`).
- **Mẫu thư viện:** tiêu đề phụ "Dùng kèm với dây này", lưới 4/2/2, không slider.
- **Trống:** không có sản phẩm liên quan thì không xuất gì.

---

## 7. Kiểm thử (Phase 4)

### 7.1 Checklist

| Nhóm | Kiểm tra |
|---|---|
| PHP | `php -l` mọi file; WP_DEBUG không có notice/warning khi render từng element ở mọi option |
| UX Builder | Element có trong Add Elements; option panel hiển thị đúng; canvas render; preset/Import chèn được |
| Trạng thái | Bật / Ẩn / Tắt đúng bảng hành vi; khoá element khung; UX Block bật/tắt + hẹn giờ |
| Dữ liệu WC | simple, variable (giá khoảng), on sale (nhãn %), hết hàng, ẩn khỏi danh mục, thiếu ảnh, global + custom attribute, đánh giá có/không verified |
| Trống | Mỗi element với 0 kết quả: khách không thấy gì, admin thấy dòng nhắc |
| Responsive | 375, 768, 1280 px; không tràn ngang |
| A11y | Tab qua mọi phần tử; focus nhìn thấy; bảng so sánh có header; tương phản theo token |
| CSS | Không đổi giao diện trang không có element (so ảnh chụp shop, trang sản phẩm trước/sau khi bật plugin) |
| Hiệu năng | Không có query trong vòng lặp ngoài `wc_get_product`; CSS/JS chỉ nạp khi có element |
| An toàn | Không có route/option nhận code; REST kiểm tra quyền + nonce; escape mọi đầu ra |

### 7.2 Dữ liệu test cần tạo trên DDEV

Site local hiện không có thuộc tính, biến thể hay đánh giá. Phase 4 cần thêm (sau khi chụp `ddev snapshot` để khôi phục được):

- 3 global attribute (Bản rộng, Loại da, Màu chỉ) + 1 custom attribute.
- 2 sản phẩm biến thể (giá khác nhau theo bản rộng), 1 sản phẩm hết hàng, 1 sản phẩm ẩn khỏi danh mục, 1 sản phẩm không ảnh.
- 10 đánh giá (có/không verified, 1–5 sao).
- Liên kết cross-sell/upsell cho 3 sản phẩm.

---

## 8. Kế hoạch & ước tính

| Bước | Việc | Ước tính |
|---|---|---|
| B1 | Lõi, danh mục element, Bật/Ẩn/Tắt (kiểm chứng cơ chế Ẩn trước) | 0,5 ngày |
| B2 | UX Block bật/tắt + hẹn giờ + nơi dùng (chuyển từ v1) | 0,25 ngày |
| B3 | `Products/Query` + `Products/Loop` | 0,5 ngày |
| B4 | `fbm_products` | 0,5 ngày |
| B5 | ~~`fbm_product_attributes`~~ đã xong trước: `handdn_product_attributes` + nút 3D | 0 |
| B6 | `fbm_product_compare` | 0,75 ngày |
| B7 | `fbm_reviews` (khung bọc widget Judge.me) | 0,5 ngày |
| B8 | `fbm_before_after` + `fbm_ba_carousel` (chuyển từ v1, theo quy tắc CSS mới) | 0,5 ngày |
| B9 | Thư viện 10 block (content.txt, Import, nút tạo UX Block nháp) | 1 ngày |
| B10 | Admin 4 tab | 0,75 ngày |
| B11 | Dọn v1 khi nâng cấp | 0,25 ngày |
| B12 | Dữ liệu test + QA theo mục 7 + zip | 1 ngày |
| | **Tổng** | **6,5 ngày** |

Mỗi bước kết thúc bằng phần chạy được và kiểm tra được trên DDEV. Sau B4, B7 và B9 sẽ dừng lại cho người dùng xem trước khi làm tiếp.

---

## 9. Rủi ro, giả định, câu hỏi mở

| # | Nội dung | Cần gì |
|---|---|---|
| Q1 ✅ | Đã trả lời: chưa có code, plugin tự sinh (module `handdn`). Trên site thật, code nào đang sinh `handdn-product-attributes`? Nếu nó tự chèn vào trang sản phẩm, đặt thêm element sẽ hiện 2 lần. | Người dùng cho biết nguồn (snippet / plugin / child theme). |
| Q2 ✅ | Đã trả lời: Judge.me, block 8 đổi hướng. Site thật dùng CusRev hay Judge.me? CusRev lưu đánh giá trong WooCommerce nên element đọc được; Judge.me lưu ngoài nên không. | Người dùng xác nhận. Nếu là Judge.me, block 8 cần đổi hướng (dùng widget của Judge.me). |
| A1 ✅ | Đã kiểm chứng 2026-10-17 trên DDEV (Task 2): cờ `hidden` qua filter `ux_builder_shortcode_data` làm element biến khỏi Add elements (tìm "map" không ra), trang đã dùng vẫn render trên canvas. Cơ chế Ẩn (`ux_builder_shortcode_data_{tag}` → `hidden`). | Kiểm chứng ở B1; nếu không được, dùng `ux_builder_remove_element` chỉ trong màn hình Add Elements. |
| A2 | Màu chính `#161735` suy ra từ CSS; element không phụ thuộc giá trị này. | Không. |
| A3 | Font `title-handdn` không có trong site local; giao diện local có thể khác site thật. | Kiểm tra lại trên staging. |
| A4 | Nội dung chữ trong mẫu thư viện (lợi ích, FAQ, chính sách) là gợi ý. | Người bán sửa trước khi đăng. |
| R1 | Gộp Product Slider/Grid thành `fbm_products` khác với bản design đã duyệt. | Người dùng duyệt thay đổi này. |
| R2 | Quy tắc CSS nới ra cho 3 element mới (mục 4.2). | Người dùng duyệt thay đổi này. |
