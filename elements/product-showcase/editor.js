/**
 * Canvas UX Builder (chỉ nạp trong iframe canvas): Product Showcase Slider dùng controller của element Slider (ux_slider),
 * nên trong canvas Flickity chạy thật (Fade / Slide, 2 cột) và chọn một slide ở cây bên trái / thanh chọn slide thì slider
 * chuyển tới slide đó. UxBuilder.controller() là API Flatsome dùng cho chính ux_slider; dữ liệu nằm ở cửa sổ editor.
 * Ảnh / chữ của slide là template Angular (wp-attachment) nên đổi option là canvas cập nhật ngay qua digest của $rootScope.
 *
 * Không có Angular / API đổi: canvas hiện tĩnh — chỉ slide đầu (hoặc slide đang chọn), không lỗi script.
 */
( () => {
	const TAG = 'fbm_product_showcase';
	let linked = false;
	try {
		const data = window.parent && window.parent.uxBuilderData;
		const slider = data && data.shortcodes && data.shortcodes.ux_slider;
		if ( window.angular && window.UxBuilder && typeof window.UxBuilder.controller === 'function' && slider && slider.controller && data.shortcodes[ TAG ] ) {
			window.UxBuilder.controller( TAG, slider.controller );
			linked = true;
		}
	} catch ( e ) {
		linked = false;
	}
	if ( linked ) {
		return;
	}

	// Chế độ tĩnh: slider không được khởi tạo thì các slide nằm thành một hàng; chỉ để lại slide đang chọn (mặc định slide đầu).
	const still = () =>
		document.querySelectorAll( '.fbm-ps .slider:not(.flickity-enabled)' ).forEach( ( slider ) => {
			const slides = Array.from( slider.querySelectorAll( '.fbm-ps__slide' ) );
			const picked = slides.find( ( s ) => s.classList.contains( 'uxb-selected' ) || s.querySelector( '.uxb-selected' ) ) || slides[ 0 ];
			slides.forEach( ( s ) => {
				s.style.display = s === picked ? '' : 'none';
			} );
		} );
	let queued = false;
	const start = () => {
		still();
		new MutationObserver( () => {
			if ( ! queued ) {
				queued = true;
				setTimeout( () => {
					queued = false;
					still();
				}, 50 );
			}
		} ).observe( document.body, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
	};
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
