/**
 * Gọi REST /fbm/v2. apiFetch của WP tự gắn nonce (X-WP-Nonce).
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
	return error.message || 'Có lỗi xảy ra. Thử tải lại trang.';
}
