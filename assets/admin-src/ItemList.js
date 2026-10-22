/**
 * One tab: search box, On/Hidden/Off totals, rows with a 3-button group.
 * Turning Off something in use asks inline first (Cancel / Hide instead / Turn off anyway).
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { cfg, errorMessage } from './api';

const LABELS = { on: 'On', hidden: 'Hidden', off: 'Off' };
const LOCKED_TITLE = 'Layout elements are always on: turning them off would break every page layout.';

const usageText = ( usage ) => {
	if ( usage === null || usage === undefined ) {
		return 'Auto';
	}
	return usage > 0 ? `${ usage } ${ usage === 1 ? 'place' : 'places' }` : 'Unused';
};

const normalize = ( s ) =>
	String( s )
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.replace( /đ/g, 'd' ); // U+0111 has no NFD decomposition: map it so diacritic-free searches match Vietnamese item names.

function StateGroup( { item, busy, onPick } ) {
	const refs = useRef( {} );
	const enabled = item.states.filter( ( s ) => ! item.locked || s === 'on' );

	const onKeyDown = ( e ) => {
		const dir = { ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 }[ e.key ];
		if ( ! dir || busy || enabled.length < 2 ) {
			return;
		}
		e.preventDefault();
		const i = enabled.indexOf( item.state );
		const next = enabled[ ( i + dir + enabled.length ) % enabled.length ];
		refs.current[ next ]?.focus();
		onPick( next );
	};

	return (
		<div
			className="fbm-seg"
			role="radiogroup"
			aria-label={ `State of ${ item.name }` }
			aria-busy={ busy }
			onKeyDown={ onKeyDown }
		>
			{ item.states.map( ( s ) => {
				// Do not disable while saving: a disabled button loses keyboard focus.
				const disabled = item.locked && s !== 'on';
				const checked = item.state === s;
				return (
					<button
						key={ s }
						ref={ ( el ) => ( refs.current[ s ] = el ) }
						type="button"
						role="radio"
						data-v={ s }
						aria-checked={ checked }
						tabIndex={ checked ? 0 : -1 }
						disabled={ disabled }
						title={ item.locked && s !== 'on' ? LOCKED_TITLE : undefined }
						onClick={ () => ! checked && ! busy && onPick( s ) }
					>
						{ LABELS[ s ] }
					</button>
				);
			} ) }
		</div>
	);
}

function Confirm( { item, onCancel, onHide, onOff } ) {
	const first = useRef();
	useEffect( () => first.current?.focus(), [] );
	return (
		<div className="fbm-confirm" role="alertdialog" aria-labelledby={ `fbm-cf-${ item.id }` }>
			<p id={ `fbm-cf-${ item.id }` }>
				<b>Turn off “{ item.name }”?</b> It is used in { item.usage } place(s). Once Off,
				those places are empty for guests; editors see a dashed placeholder.
				{ item.states.includes( 'hidden' ) &&
					' To only stop it from being picked again, choose Hidden.' }
			</p>
			<div className="fbm-confirm__acts">
				<button ref={ first } type="button" className="components-button is-secondary" onClick={ onCancel }>
					Cancel
				</button>
				{ item.states.includes( 'hidden' ) && (
					<button type="button" className="components-button is-secondary" onClick={ onHide }>
						Hide instead
					</button>
				) }
				<button type="button" className="components-button is-primary is-destructive" onClick={ onOff }>
					Turn off anyway
				</button>
			</div>
		</div>
	);
}

const PER_PAGE = 10;

function Pager( { page, pages, total, onPage } ) {
	if ( pages < 2 ) {
		return null;
	}
	const from = ( page - 1 ) * PER_PAGE + 1;
	const to = Math.min( total, page * PER_PAGE );
	return (
		<nav className="fbm-pager" aria-label="Pagination">
			<span className="fbm-pager__info">
				{ from }–{ to } / { total }
			</span>
			<button type="button" className="components-button is-secondary is-small" disabled={ page === 1 } onClick={ () => onPage( page - 1 ) } aria-label="Previous page">
				‹
			</button>
			{ Array.from( { length: pages }, ( _, i ) => i + 1 ).map( ( n ) => (
				<button
					key={ n }
					type="button"
					className={ `components-button is-small ${ n === page ? 'is-primary' : 'is-secondary' }` }
					aria-current={ n === page ? 'page' : undefined }
					aria-label={ `Page ${ n }` }
					onClick={ () => onPage( n ) }
				>
					{ n }
				</button>
			) ) }
			<button type="button" className="components-button is-secondary is-small" disabled={ page === pages } onClick={ () => onPage( page + 1 ) } aria-label="Next page">
				›
			</button>
		</nav>
	);
}

export default function ItemList( { type, data, searchLabel, note, onChange } ) {
	const [ q, setQ ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ confirming, setConfirming ] = useState( null );
	const [ busy, setBusy ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ copiedId, setCopiedId ] = useState( null );

	const copyShortcode = ( text, id ) => {
		if ( ! text ) {
			return;
		}
		const onSuccess = () => {
			setCopiedId( id );
			setTimeout( () => setCopiedId( null ), 1500 );
		};
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( onSuccess ).catch( () => {
				fallbackCopy( text );
				onSuccess();
			} );
		} else {
			fallbackCopy( text );
			onSuccess();
		}
	};

	const fallbackCopy = ( text ) => {
		const ta = document.createElement( 'textarea' );
		ta.value = text;
		ta.style.position = 'fixed';
		ta.style.opacity = '0';
		document.body.appendChild( ta );
		ta.select();
		document.execCommand( 'copy' );
		document.body.removeChild( ta );
	};

	const apply = ( item, state ) => {
		setConfirming( null );
		setBusy( item.id );
		setError( '' );
		onChange( item.id, state )
			.catch( ( e ) => setError( errorMessage( e ) ) )
			.finally( () => setBusy( null ) );
	};

	const pick = ( item, state ) => {
		if ( state === 'off' && item.usage > 0 ) {
			setConfirming( item.id );
			return;
		}
		apply( item, state );
	};

	const needle = normalize( q.trim() );
	const items = needle
		? data.items.filter( ( it ) =>
				normalize( `${ it.name } ${ it.sub } ${ it.category }` ).includes( needle )
		  )
		: data.items;

	// 10 rows per page; the current page never exceeds the page count (search can shorten the list).
	const pages = Math.max( 1, Math.ceil( items.length / PER_PAGE ) );
	const current = Math.min( page, pages );
	const shown = items.slice( ( current - 1 ) * PER_PAGE, current * PER_PAGE );

	const totals = { on: 0, hidden: 0, off: 0 };
	data.items.forEach( ( it ) => ( totals[ it.state ] += 1 ) );

	if ( type === 'flatsome' && data.catalog_empty ) {
		return (
			<div className="fbm-pane">
				<Notice status="info" isDismissible={ false }>
					Open UX Builder once so the plugin can read Flatsome's element list.{ ' ' }
					<a href={ cfg.uxBuilderUrl }>Open UX Builder</a>
				</Notice>
			</div>
		);
	}

	return (
		<div className="fbm-pane">
			<div className="fbm-toolbar">
				<input
					className="fbm-search"
					type="search"
					placeholder={ searchLabel }
					aria-label={ searchLabel }
					value={ q }
					onChange={ ( e ) => {
						setQ( e.target.value );
						setPage( 1 );
					} }
				/>
				<div className="fbm-summary">
					<span><b>{ totals.on }</b> on</span>
					<span><b>{ totals.hidden }</b> hidden</span>
					<span><b>{ totals.off }</b> off</span>
				</div>
			</div>
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ items.length === 0 ? (
				<p className="fbm-empty">
					{ data.items.length === 0 ? 'No items yet.' : 'No matching items.' }
				</p>
			) : (
				<ul className="fbm-list">
					{ shown.map( ( it ) => (
						<li key={ it.id } className={ `fbm-row is-${ it.state }` }>
							<div className="fbm-row__main">
								<div className="fbm-row__name">
									<b>{ it.name }</b>
									<small>
										<code
											className={ `fbm-code-copyable ${ copiedId === it.id ? 'is-copied' : '' }` }
											title="Double-click to copy"
											onDoubleClick={ () => copyShortcode( it.sub, it.id ) }
										>
											{ copiedId === it.id ? '✓ Copied!' : it.sub }
										</code>
										{ it.category && <span>{ it.category }</span> }
										{ it.locked && (
											<span className="fbm-lock">
												<span className="dashicons dashicons-lock" aria-hidden="true" />
												khung trang
											</span>
										) }
									</small>
								</div>
								<span className="fbm-row__usage">{ usageText( it.usage ) }</span>
								<StateGroup
									item={ it }
									busy={ busy === it.id }
									onPick={ ( s ) => pick( it, s ) }
								/>
							</div>
							{ confirming === it.id && (
								<Confirm
									item={ it }
									onCancel={ () => setConfirming( null ) }
									onHide={ () => apply( it, 'hidden' ) }
									onOff={ () => apply( it, 'off' ) }
								/>
							) }
						</li>
					) ) }
				</ul>
			) }
			<Pager page={ current } pages={ pages } total={ items.length } onPage={ setPage } />
			<p className="fbm-note">{ note }</p>
		</div>
	);
}
