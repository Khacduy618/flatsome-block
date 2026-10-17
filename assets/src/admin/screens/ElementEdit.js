import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Button, CheckboxControl, DropdownMenu, MenuGroup, MenuItem, Modal, Notice, SelectControl, TextControl } from '@wordpress/components';
import { api, cfg, copyText, errorMessage } from '../api';
import { formatDate, navigate, useDebounced, useResource, useUnsavedWarning } from '../hooks';
import { CheckStep, CodeEditor, ErrorNotice, HelpTip, Issue, Loading, PageHeader, Panel, StatusBadge, useNotice } from '../design-system';
import { OPTION_SNIPPETS } from '../help/snippets';

const TABS = [
	{ key: 'template', file: 'template.php', mode: 'php', help: 'template' },
	{ key: 'schema', file: 'schema.json', mode: 'json', help: 'schema' },
	{ key: 'css', file: 'style.css', mode: 'css', help: 'cssjs' },
	{ key: 'js', file: 'script.js', mode: 'js', help: 'cssjs' },
	{ key: 'sample', file: 'Dữ liệu thử', mode: 'json', help: 'sample' },
];

/** Kiểm tra cú pháp JS trong trình duyệt: new Function() chỉ parse, không chạy. */
function lintJs( code ) {
	if ( ! code || ! code.trim() ) {
		return [];
	}
	try {
		// eslint-disable-next-line no-new-func
		new Function( 'fbm', code );
		return [];
	} catch ( e ) {
		return [ { field: 'js', line: e.lineNumber ? e.lineNumber - 2 : 0, message: e.message } ];
	}
}

function lintSample( code ) {
	try {
		const v = JSON.parse( code || '{}' );
		return v && typeof v === 'object' && ! Array.isArray( v ) ? [] : [ { field: 'sample', line: 1, message: 'Dữ liệu thử phải là object JSON { ... }.' } ];
	} catch ( e ) {
		return [ { field: 'sample', line: 1, message: 'JSON không hợp lệ: ' + e.message } ];
	}
}

function previewDoc( html, css, js ) {
	const links = ( cfg.previewCss || [] ).map( ( href ) => `<link rel="stylesheet" href="${ href }">` ).join( '' );
	const safeJs = ( js || '' ).replace( /<\/script/gi, '<\\/script' );
	return `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">${ links }<style>html,body{background:#fff!important}body{margin:0;padding:16px;font-family:-apple-system,Segoe UI,Roboto,sans-serif}${ css || '' }</style></head><body>${ html || '' }<script src="${ cfg.runtimeUrl }"></script><script>${ safeJs }</script></body></html>`;
}

