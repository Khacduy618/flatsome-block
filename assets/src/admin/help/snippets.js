/**
 * Danh mục option dùng chung cho tab Hướng dẫn (bảng "Các kiểu option") và nút "Chèn option" trong editor schema.
 */
export const OPTION_SNIPPETS = [
	{
		type: 'textfield',
		ux: 'Ô chữ 1 dòng',
		gb: 'TextControl',
		snippet: '"title": { "type": "textfield", "heading": "Tiêu đề", "default": "" }',
		php: '<?php echo esc_html( $title ); ?>',
	},
	{
		type: 'textarea',
		ux: 'Ô chữ nhiều dòng',
		gb: 'TextareaControl',
		snippet: '"text": { "type": "textarea", "heading": "Đoạn văn", "default": "" }',
		php: '<?php echo wp_kses_post( wpautop( $text ) ); ?>',
	},
	{
		type: 'select',
		ux: 'Danh sách chọn',
		gb: 'SelectControl',
		snippet: '"layout": { "type": "select", "heading": "Bố cục", "default": "grid", "options": { "grid": "Lưới", "list": "Danh sách" } }',
		php: '<div class="layout-<?php echo esc_attr( $layout ); ?>">',
	},
	{
		type: 'radio-buttons',
		ux: 'Nhóm nút chọn',
		gb: 'SelectControl',
		snippet: '"align": { "type": "radio-buttons", "heading": "Căn lề", "default": "left", "options": { "left": { "title": "Trái" }, "center": { "title": "Giữa" } } }',
		php: 'style="text-align: <?php echo esc_attr( $align ); ?>"',
	},
	{
		type: 'slider',
		ux: 'Thanh kéo số',
		gb: 'RangeControl',
		snippet: '"size": { "type": "slider", "heading": "Kích thước", "default": "50", "min": 0, "max": 100, "unit": "%" }',
		php: 'width: <?php echo (float) $size; ?>%',
	},
	{
		type: 'checkbox',
		ux: 'Công tắc',
		gb: 'ToggleControl',
		snippet: '"show_title": { "type": "checkbox", "heading": "Hiện tiêu đề", "default": "true" }',
		php: "<?php if ( 'true' === $show_title ) : ?> … <?php endif; ?>",
	},
	{
		type: 'colorpicker',
		ux: 'Chọn màu',
		gb: 'ColorPicker',
		snippet: '"bg": { "type": "colorpicker", "heading": "Màu nền", "default": "", "alpha": true }',
		php: 'style="background: <?php echo esc_attr( $bg ); ?>"',
	},
	{
		type: 'image',
		ux: 'Chọn ảnh (lưu ID)',
		gb: 'MediaUpload',
		snippet: '"img": { "type": "image", "heading": "Ảnh", "default": "" }',
		php: "<?php echo wp_get_attachment_image( (int) $img, 'large' ); ?>",
	},
	{
		type: 'file',
		ux: 'Chọn file / video',
		gb: 'MediaUpload',
		snippet: '"video": { "type": "file", "heading": "Video", "default": "" }',
		php: '<video src="<?php echo esc_url( wp_get_attachment_url( (int) $video ) ); ?>"></video>',
	},
	{
		type: 'group',
		ux: 'Nhóm thu gọn',
		gb: 'PanelBody',
		snippet: '"label_group": { "type": "group", "heading": "Nhãn", "options": {\n    "label": { "type": "textfield", "heading": "Nhãn", "default": "" }\n  } }',
		php: '// Option trong group vẫn là biến bình thường: $label',
	},
	{
		type: '@commons/advanced',
		ux: 'Class + Visibility của Flatsome',
		gb: 'PanelBody',
		snippet: '"advanced_options": "@commons/advanced"',
		php: '<div class="<?php echo esc_attr( trim( $class . \' \' . $visibility ) ); ?>">',
	},
];
