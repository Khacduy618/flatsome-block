# Shop The Look: element UX Builder

Ngày: 2026-10-19 · Trạng thái: **chờ duyệt** · Thuộc plugin Flatsome Block Manager v2 (nhánh `v2`).

## 1. Mục tiêu

Một section 2 cột cho lookbook:

- Một bên là ảnh tổng thể có các pin tròn.
- Bên kia là **một thẻ sản phẩm** đang được chọn.

Khi khách bấm pin, thẻ đổi sang sản phẩm của pin đó. Khi khách rê chuột lên thẻ, pin của sản phẩm phát sáng.

Người dựng trang làm toàn bộ trong bảng cài đặt của element: chọn ảnh, chọn sản phẩm cho từng pin, rồi đặt pin bằng cách **kéo trên ảnh trong canvas** hoặc bằng thanh X/Y.

### Quyết định đã chốt với người dùng

| # | Chủ đề | Quyết định |
|---|---|---|
| D1 | Nền tảng | Element UX Builder trong plugin này (như 6 element v2). Không dùng ACF / Gutenberg (site không có ACF; spec v2 đã bỏ Gutenberg). |
| D2 | Danh sách pin | Nằm **trong bảng cài đặt của Shop The Look**, không phải element con. UX Builder không có option repeater, nên dùng **8 ô pin cố định** (`Pin 1`…`Pin 8`). Ô kế tiếp chỉ hiện khi ô trước đã chọn sản phẩm. |
| D3 | Đặt pin | Kéo pin trên ảnh trong canvas. Bấm chỗ trống trên ảnh thì pin đang chọn nhảy tới đó. Thanh X/Y (bước 1 %) để tinh chỉnh. Kéo-thả dùng đối tượng nội bộ của UX Builder (đã thử khả thi 2026-10-19: ghi `shortcode.options`, canvas vẽ lại, Save lưu đúng); nếu không truy cập được thì tắt kéo-thả, thanh X/Y vẫn chạy. |
| D4 | Thẻ sản phẩm | **Một thẻ duy nhất**: ảnh (bật/tắt), tên, giá, nút "Xem sản phẩm". |
| D5 | Pin | Hình tròn. Tùy chọn vòng lan toả (pulse). Pin đang chọn phóng to nhẹ (khoảng 1.15×), chuyển động mượt. Kiểu: chấm hoặc số thứ tự. |
| D6 | Màu | **Mọi màu lấy từ màu theme**. Không có ô chọn màu. |
| D7 | Mobile (≤ 549px) | Ảnh trên, thẻ dưới. Bấm pin thì thẻ đổi; nếu thẻ khuất thì cuộn nhẹ tới thẻ. |
| D8 | Số pin | Tối đa 8. |

### Không làm (ngoài phạm vi)

- Ô chọn màu, kiểu pin "giỏ hàng".
- Kéo-thả để sắp thứ tự pin. Muốn đổi thứ tự thì đổi sản phẩm giữa các ô.
- Chọn biến thể / thêm vào giỏ ngay trên thẻ.
- Tải thẻ bằng AJAX.
- Thẻ nổi trên ảnh (tooltip) ở mobile.
- Block mẫu trong UX Builder › Import (có thể thêm sau).

## 2. Element

- Tag `fbm_shop_look`. Thư mục `elements/shop-look/` theo cấu trúc v2 (`meta.php`, `builder.php`, `render.php`, `shop-look.css`, `view.js`, `editor.js`).
- Nhóm **Shop**, tên "Shop The Look".
- `wrap => false`, và output có **đúng 1 phần tử gốc**. Thẻ `<style>` nếu có phải nằm trong phần tử gốc. Đây là bài học từ lỗi Before/After 2026-10-19: hai phần tử gốc làm UX Builder mất liên kết sau lần vẽ lại đầu tiên.
- `_id` mặc định `'shop-look-' . rand()`.

### 2.1 Bảng cài đặt

| Nhóm | Key | Kiểu | Mặc định | Ghi chú |
|---|---|---|---|---|
| Chung | `title` | textfield | `''` | In bằng markup `section-title` của Flatsome. |
| | `description` | textarea | `''` | Đoạn ngắn dưới tiêu đề. |
| Ảnh | `image` | image | `''` | Ảnh lookbook. |
| | `image_size` | select | `large` | medium · medium_large · large · full |
| | `ratio` | select | `''` | Theo ảnh · 1:1 · 4:5 · 3:4 · 2:3 · 3:2 · 16:9. Ảnh `object-fit: cover`. |
| Bố cục | `image_side` | radio | `left` | Trái / Phải |
| | `split` | radio | `50` | 50/50 · 60/40 · 66/33 (phần trăm cho cột ảnh) |
| Pin | `pin_style` | radio | `dot` | Chấm / Số thứ tự |
| | `pulse` | checkbox | `true` | Vòng lan toả |
| | `pin_tone` | radio | `light` | Sáng (nền tối) / Tối (nền sáng) |
| Thẻ | `card_image` | checkbox | `true` | Hiện ảnh sản phẩm |
| | `button_text` | textfield | `Xem sản phẩm` | |
| Pin 1…8 | `pinN_product` | select (postSelect product) | `''` | Nhóm N > 1 chỉ hiện khi `pin(N-1)_product` có giá trị. |
| | `pinN_x` | slider 0–100, bước 1, `%` | `50` | Kéo thanh thì pin trên canvas di chuyển ngay. |
| | `pinN_y` | slider 0–100, bước 1, `%` | `50` | |
| Advanced | `class`, `visibility` | `@commons/advanced` | | |

