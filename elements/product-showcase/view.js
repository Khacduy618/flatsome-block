/**
 * Product Showcase Slider: tự chạy, Timer, Pause on Hover, đổi .is-selected đều do Slider (Flickity) của Flatsome làm;
 * hiệu ứng ảnh chạy theo .is-selected trong CSS nên luôn khớp nhịp slider.
 * File này chỉ thêm: máy bật "giảm chuyển động" thì không tự chạy (Flatsome không tự tắt autoPlay).
 */
( () => {
	if ( ! window.matchMedia || ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches || ! window.jQuery ) {
		return;
	}
	const stop = ( slider ) => {
		const flkty = window.jQuery( slider ).data( 'flickity' );
		if ( flkty && ! slider.dataset.fbmPsStopped ) {
			slider.dataset.fbmPsStopped = '1';
			flkty.options.autoPlay = false;
			flkty.stopPlayer();
		}
	};
	const scan = () => document.querySelectorAll( '.fbm-ps .slider.flickity-enabled' ).forEach( stop );
	const start = () => {
		scan();
		// Flatsome khởi tạo slider sau khi trang tải (và khi slider cuộn tới): bắt lúc có .flickity-enabled.
		new MutationObserver( scan ).observe( document.body, { subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
	};
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
