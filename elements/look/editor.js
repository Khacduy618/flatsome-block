/**
 * Canvas UX Builder (chỉ nạp trong iframe canvas): kéo pin của Look trên ảnh, hoặc bấm chỗ trống trên ảnh của Look
 * đang chọn để đưa pin đang chọn tới đó. Giá trị ghi vào options.pinN_x / pinN_y của shortcode fbm_look, nên bảng
 * cài đặt, canvas và nội dung lưu đều cập nhật như khi kéo thanh X/Y.
 *
 * Dựa vào cấu trúc nội bộ của UX Builder (Angular $rootScope, shortcode.options). Thiếu thứ gì thì không làm gì:
 * thanh X/Y vẫn dùng bình thường.
 */
( () => {
	if ( ! window.angular ) {
		return;
	}

	const rootScope = () => {
		try {
			const injector = window.angular.element( document.documentElement ).injector();
			return injector ? injector.get( '$rootScope' ) : null;
		} catch ( e ) {
			return null;
		}
	};

	// Đối tượng shortcode fbm_look có phần tử gốc là section.fbm-stl đang xét. Tìm lại mỗi lần vì canvas vẽ lại.
	const findShortcode = ( root ) => {
		const scope = rootScope();
		if ( ! scope ) {
			return null;
		}
		let found = null;
		const seen = new Set();
		const walk = ( s ) => {
			for ( ; s && ! found; s = s.$$nextSibling ) {
				Object.keys( s ).forEach( ( key ) => {
					const v = s[ key ];
					if ( ! found && key.charAt( 0 ) !== '$' && v && typeof v === 'object' && ! seen.has( v ) ) {
						seen.add( v );
						const el = v.$element && v.$element[ 0 ];
						if ( v.tag === 'fbm_look' && v.options && el && ( el === root || el.contains( root ) ) ) {
							found = v;
						}
					}
				} );
				if ( s.$$childHead ) {
					walk( s.$$childHead );
				}
			}
		};
		walk( scope );
		return found;
	};

	const clamp = ( n ) => Math.max( 0, Math.min( 100, Math.round( n ) ) );
	const pointToPct = ( media, e ) => {
		const r = media.getBoundingClientRect();
		return [ clamp( ( ( e.clientX - r.left ) / r.width ) * 100 ), clamp( ( ( e.clientY - r.top ) / r.height ) * 100 ) ];
	};

	const save = ( root, slot, x, y ) => {
		const sc = findShortcode( root );
		const scope = rootScope();
		if ( ! sc || ! scope || ! slot ) {
			return;
		}
		sc.options[ 'pin' + slot + '_x' ] = x;
		sc.options[ 'pin' + slot + '_y' ] = y;
		scope.$apply();
	};

	const place = ( pin, x, y ) => {
		pin.style.setProperty( '--fbm-x', x + '%' );
		pin.style.setProperty( '--fbm-y', y + '%' );
	};

	// Kéo pin.
	document.addEventListener(
		'pointerdown',
		( e ) => {
			const pin = e.target.closest && e.target.closest( '.fbm-stl__pin' );
			const root = pin && pin.closest( '.fbm-stl' );
			const media = pin && pin.closest( '.fbm-stl__media' );
			if ( ! pin || ! root || ! media || e.button !== 0 ) {
				return;
			}
			root.dataset.fbmEditSlot = pin.dataset.slot;
			const start = [ e.clientX, e.clientY ];
			let moved = false;
			let last = null;
			const onMove = ( ev ) => {
				if ( ! moved && Math.hypot( ev.clientX - start[ 0 ], ev.clientY - start[ 1 ] ) < 4 ) {
					return;
				}
				moved = true;
				ev.preventDefault();
				last = pointToPct( media, ev );
				place( pin, last[ 0 ], last[ 1 ] );
			};
			const onUp = () => {
				document.removeEventListener( 'pointermove', onMove, true );
				document.removeEventListener( 'pointerup', onUp, true );
				if ( moved && last ) {
					save( root, pin.dataset.slot, last[ 0 ], last[ 1 ] );
				}
			};
			// Không để UX Builder hiểu cú kéo là kéo cả element sang chỗ khác.
			e.stopPropagation();
			document.addEventListener( 'pointermove', onMove, true );
			document.addEventListener( 'pointerup', onUp, true );
		},
		true
	);

	// Bấm chỗ trống trên ảnh của Look đang chọn: pin đang chọn (mặc định pin đầu tiên) nhảy tới đó.
	document.addEventListener(
		'click',
		( e ) => {
			if ( e.target.closest && e.target.closest( '.fbm-stl__pin' ) ) {
				return;
			}
			const media = e.target.closest && e.target.closest( '.fbm-stl__media' );
			const root = media && media.closest( '.fbm-stl' );
			if ( ! root || ! root.classList.contains( 'uxb-selected' ) ) {
				return;
			}
			const slot = root.dataset.fbmEditSlot || ( root.querySelector( '.fbm-stl__pin' ) || { dataset: {} } ).dataset.slot;
			const pin = slot && root.querySelector( '.fbm-stl__pin[data-slot="' + slot + '"]' );
			if ( ! pin ) {
				return;
			}
			const [ x, y ] = pointToPct( media, e );
			place( pin, x, y );
			save( root, slot, x, y );
		},
		true
	);
} )();
