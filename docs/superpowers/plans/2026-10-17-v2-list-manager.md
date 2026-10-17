# Flatsome Block Manager v2 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Viết lại plugin tại chỗ thành v2: admin chỉ có 3 tab danh sách (UX Blocks · Element plugin · Element Flatsome). Mỗi dòng có Bật / Ẩn / Tắt. Kèm 6 element viết sẵn bằng file và 10 block mẫu nạp qua UX Builder › Import.

**Architecture:** Không còn module/container/settings của v1. `Plugin.php` khởi động một số service nhỏ, mỗi service một file:
- `States` + `Catalog` cho element.
- `BlockStates` cho UX Block.
- `UsageIndex` đếm nơi dùng.
- `Elements\Registry` nạp `elements/<slug>/`.
- `Templates` nạp `templates/<slug>/`.
- `Admin` gồm trang + REST.

Element render theo markup Flatsome/WooCommerce. Database chỉ lưu trạng thái.

**Tech Stack:** PHP 7.4+, WordPress 6.4+, Flatsome 3.20.11 (UX Builder API), WooCommerce 11.x, React qua `@wordpress/scripts` 30 (`@wordpress/components`, `@wordpress/api-fetch`). Test bằng PHP chạy qua `ddev wp eval-file` trên site DDEV `handdn`. UX Builder và admin kiểm bằng Playwright.

**Spec:** `docs/superpowers/specs/2026-10-17-v2-ui-blocks-design.md`. Đọc ghi chú "Cập nhật lần 4" ở §0 trước; nó thắng mọi dòng mâu thuẫn bên dưới. Trang design: https://claude.ai/artifact/PMKFjzxBTvU1mejw9DQryY (bản 4).

## Global Constraints

