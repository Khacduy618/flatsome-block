import { useState } from '@wordpress/element';
import { Button, Notice } from '@wordpress/components';
import { api, errorMessage } from '../api';
import { useResource } from '../hooks';
import { HelpTip } from '../design-system';

/** Quét toàn bộ nội dung theo lô 100 bài, có thanh tiến độ. */
export async function rebuildUsage( onProgress ) {
	let offset = 0;
	for ( ;; ) {
		const res = await api.post( '/usage/rebuild', { offset } );
		onProgress( res );
		if ( res.done ) {
			return res;
		}
		offset = res.next_offset;
	}
}

export function UsageProgress( { progress } ) {
	if ( ! progress ) {
		return null;
	}
	const pct = progress.total ? Math.min( 100, Math.round( ( progress.next_offset / progress.total ) * 100 ) ) : 100;
	return (
		<div className="fbm-progress" role="progressbar" aria-valuenow={ pct } aria-valuemin={ 0 } aria-valuemax={ 100 }>
			<span style={ { width: pct + '%' } } />
			<em>
				{ progress.next_offset } / { progress.total } bài
			</em>
		</div>
	);
}

export default function UsageIndexNotice( { onDone } ) {
	const status = useResource( () => api.get( '/usage/status' ), [] );
	const [ progress, setProgress ] = useState( null );
	const [ error, setError ] = useState( null );

	if ( ! status.data || status.data.built ) {
		return null;
	}
	const run = async () => {
		setError( null );
		try {
			await rebuildUsage( setProgress );
			status.reload();
			onDone();
		} catch ( e ) {
			setError( errorMessage( e ) );
		}
	};
	return (
		<Notice status="info" isDismissible={ false }>
			<div className="fbm-stack fbm-stack--tight">
				<span>
					Chưa có chỉ mục nơi dùng. Quét một lần để biết block nào đang được dùng ở đâu.
					<HelpTip topic="usageIndex" />
				</span>
				<UsageProgress progress={ progress } />
				{ error && <span className="fbm-error-text">{ error }</span> }
				<div>
					<Button variant="primary" size="small" onClick={ run } disabled={ !! progress && ! progress.done }>
						Quét ngay
					</Button>
				</div>
			</div>
		</Notice>
	);
}
