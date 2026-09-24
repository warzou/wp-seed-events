(function (window, document) {
	'use strict';

	if (window.wpSeedEventsShareActions) {
		return;
	}

	var resetTimers = new WeakMap();

	function fallbackCopy(text) {
		var input = document.createElement('textarea');
		var copied = false;

		input.value = text;
		input.setAttribute('readonly', '');
		input.style.position = 'fixed';
		input.style.insetInlineStart = '-9999px';
		document.body.appendChild(input);
		input.select();

		try {
			copied = document.execCommand('copy');
		} catch (error) {
			copied = false;
		}

		document.body.removeChild(input);
		return copied;
	}

	function copyText(text) {
		if (window.navigator.clipboard && window.isSecureContext) {
			return window.navigator.clipboard.writeText(text).then(
				function () { return true; },
				function () { return fallbackCopy(text); }
			);
		}

		return Promise.resolve(fallbackCopy(text));
	}

	function report(button, label, message) {
		var root = button.closest('[data-wp-seed-event-share]');
		var feedback = root ? root.querySelector('[data-wp-seed-event-share-feedback]') : null;
		var labelNode = button.querySelector('[data-wp-seed-event-share-action-label]');
		var originalLabel = button.getAttribute('data-original-label') || (labelNode ? labelNode.textContent : button.getAttribute('aria-label'));
		var previousTimer = resetTimers.get(button);

		if (previousTimer) {
			window.clearTimeout(previousTimer);
		}

		button.setAttribute('data-original-label', originalLabel);
		if (labelNode) {
			labelNode.textContent = label;
		}
		if (feedback) {
			feedback.textContent = message;
		}

		resetTimers.set(button, window.setTimeout(function () {
			if (labelNode) {
				labelNode.textContent = originalLabel;
			}
			resetTimers.delete(button);
		}, 1800));
	}

	function reportCopy(button, copied) {
		report(
			button,
			copied ? 'Copié' : 'Copie impossible',
			copied ? 'Le lien de l’événement a été copié.' : 'Le lien de l’événement n’a pas pu être copié.'
		);
	}

	function copyAction(button) {
		var url = button.getAttribute('data-share-url') || '';

		if (!url) {
			reportCopy(button, false);
			return Promise.resolve(false);
		}

		return copyText(url).then(function (copied) {
			reportCopy(button, copied);
			return copied;
		});
	}

	function shareAction(button) {
		var title = button.getAttribute('data-share-title') || '';
		var text = button.getAttribute('data-share-text') || '';
		var url = button.getAttribute('data-share-url') || '';

		if (!url) {
			reportCopy(button, false);
			return Promise.resolve(false);
		}

		if (typeof window.navigator.share !== 'function') {
			return copyAction(button);
		}

		return window.navigator.share({ title: title, text: text, url: url }).then(
			function () {
				report(button, 'Partagé', 'L’événement a été partagé.');
				return true;
			},
			function (error) {
				if (error && error.name === 'AbortError') {
					return false;
				}
				return copyAction(button);
			}
		);
	}

	function handleClick(event) {
		var shareButton = event.target.closest('[data-wp-seed-event-share-native]');
		var copyButton = event.target.closest('[data-wp-seed-event-share-copy]');

		if (shareButton) {
			event.preventDefault();
			shareAction(shareButton);
			return;
		}
		if (copyButton) {
			event.preventDefault();
			copyAction(copyButton);
		}
	}

	document.addEventListener('click', handleClick);
	window.wpSeedEventsShareActions = {
		copyAction: copyAction,
		shareAction: shareAction,
	};
}(window, document));
