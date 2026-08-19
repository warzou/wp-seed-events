<?php
/**
 * Explicit, idempotent migration to the canonical event Contact role.
 *
 * Nothing runs automatically. Deployment tooling must preflight, back up, and
 * invoke the migration deliberately.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_contact_migration_role_rows( $contacts, $role ) {
	$rows = array();

	foreach ( is_array( $contacts ) ? $contacts : array() as $contact ) {
		if ( ! is_array( $contact ) ) {
			continue;
		}

		$raw_roles = is_array( $contact['roles'] ?? null ) ? $contact['roles'] : array( $contact['role'] ?? '' );
		$raw_roles = array_map( 'sanitize_key', $raw_roles );
		if ( ! in_array( $role, $raw_roles, true ) ) {
			continue;
		}

		$row = $contact;
		unset( $row['role'], $row['roles'] );
		ksort( $row );
		$rows[] = $row;
	}

	usort( $rows, static function ( $left, $right ) {
		return strcmp( wp_json_encode( $left ), wp_json_encode( $right ) );
	} );

	return $rows;
}

function wp_seed_events_contact_migration_classify( $contacts ) {
	$registration = wp_seed_events_contact_migration_role_rows( $contacts, 'registration_contact' );
	$information  = wp_seed_events_contact_migration_role_rows( $contacts, 'information_contact' );

	if ( array() === $registration && array() === $information ) {
		return 'A';
	}
	if ( array() !== $registration && array() === $information ) {
		return 'B';
	}
	if ( array() === $registration && array() !== $information ) {
		return 'C';
	}

	return $registration === $information ? 'D' : 'E';
}

function wp_seed_events_contact_migration_preflight( $event_ids = null ) {
	if ( null === $event_ids ) {
		$event_ids = get_posts( array(
			'post_type' => 'wp_seed_event', 'post_status' => 'any', 'posts_per_page' => -1,
			'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC',
		) );
	}

	$result = array( 'counts' => array_fill_keys( array( 'A', 'B', 'C', 'D', 'E' ), 0 ), 'events' => array() );
	foreach ( $event_ids as $event_id ) {
		$category = wp_seed_events_contact_migration_classify( get_post_meta( $event_id, '_wp_seed_event_contacts', true ) );
		$result['counts'][ $category ]++;
		$result['events'][] = array( 'event_id' => absint( $event_id ), 'category' => $category );
	}

	return $result;
}

function wp_seed_events_contact_migration_normalize_rows( $contacts ) {
	$normalized = array();
	$seen       = array();
	$roles_map  = wp_seed_events_contact_roles();

	foreach ( is_array( $contacts ) ? $contacts : array() as $contact ) {
		if ( ! is_array( $contact ) ) {
			continue;
		}

		$raw_roles = is_array( $contact['roles'] ?? null ) ? $contact['roles'] : array( $contact['role'] ?? '' );
		$raw_roles = array_map( 'sanitize_key', $raw_roles );
		$legacy_roles = array( 'registration_contact', 'information_contact', 'contact_inscription', 'contact_information' );
		if ( array() === array_intersect( $raw_roles, $legacy_roles ) ) {
			$normalized[] = $contact;
			continue;
		}
		$roles     = array();
		foreach ( $raw_roles as $raw_role ) {
			$role = wp_seed_events_canonical_contact_role( $raw_role );
			if ( isset( $roles_map[ $role ] ) && ! in_array( $role, $roles, true ) ) {
				$roles[] = $role;
			}
		}
		$contact['roles'] = $roles;
		$contact['role']  = $roles[0] ?? '';

		$signature_row = $contact;
		ksort( $signature_row );
		$signature = hash( 'sha256', wp_json_encode( $signature_row ) );
		if ( isset( $seen[ $signature ] ) ) {
			continue;
		}
		$seen[ $signature ] = true;
		$normalized[]       = $contact;
	}

	return $normalized;
}

function wp_seed_events_migrate_contacts_to_canonical( $event_ids = null ) {
	$preflight = wp_seed_events_contact_migration_preflight( $event_ids );
	if ( 0 < $preflight['counts']['E'] ) {
		return new WP_Error( 'contact_migration_ambiguous', 'Different registration and information contacts require human arbitration.', $preflight );
	}

	$changed = 0;
	$before  = array();
	$missing = array( '__wp_seed_events_missing_option__' => true );
	$options_before = array(
		'wp_seed_events_contact_model_version' => get_option( 'wp_seed_events_contact_model_version', $missing ),
		'wp_seed_events_contact_migration_log'  => get_option( 'wp_seed_events_contact_migration_log', $missing ),
	);
	foreach ( $preflight['events'] as $event ) {
		$event_id = $event['event_id'];
		$current  = get_post_meta( $event_id, '_wp_seed_event_contacts', true );
		$current  = is_array( $current ) ? $current : array();
		$next     = wp_seed_events_contact_migration_normalize_rows( $current );
		if ( $next === $current ) {
			continue;
		}
		$before[ $event_id ] = $current;
		update_post_meta( $event_id, '_wp_seed_event_contacts', $next );
		$changed++;
	}

	update_option( 'wp_seed_events_contact_model_version', 'contact-v1', false );
	update_option( 'wp_seed_events_contact_migration_log', array( 'changed' => $changed, 'counts' => $preflight['counts'] ), false );

	return array( 'changed' => $changed, 'before' => $before, 'options_before' => $options_before, 'preflight' => $preflight );
}

function wp_seed_events_rollback_contact_migration( $before, $options_before = null ) {
	foreach ( is_array( $before ) ? $before : array() as $event_id => $contacts ) {
		update_post_meta( absint( $event_id ), '_wp_seed_event_contacts', $contacts );
	}

	foreach ( is_array( $options_before ) ? $options_before : array() as $option => $value ) {
		if ( is_array( $value ) && true === ( $value['__wp_seed_events_missing_option__'] ?? false ) ) {
			delete_option( $option );
		} else {
			update_option( $option, $value, false );
		}
	}
}
