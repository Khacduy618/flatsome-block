# Shop The Look Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Element UX Builder `fbm_shop_look`. Ảnh lookbook có tối đa 8 pin tròn; bấm pin thì đổi một thẻ sản phẩm (ảnh, tên, giá, nút "Xem sản phẩm"). Người dựng trang đặt pin bằng cách kéo trên ảnh trong canvas, hoặc bằng thanh X/Y trong bảng cài đặt của element.

**Architecture:** Một element v2 nằm trong `elements/shop-look/`, gồm:

- `render.php`: in sẵn mọi pin và thẻ từ server.
- `view.js`: đổi thẻ ngoài site.
- `editor.js`: chỉ nạp trong canvas; kéo pin rồi ghi `options.pinN_x/pinN_y` vào đối tượng shortcode của UX Builder, có lớp bảo vệ.
- `Registry`: thêm khoá meta `editor`, để nạp file chỉ dành cho canvas.

**Tech Stack:** PHP 7.4+, WordPress 6.4+, WooCommerce, Flatsome 3.20.11 UX Builder, vanilla JS (không build), test PHP qua `sh bin/test.sh` trên DDEV `handdn`, kiểm UX Builder bằng Playwright.

**Spec:** `docs/superpowers/specs/2026-10-19-shop-the-look-design.md`. Spec gốc v2: `docs/superpowers/specs/2026-10-17-v2-ui-blocks-design.md` (§4.2 luật CSS).

## Global Constraints

- Không sửa Before/After Carousel (`elements/ba-carousel/`) và Before/After.
- Tag `fbm_shop_look`, tên "Shop The Look", nhóm Shop, text domain `flatsome-block-manager`, `_id` mặc định `'shop-look-' . wp_rand()`, có nhóm `@commons/advanced`.
- `wrap => false`; output **đúng 1 phần tử gốc** `<section>`; mọi `<style>` (nếu có) nằm trong phần tử gốc.
- Màu chỉ qua `var(--primary-color)`, `var(--secondary-color, #f8f8f8)`, `var(--text-color, #6b818c)`, `var(--color-grey, #ececec)`, `var(--global-radius, 4px)`. Không hex/rgba mới ngoài fallback trong `var()`, không `!important`, không `font-family`, không ô chọn màu.
- Biến inline duy nhất được phép: `--fbm-x`, `--fbm-y` trên pin (giá trị kẹp 0–100).
- Tối đa 8 pin (`pin1`…`pin8`). Pin hợp lệ: sản phẩm `publish` và `catalog_visibility` thuộc `visible` hoặc `catalog`.
- Breakpoint 549px / 849px. `prefers-reduced-motion: reduce` tắt pulse và transition. `:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px }`.
- Trống: khách nhận `''`, người biên tập thấy `FBM\Placeholder::empty()`. Escape mọi đầu ra.
- CSS/JS chỉ nạp khi element có trên trang; `editor.js` chỉ nạp trong canvas.
- Đồng bộ DDEV bằng `rsync` (đã có trong `bin/test.sh`), không bind-mount.

## Review Focus

1. **Một sản phẩm gắn cho 2 ô.** Hai pin, hai thẻ, id thẻ khác nhau; bấm pin nào thì đúng thẻ đó. Test: `test_stl_same_product_twice_distinct_cards` (Task 1).
2. **Hai Shop The Look trên cùng trang.** Id không trùng; bấm pin ở element này không đổi thẻ của element kia. Test: `test_stl_two_instances_unique_ids` (Task 1); Playwright bước 3 (Task 3).
3. **Pin sát mép (X hoặc Y = 0 / 100).** Pin vẫn nằm trọn trong ảnh và bấm được. Test: Playwright bước 2 (Task 2).
4. **Sản phẩm không có giá.** Thẻ không có khối giá rỗng; nút vẫn có. Test: `test_stl_no_price_no_empty_price_block` (Task 1).
5. **Kéo pin rồi canvas vẽ lại** (đổi Tỉ lệ / Bố cục sau khi kéo). Kéo vẫn chạy cho element mới và giá trị cũ còn. Test: Playwright bước 3 (Task 4).

