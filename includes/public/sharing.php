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

function wp_seed_events_share_text_option( $value, $default ) {
	$value = is_scalar( $value ) ? trim( wp_strip_all_tags( (string) $value, true ) ) : '';

	return '' !== $value ? $value : $default;
}

function wp_seed_events_share_display_mode( $value ) {
	$value = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( is_scalar( $value ) ? (string) $value : '' ) );

	return in_array( $value, array( 'text_icon', 'text', 'icon' ), true ) ? $value : 'text_icon';
}

function wp_seed_events_share_action_order_key( $value ) {
	$value = preg_replace( '/[^a-z0-9_\-]/', '', strtolower( is_scalar( $value ) ? (string) $value : '' ) );
	$valid = array(
		'share_copy_email',
		'share_email_copy',
		'copy_share_email',
		'copy_email_share',
		'email_share_copy',
		'email_copy_share',
	);

	return in_array( $value, $valid, true ) ? $value : 'share_copy_email';
}

function wp_seed_events_share_action_order( $value ) {
	return explode( '_', wp_seed_events_share_action_order_key( $value ) );
}

/** Allow presentation attributes, never executable attributes or replacement URLs. */
function wp_seed_events_share_html_attributes( $attributes ) {
	$html = '';
	foreach ( array( 'class', 'aria-label', 'data-icon', 'data-icon-placement' ) as $name ) {
		if ( isset( $attributes[ $name ] ) && is_scalar( $attributes[ $name ] ) && '' !== (string) $attributes[ $name ] ) {
			$html .= ' ' . $name . '="' . esc_attr( (string) $attributes[ $name ] ) . '"';
		}
	}
	return $html;
}

function wp_seed_events_share_action_label( $label, $attributes, $decorative = '' ) {
	if ( empty( $attributes ) ) {
		return $decorative . '<span data-wp-seed-event-share-label>' . esc_attr( $label ) . '</span>';
	}
	$label_class = ! empty( $attributes['hide_label'] ) ? ' screen-reader-text' : '';
	return '<span class="wp-seed-event-share__label' . $label_class . '" data-wp-seed-event-share-label>' . esc_attr( $label ) . '</span>';
}

/** The primary action supports native sharing and its associated fallback. */
function wp_seed_events_render_native_share_button( $share, $panel_id = '', $options = array() ) {
	return '<button type="button" hidden data-wp-seed-event-share-native aria-expanded="false" aria-controls="' . esc_attr( $panel_id ) . '" data-share-title="' . esc_attr( $share['title'] )
		. '" data-share-url="' . esc_url( $share['url'] ) . '"' . wp_seed_events_share_html_attributes( $options['action_attributes']['share'] ?? array() ) . '>'
		. ( empty( $options['action_attributes']['share'] ) ? esc_attr( $options['label'] ?? 'Partager' ) : wp_seed_events_share_action_label( $options['label'] ?? 'Partager', $options['action_attributes']['share'] ) ) . '</button>';
}

/** PRIMARY_SHARE_ONLY by default. secondary_actions accepts none, copy, email or both. */
function wp_seed_events_render_event_share_menu( $event, $layout = 'inline', $options = array() ) {
	// The historical two-argument API passed presentation options in $layout.
	$options = is_array( $options ) ? $options : array();
	if ( is_array( $layout ) ) {
		$options = array_merge( $layout, $options );
	}
	foreach ( array( 'label' => 'Partager', 'copy_label' => 'Copier le lien', 'email_label' => 'E-mail' ) as $key => $fallback ) {
		$options[ $key ] = wp_seed_events_share_text_option( $options[ $key ] ?? '', html_entity_decode( esc_html__( $fallback, 'wp-seed-events' ), ENT_QUOTES, 'UTF-8' ) );
	}
	$share = wp_seed_events_event_share_data( $event );

	if ( array() === $share ) {
		return '';
	}

	$GLOBALS['wp_seed_events_share_script_required'] = true;

	static $instance = 0;
	$panel_id = 'wp-seed-event-share-panel-' . ++$instance;
	$secondary = $options['secondary_actions'] ?? 'none';
	$attrs = is_array( $options['action_attributes'] ?? null ) ? $options['action_attributes'] : array();
	foreach ( array( 'share', 'copy', 'email' ) as $action ) {
		$attrs[ $action ] = is_array( $attrs[ $action ] ?? null ) ? $attrs[ $action ] : array();
	}
	$options['action_attributes'] = $attrs;
	$copy = '<button type="button" disabled data-wp-seed-event-share-copy data-share-url="' . esc_url( $share['url'] ) . '"' . wp_seed_events_share_html_attributes( $attrs['copy'] ) . '>'
		. wp_seed_events_share_action_label( $options['copy_label'] ?? 'Copier le lien', $attrs['copy'], '<span aria-hidden="true">&#x2398;</span> ' ) . '</button>';
	$email = '<a href="' . esc_url( $share['email_url'] ) . '"' . wp_seed_events_share_html_attributes( $attrs['email'] ) . '>'
		. wp_seed_events_share_action_label( $options['email_label'] ?? 'E-mail', $attrs['email'], '<span aria-hidden="true">&#x2709;</span> ' ) . '</a>';
	$enabled = static function ( $key ) use ( $options ) {
		return ! isset( $options[ $key ] ) || ! in_array( $options[ $key ], array( false, 0, '0', 'off', 'no' ), true );
	};
	$show_share = $enabled( 'show_share' );
	$copy = $enabled( 'show_copy' ) ? $copy : '';
	$email = $enabled( 'show_email' ) ? $email : '';
	$actions = array(
		'share' => $show_share ? wp_seed_events_render_native_share_button( $share, $panel_id, $options ) : '',
		'copy' => ( ! $show_share || in_array( $secondary, array( 'copy', 'both' ), true ) ) ? $copy : '',
		'email' => ( ! $show_share || in_array( $secondary, array( 'email', 'both' ), true ) ) ? $email : '',
	);
	$primary = '';
	foreach ( wp_seed_events_share_action_order( $options['action_order'] ?? 'share_copy_email' ) as $action ) {
		$primary .= $actions[ $action ];
	}
	if ( '' === $primary ) { return ''; }
	// Both historical layout values use the same builder-independent component.
	return '<div class="wp-seed-event-share wp-seed-event-share--inline' . ( empty( $options['root_class'] ) || ! is_scalar( $options['root_class'] ) ? '' : ' ' . esc_attr( $options['root_class'] ) ) . '" data-wp-seed-event-share>'
		. '<div class="wp-seed-event-share__actions">' . $primary
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
	if ( empty( $GLOBALS['wp_seed_events_share_script_required'] ) ) { return; }
	// Older bootstraps already enqueue these same asset paths. Never print a second engine.
	if ( ! function_exists( 'wp_style_is' ) || ! wp_style_is( 'wp-seed-events-public-share', 'enqueued' ) ) {
		echo '<style>';
		readfile( __DIR__ . '/event-share.css' );
		echo '</style>';
	}
	if ( ! function_exists( 'wp_script_is' ) || ! wp_script_is( 'wp-seed-events-public-share', 'enqueued' ) ) {
		echo '<script>';
		readfile( __DIR__ . '/event-share.js' );
		echo '</script>';
	}
}

// Safe with both the current bootstrap and historical asset-enqueuing bootstraps.
if ( function_exists( 'add_action' ) ) {
	add_action( 'wp_footer', 'wp_seed_events_render_public_share_script', 20 );
}
