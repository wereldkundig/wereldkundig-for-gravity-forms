(function () {
	'use strict';

	document.addEventListener('change', function (event) {
		var theme = event.target;
		if (!theme || !theme.classList || !theme.classList.contains('wk-gf-theme-checkbox')) {
			return;
		}

		var box = theme.closest('.wk-gf-theme');
		if (!box) {
			return;
		}

		box.querySelectorAll('.wk-gf-category-checkbox').forEach(function (category) {
			category.disabled = !theme.checked;
		});
	});
})();