---

## File Structure

```
elements/shop-look/
├─ meta.php        tag, name, category, css, js, editor
├─ options.php     danh sách dùng chung: ratios, splits (builder + render + test)
├─ builder.php     bảng cài đặt (8 nhóm pin)
├─ render.php      HTML 1 phần tử gốc
├─ shop-look.css   bố cục, pin, pulse, active, mobile
├─ view.js         đổi thẻ / sáng pin (ngoài site + canvas)
└─ editor.js       kéo pin trong canvas
src/Elements/Registry.php   (sửa) khoá meta 'editor', nạp ở enqueue_for_canvas
tests/ShopLookTest.php
docs/huong-dan-element.md   (sửa) thêm mục Shop The Look
```

---

### Task 1: Element, bảng cài đặt và render

**Files:**
- Create: `elements/shop-look/meta.php`, `elements/shop-look/options.php`, `elements/shop-look/builder.php`, `elements/shop-look/render.php`
- Test: `tests/ShopLookTest.php`

**Interfaces:**
- Consumes: `FBM\Elements\Registry` (tự tìm `elements/*/meta.php`), `FBM\Placeholder::empty( string )`, fixtures `fbm_fixtures_up()` / `fbm_fixtures_down()`, `fbm_qa_image()` (định nghĩa trong `tests/BeforeAfterTest.php`; nếu file test chạy riêng thì định nghĩa lại có `function_exists`).
- Produces:
  - `options.php` trả `array( 'ratios' => array( '1-1' => '1:1', '4-5' => '4:5', '3-4' => '3:4', '2-3' => '2:3', '3-2' => '3:2', '16-9' => '16:9' ), 'splits' => array( '50' => '50/50', '60' => '60/40', '66' => '66/33' ) )`.
  - Markup cho Task 2–4:
    - Phần tử gốc: `section#{_id}.fbm-stl` kèm class `fbm-stl--right` (khi `image_side=right`), `fbm-stl--split-60|66` (khi khác 50), `fbm-stl--numbers` (khi `pin_style=numbers`, ngược lại `fbm-stl--dots`), `fbm-stl--pulse` (khi `pulse=true`), `fbm-stl--tone-dark` (khi `pin_tone=dark`), `fbm-stl--ratio fbm-stl--r-{key}` (khi `ratio` hợp lệ).
    - `.fbm-stl__grid > .fbm-stl__media` chứa `img.fbm-stl__img`, và các `button.fbm-stl__pin`.
    - Mỗi pin có các thuộc tính `type="button"`, `data-slot="{N gốc 1..8}"`, `data-index="{i 0-based}"`, `aria-controls="{_id}-card-{i}"`, `aria-pressed`, `aria-label="Xem {tên}"`, `style="--fbm-x:{x}%;--fbm-y:{y}%"`. Bên trong pin có `<span class="fbm-stl__pin-num">{i+1}</span>`.
    - `.fbm-stl__cards[aria-live=polite] > article.fbm-stl__card#{_id}-card-{i}`, có `data-index`; thẻ i>0 có thêm `hidden`.
    - Nội dung thẻ: `.fbm-stl__card-img` (khi `card_image=true`), `a.fbm-stl__card-name`, `.fbm-stl__card-price` (chỉ khi `get_price_html()` khác rỗng), `a.button.primary.is-small.fbm-stl__card-btn`.

