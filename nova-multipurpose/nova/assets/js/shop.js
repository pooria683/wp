/* Nova — shop.js : AJAX filters, sorting, view switch, pagination */
(function () {
	'use strict';

	var NOVA = window.NOVA || {};
	var UI = window.NovaUI || {};

	function shop() { return document.querySelector('.nv-shop'); }

	function loading(on) {
		var s = document.querySelector('.nv-shop__content');
		if (s) { s.classList.toggle('is-loading', on); }
	}

	function collectFilters() {
		var data = {};
		document.querySelectorAll('[data-nv-filters] input[type="checkbox"]:checked, [data-nv-filters] input[type="range"]').forEach(function (input) {
			var name = (input.name || '').replace('[]', '');
			if (!name) { return; }
			if (input.type === 'checkbox') {
				data[name] = data[name] || [];
				data[name].push(input.value);
			} else {
				data[name] = input.value;
			}
		});
		var sort = document.querySelector('[data-nv-orderby]');
		if (sort) { data.orderby = sort.value; }
		return data;
	}

	function render(html) {
		var list = document.querySelector('.nv-shop__content .products');
		if (list) {
			var wrap = document.createElement('ul');
			wrap.innerHTML = html;
			list.innerHTML = wrap.innerHTML;
		}
		// Re-run the theme's card scripts for the new markup.
		document.dispatchEvent(new CustomEvent('nova:shop-updated'));
	}

	function apply(page) {
		loading(true);
		var data = collectFilters();
		if (page) { data.page = page; }
		UI.post('filter', data).then(function (res) {
			render(res.html || '');
			var count = document.querySelector('.nv-shop__count');
			if (count) { count.textContent = res.found + ' محصول'; }
			loading(false);
		}).catch(function () {
			loading(false);
			// Graceful degradation: fall back to a normal page reload.
			var params = new URLSearchParams();
			Object.keys(collectFilters()).forEach(function (k) {
				var v = collectFilters()[k];
				if (Array.isArray(v)) { params.set(k, v.join(',')); } else { params.set(k, v); }
			});
			location.search = params.toString();
		});
	}

	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-nv-apply-filters]')) {
			e.preventDefault();
			apply(1);
		}
		if (e.target.closest('[data-nv-filters-toggle]')) {
			var f = document.querySelector('[data-nv-filters]');
			if (f) { f.classList.add('is-open'); }
		}
		if (e.target.closest('[data-nv-filters-close]')) {
			var f2 = document.querySelector('[data-nv-filters]');
			if (f2) { f2.classList.remove('is-open'); }
		}
		var view = e.target.closest('[data-view]');
		if (view) {
			var s = shop();
			if (s) {
				s.classList.toggle('is-list', view.getAttribute('data-view') === 'list');
				document.querySelectorAll('.nv-viewswitch button').forEach(function (b) { b.classList.toggle('is-active', b === view); });
				try { localStorage.setItem('nova-shop-view', view.getAttribute('data-view')); } catch (err) {}
			}
		}
	});

	document.addEventListener('change', function (e) {
		if (e.target.matches('[data-nv-orderby]')) { apply(1); }
	});

	// Range live label.
	document.addEventListener('input', function (e) {
		var range = e.target.closest('.nv-range input[type="range"]');
		if (!range) { return; }
		var out = range.parentNode.querySelector('output');
		if (out) { out.textContent = Number(range.value).toLocaleString('fa-IR'); }
	});

	function boot() {
		// Restore the last used view.
		try {
			var saved = localStorage.getItem('nova-shop-view');
			if (saved === 'list') {
				var btn = document.querySelector('.nv-viewswitch [data-view="list"]');
				btn && btn.click();
			}
		} catch (err) {}

		// Keep the product cards in sync after WooCommerce's own ajax add-to-cart.
		if (window.jQuery) {
			window.jQuery(document.body).on('added_to_cart', function () {
				if (UI.toast) { UI.toast((NOVA.i18n && NOVA.i18n.added) || 'added'); }
			});
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