export default function ElementEdit( { id } ) {
	const notice = useNotice();
	const el = useResource( () => api.get( `/elements/${ id }` ), [ id ] );
	const [ draft, setDraft ] = useState( null );
	const [ meta, setMeta ] = useState( null );
	const [ saved, setSaved ] = useState( null );
	const [ tab, setTab ] = useState( 'template' );
	const [ lint, setLint ] = useState( null );
	const [ busy, setBusy ] = useState( '' );
	const [ previewWidth, setPreviewWidth ] = useState( '100%' );
	const [ confirmDelete, setConfirmDelete ] = useState( false );
	const [ confirmForce, setConfirmForce ] = useState( false );
	const editorRef = useRef();

	// Nạp dữ liệu vào state cục bộ.
	useEffect( () => {
		if ( ! el.data ) {
			return;
		}
		const d = { ...el.data.draft, sample: el.data.sample };
		const m = { name: el.data.name, category: el.data.category, targets: el.data.targets };
		setDraft( d );
		setMeta( m );
		setSaved( JSON.stringify( { d, m } ) );
	}, [ el.data ] );

	const dirty = !! draft && saved !== JSON.stringify( { d: draft, m: meta } );
	useUnsavedWarning( dirty );

	// Cổng 1 chạy khi gõ (server: PHP + schema + CSS; trình duyệt: JS + dữ liệu thử).
	const debounced = useDebounced( draft, 900 );
	useEffect( () => {
		if ( ! debounced ) {
			return;
		}
		let cancelled = false;
		api.post( `/elements/${ id }/lint`, { payload: { schema: debounced.schema, template: debounced.template, css: debounced.css, js: debounced.js } } ).then(
			( res ) => ! cancelled && setLint( res ),
			() => {}
		);
		return () => {
			cancelled = true;
		};
	}, [ debounced, id ] );

	const clientErrors = useMemo( () => ( draft ? [ ...lintJs( draft.js ), ...lintSample( draft.sample ) ] : [] ), [ draft ] );
	const errors = [ ...( lint ? lint.errors : [] ), ...clientErrors ];
	const warnings = lint ? lint.warnings : [];
	const linesFor = ( field ) => errors.filter( ( e ) => e.field === field && e.line ).map( ( e ) => e.line );
	const tabHasError = ( key ) => errors.some( ( e ) => e.field === key );

	const save = useCallback( async () => {
		if ( ! draft ) {
			return null;
		}
		setBusy( 'save' );
		try {
			const res = await api.put( `/elements/${ id }`, { ...draft, ...meta } );
			el.setData( res );
			notice( 'Đã lưu bản nháp' );
			setBusy( '' );
			return res;
		} catch ( e ) {
			notice( errorMessage( e ) );
			setBusy( '' );
			return null;
		}
	}, [ draft, meta, id, el, notice ] );

	// Ctrl/Cmd + S.
	useEffect( () => {
		const onKey = ( e ) => {
			if ( ( e.metaKey || e.ctrlKey ) && e.key.toLowerCase() === 's' ) {
				e.preventDefault();
				save();
			}
		};
		window.addEventListener( 'keydown', onKey );
		return () => window.removeEventListener( 'keydown', onKey );
	}, [ save ] );

	const runTest = async () => {
		if ( clientErrors.length ) {
			notice( 'Sửa lỗi JS / dữ liệu thử trước khi chạy thử.' );
			return;
		}
		if ( dirty && ! ( await save() ) ) {
			return;
		}
		setBusy( 'test' );
		try {
			await api.post( `/elements/${ id }/test` );
			await el.reload();
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
		setBusy( '' );
	};

	const publish = async ( force = false ) => {
		setBusy( 'publish' );
		try {
			const res = await api.post( `/elements/${ id }/publish`, { force_untested: force } );
			el.setData( res );
			setConfirmForce( false );
			notice( `Đã publish v${ res.live_version }. Element đã có trong UX Builder${ res.targets.includes( 'gutenberg' ) ? ' và Gutenberg' : '' }.` );
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
		setBusy( '' );
	};

	const act = async ( fn, ok ) => {
		try {
			const res = await fn();
			if ( res && res.id ) {
				el.setData( res );
			}
			notice( ok );
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
	};

	if ( el.loading && ! el.data ) {
		return <Loading />;
	}
	if ( el.error ) {
		return <ErrorNotice error={ el.error } onRetry={ el.reload } />;
	}
	if ( ! draft || ! meta ) {
		return <Loading />;
	}

	const data = el.data;
	const check = data.check;
	// Kết quả chạy thử chỉ còn giá trị khi khớp đúng bản đang hiển thị (đã lưu, không sửa thêm).
	const test = check && ! check.stale && ! dirty ? check.test : null;
	const stale = ! check || check.stale || dirty;
	const changed = data.dirty || ! data.live_version;
	const canPublish = changed && ! stale && check.lint.ok && test && test.status === 'passed' && ! clientErrors.length;
	const nextVersion = ( data.versions[ 0 ] ? data.versions[ 0 ].version : 0 ) + 1;
	const current = TABS.find( ( t ) => t.key === tab );
	const value = draft[ tab ];

	const goto = ( issue ) => {
		const field = issue.field === 'css' ? 'css' : issue.field;
		if ( TABS.some( ( t ) => t.key === field ) ) {
			setTab( field );
			setTimeout( () => issue.line && editorRef.current && editorRef.current.goto( issue.line ), 50 );
		}
	};

	return (
		<div className="fbm-stack">
			{ data.state === 'tripped' && data.trip && (
				<Notice status="error" isDismissible={ false } actions={ [ { label: 'Bật lại', onClick: () => act( () => api.post( `/elements/${ id }/state`, { state: 'active' } ), 'Đã bật lại' ) } ] }>
					<b>Element đã bị ngắt lúc { formatDate( new Date( data.trip.at * 1000 ).toISOString() ) }.</b> Lỗi: <code>{ data.trip.message }</code>
					{ data.trip.line ? ` (template dòng ${ data.trip.line })` : '' }
					{ data.trip.url ? ` khi mở ${ data.trip.url }` : '' }. Element đã tự tắt để trang không lỗi.
					<HelpTip topic="tripped" />
				</Notice>
			) }
			{ data.last_error && data.state !== 'tripped' && (
				<Notice status="warning" isDismissible={ false }>
					Lỗi gần nhất trên site ({ formatDate( new Date( data.last_error.at * 1000 ).toISOString() ) }): <code>{ data.last_error.message }</code>
					{ data.last_error.line ? ` ở dòng ${ data.last_error.line }` : '' }. Khách thấy element trống, admin thấy khung báo lỗi.
				</Notice>
			) }

			<PageHeader
				back="#/elements"
				title={ meta.name }
				badges={
					<span className="fbm-badges">
						<button type="button" className="fbm-sc" onClick={ async () => notice( ( await copyText( data.shortcode ) ) ? `Đã sao chép ${ data.shortcode }` : 'Không sao chép được' ) }>
							{ data.shortcode }
						</button>
						{ data.live_version ? <StatusBadge status={ data.state } label={ `${ data.state === 'active' ? 'Live' : data.state === 'tripped' ? 'Đã ngắt' : 'Tắt' } v${ data.live_version }` } /> : <StatusBadge status="draft" label="Chưa publish" /> }
						{ ( dirty || data.dirty ) && <StatusBadge status="dirty" /> }
					</span>
				}
				actions={
					<>
						<Button variant="secondary" onClick={ save } isBusy={ busy === 'save' } disabled={ !! busy || ! dirty }>
							Lưu nháp
						</Button>
						<Button variant="secondary" onClick={ runTest } isBusy={ busy === 'test' } disabled={ !! busy }>
							Chạy thử
						</Button>
						{ test && test.status === 'unavailable' && ! stale && changed ? (
							<Button variant="primary" onClick={ () => setConfirmForce( true ) } disabled={ !! busy || ! check.lint.ok }>
								Publish v{ nextVersion } (chưa chạy thử)
							</Button>
						) : (
							<Button variant="primary" onClick={ () => publish( false ) } isBusy={ busy === 'publish' } disabled={ !! busy || ! canPublish } title={ canPublish ? '' : changed ? 'Cần Chạy thử đạt trên bản nháp hiện tại' : 'Bản nháp giống bản Live' }>
								Publish v{ nextVersion }
							</Button>
						) }
						<DropdownMenu icon="ellipsis" label="Thêm thao tác">
							{ ( { onClose } ) => (
								<MenuGroup>
									{ data.live_version > 0 && data.state !== 'disabled' && (
										<MenuItem onClick={ () => { onClose(); act( () => api.post( `/elements/${ id }/state`, { state: 'disabled' } ), 'Đã tắt element' ); } }>
											Tắt element
										</MenuItem>
									) }
									{ data.live_version > 0 && data.state !== 'active' && (
										<MenuItem onClick={ () => { onClose(); act( () => api.post( `/elements/${ id }/state`, { state: 'active' } ), 'Đã bật element' ); } }>
											Bật element
										</MenuItem>
									) }
									<MenuItem href="#/guide/template">Hướng dẫn viết template</MenuItem>
									<MenuItem isDestructive onClick={ () => { onClose(); setConfirmDelete( true ); } }>
										Xoá element
									</MenuItem>
								</MenuGroup>
							) }
						</DropdownMenu>
					</>
				}
			/>

			<div className="fbm-two-col fbm-two-col--editor">
				<div className="fbm-editor">
					<div className="fbm-editor__tabs" role="tablist">
						{ TABS.map( ( t ) => (
							<button key={ t.key } type="button" role="tab" aria-selected={ tab === t.key } className={ tab === t.key ? 'is-active' : '' } onClick={ () => setTab( t.key ) }>
								{ t.file }
								{ tabHasError( t.key ) && <i className="fbm-dot" aria-label="có lỗi" /> }
							</button>
						) ) }
						<span className="fbm-editor__tools">
							<HelpTip topic={ current.help } />
							{ tab === 'schema' && (
								<DropdownMenu icon="plus" label="Chèn option" text="Chèn option" toggleProps={ { size: 'small', variant: 'tertiary' } }>
									{ ( { onClose } ) => (
										<MenuGroup label="Chèn tại con trỏ">
											{ OPTION_SNIPPETS.map( ( o ) => (
												<MenuItem
													key={ o.type }
													info={ o.ux }
													onClick={ () => {
														onClose();
														editorRef.current && editorRef.current.insert( o.snippet + ',\n' );
													} }
												>
													{ o.type }
												</MenuItem>
											) ) }
										</MenuGroup>
									) }
								</DropdownMenu>
							) }
						</span>
					</div>
					<CodeEditor
						key={ tab }
						ref={ editorRef }
						mode={ current.mode }
						value={ value }
						height={ 520 }
						errorLines={ linesFor( tab ) }
						label={ current.file }
						onChange={ ( v ) => setDraft( ( d ) => ( { ...d, [ tab ]: v } ) ) }
					/>
					<p className="fbm-hint">
						{ tab === 'template' && 'Biến có sẵn: mỗi option, $atts, $content (container), $tag, $fbm_id. Ctrl/Cmd + S để lưu.' }
						{ tab === 'schema' && 'Tên option: chữ thường, số, "_". Element thường không khai báo "type".' }
						{ tab === 'css' && 'Chỉ nạp trên trang có element. Đặt selector dưới class gốc của element.' }
						{ tab === 'js' && 'Viết trong fbm.ready(".class", function (el) { … }) để chạy được trong UX Builder và Gutenberg.' }
						{ tab === 'sample' && 'Giá trị option khi Chạy thử. Container: ghi element con vào "$content".' }
					</p>
				</div>

				<div className="fbm-stack">
					<Panel title="Kiểm tra bản nháp" help="checks">
						<CheckStep
							state={ ! lint ? 'wait' : errors.some( ( e ) => [ 'template', 'schema' ].includes( e.field ) ) ? 'bad' : 'ok' }
							title="Cú pháp PHP + schema"
							detail={ ! lint ? 'Đang kiểm tra…' : `${ errors.filter( ( e ) => [ 'template', 'schema' ].includes( e.field ) ).length } lỗi · ${ warnings.length } cảnh báo` }
						/>
						<CheckStep state={ clientErrors.length ? 'bad' : 'ok' } title="JavaScript + dữ liệu thử" detail={ clientErrors.length ? `${ clientErrors.length } lỗi` : 'Hợp lệ' } />
						{ errors.map( ( e, i ) => (
							<Issue key={ 'e' + i } issue={ e } onClick={ () => goto( e ) } />
						) ) }
						{ warnings.map( ( w, i ) => (
							<Issue key={ 'w' + i } issue={ w } warn onClick={ () => goto( w ) } />
						) ) }
						<CheckStep
							state={ busy === 'test' ? 'run' : ! test ? 'wait' : test.status === 'passed' ? ( test.warnings.length ? 'warn' : 'ok' ) : test.status === 'unavailable' ? 'warn' : 'bad' }
							title="Chạy thử"
							detail={
								busy === 'test'
									? 'Đang chạy trong request riêng…'
									: ! test
									? stale
										? 'Bản nháp đã đổi. Bấm Chạy thử.'
										: 'Chưa chạy.'
									: test.status === 'passed'
									? `Đạt · ${ test.time_ms < 1 ? '<1' : test.time_ms } ms · +${ Math.round( test.memory_kb / 102.4 ) / 10 } MB · render ${ test.renders } lần`
									: test.message
							}
						/>
						{ test &&
							test.warnings.map( ( w, i ) => (
								<Issue key={ 'tw' + i } warn issue={ { field: 'template', line: w.line, message: `${ w.type }: ${ w.message }` } } onClick={ () => goto( { field: 'template', line: w.line } ) } />
							) ) }
						{ test && test.status === 'failed' && ( test.fatal || test.exception ) && (
							<Issue
								issue={ { field: 'template', line: ( test.fatal || test.exception ).line, message: test.message } }
								onClick={ () => goto( { field: 'template', line: ( test.fatal || test.exception ).line } ) }
							/>
						) }
					</Panel>

					{ test && test.status === 'passed' && (
						<Panel
							title="Xem trước"
							actions={
								<span className="fbm-seg">
									{ [ [ '100%', 'Desktop' ], [ '768px', 'Tablet' ], [ '375px', 'Mobile' ] ].map( ( [ w, l ] ) => (
										<button key={ w } type="button" className={ previewWidth === w ? 'is-active' : '' } onClick={ () => setPreviewWidth( w ) }>
											{ l }
										</button>
									) ) }
								</span>
							}
						>
							<div className="fbm-preview">
								<iframe
									title="Xem trước element"
									sandbox="allow-scripts"
									style={ { width: previewWidth } }
									srcDoc={ previewDoc( test.html, draft.css, draft.js ) }
								/>
							</div>
							<p className="fbm-hint">Dùng dữ liệu thử và CSS/JS của bản nháp. Font và màu của theme có thể khác một chút so với trang thật.</p>
						</Panel>
					) }

					<Panel title="Cài đặt element">
						<TextControl __nextHasNoMarginBottom label="Tên" value={ meta.name } onChange={ ( v ) => setMeta( { ...meta, name: v } ) } />
						<SelectControl
							__nextHasNoMarginBottom
							label="Nhóm trong UX Builder"
							value={ meta.category }
							options={ [ ...new Set( [ 'Content', 'Layout', 'Media', 'Shop', 'Elements', meta.category ] ) ].map( ( c ) => ( { label: c, value: c } ) ) }
							onChange={ ( v ) => setMeta( { ...meta, category: v } ) }
						/>
						<div>
							<span className="fbm-field-label">
								Xuất hiện ở <HelpTip topic="targets" />
							</span>
							{ [
								[ 'uxbuilder', 'UX Builder' ],
								[ 'gutenberg', `Gutenberg (${ data.block_name })` ],
							].map( ( [ key, label ] ) => (
								<CheckboxControl
									__nextHasNoMarginBottom
									key={ key }
									label={ label }
									checked={ meta.targets.includes( key ) }
									onChange={ ( on ) => {
										const next = on ? [ ...meta.targets, key ] : meta.targets.filter( ( t ) => t !== key );
										if ( next.length ) {
											setMeta( { ...meta, targets: next } );
										}
									} }
								/>
							) ) }
						</div>
						<p className="fbm-hint">Đổi tên, nhóm, nơi xuất hiện có hiệu lực ngay sau khi lưu, không cần publish lại.</p>
					</Panel>

					<Panel title="Phiên bản" help="versions">
						{ data.versions.length === 0 && <p className="fbm-muted">Chưa publish lần nào.</p> }
						<ul className="fbm-versions">
							{ data.versions.map( ( v ) => (
								<li key={ v.version }>
									<span>
										<b>v{ v.version }</b> · { formatDate( v.created_at ) } · { v.created_by }
									</span>
									{ v.version === data.live_version ? (
										<StatusBadge status={ data.state } label="Live" />
									) : (
										<span>
											<Button
												variant="tertiary"
												size="small"
												onClick={ () => act( () => api.post( `/elements/${ id }/rollback`, { version: v.version } ), `Đã đưa v${ v.version } lên chạy` ) }
											>
												Khôi phục
											</Button>
											<Button
												variant="tertiary"
												size="small"
												onClick={ async () => {
													try {
														const row = await api.get( `/elements/${ id }/versions/${ v.version }` );
														setDraft( ( d ) => ( { ...d, ...row.payload } ) );
														notice( `Đã mở code v${ v.version } vào bản nháp (chưa lưu)` );
													} catch ( e ) {
														notice( errorMessage( e ) );
													}
												} }
											>
												Mở vào nháp
											</Button>
										</span>
									) }
								</li>
							) ) }
						</ul>
					</Panel>
				</div>
			</div>

			{ confirmForce && (
				<Modal title="Publish khi chưa chạy thử được?" onRequestClose={ () => setConfirmForce( false ) } className="fbm-modal">
					<div className="fbm-stack">
						<p>{ test && test.message }</p>
						<p>Bản nháp đã qua kiểm tra cú pháp, nhưng chưa được chạy thử. Nếu template có lỗi nặng, trang đầu tiên dùng element sẽ lỗi một lần, sau đó cầu dao tự ngắt element.</p>
						<div className="fbm-modal__actions">
							<Button variant="tertiary" onClick={ () => setConfirmForce( false ) }>
								Huỷ
							</Button>
							<Button variant="primary" isBusy={ busy === 'publish' } onClick={ () => publish( true ) }>
								Vẫn publish
							</Button>
						</div>
					</div>
				</Modal>
			) }

			{ confirmDelete && (
				<Modal title={ `Xoá element "${ meta.name }"?` } onRequestClose={ () => setConfirmDelete( false ) } className="fbm-modal">
					<div className="fbm-stack">
						<p>
							Xoá cả bản nháp, mọi phiên bản và file đã biên dịch. Nơi nào đang dùng <code>{ data.shortcode }</code> sẽ hiện nguyên shortcode dạng chữ.
							Muốn ẩn tạm, hãy dùng "Tắt element".
						</p>
						<div className="fbm-modal__actions">
							<Button variant="tertiary" onClick={ () => setConfirmDelete( false ) }>
								Huỷ
							</Button>
							<Button
								variant="primary"
								isDestructive
								onClick={ async () => {
									try {
										await api.del( `/elements/${ id }` );
										notice( 'Đã xoá element' );
										navigate( 'elements' );
									} catch ( e ) {
										notice( errorMessage( e ) );
									}
								} }
							>
								Xoá vĩnh viễn
							</Button>
						</div>
					</div>
				</Modal>
			) }
		</div>
	);
}
