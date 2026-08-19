<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wp_seed_events_contact_usage_counts() {
	$usage     = array();
	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( $event_ids as $event_id ) {
		$contacts = get_post_meta( $event_id, '_wp_seed_event_contacts', true );
		$seen     = array();

		if ( ! is_array( $contacts ) ) {
			continue;
		}

		foreach ( $contacts as $contact ) {
			$person_key = is_array( $contact ) ? wp_seed_events_contact_person_key( $contact ) : '';
			if ( '' !== $person_key ) {
				$seen[ $person_key ] = true;
			}
		}

		foreach ( array_keys( $seen ) as $person_key ) {
			$usage[ $person_key ] = ( $usage[ $person_key ] ?? 0 ) + 1;
		}
	}

	return $usage;
}

function wp_seed_events_get_contact_suggestions( $current_post_id, $limit = 0 ) {
	unset( $current_post_id );
	$people = array_values( wp_seed_events_people() );
	$usage  = wp_seed_events_contact_usage_counts();

	usort(
		$people,
		function ( $first_person, $second_person ) use ( $usage ) {
			$first_key    = sanitize_key( $first_person['person_key'] ?? '' );
			$second_key   = sanitize_key( $second_person['person_key'] ?? '' );
			$first_usage  = $usage[ $first_key ] ?? 0;
			$second_usage = $usage[ $second_key ] ?? 0;

			if ( $first_usage !== $second_usage ) {
				return $second_usage <=> $first_usage;
			}

			$name_compare = strcasecmp(
				remove_accents( (string) ( $first_person['name'] ?? '' ) ),
				remove_accents( (string) ( $second_person['name'] ?? '' ) )
			);

			return 0 !== $name_compare ? $name_compare : strcmp( $first_key, $second_key );
		}
	);

	return $limit > 0 ? array_slice( $people, 0, $limit ) : $people;
}