- [ ] **Step 1: Viết test thất bại** `tests/ShopLookTest.php`. Mỗi test tạo dữ liệu bằng `fbm_fixtures_up()` + `fbm_qa_image()` rồi dọn bằng `fbm_fixtures_down()`. Shortcode gọi qua helper `fbm_stl( string $atts ): string` (`do_shortcode( '[fbm_shop_look ' . $atts . ']' )`).
  - `test_stl_registered`: `Plugin::get('registry')->all()` có `fbm_shop_look`.
  - `test_stl_renders_pins_and_cards`: `image` + `pin1_product=simple pin1_x=30 pin1_y=40 pin2_product=sale pin3_product=attrs`. Mong đợi 3 `class="fbm-stl__pin`, 3 `fbm-stl__card"`, chứa `--fbm-x:30%;--fbm-y:40%`, `aria-controls="X-card-0"` (X = `_id` truyền vào), `data-slot="1"`.
  - `test_stl_first_card_visible_others_hidden`: thẻ 0 không có `hidden`, thẻ 1 và 2 có `hidden`.
  - `test_stl_skips_invalid_and_renumbers`: pin1 = `hidden`, pin2 = sản phẩm nháp, pin3 = ID của page, pin4 = `simple`. Mong đợi 1 pin, `data-slot="4"`, `data-index="0"`, số hiển thị `>1<`.
  - `test_stl_empty_guest_blank` / `test_stl_empty_editor_note`: không ảnh → khách `''`, editor chứa `fbm-empty`. Có ảnh nhưng 0 pin hợp lệ → như trên.
  - `test_stl_coordinates_clamped`: `pin1_x=150 pin1_y=-5` → `--fbm-x:100%;--fbm-y:0%`.
  - `test_stl_single_root`: chuỗi trim bắt đầu `<section` và kết thúc `</section>`, đúng 1 `<section`.
  - `test_stl_escapes_product_name`: đổi tên `simple` thành `A <script>x</script>` → output không chứa `<script>x`.
  - `test_stl_same_product_twice_distinct_cards` (Review Focus 1): pin1 và pin2 cùng `simple` → 2 thẻ, id `-card-0` và `-card-1`.
  - `test_stl_two_instances_unique_ids` (Review Focus 2): render 2 lần không truyền `_id` → hai id `section` khác nhau.
  - `test_stl_no_price_no_empty_price_block` (Review Focus 4): sản phẩm `set_regular_price('')` → không chứa `fbm-stl__card-price`, vẫn chứa `fbm-stl__card-btn`.
  - `test_stl_classes_from_options`: `image_side=right split=60 pin_style=numbers pin_tone=dark ratio=4-5` → đủ 5 class tương ứng; `ratio=evil` → không có `fbm-stl--r-`.
  - `test_stl_card_image_toggle`: `card_image=false` → không có `fbm-stl__card-img`.
  - `test_stl_button_text`: mặc định nút là `Xem sản phẩm`; `button_text="Mua ngay"` → nút chứa `Mua ngay`; href nút bằng `get_permalink( simple )`.
  - `test_stl_builder_options`: include `builder.php` với `$commons`. Mong đợi:
    - `wrap === false`.
    - Đủ 8 nhóm `pin1_options`…`pin8_options`.
    - `pin1_options` không có `conditions`; `pinN_options['conditions'] === 'pin' . (N-1) . '_product'` với N ≥ 2.
    - `pin3_x` có `step === 1`, `min === 0`, `max === 100`.
    - Danh sách `ratio` = `''` + các khoá trong `options.php`.
- [ ] **Step 2:** `sh bin/test.sh ShopLook`. Mong đợi: FAIL (`fbm_shop_look` chưa có).
- [ ] **Step 3: Implement**
  - `meta.php`: `css => 'shop-look.css'`, `js => 'view.js'`, `editor => 'editor.js'` (Registry đọc khoá này ở Task 4; trước đó bị bỏ qua).
  - `builder.php`: nhóm Chung / Ảnh / Bố cục / Pin / Thẻ theo spec §2.1. Mỗi nhóm `pinN_options` có `heading` "Pin N" và chứa `pinN_product` (select `postSelect` product, giống `product-attributes/builder.php`), `pinN_x`, `pinN_y`. Slider có `on_change => array( 'selector' => '.fbm-stl__pin[data-slot="N"]', 'style' => '--fbm-x: {{ value }}%' )` (với y là `--fbm-y`).
  - `render.php`: `shortcode_atts` với mặc định theo spec §2.1. Vòng N = 1..8: `wc_get_product( absint( pinN_product ) )`, giữ theo luật hợp lệ ở Global Constraints. Tiêu đề in bằng `do_shortcode( '[title text="…"]' )` (bỏ `"`, `[`, `]`, như `alias-v1.php`). Mô tả là `<p class="fbm-stl__desc">`.
