<?php
/**
 * Person contact normalization and public projections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

if ( function_exists( 'add_action' ) ) {
	add_action( 'rest_api_init', 'wp_seed_events_register_contact_rest_fields' );
}

/**
 * Normalize writable contact associations through the existing people model.
 *
 * @param mixed $raw_contacts      Submitted contact rows.
 * @param mixed $existing_contacts Existing stored rows.
 * @return array
 */
function wp_seed_events_normalize_contact_rest_value( $raw_contacts, $existing_contacts = array() ) {
	$raw_contacts      = is_array( $raw_contacts ) ? $raw_contacts : array();
	$existing_contacts = is_array( $existing_contacts ) ? $existing_contacts : array();
	$normalized        = array();

	foreach ( $raw_contacts as $index => $raw_contact ) {
		if ( ! is_array( $raw_contact ) ) {
			continue;
		}

		$name = sanitize_text_field( $raw_contact['name'] ?? '' );
		if ( '' === $name ) {
			continue;
		}

		$person_key = sanitize_key( $raw_contact['person_key'] ?? '' );
		$person_key = '' !== $person_key ? $person_key : wp_seed_events_person_key_from_name( $name );
		$existing   = is_array( $existing_contacts[ $index ] ?? null ) ? $existing_contacts[ $index ] : array();
		$submitted  = array_merge( $raw_contact, array( 'person_key' => $person_key, 'name' => $name ) );
		$publication = wp_seed_events_normalize_contact_publication_for_storage(
			$submitted,
			$existing,
			wp_seed_events_contacts_identify_same_association( $submitted, $existing )
		);

		$normalized[] = array(
			'person_key'    => $person_key,
			'role'          => 'contact',
			'roles'         => array( 'contact' ),
			'name'          => $name,
			'phone'         => $publication['phone'],
			'email'         => $publication['email'],
			'link'          => $publication['link'],
			'publish_email' => $publication['publish_email'],
			'publish_phone' => $publication['publish_phone'],
			'publish_link'  => $publication['publish_link'],
		);
	}

	return $normalized;
}

function wp_seed_events_contact_rest_get( $object, $field_name, $request ) {
	unset( $field_name );
	$event_id = absint( $object['id'] ?? 0 );
	$context  = is_object( $request ) && is_callable( array( $request, 'get_param' ) ) ? $request->get_param( 'context' ) : 'view';

	if ( 'edit' === $context && current_user_can( 'edit_post', $event_id ) ) {
		$contacts = get_post_meta( $event_id, '_wp_seed_event_contacts', true );
		$contacts = array_values(
			array_filter(
				is_array( $contacts ) ? $contacts : array(),
				static function ( $contact ) {
					$roles = wp_seed_events_contact_role_keys( $contact, wp_seed_events_contact_roles() );
					return in_array( 'contact', $roles, true );
				}
			)
		);
		return wp_seed_events_normalize_contact_rest_value( $contacts, $contacts );
	}

	$event = wp_seed_events_get_event_data( $event_id );
	return $event['contact'] ?? array();
}

