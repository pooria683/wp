/* Nova — admin.js : repeaters, colour pickers, media picker */
(function ($) {
	'use strict';

	$(function () {
		if ($.fn.wpColorPicker) {
			$('.nv-color').wpColorPicker();
		}

		// Media picker.
		$(document).on('click', '.nv-media__pick', function (e) {
			e.preventDefault();
			var wrap = $(this).closest('.nv-media');
			var frame = wp.media({ title: NOVA_ADMIN.i18n.selectImage, multiple: false, library: { type: ['image', 'video', 'audio', 'font'] } });
			frame.on('select', function () {
				var att = frame.state().get('selection').first().toJSON();
				wrap.find('.nv-media__url').val(att.url).trigger('change');
			});
			frame.open();
		});
		$(document).on('click', '.nv-media__clear', function (e) {
			e.preventDefault();
			$(this).closest('.nv-media').find('.nv-media__url').val('');
		});

		// Repeaters (fonts, footer columns, custom post types).
		$(document).on('click', '.nv-repeater__add', function (e) {
			e.preventDefault();
			var rep = $(this).closest('.nv-repeater');
			var tpl = rep.find('> .nv-repeater__tpl').html();
			var index = Date.now();
			rep.find('.nv-repeater__rows').append(tpl.replace(/__i__/g, index));
		});

		$(document).on('click', '.nv-rep-del', function (e) {
			e.preventDefault();
			if (window.confirm(NOVA_ADMIN.i18n.confirm)) {
				$(this).closest('.nv-rep-row').remove();
			}
		});

		// Keep mode cards in sync with the checkbox state.
		$(document).on('change', '.nv-mode input', function () {
			$(this).closest('.nv-mode').toggleClass('is-on', this.checked);
		});
	});
})(jQuery);
