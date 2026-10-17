import { useCallback, useEffect, useRef, useState } from '@wordpress/element';

/**
 * Tải dữ liệu bất đồng bộ: { data, error, loading, reload, setData }.
 */
export function useResource( fetcher, deps ) {
	const [ state, setState ] = useState( { data: null, error: null, loading: true } );
	const seq = useRef( 0 );

	// eslint-disable-next-line react-hooks/exhaustive-deps
	const reload = useCallback( () => {
		const id = ++seq.current;
		setState( ( s ) => ( { ...s, loading: true } ) );
		return fetcher().then(
			( data ) => {
				if ( id === seq.current ) {
					setState( { data, error: null, loading: false } );
				}
				return data;
			},
			( error ) => {
				if ( id === seq.current ) {
					setState( { data: null, error, loading: false } );
				}
			}
		);
	}, deps );

	useEffect( () => {
		reload();
	}, [ reload ] );

	const setData = useCallback( ( update ) => {
		setState( ( s ) => ( { ...s, data: typeof update === 'function' ? update( s.data ) : update } ) );
	}, [] );

	return { ...state, reload, setData };
}

export function useDebounced( value, ms = 300 ) {
	const [ debounced, setDebounced ] = useState( value );
	useEffect( () => {
		const t = setTimeout( () => setDebounced( value ), ms );
		return () => clearTimeout( t );
	}, [ value, ms ] );
	return debounced;
}

/** Router theo hash: #/blocks/12 -> ['blocks', '12']. */
export function useRoute() {
	const read = () => window.location.hash.replace( /^#\/?/, '' ).split( '/' ).filter( Boolean );
	const [ parts, setParts ] = useState( read );
	useEffect( () => {
		const onChange = () => setParts( read() );
		window.addEventListener( 'hashchange', onChange );
		return () => window.removeEventListener( 'hashchange', onChange );
	}, [] );
	return parts;
}

export function navigate( path ) {
	window.location.hash = '#/' + String( path ).replace( /^[#/]+/, '' );
}

/** Cảnh báo khi rời trang mà còn thay đổi chưa lưu. */
export function useUnsavedWarning( dirty ) {
	useEffect( () => {
		if ( ! dirty ) {
			return undefined;
		}
		const onUnload = ( e ) => {
			e.preventDefault();
			e.returnValue = '';
		};
		window.addEventListener( 'beforeunload', onUnload );
		return () => window.removeEventListener( 'beforeunload', onUnload );
	}, [ dirty ] );
}

export function formatDate( iso ) {
	if ( ! iso ) {
		return '';
	}
	const d = new Date( iso );
	if ( Number.isNaN( d.getTime() ) ) {
		return '';
	}
	const diff = ( Date.now() - d.getTime() ) / 1000;
	if ( diff >= 0 && diff < 60 ) {
		return 'vừa xong';
	}
	if ( diff >= 0 && diff < 3600 ) {
		return Math.round( diff / 60 ) + ' phút trước';
	}
	if ( diff >= 0 && diff < 86400 ) {
		return Math.round( diff / 3600 ) + ' giờ trước';
	}
	return d.toLocaleString( 'vi-VN', { dateStyle: 'short', timeStyle: 'short' } );
}

/** ISO (UTC) -> giá trị cho <input type="datetime-local"> theo giờ trình duyệt. */
export function toLocalInput( iso ) {
	if ( ! iso ) {
		return '';
	}
	const d = new Date( iso );
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }T${ pad( d.getHours() ) }:${ pad( d.getMinutes() ) }`;
}

/** Giá trị datetime-local (giờ trình duyệt) -> ISO có múi giờ. */
export function fromLocalInput( value ) {
	if ( ! value ) {
		return '';
	}
	const d = new Date( value );
	return Number.isNaN( d.getTime() ) ? '' : d.toISOString();
}
