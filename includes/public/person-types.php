<?php
/**
 * Canonical person type registry shared by admin and builder integrations.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_default_person_type_options() {
	return array(
		'organizer' => __( 'Organisateur', 'wp-seed-events' ),
		'speaker'   => __( 'Intervenant', 'wp-seed-events' ),
		'contact'   => __( 'Contact', 'wp-seed-events' ),
	);
}

function wp_seed_events_person_type_options() {
	$stored = get_option( 'wp_seed_events_person_types', null );
	$types  = null === $stored ? wp_seed_events_default_person_type_options() : $stored;

	if ( ! is_array( $types ) ) {
		return array();
	}

	$clean = array();
	foreach ( $types as $key => $label ) {
		$key   = sanitize_key( $key );
		$label = sanitize_text_field( $label );
		if ( '' !== $key && '' !== $label ) {
			$clean[ $key ] = $label;
		}
	}

	return $clean;
}

function wp_seed_events_person_type_key_from_label( $label, $types = null ) {
	$label = sanitize_text_field( $label );
	$types = is_array( $types ) ? $types : wp_seed_events_person_type_options();
	$base  = sanitize_key( sanitize_title( $label ) );

	if ( '' === $base ) {
		return '';
	}

	$key   = $base;
	$index = 2;
	while ( isset( $types[ $key ] ) ) {
		$key = $base . '-' . $index;
		$index++;
	}

	return $key;
}

function wp_seed_events_save_person_type_options( $types ) {
	$clean = array();
	foreach ( is_array( $types ) ? $types : array() as $key => $label ) {
		$key   = sanitize_key( $key );
		$label = sanitize_text_field( $label );
		if ( '' !== $key && '' !== $label ) {
			$clean[ $key ] = $label;
		}
	}

	return update_option( 'wp_seed_events_person_types', $clean, false );
}

function wp_seed_events_add_person_type( $label ) {
	$types = wp_seed_events_person_type_options();
	$key   = wp_seed_events_person_type_key_from_label( $label, $types );
	if ( '' === $key ) {
		return new WP_Error( 'person_type_invalid', __( 'Le nom du type de personne est obligatoire.', 'wp-seed-events' ) );
	}
	$types[ $key ] = sanitize_text_field( $label );
	wp_seed_events_save_person_type_options( $types );
	return $key;
}

function wp_seed_events_rename_person_type( $key, $label ) {
	$key   = sanitize_key( $key );
	$label = sanitize_text_field( $label );
	$types = wp_seed_events_person_type_options();
	if ( '' === $key || '' === $label || ! isset( $types[ $key ] ) ) {
		return new WP_Error( 'person_type_invalid', __( 'Type de personne invalide.', 'wp-seed-events' ) );
	}
	$types[ $key ] = $label;
	wp_seed_events_save_person_type_options( $types );
	return true;
}

function wp_seed_events_delete_person_type( $key ) {
	$key      = sanitize_key( $key );
	$types    = wp_seed_events_person_type_options();
	$defaults = wp_seed_events_default_person_type_options();
	if ( isset( $defaults[ $key ] ) ) {
		return new WP_Error( 'person_type_protected', __( 'Ce type de personne est requis par le modèle canonique.', 'wp-seed-events' ) );
	}
	if ( ! isset( $types[ $key ] ) ) {
		return false;
	}

	$history         = get_option( 'wp_seed_events_retired_person_types', array() );
	$history         = is_array( $history ) ? $history : array();
	$history[ $key ] = $types[ $key ];
	unset( $types[ $key ] );
	update_option( 'wp_seed_events_retired_person_types', $history, false );
	wp_seed_events_save_person_type_options( $types );
	return true;
}

function wp_seed_events_person_type_label( $key ) {
	$key   = sanitize_key( $key );
	$types = wp_seed_events_person_type_options();
	if ( isset( $types[ $key ] ) ) {
		return $types[ $key ];
	}
	$history = get_option( 'wp_seed_events_retired_person_types', array() );
	return is_array( $history ) && isset( $history[ $key ] )
		? sanitize_text_field( $history[ $key ] )
		: $key;
}

function wp_seed_events_register_person_type_rest_route() {
	register_rest_route(
		'wp-seed-events/v1',
		'/person-types',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'callback'            => static function () {
				$items = array();
				foreach ( wp_seed_events_person_type_options() as $key => $label ) {
					$items[] = array( 'key' => $key, 'label' => $label );
				}
				return rest_ensure_response( array( 'types' => $items ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'wp_seed_events_register_person_type_rest_route' );