- [ ] **Step 4:** `sh bin/test.sh ShopLook`, rồi `sh bin/test.sh`. Mong đợi: PASS hết.
- [ ] **Step 5: Kiểm R2 trong UX Builder thật.** Tạo trang có `[fbm_shop_look]` (không có meta `_fbm_qa`). Mở UX Builder, chọn element:
  - Mong đợi chỉ thấy nhóm "Pin 1".
  - Chọn sản phẩm cho Pin 1 thì "Pin 2" hiện ra.
  - Nếu `conditions` trên group không chạy: chuyển `conditions` xuống từng option trong nhóm, sửa test builder cho khớp, rồi ghi `Ruling:` vào ledger.
- [ ] **Step 6:** Commit `feat: fbm_shop_look element and render`.

---

### Task 2: CSS: bố cục, pin, pulse, active, mobile

**Files:**
- Create: `elements/shop-look/shop-look.css`
- Test: `tests/ShopLookTest.php` (thêm)

**Interfaces:**
- Consumes: class và markup ở Task 1 (Produces).
- Produces: class trạng thái do JS bật/tắt: `.is-active` (pin đang chọn), `.is-highlight` (pin sáng khi rê chuột lên thẻ).

- [ ] **Step 1: Test thất bại**
  - `test_stl_css_rules`: đọc `shop-look.css`, không khớp `/#[0-9a-f]{3,6}\b|rgba?\(|!important|font-family/i` sau khi xoá các đoạn `var(--…, …)`. Cách lọc dùng lại như `test_ba_css_rules`.
  - `test_stl_css_has_ratio_rules`: mỗi khoá trong `options.php['ratios']` có dòng `.fbm-stl--r-{w}-{h} .fbm-stl__media { aspect-ratio: {w} / {h}; }`.
  - `test_stl_css_reduced_motion`: chứa `@media (prefers-reduced-motion: reduce)`.
  - `test_stl_assets_enqueued_only_when_used`: `wp_dequeue_style/script('fbm-shop-look')`, render chỉ `[fbm_products]` → `wp_style_is('fbm-shop-look')` false; render `[fbm_shop_look …]` → `wp_style_is` và `wp_script_is('fbm-shop-look')` true.
- [ ] **Step 2:** `sh bin/test.sh ShopLook`. Mong đợi: FAIL (file CSS chưa có).
- [ ] **Step 3: Implement** theo spec §4. Các giá trị cố định:
  - Pin đường kính 32px, vùng bấm 44px.
  - Vị trí pin: `left: clamp(22px, var(--fbm-x), calc(100% - 22px)); top: clamp(22px, var(--fbm-y), calc(100% - 22px)); transform: translate(-50%, -50%)` (Review Focus 3).
  - Pulse: `@keyframes fbm-stl-pulse` scale 1 → 2.2, opacity .6 → 0, 2s lặp vô hạn.
  - Pin `.is-active`: `scale(1.15)`, transition `transform .25s cubic-bezier(.2,.8,.2,1)`.
  - Lưới desktop: `grid-template-columns` 1fr 1fr / 3fr 2fr / 2fr 1fr theo split; `fbm-stl--right` đảo thứ tự bằng `order`.
  - `≤ 549px`: 1 cột, thẻ dưới ảnh.
  - Ảnh có `fbm-stl--ratio`: `object-fit: cover`, phủ kín `.fbm-stl__media`.
  - Chế độ `fbm-stl--dots` ẩn `.fbm-stl__pin-num` (vẫn đọc được bằng trình đọc màn hình); `fbm-stl--numbers` hiện số.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Sau đó kiểm bằng Playwright trên trang có 3 pin, trong đó pin3 có `pin3_x=100 pin3_y=0`:
  1. Ở 375 / 768 / 1280px: `scrollWidth` bằng chiều rộng màn; 1280px có 2 cột, 375px thẻ nằm dưới ảnh.
  2. Pin 3 nằm trọn trong `.fbm-stl__media` (bounding box nằm trong) và bấm được (Review Focus 3).
  3. Bật `reducedMotion: 'reduce'`: `getComputedStyle(pin, '::after').animationName` là `none`.
