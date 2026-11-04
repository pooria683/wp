/* Nova — portfolio.js : client side category filter + optional load more */
(function () {
	'use strict';

	function init(root) {
		var buttons = root.querySelectorAll('[data-filter]');
		var items = root.querySelectorAll('.nv-project');

		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var filter = btn.getAttribute('data-filter');
				buttons.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
				items.forEach(function (item) {
					var cats = (item.getAttribute('data-cats') || '').split(' ');
					var show = filter === '*' || cats.indexOf(filter) > -1;
					item.style.display = show ? '' : 'none';
					if (show) {
						item.animate(
							[{ opacity: 0, transform: 'translateY(12px)' }, { opacity: 1, transform: 'none' }],
							{ duration: 260, easing: 'ease-out' }
						);
					}
				});
			});
		});
	}

	function boot() {
		document.querySelectorAll('[data-nv-portfolio]').forEach(init);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
