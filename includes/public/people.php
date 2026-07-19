<?php
/**
 * Person contact normalization and public projections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

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
