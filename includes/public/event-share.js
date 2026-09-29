(function () {
	'use strict';
	if (window.wpSeedEventsPublicShareInitialized) { return; }
	window.wpSeedEventsPublicShareInitialized = true;

	function nativeSupported() {
		return window.isSecureContext && typeof navigator.share === 'function';
	}

	function enhance() {
		if (typeof document === 'undefined' || !document) { return; }
		document.querySelectorAll('[data-wp-seed-event-share-native]').forEach(function (button) {
			button.hidden = false;
		});
		document.querySelectorAll('[data-wp-seed-event-share-copy]').forEach(function (button) {
			button.disabled = false;
		});
	}
	enhance();
	if (typeof MutationObserver === 'function') {
		new MutationObserver(enhance).observe(document.documentElement, {childList: true, subtree: true});
	}

	function shareFeedback(button, message) {
		var root = button.closest('[data-wp-seed-event-share]');
		var feedback = root ? root.querySelector('[data-wp-seed-event-share-feedback]') : null;
		if (feedback) { feedback.textContent = message; }
	}

	function togglePanel(button, open, restoreFocus) {
		var root = button.closest('[data-wp-seed-event-share]');
		var panel = root ? root.querySelector('[data-wp-seed-event-share-panel]') : null;
		if (!panel) { return; }
		panel.hidden = !open;
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
		if (open) {
			var first = panel.querySelector('button, a');
			if (first) { first.focus(); }
		} else if (restoreFocus) { button.focus(); }
	}

	function closePanels(event, escape) {
		document.querySelectorAll('[data-wp-seed-event-share-native][aria-expanded="true"]').forEach(function (button) {
			var root = button.closest('[data-wp-seed-event-share]');
			if (escape || !root.contains(event.target)) {
				togglePanel(button, false, escape);
				if (escape) { event.preventDefault(); }
			}
		});
	}
	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape') { closePanels(event, true); }
	});

	function nativeShare(button) {
		var url = button.getAttribute('data-share-url') || '';
		var title = button.getAttribute('data-share-title') || '';
		if (button.disabled) { return; }
		shareFeedback(button, '');
		if (button.getAttribute('aria-expanded') === 'true') {
			togglePanel(button, false, true);
			return;
		}
		function failed(error) {
			if (!error || error.name !== 'AbortError') {
				togglePanel(button, true, false);
			}
		}
		if (!nativeSupported() || !url) {
			failed();
			return;
		}
		button.disabled = true;
		try {
			// Call during the click, before awaiting anything: Web Share needs user activation.
			Promise.resolve(navigator.share({ title: title, url: url })).then(function () {
				// Resolution does not certify delivery to another application.
			}, failed).then(function () { button.disabled = false; });
		} catch (error) {
			failed(error);
			button.disabled = false;
		}
	}

	function fallbackCopy(text) {
		var input = document.createElement('textarea');
		var copied = false;
		var previousFocus = document.activeElement;

		input.value = text;
		input.setAttribute('readonly', '');
		input.style.position = 'fixed';
		input.style.opacity = '0';
		document.body.appendChild(input);
		input.select();

		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}

		document.body.removeChild(input);
		if (previousFocus && typeof previousFocus.focus === 'function') {
			previousFocus.focus();
		}
		return copied;
	}

	function report(button, success) {
		var root = button.closest('[data-wp-seed-event-share]');
		var feedback = root ? root.querySelector('[data-wp-seed-event-share-feedback]') : null;
		var label = button.querySelector('[data-wp-seed-event-share-label]') || button;
		var originalLabel = button.getAttribute('data-original-label') || label.textContent;

		button.setAttribute('data-original-label', originalLabel);
		label.textContent = success ? 'Lien copié' : 'Copie impossible';

		if (feedback) {
			feedback.textContent = success ? 'Le lien de l’événement a été copié.' : 'Le lien n’a pas pu être copié.';
		}

		window.setTimeout(function () {
			label.textContent = originalLabel;
		}, 2000);
	}

	document.addEventListener('click', function (event) {
		closePanels(event, false);
		var nativeButton = event.target && typeof event.target.closest === 'function'
			? event.target.closest('[data-wp-seed-event-share-native]') : null;
		if (nativeButton) {
			nativeShare(nativeButton);
			return;
		}
		var button = event.target && typeof event.target.closest === 'function'
			? event.target.closest('[data-wp-seed-event-share-copy]') : null;

		if (!button) {
			return;
		}

		var url = button.getAttribute('data-share-url') || '';

		if (!url) {
			report(button, false);
			return;
		}

		if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function' && window.isSecureContext) {
			navigator.clipboard.writeText(url).then(
				function () {
					report(button, true);
				},
				function () {
					report(button, fallbackCopy(url));
				}
			);
			return;
		}

		report(button, fallbackCopy(url));
	});
}());
