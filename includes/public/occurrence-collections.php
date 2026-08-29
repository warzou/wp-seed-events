<?php
/**
 * Canonical public occurrence collections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_occurrence_collection_error( $code, $message, $status = 400 ) {
	return new WP_Error( $code, $message, array( 'status' => absint( $status ) ) );
}

function wp_seed_events_occurrence_collection_boolean( $value, $name ) {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_int( $value ) || is_string( $value ) ) {
		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}

		if ( in_array( $value, array( '0', 'false', 'no', 'off', '' ), true ) ) {
			return false;
		}
	}

	return wp_seed_events_occurrence_collection_error(
		'wp_seed_events_occurrence_collection_invalid_' . sanitize_key( $name ),
		'Invalid boolean collection argument.'
	);
}

function wp_seed_events_occurrence_collection_date( $value, $boundary ) {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $matches ) ) {
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return false;
		}

		return $value . ( 'to' === $boundary ? ' 23:59' : ' 00:00' );
	}

	if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2}) ([01]\d|2[0-3]):([0-5]\d)$/', $value, $matches ) ) {
		if ( ! checkdate( (int) $matches[2], (int) $matches[3], (int) $matches[1] ) ) {
			return false;
		}

		return $value;
	}

	return false;
}

function wp_seed_events_occurrence_collection_normalize_args( $raw_args = array() ) {
	$raw_args = is_array( $raw_args ) ? $raw_args : array();
	$args     = wp_parse_args(
		$raw_args,
		array(
			'event_id'          => null,
			'type'              => '',
			'status'            => 'upcoming',
			'pinned'            => 'all',
			'include_cancelled' => false,
			'from'              => '',
			'to'                => '',
			'order'             => 'upcoming',
			'page'              => 1,
			'per_page'          => 20,
		)
	);

	$event_id = null;

	if ( array_key_exists( 'event_id', $raw_args ) && ! in_array( $raw_args['event_id'], array( '', null ), true ) ) {
		$event_id = absint( $raw_args['event_id'] );

		if ( 0 === $event_id ) {
			return wp_seed_events_occurrence_collection_error(
				'wp_seed_events_occurrence_collection_invalid_event',
				'Event ID must be a positive integer.'
			);
		}
	}

	$type      = sanitize_title( (string) $args['type'] );
	$type_keys = wp_seed_events_public_collection_type_keys( $type );

	if ( false === $type_keys ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_type',
			'The requested event type does not exist.'
		);
	}

	$status = strtolower( trim( (string) $args['status'] ) );

	if ( ! in_array( $status, array( 'upcoming', 'past', 'all' ), true ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_status',
			'Status must be upcoming, past or all.'
		);
	}

	$pinned = is_bool( $args['pinned'] ) ? ( $args['pinned'] ? 'only' : 'all' ) : strtolower( trim( (string) $args['pinned'] ) );

	if ( ! in_array( $pinned, array( 'all', 'only' ), true ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_pinned',
			'Pinned must be all or only.'
		);
	}

	$include_cancelled = wp_seed_events_occurrence_collection_boolean( $args['include_cancelled'], 'include_cancelled' );

	if ( is_wp_error( $include_cancelled ) ) {
		return $include_cancelled;
	}

	$order = strtolower( trim( (string) $args['order'] ) );

	if ( ! in_array( $order, array( 'upcoming', 'chronological', 'chronological_desc' ), true ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_order',
			'Unknown occurrence collection order.'
		);
	}

	$from = wp_seed_events_occurrence_collection_date( $args['from'], 'from' );
	$to   = wp_seed_events_occurrence_collection_date( $args['to'], 'to' );

	if ( false === $from || false === $to ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_date',
			'Date bounds must use YYYY-MM-DD or YYYY-MM-DD HH:MM.'
		);
	}

	if ( '' !== $from && '' !== $to && $from > $to ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_incoherent_combination',
			'The from date must not be later than the to date.'
		);
	}

	$page = absint( $args['page'] );

	if ( 1 > $page ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_page',
			'Page must be greater than or equal to 1.'
		);
	}

	$per_page = (int) $args['per_page'];

	if ( 1 > $per_page || 100 < $per_page ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_per_page',
			'Per page must be between 1 and 100.'
		);
	}

	return array(
		'event_id'          => $event_id,
		'type'              => $type,
		'type_keys'         => $type_keys,
		'status'            => $status,
		'pinned'            => $pinned,
		'include_cancelled' => $include_cancelled,
		'from'              => $from,
		'to'                => $to,
		'order'             => $order,
		'page'              => $page,
		'per_page'          => $per_page,
	);
}

function wp_seed_events_occurrence_collection_public_args( $args ) {
	return array(
		'event_id'          => $args['event_id'],
		'type'              => $args['type'],
		'status'            => $args['status'],
		'pinned'            => $args['pinned'],
		'include_cancelled' => $args['include_cancelled'],
		'from'              => $args['from'],
		'to'                => $args['to'],
		'order'             => $args['order'],
		'page'              => $args['page'],
		'per_page'          => $args['per_page'],
	);
}
function wp_seed_events_occurrence_collection_row_matches( $row, $args, $event_id ) {
	if ( ! $args['include_cancelled'] && ! empty( $row['is_cancelled'] ) ) {
		return false;
	}

	if ( null !== $args['event_id'] && $args['event_id'] !== absint( $event_id ) ) {
		return false;
	}

	if ( 'only' === $args['pinned'] && empty( $row['is_pinned'] ) ) {
		return false;
	}

	if ( array() !== $args['type_keys'] ) {
		$event_types = wp_seed_events_event_type_keys_for_event( $event_id );

		if ( array() === array_intersect( $args['type_keys'], $event_types ) ) {
			return false;
		}
	}

	$start_sort = (string) ( $row['start_sort'] ?? '' );
	$end_sort   = (string) ( $row['end_sort'] ?? $start_sort );
	$now        = current_time( 'Y-m-d H:i' );

	if ( 'upcoming' === $args['status'] && $end_sort < $now ) {
		return false;
	}

	if ( 'past' === $args['status'] && $end_sort >= $now ) {
		return false;
	}

	if ( '' !== $args['from'] && $end_sort < $args['from'] ) {
		return false;
	}

	if ( '' !== $args['to'] && $start_sort > $args['to'] ) {
		return false;
	}

	return true;
}

function wp_seed_events_occurrence_collection_compare_rows( $first, $second, $order ) {
	$first_pinned  = ! empty( $first['is_pinned'] );
	$second_pinned = ! empty( $second['is_pinned'] );

	if ( $first_pinned !== $second_pinned ) {
		return $first_pinned ? -1 : 1;
	}

	$comparison = strcmp( (string) $first['start_sort'], (string) $second['start_sort'] );

	if ( 0 !== $comparison ) {
		return 'chronological_desc' === $order ? -$comparison : $comparison;
	}

	$comparison = strcmp( (string) $first['end_sort'], (string) $second['end_sort'] );

	if ( 0 !== $comparison ) {
		return 'chronological_desc' === $order ? -$comparison : $comparison;
	}

	$comparison = absint( $first['event_id'] ) <=> absint( $second['event_id'] );

	return 0 !== $comparison ? $comparison : strcmp( (string) $first['occurrence_uid'], (string) $second['occurrence_uid'] );
}

function wp_seed_events_occurrence_collection_item( $row, $event ) {
	return array(
		'event_id'            => absint( $event->ID ),
		'event_title'         => sanitize_text_field( (string) $event->post_title ),
		'event_slug'          => sanitize_title( (string) $event->post_name ),
		'event_type'          => sanitize_key( (string) ( $row['event_type'] ?? '' ) ),
		'event_status'        => sanitize_key( (string) $event->post_status ),
		'is_pinned'           => ! empty( $row['is_pinned'] ),
		'occurrence_uid'      => sanitize_text_field( (string) ( $row['occurrence_uid'] ?? '' ) ),
		'occurrence_index'    => max( 0, (int) ( $row['occurrence_index'] ?? 0 ) ),
		'start'               => sanitize_text_field( (string) ( $row['start_raw'] ?? '' ) ),
		'end'                 => sanitize_text_field( (string) ( $row['end_raw'] ?? '' ) ),
		'start_sort'          => sanitize_text_field( (string) ( $row['start_sort'] ?? '' ) ),
		'end_sort'            => sanitize_text_field( (string) ( $row['end_sort'] ?? '' ) ),
		'is_cancelled'        => ! empty( $row['is_cancelled'] ),
	);
}

function wp_seed_events_occurrence_collection_result( $rows, $args, $total ) {
	$event_ids = array_values( array_unique( array_filter( array_map( 'absint', array_column( $rows, 'event_id' ) ) ) ) );
	$post_ids  = $event_ids;

	if ( function_exists( '_prime_post_caches' ) && array() !== $post_ids ) {
		_prime_post_caches( $post_ids, false, false );
	}

	if ( function_exists( 'update_meta_cache' ) && array() !== $post_ids ) {
		update_meta_cache( 'post', $post_ids );
	}

	$events = array();
	$items  = array();

	foreach ( $event_ids as $event_id ) {
		$event = get_post( $event_id );

		if ( $event instanceof WP_Post && 'wp_seed_event' === $event->post_type && 'publish' === $event->post_status ) {
			$events[ $event_id ] = $event;
		}
	}

	foreach ( $rows as $row ) {
		$event_id = absint( $row['event_id'] ?? 0 );

		if ( isset( $events[ $event_id ] ) ) {
			$items[] = wp_seed_events_occurrence_collection_item( $row, $events[ $event_id ] );
		}
	}

	$total_pages = max( 1, (int) ceil( $total / $args['per_page'] ) );

	return array(
		'items'        => $items,
		'page'         => $args['page'],
		'per_page'     => $args['per_page'],
		'total_items'  => absint( $total ),
		'total_pages'  => $total_pages,
		'has_previous' => 1 < $args['page'],
		'has_next'     => $args['page'] < $total_pages,
		'args'         => wp_seed_events_occurrence_collection_public_args( $args ),
	);
}
function wp_seed_events_query_fallback_occurrence_collection( $args ) {
	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$rows = array();

	foreach ( is_array( $event_ids ) ? $event_ids : array() as $event_id ) {
		$event_id   = absint( $event_id );
		$event_rows = wp_seed_events_get_occurrence_projection_rows( $event_id, false );

		if ( is_wp_error( $event_rows ) ) {
			return wp_seed_events_occurrence_collection_error(
				'wp_seed_events_occurrence_collection_fallback_failed',
				'Canonical occurrence fallback failed.',
				500
			);
		}

		foreach ( $event_rows as $row ) {
			if ( wp_seed_events_occurrence_collection_row_matches( $row, $args, $event_id ) ) {
				$rows[] = $row;
			}
		}
	}

	usort(
		$rows,
		static function ( $first, $second ) use ( $args ) {
			return wp_seed_events_occurrence_collection_compare_rows( $first, $second, $args['order'] );
		}
	);

	$total  = count( $rows );
	$offset = ( $args['page'] - 1 ) * $args['per_page'];
	$rows   = array_slice( $rows, $offset, $args['per_page'] );

	return wp_seed_events_occurrence_collection_result( $rows, $args, $total );
}

function wp_seed_events_occurrence_collection_sql_parts( $args ) {
	global $wpdb;

	$where  = array(
		'event_posts.post_type = %s',
		'event_posts.post_status = %s',
		'projection.event_status = %s',
	);
	$params = array( 'wp_seed_event', 'publish', 'publish' );

	if ( ! $args['include_cancelled'] ) {
		$where[] = 'projection.is_cancelled = 0';
	}

	if ( null !== $args['event_id'] ) {
		$where[]  = 'projection.event_id = %d';
		$params[] = $args['event_id'];
	}

	if ( 'only' === $args['pinned'] ) {
		$where[] = 'projection.is_pinned = 1';
	}

	if ( array() !== $args['type_keys'] ) {
		$placeholders = implode( ', ', array_fill( 0, count( $args['type_keys'] ), '%s' ) );
		$where[]      = "EXISTS (
			SELECT 1 FROM {$wpdb->postmeta} type_meta
			WHERE type_meta.post_id = projection.event_id
				AND type_meta.meta_key = '_wp_seed_event_collection_type'
				AND type_meta.meta_value IN ({$placeholders})
		)";
		$params       = array_merge( $params, $args['type_keys'] );
	}

	$now = current_time( 'Y-m-d H:i' );

	if ( 'upcoming' === $args['status'] ) {
		$where[]  = 'projection.end_sort >= %s';
		$params[] = $now;
	} elseif ( 'past' === $args['status'] ) {
		$where[]  = 'projection.end_sort < %s';
		$params[] = $now;
	}

	if ( '' !== $args['from'] ) {
		$where[]  = 'projection.end_sort >= %s';
		$params[] = $args['from'];
	}

	if ( '' !== $args['to'] ) {
		$where[]  = 'projection.start_sort <= %s';
		$params[] = $args['to'];
	}

	return array( 'where' => implode( ' AND ', $where ), 'params' => $params );
}

/**
 * Query the lifecycle projection with SQL-side filtering and pagination.
 *
 * @param array $args Normalized collection arguments.
 * @return array|WP_Error
 */