## 3. Hiển thị ngoài site

```
section.fbm-stl[.fbm-stl--right][.fbm-stl--split-60|66][.fbm-stl--dots|numbers][.fbm-stl--pulse][.fbm-stl--tone-dark]
├─ (tiêu đề + mô tả, nếu có)
└─ .fbm-stl__grid
   ├─ .fbm-stl__media              ảnh + pin
   │   ├─ img.fbm-stl__img
   │   └─ button.fbm-stl__pin × N  style="--fbm-x:34%;--fbm-y:58%"  aria-controls aria-pressed aria-label="Xem <tên SP>"
   └─ .fbm-stl__cards (aria-live="polite")
       └─ article.fbm-stl__card × N  (chỉ thẻ đang chọn hiện, còn lại `hidden`)
           ảnh (tùy chọn) · tên (link) · giá (get_price_html) · a.button.primary "Xem sản phẩm"
```

- **Pin hợp lệ:** có sản phẩm `publish` và không bị ẩn khỏi danh mục. Ô trống hoặc không hợp lệ bị bỏ. Số thứ tự trên pin đếm lại theo các pin hợp lệ.
- **Trống** (không có ảnh hoặc 0 pin hợp lệ): khách không thấy gì; người biên tập thấy `Placeholder::empty()`.
- **Tắt** (Block Manager): như mọi element v2.
- Tọa độ pin là biến CSS inline `--fbm-x`, `--fbm-y`, ngoại lệ cùng loại với `--fbm-pos` của Before/After (spec v2 §4.2). Giá trị kẹp 0–100.
- Escape mọi đầu ra. Tên và giá lấy qua hàm WooCommerce.

## 4. CSS (`shop-look.css`)

- Chỉ bố cục, định vị và hiệu ứng trong namespace `.fbm-stl__*`. Màu, viền, bo góc chỉ dùng `var(--primary-color)`, `var(--secondary-color, #f8f8f8)`, `var(--text-color, #6b818c)`, `var(--color-grey, #ececec)`, `var(--global-radius, 4px)`. Không hex/rgba mới, không `!important`, không `font-family`.
- Lưới: desktop 2 cột theo `split`, `image_side=right` thì đảo thứ tự. ≤ 549px: 1 cột, thẻ dưới ảnh.
- **Pin:** tròn, căn tâm tại `(--fbm-x, --fbm-y)`, vùng bấm tối thiểu 44px.
  - Tông sáng: nền `--secondary-color`, chấm/số `--primary-color`.
  - Tông tối: đảo ngược hai màu trên.
- **Vòng lan toả:** pseudo-element scale 1 → 2.2, opacity 0.6 → 0, lặp vô hạn, chỉ khi `.fbm-stl--pulse`.
- **Pin đang chọn:** `transform: scale(1.15)` với `transition: transform .25s cubic-bezier(.2,.8,.2,1)`. Khi rê chuột lên thẻ, pin tương ứng có vòng sáng (`.is-highlight`).
- `prefers-reduced-motion: reduce`: tắt pulse và transition.
- `:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px }`.
- File chỉ nạp khi element có trên trang, và nạp sẵn vào canvas (`ux_builder_enqueue_scripts`, đã có trong Registry).

## 5. JS ngoài site (`view.js`)

- Khởi tạo mỗi `.fbm-stl` một lần. Theo dõi phần tử chèn sau bằng MutationObserver, như `before-after/view.js`.
- **Bấm pin** (chuột, chạm, Enter/Space): hiện thẻ tương ứng và ẩn thẻ khác; cập nhật `aria-pressed` và `.is-active`. Ở ≤ 549px, nếu thẻ khuất thì `scrollIntoView({ block: 'nearest' })` (mượt, trừ khi giảm chuyển động).
- **Rê chuột / focus vào thẻ:** thêm `.is-highlight` cho pin tương ứng; bỏ khi rời.
- Lúc đầu: pin 1 đang chọn.
- Trong canvas UX Builder, view.js vẫn chạy để người dựng trang thấy hành vi thật.

