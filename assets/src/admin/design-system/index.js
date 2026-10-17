/**
 * Component riêng của plugin. Phần còn lại dùng thẳng @wordpress/components.
 * Xem trang design system: StatusBadge, VisibilityToggle, UsageChip, HelpTip, CheckStep,
 * EmptyState, ConfirmInUse, CodeEditor, PageHeader, Notices.
 */
import {
	createContext,
	forwardRef,
	useCallback,
	useContext,
	useEffect,
	useImperativeHandle,
	useRef,
	useState,
} from '@wordpress/element';
import { Button, Modal, Popover, SnackbarList, Spinner, Notice } from '@wordpress/components';
import { cfg } from '../api';
import { TOPICS } from '../help/topics';
import { GUIDE_TITLES } from '../help/guide-titles';

/* ---------- StatusBadge ---------- */

const BADGES = {
	on: [ 'on', 'Đang bật' ],
	off: [ 'off', 'Đang tắt' ],
	pending: [ 'sched', 'Hẹn giờ · chờ' ],
	running: [ 'sched', 'Hẹn giờ' ],
	expired: [ 'warn', 'Hết hạn' ],
	active: [ 'on', 'Đang chạy' ],
	disabled: [ 'off', 'Tắt' ],
	tripped: [ 'bad', 'Đã ngắt' ],
	draft: [ 'info', 'Nháp' ],
	dirty: [ 'info', 'Nháp có thay đổi' ],
	passed: [ 'on', 'Đạt' ],
	failed: [ 'bad', 'Lỗi' ],
	lint_failed: [ 'bad', 'Lỗi cú pháp' ],
	unavailable: [ 'warn', 'Chưa chạy thử được' ],
	untested: [ 'warn', 'Chưa chạy thử' ],
	stale: [ 'warn', 'Cần chạy thử lại' ],
	none: [ 'off', 'Chưa kiểm tra' ],
	trash: [ 'off', 'Thùng rác' ],
};

export function StatusBadge( { status, label } ) {
	const [ tone, text ] = BADGES[ status ] || [ 'off', status ];
	return <span className={ `fbm-badge fbm-badge--${ tone }` }>{ label || text }</span>;
}

/* ---------- VisibilityToggle ---------- */

export function VisibilityToggle( { visibility, onChange, busy, label } ) {
	const on = !! visibility?.enabled;
	const scheduled = on && ( visibility?.from || visibility?.until );
	return (
		<span className="fbm-vis">
			<button
				type="button"
				role="switch"
				aria-checked={ on }
				aria-label={ label }
				className={ `fbm-switch${ scheduled ? ' fbm-switch--sched' : '' }` }
				disabled={ busy }
				onClick={ () => onChange( ! on ) }
			/>
			<StatusBadge status={ visibility?.state || ( on ? 'on' : 'off' ) } />
		</span>
	);
}

export function Switch( { checked, onChange, label, disabled } ) {
	return (
		<button
			type="button"
			role="switch"
			aria-checked={ !! checked }
			aria-label={ label }
			className="fbm-switch"
			disabled={ disabled }
			onClick={ () => onChange( ! checked ) }
		/>
	);
}

/* ---------- UsageChip ---------- */

export function UsageChip( { count, onClick } ) {
	if ( count === null || count === undefined ) {
		return <span className="fbm-chip fbm-chip--zero">—</span>;
	}
	if ( count === 0 ) {
		return <span className="fbm-chip fbm-chip--zero">Chưa dùng</span>;
	}
	return (
		<button type="button" className="fbm-chip fbm-chip--btn" onClick={ onClick }>
			{ count } nơi
		</button>
	);
}

/* ---------- HelpTip ---------- */

export function HelpTip( { topic } ) {
	const t = TOPICS[ topic ];
	const ref = useRef();
	const [ open, setOpen ] = useState( false );
	if ( ! t ) {
		return null;
	}
	return (
		<span className="fbm-help-wrap">
			<button
				ref={ ref }
				type="button"
				className="fbm-help"
				aria-label={ `Giải thích: ${ t.title }` }
				aria-expanded={ open }
				onClick={ () => setOpen( ( o ) => ! o ) }
			>
				?
			</button>
			{ open && (
				<Popover
					anchor={ ref.current }
					placement="bottom-start"
					className="fbm-help-pop"
					onClose={ () => setOpen( false ) }
					onFocusOutside={ ( e ) => {
						if ( ref.current && e && ref.current.contains( e.relatedTarget ) ) {
							return;
						}
						setOpen( false );
					} }
				>
					<div className="fbm-help-pop__body">
						<strong>{ t.title }</strong>
						<p>{ t.text }</p>
						<a href={ `#/guide/${ t.guide }` } onClick={ () => setOpen( false ) }>
							Xem hướng dẫn: { GUIDE_TITLES[ t.guide ] || 'Hướng dẫn' } →
						</a>
					</div>
				</Popover>
			) }
		</span>
	);
}

