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
			'label'       => 'Prochaine heure',
			'type'        => 'text',
			'description' => 'Horaire de la prochaine date active.',
		),
		'display_date' => array(
			'key'         => 'display_date',
			'label'       => 'Date affichée',
			'type'        => 'text',
			'description' => 'Prochaine date active, sinon derniere date active.',
		),
		'display_time' => array(
			'key'         => 'display_time',
			'label'       => 'Heure affichée',
			'type'        => 'text',
			'description' => 'Horaire associe a la date de reference.',
		),
		'place'       => array(
			'key'         => 'place',
			'label'       => 'Lieu',
			'type'        => 'text',
			'description' => 'Nom du lieu de l\'evenement.',
		),
		'place_address' => array(
			'key'         => 'place_address',
			'label'       => 'Adresse du lieu',
			'type'        => 'text',
			'description' => 'Adresse seule du lieu de l\'evenement.',
		),
		'description' => array(
			'key'         => 'description',
			'label'       => 'Description',
			'type'        => 'text',
			'description' => 'Description texte de l\'evenement.',
		),
		'excerpt'     => array(
			'key'         => 'excerpt',
			'label'       => 'Extrait',
			'type'        => 'text',
			'description' => 'Extrait texte public de l\'evenement.',
		),
		'practical_info' => array(
			'key'         => 'practical_info',
			'label'       => 'Informations pratiques',
			'type'        => 'text',
			'description' => 'Informations pratiques propres a l\'evenement.',
		),
		'event_document_filename' => array(
			'key'         => 'event_document_filename',
			'label'       => 'Nom du document',
			'type'        => 'text',
			'description' => 'Nom public du document complementaire.',
		),
		'url'         => array(
			'key'         => 'url',
			'label'       => 'URL de l’événement',
			'type'        => 'url',
			'description' => 'URL publique canonique de l\'evenement.',
		),
		'place_url'   => array(
			'key'         => 'place_url',
			'label'       => 'URL du lieu',
			'type'        => 'url',
			'description' => 'URL publique associee au lieu.',
		),
		'event_document_url' => array(
			'key'         => 'event_document_url',
			'label'       => 'URL du document',
			'type'        => 'url',
			'description' => 'URL publique du document PDF complementaire.',
		),
	);
}

/**
 * Read Event Data once per event during the current PHP request.
 *
 * Empty results are cached deliberately so repeated invalid contexts do not
 * trigger additional Event Data work.
 *
 * @internal
 *
 * @param int $event_id Event post ID.
 * @return array
 */
function wp_seed_events_dynamic_data_get_event_data( $event_id ) {
	static $event_cache = array();

	$event_id = absint( $event_id );

	if ( 0 === $event_id ) {
		return array();
	}

	if ( ! array_key_exists( $event_id, $event_cache ) ) {
		$event_cache[ $event_id ] = wp_seed_events_get_event_data( $event_id );
	}

	return $event_cache[ $event_id ];
}

/**
 * Normalize a public multiline value without flattening useful line breaks.
 *
 * @param mixed $value Raw public value.
 * @return string
 */
function wp_seed_events_dynamic_data_multiline_text( $value ) {
	$value = wp_strip_all_tags( strip_shortcodes( (string) $value ) );
	$value = str_replace( array( "\r\n", "\r" ), "\n", $value );
	$lines = array_map(
		static function ( $line ) {
			return trim( preg_replace( '/[ \t]+/', ' ', $line ) );
		},
		explode( "\n", $value )
	);

	return trim( implode( "\n", $lines ) );
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

	$event = wp_seed_events_dynamic_data_get_event_data( $event_id );

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
		case 'place_address':
			return trim( wp_strip_all_tags( (string) ( $event['place_address'] ?? '' ) ) );
		case 'description':
			$description = wp_strip_all_tags( strip_shortcodes( (string) ( $event['description'] ?? '' ) ) );
			return trim( preg_replace( '/\s+/', ' ', $description ) );
		case 'excerpt':
			return trim( wp_strip_all_tags( (string) ( $event['excerpt'] ?? '' ) ) );
		case 'practical_info':
			return wp_seed_events_dynamic_data_multiline_text( $event['practical_info'] ?? '' );
		case 'event_document_filename':
			return trim( wp_strip_all_tags( (string) ( $event['event_document_filename'] ?? '' ) ) );
		case 'url':
			return wp_seed_events_sanitize_public_http_url( $event['url'] ?? '' );
		case 'place_url':
			return wp_seed_events_sanitize_public_http_url( $event['place_url'] ?? '' );
		case 'event_document_url':
			return wp_seed_events_sanitize_public_http_url( $event['event_document_url'] ?? '' );
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
