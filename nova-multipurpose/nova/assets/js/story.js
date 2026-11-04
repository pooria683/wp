/* Nova — story.js : Instagram-like viewer, no library */
(function () {
	'use strict';

	function init(root) {
		var dataEl = root.querySelector('.nv-story__data');
		var viewer = root.querySelector('.nv-story__viewer');
		var stage = root.querySelector('.nv-story__stage');
		var caption = root.querySelector('.nv-story__caption');
		var bars = root.querySelector('.nv-story__bars');
		if (!dataEl || !viewer) { return; }

		var items = [];
		try { items = JSON.parse(dataEl.textContent); } catch (e) { return; }
		if (!items.length) { return; }

		var index = 0;
		var timer = null;
		var currentVideo = null;

		function clearTimer() { if (timer) { clearTimeout(timer); timer = null; } if (currentVideo) { currentVideo.pause(); currentVideo = null; } }

		function renderBars() {
			bars.innerHTML = '';
			items.forEach(function (_, i) {
				var bar = document.createElement('div');
				bar.className = 'nv-story__bar' + (i < index ? ' is-done' : '');
				bar.innerHTML = '<span></span>';
				bars.appendChild(bar);
			});
		}

		function playBar(duration) {
			var bar = bars.children[index];
			if (!bar) { return; }
			var fill = bar.querySelector('span');
			fill.style.transition = 'none';
			fill.style.width = '0%';
			requestAnimationFrame(function () {
				fill.style.transition = 'width ' + duration + 'ms linear';
				fill.style.width = '100%';
			});
		}

		function show(i) {
			clearTimer();
			index = (i + items.length) % items.length;
			var item = items[index];
			stage.innerHTML = '';

			if (item.video) {
				var video = document.createElement('video');
				video.src = item.video;
				video.playsInline = true;
				video.autoplay = true;
				video.muted = false;
				stage.appendChild(video);
				currentVideo = video;
				video.play().catch(function () {});
				video.addEventListener('ended', function () { next(); });
				renderBars();
				playBar(15000);
			} else if (item.image) {
				var img = document.createElement('img');
				img.src = item.image;
				img.alt = item.title || '';
				stage.appendChild(img);
				renderBars();
				playBar(5000);
				timer = setTimeout(next, 5000);
			}

			caption.innerHTML = '';
			if (item.caption) { caption.textContent = item.caption; }
			if (item.link) {
				var a = document.createElement('a');
				a.href = item.link;
				a.textContent = ' مشاهده';
				a.style.color = '#fff';
				a.style.display = 'inline-block';
				a.style.marginInlineStart = '6px';
				caption.appendChild(a);
			}
		}

		function next() { show(index + 1); }
		function prev() { show(index - 1); }

		root.querySelectorAll('.nv-story__thumb').forEach(function (thumb, i) {
			thumb.addEventListener('click', function () {
				viewer.hidden = false;
				document.body.style.overflow = 'hidden';
				show(i);
			});
		});

		viewer.querySelectorAll('.nv-story__nav').forEach(function (btn) {
			btn.addEventListener('click', function () {
				parseInt(btn.getAttribute('data-dir'), 10) === 1 ? next() : prev();
			});
		});

		function close() {
			clearTimer();
			viewer.hidden = true;
			document.body.style.overflow = '';
		}
		viewer.querySelector('.nv-story__close').addEventListener('click', close);

		document.addEventListener('keydown', function (e) {
			if (viewer.hidden) { return; }
			if (e.key === 'Escape') { close(); }
			if (e.key === 'ArrowRight') { next(); }
			if (e.key === 'ArrowLeft') { prev(); }
		});
	}

	function boot() {
		document.querySelectorAll('[data-nv-story]').forEach(init);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