function wp_seed_events_contact_rest_update( $value, $object ) {
	$event_id = is_object( $object ) ? absint( $object->ID ?? 0 ) : absint( $object['id'] ?? 0 );

	if ( 0 === $event_id || ! current_user_can( 'edit_post', $event_id ) ) {
		return new WP_Error( 'rest_cannot_update', __( 'Vous ne pouvez pas modifier ce contact.', 'wp-seed-events' ), array( 'status' => 403 ) );
	}

	if ( ! is_array( $value ) ) {
		return new WP_Error( 'rest_invalid_contact', __( 'Le contact doit être une liste de personnes.', 'wp-seed-events' ), array( 'status' => 400 ) );
	}

	$existing          = get_post_meta( $event_id, '_wp_seed_event_contacts', true );
	$existing          = is_array( $existing ) ? $existing : array();
	$preserved         = array();
	$existing_contacts = array();
	foreach ( $existing as $association ) {
		$roles = wp_seed_events_contact_role_keys( $association, wp_seed_events_contact_roles() );
		if ( in_array( 'contact', $roles, true ) ) {
			$existing_contacts[] = $association;
		} else {
			$preserved[] = $association;
		}
	}
	$contacts = array_merge( $preserved, wp_seed_events_normalize_contact_rest_value( $value, $existing_contacts ) );

	if ( function_exists( 'wp_seed_events_stored_people' ) && function_exists( 'wp_seed_events_save_people' ) ) {
		$people = wp_seed_events_stored_people();
		foreach ( $contacts as $contact ) {
			if ( ! in_array( 'contact', wp_seed_events_contact_role_keys( $contact, wp_seed_events_contact_roles() ), true ) ) {
				continue;
			}
			$person_key = sanitize_key( $contact['person_key'] ?? '' );
			if ( '' !== $person_key ) {
				$people[ $person_key ] = wp_seed_events_sanitize_person( $contact, $person_key );
			}
		}
		wp_seed_events_save_people( $people );
	}

	if ( array() === $contacts ) {
		delete_post_meta( $event_id, '_wp_seed_event_contacts' );
	} else {
		update_post_meta( $event_id, '_wp_seed_event_contacts', $contacts );
	}

	wp_seed_events_dynamic_data_invalidate_event_cache( $event_id );
	return true;
}

function wp_seed_events_register_contact_rest_fields() {
	if ( ! function_exists( 'register_rest_field' ) ) {
		return;
	}

	register_rest_field(
		'wp_seed_event',
		'contact',
		array(
			'get_callback'    => 'wp_seed_events_contact_rest_get',
			'update_callback' => 'wp_seed_events_contact_rest_update',
			'schema'          => array(
				'description' => 'Canonical event contact associations.',
				'type'        => 'array',
				'items'       => array( 'type' => 'object' ),
			),
		)
	);
}

/**
 * Accept only the explicit stored values that authorize publication.
 *
 * @param mixed $value Stored authorization value.
 * @return bool
 */
function wp_seed_events_contact_publication_is_authorized( $value ) {
	return true === $value || 1 === $value || '1' === $value;
}

function wp_seed_events_normalize_person_email( $value ) {
	$email = sanitize_email( trim( (string) $value ) );

	return '' !== $email && is_email( $email ) ? $email : '';
}

function wp_seed_events_normalize_person_phone( $value ) {
	$phone = sanitize_text_field( (string) $value );
	$phone = trim( (string) preg_replace( '/\\s+/u', ' ', $phone ) );

	if ( '' === $phone || ! preg_match( '/^\\+?[0-9\\s().\\/-]+$/u', $phone ) ) {
		return '';
	}

	$digits = preg_replace( '/\\D+/', '', $phone );
	$length = strlen( (string) $digits );

	return $length >= 6 && $length <= 15 ? $phone : '';
}

function wp_seed_events_normalize_person_link( $value ) {
	return wp_seed_events_sanitize_public_http_url( $value );
}

function wp_seed_events_normalize_person_coordinates( $person ) {
	$person = is_array( $person ) ? $person : array();

	return array(
		'phone' => wp_seed_events_normalize_person_phone( $person['phone'] ?? '' ),
		'email' => wp_seed_events_normalize_person_email( $person['email'] ?? '' ),
		'link'  => wp_seed_events_normalize_person_link( $person['link'] ?? '' ),
	);
}

function wp_seed_events_contact_publication_fields() {
	return array(
		'phone' => 'publish_phone',
		'email' => 'publish_email',
		'link'  => 'publish_link',
	);
}

function wp_seed_events_contact_publication_state( $contact ) {
	$contact     = is_array( $contact ) ? $contact : array();
	$coordinates = wp_seed_events_normalize_person_coordinates( $contact );
	$state       = array();

	foreach ( wp_seed_events_contact_publication_fields() as $coordinate_key => $publication_key ) {
		$state[ $publication_key ] = (
			'' !== $coordinates[ $coordinate_key ]
			&& wp_seed_events_contact_publication_is_authorized( $contact[ $publication_key ] ?? false )
		);
	}

	return $state;
}

