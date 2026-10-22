# Claude Guidelines & Rules

## Output Format Policy
Sau khi hoàn thành bất kỳ nhiệm vụ lập trình, chỉnh sửa hoặc xử lý nào, bạn BẮT BUỘC phải kết thúc câu trả lời bằng một bảng Markdown duy nhất gồm 3 cột theo định dạng sau:

| Việc gì? | Làm thế nào? | File nào? |
| :--- | :--- | :--- |
| [Mô tả ngắn gọn] | [Công cụ/phương pháp] | [Tên file liên quan] |

## Rules
1. **Tuyệt đối ngắn gọn**: Đi thẳng vào trọng tâm, không giải thích dài dòng hay thêm lời dẫn rườm rà ngoài bảng.
2. **Súc tích**: Nội dung trong bảng tối đa 1-2 dòng mỗi hàng.
3. **Language Policy**: Always use English for all code, UI labels, option headings, control titles, comments, docblocks, presets, and demo text. Do not use Vietnamese in element options, UI controls, or source code.


## WordPress, WooCommerce & Flatsome Development Rules

### 1. Flatsome & UX Builder (v2) Best Practices
- **Ưu tiên UX Builder gốc**: Tận dụng tối đa các Shortcode/Elements có sẵn của Flatsome thay vì viết code custom phức tạp nếu không thực sự cần thiết, giúp duy trì khả năng bảo trì và tốc độ tải trang.
- **Custom Flatsome Elements**: Khi cần custom element riêng cho UX Builder, đặt đúng chuẩn cấu trúc cấu hình element vào thư mục `flatsome-child/inc/ux-builder/shortcodes/`.
- **CSS Scope & Performance**: Viết CSS tùy chỉnh tối ưu, tận dụng các biến (variables) có sẵn của Flatsome theme, tránh làm nặng DOM hoặc gây xung đột với Flexbox/Grid của UX Builder.

### 2. WooCommerce & Checkout Optimization
- **Child Theme First**: Mọi tùy chỉnh template của WooCommerce (như `archive-product.php`, `single-product.php`, `cart/`, `checkout/`) bắt buộc phải đặt trong thư mục **Child Theme** (`flatsome-child`), tuyệt đối không sửa trực tiếp core theme.
- **Hooks & Filters**: Ưu tiên sử dụng WordPress Action Hooks và Filter Hooks thay vì ghi đè file template nặng nề khi can thiệp vào logic giỏ hàng, checkout, hoặc tính giá sản phẩm.
- **Database & Query Optimization**: Tránh dùng `meta_query` phức tạp hoặc vòng lặp `WP_Query` nặng trên trang chủ/archive; tận dụng transients API hoặc Redis/Memcached object caching khi xử lý dữ liệu WooCommerce lớn.

### 3. WordPress Architecture & Code Quality
- **Security First**: 
  - Luôn sanitize dữ liệu đầu vào (`sanitize_text_field`, `absint`, v.v.).
  - Luôn escape dữ liệu đầu ra (`esc_html`, `esc_attr`, `esc_url`, `wp_kses`).
  - Kiểm traNonce (`wp_verify_nonce`) trong mọi AJAX request hoặc xử lý form.
- **Performance & SEO**: 
  - Tránh lạm dụng Plugin không cần thiết; ưu tiên giải pháp code thuần nhẹ nhàng khi có thể.
  - Tối ưu hóa asset loading (chỉ gọi CSS/JS trên trang thực sự cần thiết bằng `wp_enqueue_script` có điều kiện).

  ## Element-First Block Architecture (Mandatory)
- **Audit Before Building Block**: Khi được yêu cầu tạo bất kỳ Preset `ux_block` nào, BẮT BUỘC kiểm tra danh sách Custom Elements hiện có trong `inc/Elements/`.
- **Reuse or Create Custom Elements**: 
  - Sử dụng Core Flatsome (`[section]`, `[row]`, `[col]`, `[ux_stack]`) cho khung lưới (Grid/Layout).
  - Tái sử dụng Custom Elements đã có của plugin cho các thành phần UI.
  - Nếu Block chứa thành phần UI đặc thù (Card, Badge, Pricing Box, Testimonial Item, Accordion, Stat, Floating Box...) chưa có Element tương ứng, BẮT BUỘC phải code thêm Custom UX Builder Element mới (`add_ux_builder_shortcode` + Render file) với đầy đủ `options` chỉnh sửa trực quan, sau đó mới nhúng shortcode của Element đó vào Preset `ux_block`.
  - **CẤM** nhồi nhét HTML phức tạp nhiều tầng vào trong `[ux_text]`.

  ## 4. AJAX & Performance Optimization Rules
- **Lazy Load By Default**: Các tính năng nặng (Product Tabs AJAX, Infinite Scroll, Complex Sliders) bắt buộc phải chia tách rõ: Tab đầu pre-render HTML trực tiếp, các tab sau load dạng khung chờ và gọi AJAX kèm Nonce bảo mật.
- **Client Caching**: Dữ liệu trả về từ AJAX phải được cache tạm ở phía Client (JS memory/dataset) để tránh gọi lặp lại request khi người dùng chuyển đổi qua lại giữa các tab.
- **Security Check**: Mọi AJAX endpoint bắt buộc phải kiểm tra `wp_verify_nonce` và sanitize dữ liệu query đầu vào.