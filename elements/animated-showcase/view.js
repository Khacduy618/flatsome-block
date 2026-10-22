/**
 * Animated Showcase UX Block: kịch bản mỗi bước (.fbm-as__item)
 *   1. ảnh trái trượt mượt vào (.is-s1)  2. cùng lúc chữ hiện từng ký tự, gõ xong khi ảnh trái dừng (.is-s2)
 *   3. ảnh trái vừa vào xong → ảnh phải trượt từ dưới lên thay chữ ngay (.is-s3)
 *   4. đẩy lần 1: ảnh thứ ba vào bên phải, ảnh phải sang trái (.is-s4)
 *   5. đẩy lần 2: ảnh trái ban đầu vào lại từ bên phải, ảnh thứ ba sang trái (.is-s5) → về như ban đầu
 * Mỗi bước chạy 2 lượt rồi sang bước kế; hết thì quay lại bước đầu, lặp mãi. Hiệu ứng nằm ở CSS, JS chỉ hẹn giờ đổi class.
 * Dừng khi cuộn khỏi màn hình / ẩn tab; máy bật "giảm chuyển động" thì giữ trạng thái tĩnh.
 * API cho editor.js: window.fbmAnimatedShowcase.init( root ) → { focus, setStatic, refresh }.
 */
( () => {
	const STEPS = [ 'is-s1', 'is-s2', 'is-s3', 'is-s4', 'is-s5' ];
	const reduced = () => window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
	const num = ( v, d, min, max ) => {
		const n = parseFloat( v );
		return Math.min( max, Math.max( min, Number.isFinite( n ) ? n : d ) );
	};

	const init = ( root ) => {
		if ( root.fbmAnimated ) {
			return root.fbmAnimated;
		}
		const items = () => Array.from( root.querySelectorAll( '.fbm-as__item' ) );
		let timers = [];
		let current = 0;
		let cycle = 0;
		let focus = -1;
		let visible = true;
		let still = false;

		const clear = () => {
			timers.forEach( clearTimeout );
			timers = [];
		};
		const at = ( ms, fn ) => timers.push( setTimeout( fn, ms ) );

		// Về đầu kịch bản tức thì (không chạy ngược hiệu ứng).
		const reset = ( item ) => {
			item.classList.add( 'is-resetting' );
			item.classList.remove( ...STEPS );
			void item.offsetWidth; // eslint-disable-line no-void
			item.classList.remove( 'is-resetting' );
		};

		const show = ( index ) => {
			items().forEach( ( it, i ) => {
				it.classList.toggle( 'is-current', i === index );
				it.classList.remove( 'is-out' );
			} );
			root.dispatchEvent( new CustomEvent( 'fbm-as:change', { detail: { index } } ) );
		};

		const play = () => {
			clear();
			const list = items();
			const n = list.length;
			const running = n > 0 && visible && ! still && ! reduced();
			root.classList.toggle( 'is-playing', running );
			if ( ! n ) {
				return;
			}
			current = focus >= 0 ? Math.min( focus, n - 1 ) : current % n;
			list.forEach( reset );
			show( current );
			if ( ! running ) {
				return;
			}
			const item = list[ current ];
			const d = num( root.dataset.duration, 700, 300, 2000 );
			const g = num( root.dataset.delay, 1200, 200, 4000 );
			const chars = item.querySelectorAll( '.fbm-as__char' ).length;
			const enter = d * 1.5; // Ảnh trái vào (khớp calc( var(--fbm-as-dur) * 1.5 ) trong CSS).
			// Chữ gõ song song lúc ảnh trái trượt và hiện đủ khi ảnh trái dừng: mỗi ký tự tối đa 60 ms.
			const per = chars ? Math.min( 60, ( enter - d / 2 ) / chars ) : 0;
			item.style.setProperty( '--fbm-as-char', per + 'ms' );
			const t1 = 50;
			const t3 = t1 + enter; // Ảnh phải vào ngay khi ảnh trái vào xong.
			const t4 = t3 + d + g;
			const t5 = t4 + d + g;
			const end = t5 + d + g;
			at( t1, () => item.classList.add( 'is-s1', 'is-s2' ) );
			at( t3, () => item.classList.add( 'is-s3' ) );
			at( t4, () => item.classList.add( 'is-s4' ) );
			at( t5, () => item.classList.add( 'is-s5' ) );
			// Hết lượt: mờ đi, rồi chạy lại (lượt 2) hoặc sang bước kế.
			at( end, () => item.classList.add( 'is-out' ) );
			at( end + d / 2, () => {
				cycle++;
				if ( cycle >= 2 ) {
					cycle = 0;
					if ( focus < 0 ) {
						current = ( current + 1 ) % Math.max( 1, items().length );
					}
				}
				play();
			} );
		};

		if ( 'IntersectionObserver' in window ) {
			new IntersectionObserver( ( entries ) => {
				entries.forEach( ( en ) => {
					if ( en.isIntersecting !== visible ) {
						visible = en.isIntersecting;
						cycle = 0;
						play();
					}
				} );
			} ).observe( root );
		}
		document.addEventListener( 'visibilitychange', () => {
			visible = ! document.hidden;
			play();
		} );

		root.fbmAnimated = {
			// Canvas: chỉ lặp bước đang chọn (-1: chạy cả danh sách).
			focus: ( i ) => {
				if ( i !== focus ) {
					focus = i;
					cycle = 0;
					if ( i >= 0 ) {
						current = i;
					}
					play();
				}
			},
			setStatic: ( on ) => {
				if ( !! on !== still ) {
					still = !! on;
					play();
				}
			},
			refresh: () => {
				cycle = 0;
				play();
			},
		};
		play();
		return root.fbmAnimated;
	};

	window.fbmAnimatedShowcase = { init };

	// Khởi tạo khối mới chèn vào trang; khối có bước / chữ vừa đổi (canvas UX Builder) thì chạy lại từ đầu.
	let queued = 0;
	const dirty = new Set();
	const flush = () => {
		queued = 0;
		document.querySelectorAll( '.fbm-as' ).forEach( ( root ) => {
			if ( ! root.fbmAnimated ) {
				init( root );
			} else if ( dirty.has( root ) ) {
				root.fbmAnimated.refresh();
			}
		} );
		dirty.clear();
	};
	const start = () => {
		flush();
		new MutationObserver( ( records ) => {
			records.forEach( ( r ) => {
				const root = r.target.closest && r.target.closest( '.fbm-as' );
				if ( root ) {
					dirty.add( root );
				}
			} );
			if ( ! queued ) {
				queued = setTimeout( flush, 150 );
			}
		} ).observe( document.body, { childList: true, subtree: true } );
	};
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
