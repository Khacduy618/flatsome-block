import { useState } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { GUIDE_ORDER, GUIDE_TITLES } from '../help/guide-titles';
import { OPTION_SNIPPETS } from '../help/snippets';
import { copyText, cfg } from '../api';
import { useNotice } from '../design-system';

function Code( { children } ) {
	return <pre className="fbm-pre">{ children }</pre>;
}

function CopyButton( { text } ) {
	const notice = useNotice();
	return (
		<Button
			variant="tertiary"
			size="small"
			onClick={ async () => notice( ( await copyText( text ) ) ? 'Đã sao chép.' : 'Không sao chép được, hãy chọn và copy thủ công.' ) }
		>
			Sao chép
		</Button>
	);
}

const CONTENT = {
	'quick-start': (
		<>
			<p>Plugin có 4 phần, mỗi phần là một tab ở trên cùng:</p>
			<ul>
				<li><b>Blocks</b>: quản lý UX Blocks của Flatsome (thêm, sửa, nhân bản, bật/tắt, hẹn giờ, xem nơi dùng).</li>
				<li><b>Elements</b>: tự viết element mới cho UX Builder và Gutenberg bằng PHP + CSS + JS.</li>
				<li><b>Xuất / Nhập</b>: chuyển block và element sang site khác bằng file JSON.</li>
				<li><b>Cài đặt</b>: bật/tắt module, safe mode, thông tin REST API.</li>
			</ul>
			<h3>Element đầu tiên trong 5 bước</h3>
			<ol className="fbm-steps">
				<li>Elements › <b>Tạo element</b> › chọn mẫu: "Hộp chào", "Before / After", "Before / After Carousel" hoặc "Product Slider Pro" (cần WooCommerce).</li>
				<li>Đặt tên và tag shortcode, ví dụ <code>hero_countdown</code>. Tag không đổi được sau khi tạo.</li>
				<li>Khai báo option trong <b>schema.json</b>. Mỗi option là một ô trong panel UX Builder và Gutenberg.</li>
				<li>Viết <b>template.php</b>. Mỗi option thành một biến cùng tên: <code>$title</code>, <code>$color</code>…</li>
				<li>Bấm <b>Chạy thử</b>, xem preview, rồi <b>Publish</b>. Element xuất hiện trong UX Builder và Gutenberg.</li>
			</ol>
		</>
	),
	blocks: (
		<>
			<p>
				Plugin đọc và ghi thẳng vào UX Blocks của Flatsome (post type <code>blocks</code>, danh mục <code>block_categories</code>).
				Không có kho dữ liệu riêng: block tạo ở đây cũng thấy trong menu UX Blocks gốc và ngược lại. Gỡ plugin thì block vẫn còn.
			</p>
			<h3>Slug</h3>
			<p>
				Shortcode <code>[block id="slug"]</code> tìm block theo slug (hoặc theo ID số). Đổi slug làm các shortcode cũ không tìm thấy block,
				nên khi block đang được dùng, plugin dừng lại và liệt kê các nơi bị ảnh hưởng trước khi cho lưu.
			</p>
			<h3>Nội dung</h3>
			<p>Nội dung là shortcode do UX Builder tạo. Bấm <b>UX Builder</b> để sửa trực quan; ô nội dung trong plugin dùng để sửa nhanh.</p>
			<h3>Xoá</h3>
			<p>
				Chuyển vào thùng rác một block đang dùng sẽ bị hỏi lại. Lưu ý: Flatsome <b>vẫn hiển thị</b> block trong thùng rác.
				Muốn block biến mất khỏi site, dùng công tắc Hiển thị.
			</p>
		</>
	),
	visibility: (
		<>
			<p>
				Flatsome render <code>[block]</code> mà không kiểm tra trạng thái bài viết, nên block Nháp hay trong Thùng rác vẫn hiện ngoài site.
				Công tắc <b>Hiển thị</b> của plugin là cách thật sự để ẩn block.
			</p>
			<table className="fbm-table fbm-table--plain">
				<thead>
					<tr><th>Trạng thái</th><th>Khách thấy</th><th>Admin thấy</th></tr>
				</thead>
				<tbody>
					<tr><td>Đang bật</td><td>Nội dung block</td><td>Nội dung block</td></tr>
					<tr><td>Đang tắt</td><td>Không có gì</td><td>Khung nét đứt "Block đang tắt"</td></tr>
					<tr><td>Hẹn giờ · chờ</td><td>Không có gì</td><td>Khung "Sẽ hiện lúc …"</td></tr>
					<tr><td>Hẹn giờ · đang chạy</td><td>Nội dung block</td><td>Nội dung block</td></tr>
					<tr><td>Hết hạn</td><td>Không có gì</td><td>Khung "Đã hết hạn lúc …"</td></tr>
				</tbody>
			</table>
			<p>
				Plugin xoá cache trang của WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache khi bạn bấm công tắc và đúng lúc hẹn giờ bắt đầu/kết thúc
				(qua WP-Cron, nên có thể trễ vài phút nếu site ít truy cập). Plugin cache khác: gọi <code>do_action( 'fbm/purge_page_cache' )</code> để móc vào.
			</p>
			<p>Gỡ plugin thì mọi block tự hiện lại; nội dung block không bị sửa.</p>
		</>
	),
	usage: (
		<>
			<p>Plugin ghi lại block đang được nhúng ở đâu, gồm:</p>
			<ul>
				<li>Nội dung mọi trang, bài viết, sản phẩm và block khác (block lồng block).</li>
				<li>Layout sản phẩm tuỳ chỉnh (<code>_product_block</code>), nội dung trên/dưới sản phẩm, block trong mega menu.</li>
				<li>Theme Options: header block, footer block, block trang 404, popup newsletter…</li>
				<li>Widget "Flatsome UX Blocks" và widget Text/HTML có chứa <code>[block]</code>.</li>
			</ul>
			<p>
				Lần đầu cài plugin, bấm <b>Quét lại</b> trong Cài đặt (hoặc thông báo ở danh sách block) để quét toàn bộ nội dung. Sau đó chỉ mục tự cập nhật
				khi bạn lưu nội dung. Shortcode viết trong file PHP của theme con không được quét.
			</p>
			<p>Bộ lọc <b>Chưa dùng</b> ở danh sách block giúp tìm block thừa để dọn.</p>
		</>
	),
	schema: (
		<>
			<p>
				Schema là JSON khai báo các ô tuỳ chỉnh (option) của element. Mỗi option thành một ô trong panel bên phải của UX Builder,
				một ô trong sidebar Gutenberg, và một biến cùng tên trong template.
			</p>
			<h3>Dạng gọn</h3>
			<Code>{ `{
  "title": { "type": "textfield", "heading": "Tiêu đề", "default": "Xin chào" },
  "bg": { "type": "colorpicker", "heading": "Màu nền", "default": "#446084" },
  "advanced_options": "@commons/advanced"
}` }</Code>
			<h3>Dạng đầy đủ</h3>
			<p>Dùng khi cần khai báo thêm thông tin cho element:</p>
			<Code>{ `{
  "info": "{{ title }}",          // nhãn hiện trên canvas UX Builder
  "presets": [ { "name": "Mặc định", "content": "[my_tag title=\\"A\\"]" } ],
  "type": "container",            // chỉ khi element chứa element con
  "allow": [ "my_child" ],        // element con được phép
  "options": { ... }
}` }</Code>
			<h3>Quy tắc</h3>
			<ul>
				<li>Tên option chỉ dùng <b>chữ thường, số, "_"</b>. WordPress tự đổi thuộc tính shortcode thành chữ thường, nên <code>bgColor</code> không bao giờ nhận được giá trị.</li>
				<li>Không đặt tên trùng biến có sẵn: <code>atts</code>, <code>content</code>, <code>tag</code>, <code>fbm_id</code>.</li>
				<li>Element thường <b>không khai báo</b> <code>"type"</code>. Flatsome coi mọi element có khoá <code>type</code> là container.</li>
				<li>Giá trị luôn là <b>chuỗi</b>. Checkbox trả về <code>"true"</code> hoặc <code>"false"</code>.</li>
				<li><code>"@commons/tên"</code> nạp nhóm option có sẵn của Flatsome: <code>advanced</code> (Class + Visibility), <code>visibility</code>, <code>background</code>, <code>border</code>, <code>typography</code>, <code>position</code>…</li>
				<li>Thêm <code>"responsive": true</code> vào option để UX Builder lưu riêng giá trị cho mobile/tablet (<code>$size__sm</code>, <code>$size__md</code>).</li>
			</ul>
			<h3>Preset</h3>
			<p>Giống chuẩn của Flatsome: mảng <code>{'{ "name", "content" }'}</code>, <code>content</code> là shortcode bắt đầu bằng tag của chính element. Preset đầu tiên là nội dung được chèn khi kéo element vào trang; có từ 2 preset trở lên thì UX Builder hiện danh sách để chọn.</p>
			<Code>{ `"presets": [
  { "name": "2 slider", "content": "[fbm_ba_carousel][fbm_before_after][fbm_before_after][/fbm_ba_carousel]" }
]` }</Code>
			<p>Mọi tag trong preset phải là element đã đăng ký. Nếu element con chưa publish hoặc không bật nơi xuất hiện tương ứng, plugin tạm ẩn preset đó (UX Builder dùng preset mặc định rỗng) để không chèn một khối Text chứa shortcode dạng chữ.</p>
		</>
	),
	'option-types': null, // dựng riêng bên dưới
	template: (
		<>
			<p>Template là HTML + PHP. Plugin bọc template trong một hàm, nên bạn viết như một file template bình thường.</p>
			<h3>Biến có sẵn</h3>
			<ul>
				<li>Mỗi option: <code>$title</code>, <code>$bg</code>… (đã gộp với giá trị default).</li>
				<li><code>$atts</code>: mảng tất cả option. <code>$content</code>: nội dung bên trong (chỉ element container).</li>
				<li><code>$tag</code>: tag shortcode. <code>$fbm_id</code>: id duy nhất cho mỗi lần render, dùng cho <code>id=""</code> và CSS riêng.</li>
				<li><code>$class</code>, <code>$visibility</code>: khi schema có <code>@commons/advanced</code>.</li>
			</ul>
			<h3>Ví dụ</h3>
			<Code>{ `<div id="<?php echo esc_attr( $fbm_id ); ?>" class="my-box <?php echo esc_attr( $class ); ?>">
  <h3><?php echo esc_html( $title ); ?></h3>
  <?php if ( 'true' === $show_image ) : ?>
    <?php echo wp_get_attachment_image( (int) $img, 'large' ); ?>
  <?php endif; ?>
</div>` }</Code>
			<h3>Không được dùng</h3>
			<ul>
				<li><code>exit</code> / <code>die</code>: dừng cả trang. Dùng <code>return;</code> để thoát sớm.</li>
				<li>Khai báo hàm có tên, class, interface: element render lần 2 trên cùng trang sẽ lỗi "Cannot redeclare". Dùng closure: <code>$fmt = function ( $x ) {'{ … }'};</code></li>
				<li><code>namespace</code>, <code>declare</code>.</li>
			</ul>
			<h3>Escape</h3>
			<p>
				Luôn escape khi in: <code>esc_html()</code> cho chữ, <code>esc_attr()</code> cho thuộc tính, <code>esc_url()</code> cho link,
				<code>wp_kses_post()</code> cho HTML do người dùng nhập.
			</p>
		</>
	),
	'css-js': (
		<>
			<p>
				CSS và JS chỉ được nạp trên trang có element (frontend, canvas UX Builder, editor Gutenberg).
				Đặt mọi selector dưới class gốc của element để không ảnh hưởng phần khác của site.
			</p>
			<h3>JS: dùng fbm.ready()</h3>
			<p>
				Trong UX Builder và Gutenberg, element được chèn vào trang <b>sau khi</b> trang tải xong, nên <code>DOMContentLoaded</code> không đủ.
				<code>fbm.ready()</code> chạy hàm khởi tạo cho mọi phần tử khớp selector, kể cả phần tử chèn sau, và chỉ một lần cho mỗi phần tử.
			</p>
			<Code>{ `fbm.ready('.my-box', function (el) {
  el.querySelector('button').addEventListener('click', function () {
    el.classList.toggle('is-open');
  });
});` }</Code>
			<p>JS được kiểm tra cú pháp ngay trong trình duyệt khi bạn gõ. Viết JS thường (ES2017), không cần build.</p>
		</>
	),
	container: (
		<>
			<p>Element container chứa các element khác bên trong, ví dụ carousel chứa nhiều slider.</p>
			<Code>{ `{
  "type": "container",
  "allow": [ "fbm_before_after" ],
  "builder_template": "<div class=\\"my-track\\"><content></content></div>",
  "options": {
    "per_view": { "type": "slider", "heading": "Số mỗi khung", "default": "1", "min": 1, "max": 4 }
  }
}` }</Code>
			<ul>
				<li><code>allow</code>: element con được phép kéo vào. Element con có thể khai báo <code>"require": ["tag_cha"]</code> để chỉ nằm trong cha.</li>
				<li>Template PHP in con bằng <code>{ '<?php echo do_shortcode( $content ); ?>' }</code>.</li>
				<li>
					<code>builder_template</code>: khung hiển thị trên canvas UX Builder, <b>bắt buộc có</b> <code>&lt;content&gt;&lt;/content&gt;</code> làm chỗ đặt element con.
					Trong khung này option viết theo camelCase: <code>per_view</code> thành <code>{ '{{ shortcode.options.perView }}' }</code>. Bỏ trống thì plugin dùng khung mặc định.
				</li>
				<li>Option <code>$content</code> (ô soạn nội dung) chỉ dùng được với container. Element thường dùng option <code>textarea</code>.</li>
			</ul>
		</>
	),
	gutenberg: (
		<>
			<p>Mỗi element tự thành một block Gutenberg trong nhóm <b>Flatsome Elements</b>, không cần viết React hay build:</p>
			<ul>
				<li>Tên block: tag bỏ tiền tố <code>fbm_</code>, đổi "_" thành "-". Ví dụ <code>fbm_before_after</code> thành <code>fbm/before-after</code>.</li>
				<li>Sidebar tự dựng từ schema; group thành từng panel.</li>
				<li>Preview gọi đúng template PHP qua server, nên giống hệt frontend.</li>
				<li>Container dùng InnerBlocks; <code>allow</code> thành danh sách block được phép.</li>
				<li><code>presets</code> thành block variations.</li>
				<li>Option responsive chỉ có một giá trị trong Gutenberg (Gutenberg không có breakpoint riêng cho từng block).</li>
			</ul>
			<p>
				Ngoài ra có block <b>UX Block (Flatsome)</b> để chèn một UX Block bằng cách chọn từ danh sách, có preview và tôn trọng công tắc bật/tắt.
			</p>
			<p>Bỏ chọn "Gutenberg" trong phần Xuất hiện ở của element nếu chỉ muốn dùng trong UX Builder.</p>
		</>
	),
	checks: (
		<>
			<p>Code chỉ chạy trên site thật sau khi qua 2 cổng kiểm tra:</p>
			<ol className="fbm-steps">
				<li><b>Cổng 1, kiểm tra tĩnh</b> (tự chạy khi bạn gõ): cú pháp PHP, schema JSON, cú pháp JS, các lệnh bị cấm.</li>
				<li>
					<b>Cổng 2, chạy thử</b>: plugin gửi một request riêng về chính site để render template 2 lần với Dữ liệu thử,
					giới hạn 10 giây và khoảng 64 MB bộ nhớ. Request đó có lỗi nặng cũng không ảnh hưởng site hay trang admin bạn đang mở.
				</li>
				<li><b>Publish</b>: chỉ bấm được khi bản nháp hiện tại đã đạt cả hai cổng. Mỗi lần publish tạo một phiên bản mới.</li>
			</ol>
			<h3>Dữ liệu thử</h3>
			<Code>{ `{
  "title": "Tiêu đề thử",
  "$content": "[fbm_before_after][fbm_before_after]"
}` }</Code>
			<p>Option không ghi sẽ dùng default trong schema. Nên thử cả các giá trị "khó": chuỗi rỗng, ảnh chưa chọn, số 0.</p>
			<h3>Sau khi publish</h3>
			<p>
				Nếu element vẫn gây lỗi nặng trên site thật (ví dụ chỉ hết bộ nhớ với một giá trị cụ thể), cầu dao tự ngắt element đó,
				lưu thông báo lỗi kèm dòng và URL, từ request sau trang chạy bình thường. Mở element, sửa, chạy thử và publish lại,
				hoặc khôi phục phiên bản cũ.
			</p>
		</>
	),
	transfer: (
		<>
			<p>Xuất block và element ra một file JSON, rồi nhập vào site Flatsome khác có cài plugin.</p>
			<ul>
				<li><b>Kèm phụ thuộc</b>: tự thêm block lồng bên trong và element đang dùng trong nội dung.</li>
				<li><b>Xem trước</b>: luôn hiện danh sách sẽ tạo / ghi đè / bỏ qua trước khi nhập thật.</li>
				<li><b>Khi trùng</b>: Tạo bản mới (thêm -2, tự sửa <code>[block id]</code> và tag element trong nội dung nhập vào), Ghi đè, hoặc Bỏ qua.</li>
				<li>Element nhập vào luôn ở <b>bản nháp</b>: phải chạy thử trên site mới rồi mới publish.</li>
				<li>Ảnh không đi kèm. Block dùng ID ảnh của site cũ, nên cần chọn lại ảnh hoặc dùng plugin chuyển media.</li>
				<li>Trạng thái bật/tắt và hẹn giờ của block được giữ nguyên.</li>
			</ul>
		</>
	),
	api: (
		<>
			<p>Mọi chức năng của plugin đều có REST API. Giao diện này cũng chỉ gọi API đó.</p>
			<p>
				Địa chỉ gốc: <code>{ cfg.restUrl }</code>
				<br />
				Xác thực từ hệ thống ngoài: tạo <b>Application Password</b> trong Hồ sơ người dùng, rồi gửi HTTP Basic Auth.
			</p>
			<Code>{ `# Danh sách block đang tắt
curl -u "admin:xxxx xxxx xxxx xxxx" "${ cfg.restUrl || 'https://site/wp-json/fbm/v1/' }blocks?visibility=off"

# Tắt một block
curl -u "admin:xxxx xxxx xxxx xxxx" -X PATCH -H "Content-Type: application/json" \\
  -d '{"enabled": false}' "${ cfg.restUrl || 'https://site/wp-json/fbm/v1/' }blocks/42"` }</Code>
			<table className="fbm-table fbm-table--plain">
				<thead>
					<tr><th>Phương thức</th><th>Đường dẫn</th><th>Việc</th></tr>
				</thead>
				<tbody>
					<tr><td>GET</td><td><code>blocks?search&amp;category&amp;visibility&amp;status&amp;page</code></td><td>Danh sách, kèm số nơi dùng</td></tr>
					<tr><td>POST</td><td><code>blocks</code></td><td>Tạo block</td></tr>
					<tr><td>GET / PATCH</td><td><code>blocks/{ '{id}' }</code></td><td>Xem / sửa, bật/tắt (<code>enabled</code>), hẹn giờ (<code>show_from</code>, <code>show_until</code>)</td></tr>
					<tr><td>DELETE</td><td><code>blocks/{ '{id}' }?force&amp;ignore_usage</code></td><td>Thùng rác hoặc xoá hẳn; 409 nếu đang dùng</td></tr>
					<tr><td>POST</td><td><code>blocks/{ '{id}' }/duplicate</code>, <code>blocks/bulk</code></td><td>Nhân bản, thao tác hàng loạt</td></tr>
					<tr><td>GET</td><td><code>blocks/{ '{id}' }/usage</code></td><td>Nơi dùng</td></tr>
					<tr><td>POST</td><td><code>elements/{ '{id}' }/lint | test | publish | rollback</code></td><td>Kiểm tra, chạy thử, publish, khôi phục</td></tr>
					<tr><td>POST</td><td><code>transfer/export</code>, <code>transfer/import</code></td><td>Xuất / nhập</td></tr>
				</tbody>
			</table>
			<p>Quyền: quản lý block cần quyền sửa Trang (Editor trở lên). Element cần quyền Administrator và <code>unfiltered_html</code>.</p>
		</>
	),
	troubleshooting: (
		<>
			<h3>Trang trắng / lỗi nghiêm trọng sau khi publish element</h3>
			<p>
				Thường cầu dao đã tự ngắt element, chỉ cần tải lại trang. Nếu vẫn lỗi và không vào được admin, thêm vào <code>wp-config.php</code>:
			</p>
			<Code>{ "define( 'FBM_SAFE_MODE', true );" }</Code>
			<p>Mọi element tự viết sẽ tắt. Sửa xong thì xoá dòng này.</p>
			<h3>"Chưa chạy thử được"</h3>
			<p>
				Hosting chặn request từ site gọi về chính nó (loopback), thường do plugin bảo mật, tường lửa hoặc site đặt mật khẩu bảo vệ.
				Vẫn publish được sau khi xác nhận, nhưng khi đó chỉ có cổng 1 bảo vệ. Kiểm tra mục Công cụ › Tình trạng site của WordPress.
			</p>
			<h3>Option không nhận giá trị</h3>
			<p>Tên option có chữ hoa. Đổi sang chữ thường, ví dụ <code>bg_color</code>.</p>
			<h3>JS không chạy trong UX Builder / Gutenberg</h3>
			<p>Bọc code trong <code>fbm.ready('.selector', function (el) {'{ … }'})</code> thay vì <code>DOMContentLoaded</code>.</p>
			<h3>Kéo element con vào container không được</h3>
			<p>Kiểm tra <code>allow</code> của element cha có tag element con, và <code>builder_template</code> có <code>&lt;content&gt;&lt;/content&gt;</code>.</p>
			<h3>Block đã tắt nhưng vẫn thấy</h3>
			<p>Bạn đang đăng nhập (admin thấy khung nét đứt), hoặc cache trang chưa được xoá. Mở trang ở cửa sổ ẩn danh để kiểm tra.</p>
		</>
	),
};

