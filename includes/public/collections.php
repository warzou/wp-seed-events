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
	if ( wp_seed_events_public_collection_index_is_ready() ) {
		$selection = wp_seed_events_query_indexed_event_collection( $args, true );

		if ( ! is_wp_error( $selection ) ) {
			return $selection;
		}
	}

	return wp_seed_events_query_legacy_event_collection( $args );
}

/** Historical PHP selector retained while the versioned index is unavailable. */
function wp_seed_events_query_legacy_event_collection( $args = array() ) {
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

/** Whether the versioned lifecycle projections are safe for public selection. */
function wp_seed_events_public_collection_index_is_ready() {
	return function_exists( 'wp_seed_events_is_lifecycle_index_ready' )
		&& wp_seed_events_is_lifecycle_index_ready();
}

/**
 * Resolve a public type slug to the stored canonical type keys.
 *
 * @param string $type Public slug or sanitized historical key.
 * @return array|false Empty means no filter; false means unknown.
 */
function wp_seed_events_public_collection_type_keys( $type ) {
	$type = sanitize_title( (string) $type );

	if ( '' === $type ) {
		return array();
	}

	$matches = array();

	foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) {
		$type_key = sanitize_key( $type_key );

		if ( $type === sanitize_title( $type_key ) || $type === sanitize_title( $type_label ) ) {
			$matches[] = $type_key;
		}
	}

	$matches = array_values( array_unique( array_filter( $matches ) ) );

	return array() === $matches ? false : $matches;
}

/** Return the normalized canonical arguments used by the indexed selector. */
function wp_seed_events_public_collection_normalize_args( $args = array() ) {
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

	$args['status']   = wp_seed_events_public_collection_status( $args['status'] );
	$args['pinned']   = wp_seed_events_public_collection_pinned( $args['pinned'] );
	$args['type']     = sanitize_title( (string) $args['type'] );
	$args['order']    = wp_seed_events_public_collection_order( $args['order'], $args['status'] );
	$args['page']     = max( 1, absint( $args['page'] ) );
	$args['per_page'] = null === $args['per_page'] ? max( 1, absint( $args['limit'] ) ) : (int) $args['per_page'];

	return $args;
}

/** Return a canonical empty collection result. */
function wp_seed_events_public_collection_empty_result( $args ) {
	return array(
		'events'      => array(),
		'ids'         => array(),
		'total'       => 0,
		'total_pages' => 1,
		'page'        => $args['page'],
		'per_page'    => $args['per_page'],
		'args'        => array(
			'type'   => $args['type'],
			'status' => $args['status'],
			'pinned' => $args['pinned'],
			'order'  => $args['order'],
		),
	);
}

/**
 * Select ordered event IDs from the versioned lightweight projections.
 *
 * @param array $raw_args Collection arguments.
 * @param bool  $hydrate  Whether Event Data is required for the selected IDs.
 * @return array|WP_Error
 */
