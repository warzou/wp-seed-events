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

function wp_seed_events_occurrence_collection_promotion_id( $args ) {
	$selectors = array();

	if ( array_key_exists( 'promotion', $args ) && ! in_array( $args['promotion'], array( '', null, 0, '0' ), true ) ) {
		$selectors[] = $args['promotion'];
	}

	if ( array_key_exists( 'promotion_id', $args ) && ! in_array( $args['promotion_id'], array( '', null, 0, '0' ), true ) ) {
		$selectors[] = absint( $args['promotion_id'] );
	}

	if ( array_key_exists( 'promotion_slug', $args ) && '' !== trim( (string) $args['promotion_slug'] ) ) {
		$selectors[] = sanitize_title( (string) $args['promotion_slug'] );
	}

	if ( array() === $selectors ) {
		return 0;
	}

	$promotion_ids = array();

	foreach ( $selectors as $selector ) {
		$promotion = wp_seed_events_get_promotion( $selector );

		if ( array() === $promotion ) {
			return wp_seed_events_occurrence_collection_error(
				'wp_seed_events_occurrence_collection_invalid_promotion',
				'The requested Promotion does not exist.',
				404
			);
		}

		$promotion_ids[] = absint( $promotion['id'] );
	}

	$promotion_ids = array_values( array_unique( $promotion_ids ) );

	if ( 1 !== count( $promotion_ids ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_conflicting_promotion',
			'Promotion selectors must identify the same Promotion.'
		);
	}

	return reset( $promotion_ids );
}
function wp_seed_events_occurrence_collection_normalize_args( $raw_args = array() ) {
	$raw_args = is_array( $raw_args ) ? $raw_args : array();
	$args     = wp_parse_args(
		$raw_args,
		array(
			'promotion'         => '',
			'promotion_id'      => 0,
			'promotion_slug'    => '',
			'parcours_year'     => null,
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
			'require_promotion' => false,
		)
	);

	$promotion_id = wp_seed_events_occurrence_collection_promotion_id( $raw_args );

	if ( is_wp_error( $promotion_id ) ) {
		return $promotion_id;
	}

	$parcours_year = null;

	if ( array_key_exists( 'parcours_year', $raw_args ) && ! in_array( $raw_args['parcours_year'], array( '', null ), true ) ) {
		$parcours_year = wp_seed_events_normalize_parcours_year( $raw_args['parcours_year'] );

		if ( 0 === $parcours_year ) {
			return wp_seed_events_occurrence_collection_error(
				'wp_seed_events_occurrence_collection_invalid_parcours_year',
				'Parcours year must be between 1 and 4.'
			);
		}
	}

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

	if ( ! in_array( $order, array( 'upcoming', 'chronological', 'chronological_desc', 'canonical_path' ), true ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_order',
			'Unknown occurrence collection order.'
		);
	}

	if ( 'canonical_path' === $order ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_incoherent_combination',
			'Canonical path order is reserved for grouped collections.'
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

	$promotion = 0 < $promotion_id ? wp_seed_events_get_promotion( $promotion_id ) : array();

	return array(
		'promotion_id'      => $promotion_id,
		'promotion_slug'    => $promotion['slug'] ?? '',
		'parcours_year'     => $parcours_year,
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
		'require_promotion' => ! empty( $args['require_promotion'] ),
	);
}

function wp_seed_events_occurrence_collection_public_args( $args ) {
	return array(
		'promotion_id'      => $args['promotion_id'],
		'promotion_slug'    => $args['promotion_slug'],
		'parcours_year'     => $args['parcours_year'],
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

	if ( $args['require_promotion'] && 0 === absint( $row['promotion_id'] ?? 0 ) ) {
		return false;
	}

	if ( 0 < $args['promotion_id'] && $args['promotion_id'] !== absint( $row['promotion_id'] ?? 0 ) ) {
		return false;
	}

	if ( null !== $args['parcours_year'] && $args['parcours_year'] !== absint( $row['parcours_year'] ?? 0 ) ) {
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
	$today      = current_time( 'Y-m-d' ) . ' 00:00';

	if ( 'upcoming' === $args['status'] && $start_sort < $today ) {
		return false;
	}

	if ( 'past' === $args['status'] && $start_sort >= $today ) {
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
	$promotion_id = absint( $row['promotion_id'] ?? 0 );
	$promotion    = 0 < $promotion_id ? wp_seed_events_get_promotion( $promotion_id ) : array();
	$year         = wp_seed_events_normalize_parcours_year( $row['parcours_year'] ?? 0 );

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
		'promotion_id'        => $promotion_id,
		'promotion'           => $promotion,
		'parcours_year'       => $year,
		'parcours_year_label' => wp_seed_events_parcours_year_label( $year ),
	);
}

function wp_seed_events_occurrence_collection_result( $rows, $args, $total ) {
	$event_ids     = array_values( array_unique( array_filter( array_map( 'absint', array_column( $rows, 'event_id' ) ) ) ) );
	$promotion_ids = array_values( array_unique( array_filter( array_map( 'absint', array_column( $rows, 'promotion_id' ) ) ) ) );
	$post_ids      = array_values( array_unique( array_merge( $event_ids, $promotion_ids ) ) );

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

	if ( $args['require_promotion'] ) {
		$where[] = 'projection.promotion_id > 0';
	}

	if ( 0 < $args['promotion_id'] ) {
		$where[]  = 'projection.promotion_id = %d';
		$params[] = $args['promotion_id'];
	}

	if ( null !== $args['parcours_year'] ) {
		$where[]  = 'projection.parcours_year = %d';
		$params[] = $args['parcours_year'];
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

	$today = current_time( 'Y-m-d' ) . ' 00:00';

	if ( 'upcoming' === $args['status'] ) {
		$where[]  = 'projection.start_sort >= %s';
		$params[] = $today;
	} elseif ( 'past' === $args['status'] ) {
		$where[]  = 'projection.start_sort < %s';
		$params[] = $today;
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
			projection.occurrence_index, projection.promotion_id,
			projection.parcours_year, projection.start_raw, projection.end_raw,
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
function wp_seed_events_occurrence_grouped_collection_normalize_args( $raw_args ) {
	$raw_args = is_array( $raw_args ) ? $raw_args : array();

	if ( array_key_exists( 'page', $raw_args ) || array_key_exists( 'per_page', $raw_args ) ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_incoherent_combination',
			'Grouped collections use a bounded global limit and do not support pagination.'
		);
	}

	$order = isset( $raw_args['order'] ) ? strtolower( trim( (string) $raw_args['order'] ) ) : 'canonical_path';

	if ( 'canonical_path' !== $order ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_order',
			'Grouped collections require canonical_path order.'
		);
	}

	$limit = isset( $raw_args['limit'] ) ? (int) $raw_args['limit'] : 200;

	if ( 1 > $limit || 500 < $limit ) {
		return wp_seed_events_occurrence_collection_error(
			'wp_seed_events_occurrence_collection_invalid_limit',
			'Grouped collection limit must be between 1 and 500.'
		);
	}

	unset( $raw_args['limit'], $raw_args['order'] );
	$raw_args['order']             = 'chronological';
	$raw_args['page']              = 1;
	$raw_args['per_page']          = min( 100, $limit );
	$raw_args['require_promotion'] = true;
	$query_args                    = wp_seed_events_occurrence_collection_normalize_args( $raw_args );

	if ( is_wp_error( $query_args ) ) {
		return $query_args;
	}

	return array(
		'query_args' => $query_args,
		'limit'      => $limit,
	);
}

function wp_seed_events_occurrence_group_stats( $occurrences ) {
	$start_values = array_column( $occurrences, 'start_sort' );
	$end_values   = array_column( $occurrences, 'end_sort' );

	sort( $start_values, SORT_STRING );
	rsort( $end_values, SORT_STRING );

	return array(
		'count'            => count( $occurrences ),
		'first_start_sort' => (string) ( reset( $start_values ) ?: '' ),
		'last_end_sort'    => (string) ( reset( $end_values ) ?: '' ),
	);
}

function wp_seed_events_occurrence_grouped_promotion_compare( $first, $second ) {
	$result = (int) $first['promotion']['order'] <=> (int) $second['promotion']['order'];

	if ( 0 === $result ) {
		$result = (int) $first['promotion']['start_year'] <=> (int) $second['promotion']['start_year'];
	}

	if ( 0 === $result ) {
		$result = strnatcasecmp( (string) $first['promotion']['name'], (string) $second['promotion']['name'] );
	}

	return 0 === $result
		? (int) $first['promotion']['id'] <=> (int) $second['promotion']['id']
		: $result;
}

function wp_seed_events_occurrence_grouped_theme_compare( $first, $second ) {
	$result = strcmp( (string) $first['first_start_sort'], (string) $second['first_start_sort'] );

	if ( 0 === $result ) {
		$result = (int) $second['event']['is_pinned'] <=> (int) $first['event']['is_pinned'];
	}

	if ( 0 === $result ) {
		$result = strnatcasecmp( (string) $first['event']['title'], (string) $second['event']['title'] );
	}

	return 0 === $result
		? (int) $first['event']['id'] <=> (int) $second['event']['id']
		: $result;
}

/**
 * Query occurrences grouped by Promotion, parcours year and event/theme.
 *
 * Grouped V1 deliberately uses one bounded global limit rather than ambiguous
 * pagination across nested levels.
 *
 * @param array $args Public grouped collection arguments.
 * @return array|WP_Error
 */
function wp_seed_events_query_grouped_occurrence_collection( $args = array() ) {
	$normalized = wp_seed_events_occurrence_grouped_collection_normalize_args( $args );

	if ( is_wp_error( $normalized ) ) {
		return $normalized;
	}

	$query_args = $normalized['query_args'];
	$limit      = $normalized['limit'];
	$flat_args  = wp_seed_events_occurrence_collection_public_args( $query_args );
	$flat_args['require_promotion'] = true;
	$flat_args['per_page']          = min( 100, $limit );
	$flat_args['page']              = 1;
	$items                          = array();
	$total_items                    = 0;

	do {
		$page = wp_seed_events_query_occurrence_collection( $flat_args );

		if ( is_wp_error( $page ) ) {
			return $page;
		}

		$total_items = $page['total_items'];
		$items       = array_merge( $items, $page['items'] );

		if ( count( $items ) >= $limit || ! $page['has_next'] ) {
			break;
		}

		++$flat_args['page'];
	} while ( true );

	$items = array_slice( $items, 0, $limit );
	$tree  = array();

	foreach ( $items as $item ) {
		$promotion_id = absint( $item['promotion_id'] );
		$year         = absint( $item['parcours_year'] );
		$event_id     = absint( $item['event_id'] );

		if ( 0 === $promotion_id || 0 === $year ) {
			continue;
		}

		if ( ! isset( $tree[ $promotion_id ] ) ) {
			$tree[ $promotion_id ] = array(
				'promotion'  => $item['promotion'],
				'years'      => array(),
				'occurrences'=> array(),
			);
		}

		if ( ! isset( $tree[ $promotion_id ]['years'][ $year ] ) ) {
			$tree[ $promotion_id ]['years'][ $year ] = array(
				'parcours_year'       => $year,
				'parcours_year_label' => $item['parcours_year_label'],
				'themes'              => array(),
				'occurrences'         => array(),
			);
		}

		if ( ! isset( $tree[ $promotion_id ]['years'][ $year ]['themes'][ $event_id ] ) ) {
			$tree[ $promotion_id ]['years'][ $year ]['themes'][ $event_id ] = array(
				'event'       => array(
					'id'        => $event_id,
					'title'     => $item['event_title'],
					'slug'      => $item['event_slug'],
					'type'      => $item['event_type'],
					'status'    => $item['event_status'],
					'is_pinned' => $item['is_pinned'],
				),
				'occurrences' => array(),
			);
		}

		$tree[ $promotion_id ]['years'][ $year ]['themes'][ $event_id ]['occurrences'][] = $item;
		$tree[ $promotion_id ]['years'][ $year ]['occurrences'][]                         = $item;
		$tree[ $promotion_id ]['occurrences'][]                                            = $item;
	}
	$promotions = array();

	foreach ( $tree as $promotion_group ) {
		$years = array();

		foreach ( $promotion_group['years'] as $year_group ) {
			$themes = array();

			foreach ( $year_group['themes'] as $theme ) {
				usort(
					$theme['occurrences'],
					static function ( $first, $second ) {
						return wp_seed_events_occurrence_collection_compare_rows( $first, $second, 'chronological' );
					}
				);
				$theme = array_merge( $theme, wp_seed_events_occurrence_group_stats( $theme['occurrences'] ) );
				$themes[] = $theme;
			}

			usort( $themes, 'wp_seed_events_occurrence_grouped_theme_compare' );
			$year_group['themes'] = $themes;
			$year_group           = array_merge(
				$year_group,
				wp_seed_events_occurrence_group_stats( $year_group['occurrences'] )
			);
			unset( $year_group['occurrences'] );
			$years[] = $year_group;
		}

		usort(
			$years,
			function ( $first, $second ) {
				return (int) $first['parcours_year'] <=> (int) $second['parcours_year'];
			}
		);
		$promotion_group['years'] = $years;
		$promotion_group          = array_merge(
			$promotion_group,
			wp_seed_events_occurrence_group_stats( $promotion_group['occurrences'] )
		);
		unset( $promotion_group['occurrences'] );
		$promotions[] = $promotion_group;
	}

	usort( $promotions, 'wp_seed_events_occurrence_grouped_promotion_compare' );
	$public_args          = wp_seed_events_occurrence_collection_public_args( $query_args );
	$public_args['order'] = 'canonical_path';
	unset( $public_args['page'], $public_args['per_page'] );

	return array(
		'promotions'    => $promotions,
		'total_items'   => $total_items,
		'returned_items'=> count( $items ),
		'limit'          => $limit,
		'is_limited'     => $total_items > count( $items ),
		'args'           => $public_args,
	);
}

function wp_seed_events_occurrence_collection_rest_args( $grouped = false ) {
	$args = array(
		'promotion'         => array( 'type' => array( 'integer', 'string' ) ),
		'promotion_id'      => array( 'type' => 'integer', 'minimum' => 1 ),
		'promotion_slug'    => array( 'type' => 'string' ),
		'parcours_year'     => array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 4 ),
		'event_id'          => array( 'type' => 'integer', 'minimum' => 1 ),
		'type'              => array( 'type' => 'string' ),
		'status'            => array( 'type' => 'string', 'default' => 'upcoming', 'enum' => array( 'upcoming', 'past', 'all' ) ),
		'pinned'            => array( 'type' => array( 'boolean', 'string' ), 'default' => 'all' ),
		'include_cancelled' => array( 'type' => 'boolean', 'default' => false ),
		'from'              => array( 'type' => 'string' ),
		'to'                => array( 'type' => 'string' ),
	);

	if ( $grouped ) {
		$args['order'] = array( 'type' => 'string', 'default' => 'canonical_path', 'enum' => array( 'canonical_path' ) );
		$args['limit'] = array( 'type' => 'integer', 'default' => 200, 'minimum' => 1, 'maximum' => 500 );
	} else {
		$args['order']    = array( 'type' => 'string', 'default' => 'upcoming', 'enum' => array( 'upcoming', 'chronological', 'chronological_desc' ) );
		$args['page']     = array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 );
		$args['per_page'] = array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 );
	}

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
			'promotion_id'       => array( 'type' => 'integer' ),
			'promotion'          => array( 'type' => array( 'object', 'array' ) ),
			'parcours_year'      => array( 'type' => 'integer' ),
			'parcours_year_label'=> array( 'type' => 'string' ),
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

function wp_seed_events_occurrence_grouped_collection_rest_schema() {
	return array(
		'$schema'    => 'http://json-schema.org/draft-04/schema#',
		'title'      => 'wp_seed_event_occurrence_grouped_collection',
		'type'       => 'object',
		'properties' => array(
			'promotions'     => array( 'type' => 'array' ),
			'total_items'    => array( 'type' => 'integer' ),
			'returned_items' => array( 'type' => 'integer' ),
			'limit'          => array( 'type' => 'integer' ),
			'is_limited'     => array( 'type' => 'boolean' ),
			'args'           => array( 'type' => 'object' ),
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

	register_rest_route(
		'wp-seed-events/v1',
		'/occurrences/grouped',
		array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => 'wp_seed_events_rest_get_grouped_occurrence_collection',
				'permission_callback' => '__return_true',
				'args'                => wp_seed_events_occurrence_collection_rest_args( true ),
			),
			'schema' => 'wp_seed_events_occurrence_grouped_collection_rest_schema',
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

/**
 * REST callback for the canonical grouped occurrence collection.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function wp_seed_events_rest_get_grouped_occurrence_collection( $request ) {
	$result = wp_seed_events_query_grouped_occurrence_collection( $request->get_params() );

	return is_wp_error( $result ) ? $result : rest_ensure_response( $result );
}