/**
 * Shim cho WordPress < 6.6 (chưa có script "react-jsx-runtime" mà build của @wordpress/scripts cần).
 * Chỉ được đăng ký khi WordPress chưa có sẵn handle này.
 */
(function (React) {
	if (window.ReactJSXRuntime || !React) return;
	function jsx(type, props, key) {
		var p = props || {};
		if (key !== undefined) p = Object.assign({}, p, { key: key });
		return React.createElement(type, p);
	}
	window.ReactJSXRuntime = { jsx: jsx, jsxs: jsx, Fragment: React.Fragment };
})(window.React);
