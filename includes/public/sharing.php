<?php
/**
 * Public event sharing for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_event_share_data( $event ) {
	if ( ! is_array( $event ) ) {
		return array();
	}

	$title = trim( wp_strip_all_tags( (string) ( $event['title'] ?? '' ) ) );
	$url   = esc_url_raw( (string) ( $event['url'] ?? '' ) );

	if ( '' === $title || '' === $url ) {
		return array();
	}

	return array(
		'title'     => $title,
		'url'       => $url,
		'email_url' => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $title . "\r\n\r\n" . $url ),
	);
}

/** The primary action supports native sharing and its associated fallback. */
function wp_seed_events_render_native_share_button( $share, $panel_id = '' ) {
	return '<button type="button" hidden data-wp-seed-event-share-native aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '" data-share-title="' . esc_attr( $share['title'] )
		. '" data-share-url="' . esc_url( $share['url'] ) . '">' . esc_html__( 'Partager', 'wp-seed-events' ) . '</button>';
}

/** PRIMARY_SHARE_ONLY by default. secondary_actions accepts none, copy, email or both. */
function wp_seed_events_render_event_share_menu( $event, $layout = 'inline', $options = array() ) {
	$share = wp_seed_events_event_share_data( $event );

	if ( array() === $share ) {
		return '';
	}

	$GLOBALS['wp_seed_events_share_script_required'] = true;

	static $instance = 0;
	$panel_id = 'wp-seed-event-share-panel-' . ++$instance;
	$secondary = $options['secondary_actions'] ?? 'none';
	$copy = '<button type="button" disabled data-wp-seed-event-share-copy data-share-url="' . esc_url( $share['url'] ) . '">'
		. '<span aria-hidden="true">&#x2398;</span> <span data-wp-seed-event-share-label>' . esc_html__( 'Copier le lien', 'wp-seed-events' ) . '</span></button>';
	$email = '<a href="' . esc_url( $share['email_url'] ) . '"><span aria-hidden="true">&#x2709;</span> ' . esc_html__( 'E-mail', 'wp-seed-events' ) . '</a>';
	// Both historical layout values use the same compact, builder-independent component.
	return '<div class="wp-seed-event-share wp-seed-event-share--inline" data-wp-seed-event-share>'
		. '<div class="wp-seed-event-share__actions">'
		. wp_seed_events_render_native_share_button( $share, $panel_id )
		. ( in_array( $secondary, array( 'copy', 'both' ), true ) ? $copy : '' )
		. ( in_array( $secondary, array( 'email', 'both' ), true ) ? $email : '' )
		. '</div><div class="wp-seed-event-share__panel wp-seed-event-share__actions" id="' . esc_attr( $panel_id ) . '" hidden data-wp-seed-event-share-panel role="group" aria-label="' . esc_attr( esc_html__( 'Options de partage', 'wp-seed-events' ) ) . '">'
		. $copy . $email . '</div>'
		. '<noscript>' . $email . '</noscript>'
		. '<p class="wp-seed-event-share__feedback" role="status" aria-live="polite" aria-atomic="true" data-wp-seed-event-share-feedback></p></div>';
}

/** Available in the core Shortcode block, without a builder dependency. */
function wp_seed_events_event_share_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0, 'layout' => 'inline', 'secondary_actions' => 'none' ), $atts, 'wp_seed_event_share' );
	$event_id = wp_seed_events_public_shortcode_event_id( $atts['id'] );
	$html = wp_seed_events_render_event_share_menu( wp_seed_events_public_event_data( $event_id ), $atts['layout'], array( 'secondary_actions' => $atts['secondary_actions'] ) );
	if ( '' !== $html && isset( $GLOBALS['wp_seed_events_template_share_context'] )
		&& $event_id === $GLOBALS['wp_seed_events_template_share_context']['event_id'] ) {
		$GLOBALS['wp_seed_events_template_share_context']['rendered'] = true;
	}
	return $html;
}

function wp_seed_events_render_public_share_script() {
	if ( empty( $GLOBALS['wp_seed_events_share_script_required'] ) ) {
		return;
	}
	?>
	<style>
	.wp-seed-event-share [hidden] { display: none !important; }
	.wp-seed-event-share__actions { display: flex; flex-wrap: wrap; gap: .625rem; }
	.wp-seed-event-share__panel { flex-basis: 100%; width: fit-content; padding: .75rem; border: 1px solid currentColor; border-radius: .5rem; }
	.wp-seed-event-share--inline [data-wp-seed-event-share-native] { font-weight: 700; }
	.wp-seed-event-share--inline :is(button, a):focus-visible { outline: 3px solid currentColor; outline-offset: 3px; }
	</style>
	<script>
	(function () {
		'use strict';
		if (window.wpSeedEventsPublicShareInitialized) { return; }
		window.wpSeedEventsPublicShareInitialized = true;

		function nativeSupported() {
			return window.isSecureContext && typeof navigator.share === 'function';
		}

		document.querySelectorAll('[data-wp-seed-event-share-native]').forEach(function (button) {
			button.hidden = false;
		});
		document.querySelectorAll('[data-wp-seed-event-share-copy]').forEach(function (button) {
			button.disabled = false;
		});

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
	</script>
	<?php
}
