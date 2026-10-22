/**
 * Animated Showcase UX Block: per-step sequence (.fbm-as__item)
 *   1. the left image glides in (.is-s1)  2. meanwhile the text types out, finishing when the left image stops (.is-s2)
 *   3. once the left image is in → the right image slides up and replaces the text (.is-s3)
 *   4. push 1: the third image enters on the right, the right image moves left (.is-s4)
 *   5. push 2: the original left image re-enters from the right, the third image moves left (.is-s5) → back to the start
 * Each step runs twice, then the next step; after the last it returns to the first, forever. Effects live in CSS; JS only times the class changes.
 * Pauses when scrolled off screen / tab hidden; with "reduced motion" it stays static.
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

		// Jump to the start of the sequence instantly (no reverse animation).
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
			const enter = d * 1.5; // Left image entrance (matches calc( var(--fbm-as-dur) * 1.5 ) in CSS).
			// Text types while the left image slides and is complete when it stops: at most 60 ms per character.
			const per = chars ? Math.min( 60, ( enter - d / 2 ) / chars ) : 0;
			item.style.setProperty( '--fbm-as-char', per + 'ms' );
			const t1 = 50;
			const t3 = t1 + enter; // The right image enters as soon as the left image is in.
			const t4 = t3 + d + g;
			const t5 = t4 + d + g;
			const end = t5 + d + g;
			at( t1, () => item.classList.add( 'is-s1', 'is-s2' ) );
			at( t3, () => item.classList.add( 'is-s3' ) );
			at( t4, () => item.classList.add( 'is-s4' ) );
			at( t5, () => item.classList.add( 'is-s5' ) );
			// End of a run: fade out, then replay (run 2) or move to the next step.
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
			// Canvas: loop only the selected step (-1: play the whole list).
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

	// Initialize blocks newly inserted into the page; blocks whose steps / text just changed (UX Builder canvas) restart from the beginning.
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
