import { cfg } from './api';
import { useRoute } from './hooks';
import BlocksList from './screens/BlocksList';
import BlockEdit from './screens/BlockEdit';
import ElementsList from './screens/ElementsList';
import ElementEdit from './screens/ElementEdit';
import Transfer from './screens/Transfer';
import Settings from './screens/Settings';
import Guide from './screens/Guide';

const NAV = [
	[ 'blocks', 'Blocks', true ],
	[ 'elements', 'Elements', cfg.modules.elements && cfg.user.canEditCode ],
	[ 'transfer', 'Xuất / Nhập', cfg.modules.transfer ],
	[ 'settings', 'Cài đặt', cfg.user.canManageSettings ],
	[ 'guide', 'Hướng dẫn', true ],
];

function Screen( { parts } ) {
	const [ section = 'blocks', id ] = parts;
	switch ( section ) {
		case 'blocks':
			return id ? <BlockEdit key={ id } id={ id } /> : <BlocksList />;
		case 'elements':
			return id ? <ElementEdit key={ id } id={ id } /> : <ElementsList />;
		case 'transfer':
			return <Transfer />;
		case 'settings':
			return <Settings />;
		case 'guide':
			return <Guide topic={ id } />;
		default:
			return <BlocksList />;
	}
}

export default function App() {
	const parts = useRoute();
	const section = parts[ 0 ] || 'blocks';
	return (
		<div className="fbm">
			<header className="fbm-top">
				<span className="fbm-brand">
					Block Manager <span className="fbm-muted">v{ cfg.version }</span>
				</span>
				<nav className="fbm-nav" aria-label="Block Manager">
					{ NAV.filter( ( n ) => n[ 2 ] ).map( ( [ key, label ] ) => (
						<a key={ key } href={ `#/${ key }` } className={ section === key ? 'is-active' : '' } aria-current={ section === key ? 'page' : undefined }>
							{ label }
						</a>
					) ) }
				</nav>
			</header>
			<main className="fbm-main">
				<Screen parts={ parts } />
			</main>
		</div>
	);
}
