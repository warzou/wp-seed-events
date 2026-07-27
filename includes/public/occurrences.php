<?php
/**
 * Event occurrences API for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_get_event_occurrences( $event_id, $args = array() ) {
	$event_id = absint( $event_id );
	$args     = wp_parse_args(
		$args,
		array(
			'include_cancelled' => true,
			'only_active'       => false,
			'status'            => 'all',
		)
	);

	$raw_occurrences = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $raw_occurrences ) ) {
		return array();
	}

	$occurrences = array();

	foreach ( $raw_occurrences as $index => $raw_occurrence ) {
		$occurrence = wp_seed_events_normalize_occurrence( $raw_occurrence, $event_id, (int) $index );

		if ( array() === $occurrence ) {
			continue;
		}

		if ( ! $args['include_cancelled'] && $occurrence['is_cancelled'] ) {
			continue;
		}

		if ( $args['only_active'] && ! $occurrence['is_active'] ) {
			continue;
		}

		if ( 'future' === $args['status'] && ! $occurrence['is_future'] ) {
			continue;
		}

		if ( 'past' === $args['status'] && ! $occurrence['is_past'] ) {
			continue;
		}

		$occurrences[] = $occurrence;
	}

	usort(
		$occurrences,
		function ( $first, $second ) {
			return strcmp( (string) $first['start_sort'], (string) $second['start_sort'] );
		}
	);

	return $occurrences;
}

function wp_seed_events_normalize_occurrence( $raw_occurrence, $event_id, $index = 0 ) {
	if ( ! is_array( $raw_occurrence ) ) {
		return array();
	}

	$start_date = isset( $raw_occurrence['start_date'] ) ? trim( (string) $raw_occurrence['start_date'] ) : '';

	if ( '' === $start_date || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
		return array();
	}

	$end_date = isset( $raw_occurrence['end_date'] ) ? trim( (string) $raw_occurrence['end_date'] ) : '';

	if ( '' !== $end_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
		$end_date = '';
	}

	$start_time = isset( $raw_occurrence['start_time'] ) ? trim( (string) $raw_occurrence['start_time'] ) : '';
	$end_time   = isset( $raw_occurrence['end_time'] ) ? trim( (string) $raw_occurrence['end_time'] ) : '';

	if ( '' !== $start_time && ! preg_match( '/^\d{2}:\d{2}$/', $start_time ) ) {
		$start_time = '';
	}

	if ( '' !== $end_time && ! preg_match( '/^\d{2}:\d{2}$/', $end_time ) ) {
		$end_time = '';
	}

	$uid          = isset( $raw_occurrence['uid'] ) ? wp_seed_events_sanitize_occurrence_uid( $raw_occurrence['uid'] ) : '';
	$derived_id   = wp_seed_events_occurrence_id( $raw_occurrence, $event_id, $index );
	$promotion_id = absint( $raw_occurrence['promotion_id'] ?? 0 );
	$parcours_year = wp_seed_events_normalize_parcours_year( $raw_occurrence['parcours_year'] ?? 0 );
	$promotion     = 0 < $promotion_id ? wp_seed_events_get_promotion( $promotion_id ) : array();

	if ( array() === $promotion || 0 === $parcours_year ) {
		$promotion_id = 0;
		$parcours_year = 0;
		$promotion = array();
	}

	$all_day      = ! empty( $raw_occurrence['all_day'] );
	$is_cancelled = ! empty( $raw_occurrence['cancelled'] );
	$start_sort   = $start_date . ' ' . ( $all_day ? '00:00' : ( '' !== $start_time ? $start_time : '00:00' ) );
	$end_sort     = ( '' !== $end_date ? $end_date : $start_date ) . ' ' . ( $all_day ? '23:59' : ( '' !== $end_time ? $end_time : ( '' !== $start_time ? $start_time : '00:00' ) ) );
	$today          = current_time( 'Y-m-d' );
	$is_active      = ! $is_cancelled;
	$is_date_future = $start_date >= $today;
	$is_date_past   = $start_date < $today;
	$is_future      = $is_active && $is_date_future;
	$is_past        = $is_active && $is_date_past;

	$occurrence = array(
		'id'             => '' !== $uid ? $uid : $derived_id,
		'uid'            => $uid,
		'derived_id'     => $derived_id,
		'event_id'       => absint( $event_id ),
		'promotion_id'   => $promotion_id,
		'promotion'      => $promotion,
		'parcours_year'  => $parcours_year,
		'parcours_year_label' => wp_seed_events_parcours_year_label( $parcours_year ),
		'start_date'     => $start_date,
		'end_date'       => $end_date,
		'start_time'     => $start_time,
		'end_time'       => $end_time,
		'all_day'        => $all_day ? '1' : '',
		'cancelled'      => $is_cancelled ? '1' : '',
		'start_sort'     => $start_sort,
		'end_sort'       => $end_sort,
		'is_dated'       => true,
		'is_active'      => $is_active,
		'is_date_future' => $is_date_future,
		'is_date_past'   => $is_date_past,
		'is_future'      => $is_future,
		'is_past'        => $is_past,
		'is_cancelled'   => $is_cancelled,
	);

	$occurrence['date_label']     = wp_seed_events_format_occurrence_date_line( $occurrence );
	$occurrence['time_label']     = wp_seed_events_format_occurrence_time_line( $occurrence );
	$occurrence['datetime_label'] = trim( $occurrence['date_label'] . ( '' !== $occurrence['time_label'] ? ' ' . $occurrence['time_label'] : '' ) );

	return $occurrence;
}

/**
 * Validate a Promotion / parcours year pair before occurrence persistence.
 *
 * @param mixed $promotion_id Promotion ID.
 * @param mixed $parcours_year Parcours year.
 * @param bool  $allow_archived Whether an existing archived association may remain.
 * @return true|WP_Error
 */
