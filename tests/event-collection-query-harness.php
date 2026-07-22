<?php
/**
 * Standalone assertions for canonical public event collections.
 *
 * Run with: php tests/event-collection-query-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['collection_events'] = array();
$GLOBALS['collection_types']  = array();
$GLOBALS['collection_pinned'] = array();
$GLOBALS['collection_cases']  = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_title( $value ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function get_posts() {
	return array_keys( $GLOBALS['collection_events'] );
}

function get_post_meta( $event_id, $key ) {
	return '_wp_seed_event_pinned' === $key && ! empty( $GLOBALS['collection_pinned'][ absint( $event_id ) ] ) ? '1' : '';
}

function wp_seed_events_get_event_data( $event_id ) {
	return $GLOBALS['collection_events'][ absint( $event_id ) ] ?? array();
}

function wp_seed_events_event_type_keys_for_event( $event_id ) {
	return $GLOBALS['collection_types'][ absint( $event_id ) ] ?? array();
}

function wp_seed_events_event_type_public_slug( $type ) {
	return sanitize_title( $type );
}

require dirname( __DIR__ ) . '/includes/public/collections.php';

function collection_event( $id, $lifecycle, $date = '', $type = 'atelier', $pinned = false ) {
	$event = array(
		'id'              => $id,
		'title'           => 'Event ' . $id,
		'lifecycle'       => $lifecycle,
		'next_occurrence' => array(),
		'last_occurrence' => array(),
	);

	if ( 'upcoming' === $lifecycle && '' !== $date ) {
		$event['next_occurrence'] = array( 'start_sort' => $date );
	}

	if ( 'past' === $lifecycle && '' !== $date ) {
		$event['last_occurrence'] = array( 'start_sort' => $date );
	}

	$GLOBALS['collection_events'][ $id ] = $event;
	$GLOBALS['collection_types'][ $id ]  = array( $type );

	if ( $pinned ) {
		$GLOBALS['collection_pinned'][ $id ] = true;
	}
}

function collection_ids( $args ) {
	$args['per_page'] = $args['per_page'] ?? -1;

	return wp_seed_events_query_event_collection( $args )['ids'];
}

function collection_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function collection_case( $label, $callback ) {
	$GLOBALS['collection_cases']++;
	$callback();
	echo 'ok ' . $GLOBALS['collection_cases'] . ' - ' . $label . PHP_EOL;
}

collection_event( 101, 'upcoming', '2026-08-01 10:00' );
collection_event( 102, 'upcoming', '2026-07-25 10:00' ); // Multiple active occurrences; this is the canonical next one.
collection_event( 103, 'past', '2026-01-01 10:00' );
collection_event( 104, 'undated' );
collection_event( 105, 'upcoming', '2026-09-01 10:00' ); // Cancelled occurrence plus this active occurrence.
collection_event( 106, 'cancelled_only' );
collection_event( 107, 'upcoming', '2026-10-01 10:00', 'atelier', true );
collection_event( 108, 'upcoming', '2026-07-20 10:00', 'conference' );
collection_event( 109, 'upcoming', '2026-08-01 10:00' );
collection_event( 110, 'upcoming', '2026-07-30 10:00' );

collection_case( 'type only', function () {
	collection_assert( ! in_array( 108, collection_ids( array( 'type' => 'atelier', 'status' => 'all' ) ), true ), 'Other type leaked.' );
} );

collection_case( 'upcoming', function () {
	collection_assert( array( 107, 102, 110, 101, 109, 105 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming' ) ), 'Upcoming order differs.' );
} );

collection_case( 'past', function () {
	collection_assert( array( 103 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'past' ) ), 'Past selection differs.' );
} );

collection_case( 'all', function () {
	collection_assert( array( 107, 103, 102, 110, 101, 109, 105, 104, 106 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'all' ) ), 'All selection differs.' );
} );

collection_case( 'type plus upcoming', function () {
	collection_assert( 6 === count( collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming' ) ) ), 'Type and upcoming were not combined.' );
} );

collection_case( 'type plus past', function () {
	collection_assert( array( 103 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'past' ) ), 'Type and past were not combined.' );
} );

collection_case( 'type plus all', function () {
	collection_assert( 9 === count( collection_ids( array( 'type' => 'atelier', 'status' => 'all' ) ) ), 'Type and all were not combined.' );
} );

collection_case( 'pinned only', function () {
	collection_assert( array( 107 ) === collection_ids( array( 'status' => 'all', 'pinned' => 'only' ) ), 'Pinned-only selection differs.' );
} );

collection_case( 'pinned priority then date', function () {
	$ids = collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming' ) );
	collection_assert( 107 === $ids[0] && 102 === $ids[1], 'Pinned priority or chronological order differs.' );
} );

collection_case( 'ascending order', function () {
	collection_assert( array( 107, 102, 110, 101, 109, 105 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming', 'order' => 'asc' ) ), 'Ascending order differs.' );
} );

collection_case( 'descending order', function () {
	collection_assert( array( 107, 105, 101, 109, 110, 102 ) === collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming', 'order' => 'desc' ) ), 'Descending order differs.' );
} );

collection_case( 'undated and cancelled-only remain last', function () {
	$ids = collection_ids( array( 'type' => 'atelier', 'status' => 'all', 'order' => 'desc' ) );
	collection_assert( array( 104, 106 ) === array_slice( $ids, -2 ), 'No-date events are not last.' );
} );

collection_case( 'cancelled occurrences do not define upcoming', function () {
	$ids = collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming' ) );
	collection_assert( in_array( 105, $ids, true ) && ! in_array( 106, $ids, true ), 'Cancellation rules differ.' );
} );

collection_case( 'equal dates use stable IDs', function () {
	$ids = collection_ids( array( 'type' => 'atelier', 'status' => 'upcoming' ) );
	collection_assert( array_search( 101, $ids, true ) < array_search( 109, $ids, true ), 'Equal-date order is unstable.' );
} );

collection_case( 'pagination has no duplicates or gaps', function () {
	$base  = array( 'type' => 'atelier', 'status' => 'upcoming', 'per_page' => 2 );
	$page1 = wp_seed_events_query_event_collection( $base + array( 'page' => 1 ) );
	$page2 = wp_seed_events_query_event_collection( $base + array( 'page' => 2 ) );
	$page3 = wp_seed_events_query_event_collection( $base + array( 'page' => 3 ) );
	$ids   = array_merge( $page1['ids'], $page2['ids'], $page3['ids'] );
	collection_assert( 6 === $page1['total'] && 3 === $page1['total_pages'], 'Pagination totals differ.' );
	collection_assert( 6 === count( array_unique( $ids ) ) && array( 107, 102, 110, 101, 109, 105 ) === $ids, 'Pagination duplicated or omitted events.' );
} );

collection_case( 'legacy defaults remain stable', function () {
	collection_assert( 'ASC' === wp_seed_events_public_collection_order( '', 'upcoming' ), 'Upcoming default changed.' );
	collection_assert( 'DESC' === wp_seed_events_public_collection_order( '', 'past' ), 'Past default changed.' );
} );

collection_case( 'legacy accessor delegates to the canonical contract', function () {
	$events = wp_seed_events_get_event_collection(
		array(
			'type'   => 'atelier',
			'status' => 'upcoming',
			'order'  => 'desc',
			'limit'  => 2,
		)
	);
	collection_assert( array( 107, 105 ) === array_column( $events, 'id' ), 'Legacy accessor diverged from the canonical contract.' );
} );

collection_case( 'shortcode exposes order and delegates selection', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
	collection_assert( false !== strpos( $source, "'order'  => ''" ), 'Shortcode order attribute is absent.' );
	collection_assert( false !== strpos( $source, 'wp_seed_events_get_event_collection( $atts )' ), 'Shortcode bypasses the canonical accessor.' );
} );

collection_case( 'public lifecycle labels', function () {
	collection_assert( 'À venir' === wp_seed_events_public_event_status_label( 'upcoming' ), 'Upcoming label differs.' );
	collection_assert( 'Passé' === wp_seed_events_public_event_status_label( 'past' ), 'Past label differs.' );
	collection_assert( 'Sans date' === wp_seed_events_public_event_status_label( 'undated' ), 'Undated label differs.' );
	collection_assert( 'Annulé' === wp_seed_events_public_event_status_label( 'cancelled_only' ), 'Cancelled label differs.' );
} );

collection_case( 'canonical bridge preserves builder pagination and exclusions', function () {
	$query = wp_seed_events_apply_collection_to_query_args(
		array(
			'posts_per_page' => 2,
			'paged'          => 2,
			'post__not_in'   => array( 102 ),
		),
		array(
			'type'   => 'atelier',
			'status' => 'upcoming',
			'order'  => 'asc',
		)
	);

	collection_assert( 2 === $query['posts_per_page'] && 2 === $query['paged'], 'Builder pagination changed.' );
	collection_assert( ! in_array( 102, $query['post__in'], true ), 'Excluded event was restored.' );
	collection_assert( 'post__in' === $query['orderby'], 'Canonical ID order is not preserved.' );
} );

collection_case( 'canonical bridge fails closed on an empty selection', function () {
	$query = wp_seed_events_apply_collection_to_query_args(
		array(),
		array(
			'type'   => 'missing-type',
			'status' => 'upcoming',
		)
	);

	collection_assert( array( 0 ) === $query['post__in'], 'Empty selection did not fail closed.' );
} );

collection_case( 'significant volume remains deterministic', function () {
	$events = $GLOBALS['collection_events'];
	$types  = $GLOBALS['collection_types'];
	$pinned = $GLOBALS['collection_pinned'];

	for ( $index = 0; $index < 500; $index++ ) {
		collection_event(
			1000 + $index,
			'upcoming',
			'2027-01-' . str_pad( (string) ( 1 + ( $index % 28 ) ), 2, '0', STR_PAD_LEFT ) . ' 10:00',
			'volume'
		);
	}

	$started = microtime( true );
	$first   = wp_seed_events_query_event_collection(
		array( 'type' => 'volume', 'status' => 'upcoming', 'per_page' => 25, 'page' => 4 )
	);
	$second  = wp_seed_events_query_event_collection(
		array( 'type' => 'volume', 'status' => 'upcoming', 'per_page' => 25, 'page' => 4 )
	);
	$elapsed = microtime( true ) - $started;

	$GLOBALS['collection_events'] = $events;
	$GLOBALS['collection_types']  = $types;
	$GLOBALS['collection_pinned'] = $pinned;

	collection_assert( 500 === $first['total'] && 20 === $first['total_pages'], 'Volume totals differ.' );
	collection_assert( 25 === count( $first['ids'] ), 'Volume page size differs.' );
	collection_assert( $first['ids'] === $second['ids'], 'Volume order is not deterministic.' );
	collection_assert( $elapsed < 2.0, 'Volume harness exceeded its generous local guard.' );
} );

echo 'Event collection query harness: ' . $GLOBALS['collection_cases'] . '/' . $GLOBALS['collection_cases'] . ' OK' . PHP_EOL;
