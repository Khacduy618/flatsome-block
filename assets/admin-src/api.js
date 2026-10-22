/**
 * Calls REST /fbm/v2. WP apiFetch adds the nonce (X-WP-Nonce).
 */
import apiFetch from '@wordpress/api-fetch';

export const cfg = window.fbmAdmin || {};

const BASE = '/fbm/v2';

export const getItems = ( type ) =>
	apiFetch( { path: `${ BASE }/items?type=${ encodeURIComponent( type ) }` } );

export const setState = ( type, id, state ) =>
	apiFetch( { path: `${ BASE }/state`, method: 'POST', data: { type, id, state } } );

export const rebuildUsage = ( offset ) =>
	apiFetch( { path: `${ BASE }/usage/rebuild`, method: 'POST', data: { offset } } );

export function errorMessage( error ) {
	if ( ! error ) {
		return '';
	}
	return error.message || 'Something went wrong. Try reloading the page.';
}
