/**
 * Before / After: kéo bằng chuột / cảm ứng, phím mũi tên trên tay nắm (5% mỗi lần, Home/End).
 * Khởi tạo cả phần tử chèn sau (canvas UX Builder); mỗi phần tử một lần.
 */
( () => {
	const init = ( el ) => {
		if ( el.dataset.fbmReady ) {
			return;
		}
		el.dataset.fbmReady = '1';
		const stage = el.querySelector( '.fbm-ba__stage' );
		const handle = el.querySelector( '.fbm-ba__handle' );
		const vertical = el.classList.contains( 'fbm-ba--v' );
		let dragging = false;

		const set = ( p ) => {
			p = Math.max( 0, Math.min( 100, Math.round( p * 10 ) / 10 ) );
			el.style.setProperty( '--fbm-pos', p + '%' );
			handle.setAttribute( 'aria-valuenow', String( p ) );
		};
		const current = () => parseFloat( handle.getAttribute( 'aria-valuenow' ) ) || 0;
		const fromEvent = ( e ) => {
			const r = stage.getBoundingClientRect();
			return vertical ? ( ( e.clientY - r.top ) / r.height ) * 100 : ( ( e.clientX - r.left ) / r.width ) * 100;
		};

		stage.addEventListener( 'pointerdown', ( e ) => {
			dragging = true;
			e.stopPropagation(); // Không để slider Flickity của theme kéo cả slide.
			el.classList.remove( 'fbm-ba--animating' );
			if ( stage.setPointerCapture ) {
				stage.setPointerCapture( e.pointerId );
			}
			set( fromEvent( e ) );
		} );
		stage.addEventListener( 'pointermove', ( e ) => {
			if ( dragging || ( el.dataset.trigger === 'hover' && e.pointerType === 'mouse' ) ) {
				set( fromEvent( e ) );
			}
		} );
		[ 'pointerup', 'pointercancel', 'lostpointercapture' ].forEach( ( type ) =>
			stage.addEventListener( type, () => {
				dragging = false;
			} )
		);
		handle.addEventListener( 'keydown', ( e ) => {
			const keys = { ArrowLeft: -5, ArrowDown: -5, ArrowRight: 5, ArrowUp: 5, Home: -100, End: 100 };
			if ( e.key in keys ) {
				e.preventDefault();
				el.classList.remove( 'fbm-ba--animating' );
				set( current() + keys[ e.key ] );
			}
		} );

		const reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		if ( el.dataset.auto === 'true' && ! reduce && 'IntersectionObserver' in window ) {
			const start = parseFloat( el.dataset.start ) || 50;
			const io = new IntersectionObserver(
				( entries ) => {
					if ( ! entries[ 0 ].isIntersecting ) {
						return;
					}
					io.disconnect();
					el.classList.add( 'fbm-ba--animating' );
					const steps = [ Math.max( 10, start - 35 ), Math.min( 90, start + 35 ), start ];
					let i = 0;
					const next = () => {
						if ( i >= steps.length || dragging ) {
							setTimeout( () => el.classList.remove( 'fbm-ba--animating' ), 950 );
							return;
						}
						set( steps[ i++ ] );
						setTimeout( next, 1000 );
					};
					next();
				},
				{ threshold: 0.5 }
			);
			io.observe( el );
		}
	};

	const scan = ( root ) => root.querySelectorAll( '.fbm-ba' ).forEach( init );
	const start = () => {
		scan( document );
		new MutationObserver( () => scan( document ) ).observe( document.body, { childList: true, subtree: true } );
	};
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