## 6. Kéo pin trong canvas (`editor.js`)

Chỉ nạp trong canvas (`ux_builder_enqueue_scripts`, context `content`).

1. Tìm đối tượng shortcode của element: injector Angular của canvas → `$rootScope` → duyệt scope tìm object có `tag === 'fbm_shop_look'` và `$element` chứa phần tử gốc đang xét.
2. Kéo pin bằng `pointerdown/move/up` trên `.fbm-stl__media`: trong lúc kéo chỉ đổi `--fbm-x/--fbm-y` trên DOM; khi thả thì ghi `options.pinN_x/pinN_y` (làm tròn số nguyên) rồi gọi `$rootScope.$apply()` một lần.
3. Bấm chỗ trống trên ảnh: pin đang chọn (pin bấm gần nhất, mặc định pin 1) nhảy tới đó và ghi như trên.
4. Dừng lan sự kiện để UX Builder không coi cú kéo là kéo element sang chỗ khác.
5. **Bảo vệ:** không có `angular`, injector, `$rootScope` hoặc không tìm thấy shortcode thì không gắn kéo-thả và không báo lỗi. Thanh X/Y vẫn chạy.
6. Sau khi canvas vẽ lại (element mới), gắn lại.

## 7. Kiểm thử

**Test PHP** (`tests/ShopLookTest.php`, viết trước code):

- Render đúng số pin/thẻ theo số ô có sản phẩm hợp lệ. Ô trống ở giữa bị bỏ, số thứ tự đếm lại.
- Sản phẩm nháp, ẩn khỏi danh mục, ID không phải sản phẩm: bỏ.
- Không ảnh hoặc 0 pin: khách `''`, editor thấy `fbm-empty`.
- Tọa độ kẹp 0–100. `--fbm-x` / `--fbm-y` đúng giá trị.
- Đúng 1 phần tử gốc.
- Escape tên sản phẩm có `<script>`.
- `aria-controls` của pin trỏ đúng id thẻ. Thẻ 2..N có `hidden`.
- CSS qua luật màu (`/#[0-9a-f]{3,6}\b|rgba?\(|!important|font-family/` trừ giá trị dự phòng trong `var()`).
- Builder: đủ 8 nhóm pin, nhóm N có `conditions` theo nhóm N-1, slider bước 1.
- CSS/JS nạp vào canvas qua `ux_builder_enqueue_scripts`.

**Playwright** (UX Builder thật trên DDEV):

- Chọn sản phẩm cho 3 ô. Đổi X/Y bằng thanh nhiều lần liên tiếp, xen kẽ đổi tỉ lệ/bố cục: canvas luôn theo (tránh lặp lỗi Before/After).
- Kéo pin 2 tới vị trí mới rồi Save: nội dung lưu có `pin2_x`/`pin2_y` mới. Bấm chỗ trống: pin đang chọn nhảy tới.
- Ngoài site ở 375 / 768 / 1280px: không tràn ngang; bấm pin đổi thẻ; rê chuột lên thẻ thì pin sáng; Tab/Enter qua các pin; focus nhìn thấy.
- `prefers-reduced-motion`: không có animation.

## 8. Rủi ro

| # | Rủi ro | Xử lý |
|---|---|---|
| R1 | Kéo-thả dựa vào cấu trúc nội bộ của UX Builder (Angular scope, `shortcode.options`). Flatsome có thể đổi. | Lớp bảo vệ §6.5; thanh X/Y luôn chạy; test Playwright kéo-thả chạy lại mỗi khi cập nhật Flatsome. |
| R2 | `conditions` trên option kiểu `group` có thể không được UX Builder hỗ trợ. | Kiểm ngay task đầu của plan. Nếu không được: đặt `conditions` lên từng option trong nhóm. |
| R3 | 8 nhóm × 3 option làm bảng cài đặt dài. | Nhóm thu gọn mặc định; chỉ hiện nhóm kế tiếp khi nhóm trước có sản phẩm. |
| R4 | Undo/Redo của UX Builder với giá trị ghi từ canvas. | Kiểm trong Playwright; nếu Undo không lùi được cú kéo thì ghi vào spec như giới hạn đã biết. |

## 9. Cập nhật 2026-10-19 (thắng mọi dòng mâu thuẫn ở trên)

Người dùng yêu cầu danh sách sản phẩm **không giới hạn**. UX Builder không có option repeater (Flatsome 3.20.11 chỉ có textfield, select, slider, group, image, gallery…), nên người dùng chọn **pin là element con**.

