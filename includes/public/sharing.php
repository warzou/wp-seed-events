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

/** Render the legacy menu by default; inline is an additive presentation. */
function wp_seed_events_render_event_share_menu( $event, $layout = 'menu' ) {
	$share = wp_seed_events_event_share_data( $event );

	if ( array() === $share ) {
		return '';
	}

	$GLOBALS['wp_seed_events_share_script_required'] = true;

	if ( 'inline' === $layout ) {
		return '<div class="wp-seed-event-share wp-seed-event-share--inline" data-wp-seed-event-share>'
			. '<p class="wp-seed-event-share__label">' . esc_html__( 'Partager', 'wp-seed-events' ) . '</p>'
			. '<div class="wp-seed-event-share__actions">'
			. '<button type="button" data-wp-seed-event-share-copy data-share-url="' . esc_url( $share['url'] ) . '">'
			. '<span aria-hidden="true">&#x2398;</span> <span data-wp-seed-event-share-label>' . esc_html__( 'Copier le lien', 'wp-seed-events' ) . '</span></button>'
			. '<a href="' . esc_url( $share['email_url'] ) . '"><span aria-hidden="true">&#x2709;</span> ' . esc_html__( 'E-mail', 'wp-seed-events' ) . '</a>'
			. '</div><p class="wp-seed-event-share__feedback" role="status" aria-live="polite" aria-atomic="true" data-wp-seed-event-share-feedback></p></div>';
	}

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

/** Available in the core Shortcode block, without a builder dependency. */
function wp_seed_events_event_share_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'id' => 0, 'layout' => 'inline' ), $atts, 'wp_seed_event_share' );
	$event_id = wp_seed_events_public_shortcode_event_id( $atts['id'] );
	$html = wp_seed_events_render_event_share_menu( wp_seed_events_public_event_data( $event_id ), $atts['layout'] );
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
	<script>
	(function () {
		'use strict';

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
