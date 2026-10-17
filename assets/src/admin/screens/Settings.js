import { useState } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { api, cfg, copyText, errorMessage } from '../api';
import { formatDate, useResource } from '../hooks';
import { ErrorNotice, HelpTip, Loading, PageHeader, Panel, Switch, useNotice } from '../design-system';
import { UsageProgress, rebuildUsage } from './UsageIndexNotice';

const MODULES = [
	[ 'usage', 'Theo dõi nơi dùng', 'Quét trang, sản phẩm, menu, widget, theme option để biết block được dùng ở đâu.' ],
	[ 'transfer', 'Xuất / Nhập', 'Chuyển block và element giữa các site bằng file JSON.' ],
	[ 'elements', 'Code Elements', 'Tự viết element cho UX Builder và Gutenberg. Chỉ Administrator dùng được.' ],
	[ 'handdn', 'Tính năng HANDDN', 'Shortcode [handdn_product_attributes] (+ element UX Builder) và nút "Customize this strap in 3D" trên trang sản phẩm.' ],
];

function Row( { title, help, desc, children } ) {
	return (
		<div className="fbm-setting">
			<div>
				<b>{ title }</b>
				{ help && <HelpTip topic={ help } /> }
				{ desc && <p className="fbm-hint">{ desc }</p> }
			</div>
			{ children }
		</div>
	);
}

export default function Settings() {
	const notice = useNotice();
	const res = useResource( () => api.get( '/settings' ), [] );
	const usage = useResource( () => ( cfg.modules.usage ? api.get( '/usage/status' ) : Promise.resolve( null ) ), [] );
	const [ progress, setProgress ] = useState( null );
	const [ needsReload, setNeedsReload ] = useState( false );

	if ( ! cfg.user.canManageSettings ) {
		return <Notice status="warning" isDismissible={ false }>Chỉ Administrator chỉnh được cài đặt.</Notice>;
	}
	if ( res.loading && ! res.data ) {
		return <Loading />;
	}
	if ( res.error ) {
		return <ErrorNotice error={ res.error } onRetry={ res.reload } />;
	}

	const { settings, info } = res.data;
	const save = async ( patch, reloadNeeded = false ) => {
		try {
			res.setData( await api.post( '/settings', patch ) );
			notice( 'Đã lưu cài đặt' );
			if ( reloadNeeded ) {
				setNeedsReload( true );
			}
		} catch ( e ) {
			notice( errorMessage( e ) );
		}
	};

	return (
		<div className="fbm-stack">
			<PageHeader title="Cài đặt" />
			{ needsReload && (
				<Notice status="info" isDismissible={ false } actions={ [ { label: 'Tải lại trang', onClick: () => window.location.reload() } ] }>
					Tải lại trang để áp dụng thay đổi module.
				</Notice>
			) }
			<div className="fbm-grid-2">
				<div className="fbm-stack">
					<Panel title="Module" help="modules">
						{ MODULES.map( ( [ key, title, desc ] ) => (
							<Row key={ key } title={ title } desc={ desc }>
								<Switch
									checked={ settings.modules[ key ] }
									label={ title }
									onChange={ ( v ) => save( { modules: { [ key ]: v } }, true ) }
								/>
							</Row>
						) ) }
					</Panel>
					<Panel title="An toàn">
						<Row
							title="Safe mode"
							help="safeMode"
							desc={ info.safe_mode_constant ? 'Đang được điều khiển bởi FBM_SAFE_MODE trong wp-config.php.' : 'Tắt mọi element tự viết ngay lập tức. Không ảnh hưởng block.' }
						>
							<Switch checked={ info.safe_mode } disabled={ info.safe_mode_constant } label="Safe mode" onChange={ ( v ) => save( { safe_mode: v } ) } />
						</Row>
						<Row title="Xoá dữ liệu khi gỡ plugin" help="uninstall" desc="Xoá bảng, element và cài đặt của plugin. Block của Flatsome luôn được giữ.">
							<Switch checked={ settings.delete_on_uninstall } label="Xoá dữ liệu khi gỡ" onChange={ ( v ) => save( { delete_on_uninstall: v } ) } />
						</Row>
					</Panel>
				</div>
				<div className="fbm-stack">
					{ cfg.modules.usage && (
						<Panel title="Chỉ mục nơi dùng" help="usageIndex">
							<p>
								{ usage.data && usage.data.built
									? `Quét gần nhất: ${ formatDate( usage.data.built_at ) } · ${ usage.data.rows } lượt dùng.`
									: 'Chưa quét lần nào.' }
							</p>
							<UsageProgress progress={ progress } />
							<div>
								<Button
									variant="secondary"
									disabled={ !! progress && ! progress.done }
									onClick={ async () => {
										try {
											await rebuildUsage( setProgress );
											usage.reload();
											notice( 'Đã quét xong' );
										} catch ( e ) {
											notice( errorMessage( e ) );
										}
									} }
								>
									Quét lại toàn bộ
								</Button>
							</div>
						</Panel>
					) }
					<Panel title="REST API" help="api">
						<button type="button" className="fbm-sc" onClick={ async () => notice( ( await copyText( info.rest_url ) ) ? 'Đã sao chép' : 'Không sao chép được' ) }>
							{ info.rest_url }
						</button>
						<p className="fbm-hint">
							Hệ thống ngoài xác thực bằng Application Password (Người dùng › Hồ sơ › Application Passwords). <a href="#/guide/api">Ví dụ gọi API</a>
						</p>
					</Panel>
					<Panel title="Môi trường">
						<ul className="fbm-usage-list">
							<li><span>Plugin</span><span>{ info.plugin_version }</span></li>
							<li><span>Flatsome</span><span>{ info.flatsome_version }</span></li>
							<li><span>WordPress</span><span>{ info.wp_version }</span></li>
							<li><span>PHP</span><span>{ info.php_version }</span></li>
							<li><span>Quyền viết element</span><span>{ info.can_edit_code ? 'Có' : info.disallow_file_edit ? 'Không (DISALLOW_FILE_EDIT)' : 'Không' }</span></li>
						</ul>
					</Panel>
				</div>
			</div>
		</div>
	);
}
