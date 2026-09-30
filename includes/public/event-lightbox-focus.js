/* Focus restoration shared by native lightbox adapters; no dialog implementation. */
(function (window) {
	'use strict';
	if (window.wpSeedEventsLightboxFocus) return;

	var selector = 'a[href], button, input:not([type="hidden"]), select, textarea, [tabindex], [contenteditable="true"]';

	function usable(element, document) {
		return !!(element && document.documentElement.contains(element) &&
			typeof element.focus === 'function' && element.matches(selector) &&
			!element.matches(':disabled') && !element.closest('[hidden], [inert], [aria-hidden="true"]') &&
			element.getClientRects().length && window.getComputedStyle(element).visibility !== 'hidden');
	}

	function focus(element, document) {
		if (!usable(element, document)) return false;
		if (document.activeElement === element) return true;
		try {
			element.focus({ preventScroll: true });
		} catch (error) {
			try { element.focus(); } catch (ignored) { return false; }
		}
		return document.activeElement === element;
	}

	function capture(trigger, component) {
		var document = trigger && trigger.ownerDocument;
		var href = trigger && trigger.getAttribute('href');
		// Keep the exact node, not whichever element happens to have focus at close.
		return function restore() {
			if (!document) return false;
			if (focus(trigger, document)) return true;
			if (!component || !document.documentElement.contains(component)) return false;
			var candidates = Array.prototype.slice.call(component.querySelectorAll(selector));
			// A renderer may replace a link while the dialog is open. Prefer its equivalent.
			var equivalent = candidates.filter(function (element) {
				return href && element.getAttribute('href') === href;
			});
			return equivalent.concat(candidates).some(function (element) { return focus(element, document); });
		};
	}

	window.wpSeedEventsLightboxFocus = { capture: capture };
}(window));
