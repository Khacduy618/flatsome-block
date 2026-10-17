import { useState } from '@wordpress/element';
import { Button, CheckboxControl, Notice, SelectControl } from '@wordpress/components';
import { api, cfg, downloadJson, errorMessage } from '../api';
import { useDebounced, useResource } from '../hooks';
import { HelpTip, Label, Loading, PageHeader, Panel, StatusBadge, useNotice } from '../design-system';

const ACTION_LABEL = {
	create: [ 'on', 'Tạo mới' ],
	rename: [ 'sched', 'Tạo bản mới' ],
	overwrite: [ 'warn', 'Ghi đè' ],
	skip: [ 'off', 'Bỏ qua' ],
	error: [ 'bad', 'Lỗi' ],
};

function ActionBadge( { action } ) {
	const [ tone, label ] = ACTION_LABEL[ action ] || [ 'off', action ];
	return <span className={ `fbm-badge fbm-badge--${ tone }` }>{ label }</span>;
}

export default function Transfer() {
	return (
		<div className="fbm-stack">
			<PageHeader title="Xuất / Nhập" />
			<div className="fbm-grid-2">
				<ExportPanel />
				<ImportPanel />
			</div>
		</div>
	);
}

function ExportPanel() {
	const notice = useNotice();
	const [ search, setSearch ] = useState( '' );
	const q = useDebounced( search, 300 );
	const blocks = useResource( () => api.get( '/blocks', { search: q, per_page: 100, orderby: 'title', order: 'asc' } ), [ q ] );
	const elements = useResource( () => ( cfg.user.canEditCode && cfg.modules.elements ? api.get( '/elements' ) : Promise.resolve( [] ) ), [] );
	const [ blockIds, setBlockIds ] = useState( [] );
	const [ elementIds, setElementIds ] = useState( [] );
	const [ withDeps, setWithDeps ] = useState( true );
	const [ busy, setBusy ] = useState( false );

	const toggle = ( list, set, id ) => set( list.includes( id ) ? list.filter( ( x ) => x !== id ) : [ ...list, id ] );

	const run = async () => {
		setBusy( true );
		try {
			const pkg = await api.post( '/transfer/export', { block_ids: blockIds, element_ids: elementIds, with_deps: withDeps } );
			const stamp = new Date().toISOString().slice( 0, 10 );
			downloadJson( pkg, `fbm-export-${ stamp }.json` );
			notice( `Đã xuất ${ pkg.blocks.length } block, ${ pkg.elements.length } element` );
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
		setBusy( false );
	};

	return (
		<Panel title="Xuất">
			<input type="search" className="fbm-input" placeholder="Tìm block…" value={ search } onChange={ ( e ) => setSearch( e.target.value ) } aria-label="Tìm block để xuất" />
			<div className="fbm-pick-list">
				{ blocks.loading && ! blocks.data && <Loading /> }
				{ ( blocks.data ? blocks.data.items : [] ).map( ( b ) => (
					<CheckboxControl
						__nextHasNoMarginBottom
						key={ b.id }
						label={ `${ b.title } (${ b.slug })` }
						checked={ blockIds.includes( b.id ) }
						onChange={ () => toggle( blockIds, setBlockIds, b.id ) }
					/>
				) ) }
			</div>
			{ ( elements.data || [] ).length > 0 && (
				<>
					<span className="fbm-field-label">Element</span>
					<div className="fbm-pick-list fbm-pick-list--short">
						{ elements.data.map( ( el ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ el.id }
								label={ `${ el.name } [${ el.tag }]` }
								checked={ elementIds.includes( el.id ) }
								onChange={ () => toggle( elementIds, setElementIds, el.id ) }
							/>
						) ) }
					</div>
				</>
			) }
			<span className="fbm-inline">
				<CheckboxControl __nextHasNoMarginBottom label="Kèm block lồng bên trong và element đang dùng" checked={ withDeps } onChange={ setWithDeps } />
				<HelpTip topic="withDeps" />
			</span>
			<div>
				<Button variant="primary" onClick={ run } isBusy={ busy } disabled={ busy || ( ! blockIds.length && ! elementIds.length ) }>
					Tải file .json ({ blockIds.length } block, { elementIds.length } element)
				</Button>
			</div>
			<p className="fbm-hint">Ảnh trong block không đi kèm file: block giữ ID ảnh của site cũ.</p>
		</Panel>
	);
}