- [ ] **Step 5:** Commit `feat: shop the look styles`.

---

### Task 3: `view.js`: bấm pin đổi thẻ, rê thẻ làm sáng pin

**Files:**
- Create: `elements/shop-look/view.js`

**Interfaces:**
- Consumes: markup Task 1, class trạng thái Task 2.
- Produces: không có (Task 4 dùng chung markup, không gọi hàm của view.js).

- [ ] **Step 1: Viết kịch bản Playwright trước** (bước "test" của JS; project không có test runner JS), lưu ở `tests/playwright/shop-look-view.js` để chạy lại khi QA. Trang có 2 element Shop The Look, mỗi element 3 pin. Kịch bản kiểm:
  1. Lúc đầu: pin 0 `aria-pressed="true"` và `.is-active`; chỉ thẻ 0 hiện.
  2. Bấm pin 2 → thẻ 2 hiện, thẻ 0 `hidden`, pin 2 `aria-pressed="true"`, pin 0 `"false"`.
  3. Element thứ hai không đổi (Review Focus 2).
  4. Tab tới pin 1, nhấn Enter → thẻ 1 hiện.
  5. Rê chuột lên thẻ đang hiện → pin tương ứng có `.is-highlight`; rời chuột thì mất.
  6. Ở 375px: cuộn trang để thẻ khuất, bấm pin → thẻ vào khung nhìn (`getBoundingClientRect().top < innerHeight`).
- [ ] **Step 2:** Chạy kịch bản. Mong đợi: FAIL ở bước 2 (chưa có view.js).
- [ ] **Step 3: Implement `view.js`** dạng IIFE như `before-after/view.js`:
  - `init(root)` đánh dấu `root.dataset.fbmReady`.
  - Hàm cục bộ `select(root, index, { scroll })`.
  - `scan` + MutationObserver cho phần tử chèn sau.
  - Mọi truy vấn DOM giới hạn trong `root`.
  - Cuộn chỉ khi `matchMedia('(max-width: 549px)')`, `behavior` là `'auto'` nếu giảm chuyển động.
- [ ] **Step 4:** Chạy lại kịch bản. Mong đợi: PASS cả 6 bước. `sh bin/test.sh`. Mong đợi: PASS.
- [ ] **Step 5:** Commit `feat: shop the look pin/card interaction`.

---

### Task 4: Kéo pin trong canvas (`editor.js`) + Registry nạp file chỉ cho canvas

**Files:**
- Create: `elements/shop-look/editor.js`
- Modify: `src/Elements/Registry.php` (đọc meta `editor`, nạp trong `enqueue_for_canvas()`)
- Test: `tests/ShopLookTest.php` (thêm)

**Interfaces:**
- Consumes: `data-slot` trên pin (Task 1), meta `editor` (Task 1).
- Produces: `Registry::all()` mỗi phần tử có thêm khoá `editor` (`string|null`). Handle script `fbm-{slug}-editor`.

- [ ] **Step 1: Test thất bại**
  - `test_registry_reads_editor_meta`: `all()['fbm_shop_look']['editor'] === 'editor.js'`; `all()['fbm_products']['editor'] === null`.
  - `test_stl_editor_js_only_in_canvas`: dequeue `fbm-shop-look-editor`, render element → `wp_script_is( 'fbm-shop-look-editor', 'enqueued' ) === false`. Sau `do_action( 'ux_builder_enqueue_scripts', 'content' )` → `true`.