function wp_seed_events_query_indexed_event_collection( $raw_args, $hydrate = true ) {
	global $wpdb;

	$args = wp_seed_events_public_collection_normalize_args( $raw_args );

	if ( ! isset( $wpdb->posts, $wpdb->postmeta ) ) {
		return new WP_Error( 'event_collection_index_unavailable', 'The event collection index is unavailable.' );
	}

	$type_keys = wp_seed_events_public_collection_type_keys( $args['type'] );

	if ( false === $type_keys ) {
		return wp_seed_events_public_collection_empty_result( $args );
	}

	$today_sql     = "'" . esc_sql( current_time( 'Y-m-d' ) . ' 00:00' ) . "'";
	$next_sort     = "MIN(CASE WHEN occurrence_meta.meta_value >= {$today_sql} THEN occurrence_meta.meta_value ELSE NULL END)";
	$last_sort     = "MAX(CASE WHEN occurrence_meta.meta_value < {$today_sql} THEN occurrence_meta.meta_value ELSE NULL END)";
	$business_sort = "COALESCE({$next_sort}, {$last_sort})";
	$joins         = "
		LEFT JOIN {$wpdb->postmeta} occurrence_meta
			ON occurrence_meta.post_id = event_posts.ID
			AND occurrence_meta.meta_key = '_wp_seed_event_collection_occurrence_sort'
		LEFT JOIN {$wpdb->postmeta} pinned_meta
			ON pinned_meta.post_id = event_posts.ID
			AND pinned_meta.meta_key = '_wp_seed_event_pinned'
			AND pinned_meta.meta_value = '1'";
	$where         = "event_posts.post_type = 'wp_seed_event' AND event_posts.post_status = 'publish'";

	if ( array() !== $type_keys ) {
		$quoted_types = array_map(
			static function ( $type_key ) {
				return "'" . esc_sql( $type_key ) . "'";
			},
			$type_keys
		);
		$joins       .= "
		INNER JOIN {$wpdb->postmeta} type_meta
			ON type_meta.post_id = event_posts.ID
			AND type_meta.meta_key = '_wp_seed_event_collection_type'
			AND type_meta.meta_value IN (" . implode( ', ', $quoted_types ) . ')';
	}

	if ( 'only' === $args['pinned'] ) {
		$where .= ' AND pinned_meta.post_id IS NOT NULL';
	}

	$having = '';

	if ( 'upcoming' === $args['status'] ) {
		$having = "HAVING {$next_sort} IS NOT NULL";
	} elseif ( 'past' === $args['status'] ) {
		$having = "HAVING {$next_sort} IS NULL AND {$last_sort} IS NOT NULL";
	}

	$from_sql = "
		FROM {$wpdb->posts} event_posts
		{$joins}
		WHERE {$where}
		GROUP BY event_posts.ID
		{$having}";
	$count_sql = "SELECT COUNT(*) FROM (SELECT event_posts.ID {$from_sql}) indexed_events";

	$wpdb->last_error = '';
	$total             = absint( $wpdb->get_var( $count_sql ) );

	if ( '' !== $wpdb->last_error ) {
		return new WP_Error( 'event_collection_index_query_failed', 'The indexed event collection count failed.' );
	}

	$order_sql  = 'DESC' === $args['order'] ? 'DESC' : 'ASC';
	$select_sql = "
		SELECT event_posts.ID
		{$from_sql}
		ORDER BY
			MAX(CASE WHEN pinned_meta.post_id IS NULL THEN 0 ELSE 1 END) DESC,
			CASE WHEN {$business_sort} IS NULL THEN 1 ELSE 0 END ASC,
			{$business_sort} {$order_sql},
			event_posts.ID ASC";

	if ( $args['per_page'] > 0 ) {
		$offset      = ( $args['page'] - 1 ) * $args['per_page'];
		$select_sql .= ' LIMIT ' . absint( $args['per_page'] ) . ' OFFSET ' . absint( $offset );
	}

	$wpdb->last_error = '';
	$ids               = $wpdb->get_col( $select_sql );

	if ( '' !== $wpdb->last_error || ! is_array( $ids ) ) {
		return new WP_Error( 'event_collection_index_query_failed', 'The indexed event collection selection failed.' );
	}

	$ids         = array_values( array_filter( array_map( 'absint', $ids ) ) );
	$total_pages = $args['per_page'] > 0 ? max( 1, (int) ceil( $total / $args['per_page'] ) ) : 1;
	$events      = array();

	if ( $hydrate ) {
		foreach ( $ids as $event_id ) {
			$event = wp_seed_events_get_event_data( $event_id );

			if ( array() === $event ) {
				return new WP_Error( 'event_collection_hydration_failed', 'An indexed event could not be hydrated.' );
			}

			$events[] = $event;
		}
	}

	return array(
		'events'      => $events,
		'ids'         => $ids,
		'total'       => $total,
		'total_pages' => $total_pages,
		'page'        => $args['page'],
		'per_page'    => $args['per_page'],
		'args'        => array(
			'type'   => $args['type'],
			'status' => $args['status'],
			'pinned' => $args['pinned'],
			'order'  => $args['order'],
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

	if ( wp_seed_events_public_collection_index_is_ready() ) {
		$result = wp_seed_events_query_indexed_event_collection( $collection_args, false );
	}

	if ( ! isset( $result ) || is_wp_error( $result ) ) {
		$result = wp_seed_events_query_legacy_event_collection( $collection_args );
	}
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
