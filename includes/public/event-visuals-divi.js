(function ($, window, document) {
	'use strict';
	var focusReturns = new WeakMap();

	function initializeModule(module) {
		if (typeof window.et_pb_image_lightbox_init !== 'function' || typeof $.fn.magnificPopup !== 'function') {
			return;
		}

		var links = $(module).find('a.et_pb_lightbox_image[data-wp-seed-divi-lightbox="1"]');

		if (links.length) {
			window.et_pb_image_lightbox_init(links);
			// The native adapter supplies its real opener and its completed-close event.
			links.off('mfpOpen.wpSeedEventsFocus mfpAfterClose.wpSeedEventsFocus')
				.on('mfpOpen.wpSeedEventsFocus', function () {
					var popup = $.magnificPopup.instance;
					var trigger = popup.currItem && popup.currItem.el && popup.currItem.el[0];
					focusReturns.set(module, window.wpSeedEventsLightboxFocus && trigger
						? window.wpSeedEventsLightboxFocus.capture(trigger, module) : null);
				})
				.on('mfpAfterClose.wpSeedEventsFocus', function () {
					// AfterClose runs after native teardown and its focus trap have finished.
					var restoreFocus = focusReturns.get(module);
					focusReturns.delete(module);
					if (restoreFocus) restoreFocus();
				});
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
