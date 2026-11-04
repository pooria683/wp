/*!
 * Nova — core.js
 * Tiny (~4KB gz) runtime: no dependencies, no globals leaking.
 * Everything is progressive enhancement: the site works without JS.
 */
(function () {
	'use strict';

	var NOVA = window.NOVA || { ajax: '', nonce: '', i18n: {} };
	var doc = document;
	var on = function (el, ev, fn, opt) { el && el.addEventListener(ev, fn, opt || false); };
	var qs = function (s, c) { return (c || doc).querySelector(s); };
	var qsa = function (s, c) { return Array.prototype.slice.call((c || doc).querySelectorAll(s)); };

	/* --------------------------------------------------------------- util */

	function toast(msg, isError) {
		var el = qs('.nv-toast');
		if (!el) {
			el = doc.createElement('div');
			el.className = 'nv-toast';
			el.setAttribute('role', 'status');
			doc.body.appendChild(el);
		}
		el.textContent = msg;
		el.classList.toggle('is-error', !!isError);
		el.classList.add('is-open');
		clearTimeout(el._t);
		el._t = setTimeout(function () { el.classList.remove('is-open'); }, 3200);
	}

	function post(action, data) {
		var body = new FormData();
		body.append('action', 'nova_' + action);
		body.append('nonce', NOVA.nonce);
		Object.keys(data || {}).forEach(function (k) {
			var v = data[k];
			if (Array.isArray(v)) {
				v.forEach(function (item) { body.append(k + '[]', item); });
			} else {
				body.append(k, v);
			}
		});
		return fetch(NOVA.ajax, { method: 'POST', credentials: 'same-origin', body: body })
			.then(function (r) { return r.json(); })
			.then(function (res) {
				if (!res || !res.success) {
					throw new Error((res && res.data && res.data.message) || NOVA.i18n.error);
				}
				return res.data;
			});
	}

	function modal(html) {
		var m = qs('#nv-modal');
		if (!m) {
			m = doc.createElement('div');
			m.id = 'nv-modal';
			m.className = 'nv-modal';
			m.innerHTML = '<div class="nv-modal__box" role="dialog" aria-modal="true">' +
				'<button class="nv-modal__close" type="button" aria-label="' + (NOVA.i18n.close || 'close') + '">&times;</button>' +
				'<div class="nv-modal__content"></div></div>';
			doc.body.appendChild(m);
			on(m, 'click', function (e) {
				if (e.target === m || e.target.closest('.nv-modal__close')) { closeModal(); }
			});
		}
		qs('.nv-modal__content', m).innerHTML = html;
		m.classList.add('is-open');
		doc.body.style.overflow = 'hidden';
		var focusable = qs('.nv-modal__close', m);
		focusable && focusable.focus();
	}

	function closeModal() {
		var m = qs('#nv-modal');
		if (m) { m.classList.remove('is-open'); }
		doc.body.style.overflow = '';
	}

	window.NovaUI = { toast: toast, post: post, modal: modal, closeModal: closeModal };

	/* ------------------------------------------------------------- header */

	function initHeader() {
		var header = qs('.nv-header');
		var sentinel = doc.createElement('div');
		sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px';
		if (!header) { return; }

		var isTransparent = doc.body.classList.contains('nv-header-transparent');
		if (isTransparent) {
			doc.body.insertBefore(sentinel, doc.body.firstChild);
			var io0 = new IntersectionObserver(function (entries) {
				header.classList.toggle('is-scrolled', !entries[0].isIntersecting);
			}, { threshold: 0 });
			io0.observe(sentinel);
		} else if (doc.body.classList.contains('nv-header-sticky-on')) {
			var last = 0;
			window.addEventListener('scroll', function () {
				var y = window.pageYOffset;
				header.classList.toggle('is-scrolled', y > 8);
				last = y;
			}, { passive: true });
		}

		// Mobile drawer.
		var burger = qs('.nv-burger');
		var drawer = qs('#nv-mobilenav');
		if (burger && drawer) {
			drawer.hidden = false;
			var toggle = function (open) {
				drawer.classList.toggle('is-open', open);
				burger.setAttribute('aria-expanded', open ? 'true' : 'false');
				doc.body.style.overflow = open ? 'hidden' : '';
				if (open) {
					var first = qs('a, button', drawer);
					first && first.focus();
				}
			};
			on(burger, 'click', function () { toggle(!drawer.classList.contains('is-open')); });
			on(qs('.nv-mobilenav__close'), 'click', function () { toggle(false); });
			on(drawer, 'click', function (e) { if (e.target === drawer) { toggle(false); } });
			on(doc, 'keydown', function (e) { if (e.key === 'Escape') { toggle(false); } });
		}

		// Search popup with live results.
		var opener = qs('[data-nv-open="search"]');
		var box = qs('.nv-searchbox');
		if (opener && box) {
			on(opener, 'click', function () {
				box.hidden = !box.hidden;
				if (!box.hidden) {
					var input = qs('input[type="search"]', box);
					input && input.focus();
				}
			});
			var timer;
			var liveInput = qs('[data-nv-live-search]');
			var results = qs('.nv-live-search');
			if (liveInput && results) {
				on(liveInput, 'input', function () {
					clearTimeout(timer);
					var value = liveInput.value.trim();
					if (value.length < 2) { results.innerHTML = ''; return; }
					timer = setTimeout(function () {
						post('search', { s: value }).then(function (data) {
							results.innerHTML = data.html || '';
						}).catch(function () {});
					}, 320);
				});
			}
		}

		// Dark mode toggle (remembers the choice).
		var themeBtn = qs('[data-nv-theme-toggle]');
		if (themeBtn) {
			var saved = localStorage.getItem('nova-theme');
			if (saved === 'dark') { doc.body.classList.add('nv-dark'); }
			on(themeBtn, 'click', function () {
				var dark = doc.body.classList.toggle('nv-dark');
				localStorage.setItem('nova-theme', dark ? 'dark' : 'light');
			});
		}
	}

	/* --------------------------------------------------------- back to top */

	function initToTop() {
		var btn = qs('.nv-totop');
		if (!btn) { return; }
		var ticking = false;
		window.addEventListener('scroll', function () {
			if (ticking) { return; }
			ticking = true;
			requestAnimationFrame(function () {
				btn.classList.toggle('is-visible', window.pageYOffset > 600);
				ticking = false;
			});
		}, { passive: true });
		on(btn, 'click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
	}

	/* ---------------------------------------------------------- quantity */

	function initQty() {
		doc.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-qty]');
			if (!btn) { return; }
			var wrap = btn.closest('.nv-qty') || btn.parentNode;
			var input = qs('input[type="number"]', wrap);
			if (!input) { return; }
			var step = parseInt(btn.getAttribute('data-qty'), 10) || 1;
			var min = parseInt(input.min, 10) || 1;
			var next = (parseInt(input.value, 10) || 0) + step;
			input.value = Math.max(min, next);
			input.dispatchEvent(new Event('change', { bubbles: true }));
		});
	}

	/* ----------------------------------------------------- ajax counters */

	function setCount(sel, n) {
		var el = qs(sel);
		if (el) { el.textContent = n; }
	}

	function toggleList(action, id, btn) {
		post(action, { id: id }).then(function (data) {
			btn.classList.toggle('is-on', data.in_list);
			setCount(action === 'wishlist' ? '[data-wishlist-count]' : '[data-compare-count]', data.count);
			toast(data.in_list
				? (action === 'wishlist' ? 'به علاقه‌مندی‌ها اضافه شد' : 'به لیست مقایسه اضافه شد')
				: 'از لیست حذف شد');
		}).catch(function (err) { toast(err.message, true); });
	}

	doc.addEventListener('click', function (e) {
		var w = e.target.closest('[data-nv-wishlist]');
		if (w) { e.preventDefault(); toggleList('wishlist', w.getAttribute('data-nv-wishlist'), w); return; }
		var c = e.target.closest('[data-nv-compare]');
		if (c) { e.preventDefault(); toggleList('compare', c.getAttribute('data-nv-compare'), c); return; }
		var q = e.target.closest('[data-nv-quickview]');
		if (q) {
			e.preventDefault();
			modal('<div class="nv-modal__loading">' + (NOVA.i18n.loading || '…') + '</div>');
			post('quickview', { id: q.getAttribute('data-nv-quickview') })
				.then(function (data) { modal(data.html); })
				.catch(function (err) { closeModal(); toast(err.message, true); });
			return;
		}
	});

	/* -------------------------------------------------------- notify form */

	on(doc, 'submit', function (e) {
		var form = e.target.closest('[data-nv-notify]');
		if (!form) { return; }
		e.preventDefault();
		var msg = qs('.nv-notify__msg', form);
		var phone = qs('input[name="phone"]', form).value;
		post('notify_stock', { id: form.getAttribute('data-nv-notify'), phone: phone })
			.then(function (data) {
				form.reset();
				if (msg) { msg.textContent = data.message; msg.className = 'nv-notify__msg is-ok'; }
			})
			.catch(function (err) {
				if (msg) { msg.textContent = err.message; msg.className = 'nv-notify__msg is-error'; }
			});
	});

	/* -------------------------------------------------------- 360 viewer */

	doc.addEventListener('click', function (e) {
		var open = e.target.closest('[data-nv-360]');
		if (open) {
			e.preventDefault();
			init360(JSON.parse(open.getAttribute('data-nv-360')));
		}
	});

	function init360(frames) {
		var root = qs('[data-nv-360-modal]');
		if (!root || !frames.length) { return; }
		var stage = qs('.nv-360__stage', root);
		var img = doc.createElement('img');
		img.src = frames[0];
		img.alt = '';
		stage.innerHTML = '';
		stage.appendChild(img);
		root.hidden = false;
		doc.body.style.overflow = 'hidden';

		var index = 0;
		var dragging = false;
		var startX = 0;
		var startIndex = 0;

		var render = function (i) {
			index = (i + frames.length) % frames.length;
			img.src = frames[index];
		};

		var down = function (x) { dragging = true; startX = x; startIndex = index; };
		var move = function (x) {
			if (!dragging) { return; }
			var delta = Math.round((startX - x) / 10);
			if (delta !== 0) { render(startIndex + delta); }
		};
		var up = function () { dragging = false; };

		stage.onmousedown = function (ev) { down(ev.clientX); ev.preventDefault(); };
		stage.onmousemove = function (ev) { move(ev.clientX); };
		doc.addEventListener('mouseup', up);
		stage.ontouchstart = function (ev) { down(ev.touches[0].clientX); };
		stage.ontouchmove = function (ev) { move(ev.touches[0].clientX); };
		stage.ontouchend = up;
		stage.onkeydown = function (ev) {
			if (ev.key === 'ArrowLeft') { render(index - 1); }
			if (ev.key === 'ArrowRight') { render(index + 1); }
		};
		stage.tabIndex = 0;
		stage.focus();

		var close = function () {
			root.hidden = true;
			doc.body.style.overflow = '';
		};
		on(qs('.nv-360__close', root), 'click', close);
		on(doc, 'keydown', function (ev) { if (ev.key === 'Escape' && !root.hidden) { close(); } });
	}

	/* ---------------------------------------------------- ajax pagination */

	doc.addEventListener('click', function (e) {
		var more = e.target.closest('[data-nv-load-more]');
		if (!more) { return; }
		e.preventDefault();
		var page = parseInt(more.getAttribute('data-page'), 10) || 2;
		more.classList.add('is-loading');
		post('load_more', {
			page: page,
			type: more.getAttribute('data-type') || 'post',
			tax: more.getAttribute('data-tax') || '',
			term: more.getAttribute('data-term') || ''
		}).then(function (data) {
			var grid = qs(more.getAttribute('data-target') || '.nv-grid--posts');
			if (grid) { grid.insertAdjacentHTML('beforeend', data.html); }
			more.classList.remove('is-loading');
			if (data.more) {
				more.setAttribute('data-page', page + 1);
			} else {
				more.remove();
			}
		}).catch(function (err) { more.classList.remove('is-loading'); toast(err.message, true); });
	});

	/* ------------------------------------------------------------ boot */

	function boot() {
		initHeader();
		initToTop();
		initQty();
		on(doc, 'keydown', function (e) { if (e.key === 'Escape') { closeModal(); } });
	}

	if (doc.readyState === 'loading') {
		doc.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