- Slug/text domain `flatsome-block-manager`, namespace PHP `FBM\`, `FBM_VERSION` = `2.0.0`, `Requires at least: 6.4`, `Requires PHP: 7.4`.
- Admin có đúng 3 tab: **UX Blocks · Element plugin · Element Flatsome**. Không có trang Cài đặt, Thư viện, Hướng dẫn, Xuất/Nhập, hẹn giờ, Gutenberg, chỗ nhập code.
- Trạng thái hợp lệ: `on` | `hidden` | `off`. Database chỉ lưu `hidden`/`off`; không có bản ghi nghĩa là `on`.
- Element khung khoá ở `on`: `section`, `row`, `row_inner`, `col`, `col_inner`, `col_grid`, `ux_stack`, `text`, `block`, `_root`.
- REST namespace `fbm/v2`. Chỉ nhận tag có trong Catalog/Registry + 1 trong 3 trạng thái, hoặc ID post type `blocks` + 1 trong 3 trạng thái. Element cần `manage_options`, UX Block cần `edit_pages`. Nonce REST của WP (`X-WP-Nonce`).
- Shortcode mới có tiền tố `fbm_`. Ngoại lệ: `handdn_product_attributes`, giữ tag của người dùng.
- Mặc định `'_id' => '<tên>-' . rand()` theo quy ước Flatsome. Mọi element có nhóm `@commons/advanced` (class, visibility).
- CSS theo spec §4.2:
  - Chỉ CSS bố cục trong `.fbm-compare__*`, `.fbm-ba__*`.
  - Màu/viền/bo góc chỉ qua `var(--primary-color)`, `var(--secondary-color, #f8f8f8)`, `var(--text-color, #6b818c)`, `var(--color-notactive, #abb8c3)`, `var(--color-grey, #ececec)`, `var(--success-color, #60b2b7)`, `var(--global-radius, 4px)`, `var(--fs-color-alert)`.
  - Không hex/rgba mới, không `font-family`, không `!important`, không selector toàn cục.
  - File CSS/JS chỉ nạp khi element có trên trang.
- Phần tử tương tác của element mới: `:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px }`.
- Trống: khách không thấy gì. Người có `edit_pages` (hoặc đang trong UX Builder) thấy 1 dòng `<p class="fbm-empty op-7">…</p>`.
- Tắt: khách không thấy gì. Người có `edit_pages` thấy khung nét đứt (markup placeholder của v1 `Visibility::placeholder()`).
- Breakpoint 549px / 849px. Không thêm schema FAQPage. Escape mọi đầu ra.
- Không sửa theme Flatsome. Không bind-mount source vào DDEV: đồng bộ bằng `rsync` (sự cố xoá source ở v1).

## Review Focus

1. **Element/UX Block bị Tắt nhưng nằm trong một UX Block khác** (`[block]` lồng nhau, header/footer block). Khách thấy rỗng, không vỡ layout; editor thấy placeholder; feed/REST không lộ placeholder. Test: `test_off_element_inside_block_renders_empty_for_guest` (Task 2).
2. **REST bị gọi với dữ liệu giả.** Gồm: tag khung (`section`), tag lạ (`evil_tag`), trạng thái `delete`, ID của page thay vì block, user `editor` đổi element. Kết quả mong đợi: 400/403 và không đổi gì. Test: `test_rest_rejects_*` (Task 10).
3. **Chưa từng mở UX Builder sau khi cài**, Catalog rỗng. Tab Element Flatsome hiện hướng dẫn "Mở UX Builder một lần để nạp danh sách", không phải bảng trống; REST từ chối tag Flatsome chưa biết. Test: `test_catalog_empty_flatsome_list` (Task 2).
4. **Nội dung v1 còn dùng tag cũ sau khi nâng cấp.** Các tag: `[fbm_product_slider …]` (block #78 trên DDEV), `[fbm_hello]`, `[fbm_before_after line_color=…]`. Phải hiển thị hợp lý hoặc rỗng, không PHP warning. Test: `test_v1_slider_alias_maps_atts`, `test_v1_unknown_tag_renders_empty` (Task 11).
5. **Truy vấn sản phẩm gặp dữ liệu khó.** Các ca: sản phẩm ẩn khỏi danh mục, hết hàng khi bật "ẩn hàng hết", nguồn `sale` không có sản phẩm giảm giá, nguồn `related` ở trang không phải sản phẩm và giỏ rỗng. Phải tôn trọng visibility, không fatal, rỗng thì trả `''` cho khách. Test: `test_query_*` (Task 3).

---

## File Structure

```
flatsome-block-manager/
├─ flatsome-block-manager.php     (sửa: header 2.0.0, bootstrap mới)
├─ uninstall.php                  (viết lại)
├─ src/
│  ├─ autoload.php                (giữ)
│  ├─ Plugin.php                  (viết lại)
│  ├─ Requirements.php            (chuyển từ Core/)
│  ├─ Installer.php               (chuyển từ Core/, chỉ bảng usage)
│  ├─ Upgrade.php                 (mới, dọn v1 – Task 11)
│  ├─ Placeholder.php             (mới, khung nét đứt + dòng trống dùng chung)
│  ├─ States.php · Catalog.php    (element)
│  ├─ BlockStates.php             (UX Block)
│  ├─ Usage/UsageScanner.php · Usage/UsageRepository.php · Usage/UsageIndex.php
│  ├─ Elements/Registry.php
│  ├─ Products/Query.php · Products/Loop.php
│  ├─ Templates.php
│  ├─ Handdn/Product3DButton.php  (chuyển từ Modules/Handdn)
│  └─ Admin/Page.php · Admin/Rest.php
├─ elements/<slug>/meta.php · builder.php · render.php [· *.css · view.js]
│     products · product-attributes · product-compare · reviews · before-after · ba-carousel
├─ templates/<slug>/meta.php · content.txt · thumb.svg   (10 thư mục)
├─ assets/admin-src/ (React) → build/admin.js · assets/handdn/ (giữ) · assets/img/element.svg (giữ)
├─ tests/bootstrap.php · tests/run.php · tests/fixtures.php · tests/*Test.php
└─ bin/test.sh (mới) · bin/zip.sh (sửa: bỏ tests/, docs/)
```

**Xoá hẳn:**
- `src/Core/`, `src/Modules/`.
- `starters/` (sau khi port Before/After ở Task 8).
- `assets/runtime/`, `assets/src/gutenberg/`, `assets/src/admin/` (thay bằng `assets/admin-src/`).
- `DESIGN.md` (thay bằng `readme.txt` cập nhật).

---

### Task 0: git + bộ chạy test

Thư mục chưa phải git repo. **Bước 1 cần người dùng đồng ý.**

**Files:**
- Create: `.gitignore`, `tests/bootstrap.php`, `tests/run.php`, `tests/fixtures.php`, `tests/SmokeTest.php`, `bin/test.sh`
- Modify: `bin/zip.sh` (thêm `--exclude tests --exclude docs`)

**Interfaces:**
- Produces:
  - `fbm_test( string $name, callable $fn ): void`
  - `fbm_assert_same( $expected, $actual, string $msg = '' ): void`
  - `fbm_assert_true( bool $v, string $msg = '' ): void`
  - `fbm_assert_contains( string $needle, string $haystack, string $msg = '' ): void`
  - `fbm_assert_not_contains(...)`
  - `fbm_as_user( string $role, callable $fn )`: tạo user tạm, chạy, xoá.
  - `fbm_fixtures_up(): array{simple:int,variable:int,sale:int,outofstock:int,hidden:int,noimage:int,attrs:int,block_a:int,block_b:int}`
  - `fbm_fixtures_down(): void`: xoá mọi post có meta `_fbm_qa`, attribute `ban-rong`, `loai-da`, `mau-chi`.
  - `bin/test.sh [filter]`.

- [ ] **Step 1:** `git init`, `.gitignore` (`node_modules/`, `build/`, `dist/`, `.playwright-mcp/`). Commit baseline v1 + module handdn: `chore: baseline v1.0 + handdn module`.
- [ ] **Step 2:** Viết `tests/SmokeTest.php`: `fbm_test('smoke_plugin_loaded', fn() => fbm_assert_true(defined('FBM_VERSION')))`.
- [ ] **Step 3:** Viết harness:
  - `tests/run.php` nạp bootstrap, include `tests/*Test.php` (lọc theo `$args[0]` nếu có), in `PASS/FAIL tên`, cuối cùng `N passed, M failed`; `exit(1)` nếu có fail.
  - `bin/test.sh` export PATH Docker, `rsync -a --delete --exclude node_modules --exclude dist --exclude .git ./ ~/Documents/project-wp/handdn/wp-content/plugins/flatsome-block-manager/`, rồi `cd ~/Documents/project-wp/handdn && ddev wp eval-file wp-content/plugins/flatsome-block-manager/tests/run.php "$@"`.
- [ ] **Step 4:** Chạy `sh bin/test.sh`. Mong đợi: `1 passed, 0 failed`.
- [ ] **Step 5:** `ddev snapshot --name before-v2` (trong `~/Documents/project-wp/handdn`). Commit `test: wp-cli test harness on DDEV`.

---

### Task 1 (B1a): Bootstrap v2, gỡ v1

**Files:**
- Modify: `flatsome-block-manager.php`, `src/Plugin.php`, `assets/admin-src/` (tạm: App rỗng 3 tab), `package.json` (entry chỉ `admin=./assets/admin-src/index.js`)
- Move: `src/Core/Requirements.php` → `src/Requirements.php`; `src/Core/Installer.php` → `src/Installer.php` (bỏ bảng versions); `src/Modules/Handdn/Product3DButton.php` → `src/Handdn/Product3DButton.php`
- Create: `src/Placeholder.php`
- Delete: `src/Core/`, `src/Modules/` (giữ lại bản sao `Visibility.php`, `UsageScanner.php`, `UsageRepository.php`, `ProductAttributes.php` trong `tests/_v1/` đến hết Task 5 để tham chiếu, rồi xoá), `assets/src/`, `assets/runtime/`
- Test: `tests/BootTest.php`

**Interfaces:**
- Produces:
  - `FBM\Plugin::start(): void` (gọi ở `after_setup_theme` priority 20)
  - `FBM\Plugin::get( string $id ): object` cho `states`, `catalog`, `block_states`, `usage`, `registry`
  - `FBM\Placeholder::off( string $label ): string`
  - `FBM\Placeholder::empty( string $text ): string`. Cả hai trả `''` nếu user không có `edit_pages` và không trong UX Builder, hoặc khi `is_feed()` / `REST_REQUEST`.

- [ ] **Step 1: Test thất bại.** `tests/BootTest.php`:
  - `test_v1_routes_gone`: `rest_get_server()->get_routes()` không có key nào bắt đầu `/fbm/v1`.
  - `test_no_settings_option_written`: sau `Plugin::start()` không có option `fbm_settings` mới.
  - `test_placeholder_guest_empty`: `wp_set_current_user(0)`, `Placeholder::off('X') === ''`.
- [ ] **Step 2:** `sh bin/test.sh Boot`. Mong đợi: FAIL (route `/fbm/v1/...` còn).
- [ ] **Step 3:** Viết lại bootstrap.
  - `Plugin::start()` kiểm `Requirements::missing()`, gọi `Installer::maybe_upgrade()`, khởi tạo service (Task 2+ thêm vào) và `Handdn\Product3DButton`.
  - `Placeholder::off` dùng lại markup/CSS inline của `Visibility::placeholder()` v1.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS hết. `npm run build` thành công.
- [ ] **Step 5:** Commit `refactor: v2 bootstrap, remove v1 modules`.

---

### Task 2 (B1b): States + Catalog cho element

**Files:**
- Create: `src/States.php`, `src/Catalog.php`
- Test: `tests/StatesTest.php`

**Interfaces:**
- Consumes: `Placeholder::off`.
- Produces:
  - `FBM\States`:
    - `const OPTION = 'fbm_element_states'`, `const LOCKED = [ ...danh sách Global Constraints ]`.
    - `get( string $tag ): string`.
    - `set( string $tag, string $state ): true|\WP_Error`. Mã lỗi: `fbm_locked`, `fbm_unknown_tag`, `fbm_bad_state`.
    - `all(): array<string,string>`.
    - `boot(): void`.
  - `FBM\Catalog`:
    - `const OPTION = 'fbm_catalog'`.
    - `flatsome(): array<string tag, array{name:string, category:string}>`.
    - `has( string $tag ): bool`: tag trong snapshot, hoặc trong Registry, hoặc `handdn_3d_button`. Registry có từ Task 5; trước đó chỉ cần snapshot + `handdn_3d_button`.
    - `boot(): void`.

- [ ] **Step 1: Test thất bại**:
  - `test_default_state_on`: `get('ux_lottie') === 'on'`.
  - `test_locked_cannot_change`: `set('section','off')` là `WP_Error` code `fbm_locked`.
  - `test_unknown_tag_rejected`: `set('evil_tag','off')` có code `fbm_unknown_tag`.
  - `test_bad_state_rejected`: `set('ux_lottie','delete')` có code `fbm_bad_state`.
  - `test_builder_data_hidden`: sau `set('ux_lottie','hidden')`, `apply_filters('ux_builder_shortcode_data', ['hidden'=>false], 'ux_lottie')['hidden'] === true`.
  - `test_off_renders_empty_for_guest`: `set('map','off')`, `wp_set_current_user(0)`, `do_shortcode('[map]') === ''`.
  - `test_off_element_inside_block_renders_empty_for_guest`: block fixture chứa `[map]`, `do_shortcode('[block id="'.$block_a.'"]')` không chứa markup map.
  - `test_catalog_empty_flatsome_list`: xoá option, `flatsome() === []`, `has('ux_lottie') === false`.

  Test tự ghi snapshot giả (`update_option(Catalog::OPTION, ['ux_lottie'=>…,'map'=>…])`) và dọn lại.
- [ ] **Step 2:** `sh bin/test.sh States`. Mong đợi: FAIL (class not found).
- [ ] **Step 3:** Implement.
  - `Catalog::boot()`:
    - Filter `ux_builder_shortcode_data` priority 5 ghi nhận `[tag => name, category]`. Chỉ ghi khi `$data['hidden'] === false` và tag không thuộc Registry.
    - `shutdown` lưu option (autoload `false`) khi có dữ liệu và khác bản cũ.
  - `States::boot()`:
    - Filter `ux_builder_shortcode_data` priority 10 đặt `hidden = true` cho `hidden|off`.
    - Filter `pre_do_shortcode_tag` priority 9 trả `Placeholder::off($name)` cho `off`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS.
- [ ] **Step 5: Kiểm chứng A1 trên UX Builder thật (Playwright, user QA tạm như lần trước).**
  1. Đặt `ux_lottie` = hidden.
  2. Mở `post.php?post=<page>&action=edit&app=uxbuilder&type=editor`.
  3. Mong đợi: `uxBuilderData.shortcodes.ux_lottie.hidden === true`, và Add Elements không có "Lottie". Một trang đã có `[ux_lottie]` vẫn render trên canvas.

  Nếu không đạt thì dùng phương án A1 trong spec (`ux_builder_remove_element` chỉ khi `ux_builder_is_editor()`), rồi ghi lại vào spec §9.
- [ ] **Step 6:** Commit `feat: element states on/hidden/off + catalog snapshot`.

---

### Task 3 (B2): Trạng thái UX Block + nơi dùng

**Files:**
- Create: `src/BlockStates.php`, `src/Usage/UsageIndex.php`
- Move: `UsageScanner.php`, `UsageRepository.php` → `src/Usage/` (namespace `FBM\Usage`, logic giữ nguyên, bỏ `fbm_element` khỏi `SKIP_POST_TYPES`)
- Test: `tests/BlockStatesTest.php`, `tests/UsageTest.php`

**Interfaces:**
- Produces:
  - `FBM\BlockStates`:
    - `const META = '_fbm_state'`.
    - `get( int $id ): string`.
    - `set( int $id, string $state ): true|\WP_Error`. Mã lỗi `fbm_not_block` nếu không phải post type `blocks`, `fbm_bad_state`.
    - `rows(): array<int, array{id:int,title:string,slug:string,state:string,usage:int}>`.
    - `boot()`.
  - `FBM\Usage\UsageIndex`:
    - `block_counts( int[] $ids ): array<int,int>`.
    - `element_counts(): array<string,int>`: số post có tag. Transient `fbm_element_usage` 1 giờ, xoá ở `save_post`.
    - `is_built(): bool`.
    - `rebuild_batch( int $offset ): array{next:int|null,total:int}`.
    - `boot()`: hook của v1 `Usage/Module::boot`.

- [ ] **Step 1: Test thất bại**:
  - `test_block_default_on`.
  - `test_set_page_id_rejected`: ID của page trả `fbm_not_block`.
  - `test_block_off_guest_empty`: `do_shortcode('[block id="'.$slug.'"]') === ''`.
  - `test_block_hidden_still_renders`.
  - `test_hidden_block_excluded_from_builder_search`: `apply_filters('ux_builder_ajax_posts_search_posts_get_posts_args', ['post_type'=>['blocks']])['post__not_in']` chứa ID hidden và off, không chứa ID on.
  - `test_search_args_untouched_for_pages`: `post_type` `['page']` thì không thêm `post__not_in`.
  - `test_element_counts`: fixture page chứa `[map][map][ux_lottie]` thì `map => 1`, `ux_lottie => 1`.
- [ ] **Step 2:** `sh bin/test.sh Block` và `sh bin/test.sh Usage`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement.
  - Off: `pre_do_shortcode_tag` cho tag `block`, phân giải ID bằng `flatsome_get_block_id`.
  - Hidden: filter search args. Gộp với `post__not_in` sẵn có, không ghi đè.
  - `element_counts`: một query `post_content LIKE '%[%'` trên post `publish`/`private`/`draft` (trừ revision), regex `/\[([a-z0-9_-]+)[\s\]\/]/i`, đếm mỗi post một lần cho mỗi tag.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS.
- [ ] **Step 5:** Commit `feat: UX Block on/hidden/off + usage counts`.

---

### Task 4 (B3): Products\Query + Products\Loop

**Files:**
- Create: `src/Products/Query.php`, `src/Products/Loop.php`
- Test: `tests/ProductsQueryTest.php`

**Interfaces:**
- Produces:
  - `FBM\Products\Query::ids( array $atts ): int[]`. Các key trong `$atts`:
    - `source`: `recent` | `featured` | `sale` | `best_selling` | `top_rated` | `ids` | `cross_sell` | `upsell` | `related` | `auto`.
    - `ids`, `cat`, `tags` (CSV ID hoặc slug), `exclude`.
    - `products` (số, mặc định 8, tối đa 48), `offset`.
    - `orderby`: `date` | `price` | `popularity` | `rating` | `title` | `menu_order` | `rand`. `order`: `ASC` | `DESC`.
    - `price_min`, `price_max`, `in_stock` (`true`/`false`).
  - `FBM\Products\Loop::render( int[] $ids, array $repeater ): string`. `$repeater` là các key theo `ux_products.php:212-232` (`type`, `columns`, `columns__md`, `columns__sm`, `row_spacing`, `slider_style`, `slider_nav_color`, `slider_nav_position`, `slider_bullets`, `auto_slide`, `infinitive`, `id`, `class`, `visibility`, `depth`, `depth_hover`).

- [ ] **Step 1: Test thất bại** (dùng `fbm_fixtures_up()`):
  - `test_query_excludes_hidden_from_catalog`.
  - `test_query_respects_hide_out_of_stock`: `update_option('woocommerce_hide_out_of_stock_items','yes')`.
  - `test_query_sale_source_empty_returns_empty_array`.
  - `test_query_related_off_product_page_empty_cart_returns_empty`.
  - `test_query_ids_keeps_order`.
  - `test_query_unknown_category_returns_empty`: lỗi v1 trả toàn bộ sản phẩm.
  - `test_query_products_capped_48`.
  - `test_loop_uses_content_product`: output chứa `product-small` và `woocommerce-loop-product__link`.
- [ ] **Step 2:** `sh bin/test.sh ProductsQuery`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement.
  - Query: `WP_Query` `post_type=product`, `fields=ids`, `no_found_rows=true`, `tax_query` `product_visibility` (`exclude-from-catalog`, và `outofstock` khi option bật). `sale` dùng `wc_get_product_ids_on_sale()`. `best_selling` dùng meta `total_sales`. `top_rated` dùng `_wc_average_rating`.
  - `cross_sell`/`upsell`/`related` lấy từ sản phẩm đang xem (`get_queried_object_id()`); ngoài trang sản phẩm thì lấy từ `WC()->cart`. `auto` lấy nguồn đầu tiên có dữ liệu theo thứ tự cross_sell → upsell → related.
  - Có thể tham khảo logic lọc của v1 `starters/product-slider-pro/template.php.tpl`.
  - Loop: `get_flatsome_repeater_start($repeater)`, rồi `setup_postdata` + `wc_get_template_part('content','product')` cho mỗi ID, rồi `wp_reset_postdata()` + `get_flatsome_repeater_end($repeater)`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS.
- [ ] **Step 5:** Commit `feat: product query + Flatsome product loop`.

---

### Task 5 (B4): Elements\Registry + `fbm_products`

**Files:**
- Create: `src/Elements/Registry.php`, `elements/products/meta.php`, `elements/products/builder.php`, `elements/products/render.php`
- Test: `tests/ProductsElementTest.php`

**Interfaces:**
- Consumes: `Query::ids`, `Loop::render`, `States`, `Placeholder::empty`.
- Produces:
  - `FBM\Elements\Registry`:
    - `all(): array<string tag, array{tag:string,name:string,category:string,dir:string,container:bool,css:string|null,js:string|null}>`.
    - `boot()`.
    - `render( string $tag, $atts, string $content = '' ): string`.
  - Định dạng file:
    - `meta.php` trả `['tag','name','category','container'=>bool,'css'=>?file,'js'=>?file]`.
    - `builder.php` trả mảng args cho `add_ux_builder_shortcode`; chỉ include trong `ux_builder_setup`, có biến `$commons` trỏ tới `get_template_directory().'/inc/builder/shortcodes/commons/'`.
    - `render.php` nhận `$atts` (đã `shortcode_atts` với `$defaults` khai báo trong chính file), `$content`, `$tag`, và echo HTML.

- [ ] **Step 1: Test thất bại**:
  - `test_registry_finds_products`.
  - `test_products_renders_slider_markup`: `[fbm_products source="recent" type="slider" products="4"]` chứa `slider` và 4 `product-small`.
  - `test_products_empty_guest_blank`, `test_products_empty_editor_note`: chứa `fbm-empty`.
  - `test_products_off_guest_blank`.
- [ ] **Step 2:** `sh bin/test.sh ProductsElement`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement Registry và element.
  - Registry đăng ký shortcode ở `init`. Ở `ux_builder_setup` (priority 20) gọi `add_ux_builder_shortcode` với `thumbnail => FBM_URL.'assets/img/element.svg'`.
  - `builder.php` của products có các nhóm option:
    - `source` (select theo Task 4).
    - `post_options` = `require $commons.'repeater-posts.php'` với `$repeater_post_type='product'`, `$repeater_post_cat='product_cat'`, `$repeater_posts='products'`.
    - Lọc: `price_min`, `price_max`, `in_stock`, `exclude`.
    - `layout_options` (`repeater-options.php`, `$repeater_type='slider'`, `$repeater_columns='4'`).
    - `layout_options_slider` (`repeater-slider.php`).
    - `advanced_options`.
  - Tiêu đề section dùng element `[title]` có sẵn, không làm option riêng.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Sau đó kiểm bằng Playwright trong UX Builder:
  1. Element "Products" (nhóm Shop) có trong Add Elements.
  2. Đổi cột 4→3 thì canvas cập nhật.
  3. Trang ngoài site ở 375/768/1280px không tràn ngang.
- [ ] **Step 5:** Commit `feat: fbm_products element`.
- [ ] **Step 6: DỪNG.** Báo người dùng: zip (`npm run build && sh bin/zip.sh`), ảnh chụp, kết quả test. Chờ phản hồi trước Task 6.

---

### Task 6 (B5): Product Attributes + nút 3D vào cấu trúc mới

**Files:**
- Create: `elements/product-attributes/{meta.php,builder.php,render.php}` (port từ `ProductAttributes.php`, giữ nguyên markup và empty state)
- Modify: `src/Handdn/Product3DButton.php`:
  - Boot chỉ khi `States::get('handdn_3d_button') !== 'off'`.
  - Thêm CSS focus vào `assets/handdn/3d-button.css`, chỉ nạp khi có link: `a[data-handdn-3d]:focus-visible { outline: 2px solid var(--primary-color); outline-offset: 2px }`.
- Test: `tests/HanddnTest.php`

**Interfaces:**
- Consumes: Registry, States.
- Produces: tag `handdn_product_attributes`; trạng thái ảo `handdn_3d_button` (chỉ `on`/`off`; `set(..., 'hidden')` trả `fbm_bad_state`).

- [ ] **Step 1: Test thất bại.** Port 8 ca đã chạy tay ngày 2026-10-17 thành test:
  - `id`, thuộc tính ẩn, giá trị rỗng/trim, escape `<script>`, sản phẩm nháp, ID không phải sản phẩm, global `$product` là chuỗi, class lạ.
  - Thêm 3 ca cho nút 3D: link `javascript:` thì không có nút; `handdn_3d_button=off` thì không có nút; `set('handdn_3d_button','hidden')` bị từ chối.
- [ ] **Step 2:** `sh bin/test.sh Handdn`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement theo Interfaces.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS.
- [ ] **Step 5:** Commit `refactor: product attributes + 3D button into v2 structure`.

---

### Task 7 (B6): `fbm_product_compare`

**Files:**
- Create: `elements/product-compare/{meta.php,builder.php,render.php,compare.css}`
- Test: `tests/CompareTest.php`

**Interfaces:**
- Produces: shortcode `fbm_product_compare` với các att:
  - `ids` (2–4, CSV), `source` (`ids` | `current_upsell`).
  - `rows` (CSV, mặc định `image,price,rating,stock,attributes,add_to_cart`).
  - `attributes` (CSV tên thuộc tính; trống = thuộc tính hiển thị chung của các sản phẩm).
  - `only_diff` (`true`/`false`), `title`, `class`, `visibility`.

- [ ] **Step 1: Test thất bại**:
  - `test_compare_needs_two_products`: 1 ID hợp lệ thì `''` cho khách.
  - `test_compare_table_semantics`: có `<caption>`, `th scope="col"` × số sản phẩm, `th scope="row"`.
  - `test_compare_missing_cell_dash`: ô thiếu là `—`.
  - `test_compare_only_diff_drops_equal_rows`.
  - `test_compare_add_to_cart_markup`: chứa `add_to_cart_button` và `ajax_add_to_cart` cho simple.
  - `test_compare_css_enqueued_only_when_used`.
- [ ] **Step 2:** `sh bin/test.sh Compare`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement.
  - Dữ liệu thuộc tính dùng lại logic `items()` của product-attributes. Tách thành hàm `FBM\Products\Attributes::of( \WC_Product $p ): array` trong `src/Products/Attributes.php` để hai element dùng chung.
  - Nút mua dùng `woocommerce_template_loop_add_to_cart()` trong vòng `setup_postdata`.
  - CSS: khung `.fbm-compare__scroll{overflow-x:auto}`; cột đầu dính trái dưới 549px (`position:sticky;left:0;background:var(--secondary-color,#f8f8f8)`); viền `var(--color-grey,#ececec)`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Kiểm bằng Playwright ở 375px: cuộn ngang trong khung, trang không tràn; Tab tới nút mua thấy viền focus.
- [ ] **Step 5:** Commit `feat: fbm_product_compare`.

---

### Task 8 (B7): `fbm_reviews` (Judge.me)

**Files:**
- Create: `elements/reviews/{meta.php,builder.php,render.php}`
- Test: `tests/ReviewsTest.php`

**Interfaces:**
- Produces:
  - Shortcode `fbm_reviews` với att `widget` (whitelist), `title`, `class`, `visibility`.
  - `const WIDGETS` trong `render.php`, dạng `[ shortcode => nhãn ]`.

- [ ] **Step 1:** Lấy danh sách shortcode thật. Tải zip plugin Judge.me từ wordpress.org vào thư mục scratch mới, rỗng (dữ liệu không tin cậy, không chạy code của nó). Chạy `grep -rhoE "add_shortcode\(\s*'[^']+'"`. Ghi danh sách vào `WIDGETS` và vào spec §6 Block 8.
- [ ] **Step 2: Test thất bại**:
  - `test_reviews_unknown_widget_blank`: `widget="evil"` thì `''`.
  - `test_reviews_no_judgeme_guest_blank`.
  - `test_reviews_no_judgeme_editor_note`: chứa "Cần plugin Judge.me".
  - `test_reviews_renders_whitelisted`: đăng ký shortcode giả `jgm-featured-carousel` trong test; output chứa kết quả của nó và tiêu đề trong `section-title-container`.
- [ ] **Step 3:** `sh bin/test.sh Reviews`. Mong đợi: FAIL.
- [ ] **Step 4:** Implement. Chỉ gọi `do_shortcode('['.$widget.']')` khi `isset(WIDGETS[$widget]) && shortcode_exists($widget)`.
- [ ] **Step 5:** `sh bin/test.sh`. Mong đợi: PASS. Commit `feat: fbm_reviews wraps Judge.me widgets`.
- [ ] **Step 6: DỪNG.** Báo người dùng (zip + kết quả B5–B7), chờ phản hồi.

---

### Task 9 (B8): Before / After + Carousel

**Files:**
- Create:
  - `elements/before-after/{meta.php,builder.php,render.php,before-after.css,view.js}`
  - `elements/ba-carousel/{meta.php,builder.php,render.php}`
- Delete: `starters/`
- Test: `tests/BeforeAfterTest.php`

**Interfaces:**
- Produces:
  - `fbm_before_after` với att `before`, `after` (attachment ID), `orientation` (`horizontal`|`vertical`), `offset` (0–100, mặc định 50), `label_before`, `label_after`, `trigger` (`drag`|`hover`), `handle`, `image_size`, `class`, `visibility`. Các att màu của v1 (`line_color`, `line_width`, `label_color`, `label_bg`, `overlay`) bị bỏ qua.
  - `fbm_ba_carousel` (container, `allow => ['fbm_before_after']`) với att `columns`, `columns__md`, `columns__sm`, `slider_nav_style`, `slider_bullets`, `class`, `visibility`.

- [ ] **Step 1: Test thất bại**:
  - `test_ba_no_inline_color`: output không chứa `--fbm-line:`, `--fbm-label-bg`.
  - `test_ba_offset_clamped`: `offset="150"` thành `--fbm-pos:100%`.
  - `test_ba_v1_color_atts_ignored_no_warning`.
  - `test_ba_empty_images_editor_note`.
  - `test_ba_handle_is_button_with_label`: tay nắm là `<button type="button" aria-label=…>` có class `flickity-button-icon`; nhãn dùng `badge-inner`.
  - `test_carousel_wraps_children_in_flatsome_slider`: chứa `slider` và mỗi con trong `col`.
  - `test_ba_css_rules`: đọc `before-after.css`, không khớp `/#[0-9a-f]{3,6}\b|rgba?\(|!important|font-family/i` (trừ giá trị dự phòng trong `var(..., #xxx)`).
- [ ] **Step 2:** `sh bin/test.sh BeforeAfter`. Mong đợi: FAIL.
- [ ] **Step 3:** Port từ `starters/before-after*`.
  - CSS theo mẫu ở design §02 lớp 3.
  - `view.js` giữ cơ chế kéo bằng `--fbm-pos`; phím ←/→ đổi 5%; tôn trọng `prefers-reduced-motion` cho `auto`.
  - Carousel dựng bằng `get_flatsome_repeater_start(['type'=>'slider', …])`, tách các shortcode con bằng `get_shortcode_regex(['fbm_before_after'])`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Kiểm bằng Playwright: kéo bằng chuột, dùng phím mũi tên, mobile 375px.
- [ ] **Step 5:** Commit `feat: before/after + carousel on v2 CSS rules`.

---

### Task 10 (B9): 10 block mẫu qua UX Builder › Import

**Files:**
- Create: `src/Templates.php`, `templates/{showcase,category,hero,benefits,attributes,compare,trust,reviews,faq,cross-sell}/{meta.php,content.txt,thumb.svg}`
- Test: `tests/TemplatesTest.php`

**Interfaces:**
- Produces:
  - `FBM\Templates::all(): array<string slug, array{name:string,content:string,thumb:string}>`.
  - `boot()` gọi `ux_builder_add_template( 'fbm_'.$slug, [ 'post_types'=>['page','blocks'], 'category'=>'HANDDN', 'name'=>$name, 'thumbnail'=>$thumb_url, 'content'=>$content ] )` ở `ux_builder_setup`.

- [ ] **Step 1: Test thất bại**:
  - `test_ten_templates`: `count(all()) === 10`.
  - `test_templates_only_known_tags`: mọi tag trong `content.txt` (regex `\[([a-z0-9_-]+)`) thuộc tag Flatsome (danh sách 85 tag của `inc/builder/shortcodes`, cộng `row_inner`, `col_inner`, `tab`) hoặc Registry. Không có `fbm_product_attributes`.
  - `test_templates_render_without_warning`: `do_shortcode(content)` với `WP_DEBUG` không phát notice (bắt bằng `set_error_handler`).
  - `test_faq_has_no_faqpage_schema`.
- [ ] **Step 2:** `sh bin/test.sh Templates`. Mong đợi: FAIL.
- [ ] **Step 3:** Viết 10 `content.txt` theo spec §6 (nội dung chữ là gợi ý, tiếng Việt, ngành dây da). Class HANDDN gắn qua option `class` của Flatsome: `grid-banner`, `icon-box_bg`, `featured-box_button featured-box_center`, `h1-title`/`h2-title`. Thumbnail SVG đơn sắc dùng `currentColor`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Kiểm bằng Playwright: UX Builder › Add Content › Import có nhóm HANDDN với 10 mẫu; chèn "Cross-sell" vào trang thì canvas render.
- [ ] **Step 5:** Commit `feat: 10 HANDDN templates in UX Builder import`.
- [ ] **Step 6: DỪNG.** Báo người dùng (zip + ảnh 10 mẫu), chờ phản hồi.

---

### Task 11 (B10): Admin 3 tab + REST

**Files:**
- Create: `src/Admin/Page.php`, `src/Admin/Rest.php`, `assets/admin-src/{index.js,App.js,ItemList.js,api.js,style.scss}`
- Test: `tests/RestTest.php`

**Interfaces:**
- Consumes: `States`, `Catalog`, `BlockStates`, `UsageIndex`, `Registry`.
- Produces REST `fbm/v2`:
  - `GET /items?type=ux_block|plugin|flatsome` trả `{items: [{id, name, sub, state, locked, usage}], catalog_empty: bool, usage_built: bool}`.
    - `ux_block` cần `edit_pages`; còn lại cần `manage_options`.
  - `POST /state` với body `{type, id, state}` trả `{id, state}`.
  - `POST /usage/rebuild` với body `{offset}` trả `{next, total}` (`edit_pages`).

- [ ] **Step 1: Test thất bại** (`rest_do_request`):
  - `test_rest_rejects_locked`: `section` thì 400.
  - `test_rest_rejects_unknown_tag`: 400.
  - `test_rest_rejects_bad_state`: 400.
  - `test_rest_rejects_page_id_as_block`: 400.
  - `test_rest_editor_cannot_change_element`: `fbm_as_user('editor')` thì 403.
  - `test_rest_editor_can_change_block`: 200.
  - `test_rest_list_flatsome_catalog_empty_flag`.
  - `test_rest_no_code_fields`: gửi `{type:'plugin', id:'fbm_products', state:'on', code:'<?php'}` thì 200, và không option/meta nào chứa `<?php`.
- [ ] **Step 2:** `sh bin/test.sh Rest`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement REST và UI.
  - REST dùng `validate_callback`/`sanitize_callback` cho cả 3 tham số.
  - UI theo mockup bản 4:
    - Tab bằng `TabPanel`; mỗi dòng có tên, phụ đề (tag hoặc `[block id="slug"]`), cột "N nơi" và nhóm 3 nút `role="radio"`.
    - Element khoá thì nút Ẩn/Tắt `disabled` kèm `title`.
    - Bấm Tắt khi usage > 0 thì hiện hộp xác nhận trong trang (Huỷ / Ẩn thay vì Tắt / Vẫn Tắt), không dùng `confirm()`.
    - Có ô tìm kiếm.
    - Tab Flatsome khi `catalog_empty` hiện "Mở UX Builder một lần để plugin đọc danh sách element" kèm link.
    - Khi `!usage_built`, gọi rebuild theo lô và hiện tiến độ.
  - Menu `Block Manager` dưới `Flatsome` (giống v1).
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. `npm run build` OK. Kiểm bằng Playwright:
  1. 3 tab đổi được bằng chuột và phím ←/→.
  2. Đổi trạng thái thì tải lại vẫn giữ.
  3. Hộp xác nhận hiện khi Tắt thứ đang dùng.
  4. Màn 375px không tràn ngang.
- [ ] **Step 5:** Commit `feat: admin 3 list tabs + REST v2`.

---

### Task 12 (B11): Nâng cấp từ v1

**Files:**
- Create: `src/Upgrade.php`, `elements/products/alias-v1.php` (đăng ký `fbm_product_slider`)
- Modify: `uninstall.php`
- Test: `tests/UpgradeTest.php`

**Interfaces:**
- Produces:
  - `FBM\Upgrade::maybe_run(): void`, gọi trong `Plugin::start()` khi option `fbm_version` < `2.0.0`.
  - `FBM\Upgrade::map_v1_slider_atts( array $v1 ): array`.

- [ ] **Step 1: Test thất bại**:
  - `test_v1_disabled_block_becomes_off`: meta `_fbm_enabled='0'` thành `_fbm_state='off'`.
  - `test_v1_schedule_meta_removed`: `_fbm_show_from`, `_fbm_show_until`, cron `fbm_visibility_boundary`, `fbm_cleanup_sandbox` bị xoá.
  - `test_v1_elements_removed`: post `fbm_element`, bảng `fbm_element_versions`, option `fbm_settings`, `fbm_elements_registry`, thư mục `uploads/fbm-elements`.
  - `test_v1_slider_alias_maps_atts`: `map_v1_slider_atts(['source'=>'best_selling','limit'=>'9','cat'=>'150,18','per_view'=>'5','autoplay'=>'true','autoplay_ms'=>'2000','radius'=>'3','title'=>'abc'])` trả `['source'=>'best_selling','products'=>'9','cat'=>'150,18','columns'=>'5','auto_slide'=>'2000','type'=>'slider']`.
    - `title` hiển thị qua `[title]` trước slider.
    - `radius` bị bỏ.
    - Bảng map đầy đủ: `limit→products`, `per_view→columns`, `per_view_md→columns__md`, `per_view_sm→columns__sm`, `dots→slider_bullets`, `loop→infinitive`, `autoplay`+`autoplay_ms→auto_slide` (ms hoặc `false`), `orderby popularity→popularity`.
    - Giữ nguyên: `source`, `ids`, `cat`, `tags`, `offset`, `order`, `exclude`, `price_min`, `price_max`, `in_stock`.
    - Bỏ: kiểu thẻ, màu, bo góc, tỷ lệ ảnh, `show_*`.
  - `test_v1_unknown_tag_renders_empty`: `[fbm_hello]` cho khách thì `''`, editor thấy `Placeholder::off('fbm_hello (v1)')`.
  - `test_upgrade_runs_once`.
- [ ] **Step 2:** `sh bin/test.sh Upgrade`. Mong đợi: FAIL.
- [ ] **Step 3:** Implement.
  - Trước khi xoá, ghi danh sách tag v1 đang dùng (`UsageIndex::element_counts()` ∩ tag v1) vào option `fbm_v1_report`. Admin hiện 1 notice có nút đóng.
  - `uninstall.php`: xoá `fbm_element_states`, `fbm_catalog`, `fbm_version`, `fbm_v1_report`, meta `_fbm_state`, bảng `fbm_block_usage`, transient `fbm_element_usage`.
- [ ] **Step 4:** `sh bin/test.sh`. Mong đợi: PASS. Trên DDEV:
  1. `ddev snapshot restore before-v2`.
  2. Cài lại zip v1, rồi upload zip v2 bằng "Replace current with uploaded".
  3. Mong đợi: block #78 hiện slider `fbm_products`, có notice báo `fbm_product_slider` đã đổi.
- [ ] **Step 5:** Commit `feat: upgrade path from v1`.

---

### Task 13 (B12): QA theo spec §7 + zip

**Files:**
- Modify: `readme.txt` (mô tả v2, changelog 2.0.0), `docs/superpowers/specs/...` (ghi kết quả)
- Create: `docs/qa/2026-xx-xx-v2-qa.md`

- [ ] **Step 1:** Chạy `fbm_fixtures_up()` trên DDEV, đã có snapshot `before-v2`. Thêm 2 sản phẩm biến thể và liên kết cross-sell/upsell cho 3 sản phẩm.
- [ ] **Step 2:** Chạy `sh bin/test.sh` toàn bộ và `php -l` mọi file. Mong đợi: 0 fail, 0 lỗi cú pháp.
- [ ] **Step 3:** Đi qua checklist §7.1 bằng Playwright ở 375/768/1280px. Ghi từng dòng "đã kiểm thật" hay "chỉ review code" vào `docs/qa/…`. Có so ảnh shop và trang sản phẩm trước/sau khi bật plugin.
- [ ] **Step 4:** `npm run build && sh bin/zip.sh`, rồi copy sang `~/Documents/project-wp/zips/`. Kiểm `unzip -l` không có `tests/`, `docs/`, `starters/`, `src/Modules/`.
- [ ] **Step 5:** `fbm_fixtures_down()`. Commit `chore: v2.0.0 QA + package`.
