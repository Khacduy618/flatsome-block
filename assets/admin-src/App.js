/**
 * Block Manager v2: 3 tab danh sách. Người không có manage_options chỉ thấy tab UX Blocks.
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { Notice, Spinner, TabPanel } from '@wordpress/components';
import ItemList from './ItemList';
import { cfg, errorMessage, getItems, rebuildUsage, setState } from './api';

const TABS = [
	{
		name: 'ux_block',
		label: 'UX Blocks',
		search: 'Tìm UX Block…',
		note: 'Tạo, sửa nội dung, đổi tên, xoá UX Block: vẫn dùng menu UX Blocks của Flatsome và UX Builder.',
	},
	{
		name: 'plugin',
		label: 'Element plugin',
		search: 'Tìm element plugin…',
		note: 'Element viết sẵn trong plugin. Muốn đổi code: sửa file rồi cài zip mới, admin không sửa được.',
	},
	{
		name: 'flatsome',
		label: 'Element Flatsome',
		search: 'Tìm element Flatsome…',
		note: 'Element của theme. Element khung trang bị khoá ở Bật.',
	},
];

const tabsFor = () => ( cfg.canManage ? TABS : TABS.slice( 0, 1 ) );

export default function App() {
	const [ lists, setLists ] = useState( {} );
	const [ error, setError ] = useState( '' );
	const [ progress, setProgress ] = useState( null );
	const rebuilding = useRef( false );

	const load = useCallback( () => {
		return Promise.all(
			tabsFor().map( ( t ) =>
				getItems( t.name ).then( ( data ) => [ t.name, data ] )
			)
		)
			.then( ( pairs ) => {
				setLists( Object.fromEntries( pairs ) );
				return pairs;
			} )
			.catch( ( e ) => setError( errorMessage( e ) ) );
	}, [] );

	// Chỉ mục nơi dùng UX Block chưa dựng (cài mới): dựng theo lô rồi tải lại danh sách.
	const rebuild = useCallback( () => {
		if ( rebuilding.current ) {
			return;
		}
		rebuilding.current = true;
		const step = ( offset ) =>
			rebuildUsage( offset ).then( ( r ) => {
				setProgress( { done: r.next === null ? r.total : r.next, total: r.total } );
				return r.next === null ? load() : step( r.next );
			} );
		step( 0 )
			.catch( ( e ) => setError( errorMessage( e ) ) )
			.finally( () => setProgress( null ) );
	}, [ load ] );

	useEffect( () => {
		load().then( ( pairs ) => {
			if ( pairs && pairs.some( ( [ , d ] ) => ! d.usage_built ) ) {
				rebuild();
			}
		} );
	}, [ load, rebuild ] );

	const change = ( type, id, state ) =>
		setState( type, id, state ).then( ( r ) => {
			setLists( ( prev ) => ( {
				...prev,
				[ type ]: {
					...prev[ type ],
					items: prev[ type ].items.map( ( it ) =>
						it.id === r.id ? { ...it, state: r.state } : it
					),
				},
			} ) );
			return r;
		} );

	const tabs = tabsFor().map( ( t ) => ( {
		name: t.name,
		title: (
			<>
				{ t.label }
				{ lists[ t.name ] && (
					<span className="fbm-count">{ lists[ t.name ].items.length }</span>
				) }
			</>
		),
		className: 'fbm-tab',
	} ) );

	return (
		<div className="fbm-admin">
			<h1 className="fbm-title">
				Block Manager <span className="fbm-version">v{ cfg.version }</span>
			</h1>
			{ error && (
				<Notice status="error" onRemove={ () => setError( '' ) }>
					{ error }
				</Notice>
			) }
			{ progress && (
				<p className="fbm-progress" role="status">
					Đang đếm nơi dùng UX Block… { progress.done }/{ progress.total }
				</p>
			) }
			<TabPanel className="fbm-tabs" tabs={ tabs }>
				{ ( tab ) => {
					const t = TABS.find( ( x ) => x.name === tab.name );
					const data = lists[ tab.name ];
					if ( ! data ) {
						return <Spinner />;
					}
					return (
						<ItemList
							key={ t.name }
							type={ t.name }
							data={ data }
							searchLabel={ t.search }
							note={ t.note }
							onChange={ ( id, state ) => change( t.name, id, state ) }
						/>
					);
				} }
			</TabPanel>
		</div>
	);
}