function wp_seed_events_contacts_identify_same_association( $submitted_contact, $stored_contact ) {
	if ( ! is_array( $submitted_contact ) || ! is_array( $stored_contact ) ) {
		return false;
	}

	$submitted_key = sanitize_key( $submitted_contact['person_key'] ?? '' );
	$stored_key    = sanitize_key( $stored_contact['person_key'] ?? '' );

	if ( '' !== $submitted_key && '' !== $stored_key ) {
		return $submitted_key === $stored_key;
	}

	$submitted_name = sanitize_text_field( $submitted_contact['name'] ?? '' );
	$stored_name    = sanitize_text_field( $stored_contact['name'] ?? '' );

	return '' !== $submitted_name && $submitted_name === $stored_name;
}

/**
 * Normalize coordinates and their three independent publication flags.
 *
 * Existing associations revoke a flag whenever the normalized coordinate
 * changes. New associations may explicitly authorize a valid coordinate.
 *
 * @param array $contact              Submitted association.
 * @param array $existing_contact     Stored association at the same position.
 * @param bool  $existing_association Whether both rows identify the same association.
 * @return array
 */
function wp_seed_events_normalize_contact_publication_for_storage( $contact, $existing_contact = array(), $existing_association = false ) {
	$contact              = is_array( $contact ) ? $contact : array();
	$existing_contact     = is_array( $existing_contact ) ? $existing_contact : array();
	$coordinates          = wp_seed_events_normalize_person_coordinates( $contact );
	$existing_coordinates = wp_seed_events_normalize_person_coordinates( $existing_contact );
	$normalized           = array();

	foreach ( wp_seed_events_contact_publication_fields() as $coordinate_key => $publication_key ) {
		$is_authorized = wp_seed_events_contact_publication_is_authorized( $contact[ $publication_key ] ?? false );

		if (
			'' === $coordinates[ $coordinate_key ]
			|| ( $existing_association && $coordinates[ $coordinate_key ] !== $existing_coordinates[ $coordinate_key ] )
		) {
			$is_authorized = false;
		}

		$normalized[ $coordinate_key ] = $coordinates[ $coordinate_key ];
		$normalized[ $publication_key ] = $is_authorized;
	}

	return $normalized;
}

function wp_seed_events_people_submission_has_complete_payload( $request ) {
	if ( ! is_array( $request ) ) {
		return false;
	}

	$changed = isset( $request['wp_seed_event_people_changed'] ) && is_scalar( $request['wp_seed_event_people_changed'] )
		? (string) $request['wp_seed_event_people_changed']
		: '';
	$payload_present = isset( $request['wp_seed_event_people_payload_present'] ) && is_scalar( $request['wp_seed_event_people_payload_present'] )
		? (string) $request['wp_seed_event_people_payload_present']
		: '';

	return '1' === $changed && '1' === $payload_present;
}

function wp_seed_events_public_event_people_data( $post_id ) {
	$contacts = get_post_meta( $post_id, '_wp_seed_event_contacts', true );

	if ( ! is_array( $contacts ) ) {
		return array();
	}

	$roles  = wp_seed_events_contact_roles();
	$people = array();

	foreach ( $contacts as $contact ) {
		$name = is_array( $contact ) ? sanitize_text_field( $contact['name'] ?? '' ) : '';

		if ( '' === $name ) {
			continue;
		}

		$coordinates  = wp_seed_events_normalize_person_coordinates( $contact );
		$public_phone = wp_seed_events_contact_publication_is_authorized( $contact['publish_phone'] ?? false ) ? $coordinates['phone'] : '';
		$public_email = wp_seed_events_contact_publication_is_authorized( $contact['publish_email'] ?? false ) ? $coordinates['email'] : '';
		$public_url   = wp_seed_events_contact_publication_is_authorized( $contact['publish_link'] ?? false ) ? $coordinates['link'] : '';

		$people[] = array(
			'name'         => $name,
			'role_keys'    => wp_seed_events_contact_role_keys( $contact, $roles ),
			'roles'        => wp_seed_events_contact_role_labels( $contact, $roles ),
			'public_email' => $public_email,
			'public_phone' => $public_phone,
			'public_url'   => $public_url,

			// Temporary aliases keep historical consumers on the filtered values.
			'email'        => $public_email,
			'phone'        => $public_phone,
			'link'         => $public_url,
		);
	}

	return $people;
}
