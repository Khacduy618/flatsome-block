# Flatsome Block Manager — thiết kế kỹ thuật

Tài liệu cho dev bảo trì plugin. Hướng dẫn cho người dùng nằm trong tab **Hướng dẫn** của plugin.

## Mục tiêu

- Quản lý UX Blocks của Flatsome trong một màn hình: CRUD, nhân bản, danh mục, thao tác hàng loạt.
- Bật/tắt và hẹn giờ block (Flatsome không có: `[block]` render cả block Nháp lẫn block trong Thùng rác).
- Biết block đang được dùng ở đâu; chặn xoá / đổi slug block đang dùng.
- Xuất/nhập block + element giữa các site.
- Viết UX Builder element **và** Gutenberg block bằng code ngay trong admin, có kiểm tra cú pháp, chạy thử cô lập, phiên bản, cầu dao chống sập site.
- REST API đầy đủ cho UI và hệ thống ngoài.

Phạm vi: dùng nội bộ, người viết code là Administrator tin cậy. Mục tiêu an toàn là **chống lỗi do code**, không phải sandbox chống người có ý xấu.

## Cấu trúc

```
flatsome-block-manager.php   header + khởi động sau after_setup_theme (Flatsome đăng ký CPT blocks khi nạp theme)
uninstall.php                chỉ xoá dữ liệu của plugin khi bật "Xoá dữ liệu khi gỡ"; luôn giữ block
src/
  Plugin.php                 nạp module -> register() -> boot(); shim react-jsx-runtime cho WP < 6.6
  Core/                      Container, ModuleInterface, Requirements, Installer (dbDelta), Settings, Capabilities, RestController
  Modules/
    Blocks/                  BlockRepository · BlockService · BlocksController · Visibility
    Usage/                   UsageScanner · UsageRepository · UsageController
    Elements/                Schema · Compiler · Renderer · Registry · ElementRepository · VersionRepository
                             ElementService · ElementsController · Starters
      Adapters/              UxBuilderAdapter · GutenbergAdapter
      Sandbox/               Linter · SandboxRunner · SandboxEndpoint · CircuitBreaker
    Transfer/                TransferService · TransferController
    Settings/                REST /settings
    Admin/                   menu "Block Manager" + nạp build/admin.js
starters/<key>/              mẫu element: meta.json, schema.json, template.php.tpl, style.css, script.js
assets/src/admin/            app React (wp-scripts): design-system/, screens/, help/
assets/src/gutenberg/        editor script tự dựng block từ schema
assets/runtime/              fbm-runtime.js (fbm.ready), react-jsx-runtime.js (shim)
build/                       output của npm run build — phải có trong zip
```

### Quy ước module

1. Repository (dữ liệu) → Service (luật) → Controller (REST). Controller không gọi Repository.
2. Module không đọc Repository của module khác; nói chuyện qua filter/action:
   - `fbm/block_usages ( null, $block_id )`, `fbm/block_usage_counts ( null, $ids )`, `fbm/used_block_ids ( null )` — Usage cung cấp.
   - `fbm/block_saved ( $id )`, `fbm/block_deleted ( $id )` — Blocks/Transfer phát.
   - `fbm/purge_page_cache` — móc plugin cache khác.
   - `fbm/uxbuilder_args`, `fbm/gutenberg_args` — chỉnh tham số đăng ký element.
3. Module tắt trong Cài đặt thì không đăng ký hook nào. `blocks`, `settings`, `admin` luôn bật.
4. PHP không render HTML cho admin; mọi màn hình là React gọi `/wp-json/fbm/v1`.

## Dữ liệu

| Dữ liệu | Nơi lưu |
|---|---|
| Block | CPT `blocks` + taxonomy `block_categories` của Flatsome (dùng chung) |
| Bật/tắt, hẹn giờ | post meta `_fbm_enabled` (`'0'` = tắt), `_fbm_show_from`, `_fbm_show_until` (unix UTC) |
| Nơi dùng | bảng `{prefix}fbm_block_usage` (block_id, source_type post/postmeta/theme_mod/widget, source_id, source_key) |
| Element | CPT ẩn `fbm_element`; meta `_fbm_tag`, `_fbm_category`, `_fbm_targets`, `_fbm_draft`, `_fbm_check`, `_fbm_live_version`, `_fbm_state`, `_fbm_trip`, `_fbm_last_error`, `_fbm_sample` |
| Phiên bản element | bảng `{prefix}fbm_element_versions` (payload JSON bất biến + hash + báo cáo chạy thử) |
| File chạy | `uploads/fbm-elements/{tag}-v{n}-{hash8}.php|css|js` (+ `.htaccess` chặn gọi trực tiếp .php) |
| Danh sách element Live | option `fbm_elements_registry` (autoload) — không query DB mỗi request |
| Cài đặt | option `fbm_settings`; hằng `FBM_SAFE_MODE` thắng cài đặt |

