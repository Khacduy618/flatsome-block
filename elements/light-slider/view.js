/**
 * Light Slider: infinite right-to-left slider, zero library.
 *
 * Loop by DOM rotation: slide the track left, then (same frame, no transition) move the slides that left the
 * viewport to the end of the track and reset the transform. At rest the transform is always 0, so the last
 * slide is followed by the first one sliding in from the right with no rewind and no cloned nodes.
 *
 * The UX Builder canvas (.is-canvas) is never touched: Angular owns the child order there.
 *
 * @package FlatsomeBlockManager
 */
( () => {
	'use strict';

	const reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	// Load Flatsome lazy images early: slides outside the clipped viewport never reach its observer.
	const prime = ( el ) => {
		if ( ! el ) {
			return;
		}
		el.querySelectorAll( 'img.lazy-load:not(.lazy-load-active)' ).forEach( ( img ) => {
			img.classList.add( 'lazy-load-active' );
			img.addEventListener( 'load', () => img.classList.remove( 'lazy-load' ), { once: true } );
			if ( img.dataset.srcset ) {
				img.srcset = img.dataset.srcset;
			}
			if ( img.dataset.src ) {
				img.src = img.dataset.src;
			}
		} );
	};

	function init( root ) {
		const track = root.querySelector( '.fbm-ls__track' );
		if ( ! track || root.dataset.fbmLsReady || track.children.length < 2 ) {
			return;
		}
		root.dataset.fbmLsReady = '1';

		let cfg = {};
		try {
			cfg = JSON.parse( root.dataset.fbmLs || '{}' );
		} catch ( e ) {}
		cfg = Object.assign( { step: 1, autoplay: true, timer: 3000, speed: 500, pause: true }, cfg );

		const dots = root.querySelector( '.flickity-page-dots' );
		const count = track.children.length;
		let index = 0;
		let goal = null;
		let busy = false;
		let pending = 0;
		let timer = 0;
		let fallback = 0;
		let hold = false;
		let inView = true;

		const cols = () => Math.ceil( parseFloat( getComputedStyle( root ).getPropertyValue( '--fbm-ls-cur' ) ) || 1 );
		const max = () => count - cols();
		const step = () => Math.max( 1, Math.min( cfg.step, max() ) );
		const enabled = () => max() >= 1;
		// Real layout distance between slot 0 and slot d: no sub-pixel drift with fractional columns.
		const dist = ( d ) => track.children[ d ].getBoundingClientRect().left - track.children[ 0 ].getBoundingClientRect().left;
		const move = ( x ) => ( track.style.transform = 'translate3d(' + x + 'px,0,0)' );
		const primeNext = () => {
			for ( let i = 0; i < step(); i++ ) {
				prime( track.children[ cols() + i ] );
			}
		};

		const jump = ( mutate, x ) => {
			track.style.transition = 'none';
			mutate();
			move( x() );
			void track.offsetWidth; // Commit the instant position before the transition comes back.
			track.style.transition = '';
		};

		const shortest = ( target ) => {
			let d = target - index;
			if ( d > count / 2 ) {
				d -= count;
			}
			if ( d < -count / 2 ) {
				d += count;
			}
			return d;
		};

		function go( d ) {
			if ( busy || ! d || ! enabled() ) {
				return;
			}
			d = Math.max( -max(), Math.min( max(), d ) );
			busy = true;
			pending = d;
			clearTimeout( timer );
			if ( d > 0 ) {
				for ( let i = 0; i < d; i++ ) {
					prime( track.children[ cols() + i ] );
				}
				move( -dist( d ) );
			} else {
				jump(
					() => {
						for ( let i = 0; i < -d; i++ ) {
							track.prepend( track.lastElementChild );
						}
					},
					() => -dist( -d )
				);
				for ( let i = 0; i < -d; i++ ) {
					prime( track.children[ i ] );
				}
				move( 0 );
			}
			fallback = setTimeout( done, reduce ? 0 : cfg.speed + 100 );
		}

		function done( e ) {
			if ( e && ( e.target !== track || 'transform' !== e.propertyName ) ) {
				return;
			}
			if ( ! busy ) {
				return;
			}
			clearTimeout( fallback );
			if ( pending > 0 ) {
				jump(
					() => {
						for ( let i = 0; i < pending; i++ ) {
							track.append( track.firstElementChild );
						}
					},
					() => 0
				);
			}
			index = ( index + pending + count ) % count;
			busy = false;
			pending = 0;
			paint();
			primeNext();
			if ( null !== goal && goal !== index ) {
				go( shortest( goal ) );
				return;
			}
			goal = null;
			schedule();
		}

		function schedule() {
			clearTimeout( timer );
			if ( cfg.autoplay && ! reduce && ! hold && inView && ! document.hidden && enabled() ) {
				timer = setTimeout( () => go( step() ), cfg.timer );
			}
		}

		function paint() {
			if ( ! dots ) {
				return;
			}
			const active = Math.floor( index / cfg.step );
			Array.from( dots.children ).forEach( ( dot, i ) => dot.classList.toggle( 'is-selected', i === active ) );
		}

		if ( dots ) {
			for ( let i = 0; i < Math.ceil( count / cfg.step ); i++ ) {
				const dot = document.createElement( 'li' );
				dot.className = 'dot';
				dot.setAttribute( 'aria-label', 'Page dot ' + ( i + 1 ) );
				dot.addEventListener( 'click', () => {
					goal = Math.min( i * cfg.step, count - 1 );
					go( shortest( goal ) );
				} );
				dots.appendChild( dot );
			}
			paint();
		}

		track.addEventListener( 'transitionend', done );
		const next = root.querySelector( '.flickity-prev-next-button.next' );
		const prev = root.querySelector( '.flickity-prev-next-button.previous' );
		if ( next ) {
			next.addEventListener( 'click', () => go( step() ) );
		}
		if ( prev ) {
			prev.addEventListener( 'click', () => go( -step() ) );
		}

		const pause = ( on ) => () => {
			if ( ! cfg.pause ) {
				return;
			}
			hold = on;
			if ( on ) {
				clearTimeout( timer );
			} else if ( ! busy ) {
				schedule();
			}
		};

		let x0 = 0;
		track.addEventListener(
			'touchstart',
			( e ) => {
				x0 = e.changedTouches[ 0 ].clientX;
				pause( true )();
			},
			{ passive: true }
		);
		track.addEventListener(
			'touchend',
			( e ) => {
				const dx = e.changedTouches[ 0 ].clientX - x0;
				pause( false )();
				if ( Math.abs( dx ) > 40 ) {
					go( dx < 0 ? step() : -step() );
				}
			},
			{ passive: true }
		);
		root.addEventListener( 'mouseenter', pause( true ) );
		root.addEventListener( 'mouseleave', pause( false ) );
		root.addEventListener( 'focusin', pause( true ) );
		root.addEventListener( 'focusout', pause( false ) );

		document.addEventListener( 'visibilitychange', () => ! busy && schedule() );
		new IntersectionObserver( ( [ entry ] ) => {
			inView = entry.isIntersecting;
			if ( ! busy ) {
				schedule();
			}
		} ).observe( root );
		// At rest the transform is 0, so a resize only toggles static mode (too few slides for this breakpoint).
		new ResizeObserver( () => {
			root.classList.toggle( 'is-static', ! enabled() );
			if ( ! busy ) {
				schedule();
			}
		} ).observe( root );

		primeNext();
	}

	const boot = () => document.querySelectorAll( '.fbm-ls:not(.is-canvas)' ).forEach( init );
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', boot, { once: true } );
	} else {
		boot();
	}
} )();
