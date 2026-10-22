/**
 * Block Manager v2: 3 list tabs. Users without manage_options only see the UX Blocks tab.
 */
import { useCallback, useEffect, useRef, useState } from '@wordpress/element';
import { Notice, Spinner, TabPanel } from '@wordpress/components';
import ItemList from './ItemList';
import { cfg, errorMessage, getItems, rebuildUsage, setState } from './api';

const TABS = [
	{
		name: 'ux_block',
		label: 'UX Blocks',
		search: 'Search UX Blocks…',
		note: 'To create, edit, rename or delete UX Blocks, use Flatsome\'s UX Blocks menu and UX Builder.',
	},
	{
		name: 'plugin',
		label: 'Plugin Elements',
		search: 'Search plugin elements…',
		note: 'Elements shipped with the plugin. To change their code, edit the files and install a new zip; they cannot be edited in the admin.',
	},
	{
		name: 'flatsome',
		label: 'Flatsome Elements',
		search: 'Search Flatsome elements…',
		note: 'Theme elements. Layout elements are locked On.',
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

	// UX Block usage index not built yet (fresh install): build it in batches, then reload the list.
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
					Counting UX Block usage… { progress.done }/{ progress.total }
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
