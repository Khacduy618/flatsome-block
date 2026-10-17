fbm.ready('.fbm-bac', function (el) {
	var track = el.querySelector('.fbm-bac__track');
	var prev = el.querySelector('.fbm-bac__nav--prev');
	var next = el.querySelector('.fbm-bac__nav--next');
	if (!track || !prev || !next) return;

	function step() {
		var first = track.firstElementChild;
		var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
		return first ? first.getBoundingClientRect().width + gap : track.clientWidth;
	}
	function update() {
		prev.disabled = track.scrollLeft <= 1;
		next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 1;
	}
	prev.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); });
	next.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); });
	track.addEventListener('scroll', update, { passive: true });
	window.addEventListener('resize', update);
	update();
});