- [ ] **Step 2:** `sh bin/test.sh ShopLook`. Mong đợi: FAIL.
- [ ] **Step 3: Implement**
  - `Registry`: thêm `'editor' => isset( $meta['editor'] ) ? (string) $meta['editor'] : null`. Trong `enqueue_for_canvas()`, sau `enqueue( $el )`, nạp `editor` với handle `fbm-{slug}-editor`, phụ thuộc handle `fbm-{slug}`.
  - `editor.js` theo spec §6:
    - `findShortcode(root)` duyệt `$rootScope` (`angular.element(document.documentElement).injector().get('$rootScope')`) tìm object có `tag === 'fbm_shop_look'` và `$element` chứa `root` (`$element[0] === root || $element[0].contains(root)`).
    - Kéo: `pointerdown` trên `.fbm-stl__pin`, `pointermove` trên `document`, `stopPropagation` + `preventDefault`. Trong lúc kéo chỉ đặt `--fbm-x/--fbm-y` (đã kẹp 0–100, làm tròn). Khi `pointerup`: `sc.options['pin' + slot + '_x'] = x` (số nguyên), tương tự y, rồi `$rootScope.$apply()` một lần.
    - Bấm vùng trống của `.fbm-stl__media` (không trúng pin): pin chọn gần nhất (mặc định pin `data-index=0`) nhảy tới chỗ bấm và ghi như trên.
    - Bảo vệ: thiếu `angular`, injector hoặc shortcode → `return` không gắn gì. Bọc toàn bộ trong `try/catch`.
    - MutationObserver gắn lại cho `.fbm-stl` mới; `root.dataset.fbmEditorReady` để không gắn 2 lần.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Sau đó kiểm bằng Playwright trong UX Builder thật (trang không có `_fbm_qa`, user admin tạm):
  1. Element có 3 pin. Kéo pin 2 tới vị trí khoảng (70 %, 30 %) → bấm Save → `wp post get … --field=post_content` có `pin2_x="70"` (±2) và `pin2_y="30"` (±2).
  2. Bấm vùng trống tại (20 %, 80 %) sau khi đã chọn pin 1 → lưu `pin1_x≈20`, `pin1_y≈80`.
  3. Đổi Tỉ lệ khung sang 1:1, rồi kéo pin 3, rồi đổi Bố cục sang Phải, rồi kéo pin 3 lần nữa → mỗi lần canvas theo đúng, giá trị cuối được lưu (Review Focus 5).
  4. Đổi `pin1_x` bằng thanh trượt 3 lần liên tiếp → canvas theo cả 3 lần.
  5. Undo (nút hoàn tác UX Builder) sau một cú kéo: ghi lại kết quả. Nếu không lùi được thì ghi giới hạn vào spec §8 R4 và ledger.
  6. Giả lập mất API: trong canvas `delete window.angular` trước khi editor.js chạy (nạp lại trang với script chặn) → không có lỗi console từ `editor.js`, thanh X/Y vẫn đổi canvas.
- [ ] **Step 5:** Commit `feat: drag shop the look pins in UX Builder canvas`.

---

### Task 5: Hướng dẫn, QA ngoài site, đóng gói

**Files:**
- Modify: `docs/huong-dan-element.md` (thêm mục "8. Shop The Look" sau Reviews, trước nút 3D; cập nhật số mục sau)
- Modify: `docs/qa/2026-10-19-v2-qa.md` (thêm dòng QA Shop The Look)

- [ ] **Step 1:** Viết mục hướng dẫn: cách thêm element; bảng cài đặt (theo spec §2.1, chữ cho người dựng trang); cách đặt pin (kéo trên ảnh, bấm chỗ trống, thanh X/Y); giới hạn (tối đa 8 pin, không kéo sắp thứ tự, màu theo theme).
- [ ] **Step 2:** QA ngoài site trên DDEV (dữ liệu từ `tests/qa-seed.php up`):
  - 375 / 768 / 1280px: không tràn ngang, không notice với `WP_DEBUG`.
  - Bấm pin đổi thẻ; giá biến thể hiện khoảng giá; giá sale có giá gạch.
  - Tab / Enter qua các pin, viền focus nhìn thấy.
  - Trang không có element thì không nạp `shop-look.css` / `view.js`.
  - Ghi kết quả từng dòng vào file QA, kèm "kiểm thật" hay "review code".
