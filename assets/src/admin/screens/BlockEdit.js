import { useEffect, useState } from '@wordpress/element';
import { Button, CheckboxControl, SelectControl, TextControl } from '@wordpress/components';
import { api, cfg, copyText, errorMessage } from '../api';
import { fromLocalInput, navigate, toLocalInput, useResource, useUnsavedWarning } from '../hooks';
import {
	CodeEditor,
	ConfirmInUse,
	ErrorNotice,
	HelpTip,
	Label,
	Loading,
	PageHeader,
	Panel,
	StatusBadge,
	Switch,
	UsageList,
	useNotice,
} from '../design-system';

const EMPTY = { title: '', slug: '', status: 'publish', content: '', categories: [], enabled: true, show_from: '', show_until: '' };

function toForm( block ) {
	return {
		title: block.title,
		slug: block.slug,
		status: block.status === 'trash' ? 'draft' : block.status,
		content: block.content || '',
		categories: block.categories.map( ( c ) => c.id ),
		enabled: block.visibility.enabled,
		show_from: toLocalInput( block.visibility.from ),
		show_until: toLocalInput( block.visibility.until ),
	};
}

export default function BlockEdit( { id } ) {
	const isNew = id === 'new';
	const notice = useNotice();
	const block = useResource( () => ( isNew ? Promise.resolve( null ) : api.get( `/blocks/${ id }` ) ), [ id ] );
	const cats = useResource( () => api.get( '/block-categories' ), [] );
	const [ form, setForm ] = useState( EMPTY );
	const [ original, setOriginal ] = useState( EMPTY );
	const [ saving, setSaving ] = useState( false );
	const [ slugConfirm, setSlugConfirm ] = useState( null );

	useEffect( () => {
		if ( block.data ) {
			const f = toForm( block.data );
			setForm( f );
			setOriginal( f );
		}
	}, [ block.data ] );

	const dirty = JSON.stringify( form ) !== JSON.stringify( original );
	useUnsavedWarning( dirty );
	const set = ( key ) => ( value ) => setForm( ( f ) => ( { ...f, [ key ]: value } ) );

	const save = async ( confirmSlug = false ) => {
		if ( ! form.title.trim() ) {
			notice( 'Hãy nhập tên block.' );
			return;
		}
		setSaving( true );
		const payload = {
			...form,
			show_from: fromLocalInput( form.show_from ),
			show_until: fromLocalInput( form.show_until ),
			confirm_slug_change: confirmSlug,
		};
		try {
			if ( isNew ) {
				const created = await api.post( '/blocks', payload );
				setOriginal( form );
				notice( `Đã tạo "${ created.title }"` );
				navigate( `blocks/${ created.id }` );
			} else {
				const updated = await api.patch( `/blocks/${ id }`, payload );
				block.setData( updated );
				setSlugConfirm( null );
				notice( 'Đã lưu' );
			}
		} catch ( e ) {
			if ( e.code === 'fbm_slug_in_use' ) {
				setSlugConfirm( e.data?.usages || [] );
			} else {
				notice( errorMessage( e ) );
			}
		}
		setSaving( false );
	};

	if ( block.loading && ! isNew ) {
		return <Loading />;
	}
	if ( block.error ) {
		return <ErrorNotice error={ block.error } onRetry={ block.reload } />;
	}

	const data = block.data;
	const vis = data ? data.visibility : null;

	return (
		<div className="fbm-stack">
			<PageHeader
				back="#/blocks"
				title={ isNew ? 'Block mới' : form.title || '(không tên)' }
				badges={ vis && <StatusBadge status={ vis.state } /> }
				actions={
					<>
						{ data && cfg.uxBuilder && data.links.uxbuilder && (
							<Button variant="secondary" href={ data.links.uxbuilder }>
								Mở UX Builder
							</Button>
						) }
						<Button variant="primary" isBusy={ saving } disabled={ saving || ( ! dirty && ! isNew ) } onClick={ () => save( false ) }>
							{ isNew ? 'Tạo block' : 'Lưu' }
						</Button>
					</>
				}
			/>

			<div className="fbm-two-col">
				<div className="fbm-stack">
					<Panel>
						<TextControl __nextHasNoMarginBottom label="Tên block" value={ form.title } onChange={ set( 'title' ) } />
						<div>
							<Label help="slug" htmlFor="fbm-slug">
								Slug (dùng trong shortcode)
							</Label>
							<input
								id="fbm-slug"
								className="fbm-input fbm-mono"
								value={ form.slug }
								placeholder={ isNew ? 'Bỏ trống để tạo từ tên' : '' }
								onChange={ ( e ) => set( 'slug' )( e.target.value ) }
							/>
							{ data && form.slug !== data.slug && data.usages && data.usages.length > 0 && (
								<p className="fbm-hint fbm-hint--warn">
									Block đang được dùng ở { data.usages.length } nơi. Đổi slug sẽ làm các shortcode [block id="{ data.slug }"] không tìm thấy block.
								</p>
							) }
						</div>
						<div>
							<Label help="status">Trạng thái bài viết</Label>
							<SelectControl
								__nextHasNoMarginBottom
								label="Trạng thái bài viết"
								hideLabelFromVision
								value={ form.status }
								onChange={ set( 'status' ) }
								options={ [
									{ label: 'Đã xuất bản', value: 'publish' },
									{ label: 'Nháp', value: 'draft' },
									{ label: 'Riêng tư', value: 'private' },
								] }
							/>
						</div>
						<div>
							<Label help="content">Nội dung (shortcode do UX Builder tạo)</Label>
							<CodeEditor mode="html" value={ form.content } onChange={ set( 'content' ) } height={ 360 } label="Nội dung block" />
						</div>
					</Panel>
				</div>

				<div className="fbm-stack">
					<Panel
						title="Hiển thị"
						help="schedule"
						actions={ <Switch checked={ form.enabled } onChange={ set( 'enabled' ) } label="Bật/tắt block" /> }
					>
						<div>
							<label className="fbm-field-label" htmlFor="fbm-from">
								Hiện từ
							</label>
							<input id="fbm-from" type="datetime-local" className="fbm-input" value={ form.show_from } onChange={ ( e ) => set( 'show_from' )( e.target.value ) } />
						</div>
						<div>
							<label className="fbm-field-label" htmlFor="fbm-until">
								Tự tắt lúc
							</label>
							<input id="fbm-until" type="datetime-local" className="fbm-input" value={ form.show_until } onChange={ ( e ) => set( 'show_until' )( e.target.value ) } />
						</div>
						<p className="fbm-hint">
							{ form.enabled ? 'Bỏ trống cả hai ô thì block luôn hiện.' : 'Block đang tắt: khách không thấy block này ở bất kỳ đâu.' } Giờ site: { cfg.timezone }.
						</p>
					</Panel>

					{ data && (
						<Panel
							title="Shortcode"
							actions={
								<Button variant="tertiary" size="small" onClick={ async () => notice( ( await copyText( data.shortcode ) ) ? 'Đã sao chép' : 'Không sao chép được' ) }>
									Sao chép
								</Button>
							}
						>
							<code className="fbm-sc">{ data.shortcode }</code>
						</Panel>
					) }

					{ data && cfg.modules.usage && (
						<Panel title={ `Nơi dùng (${ data.usages ? data.usages.length : 0 })` } help="usage">
							{ data.usages && data.usages.length ? <UsageList usages={ data.usages } /> : <p className="fbm-muted">Chưa được dùng ở đâu.</p> }
						</Panel>
					) }

					<Panel title="Danh mục" help="categories">
						{ ( cats.data || [] ).length === 0 && <p className="fbm-muted">Chưa có danh mục. Tạo ở danh sách Blocks › Danh mục.</p> }
						{ ( cats.data || [] ).map( ( c ) => (
							<CheckboxControl
								__nextHasNoMarginBottom
								key={ c.id }
								label={ c.name }
								checked={ form.categories.includes( c.id ) }
								onChange={ ( on ) => set( 'categories' )( on ? [ ...form.categories, c.id ] : form.categories.filter( ( x ) => x !== c.id ) ) }
							/>
						) ) }
					</Panel>

					{ data && (
						<Panel title="Liên kết">
							<a href={ data.links.preview } target="_blank" rel="noreferrer">
								Xem trước block ↗
							</a>
							<a href={ data.links.backend }>Mở trong trình soạn thảo của WordPress</a>
							<span className="fbm-muted">ID { data.id } · tác giả { data.author }</span>
						</Panel>
					) }
				</div>
			</div>

			{ slugConfirm && (
				<ConfirmInUse
					title="Đổi slug của block đang được dùng?"
					message={ `Các nơi dưới đây đang dùng block này. Nếu chúng gọi bằng slug [block id="${ data.slug }"], block sẽ không hiện nữa cho tới khi bạn sửa shortcode.` }
					usages={ slugConfirm }
					onClose={ () => setSlugConfirm( null ) }
					actions={ [
						{ label: 'Giữ slug cũ', variant: 'secondary', onClick: () => { set( 'slug' )( data.slug ); setSlugConfirm( null ); } },
						{ label: 'Vẫn đổi slug', isDestructive: true, onClick: () => save( true ) },
					] }
				/>
			) }

			{ ! isNew && <HelpFooter /> }
		</div>
	);
}

function HelpFooter() {
	return (
		<p className="fbm-muted">
			Mẹo: dùng công tắc Hiển thị để ẩn block; trạng thái "Nháp" không làm block biến mất khỏi site. <HelpTip topic="visibility" />
		</p>
	);
}
