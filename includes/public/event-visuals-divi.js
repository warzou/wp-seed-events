(function ($, window, document) {
	'use strict';

	function initializeModule(module) {
		if (typeof window.et_pb_image_lightbox_init !== 'function' || typeof $.fn.magnificPopup !== 'function') {
			return;
		}

		var links = $(module).find('a.et_pb_lightbox_image[data-wp-seed-divi-lightbox="1"]');

		if (links.length) {
			window.et_pb_image_lightbox_init(links);
		}
	}

	function initializeVisualsLightboxes(root) {
		$(root || document)
			.find('.wp_seed_events_divi_event_visuals')
			.each(function () {
				initializeModule(this);
			});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initializeVisualsLightboxes(document);
		}, { once: true });
	} else {
		initializeVisualsLightboxes(document);
	}

	window.addEventListener('load', function () {
		initializeVisualsLightboxes(document);
	}, { once: true });
}(window.jQuery, window, document));
