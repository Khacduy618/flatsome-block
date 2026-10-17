/**
 * Nội dung ngắn cho nút "?" (HelpTip). Mỗi mục trỏ tới một chủ đề trong tab Hướng dẫn.
 * Tối đa 3 câu.
 */
export const TOPICS = {
	visibility: {
		title: 'Bật / tắt block',
		text: 'Tắt thì [block] không hiện gì với khách, admin thấy khung nét đứt. Flatsome vẫn hiện block ở trạng thái Nháp hoặc trong Thùng rác, nên muốn ẩn block thì dùng công tắc này.',
		guide: 'visibility',
	},
	schedule: {
		title: 'Hẹn giờ hiển thị',
		text: 'Bỏ trống hai ô thì block luôn hiện khi công tắc bật. Điền một hoặc cả hai ô để block tự hiện và tự ẩn. Giờ nhập theo đồng hồ máy bạn, plugin tự quy đổi.',
		guide: 'visibility',
	},
	slug: {
		title: 'Slug',
		text: 'Tên ngắn dùng trong [block id="slug"]. Đổi slug làm các shortcode cũ không tìm thấy block. Plugin sẽ liệt kê những nơi bị ảnh hưởng trước khi lưu.',
		guide: 'blocks',
	},
	status: {
		title: 'Trạng thái bài viết',
		text: 'Chỉ ảnh hưởng tới việc block hiện trong danh sách và có thể xem trước. Flatsome vẫn render block dù đang Nháp. Muốn ẩn ngoài site, dùng công tắc Hiển thị.',
		guide: 'visibility',
	},
	usage: {
		title: 'Nơi dùng',
		text: 'Các trang, sản phẩm, menu, widget và theme option đang chứa block này. Chỉ mục tự cập nhật mỗi khi bạn lưu nội dung.',
		guide: 'usage',
	},
	usageIndex: {
		title: 'Chỉ mục nơi dùng',
		text: 'Lần đầu cài plugin cần quét toàn bộ nội dung một lần. Sau đó chỉ mục tự cập nhật khi bạn lưu bài viết, menu, widget hoặc theme option.',
		guide: 'usage',
	},
	content: {
		title: 'Nội dung block',
		text: 'Là shortcode do UX Builder tạo ra. Nên sửa bằng UX Builder; chỉ sửa trực tiếp ở đây khi cần thay nhanh một chữ hoặc một thuộc tính.',
		guide: 'blocks',
	},
	categories: {
		title: 'Danh mục block',
		text: 'Dùng để nhóm và lọc block. Là taxonomy block_categories của Flatsome nên dùng chung với màn hình UX Blocks gốc.',
		guide: 'blocks',
	},
	tag: {
		title: 'Tag shortcode',
		text: 'Tên shortcode của element, ví dụ hero_countdown thành [hero_countdown]. Chỉ gồm chữ thường, số và "_". Không đổi được sau khi tạo vì nội dung trang đang dùng tag này.',
		guide: 'quick-start',
	},
	targets: {
		title: 'Xuất hiện ở đâu',
		text: 'UX Builder: element có trong danh sách kéo-thả. Gutenberg: element thành block "fbm/…" trong nhóm Flatsome Elements. Cả hai dùng chung template PHP.',
		guide: 'gutenberg',
	},
	schema: {
		title: 'Schema',
		text: 'Khai báo các ô tuỳ chỉnh của element. Mỗi option thành một ô trong panel UX Builder và Gutenberg, và thành một biến cùng tên trong template.',
		guide: 'schema',
	},
	template: {
		title: 'Template PHP',
		text: 'HTML + PHP để render element. Mỗi option là một biến cùng tên. Luôn escape dữ liệu bằng esc_html(), esc_attr(), esc_url().',
		guide: 'template',
	},
	cssjs: {
		title: 'CSS và JS',
		text: 'Chỉ được nạp trên trang có element. Viết JS trong fbm.ready(".class", function (el) { … }) để chạy được cả trong UX Builder và Gutenberg.',
		guide: 'css-js',
	},
	sample: {
		title: 'Dữ liệu thử',
		text: 'Giá trị option dùng khi bấm Chạy thử, dạng JSON. Option không ghi ở đây sẽ dùng giá trị default trong schema. Với element container, ghi nội dung con vào khoá "$content".',
		guide: 'checks',
	},
	checks: {
		title: 'Hai cổng kiểm tra',
		text: 'Cổng 1 đọc code mà không chạy: cú pháp PHP, schema, JS. Cổng 2 chạy thử template 2 lần trong một request riêng, nên lỗi nặng cũng không làm sập site. Chỉ publish được khi cả hai cổng đều đạt.',
		guide: 'checks',
	},
	versions: {
		title: 'Phiên bản',
		text: 'Mỗi lần publish tạo một phiên bản không sửa được. Khôi phục đưa phiên bản cũ lên chạy ngay, bản nháp hiện tại không bị mất.',
		guide: 'checks',
	},
	tripped: {
		title: 'Đã ngắt',
		text: 'Element gây lỗi nặng (hết bộ nhớ, quá thời gian) trên site thật nên plugin đã tự tắt nó. Sửa lỗi, chạy thử, publish lại hoặc bấm Bật lại.',
		guide: 'troubleshooting',
	},
	conflict: {
		title: 'Khi trùng slug hoặc tag',
		text: 'Tạo bản mới: thêm -2 và tự sửa các [block] lồng nhau trong file nhập. Ghi đè: thay nội dung đang có. Bỏ qua: giữ nguyên dữ liệu trên site.',
		guide: 'transfer',
	},
	withDeps: {
		title: 'Kèm phụ thuộc',
		text: 'Tự thêm các block được nhúng bên trong block đã chọn, và các element đang được dùng trong nội dung của chúng.',
		guide: 'transfer',
	},
	safeMode: {
		title: 'Safe mode',
		text: 'Tắt mọi element tự viết ngay lập tức, không ảnh hưởng block. Nếu không vào được admin, thêm define( \'FBM_SAFE_MODE\', true ); vào wp-config.php.',
		guide: 'troubleshooting',
	},
	modules: {
		title: 'Module',
		text: 'Tắt module bạn không dùng để plugin nhẹ hơn. Module tắt không đăng ký hook nào. Dữ liệu vẫn được giữ.',
		guide: 'quick-start',
	},
	uninstall: {
		title: 'Xoá dữ liệu khi gỡ',
		text: 'Khi bật, gỡ plugin sẽ xoá bảng, element và cài đặt của plugin. Block của Flatsome luôn được giữ nguyên.',
		guide: 'troubleshooting',
	},
	api: {
		title: 'REST API',
		text: 'Hệ thống ngoài gọi được mọi chức năng của plugin. Xác thực bằng Application Password tạo trong Hồ sơ người dùng.',
		guide: 'api',
	},
};