Lưu ý: `update_post_meta()` tự `stripslashes`, nên `ElementRepository::set_meta()` luôn `wp_slash()` trước (nếu không code có `\n`, regex sẽ hỏng).

## Bật/tắt block

`Visibility` móc `pre_do_shortcode_tag`: với tag `block`, giải ID bằng `flatsome_get_block_id()` (có WPML/Polylang), nếu không hiển thị thì trả `''` (khách) hoặc khung nét đứt (người có `edit_pages`). Không sửa file theme; gỡ plugin là block hiện lại. Khi đổi trạng thái: xoá cache trang (WP Rocket, LiteSpeed, W3TC, WP Super Cache) và đặt WP-Cron đúng mốc hẹn giờ để xoá cache lần nữa.

## Nơi dùng

`UsageScanner` quét: `post_content` mọi post type; meta `_product_block`, `_menu_item_block` (ID trực tiếp), `_top_content`, `_bottom_content` (text); theme mods (khoá chứa "block" + text có `[block]`); widget `block_widget`, text, custom_html, block. Cập nhật theo `save_post`, `*_post_meta`, `updated_option`, `fbm/block_saved`. Quét toàn bộ theo lô 100 bài qua `POST /usage/rebuild`.

Chỉ cache kết quả giải slug khi **tìm thấy** (block có thể được tạo sau trong cùng request khi nhập).

## Element

### Một định nghĩa, hai editor

```
schema.json ──Schema::parse──┬─ UxBuilderAdapter → add_ux_builder_shortcode()
                             └─ GutenbergAdapter → register_block_type('fbm/<tag>') + window.fbmGutenberg
template.php ─Compiler───────── file PHP trả closure ─ Renderer (dùng chung shortcode + render_callback)
```

Những điều đọc được từ mã nguồn Flatsome (`inc/builder/core/server/src/Collections/Elements.php`) và đã áp dụng:

- Có khoá `type` (bất kỳ giá trị) là bị coi là container → adapter chỉ đặt `type` khi `container`.
- Không có `template` → UX Builder render bằng server (gọi shortcode) → element thường không cần template Angular.
- Container cần template canvas có `<content></content>` → `builder_template` trong schema, hoặc khung mặc định.
- Option `$content` chỉ hoạt động với container (element thường sinh shortcode không có thẻ đóng) → Linter báo lỗi.
- Tên option phải chữ thường (`shortcode_atts` đổi key thành chữ thường).
- Option responsive lưu thêm `key__sm`, `key__md` → `Schema::defaults()` thêm default rỗng để `shortcode_atts` không bỏ mất.
- `"@commons/<tên>"` → `require` file `inc/builder/shortcodes/commons/<tên>.php` của theme.

- `presets` theo đúng chuẩn theme (`{ name, content }`). UX Builder parse preset bằng `StringToArray`: tag chưa đăng ký **không** gây lỗi PHP mà bị gói vào element `text` (chèn shortcode dạng chữ vào trang). Vì vậy adapter bỏ preset có tag con chưa đăng ký cho UX Builder (Gutenberg: bỏ variation có block con chưa đăng ký); Linter báo lỗi preset không bắt đầu bằng `[tag]` và cảnh báo tag con ngoài `allow`. Đã kiểm chứng qua `admin-ajax.php?action=ux_builder_parse_presets`.

Tên block Gutenberg: bỏ tiền tố `fbm_`, `_` → `-` (`fbm_before_after` → `fbm/before-after`).

### File biên dịch

```php
<?php return static function ( array $__fbm_atts, $content, $tag, $fbm_id ) { extract( $__fbm_atts, EXTR_SKIP ); $atts = $__fbm_atts; ?>TEMPLATE
<?php };
```

Toàn bộ phần bọc nằm trên dòng 1 → số dòng lỗi khớp số dòng template. Nếu template kết thúc trong chế độ PHP, compiler thêm `?>`.

### Hai cổng kiểm tra

1. **Linter** (không chạy code): `token_get_all( $code, TOKEN_PARSE )` bắt lỗi cú pháp kèm dòng, không cần `exec`. Cấm `exit/die`, hàm/class/interface/trait/enum có tên (lỗi "Cannot redeclare" ở lần render thứ 2), `namespace`, `declare`, `__halt_compiler`. Cảnh báo `eval`, hàm shell, hàm ảnh hưởng cả request. JS kiểm tra ở trình duyệt bằng `new Function()` (chỉ parse).
2. **Sandbox**: `SandboxRunner` ghi file tạm, lưu job vào transient với token 32 ký tự dùng 1 lần, `wp_remote_post` tới `/fbm/v1/sandbox/run`. `SandboxEndpoint` định nghĩa `WP_SANDBOX_SCRAPING` (WP bỏ qua trang "critical error" và recovery mode), giới hạn 10 giây và ~64 MB, render 2 lần, gom warning của đúng file template. Lỗi fatal → hàm shutdown in JSON. Không gọi được loopback → `unavailable`: vẫn publish được nếu xác nhận (`force_untested`).

