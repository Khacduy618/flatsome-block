import { useState } from '@wordpress/element';
import { Button, Modal, SelectControl, TextControl } from '@wordpress/components';
import { api, errorMessage } from '../api';
import { useResource } from '../hooks';
import { HelpTip, Loading, useNotice } from '../design-system';

export default function CategoryManager( { onClose } ) {
	const notice = useNotice();
	const cats = useResource( () => api.get( '/block-categories' ), [] );
	const [ name, setName ] = useState( '' );
	const [ parent, setParent ] = useState( '0' );
	const [ editing, setEditing ] = useState( null );

	const run = async ( fn, ok ) => {
		try {
			await fn();
			notice( ok );
			cats.reload();
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
	};

	const list = cats.data || [];
	const parentOptions = [ { label: '— Không có danh mục cha —', value: '0' } ].concat(
		list.map( ( c ) => ( { label: c.name, value: String( c.id ) } ) )
	);

	return (
		<Modal title="Danh mục block" onRequestClose={ onClose } className="fbm-modal">
			<div className="fbm-stack">
				<p className="fbm-muted">
					Dùng chung với taxonomy block_categories của Flatsome.
					<HelpTip topic="categories" />
				</p>
				<form
					className="fbm-inline-form"
					onSubmit={ ( e ) => {
						e.preventDefault();
						if ( ! name.trim() ) {
							return;
						}
						run( () => api.post( '/block-categories', { name, parent: Number( parent ) } ), `Đã tạo "${ name }"` );
						setName( '' );
					} }
				>
					<TextControl __nextHasNoMarginBottom label="Tên danh mục mới" value={ name } onChange={ setName } />
					<SelectControl __nextHasNoMarginBottom label="Danh mục cha" value={ parent } options={ parentOptions } onChange={ setParent } />
					<Button variant="primary" type="submit">
						Thêm
					</Button>
				</form>
				{ cats.loading && ! cats.data ? (
					<Loading />
				) : (
					<ul className="fbm-list">
						{ list.length === 0 && <li className="fbm-muted">Chưa có danh mục.</li> }
						{ list.map( ( c ) => (
							<li key={ c.id }>
								{ editing && editing.id === c.id ? (
									<form
										className="fbm-inline-form"
										onSubmit={ ( e ) => {
											e.preventDefault();
											run( () => api.patch( `/block-categories/${ c.id }`, { name: editing.name, parent: c.parent } ), 'Đã đổi tên' );
											setEditing( null );
										} }
									>
										<TextControl
											__nextHasNoMarginBottom
											label="Tên"
											hideLabelFromVision
											value={ editing.name }
											onChange={ ( v ) => setEditing( { ...editing, name: v } ) }
										/>
										<Button variant="primary" size="small" type="submit">
											Lưu
										</Button>
										<Button variant="tertiary" size="small" onClick={ () => setEditing( null ) }>
											Huỷ
										</Button>
									</form>
								) : (
									<>
										<span>
											{ c.parent ? '— ' : '' }
											{ c.name } <span className="fbm-muted">({ c.count } block)</span>
										</span>
										<span>
											<Button variant="tertiary" size="small" onClick={ () => setEditing( { id: c.id, name: c.name } ) }>
												Đổi tên
											</Button>
											<Button
												variant="tertiary"
												size="small"
												isDestructive
												onClick={ () => run( () => api.del( `/block-categories/${ c.id }` ), `Đã xoá "${ c.name }"` ) }
											>
												Xoá
											</Button>
										</span>
									</>
								) }
							</li>
						) ) }
					</ul>
				) }
			</div>
		</Modal>
	);
}