- [ ] **Step 3:** `sh bin/test.sh` toàn bộ và `php -l elements/shop-look/*.php src/Elements/Registry.php`. Mong đợi: 0 fail, 0 lỗi cú pháp.
- [ ] **Step 4:** `npm run build && sh bin/zip.sh`, rồi chép thành `~/Documents/project-wp/zips/flatsome-block-manager-2.0.0.zip`. `unzip -l` có `elements/shop-look/editor.js`, không có `tests/`.
- [ ] **Step 5:** Trong `~/Documents/project-wp/handdn`: `ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/qa-seed.php down`, xoá trang và user tạm đã tạo cho Playwright. Commit `docs: shop the look guide + QA`.

---

## Amendment 2026-10-19: pin là element con (spec §9)

Chèn **Task 1b** trước Task 3. Task 3–5 dùng markup sau Task 1b.

### Task 1b: Container + `fbm_look_pin`

**Files:**
- Create: `elements/look-pin/{meta.php,builder.php,render.php}`, `elements/shop-look/template.html`
- Modify: `elements/shop-look/{builder.php,render.php,shop-look.css}`, `tests/ShopLookTest.php`

**Interfaces:**
- Produces:
  - Shortcode `[fbm_look_pin product x y]`.
  - `fbm_shop_look` builder có `type => 'container'`, `allow => array( 'fbm_look_pin' )`, `template` (nội dung `template.html`), preset 3 pin.
  - Render ngoài site: như Task 1, bỏ `data-slot`. `data-index` = thứ tự pin hợp lệ; `.fbm-stl__pin-num` để trống, số do CSS counter `fbm-stl-pin`.
  - Render `fbm_look_pin` đứng riêng (canvas): `<div class="fbm-stl__item">` + `button.fbm-stl__pin` + `article.fbm-stl__card.fbm-stl__card--tpl[hidden]`. Sản phẩm không hợp lệ thì `''`.
- Consumes (Task 3): `view.js` lấy thẻ theo `data-index` trong `.fbm-stl__cards`; nếu cột trống (canvas) thì sao chép `.fbm-stl__card--tpl` của pin đang chọn vào cột. Thứ tự pin tính theo DOM.
- Consumes (Task 4): `editor.js` ghi `options.x/y` của shortcode `fbm_look_pin` có `$element` chứa pin.

- [ ] **Step 1: Sửa test** trong `tests/ShopLookTest.php`:
  - Shortcode viết dạng `[fbm_shop_look …][fbm_look_pin product="…" x="30" y="40"]…[/fbm_shop_look]` (helper `fbm_stl( $atts, array $pins )`).
  - Bỏ kiểm tra `data-slot`.
  - `test_stl_skips_invalid_and_renumbers`: kiểm `data-index="0"` cho pin hợp lệ duy nhất.
  - `test_stl_builder_options`: `type === 'container'`, `allow === array('fbm_look_pin')`, có `template`, không còn khoá `pin1_options`.
  - Thêm `test_look_pin_builder`: `wrap === false`, `allow_in === array( 'fbm_shop_look' )`, slider `x` có `step` 1.
  - Thêm `test_look_pin_standalone_item`: render `[fbm_look_pin product=simple x=10 y=20]` thì bắt đầu bằng `<div class="fbm-stl__item"`, chứa `--fbm-x:10%;--fbm-y:20%` và `fbm-stl__card--tpl`; sản phẩm nháp thì trả `''`.
  - Thêm `test_stl_unlimited_pins`: 12 pin hợp lệ thì có 12 nút.
  - Thêm `test_stl_css_counter`: CSS chứa `counter-increment: fbm-stl-pin`.
- [ ] **Step 2:** `sh bin/test.sh ShopLook`. Mong đợi: FAIL.
- [ ] **Step 3: Implement** theo Interfaces và spec §9.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Kiểm trong UX Builder: thêm pin bằng +, đổi sản phẩm/X/Y của pin 2 thì canvas đổi; ảnh và class của cha cập nhật khi đổi option.
- [ ] **Step 5:** Commit `refactor: shop the look pins as child elements`.