/** Nhãn có nút "?" đi kèm. */
export function Label( { children, help, htmlFor } ) {
	return (
		<span className="fbm-label">
			{ htmlFor ? <label htmlFor={ htmlFor }>{ children }</label> : children }
			{ help && <HelpTip topic={ help } /> }
		</span>
	);
}

/* ---------- CheckStep ---------- */

export function CheckStep( { state, title, detail, children } ) {
	const icon = { ok: '✓', bad: '!', warn: '!', wait: '·', run: '' }[ state ] ?? '·';
	return (
		<div className="fbm-check">
			<span className={ `fbm-check__ic fbm-check__ic--${ state }` }>{ state === 'run' ? <Spinner /> : icon }</span>
			<div className="fbm-check__body">
				<b>{ title }</b>
				{ detail && <p>{ detail }</p> }
				{ children }
			</div>
		</div>
	);
}

export function Issue( { issue, warn, onClick } ) {
	const where = issue.line ? `${ issue.field || '' } dòng ${ issue.line }`.trim() : issue.field || '';
	const body = (
		<>
			{ where && <b>{ where }: </b> }
			<code>{ issue.message }</code>
		</>
	);
	return onClick ? (
		<button type="button" className={ `fbm-issue${ warn ? ' fbm-issue--warn' : '' }` } onClick={ onClick }>
			{ body }
		</button>
	) : (
		<div className={ `fbm-issue${ warn ? ' fbm-issue--warn' : '' }` }>{ body }</div>
	);
}

/* ---------- EmptyState ---------- */

export function EmptyState( { title, children, action } ) {
	return (
		<div className="fbm-empty">
			<strong>{ title }</strong>
			{ children && <p>{ children }</p> }
			{ action }
		</div>
	);
}

/* ---------- PageHeader ---------- */

export function PageHeader( { title, count, badges, actions, back } ) {
	return (
		<div className="fbm-page-head">
			<div className="fbm-page-head__title">
				{ back && (
					<a className="fbm-back" href={ back }>
						← Quay lại
					</a>
				) }
				<h1>{ title }</h1>
				{ count !== undefined && <span className="fbm-chip">{ count }</span> }
				{ badges }
			</div>
			{ actions && <div className="fbm-page-head__actions">{ actions }</div> }
		</div>
	);
}

export function Panel( { title, help, actions, children, className = '' } ) {
	return (
		<section className={ `fbm-panel ${ className }` }>
			{ ( title || actions ) && (
				<header className="fbm-panel__head">
					<span className="fbm-panel__title">
						{ title }
						{ help && <HelpTip topic={ help } /> }
					</span>
					{ actions }
				</header>
			) }
			<div className="fbm-panel__body">{ children }</div>
		</section>
	);
}

export function Loading( { label = 'Đang tải…' } ) {
	return (
		<div className="fbm-loading">
			<Spinner /> { label }
		</div>
	);
}

export function ErrorNotice( { error, onRetry } ) {
	if ( ! error ) {
		return null;
	}
	return (
		<Notice status="error" isDismissible={ false } actions={ onRetry ? [ { label: 'Thử lại', onClick: onRetry } ] : [] }>
			{ error.message || String( error ) }
		</Notice>
	);
}

/* ---------- ConfirmInUse ---------- */

export function UsageList( { usages } ) {
	if ( ! usages || ! usages.length ) {
		return null;
	}
	return (
		<ul className="fbm-usage-list">
			{ usages.map( ( u, i ) => (
				<li key={ i }>
					<span>{ u.edit ? <a href={ u.edit }>{ u.label }</a> : u.label }</span>
					<span className="fbm-muted">{ u.where }</span>
				</li>
			) ) }
		</ul>
	);
}

/**
 * Hộp xác nhận khi thao tác ảnh hưởng tới block đang được dùng.
 * actions: [{ label, variant, onClick, isDestructive }]
 */