function wp_seed_events_query_indexed_occurrence_collection( $args ) {
	global $wpdb;

	if ( ! wp_seed_events_occurrence_projection_table_exists() ) {
		return wp_seed_events_occurrence_collection_error(
			'index_unavailable',
			'Occurrence projection is unavailable.'
		);
	}

	$table_name = wp_seed_events_occurrence_projection_table_name();
	$sql_parts  = wp_seed_events_occurrence_collection_sql_parts( $args );
	$direction  = 'chronological_desc' === $args['order'] ? 'DESC' : 'ASC';
	$offset     = ( $args['page'] - 1 ) * $args['per_page'];

	$wpdb->last_error = '';
	$count_sql        = "SELECT COUNT(*)
		FROM {$table_name} projection
		INNER JOIN {$wpdb->posts} event_posts ON event_posts.ID = projection.event_id
		WHERE {$sql_parts['where']}";
	$total            = absint( $wpdb->get_var( $wpdb->prepare( $count_sql, $sql_parts['params'] ) ) );

	if ( '' !== $wpdb->last_error ) {
		return wp_seed_events_occurrence_collection_error(
			'index_query_failed',
			'Occurrence projection could not be queried.'
		);
	}

	$row_params = array_merge( $sql_parts['params'], array( $args['per_page'], $offset ) );
	$rows_sql   = "SELECT projection.event_id, projection.occurrence_uid,
			projection.occurrence_index, projection.start_raw, projection.end_raw,
			projection.start_sort, projection.end_sort, projection.is_cancelled,
			projection.event_type, projection.event_status, projection.is_pinned
		FROM {$table_name} projection
		INNER JOIN {$wpdb->posts} event_posts ON event_posts.ID = projection.event_id
		WHERE {$sql_parts['where']}
		ORDER BY projection.is_pinned DESC,
			projection.start_sort {$direction},
			projection.end_sort {$direction},
			projection.event_id ASC,
			projection.occurrence_uid ASC
		LIMIT %d OFFSET %d";
	$rows       = $wpdb->get_results( $wpdb->prepare( $rows_sql, $row_params ), ARRAY_A );

	if ( '' !== $wpdb->last_error || ! is_array( $rows ) ) {
		return wp_seed_events_occurrence_collection_error(
			'index_query_failed',
			'Occurrence projection could not be queried.'
		);
	}

	return wp_seed_events_occurrence_collection_result( $rows, $args, $total );
}

