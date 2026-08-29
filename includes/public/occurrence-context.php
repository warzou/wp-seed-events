<?php
/**
 * Request-local occurrence context for builder integrations.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return the request-local occurrence context stack.
 *
 * @internal
 *
 * @return array
 */
function &wp_seed_events_occurrence_context_stack() {
	static $stack = array();

	return $stack;
}

/**
 * Split a canonical public occurrence date/time value.
 *
 * @param mixed $value Public date/time value.
 * @return array
 */
function wp_seed_events_occurrence_context_split_datetime( $value ) {
	$value = trim( (string) $value );

	if ( ! preg_match( '/^(\d{4}-\d{2}-\d{2})(?:[ T]([0-2]\d:[0-5]\d))?/', $value, $matches ) ) {
		return array(
			'date' => '',
			'time' => '',
		);
	}

	return array(
		'date' => $matches[1],
		'time' => isset( $matches[2] ) ? $matches[2] : '',
	);
}

/**
 * Build the canonical occurrence context from one public collection item.
 *
 * @param array  $item                   Public occurrence collection item.
 * @param string $collection_instance_id Collection instance identifier.
 * @param int    $current_item_index     Zero-based item index.
 * @return array
 */
function wp_seed_events_occurrence_context_from_item( $item, $collection_instance_id, $current_item_index ) {
	if ( ! is_array( $item ) ) {
		return array();
	}

	$event_id       = absint( $item['event_id'] ?? 0 );
	$occurrence_uid = sanitize_text_field( (string) ( $item['occurrence_uid'] ?? '' ) );
	$collection_id  = sanitize_key( (string) $collection_instance_id );
	$item_index     = max( 0, (int) $current_item_index );

	if ( 0 === $event_id || '' === $occurrence_uid || '' === $collection_id ) {
		return array();
	}

	$context = array(
		'event_id'               => $event_id,
		'occurrence_uid'         => $occurrence_uid,
		'collection_instance_id' => $collection_id,
		'current_item_index'     => $item_index,
		'item_key'               => implode( ':', array( $collection_id, $event_id, $occurrence_uid, $item_index ) ),
		'item'                   => $item,
	);

	return $context;
}

/**
 * Return the current occurrence context without exposing the mutable stack.
 *
 * @return array
 */
function wp_seed_events_occurrence_context_current() {
	$stack = &wp_seed_events_occurrence_context_stack();

	return array() === $stack ? array() : end( $stack );
}

/**
 * Run a callback under one occurrence context and always restore its parent.
 *
 * @param array    $context  Canonical occurrence context.
 * @param callable $callback Rendering callback.
 * @return mixed
 */
function wp_seed_events_with_occurrence_context( $context, $callback ) {
	if ( ! is_array( $context ) || array() === $context || ! is_callable( $callback ) ) {
		return null;
	}

	$stack   = &wp_seed_events_occurrence_context_stack();
	$stack[] = $context;

	try {
		return call_user_func( $callback, $context );
	} finally {
		array_pop( $stack );
	}
}

/**
 * Registry of public fields available only under an occurrence context.
 *
 * @return array
 */
function wp_seed_events_occurrence_dynamic_data_fields() {
	return array(
		'event_title'              => 'Titre de l’événement',
		'event_slug'               => 'Slug de l’événement',
		'event_type'               => 'Type d’événement',
		'event_status'             => 'Statut de l’événement',
		'event_is_pinned'          => 'Événement épinglé',
		'occurrence_uid'           => 'Identifiant de l’occurrence',
		'occurrence_start'         => 'Début canonique de l’occurrence',
		'occurrence_end'           => 'Fin canonique de l’occurrence',
		'occurrence_start_date'    => 'Date de début',
		'occurrence_end_date'      => 'Date de fin',
		'occurrence_start_time'    => 'Heure de début',
		'occurrence_end_time'      => 'Heure de fin',
		'occurrence_is_cancelled'  => 'Occurrence annulée',
	);
}

/**
 * Project one public field from the current occurrence context.
 *
 * @param string     $field   Canonical occurrence field.
 * @param array|null $context Optional explicit context.
 * @return string
 */
function wp_seed_events_occurrence_context_value( $field, $context = null ) {
	$field   = sanitize_key( (string) $field );
	$context = is_array( $context ) ? $context : wp_seed_events_occurrence_context_current();

	if ( ! array_key_exists( $field, wp_seed_events_occurrence_dynamic_data_fields() ) || array() === $context ) {
		return '';
	}

	$item = isset( $context['item'] ) && is_array( $context['item'] ) ? $context['item'] : array();

	if ( array() === $item ) {
		return '';
	}

	$start     = wp_seed_events_occurrence_context_split_datetime( $item['start'] ?? '' );
	$end       = wp_seed_events_occurrence_context_split_datetime( $item['end'] ?? '' );

	switch ( $field ) {
		case 'event_title':
			return trim( wp_strip_all_tags( (string) ( $item['event_title'] ?? '' ) ) );
		case 'event_slug':
			return sanitize_title( (string) ( $item['event_slug'] ?? '' ) );
		case 'event_type':
			return sanitize_key( (string) ( $item['event_type'] ?? '' ) );
		case 'event_status':
			return sanitize_key( (string) ( $item['event_status'] ?? '' ) );
		case 'event_is_pinned':
			return empty( $item['is_pinned'] ) ? '0' : '1';
		case 'occurrence_uid':
			return sanitize_text_field( (string) ( $item['occurrence_uid'] ?? '' ) );
		case 'occurrence_start':
			return sanitize_text_field( (string) ( $item['start'] ?? '' ) );
		case 'occurrence_end':
			return sanitize_text_field( (string) ( $item['end'] ?? '' ) );
		case 'occurrence_start_date':
			return '' === $start['date'] ? '' : wp_seed_events_public_format_occurrence_date( $start['date'] );
		case 'occurrence_end_date':
			return '' === $end['date'] ? '' : wp_seed_events_public_format_occurrence_date( $end['date'] );
		case 'occurrence_start_time':
			return '' === $start['time'] ? '' : wp_seed_events_format_occurrence_time( $start['time'] );
		case 'occurrence_end_time':
			return '' === $end['time'] ? '' : wp_seed_events_format_occurrence_time( $end['time'] );
		case 'occurrence_is_cancelled':
			return empty( $item['is_cancelled'] ) ? '0' : '1';
		default:
			return '';
	}
}