export function ConfirmInUse( { title, message, usages, actions, onClose } ) {
	return (
		<Modal title={ title } onRequestClose={ onClose } className="fbm-modal">
			<div className="fbm-stack">
				{ message && <p>{ message }</p> }
				<UsageList usages={ usages } />
				<div className="fbm-modal__actions">
					<Button variant="tertiary" onClick={ onClose }>
						Huỷ
					</Button>
					{ actions.map( ( a ) => (
						<Button key={ a.label } variant={ a.variant || 'secondary' } isDestructive={ a.isDestructive } onClick={ a.onClick }>
							{ a.label }
						</Button>
					) ) }
				</div>
			</div>
		</Modal>
	);
}

/* ---------- CodeEditor (CodeMirror của WP) ---------- */

export const CodeEditor = forwardRef( function CodeEditor( { value, onChange, mode = 'php', errorLines = [], height = 440, label }, ref ) {
	const textarea = useRef();
	const cmRef = useRef();
	const onChangeRef = useRef( onChange );
	onChangeRef.current = onChange;
	const settings = cfg.codeEditor && cfg.codeEditor[ mode ];
	const enabled = !! ( settings && window.wp && window.wp.codeEditor );

	useEffect( () => {
		if ( ! enabled ) {
			return undefined;
		}
		const instance = window.wp.codeEditor.initialize( textarea.current, settings );
		const cm = instance.codemirror;
		cm.setSize( null, height );
		cm.on( 'change', () => onChangeRef.current && onChangeRef.current( cm.getValue() ) );
		cmRef.current = cm;
		return () => {
			cm.toTextArea();
			cmRef.current = null;
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ mode, enabled ] );

	useEffect( () => {
		const cm = cmRef.current;
		if ( cm && cm.getValue() !== ( value || '' ) ) {
			const cursor = cm.getCursor();
			cm.setValue( value || '' );
			cm.setCursor( cursor );
		}
	}, [ value ] );

	const linesKey = errorLines.join( ',' );
	useEffect( () => {
		const cm = cmRef.current;
		if ( ! cm ) {
			return undefined;
		}
		const handles = errorLines
			.filter( ( l ) => l > 0 && l <= cm.lineCount() )
			.map( ( l ) => cm.addLineClass( l - 1, 'background', 'fbm-cm-error' ) );
		return () => handles.forEach( ( h ) => cm.removeLineClass( h, 'background', 'fbm-cm-error' ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ linesKey, value ] );

	useImperativeHandle( ref, () => ( {
		goto( line ) {
			const cm = cmRef.current;
			if ( ! cm ) {
				return;
			}
			cm.focus();
			cm.setCursor( { line: Math.max( 0, line - 1 ), ch: 0 } );
			cm.scrollIntoView( { line: Math.max( 0, line - 1 ), ch: 0 }, 120 );
		},
		insert( text ) {
			const cm = cmRef.current;
			if ( cm ) {
				cm.replaceSelection( text );
				cm.focus();
			} else {
				onChange( ( value || '' ) + text );
			}
		},
	} ) );

	if ( ! enabled ) {
		return (
			<textarea
				className="fbm-code-fallback"
				value={ value || '' }
				onChange={ ( e ) => onChange( e.target.value ) }
				style={ { height } }
				aria-label={ label }
				spellCheck={ false }
			/>
		);
	}
	return (
		<div className="fbm-code">
			<textarea ref={ textarea } defaultValue={ value || '' } aria-label={ label } />
		</div>
	);
} );

/* ---------- Notices (snackbar) ---------- */

const NoticeContext = createContext( () => {} );

export function NoticesProvider( { children } ) {
	const [ list, setList ] = useState( [] );
	const remove = useCallback( ( id ) => setList( ( l ) => l.filter( ( n ) => n.id !== id ) ), [] );
	const push = useCallback(
		( content, options = {} ) => {
			const id = Math.random().toString( 36 ).slice( 2 );
			setList( ( l ) => [ ...l.slice( -3 ), { id, content, spokenMessage: content, actions: options.actions || [] } ] );
			setTimeout( () => remove( id ), options.timeout || 6000 );
		},
		[ remove ]
	);
	return (
		<NoticeContext.Provider value={ push }>
			{ children }
			<SnackbarList notices={ list } onRemove={ remove } className="fbm-snackbars" />
		</NoticeContext.Provider>
	);
}

export function useNotice() {
	return useContext( NoticeContext );
}
