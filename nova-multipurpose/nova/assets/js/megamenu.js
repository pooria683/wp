/* Nova — megamenu.js : keyboard + touch support for mega panels (CSS does the hover) */
(function () {
	'use strict';

	function boot() {
		document.querySelectorAll('.nv-menu > li').forEach(function (li) {
			var panel = li.querySelector('.nv-mega');
			if (!panel) { return; }
			var link = li.querySelector(':scope > a');

			// Touch devices: first tap opens the panel, second follows the link.
			li.addEventListener('touchstart', function () {
				if (!li.classList.contains('is-open')) {
					li.classList.add('is-open');
				}
			}, { passive: true });

			link && link.addEventListener('keydown', function (e) {
				if (e.key === 'ArrowDown' || e.key === 'Enter') {
					e.preventDefault();
					li.classList.add('is-open');
					var first = panel.querySelector('a, button');
					first && first.focus();
				}
			});

			document.addEventListener('click', function (e) {
				if (!li.contains(e.target)) { li.classList.remove('is-open'); }
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