/**
 * Query the canonical public occurrence collection.
 *
 * @param array $args Public collection arguments.
 * @return array|WP_Error
 */
function wp_seed_events_query_occurrence_collection( $args = array() ) {
	$args = wp_seed_events_occurrence_collection_normalize_args( $args );

	if ( is_wp_error( $args ) ) {
		return $args;
	}

	if (
		function_exists( 'wp_seed_events_is_lifecycle_index_ready' )
		&& wp_seed_events_is_lifecycle_index_ready()
		&& wp_seed_events_occurrence_projection_table_exists()
	) {
		$indexed = wp_seed_events_query_indexed_occurrence_collection( $args );

		if ( ! is_wp_error( $indexed ) ) {
			return $indexed;
		}
	}

	return wp_seed_events_query_fallback_occurrence_collection( $args );
}
function wp_seed_events_occurrence_collection_rest_args() {
	$args = array(
		'event_id'          => array( 'type' => 'integer', 'minimum' => 1 ),
		'type'              => array( 'type' => 'string' ),
		'status'            => array( 'type' => 'string', 'default' => 'upcoming', 'enum' => array( 'upcoming', 'past', 'all' ) ),
		'pinned'            => array( 'type' => array( 'boolean', 'string' ), 'default' => 'all' ),
		'include_cancelled' => array( 'type' => 'boolean', 'default' => false ),
		'from'              => array( 'type' => 'string' ),
		'to'                => array( 'type' => 'string' ),
	);

	$args['order']    = array( 'type' => 'string', 'default' => 'upcoming', 'enum' => array( 'upcoming', 'chronological', 'chronological_desc' ) );
	$args['page']     = array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 );
	$args['per_page'] = array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 );

	return $args;
}