function wp_seed_events_validate_occurrence_parcours( $promotion_id, $parcours_year, $allow_archived = false ) {
	$promotion_id = absint( $promotion_id );
	$parcours_year = absint( $parcours_year );

	if ( 0 === $promotion_id && 0 === $parcours_year ) {
		return true;
	}

	if ( 0 === $promotion_id ) {
		return new WP_Error( 'wp_seed_events_parcours_year_without_promotion', 'Choisissez une promotion avant l’année du parcours.' );
	}

	if ( 0 === wp_seed_events_normalize_parcours_year( $parcours_year ) ) {
		return new WP_Error( 'wp_seed_events_promotion_without_parcours_year', 'Choisissez une année du parcours comprise entre 1 et 4.' );
	}

	$promotion = wp_seed_events_get_promotion( $promotion_id );

	if ( array() === $promotion ) {
		return new WP_Error( 'wp_seed_events_invalid_promotion', 'La promotion sélectionnée n’existe pas.' );
	}

	if ( 'archived' === $promotion['status'] && ! $allow_archived ) {
		return new WP_Error( 'wp_seed_events_archived_promotion', 'Cette promotion est archivée et ne peut plus être attribuée.' );
	}

	return true;
}

function wp_seed_events_get_next_active_occurrence( $event_id ) {
	$occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => false,
			'only_active'       => true,
			'status'            => 'future',
		)
	);

	return array() === $occurrences ? array() : reset( $occurrences );
}


function wp_seed_events_get_last_active_occurrence( $event_id ) {
	$occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => false,
			'only_active'       => true,
			'status'            => 'all',
		)
	);

	return array() === $occurrences ? array() : end( $occurrences );
}

function wp_seed_events_get_event_lifecycle( $event_id ) {
	$occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => true,
			'status'            => 'all',
		)
	);

	if ( array() === $occurrences ) {
		return 'undated';
	}

	$active_occurrences = array_values(
		array_filter(
			$occurrences,
			function ( $occurrence ) {
				return ! empty( $occurrence['is_active'] );
			}
		)
	);

	if ( array() === $active_occurrences ) {
		return 'cancelled_only';
	}

	foreach ( $active_occurrences as $occurrence ) {
		if ( ! empty( $occurrence['is_future'] ) ) {
			return 'upcoming';
		}
	}

	return 'past';
}

function wp_seed_events_sanitize_occurrence_uid( $uid ) {
	$uid = strtolower( trim( sanitize_text_field( (string) $uid ) ) );

	if ( '' === $uid ) {
		return '';
	}

	if ( preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $uid ) ) {
		return $uid;
	}

	return '';
}

function wp_seed_events_generate_occurrence_uid() {
	if ( function_exists( 'wp_generate_uuid4' ) ) {
		return wp_generate_uuid4();
	}

	return sprintf(
		'%08s-%04s-%04s-%04s-%012s',
		substr( md5( uniqid( '', true ) ), 0, 8 ),
		substr( md5( uniqid( '', true ) ), 0, 4 ),
		'4' . substr( md5( uniqid( '', true ) ), 0, 3 ),
		'a' . substr( md5( uniqid( '', true ) ), 0, 3 ),
		substr( md5( uniqid( '', true ) ), 0, 12 )
	);
}

function wp_seed_events_occurrence_id( $raw_occurrence, $event_id, $index = 0 ) {
	$parts = array(
		absint( $event_id ),
		(int) $index,
		isset( $raw_occurrence['start_date'] ) ? (string) $raw_occurrence['start_date'] : '',
		isset( $raw_occurrence['start_time'] ) ? (string) $raw_occurrence['start_time'] : '',
		isset( $raw_occurrence['end_date'] ) ? (string) $raw_occurrence['end_date'] : '',
		isset( $raw_occurrence['end_time'] ) ? (string) $raw_occurrence['end_time'] : '',
		! empty( $raw_occurrence['all_day'] ) ? '1' : '',
	);

	return 'occ-' . substr( md5( implode( '|', $parts ) ), 0, 16 );
}
