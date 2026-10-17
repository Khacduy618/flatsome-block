/* fbm.ready() chạy hàm khởi tạo cho mọi .fbm-ba trên trang, kể cả phần tử được chèn sau
   (canvas UX Builder, preview Gutenberg). Mỗi phần tử chỉ được khởi tạo một lần. */
fbm.ready('.fbm-ba', function (el) {
	var stage = el.querySelector('.fbm-ba__stage');
	var range = el.querySelector('.fbm-ba__range');
	var vertical = el.classList.contains('fbm-ba--v');
	var dragging = false;

	function set(p) {
		p = Math.max(0, Math.min(100, p));
		el.style.setProperty('--fbm-pos', p + '%');
		range.value = p;
	}
	function fromEvent(e) {
		var r = stage.getBoundingClientRect();
		return vertical ? (e.clientY - r.top) / r.height * 100 : (e.clientX - r.left) / r.width * 100;
	}

	if (el.dataset.trigger === 'hover') {
		stage.addEventListener('pointermove', function (e) {
			if (e.pointerType === 'mouse' && !dragging) set(fromEvent(e));
		});
	}
	stage.addEventListener('pointerdown', function (e) {
		dragging = true;
		el.classList.remove('fbm-ba--animating');
		if (stage.setPointerCapture) stage.setPointerCapture(e.pointerId);
		set(fromEvent(e));
	});
	stage.addEventListener('pointermove', function (e) {
		if (dragging) set(fromEvent(e));
	});
	['pointerup', 'pointercancel', 'lostpointercapture'].forEach(function (type) {
		stage.addEventListener(type, function () { dragging = false; });
	});
	range.addEventListener('input', function () { set(parseFloat(range.value)); });

	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	if (el.dataset.auto === 'true' && !reduce && 'IntersectionObserver' in window) {
		var start = parseFloat(el.dataset.start) || 50;
		var io = new IntersectionObserver(function (entries) {
			if (!entries[0].isIntersecting) return;
			io.disconnect();
			el.classList.add('fbm-ba--animating');
			var steps = [Math.max(10, start - 35), Math.min(90, start + 35), start];
			var i = 0;
			(function next() {
				if (i >= steps.length || dragging) {
					setTimeout(function () { el.classList.remove('fbm-ba--animating'); }, 950);
					return;
				}
				set(steps[i++]);
				setTimeout(next, 1000);
			})();
		}, { threshold: 0.5 });
		io.observe(el);
	}
});
