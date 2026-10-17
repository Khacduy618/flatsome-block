/* Product Slider Pro: trượt bằng CSS scroll-snap (vuốt trên mobile, trackpad), thêm nút, chấm,
   kéo chuột, autoplay, vòng lặp. fbm.ready() để chạy cả trong UX Builder và Gutenberg. */
fbm.ready('.fbm-ps', function (root) {
	var track = root.querySelector('.fbm-ps__track');
	if (!track) return;
	var slides = Array.prototype.slice.call(track.children);
	var prev = root.querySelector('.fbm-ps__arrow--prev');
	var next = root.querySelector('.fbm-ps__arrow--next');
	var dotsBox = root.querySelector('.fbm-ps__dots');
	var loop = root.dataset.loop === 'true';
	var byPage = root.dataset.slideBy === 'page';
	var delay = parseInt(root.dataset.autoplay, 10) || 0;
	var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var dots = [];

	function slideWidth() {
		if (!slides.length) return track.clientWidth;
		var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
		return slides[0].getBoundingClientRect().width + gap;
	}
	function perView() { return Math.max(1, Math.round((track.clientWidth + (parseFloat(getComputedStyle(track).columnGap) || 0)) / slideWidth())); }
	function maxScroll() { return track.scrollWidth - track.clientWidth; }
	function atStart() { return track.scrollLeft <= 2; }
	function atEnd() { return track.scrollLeft >= maxScroll() - 2; }
	function pages() { return Math.max(1, Math.ceil(slides.length / perView())); }
	function currentPage() { return atEnd() ? pages() - 1 : Math.round(track.scrollLeft / (slideWidth() * perView())); }

	function go(dir) {
		if (dir > 0 && atEnd()) { if (loop) track.scrollTo({ left: 0 }); return; }
		if (dir < 0 && atStart()) { if (loop) track.scrollTo({ left: maxScroll() }); return; }
		track.scrollBy({ left: dir * slideWidth() * (byPage ? perView() : 1) });
	}
	function goPage(i) { track.scrollTo({ left: Math.min(maxScroll(), i * slideWidth() * perView()) }); }

	function buildDots() {
		if (!dotsBox) return;
		var n = pages();
		if (dots.length === n) return;
		dotsBox.innerHTML = '';
		dots = [];
		for (var i = 0; i < n; i++) {
			var b = document.createElement('button');
			b.type = 'button';
			b.className = 'fbm-ps__dot';
			b.setAttribute('aria-label', 'Trang ' + (i + 1) + ' / ' + n);
			b.addEventListener('click', goPage.bind(null, i));
			dotsBox.appendChild(b);
			dots.push(b);
		}
	}
	function update() {
		var fits = maxScroll() <= 2;
		root.classList.toggle('is-static', fits);
		if (prev) prev.disabled = !loop && atStart();
		if (next) next.disabled = !loop && atEnd();
		buildDots();
		var p = currentPage();
		dots.forEach(function (d, i) { d.setAttribute('aria-current', i === p ? 'true' : 'false'); });
	}

	if (prev) prev.addEventListener('click', function () { go(-1); });
	if (next) next.addEventListener('click', function () { go(1); });
	track.addEventListener('keydown', function (e) {
		if (e.key === 'ArrowRight') { e.preventDefault(); go(1); }
		if (e.key === 'ArrowLeft') { e.preventDefault(); go(-1); }
	});
	var raf = 0;
	track.addEventListener('scroll', function () { cancelAnimationFrame(raf); raf = requestAnimationFrame(update); }, { passive: true });
	window.addEventListener('resize', update);

	// Kéo bằng chuột (cảm ứng và trackpad đã cuộn sẵn được).
	var drag = null;
	var moved = false;
	track.addEventListener('pointerdown', function (e) {
		if (e.pointerType !== 'mouse' || e.button !== 0) return;
		drag = { x: e.clientX, left: track.scrollLeft };
		moved = false;
	});
	window.addEventListener('pointermove', function (e) {
		if (!drag) return;
		var dx = e.clientX - drag.x;
		if (!moved && Math.abs(dx) > 5) { moved = true; track.classList.add('is-dragging'); }
		if (moved) track.scrollLeft = drag.left - dx;
	});
	window.addEventListener('pointerup', function () {
		if (!drag) return;
		drag = null;
		if (!moved) return;
		track.classList.remove('is-dragging');
		var w = slideWidth();
		track.scrollTo({ left: Math.round(track.scrollLeft / w) * w });
	});
	// Kéo xong thì không mở link sản phẩm.
	track.addEventListener('click', function (e) { if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; } }, true);
	track.addEventListener('dragstart', function (e) { e.preventDefault(); });

	// Autoplay: dừng khi rê chuột, khi focus, khi không nhìn thấy, khi tab ẩn.
	if (delay && !reduce) {
		var timer = null;
		var hover = false;
		var visible = true;
		var stop = function () { clearInterval(timer); timer = null; };
		var start = function () {
			stop();
			if (!hover && visible && !document.hidden) timer = setInterval(function () { go(1); }, delay);
		};
		root.addEventListener('mouseenter', function () { hover = true; stop(); });
		root.addEventListener('mouseleave', function () { hover = false; start(); });
		root.addEventListener('focusin', function () { hover = true; stop(); });
		root.addEventListener('focusout', function () { hover = false; start(); });
		document.addEventListener('visibilitychange', start);
		if ('IntersectionObserver' in window) {
			new IntersectionObserver(function (entries) { visible = entries[0].isIntersecting; start(); }, { threshold: 0.3 }).observe(root);
		}
		start();
	}

	update();
});
