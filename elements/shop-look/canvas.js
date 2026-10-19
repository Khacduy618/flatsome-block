/**
 * Canvas UX Builder (chỉ nạp trong iframe canvas): Shop The Look dùng controller của element Slider (ux_slider)
 * để Flickity chạy và chuyển đúng slide khi chọn từng Look. UxBuilder.controller() là API đăng ký controller
 * theo tag mà Flatsome dùng cho chính ux_slider; dữ liệu shortcode nằm ở cửa sổ editor (window.parent).
 */
( () => {
	try {
		const data = window.parent && window.parent.uxBuilderData;
		const slider = data && data.shortcodes && data.shortcodes.ux_slider;
		if ( window.UxBuilder && typeof window.UxBuilder.controller === 'function' && slider && slider.controller && data.shortcodes.fbm_shop_look ) {
			window.UxBuilder.controller( 'fbm_shop_look', slider.controller );
		}
	} catch ( e ) {
		// Flatsome đổi API nội bộ: canvas vẫn hiện Look, chỉ không có slider.
	}
} )();