function ImportPanel() {
	const notice = useNotice();
	const [ pkg, setPkg ] = useState( null );
	const [ fileName, setFileName ] = useState( '' );
	const [ conflict, setConflict ] = useState( 'rename' );
	const [ preview, setPreview ] = useState( null );
	const [ result, setResult ] = useState( null );
	const [ error, setError ] = useState( '' );
	const [ busy, setBusy ] = useState( false );
	const [ over, setOver ] = useState( false );

	const dry = async ( p, c ) => {
		setBusy( true );
		setError( '' );
		try {
			setPreview( await api.post( '/transfer/import', { package: p, conflict: c, dry_run: true } ) );
		} catch ( e ) {
			setError( errorMessage( e ) );
			setPreview( null );
		}
		setBusy( false );
	};

	const readFile = ( file ) => {
		if ( ! file ) {
			return;
		}
		setResult( null );
		setFileName( file.name );
		const reader = new window.FileReader();
		reader.onload = () => {
			try {
				const data = JSON.parse( reader.result );
				setPkg( data );
				dry( data, conflict );
			} catch ( e ) {
				setError( 'File không phải JSON hợp lệ.' );
				setPkg( null );
				setPreview( null );
			}
		};
		reader.readAsText( file );
	};

	const run = async () => {
		setBusy( true );
		try {
			const res = await api.post( '/transfer/import', { package: pkg, conflict, dry_run: false } );
			setResult( res );
			setPreview( null );
			notice( `Đã nhập ${ res.blocks.filter( ( b ) => b.action !== 'skip' ).length } block, ${ res.elements.filter( ( e ) => e.action !== 'skip' ).length } element` );
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
		setBusy( false );
	};

	const shown = result || preview;
	const count = shown ? shown.blocks.filter( ( b ) => b.action !== 'skip' ).length + shown.elements.filter( ( e ) => e.action !== 'skip' ).length : 0;

	return (
		<Panel title="Nhập">
			<label
				className={ `fbm-drop${ over ? ' is-over' : '' }` }
				onDragOver={ ( e ) => {
					e.preventDefault();
					setOver( true );
				} }
				onDragLeave={ () => setOver( false ) }
				onDrop={ ( e ) => {
					e.preventDefault();
					setOver( false );
					readFile( e.dataTransfer.files[ 0 ] );
				} }
			>
				<b>{ fileName || 'Kéo file .json vào đây' }</b>
				<span>hoặc bấm để chọn file</span>
				<input type="file" accept=".json,application/json" className="screen-reader-text" onChange={ ( e ) => readFile( e.target.files[ 0 ] ) } />
			</label>
			{ pkg && pkg.source && (
				<p className="fbm-hint">
					Xuất từ { pkg.source } lúc { new Date( pkg.exported_at ).toLocaleString( 'vi-VN' ) } (plugin { pkg.plugin_version })
				</p>
			) }
			<div>
				<Label help="conflict">Khi trùng slug hoặc tag</Label>
				<SelectControl
					__nextHasNoMarginBottom
					label="Khi trùng"
					hideLabelFromVision
					value={ conflict }
					onChange={ ( c ) => {
						setConflict( c );
						if ( pkg && ! result ) {
							dry( pkg, c );
						}
					} }
					options={ [
						{ label: 'Tạo bản mới (thêm -2)', value: 'rename' },
						{ label: 'Ghi đè', value: 'overwrite' },
						{ label: 'Bỏ qua', value: 'skip' },
					] }
				/>
			</div>
			{ error && <Notice status="error" isDismissible={ false }>{ error }</Notice> }
			{ shown && (
				<>
					<span className="fbm-field-label">{ result ? 'Kết quả' : 'Xem trước' }</span>
					<ul className="fbm-usage-list">
						{ shown.elements.map( ( e, i ) => (
							<li key={ 'e' + i }>
								<span>
									Element <code>[{ e.tag }]</code>
									{ e.message && <span className="fbm-error-text"> { e.message }</span> }
								</span>
								<span className="fbm-badges">
									<ActionBadge action={ e.action } />
									{ result && e.id > 0 && e.action !== 'skip' && <a href={ `#/elements/${ e.id }` }>Mở</a> }
								</span>
							</li>
						) ) }
						{ shown.blocks.map( ( b, i ) => (
							<li key={ 'b' + i }>
								<span>
									{ b.title } <code>{ b.new_slug !== b.slug ? `${ b.slug } → ${ b.new_slug }` : b.slug }</code>
									{ b.message && <span className="fbm-error-text"> { b.message }</span> }
								</span>
								<span className="fbm-badges">
									<ActionBadge action={ b.action } />
									{ result && b.id > 0 && b.action !== 'skip' && <a href={ `#/blocks/${ b.id }` }>Mở</a> }
								</span>
							</li>
						) ) }
					</ul>
					{ shown.notes.map( ( n, i ) => (
						<Notice key={ i } status="info" isDismissible={ false }>
							{ n }
						</Notice>
					) ) }
				</>
			) }
			{ preview && (
				<div>
					<Button variant="primary" onClick={ run } isBusy={ busy } disabled={ busy || ! count }>
						Nhập { count } mục
					</Button>
				</div>
			) }
			{ result && (
				<StatusBadge status="passed" label="Đã nhập xong" />
			) }
		</Panel>
	);
}
