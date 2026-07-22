<?php
/**
 * Canonical public event collection queries.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Query a deterministic public event collection.
 *
 * When order is omitted, the historical shortcode order is preserved: past
 * collections are descending, while upcoming and all collections are
 * ascending. Pinned events always remain before non-pinned events.
 *
 * @param array $args Collection arguments.
 * @return array
 */
function wp_seed_events_query_event_collection( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'limit'    => 6,
			'per_page' => null,
			'page'     => 1,
			'status'   => 'upcoming',
			'type'     => '',
			'pinned'   => 'all',
			'order'    => '',
		)
	);

	$status   = wp_seed_events_public_collection_status( $args['status'] );
	$pinned   = wp_seed_events_public_collection_pinned( $args['pinned'] );
	$type     = sanitize_title( (string) $args['type'] );
	$order    = wp_seed_events_public_collection_order( $args['order'], $status );
	$page     = max( 1, absint( $args['page'] ) );
	$per_page = null === $args['per_page'] ? max( 1, absint( $args['limit'] ) ) : (int) $args['per_page'];

	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
		)
	);

	$items = array();

	foreach ( $event_ids as $event_id ) {
		$event_id = absint( $event_id );

		if ( ! wp_seed_events_public_collection_event_matches_type( $event_id, $type ) ) {
			continue;
		}

		$is_pinned = '1' === get_post_meta( $event_id, '_wp_seed_event_pinned', true );

		if ( 'only' === $pinned && ! $is_pinned ) {
			continue;
		}

		$event = wp_seed_events_get_event_data( $event_id );

		if ( array() === $event ) {
			continue;
		}

		$timing = wp_seed_events_public_collection_event_timing( $event );

		if ( 'upcoming' === $status && ! $timing['is_upcoming'] ) {
			continue;
		}

		if ( 'past' === $status && ! $timing['is_past'] ) {
			continue;
		}

		$items[] = array(
			'id'        => $event_id,
			'event'     => $event,
			'is_pinned' => $is_pinned,
			'has_date'  => $timing['has_date'],
			'sort'      => $timing['sort'],
		);
	}

	usort(
		$items,
		static function ( $first, $second ) use ( $order ) {
			if ( $first['is_pinned'] !== $second['is_pinned'] ) {
				return $first['is_pinned'] ? -1 : 1;
			}

			if ( $first['has_date'] !== $second['has_date'] ) {
				return $first['has_date'] ? -1 : 1;
			}

			$comparison = strcmp( (string) $first['sort'], (string) $second['sort'] );

			if ( 0 !== $comparison ) {
				return 'DESC' === $order ? -$comparison : $comparison;
			}

			return $first['id'] <=> $second['id'];
		}
	);

	$total       = count( $items );
	$total_pages = 1;

	if ( $per_page > 0 ) {
		$total_pages = max( 1, (int) ceil( $total / $per_page ) );
		$items       = array_slice( $items, ( $page - 1 ) * $per_page, $per_page );
	}

	return array(
		'events'      => array_values( array_column( $items, 'event' ) ),
		'ids'         => array_values( array_map( 'absint', array_column( $items, 'id' ) ) ),
		'total'       => $total,
		'total_pages' => $total_pages,
		'page'        => $page,
		'per_page'    => $per_page,
		'args'        => array(
			'type'   => $type,
			'status' => $status,
			'pinned' => $pinned,
			'order'  => $order,
		),
	);
}

/**
 * Backward-compatible event collection accessor.
 *
 * @param array $args Collection arguments.
 * @return array
 */
function wp_seed_events_get_event_collection( $args = array() ) {
	$result = wp_seed_events_query_event_collection( $args );

	return $result['events'];
}

/**
 * Apply the canonical collection selection to existing WP_Query arguments.
 *
 * Builders keep ownership of pagination and rendering. This bridge only
 * supplies the complete, ordered ID list selected by the public collection
 * contract.
 *
 * @param array $query_args      Existing WP_Query arguments.
 * @param array $collection_args Canonical collection arguments.
 * @return array
 */
