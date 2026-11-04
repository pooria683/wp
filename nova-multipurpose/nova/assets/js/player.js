/* Nova — player.js : custom player for local media + lazy embeds (YouTube/Aparat/Vimeo) */
(function () {
	'use strict';

	var ICON_PLAY = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z" fill="currentColor"/></svg>';
	var ICON_PAUSE = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 5h4v14H7zM15 5h4v14h-4z" fill="currentColor"/></svg>';

	function fmt(sec) {
		if (!isFinite(sec)) { return '0:00'; }
		var m = Math.floor(sec / 60);
		var s = Math.floor(sec % 60);
		return m + ':' + (s < 10 ? '0' + s : s);
	}

	function initPlayer(root) {
		var media = root.querySelector('video') || root.querySelector('audio');
		var type = root.getAttribute('data-type');
		var bar = root.querySelector('.nv-player__bar');
		var playBtn = root.querySelector('[data-act="play"]');
		var muteBtn = root.querySelector('[data-act="mute"]');
		var fullBtn = root.querySelector('[data-act="full"]');
		var seek = root.querySelector('.nv-player__seek');
		var vol = root.querySelector('.nv-player__vol');
		var time = root.querySelector('[data-time]');
		var speed = root.querySelector('[data-act="speed"]');
		var autoplay = root.getAttribute('data-autoplay') === '1';
		var muted = root.getAttribute('data-muted') === '1';
		var loop = root.getAttribute('data-loop') === '1';

		/* External providers: lazy facade, iframe injected on click. */
		if (!media) {
			var facade = root.querySelector('.nv-player__facade');
			if (!facade) { return; }
			var start = function () {
				var url = facade.getAttribute('data-embed');
				if (autoplay) { url += (url.indexOf('?') > -1 ? '&' : '?') + 'autoplay=1'; }
				var iframe = document.createElement('iframe');
				iframe.src = url;
				iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen';
				iframe.allowFullscreen = true;
				iframe.loading = 'lazy';
				iframe.title = root.querySelector('.nv-player__title') ? root.querySelector('.nv-player__title').textContent : 'video';
				var stage = root.querySelector('.nv-player__stage');
				stage.innerHTML = '';
				stage.appendChild(iframe);
				if (bar) { bar.hidden = true; }
			};
			facade.addEventListener('click', start);
			return;
		}

		media.muted = muted;
		media.loop = loop;
		media.playsInline = true;
		media.preload = 'metadata';

		var setPlayIcon = function (playing) {
			if (playBtn) { playBtn.innerHTML = playing ? ICON_PAUSE : ICON_PLAY; }
			root.classList.toggle('is-playing', playing);
		};

		var big = root.querySelector('.nv-player__big');

		var play = function () {
			var p = media.play();
			if (p && p.catch) { p.catch(function () {}); }
		};

		if (big) { big.addEventListener('click', play); }
		if (playBtn) {
			playBtn.addEventListener('click', function () {
				media.paused ? play() : media.pause();
			});
		}
		if (root.querySelector('.nv-player__stage')) {
			root.querySelector('.nv-player__stage').addEventListener('click', function (e) {
				if (e.target === media) { media.paused ? play() : media.pause(); }
			});
		}

		media.addEventListener('play', function () { setPlayIcon(true); });
		media.addEventListener('pause', function () { setPlayIcon(false); });
		media.addEventListener('ended', function () { setPlayIcon(false); });

		media.addEventListener('timeupdate', function () {
			if (seek && media.duration) {
				seek.value = (media.currentTime / media.duration) * 100;
			}
			if (time) { time.textContent = fmt(media.currentTime); }
		});
		media.addEventListener('loadedmetadata', function () {
			if (time) { time.textContent = fmt(media.currentTime); }
		});

		if (seek) {
			seek.addEventListener('input', function () {
				if (media.duration) { media.currentTime = (seek.value / 100) * media.duration; }
			});
		}
		if (vol) {
			vol.addEventListener('input', function () {
				media.volume = parseFloat(vol.value);
				media.muted = media.volume === 0;
			});
		}
		if (muteBtn) {
			muteBtn.addEventListener('click', function () {
				media.muted = !media.muted;
				muteBtn.classList.toggle('is-muted', media.muted);
			});
		}
		if (speed) {
			speed.addEventListener('change', function () { media.playbackRate = parseFloat(speed.value); });
		}
		if (fullBtn) {
			fullBtn.addEventListener('click', function () {
				if (document.fullscreenElement) {
					document.exitFullscreen();
				} else if (root.requestFullscreen) {
					root.requestFullscreen();
				}
			});
		}

		// Keyboard: space, arrows, m, f.
		root.addEventListener('keydown', function (e) {
			if (e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') { return; }
			switch (e.key) {
				case ' ': e.preventDefault(); media.paused ? play() : media.pause(); break;
				case 'ArrowRight': media.currentTime += 5; break;
				case 'ArrowLeft': media.currentTime -= 5; break;
				case 'm': media.muted = !media.muted; break;
				case 'f': fullBtn && fullBtn.click(); break;
			}
		});

		if (autoplay) { play(); }
		setPlayIcon(!media.paused);
	}

	function boot() {
		document.querySelectorAll('[data-nvp]').forEach(initPlayer);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
