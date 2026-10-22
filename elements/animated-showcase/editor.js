/**
 * Canvas UX Builder (chỉ nạp trong iframe canvas): Animated Showcase chạy kịch bản như ngoài site; chọn một bước
 * (cây bên trái hoặc bấm trên canvas) thì chỉ lặp bước đó để xem trước; bỏ chọn thì chạy cả danh sách.
 * Ảnh / chữ của bước là template Angular nên đổi option là canvas vẽ lại ngay; controller theo dõi option qua digest
 * của $rootScope và chạy lại kịch bản (view.js refresh).
 *
 * Dựa vào API nội bộ của UX Builder (UxBuilder.controller, service app / targets). Thiếu thứ gì thì khối hiện tĩnh
 * (ảnh trái + chữ của bước đang chọn hoặc bước đầu), không lỗi script.
 */
( () => {
	const TAG = 'fbm_animated_showcase';

	const api = ( el ) => {
		const root = el && ( el.classList.contains( 'fbm-as' ) ? el : el.querySelector && el.querySelector( '.fbm-as' ) );
		return root && window.fbmAnimatedShowcase ? window.fbmAnimatedShowcase.init( root ) : null;
	};

	function AnimatedCanvas( app, shortcode, targets, $scope, $element, $timeout ) {
		const el = $element && $element[ 0 ];
		const later = ( fn ) => $timeout( fn, 0, false );
		if ( el ) {
			el.dataset.fbmAsCanvas = '1';
			// Các bước xếp chồng: chỉ bước đang hiện nhận chuột của UX Builder.
			el.addEventListener( 'fbm-as:change', ( e ) => {
				try {
					targets.disable( shortcode.children );
					if ( shortcode.children[ e.detail.index ] ) {
						targets.enable( shortcode.children[ e.detail.index ] );
					}
				} catch ( err ) {}
			} );
		}

		const selectedIndex = () => {
			const sel = app && app.states && app.states.selectedShortcode;
			return sel ? shortcode.children.findIndex( ( c ) => sel === c || ( typeof sel.isSelfOrDescendantOf === 'function' && sel.isSelfOrDescendantOf( c ) ) ) : -1;
		};
		$scope.$watch( selectedIndex, ( i ) =>
			later( () => {
				const a = api( el );
				if ( a ) {
					a.focus( i );
				}
			} )
		);

		// Option của khung và của từng bước (ảnh, chữ): đổi là chạy lại kịch bản để thấy ngay.
		const snapshot = () => {
			const o = shortcode.options || {};
			return JSON.stringify( [ o.duration, o.stepDelay, o.imageRatio, o.imageSize, shortcode.children.map( ( c ) => c.options ) ] );
		};
		$scope.$watch( snapshot, ( now, before ) => {
			if ( now !== before ) {
				later( () => {
					const a = api( el );
					if ( a ) {
						a.refresh();
					}
				} );
			}
		} );

		$scope.$on( '$destroy', () => {
			const a = api( el );
			if ( a ) {
				a.setStatic( true );
			}
		} );
	}
	AnimatedCanvas.$inject = [ 'app', 'shortcode', 'targets', '$scope', '$element', '$timeout' ];

	try {
		const data = window.parent && window.parent.uxBuilderData;
		if ( window.angular && window.UxBuilder && typeof window.UxBuilder.controller === 'function' && data && data.shortcodes && data.shortcodes[ TAG ] ) {
			window.UxBuilder.controller( TAG, AnimatedCanvas );
		}
	} catch ( e ) {
		// Flatsome đổi API nội bộ: dùng chế độ tĩnh bên dưới.
	}

	// Chế độ tĩnh cho khối controller không nhận: không chạy kịch bản, hiện bước đang chọn (mặc định bước đầu).
	const fallback = () =>
		document.querySelectorAll( '.fbm-as' ).forEach( ( root ) => {
			if ( root.dataset.fbmAsCanvas ) {
				return;
			}
			const a = api( root );
			if ( ! a ) {
				return;
			}
			const steps = Array.from( root.querySelectorAll( '.fbm-as__item' ) );
			const picked = steps.findIndex( ( s ) => s.classList.contains( 'uxb-selected' ) || s.querySelector( '.uxb-selected' ) );
			a.focus( Math.max( 0, picked ) );
			a.setStatic( true );
		} );
	let queued = 0;
	const start = () => {
		setTimeout( fallback, 0 ); // Một nhịp cho controller (nếu có) nhận khối trước.
		new MutationObserver( () => {
			if ( ! queued ) {
				queued = setTimeout( () => {
					queued = 0;
					fallback();
				}, 100 );
			}
		} ).observe( document.body, { childList: true, subtree: true, attributes: true, attributeFilter: [ 'class' ] } );
	};
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
