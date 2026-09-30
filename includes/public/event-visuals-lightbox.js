/* Core owns the lightbox. Contain keyboard focus among its visible controls. */
(function (document) {
	'use strict';
	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Tab' && event.key !== 'Escape') return;
		var overlay = document.querySelector('.wp-lightbox-overlay.active');
		if (!overlay || !overlay.querySelector('.wp-seed-event-visuals__figure')) return;
		var controls = Array.prototype.filter.call(overlay.querySelectorAll('button'), function (button) {
			return !button.disabled && !button.hidden && button.getClientRects().length > 0;
		});
		if (!controls.length) return;
		var current = document.activeElement;
		// Core handles Escape when focus is inside; cover focus moved externally.
		if (event.key === 'Escape') {
			if (!overlay.contains(current)) {
				event.preventDefault();
				overlay.querySelector('.wp-lightbox-close-button').click();
			}
			return;
		}
		var first = controls[0], last = controls[controls.length - 1];
		if (!controls.includes(current) || (event.shiftKey ? current === first : current === last)) {
			event.preventDefault();
			event.stopPropagation();
			(event.shiftKey ? last : first).focus();
		}
	}, true);
}(document));
