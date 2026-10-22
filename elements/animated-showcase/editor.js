/**
 * UX Builder canvas (loaded only in the canvas iframe): Animated Showcase runs its sequence as on the site; selecting a step
 * (left tree or click on the canvas) loops just that step for preview; deselecting runs the whole list.
 * Step images / text are Angular templates, so option changes redraw the canvas instantly; the controller watches options through the
 * $rootScope digest and restarts the sequence (view.js refresh).
 *
 * Relies on UX Builder internal APIs (UxBuilder.controller, app / targets services). If anything is missing the block shows statically
 * (left image + text of the selected or first step), with no script errors.
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
			// Steps are stacked: only the visible step receives UX Builder pointer events.
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

		// Frame and step options (images, text): any change restarts the sequence so it shows instantly.
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
		// Flatsome changed its internal API: use the static mode below.
	}

	// Static mode for blocks the controller did not pick up: no sequence, shows the selected step (first by default).
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
		setTimeout( fallback, 0 ); // One tick so the controller (if any) can claim the block first.
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
