/**
 * Look (Shop The Look): bấm pin → hiện thẻ sản phẩm tương ứng; rê chuột / focus vào thẻ → pin phát sáng.
 * Mỗi section.fbm-stl khởi tạo một lần; theo dõi phần tử chèn sau (canvas UX Builder, slider).
 */
( () => {
	const mobile = () => window.matchMedia && window.matchMedia( '(max-width: 549px)' ).matches;
	const reduced = () => window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	const init = ( root ) => {
		if ( root.dataset.fbmReady ) {
			return;
		}
		root.dataset.fbmReady = '1';
		// Canvas UX Builder: Look được vẽ riêng nên chữ trên nút lấy từ cài đặt chung của Shop The Look.
		const shared = root.closest( '[data-button-text]' );
		if ( shared && shared.dataset.buttonText ) {
			root.querySelectorAll( '.fbm-stl__card-btn' ).forEach( ( btn ) => ( btn.textContent = shared.dataset.buttonText ) );
		}
		const pins = () => Array.from( root.querySelectorAll( '.fbm-stl__pin' ) );
		const cards = () => Array.from( root.querySelectorAll( '.fbm-stl__card' ) );

		const select = ( index, scroll ) => {
			pins().forEach( ( pin, i ) => {
				const on = i === index;
				pin.classList.toggle( 'is-active', on );
				pin.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			root.querySelectorAll( '.fbm-stl__dot' ).forEach( ( dot, i ) => {
				dot.classList.toggle( 'is-active', i === index );
				dot.setAttribute( 'aria-pressed', i === index ? 'true' : 'false' );
			} );
			let shown = null;
			cards().forEach( ( card, i ) => {
				card.hidden = i !== index;
				if ( i === index ) {
					shown = card;
				}
			} );
			if ( scroll && shown && mobile() ) {
				const r = shown.getBoundingClientRect();
				if ( r.top < 0 || r.bottom > window.innerHeight ) {
					shown.scrollIntoView( { block: 'nearest', behavior: reduced() ? 'auto' : 'smooth' } );
				}
			}
		};

		const highlight = ( index ) => {
			pins().forEach( ( pin, i ) => pin.classList.toggle( 'is-highlight', i === index ) );
		};

		root.addEventListener( 'click', ( e ) => {
			const dot = e.target.closest( '.fbm-stl__dot' );
			if ( dot && root.contains( dot ) ) {
				e.preventDefault();
				select( Array.from( root.querySelectorAll( '.fbm-stl__dot' ) ).indexOf( dot ), false );
				return;
			}
			const pin = e.target.closest( '.fbm-stl__pin' );
			if ( ! pin || ! root.contains( pin ) ) {
				return;
			}
			e.preventDefault();
			select( pins().indexOf( pin ), true );
		} );

		const cardIndex = ( e ) => {
			const card = e.target.closest && e.target.closest( '.fbm-stl__card' );
			return card && root.contains( card ) ? cards().indexOf( card ) : -1;
		};
		root.addEventListener( 'mouseover', ( e ) => highlight( cardIndex( e ) ) );
		root.addEventListener( 'focusin', ( e ) => highlight( cardIndex( e ) ) );
		root.addEventListener( 'mouseleave', () => highlight( -1 ) );
		root.addEventListener( 'focusout', ( e ) => {
			if ( ! root.contains( e.relatedTarget ) ) {
				highlight( -1 );
			}
		} );
	};

	const scan = () => document.querySelectorAll( '.fbm-stl' ).forEach( init );
	const start = () => {
		scan();
		new MutationObserver( scan ).observe( document.body, { childList: true, subtree: true } );
	};
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', start, { once: true } );
	} else {
		start();
	}
} )();
