/* Nova — slider.js : dependency-free carousel (transform based, swipe, a11y) */
(function () {
	'use strict';

	function init(root) {
		var track = root.querySelector('.nv-slider__track');
		var slides = Array.prototype.slice.call(root.querySelectorAll('.nv-slider__slide'));
		if (!track || slides.length < 1) { return; }

		var dots = Array.prototype.slice.call(root.querySelectorAll('.nv-slider__dot'));
		var autoplay = parseInt(root.getAttribute('data-autoplay'), 10) || 0;
		var index = 0;
		var timer = null;
		var startX = 0;

		function go(i) {
			index = (i + slides.length) % slides.length;
			track.style.transform = 'translate3d(' + (-index * 100) + '%,0,0)';
			slides.forEach(function (s, n) {
				s.classList.toggle('is-active', n === index);
				s.setAttribute('aria-hidden', n === index ? 'false' : 'true');
			});
			dots.forEach(function (d, n) { d.classList.toggle('is-active', n === index); });
		}

		function next() { go(index + 1); }
		function prev() { go(index - 1); }

		function play() {
			if (!autoplay || slides.length < 2) { return; }
			stop();
			timer = setInterval(next, autoplay);
		}
		function stop() { if (timer) { clearInterval(timer); timer = null; } }

		root.querySelectorAll('[data-dir]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				parseInt(btn.getAttribute('data-dir'), 10) === 1 ? next() : prev();
				play();
			});
		});
		dots.forEach(function (dot) {
			dot.addEventListener('click', function () {
				go(parseInt(dot.getAttribute('data-go'), 10) || 0);
				play();
			});
		});

		root.addEventListener('mouseenter', stop);
		root.addEventListener('mouseleave', play);
		root.addEventListener('focusin', stop);

		root.setAttribute('tabindex', '0');
		root.addEventListener('keydown', function (e) {
			if (e.key === 'ArrowRight') { next(); }
			if (e.key === 'ArrowLeft') { prev(); }
		});

		root.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; stop(); }, { passive: true });
		root.addEventListener('touchend', function (e) {
			var delta = e.changedTouches[0].clientX - startX;
			if (Math.abs(delta) > 40) { delta < 0 ? next() : prev(); }
			play();
		}, { passive: true });

		go(0);
		play();
	}

	function boot() {
		document.querySelectorAll('[data-nv-slider]').forEach(init);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
