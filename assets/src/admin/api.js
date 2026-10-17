/**
 * Gọi REST /fbm/v1. apiFetch của WP tự gắn nonce và đổi PUT/PATCH/DELETE sang POST + header override.
 */
import apiFetch from '@wordpress/api-fetch';

export const cfg = window.fbmAdmin || {};

const BASE = '/fbm/v1';

function qs( params ) {
	const search = new URLSearchParams();
	Object.entries( params || {} ).forEach( ( [ key, value ] ) => {
		if ( value === undefined || value === null || value === '' ) {
			return;
		}
		if ( Array.isArray( value ) ) {
			value.forEach( ( v ) => search.append( key + '[]', v ) );
		} else {
			search.append( key, value );
		}
	} );
	const str = search.toString();
	return str ? '?' + str : '';
}

export const api = {
	get: ( path, params ) => apiFetch( { path: BASE + path + qs( params ) } ),
	post: ( path, data ) => apiFetch( { path: BASE + path, method: 'POST', data } ),
	put: ( path, data ) => apiFetch( { path: BASE + path, method: 'PUT', data } ),
	patch: ( path, data ) => apiFetch( { path: BASE + path, method: 'PATCH', data } ),
	del: ( path, params ) => apiFetch( { path: BASE + path + qs( params ), method: 'DELETE' } ),
};

export function errorMessage( error ) {
	if ( ! error ) {
		return '';
	}
	return error.message || 'Có lỗi xảy ra. Thử tải lại trang.';
}

/** Tải một object JSON xuống máy dưới dạng file. */
export function downloadJson( data, filename ) {
	const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
	const url = URL.createObjectURL( blob );
	const a = document.createElement( 'a' );
	a.href = url;
	a.download = filename;
	document.body.appendChild( a );
	a.click();
	a.remove();
	setTimeout( () => URL.revokeObjectURL( url ), 1000 );
}

export async function copyText( text ) {
	try {
		await window.navigator.clipboard.writeText( text );
		return true;
	} catch ( e ) {
		const ta = document.createElement( 'textarea' );
		ta.value = text;
		document.body.appendChild( ta );
		ta.select();
		const ok = document.execCommand( 'copy' );
		ta.remove();
		return ok;
	}
}
