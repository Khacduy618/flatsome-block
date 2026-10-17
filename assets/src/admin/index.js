import { createRoot, render } from '@wordpress/element';
import App from './App';
import { NoticesProvider } from './design-system';
import './style.scss';

const mount = () => {
	const el = document.getElementById( 'fbm-app' );
	if ( ! el ) {
		return;
	}
	const app = (
		<NoticesProvider>
			<App />
		</NoticesProvider>
	);
	if ( createRoot ) {
		createRoot( el ).render( app );
	} else {
		render( app, el );
	}
};

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', mount );
} else {
	mount();
}