function wp_seed_events_occurrence_collection_rest_item_schema() {
	return array(
		'type'       => 'object',
		'properties' => array(
			'event_id'           => array( 'type' => 'integer' ),
			'event_title'        => array( 'type' => 'string' ),
			'event_slug'         => array( 'type' => 'string' ),
			'event_type'         => array( 'type' => 'string' ),
			'event_status'       => array( 'type' => 'string' ),
			'is_pinned'          => array( 'type' => 'boolean' ),
			'occurrence_uid'     => array( 'type' => 'string' ),
			'occurrence_index'   => array( 'type' => 'integer' ),
			'start'              => array( 'type' => 'string' ),
			'end'                => array( 'type' => 'string' ),
			'start_sort'         => array( 'type' => 'string' ),
			'end_sort'           => array( 'type' => 'string' ),
			'is_cancelled'       => array( 'type' => 'boolean' ),
		),
	);
}
function wp_seed_events_occurrence_collection_rest_schema() {
	return array(
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'wp_seed_event_occurrence_collection',
		'type'       => 'object',
		'properties' => array(
			'items'        => array( 'type' => 'array', 'items' => wp_seed_events_occurrence_collection_rest_item_schema() ),
			'page'         => array( 'type' => 'integer' ),
			'per_page'     => array( 'type' => 'integer' ),
			'total_items'  => array( 'type' => 'integer' ),
			'total_pages'  => array( 'type' => 'integer' ),
			'has_previous' => array( 'type' => 'boolean' ),
			'has_next'     => array( 'type' => 'boolean' ),
			'args'         => array( 'type' => 'object' ),
		),
	);
}

/** Register public read-only occurrence collection routes. */
function wp_seed_events_register_occurrence_collection_rest_routes() {
	register_rest_route(
		'wp-seed-events/v1',
		'/occurrences',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'wp_seed_events_rest_get_occurrence_collection',
				'permission_callback' => '__return_true',
				'args'                => wp_seed_events_occurrence_collection_rest_args(),
			),
			'schema' => 'wp_seed_events_occurrence_collection_rest_schema',
		)
	);
}

/**
 * REST callback for the flat occurrence collection.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function wp_seed_events_rest_get_occurrence_collection( $request ) {
	$result = wp_seed_events_query_occurrence_collection( $request->get_params() );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$response = rest_ensure_response( $result );
	$response->header( 'X-WP-Total', (int) $result['total_items'] );
	$response->header( 'X-WP-TotalPages', (int) $result['total_pages'] );

	return $response;
}
