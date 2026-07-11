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

function wp_seed_events_render_event_share_menu( $event ) {
	$share = wp_seed_events_event_share_data( $event );

	if ( array() === $share ) {
		return '';
	}

	$GLOBALS['wp_seed_events_share_script_required'] = true;

	ob_start();
	?>
	<div class="wp-seed-event-share" data-wp-seed-event-share>
		<details class="wp-seed-event-share__menu">
			<summary><span aria-hidden="true">&#x1F517;</span> <?php echo esc_html__( 'Partager', 'wp-seed-events' ); ?></summary>
			<div class="wp-seed-event-share__actions">
				<p>
					<button type="button" data-wp-seed-event-share-copy data-share-url="<?php echo esc_url( $share['url'] ); ?>"><?php echo esc_html__( 'Copier le lien', 'wp-seed-events' ); ?></button>
				</p>
				<p>
					<a href="<?php echo esc_url( $share['email_url'] ); ?>"><?php echo esc_html__( 'Envoyer par email', 'wp-seed-events' ); ?></a>
				</p>
				<p class="screen-reader-text" aria-live="polite" data-wp-seed-event-share-feedback></p>
			</div>
		</details>
	</div>
	<?php

	return trim( ob_get_clean() );
}

function wp_seed_events_render_public_share_script() {
	if ( empty( $GLOBALS['wp_seed_events_share_script_required'] ) ) {
		return;
	}
	?>
	<script>
	(function () {
		'use strict';

		function fallbackCopy(text) {
			var input = document.createElement('textarea');
			var copied = false;

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
			return copied;
		}

		function report(button, success) {
			var root = button.closest('[data-wp-seed-event-share]');
			var feedback = root ? root.querySelector('[data-wp-seed-event-share-feedback]') : null;
			var originalLabel = button.getAttribute('data-original-label') || button.textContent;

			button.setAttribute('data-original-label', originalLabel);
			button.textContent = success ? 'Lien copié' : 'Copie impossible';

			if (feedback) {
				feedback.textContent = success ? 'Le lien de l’événement a été copié.' : 'Le lien n’a pas pu être copié.';
			}

			window.setTimeout(function () {
				button.textContent = originalLabel;
			}, 2000);
		}

		document.addEventListener('click', function (event) {
			var button = event.target.closest('[data-wp-seed-event-share-copy]');

			if (!button) {
				return;
			}

			var url = button.getAttribute('data-share-url') || '';

			if (!url) {
				report(button, false);
				return;
			}

			if (navigator.clipboard && window.isSecureContext) {
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
