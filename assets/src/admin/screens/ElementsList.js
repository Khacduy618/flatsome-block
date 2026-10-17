import { useState } from '@wordpress/element';
import { Button, Modal, Notice, SelectControl, TextControl } from '@wordpress/components';
import { api, cfg, errorMessage } from '../api';
import { formatDate, navigate, useResource } from '../hooks';
import { EmptyState, ErrorNotice, HelpTip, Label, Loading, PageHeader, StatusBadge, useNotice } from '../design-system';

export default function ElementsList() {
	const list = useResource( () => api.get( '/elements' ), [] );
	const [ creating, setCreating ] = useState( false );

	if ( ! cfg.user.canEditCode ) {
		return (
			<Notice status="warning" isDismissible={ false }>
				Chỉ Administrator có quyền unfiltered_html mới viết được element. Nếu wp-config.php đặt DISALLOW_FILE_EDIT, chức năng này bị tắt.
			</Notice>
		);
	}

	const items = list.data || [];
	return (
		<div className="fbm-stack">
			<PageHeader
				title="Elements"
				count={ list.data ? items.length : undefined }
				actions={
					<Button variant="primary" onClick={ () => setCreating( true ) }>
						+ Tạo element
					</Button>
				}
			/>
			{ cfg.safeMode && (
				<Notice status="warning" isDismissible={ false }>
					Safe mode đang bật: mọi element đều tạm tắt ngoài site. Tắt trong Cài đặt.
				</Notice>
			) }
			<ErrorNotice error={ list.error } onRetry={ list.reload } />
			{ list.loading && ! list.data ? (
				<Loading />
			) : items.length === 0 ? (
				<EmptyState
					title="Chưa có element nào"
					action={
						<Button variant="primary" onClick={ () => setCreating( true ) }>
							Tạo element từ mẫu
						</Button>
					}
				>
					Element là một khối kéo-thả mới trong UX Builder và Gutenberg, viết bằng PHP + CSS + JS. Bắt đầu từ mẫu "Hộp chào" hoặc "Before / After".
				</EmptyState>
			) : (
				<div className="fbm-table-wrap">
					<table className="fbm-table">
						<thead>
							<tr>
								<th>Element</th>
								<th>Trạng thái</th>
								<th>Bản nháp</th>
								<th>Xuất hiện ở</th>
								<th>Sửa lúc</th>
							</tr>
						</thead>
						<tbody>
							{ items.map( ( el ) => (
								<tr key={ el.id } className={ el.state === 'active' ? '' : 'is-off' }>
									<td>
										<a className="fbm-row-title" href={ `#/elements/${ el.id }` }>
											{ el.name }
										</a>
										<div className="fbm-row-sub">
											<span className="fbm-sc">{ el.shortcode }</span>
											<span className="fbm-muted">{ el.block_name }</span>
										</div>
									</td>
									<td>
										{ el.live_version ? (
											<span className="fbm-badges">
												<StatusBadge status={ el.state } />
												<span className="fbm-chip">v{ el.live_version }</span>
											</span>
										) : (
											<StatusBadge status="draft" label="Chưa publish" />
										) }
									</td>
									<td>{ el.dirty ? <StatusBadge status={ el.check_status } /> : <span className="fbm-muted">Trùng bản Live</span> }</td>
									<td>{ el.targets.map( ( t ) => ( t === 'uxbuilder' ? 'UX Builder' : 'Gutenberg' ) ).join( ' · ' ) }</td>
									<td className="fbm-nowrap">{ formatDate( el.modified ) }</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }
			{ creating && <CreateElement onClose={ () => setCreating( false ) } /> }
		</div>
	);
}

function CreateElement( { onClose } ) {
	const notice = useNotice();
	const starters = useResource( () => api.get( '/elements/starters' ), [] );
	const [ starter, setStarter ] = useState( '' );
	const [ name, setName ] = useState( '' );
	const [ tag, setTag ] = useState( '' );
	const [ category, setCategory ] = useState( 'Content' );
	const [ error, setError ] = useState( '' );
	const [ busy, setBusy ] = useState( false );

	const pick = ( s ) => {
		setStarter( s ? s.key : '' );
		setName( s ? s.name : '' );
		setTag( s ? s.tag : '' );
		setCategory( s ? s.category : 'Content' );
	};

	const submit = async ( e ) => {
		e.preventDefault();
		setBusy( true );
		setError( '' );
		try {
			const el = await api.post( '/elements', { starter, name, tag, category } );
			( el.notes || [] ).forEach( ( n ) => notice( n, { timeout: 10000 } ) );
			navigate( `elements/${ el.id }` );
		} catch ( err ) {
			setError( errorMessage( err ) );
			setBusy( false );
		}
	};

	return (
		<Modal title="Tạo element" onRequestClose={ onClose } className="fbm-modal fbm-modal--wide">
			<form className="fbm-stack" onSubmit={ submit }>
				<div className="fbm-starters">
					<button type="button" className={ `fbm-starter${ starter === '' ? ' is-selected' : '' }` } onClick={ () => pick( null ) }>
						<b>Trống</b>
						<span>Một option tiêu đề + template tối giản.</span>
					</button>
					{ ( starters.data || [] ).map( ( s ) => (
						<button type="button" key={ s.key } className={ `fbm-starter${ starter === s.key ? ' is-selected' : '' }` } onClick={ () => pick( s ) }>
							<b>{ s.name }</b>
							<span>{ s.description }</span>
							<code>[{ s.tag }]</code>
						</button>
					) ) }
				</div>
				<div className="fbm-grid-3">
					<TextControl __nextHasNoMarginBottom label="Tên element" value={ name } onChange={ setName } required />
					<div>
						<Label help="tag" htmlFor="fbm-tag">
							Tag shortcode
						</Label>
						<input
							id="fbm-tag"
							className="fbm-input fbm-mono"
							value={ tag }
							placeholder="hero_countdown"
							onChange={ ( e ) => setTag( e.target.value.toLowerCase().replace( /[^a-z0-9_]/g, '_' ) ) }
							required
						/>
					</div>
					<SelectControl
						__nextHasNoMarginBottom
						label="Nhóm trong UX Builder"
						value={ category }
						onChange={ setCategory }
						options={ [ 'Content', 'Layout', 'Media', 'Shop', 'Elements' ].map( ( c ) => ( { label: c, value: c } ) ) }
					/>
				</div>
				{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
				<div className="fbm-modal__actions">
					<Button variant="tertiary" onClick={ onClose }>
						Huỷ
					</Button>
					<Button variant="primary" type="submit" isBusy={ busy } disabled={ busy || ! name || ! tag }>
						Tạo và mở editor
					</Button>
				</div>
				<p className="fbm-muted">
					Mới viết element lần đầu? <a href="#/guide/quick-start">Xem hướng dẫn 5 bước</a>
					<HelpTip topic="tag" />
				</p>
			</form>
		</Modal>
	);
}
