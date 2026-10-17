/**
 * fbm.ready(selector, init): chạy init(el) một lần cho mỗi phần tử khớp selector,
 * kể cả phần tử được chèn sau khi trang tải (canvas UX Builder, preview Gutenberg, AJAX).
 */
(function (w, d) {
	if (w.fbm && w.fbm.ready) return;

	var handlers = [];
	var observer = null;

	function scan(h, root) {
		var list = [];
		if (root.nodeType === 1 && root.matches && root.matches(h.sel)) list.push(root);
		if (root.querySelectorAll) list.push.apply(list, root.querySelectorAll(h.sel));
		list.forEach(function (el) {
			el.__fbmInit = el.__fbmInit || {};
			if (el.__fbmInit[h.id]) return;
			el.__fbmInit[h.id] = true;
			try {
				h.fn(el);
			} catch (e) {
				if (w.console) console.error('[fbm] ' + h.sel, e);
			}
		});
	}

	function observe() {
		if (observer || !w.MutationObserver) return;
		observer = new MutationObserver(function (mutations) {
			mutations.forEach(function (m) {
				Array.prototype.forEach.call(m.addedNodes, function (node) {
					if (node.nodeType !== 1) return;
					handlers.forEach(function (h) { scan(h, node); });
				});
			});
		});
		observer.observe(d.documentElement, { childList: true, subtree: true });
	}

	w.fbm = {
		ready: function (sel, fn) {
			var h = { sel: sel, fn: fn, id: handlers.length + 1 };
			handlers.push(h);
			var go = function () { scan(h, d); observe(); };
			if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', go);
			else go();
		}
	};
})(window, document);