function wp_seed_events_apply_collection_to_query_args( $query_args, $collection_args = array() ) {
	if ( ! is_array( $query_args ) ) {
		return $query_args;
	}

	$collection_args             = is_array( $collection_args ) ? $collection_args : array();
	$collection_args['per_page'] = -1;
	$result                       = wp_seed_events_query_event_collection( $collection_args );
	$event_ids                    = array_map( 'absint', $result['ids'] ?? array() );

	if ( ! empty( $query_args['post__in'] ) ) {
		$allowed   = array_map( 'absint', (array) $query_args['post__in'] );
		$event_ids = array_values(
			array_filter(
				$event_ids,
				static function ( $event_id ) use ( $allowed ) {
					return in_array( $event_id, $allowed, true );
				}
			)
		);
	}

	if ( ! empty( $query_args['post__not_in'] ) ) {
		$excluded  = array_map( 'absint', (array) $query_args['post__not_in'] );
		$event_ids = array_values( array_diff( $event_ids, $excluded ) );
	}

	$query_args['post__in']            = array() === $event_ids ? array( 0 ) : $event_ids;
	$query_args['orderby']             = 'post__in';
	$query_args['order']               = 'ASC';
	$query_args['ignore_sticky_posts'] = true;

	return $query_args;
}

function wp_seed_events_public_collection_status( $value ) {
	$value = strtolower( trim( (string) $value ) );

	return in_array( $value, array( 'upcoming', 'past', 'all' ), true ) ? $value : 'upcoming';
}

function wp_seed_events_public_collection_pinned( $value ) {
	$value = strtolower( trim( (string) $value ) );

	return 'only' === $value ? 'only' : 'all';
}

function wp_seed_events_public_collection_order( $value, $status = 'upcoming' ) {
	$value = strtoupper( trim( (string) $value ) );

	if ( in_array( $value, array( 'ASC', 'DESC' ), true ) ) {
		return $value;
	}

	return 'past' === $status ? 'DESC' : 'ASC';
}

function wp_seed_events_public_collection_event_matches_type( $event_id, $type ) {
	if ( '' === $type ) {
		return true;
	}

	foreach ( wp_seed_events_event_type_keys_for_event( $event_id ) as $type_key ) {
		if ( $type === wp_seed_events_event_type_public_slug( $type_key ) || $type === sanitize_title( $type_key ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Select the active business date used by public collections.
 *
 * Upcoming events use their next active occurrence. Past events use their
 * last active occurrence. Undated and cancelled-only events have no business
 * date and remain at the end of an all-events collection.
 *
 * @param array $event Event Data result.
 * @return array
 */
function wp_seed_events_public_collection_event_timing( $event ) {
	$lifecycle = isset( $event['lifecycle'] ) ? (string) $event['lifecycle'] : '';

	if ( 'upcoming' === $lifecycle && ! empty( $event['next_occurrence']['start_sort'] ) ) {
		return array(
			'has_date'    => true,
			'is_upcoming' => true,
			'is_past'     => false,
			'sort'        => (string) $event['next_occurrence']['start_sort'],
		);
	}

	if ( 'past' === $lifecycle && ! empty( $event['last_occurrence']['start_sort'] ) ) {
		return array(
			'has_date'    => true,
			'is_upcoming' => false,
			'is_past'     => true,
			'sort'        => (string) $event['last_occurrence']['start_sort'],
		);
	}

	return array(
		'has_date'    => false,
		'is_upcoming' => false,
		'is_past'     => false,
		'sort'        => '',
	);
}

/**
 * Return a public, localized lifecycle label.
 *
 * @param mixed $value Event Data lifecycle value.
 * @return string
 */
function wp_seed_events_public_event_status_label( $value ) {
	$labels = array(
		'upcoming'       => 'À venir',
		'past'           => 'Passé',
		'undated'        => 'Sans date',
		'cancelled_only' => 'Annulé',
	);

	$value = sanitize_key( (string) $value );

	return isset( $labels[ $value ] ) ? $labels[ $value ] : '';
}