Publish chỉ khi hash bản nháp = hash lần kiểm tra, lint đạt, chạy thử `passed`.

### Cầu dao

`Renderer` bọc mỗi lần render bằng `CircuitBreaker::enter/leave` + `try/catch Throwable` (lỗi bắt được: ghi `_fbm_last_error`, khách thấy rỗng, admin thấy khung lỗi). Lỗi không bắt được (hết bộ nhớ, quá giờ): hàm shutdown thấy stack đang render element → `ElementService::trip()` đặt state `tripped` + lưu thông báo, dòng, URL; dựng lại registry. Giữ 256 KB bộ nhớ dự phòng, giải phóng khi shutdown. `FBM_SAFE_MODE` tắt mọi element.

Element tắt/bị ngắt vẫn được đăng ký (shortcode trả rỗng; UX Builder ghi "(đang tắt)"; Gutenberg hiện thông báo) để nội dung cũ không lộ `[tag]` dạng chữ.

### JS chạy trong editor

`assets/runtime/fbm-runtime.js` cung cấp `fbm.ready( selector, init )`: chạy `init` một lần cho mỗi phần tử khớp, kể cả phần tử chèn sau (MutationObserver). Cần cho canvas UX Builder và preview Gutenberg, nơi element được chèn sau khi trang đã tải.

## REST `/wp-json/fbm/v1`

| Quyền | Route |
|---|---|
| `edit_pages` | `blocks`, `blocks/{id}`, `blocks/{id}/duplicate|restore|usage`, `blocks/bulk`, `block-categories`, `usage/status|rebuild`, `transfer/export|import` |
| `manage_options` + `unfiltered_html`, không `DISALLOW_FILE_EDIT` | `elements…`, phần element của `transfer` |
| `manage_options` | `settings` |
| token 1 lần | `sandbox/run` |

Mã lỗi đáng chú ý: `409 fbm_block_in_use`, `409 fbm_slug_in_use` (gửi lại với `confirm_slug_change`), `409 fbm_publish_blocked`.

## Định dạng gói xuất

```json
{ "format": "fbm-package", "format_version": 1, "plugin_version": "1.0.0", "exported_at": "…", "source": "https://…",
  "blocks":   [ { "slug", "title", "status", "content", "menu_order", "categories": [{ "slug", "name", "parent" }], "visibility": { "enabled", "from", "until" } } ],
  "elements": [ { "tag", "name", "category", "targets", "sample", "payload": { "schema", "template", "css", "js" } } ] }
```

Nhập: element trước (để biết tag đổi tên), tính slug mới cho mọi block, sửa `[block id]` và `[tag]` trong nội dung, rồi ghi; cuối cùng phát `fbm/block_saved` cho mọi block để quét lại nơi dùng. Element nhập vào luôn ở bản nháp.

Giới hạn đã biết: ảnh không đi kèm (block giữ ID ảnh của site cũ).

## Build và đóng gói

```bash
npm install
npm run build          # build/admin.js, build/style-admin.css, build/gutenberg.js (+ .asset.php)
sh bin/zip.sh          # dist/flatsome-block-manager.zip (bỏ node_modules, dist, file ẩn)
```

`@wordpress/*` được map sang biến global của WP khi build (không cần cài). Không dùng `@wordpress/icons` (không được map, sẽ bị đóng gói vào file).

## Kiểm thử đã chạy (v1.0.0)

Trên WordPress 7.1 + SQLite + Flatsome 3.20.11, PHP 8.4, `php -S` với 4 worker (loopback cần nhiều worker):

- 82 assertion qua REST nội bộ: CRUD, chặn xoá/đổi slug, bật/tắt/hẹn giờ (khách vs admin), nhân bản, bulk, lọc, nơi dùng (trang, theme mod, block lồng), lint (cú pháp, exit, hàm có tên, class, schema chữ hoa, `$content`, `@commons`, `builder_template`), sandbox (hàm không tồn tại, hết bộ nhớ, vòng lặp vô hạn, warning), publish/render/escape, Gutenberg (đăng ký, attributes, InnerBlocks, variations, `fbm/ux-block`), UX Builder args, cầu dao trên request HTTP thật, xuất/nhập (đổi slug + tag, dry run, gói sai), safe mode, phân quyền.
- Trình duyệt: danh sách block, công tắc + Hoàn tác, nút "?", tab Hướng dẫn, editor element (lỗi tô đỏ đúng dòng, khoá Publish), chèn block trong Gutenberg, kéo element vào UX Builder và đổi option.

Chưa kiểm thử: WordPress 6.4–6.5 thật (shim `react-jsx-runtime` chỉ được kiểm tra cú pháp), MySQL thật, multisite, hosting chặn loopback thật.
