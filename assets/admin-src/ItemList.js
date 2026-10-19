/**
 * Một tab: ô tìm, tổng Bật/Ẩn/Tắt, danh sách dòng có nhóm 3 nút.
 * Tắt thứ đang được dùng thì hỏi lại ngay trong trang (Huỷ / Ẩn thay vì Tắt / Vẫn Tắt).
 */
import { useEffect, useRef, useState } from '@wordpress/element';
import { Notice } from '@wordpress/components';
import { cfg, errorMessage } from './api';

const LABELS = { on: 'Bật', hidden: 'Ẩn', off: 'Tắt' };
const LOCKED_TITLE = 'Element khung trang luôn bật: tắt sẽ làm vỡ bố cục mọi trang.';

const usageText = ( usage ) => {
	if ( usage === null || usage === undefined ) {
		return 'Tự động';
	}
	return usage > 0 ? `${ usage } nơi` : 'Chưa dùng';
};

const normalize = ( s ) =>
	String( s )
		.toLowerCase()
		.normalize( 'NFD' )
		.replace( /[̀-ͯ]/g, '' )
		.replace( /đ/g, 'd' );

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
			aria-label={ `Trạng thái ${ item.name }` }
			aria-busy={ busy }
			onKeyDown={ onKeyDown }
		>
			{ item.states.map( ( s ) => {
				// Không disable khi đang lưu: nút bị disable sẽ làm mất focus bàn phím.
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
				<b>Tắt “{ item.name }”?</b> Đang được dùng ở { item.usage } nơi. Sau khi Tắt,
				những chỗ đó trống với khách; người biên tập thấy khung nét đứt.
				{ item.states.includes( 'hidden' ) &&
					' Nếu chỉ muốn không cho chọn thêm, chọn Ẩn.' }
			</p>
			<div className="fbm-confirm__acts">
				<button ref={ first } type="button" className="components-button is-secondary" onClick={ onCancel }>
					Huỷ
				</button>
				{ item.states.includes( 'hidden' ) && (
					<button type="button" className="components-button is-secondary" onClick={ onHide }>
						Ẩn thay vì Tắt
					</button>
				) }
				<button type="button" className="components-button is-primary is-destructive" onClick={ onOff }>
					Vẫn Tắt
				</button>
			</div>
		</div>
	);
}

export default function ItemList( { type, data, searchLabel, note, onChange } ) {
	const [ q, setQ ] = useState( '' );
	const [ confirming, setConfirming ] = useState( null );
	const [ busy, setBusy ] = useState( null );
	const [ error, setError ] = useState( '' );

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

	const totals = { on: 0, hidden: 0, off: 0 };
	data.items.forEach( ( it ) => ( totals[ it.state ] += 1 ) );

	if ( type === 'flatsome' && data.catalog_empty ) {
		return (
			<div className="fbm-pane">
				<Notice status="info" isDismissible={ false }>
					Mở UX Builder một lần để plugin đọc danh sách element của Flatsome.{ ' ' }
					<a href={ cfg.uxBuilderUrl }>Mở UX Builder</a>
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
					onChange={ ( e ) => setQ( e.target.value ) }
				/>
				<div className="fbm-summary">
					<span><b>{ totals.on }</b> bật</span>
					<span><b>{ totals.hidden }</b> ẩn</span>
					<span><b>{ totals.off }</b> tắt</span>
				</div>
			</div>
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ items.length === 0 ? (
				<p className="fbm-empty">
					{ data.items.length === 0 ? 'Chưa có mục nào.' : 'Không có mục khớp.' }
				</p>
			) : (
				<ul className="fbm-list">
					{ items.map( ( it ) => (
						<li key={ it.id } className={ `fbm-row is-${ it.state }` }>
							<div className="fbm-row__main">
								<div className="fbm-row__name">
									<b>{ it.name }</b>
									<small>
										<code>{ it.sub }</code>
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
			<p className="fbm-note">{ note }</p>
		</div>
	);
}
