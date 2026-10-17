import { useEffect, useState } from '@wordpress/element';
import { Button, DropdownMenu, MenuGroup, MenuItem, SelectControl, Modal, CheckboxControl, Notice, Spinner } from '@wordpress/components';
import { api, cfg, copyText, downloadJson, errorMessage } from '../api';
import { formatDate, navigate, useDebounced, useResource } from '../hooks';
import {
	ConfirmInUse,
	EmptyState,
	ErrorNotice,
	HelpTip,
	Loading,
	PageHeader,
	StatusBadge,
	UsageChip,
	UsageList,
	VisibilityToggle,
	useNotice,
} from '../design-system';
import CategoryManager from './CategoryManager';
import UsageIndexNotice from './UsageIndexNotice';

const PER_PAGE = 20;

export default function BlocksList() {
	const notice = useNotice();
	const [ search, setSearch ] = useState( '' );
	const [ category, setCategory ] = useState( '' );
	const [ visibility, setVisibility ] = useState( '' );
	const [ status, setStatus ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ selected, setSelected ] = useState( [] );
	const [ busy, setBusy ] = useState( {} );
	const [ confirm, setConfirm ] = useState( null );
	const [ usageOf, setUsageOf ] = useState( null );
	const [ showCats, setShowCats ] = useState( false );
	const [ bulkCat, setBulkCat ] = useState( null );
	const q = useDebounced( search, 300 );

	useEffect( () => setPage( 1 ), [ q, category, visibility, status ] );

	const cats = useResource( () => api.get( '/block-categories' ), [] );
	const list = useResource(
		() => api.get( '/blocks', { search: q, category, visibility, status, page, per_page: PER_PAGE } ),
		[ q, category, visibility, status, page ]
	);
	const items = list.data ? list.data.items : [];

	useEffect( () => setSelected( [] ), [ list.data ] );

	const patchItem = ( id, item ) =>
		list.setData( ( d ) => ( d ? { ...d, items: d.items.map( ( it ) => ( it.id === id ? { ...it, ...item } : it ) ) } : d ) );

	const toggle = async ( block, enabled ) => {
		setBusy( ( b ) => ( { ...b, [ block.id ]: true } ) );
		try {
			const res = await api.patch( `/blocks/${ block.id }`, { enabled } );
			patchItem( block.id, { visibility: res.visibility } );
			notice( `${ enabled ? 'Đã bật' : 'Đã tắt' } "${ block.title }"`, {
				actions: [ { label: 'Hoàn tác', onClick: () => toggle( block, ! enabled ) } ],
			} );
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
		setBusy( ( b ) => ( { ...b, [ block.id ]: false } ) );
	};

	const trash = async ( block, ignoreUsage = false ) => {
		try {
			await api.del( `/blocks/${ block.id }`, { ignore_usage: ignoreUsage ? 1 : '' } );
			setConfirm( null );
			notice( `Đã chuyển "${ block.title }" vào thùng rác`, {
				actions: [ { label: 'Hoàn tác', onClick: () => api.post( `/blocks/${ block.id }/restore` ).then( list.reload ) } ],
			} );
			list.reload();
		} catch ( e ) {
			if ( e.code === 'fbm_block_in_use' ) {
				setConfirm( { block, usages: e.data?.usages || [] } );
			} else {
				notice( errorMessage( e ) );
			}
		}
	};

	const runAction = async ( fn, okMessage ) => {
		try {
			await fn();
			if ( okMessage ) {
				notice( okMessage );
			}
			list.reload();
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
	};

	const bulk = async ( action, extra = {} ) => {
		try {
			const res = await api.post( '/blocks/bulk', { action, ids: selected, ...extra } );
			const failed = res.failed || [];
			notice(
				`Xong ${ res.done.length } block` +
					( failed.length ? `, ${ failed.length } bị bỏ qua: ${ failed.map( ( f ) => f.message ).join( '; ' ) }` : '' )
			);
			list.reload();
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
	};

	const allChecked = items.length > 0 && selected.length === items.length;
	const inTrash = status === 'trash';
	const catOptions = [ { label: 'Tất cả danh mục', value: '' } ].concat(
		( cats.data || [] ).map( ( c ) => ( { label: `${ c.name } (${ c.count })`, value: String( c.id ) } ) )
	);

	return (
		<div className="fbm-stack">
			<PageHeader
				title="Blocks"
				count={ list.data ? list.data.total : undefined }
				actions={
					<>
						<Button variant="secondary" onClick={ () => setShowCats( true ) }>
							Danh mục
						</Button>
						<Button variant="primary" onClick={ () => navigate( 'blocks/new' ) }>
							+ Thêm block
						</Button>
					</>
				}
			/>

			{ cfg.modules.usage && <UsageIndexNotice onDone={ list.reload } /> }

			<div className="fbm-filters">
				<input
					type="search"
					className="fbm-input fbm-filters__search"
					placeholder="Tìm theo tên hoặc slug…"
					value={ search }
					onChange={ ( e ) => setSearch( e.target.value ) }
					aria-label="Tìm block"
				/>
				<SelectControl __nextHasNoMarginBottom label="Danh mục" hideLabelFromVision value={ category } options={ catOptions } onChange={ setCategory } />
				<SelectControl
					__nextHasNoMarginBottom
					label="Hiển thị"
					hideLabelFromVision
					value={ visibility }
					onChange={ setVisibility }
					options={ [
						{ label: 'Mọi trạng thái hiển thị', value: '' },
						{ label: 'Đang bật', value: 'on' },
						{ label: 'Đang tắt', value: 'off' },
						{ label: 'Có hẹn giờ', value: 'scheduled' },
						...( cfg.modules.usage ? [ { label: 'Chưa được dùng', value: 'unused' } ] : [] ),
					] }
				/>
				<SelectControl
					__nextHasNoMarginBottom
					label="Trạng thái bài viết"
					hideLabelFromVision
					value={ status }
					onChange={ setStatus }
					options={ [
						{ label: 'Mọi trạng thái bài viết', value: '' },
						{ label: 'Đã xuất bản', value: 'publish' },
						{ label: 'Nháp', value: 'draft' },
						{ label: 'Riêng tư', value: 'private' },
						{ label: 'Thùng rác', value: 'trash' },
					] }
				/>
			</div>

			{ selected.length > 0 && (
				<div className="fbm-bulkbar">
					<b>Đã chọn { selected.length }</b>
					{ inTrash ? (
						<>
							<Button variant="secondary" size="small" onClick={ () => bulk( 'restore' ) }>
								Khôi phục
							</Button>
							<Button variant="secondary" size="small" isDestructive onClick={ () => bulk( 'delete', { ignore_usage: true } ) }>
								Xoá vĩnh viễn
							</Button>
						</>
					) : (
						<>
							<Button variant="secondary" size="small" onClick={ () => bulk( 'enable' ) }>
								Bật
							</Button>
							<Button variant="secondary" size="small" onClick={ () => bulk( 'disable' ) }>
								Tắt
							</Button>
							<Button variant="secondary" size="small" onClick={ () => setBulkCat( [] ) }>
								Gán danh mục
							</Button>
							<Button variant="secondary" size="small" isDestructive onClick={ () => bulk( 'trash' ) }>
								Chuyển vào thùng rác
							</Button>
						</>
					) }
					<Button variant="tertiary" size="small" onClick={ () => setSelected( [] ) }>
						Bỏ chọn
					</Button>
				</div>
			) }

			<ErrorNotice error={ list.error } onRetry={ list.reload } />

			{ list.loading && ! list.data ? (
				<Loading />
			) : items.length === 0 ? (
				<EmptyState
					title={ q || category || visibility || status ? 'Không có block nào khớp bộ lọc' : 'Chưa có block nào' }
					action={
						<Button variant="primary" onClick={ () => navigate( 'blocks/new' ) }>
							Tạo block đầu tiên
						</Button>
					}
				>
					Block là đoạn nội dung dựng bằng UX Builder, dùng lại ở nhiều trang bằng shortcode [block id="…"].
				</EmptyState>
			) : (
				<div className={ `fbm-table-wrap${ list.loading ? ' is-loading' : '' }` }>
					<table className="fbm-table">
						<thead>
							<tr>
								<th className="fbm-col-check">
									<input
										type="checkbox"
										aria-label="Chọn tất cả"
										checked={ allChecked }
										ref={ ( el ) => el && ( el.indeterminate = selected.length > 0 && ! allChecked ) }
										onChange={ () => setSelected( allChecked ? [] : items.map( ( i ) => i.id ) ) }
									/>
								</th>
								<th className="fbm-col-vis">
									Hiển thị
									<HelpTip topic="visibility" />
								</th>
								<th>Block</th>
								<th>Danh mục</th>
								{ cfg.modules.usage && (
									<th>
										Nơi dùng
										<HelpTip topic="usage" />
									</th>
								) }
								<th>Sửa lúc</th>
								<th className="fbm-col-actions">
									<span className="screen-reader-text">Thao tác</span>
								</th>
							</tr>
						</thead>
						<tbody>
							{ items.map( ( b ) => (
								<tr key={ b.id } className={ b.visibility.visible ? '' : 'is-off' }>
									<td className="fbm-col-check">
										<input
											type="checkbox"
											aria-label={ `Chọn ${ b.title }` }
											checked={ selected.includes( b.id ) }
											onChange={ () =>
												setSelected( ( s ) => ( s.includes( b.id ) ? s.filter( ( x ) => x !== b.id ) : [ ...s, b.id ] ) )
											}
										/>
									</td>
									<td className="fbm-col-vis">
										{ b.status === 'trash' ? (
											<StatusBadge status="trash" />
										) : (
											<VisibilityToggle
												visibility={ b.visibility }
												busy={ busy[ b.id ] }
												label={ `Bật/tắt ${ b.title }` }
												onChange={ ( v ) => toggle( b, v ) }
											/>
										) }
									</td>
									<td>
										<a className="fbm-row-title" href={ `#/blocks/${ b.id }` }>
											{ b.title || '(không tên)' }
										</a>
										<div className="fbm-row-sub">
											<button
												type="button"
												className="fbm-sc"
												title="Bấm để sao chép"
												onClick={ async () => notice( ( await copyText( b.shortcode ) ) ? `Đã sao chép ${ b.shortcode }` : 'Không sao chép được' ) }
											>
												{ b.shortcode }
											</button>
											{ b.status !== 'publish' && b.status !== 'trash' && <span className="fbm-muted">· { b.status }</span> }
											{ b.visibility.from && <span className="fbm-muted">từ { formatDate( b.visibility.from ) }</span> }
											{ b.visibility.until && <span className="fbm-muted">đến { formatDate( b.visibility.until ) }</span> }
										</div>
									</td>
									<td>{ b.categories.map( ( c ) => c.name ).join( ', ' ) || <span className="fbm-muted">—</span> }</td>
									{ cfg.modules.usage && (
										<td>
											<UsageChip count={ b.usage_count } onClick={ () => setUsageOf( b ) } />
										</td>
									) }
									<td className="fbm-nowrap">{ formatDate( b.modified ) }</td>
									<td className="fbm-col-actions">
										{ cfg.uxBuilder && b.links.uxbuilder && b.status !== 'trash' && (
											<Button variant="tertiary" size="small" href={ b.links.uxbuilder }>
												UX Builder
											</Button>
										) }
										<DropdownMenu icon="ellipsis" label={ `Thao tác cho ${ b.title }` } toggleProps={ { size: 'small' } }>
											{ ( { onClose } ) => (
												<>
													<MenuGroup>
														<MenuItem onClick={ () => navigate( `blocks/${ b.id }` ) }>Sửa</MenuItem>
														{ b.status !== 'trash' && (
															<MenuItem
																onClick={ () => {
																	onClose();
																	runAction( () => api.post( `/blocks/${ b.id }/duplicate` ), `Đã nhân bản "${ b.title }"` );
																} }
															>
																Nhân bản
															</MenuItem>
														) }
														{ cfg.modules.usage && (
															<MenuItem
																onClick={ () => {
																	onClose();
																	setUsageOf( b );
																} }
															>
																Xem nơi dùng
															</MenuItem>
														) }
														<MenuItem href={ b.links.preview } target="_blank">
															Xem trước
														</MenuItem>
														{ cfg.modules.transfer && (
															<MenuItem
																onClick={ async () => {
																	onClose();
																	try {
																		const pkg = await api.post( '/transfer/export', { block_ids: [ b.id ], with_deps: true } );
																		downloadJson( pkg, `fbm-${ b.slug }.json` );
																	} catch ( e ) {
																		notice( errorMessage( e ) );
																	}
																} }
															>
																Xuất file
															</MenuItem>
														) }
													</MenuGroup>
													<MenuGroup>
														{ b.status === 'trash' ? (
															<>
																<MenuItem
																	onClick={ () => {
																		onClose();
																		runAction( () => api.post( `/blocks/${ b.id }/restore` ), 'Đã khôi phục' );
																	} }
																>
																	Khôi phục
																</MenuItem>
																<MenuItem
																	isDestructive
																	onClick={ () => {
																		onClose();
																		runAction( () => api.del( `/blocks/${ b.id }`, { force: 1, ignore_usage: 1 } ), 'Đã xoá vĩnh viễn' );
																	} }
																>
																	Xoá vĩnh viễn
																</MenuItem>
															</>
														) : (
															<MenuItem
																isDestructive
																onClick={ () => {
																	onClose();
																	trash( b );
																} }
															>
																Chuyển vào thùng rác
															</MenuItem>
														) }
													</MenuGroup>
												</>
											) }
										</DropdownMenu>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</div>
			) }

			{ list.data && list.data.pages > 1 && (
				<div className="fbm-pagination">
					<Button variant="secondary" size="small" disabled={ page <= 1 } onClick={ () => setPage( page - 1 ) }>
						‹ Trước
					</Button>
					<span>
						Trang { page } / { list.data.pages }
					</span>
					<Button variant="secondary" size="small" disabled={ page >= list.data.pages } onClick={ () => setPage( page + 1 ) }>
						Sau ›
					</Button>
					{ list.loading && <Spinner /> }
				</div>
			) }

			{ confirm && (
				<ConfirmInUse
					title={ `Chuyển "${ confirm.block.title }" vào thùng rác?` }
					message={ `Block này đang được dùng ở ${ confirm.usages.length } nơi. Lưu ý: Flatsome vẫn hiển thị block trong thùng rác. Muốn ẩn block, hãy tắt nó.` }
					usages={ confirm.usages }
					onClose={ () => setConfirm( null ) }
					actions={ [
						{
							label: 'Tắt block thay vì xoá',
							variant: 'primary',
							onClick: () => {
								toggle( confirm.block, false );
								setConfirm( null );
							},
						},
						{ label: 'Vẫn chuyển vào thùng rác', isDestructive: true, onClick: () => trash( confirm.block, true ) },
					] }
				/>
			) }

			{ usageOf && <UsageModal block={ usageOf } onClose={ () => setUsageOf( null ) } /> }

			{ showCats && (
				<CategoryManager
					onClose={ () => {
						setShowCats( false );
						cats.reload();
						list.reload();
					} }
				/>
			) }

			{ bulkCat && (
				<Modal title={ `Gán danh mục cho ${ selected.length } block` } onRequestClose={ () => setBulkCat( null ) } className="fbm-modal">
					<div className="fbm-stack">
						{ ( cats.data || [] ).length === 0 && <Notice status="info" isDismissible={ false }>Chưa có danh mục. Tạo trong nút "Danh mục".</Notice> }
						{ ( cats.data || [] ).map( ( c ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ c.id }
								label={ c.name }
								checked={ bulkCat.includes( c.id ) }
								onChange={ ( on ) => setBulkCat( on ? [ ...bulkCat, c.id ] : bulkCat.filter( ( x ) => x !== c.id ) ) }
							/>
						) ) }
						<div className="fbm-modal__actions">
							<Button variant="tertiary" onClick={ () => setBulkCat( null ) }>
								Huỷ
							</Button>
							<Button
								variant="secondary"
								onClick={ () => {
									bulk( 'add_categories', { categories: bulkCat } );
									setBulkCat( null );
								} }
							>
								Thêm vào danh mục hiện có
							</Button>
							<Button
								variant="primary"
								onClick={ () => {
									bulk( 'set_categories', { categories: bulkCat } );
									setBulkCat( null );
								} }
							>
								Thay danh mục
							</Button>
						</div>
					</div>
				</Modal>
			) }
		</div>
	);
}

function UsageModal( { block, onClose } ) {
	const usage = useResource( () => api.get( `/blocks/${ block.id }/usage` ), [ block.id ] );
	return (
		<Modal title={ `Nơi dùng "${ block.title }"` } onRequestClose={ onClose } className="fbm-modal">
			<div className="fbm-stack">
				<code>{ block.shortcode }</code>
				{ usage.loading ? <Loading /> : usage.data && usage.data.length ? <UsageList usages={ usage.data } /> : <p>Block chưa được dùng ở đâu.</p> }
			</div>
		</Modal>
	);
}
