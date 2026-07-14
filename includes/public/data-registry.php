<?php
/**
 * Dynamic data registry for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_dynamic_data_fields() {
	return array(
		'title'       => array(
			'key'         => 'title',
			'label'       => 'Titre',
			'type'        => 'text',
			'description' => 'Titre de l\'evenement.',
		),
		'types'       => array(
			'key'         => 'types',
			'label'       => 'Types',
			'type'        => 'text',
			'description' => 'Types associes a l\'evenement.',
		),
		'next_date'   => array(
			'key'         => 'next_date',
			'label'       => 'Prochaine date',
			'type'        => 'text',
			'description' => 'Prochaine date active aujourd\'hui ou dans le futur.',
		),
		'next_time'   => array(
			'key'         => 'next_time',
			'label'       => 'Horaire de la prochaine date',
			'type'        => 'text',
			'description' => 'Horaire de la prochaine date active.',
		),
		'display_date' => array(
			'key'         => 'display_date',
			'label'       => 'Date de reference',
			'type'        => 'text',
			'description' => 'Prochaine date active, sinon derniere date active.',
		),
		'display_time' => array(
			'key'         => 'display_time',
			'label'       => 'Horaire de la date de reference',
			'type'        => 'text',
			'description' => 'Horaire associe a la date de reference.',
		),
		'place'       => array(
			'key'         => 'place',
			'label'       => 'Lieu',
			'type'        => 'text',
			'description' => 'Nom du lieu de l\'evenement.',
		),
		'description' => array(
			'key'         => 'description',
			'label'       => 'Description',
			'type'        => 'text',
			'description' => 'Description texte de l\'evenement.',
		),
	);
}

// Les valeurs retournees sont des chaines texte destinees aux providers ; l'echappement final reste sous la responsabilite du point de rendu.
function wp_seed_events_dynamic_data_get_value( $field, $event_id = 0, $context = array() ) {
	$field = sanitize_key( (string) $field );

	if ( ! array_key_exists( $field, wp_seed_events_dynamic_data_fields() ) ) {
		return '';
	}

	$event_id = wp_seed_events_dynamic_data_event_id( $event_id, $context );

	if ( 0 === $event_id ) {
		return '';
	}

	$event = wp_seed_events_public_event_data( $event_id );

	if ( array() === $event ) {
		return '';
	}

	switch ( $field ) {
		case 'title':
			return trim( wp_strip_all_tags( (string) ( $event['title'] ?? '' ) ) );
		case 'types':
			return empty( $event['types'] ) || ! is_array( $event['types'] ) ? '' : implode( ', ', array_map( 'wp_strip_all_tags', $event['types'] ) );
		case 'next_date':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_next_date_line( $event ) ) );
		case 'next_time':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_next_time_line( $event ) ) );
		case 'display_date':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_display_date_line( $event ) ) );
		case 'display_time':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_display_time_line( $event ) ) );
		case 'place':
			return empty( $event['place']['name'] ) ? '' : trim( wp_strip_all_tags( (string) $event['place']['name'] ) );
		case 'description':
			$description = wp_strip_all_tags( strip_shortcodes( (string) ( $event['description'] ?? '' ) ) );
			return trim( preg_replace( '/\s+/', ' ', $description ) );
		default:
			return '';
	}
}

function wp_seed_events_dynamic_data_event_id( $event_id = 0, $context = array() ) {
	$event_id = absint( $event_id );

	if ( 0 !== $event_id ) {
		return $event_id;
	}

	if ( isset( $context['event_id'] ) ) {
		$event_id = absint( $context['event_id'] );
	}

	if ( 0 === $event_id && isset( $context['post_id'] ) ) {
		$event_id = absint( $context['post_id'] );
	}

	if ( 0 !== $event_id ) {
		return $event_id;
	}

	global $wp_seed_events_public_event_id;

	$event_id = absint( $wp_seed_events_public_event_id ?? 0 );

	if ( 0 !== $event_id ) {
		return $event_id;
	}

	$current_id = get_the_ID();

	if ( $current_id && 'wp_seed_event' === get_post_type( $current_id ) ) {
		return absint( $current_id );
	}

	return 0;
}
