/**
 * Gutenberg:
 * - fbm/ux-block: chèn UX Block của Flatsome bằng cách chọn từ danh sách.
 * - fbm/<tag>: block tự dựng từ schema của element (dữ liệu trong window.fbmGutenberg).
 * Preview dùng ServerSideRender -> chạy đúng template PHP, giống hệt frontend.
 */
import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks, InspectorControls, MediaUpload, MediaUploadCheck, useBlockProps } from '@wordpress/block-editor';
import {
	Button,
	ColorPicker,
	ComboboxControl,
	ExternalLink,
	Notice,
	PanelBody,
	Placeholder,
	RangeControl,
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
	BaseControl,
} from '@wordpress/components';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import ServerSideRender from '@wordpress/server-side-render';

const DATA = window.fbmGutenberg || { elements: [], manageUrl: '' };

const ICON = (
	<svg viewBox="0 0 24 24" width="24" height="24" aria-hidden="true">
		<path d="M4 5h16v14H4z" fill="none" stroke="currentColor" strokeWidth="1.5" />
		<path d="M9 9l-3 3 3 3M15 9l3 3-3 3" fill="none" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
	</svg>
);

/* ---------- fbm/ux-block ---------- */

function UxBlockEdit( { attributes, setAttributes } ) {
	const props = useBlockProps();
	const [ options, setOptions ] = useState( [] );
	const [ search, setSearch ] = useState( '' );

	useEffect( () => {
		let live = true;
		apiFetch( { path: `/fbm/v1/blocks?per_page=50&orderby=title&order=asc&search=${ encodeURIComponent( search ) }` } )
			.then( ( res ) => {
				if ( live ) {
					setOptions( res.items.map( ( b ) => ( { value: b.slug, label: `${ b.title } (${ b.slug })${ b.visibility.visible ? '' : ' · đang tắt' }` } ) ) );
				}
			} )
			.catch( () => {} );
		return () => {
			live = false;
		};
	}, [ search ] );

	const picker = (
		<ComboboxControl
			__nextHasNoMarginBottom
			label="Chọn UX Block"
			value={ attributes.id }
			options={ attributes.id && ! options.some( ( o ) => o.value === attributes.id ) ? [ { value: attributes.id, label: attributes.id }, ...options ] : options }
			onFilterValueChange={ setSearch }
			onChange={ ( id ) => setAttributes( { id: id || '' } ) }
		/>
	);

	return (
		<div { ...props }>
			<InspectorControls>
				<PanelBody title="UX Block">
					{ picker }
					{ DATA.manageUrl && <ExternalLink href={ DATA.manageUrl + '#/blocks' }>Quản lý block</ExternalLink> }
				</PanelBody>
			</InspectorControls>
			{ attributes.id ? (
				<ServerSideRender block="fbm/ux-block" attributes={ attributes } />
			) : (
				<Placeholder icon={ ICON } label="UX Block (Flatsome)" instructions="Chọn một block đã dựng bằng UX Builder.">
					{ picker }
				</Placeholder>
			) }
		</div>
	);
}

registerBlockType( 'fbm/ux-block', {
	apiVersion: 3,
	title: 'UX Block (Flatsome)',
	description: 'Chèn một UX Block của Flatsome. Tôn trọng trạng thái bật/tắt và hẹn giờ.',
	category: 'fbm-elements',
	icon: ICON,
	keywords: [ 'flatsome', 'block', 'ux' ],
	attributes: { id: { type: 'string', default: '' } },
	supports: { html: false },
	edit: UxBlockEdit,
	save: () => null,
} );

/* ---------- Control tự dựng từ schema ---------- */

function MediaControl( { control, value, onChange } ) {
	const isImage = control.control === 'image';
	return (
		<BaseControl __nextHasNoMarginBottom label={ control.label } help={ control.help }>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ isImage ? [ 'image' ] : undefined }
					value={ value ? Number( value ) : undefined }
					onSelect={ ( media ) => onChange( String( media.id ) ) }
					render={ ( { open } ) => (
						<div style={ { display: 'flex', gap: 8, alignItems: 'center', flexWrap: 'wrap' } }>
							<Button variant="secondary" size="small" onClick={ open }>
								{ value ? `Thay (#${ value })` : isImage ? 'Chọn ảnh' : 'Chọn file' }
							</Button>
							{ value && (
								<Button variant="tertiary" size="small" isDestructive onClick={ () => onChange( '' ) }>
									Bỏ
								</Button>
							) }
						</div>
					) }
				/>
			</MediaUploadCheck>
		</BaseControl>
	);
}

