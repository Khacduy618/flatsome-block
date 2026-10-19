import { createRoot, render } from '@wordpress/element';
import App from './App';
import './style.scss';

const mount = () => {
	const el = document.getElementById( 'fbm-app' );
	if ( ! el ) {
		return;
	}
	if ( createRoot ) {
		createRoot( el ).render( <App /> );
	} else {
		render( <App />, el );
	}
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