- **D2/D8 thay bằng:** `fbm_shop_look` là container, chỉ nhận `fbm_look_pin`. Mỗi pin con có `product` (select product), `x`, `y` (slider 0–100, bước 1, mặc định 50). Không giới hạn số pin. Thêm bằng nút +, xoá, kéo sắp thứ tự trong cột trái UX Builder. Bỏ các ô `pin1…pin8`.
- **Ngoài site:** `fbm_shop_look` đọc các `[fbm_look_pin]` trong nội dung (`get_shortcode_regex`) và tự in pin vào ảnh, thẻ vào cột phải. Markup như §3, bỏ `data-slot`.
- **Trong canvas:** cha vẽ bằng template Angular (`template.html`: ảnh qua `wp-attachment`, class từ option, `<content>` trong `.fbm-stl__media`, cột thẻ trống). Mỗi `[fbm_look_pin]` render PHP thành `div.fbm-stl__item` (1 phần tử gốc) gồm nút pin và thẻ ẩn; `view.js` sao chép thẻ của pin đang chọn sang cột thẻ.
- **Số thứ tự trên pin:** CSS counter (đúng cả trong canvas, nơi pin con không biết vị trí của mình).
- **Kéo-thả (§6):** tìm đối tượng shortcode `tag === 'fbm_look_pin'` có `$element` là `div.fbm-stl__item` chứa pin, rồi ghi `options.x` / `options.y`.

## 10. Cập nhật lần 2, 2026-10-19 (thắng §9 và mọi dòng mâu thuẫn ở trên)

Người dùng không muốn pin là element con. Cấu trúc chốt: **slider → Look**.

- **`fbm_look`** (element con, `wrap => false`, 1 phần tử gốc `section.fbm-stl`): đúng bản 8 ô ở §2.1 (`pinN_product`, `pinN_x`, `pinN_y`; nhóm N chỉ hiện khi nhóm N-1 có sản phẩm) cùng toàn bộ cài đặt Chung / Ảnh / Bố cục / Kiểu pin / Thẻ. Render PHP đầy đủ, cả trong canvas. Pin có `data-slot`.
- **`fbm_shop_look`** (container, chỉ nhận `fbm_look`, số Look không giới hạn):
  - Cài đặt: nhóm Slider của Flatsome (`@commons/repeater-slider`) và Advanced. Preset gồm 1 Look.
  - 0 Look hợp lệ: khách nhận `''`, editor thấy dòng nhắc.
  - 1 Look: in Look đó, không có slider.
  - Từ 2 Look: slider Flickity của Flatsome (`get_flatsome_repeater_start`, 1 cột), mỗi Look một slide.
- **Kéo-thả (§6):** tìm shortcode `tag === 'fbm_look'` có `$element` là `section.fbm-stl`, rồi ghi `options.pinN_x/pinN_y` theo `data-slot`.
- Số thứ tự trên pin: CSS counter `fbm-stl-pin`.
- Bỏ §9 (pin con, template canvas, thẻ mẫu).

## 11. Cập nhật lần 3, 2026-10-19 (yêu cầu người dùng trong lúc làm; thắng các mục trên)

- **Look** (`fbm_look`) chỉ có: ảnh lookbook, Pin 1–8 (sản phẩm + X/Y), Advanced.
- **Shop The Look** (`fbm_shop_look`) giữ mọi cài đặt chung:
  - **General:** Title, Short description, Text align (bộ nút căn lề của Flatsome), Spacing.
  - **Look layout:** Image position, Columns `1-1` / `3-2` / `2-1` (khoá không phải số vì UX Builder so `model === value`), Frame width % và Frame height (theo từng màn hình; có chiều cao thì ảnh phủ kín khung).
  - **Image style:** Image size, Frame ratio.
  - **Pin style:** Dot/Number, Pulse, Light/Dark.
  - **Product card:** Button text (mặc định "View product").
  - **Slider:** các nhóm Layout / Navigation / Auto Slide lấy nguyên từ element Slider (`ux_slider`) của Flatsome.
- **Kiểu dáng là class trên Shop The Look** (`.fbm-shop-look--*`), nên canvas cập nhật ngay. Image size và Button text được truyền xuống từng Look khi render.
- **Bố cục mặc định theo mẫu:** ảnh trái phủ kín, khối nền phải chứa thẻ canh giữa (ảnh sản phẩm luôn hiện, tên, giá, nút) và dãy chấm ứng với các pin.
- **Pin:** chấm đặc + quầng mờ đều, mép mềm; pin đang chọn phóng to 1.15×; pulse "thở" nhẹ.
- **Slider:**
  - Ngoài site: từ 2 Look thì in bằng `[ux_slider]`, mỗi Look là một slide.
  - Canvas: dùng template, directive, tools và controller của `ux_slider`. Controller gắn qua `UxBuilder.controller('fbm_shop_look', …)` trong `canvas.js`, file chỉ nạp ở canvas.
- **Nhãn option** toàn bộ bằng tiếng Anh.
