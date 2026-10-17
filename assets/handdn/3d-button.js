/**
 * HANDDN: đưa nút "Customize this strap in 3D" xuống ngay sau phần field APF.
 * APF có thể render trễ nên theo dõi DOM tối đa 10 giây, xong là ngừng.
 */
( () => {
	const APF_SELECTOR = '.apf-fields-wrap';
	const link = document.querySelector( 'a[data-handdn-3d="form"]' );

	if ( ! link ) {
		return;
	}

	const place = () => {
		const fields = document.querySelector( APF_SELECTOR );
		if ( ! fields ) {
			return false;
		}
		if ( fields.nextElementSibling !== link ) {
			fields.insertAdjacentElement( 'afterend', link );
		}
		return true;
	};

	if ( place() ) {
		return;
	}

	const observer = new MutationObserver( () => {
		if ( place() ) {
			observer.disconnect();
			clearTimeout( timer );
		}
	} );
	const timer = setTimeout( () => observer.disconnect(), 10000 );

	observer.observe( document.body, { childList: true, subtree: true } );
} )();