function OptionTypes() {
	return (
		<>
			<p>Bảng đối chiếu kiểu option với ô hiện trong UX Builder và Gutenberg. Bấm Sao chép rồi dán vào schema, hoặc dùng nút <b>Chèn option</b> ngay trong editor.</p>
			<div className="fbm-table-wrap">
				<table className="fbm-table fbm-table--plain">
					<thead>
						<tr><th>type</th><th>UX Builder</th><th>Gutenberg</th><th>Dùng trong template</th><th /></tr>
					</thead>
					<tbody>
						{ OPTION_SNIPPETS.map( ( o ) => (
							<tr key={ o.type }>
								<td><code>{ o.type }</code></td>
								<td>{ o.ux }</td>
								<td>{ o.gb }</td>
								<td><code className="fbm-code-inline">{ o.php }</code></td>
								<td><CopyButton text={ o.snippet } /></td>
							</tr>
						) ) }
					</tbody>
				</table>
			</div>
			<p>Thuộc tính chung: <code>heading</code> (nhãn), <code>description</code> (gợi ý), <code>default</code>, <code>responsive</code>, <code>full_width</code>, <code>placeholder</code>.</p>
		</>
	);
}

export default function Guide( { topic } ) {
	const current = GUIDE_TITLES[ topic ] ? topic : 'quick-start';
	const [ query, setQuery ] = useState( '' );
	const list = GUIDE_ORDER.filter( ( [ , title ] ) => title.toLowerCase().includes( query.toLowerCase() ) );

	return (
		<div className="fbm-guide">
			<nav className="fbm-guide__nav" aria-label="Chủ đề hướng dẫn">
				<input
					type="search"
					className="fbm-input"
					placeholder="Tìm chủ đề…"
					value={ query }
					onChange={ ( e ) => setQuery( e.target.value ) }
					aria-label="Tìm chủ đề"
				/>
				{ list.map( ( [ id, title ] ) => (
					<a key={ id } href={ `#/guide/${ id }` } className={ id === current ? 'is-active' : '' } aria-current={ id === current ? 'page' : undefined }>
						{ title }
					</a>
				) ) }
			</nav>
			<article className="fbm-guide__body fbm-panel">
				<div className="fbm-panel__body fbm-prose">
					<h2>{ GUIDE_TITLES[ current ] }</h2>
					{ current === 'option-types' ? <OptionTypes /> : CONTENT[ current ] }
				</div>
			</article>
		</div>
	);
}