function Control( { control, value, onChange } ) {
	const common = { __nextHasNoMarginBottom: true, label: control.label, help: control.help || undefined };
	switch ( control.control ) {
		case 'select':
			return <SelectControl { ...common } value={ value } options={ control.choices } onChange={ onChange } />;
		case 'range':
			return (
				<RangeControl
					{ ...common }
					label={ control.unit ? `${ control.label } (${ control.unit })` : control.label }
					value={ value === '' ? undefined : Number( value ) }
					min={ control.min }
					max={ control.max }
					step={ control.step }
					allowReset
					onChange={ ( v ) => onChange( v === undefined ? '' : String( v ) ) }
				/>
			);
		case 'toggle':
			return <ToggleControl { ...common } checked={ value === 'true' } onChange={ ( on ) => onChange( on ? 'true' : 'false' ) } />;
		case 'color':
			return (
				<BaseControl { ...common }>
					<ColorPicker color={ value || undefined } enableAlpha onChange={ onChange } />
					{ value && (
						<Button variant="tertiary" size="small" onClick={ () => onChange( '' ) }>
							Xoá màu
						</Button>
					) }
				</BaseControl>
			);
		case 'image':
		case 'file':
			return <MediaControl control={ control } value={ value } onChange={ onChange } />;
		case 'textarea':
			return <TextareaControl { ...common } value={ value } onChange={ onChange } />;
		default:
			return <TextControl { ...common } value={ value } onChange={ onChange } />;
	}
}

function groupControls( controls ) {
	const groups = [];
	controls.forEach( ( c ) => {
		const title = c.group || 'Cài đặt';
		let g = groups.find( ( x ) => x.title === title );
		if ( ! g ) {
			g = { title, controls: [] };
			groups.push( g );
		}
		g.controls.push( c );
	} );
	return groups;
}

/* ---------- fbm/<tag> ---------- */

DATA.elements.forEach( ( el ) => {
	const attributes = { className: { type: 'string' } };
	el.controls.forEach( ( c ) => {
		attributes[ c.name ] = { type: 'string', default: c.default };
	} );
	const groups = groupControls( el.controls );

	function Edit( { attributes: attrs, setAttributes } ) {
		const props = useBlockProps( { className: el.container ? 'fbm-gb-container' : undefined } );
		const inspector = (
			<InspectorControls>
				{ groups.map( ( g, i ) => (
					<PanelBody key={ g.title } title={ g.title } initialOpen={ i === 0 }>
						{ g.controls.map( ( c ) => (
							<Control key={ c.name } control={ c } value={ attrs[ c.name ] ?? '' } onChange={ ( v ) => setAttributes( { [ c.name ]: v } ) } />
						) ) }
					</PanelBody>
				) ) }
				<PanelBody title="Element" initialOpen={ false }>
					<p>
						Shortcode tương đương: <code>[{ el.tag }]</code>
					</p>
					<ExternalLink href={ el.manageUrl }>Sửa code element</ExternalLink>
				</PanelBody>
			</InspectorControls>
		);
		const notice =
			el.state !== 'active' ? (
				<Notice status="warning" isDismissible={ false }>
					Element đang { el.state === 'tripped' ? 'bị ngắt do lỗi' : 'tắt' }: khách sẽ không thấy phần này.
				</Notice>
			) : null;

		if ( el.container ) {
			return (
				<div { ...props }>
					{ inspector }
					{ notice }
					<div className="fbm-gb-container__label">{ el.title }</div>
					<InnerBlocks allowedBlocks={ el.allow.length ? el.allow : undefined } />
				</div>
			);
		}
		return (
			<div { ...props }>
				{ inspector }
				{ notice }
				<ServerSideRender block={ el.name } attributes={ attrs } />
			</div>
		);
	}

	registerBlockType( el.name, {
		apiVersion: 3,
		title: el.title,
		description: `Element [${ el.tag }] của Flatsome Block Manager.`,
		category: 'fbm-elements',
		icon: ICON,
		keywords: [ el.tag, 'flatsome' ],
		attributes,
		parent: el.parent.length ? el.parent : undefined,
		supports: { html: false },
		variations: el.variations,
		edit: Edit,
		save: () => ( el.container ? <InnerBlocks.Content /> : null ),
	} );
} );
