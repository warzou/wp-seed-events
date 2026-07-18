<?php
/**
 * Standalone D0 assertions for Dynamic Data caching and Gutenberg contexts.
 *
 * Run with: php tests/dynamic-data-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['d0_events']      = array();
$GLOBALS['d0_types']       = array();
$GLOBALS['d0_statuses']    = array();
$GLOBALS['d0_data_calls']  = 0;
$GLOBALS['d0_occ_calls']   = 0;
$GLOBALS['d0_current_id']  = 0;
$GLOBALS['d0_case_count']  = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}

function strip_shortcodes( $value ) {
	return preg_replace( '/\[[^\]]+\]/', '', (string) $value );
}

function get_the_ID() {
	return (int) $GLOBALS['d0_current_id'];
}

function get_post_type( $post_id = 0 ) {
	return $GLOBALS['d0_types'][ absint( $post_id ) ] ?? false;
}

function get_post_status( $post_id = 0 ) {
	return $GLOBALS['d0_statuses'][ absint( $post_id ) ] ?? false;
}

function add_action() {
}

function register_block_bindings_source() {
}

function wp_json_encode( $value, $flags = 0 ) {
	return json_encode( $value, $flags );
}

function wp_seed_events_d0_occurrence_pass( $event_id ) {
	$GLOBALS['d0_occ_calls']++;
	$checksum = 0;

	for ( $index = 0; $index < 24; $index++ ) {
		$checksum ^= crc32( (string) $event_id . ':' . (string) $index );
	}

	return $checksum;
}

function wp_seed_events_get_event_data( $event_id ) {
	$event_id = absint( $event_id );
	$GLOBALS['d0_data_calls']++;

	for ( $pass = 0; $pass < 5; $pass++ ) {
		wp_seed_events_d0_occurrence_pass( $event_id );
	}

	if ( 'wp_seed_event' !== get_post_type( $event_id ) || 'publish' !== get_post_status( $event_id ) ) {
		return array();
	}

	return $GLOBALS['d0_events'][ $event_id ] ?? array();
}

function wp_seed_events_public_event_next_date_line( $event ) {
	return (string) ( $event['next_date_value'] ?? '' );
}

function wp_seed_events_public_event_next_time_line( $event ) {
	return (string) ( $event['next_time_value'] ?? '' );
}

function wp_seed_events_public_event_display_date_line( $event ) {
	return (string) ( $event['display_date_value'] ?? '' );
}

function wp_seed_events_public_event_display_time_line( $event ) {
	return (string) ( $event['display_time_value'] ?? '' );
}

class WP_Block {
	public $context;

	public function __construct( $context = array() ) {
		$this->context = $context;
	}
}

require dirname( __DIR__ ) . '/includes/public/data-registry.php';
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/block-bindings.php';
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-dates-block.php';

function d0_event( $event_id, $title = '' ) {
	$event_id = absint( $event_id );
	$title    = '' !== $title ? $title : 'Event ' . (string) $event_id;
	$GLOBALS['d0_types'][ $event_id ]    = 'wp_seed_event';
	$GLOBALS['d0_statuses'][ $event_id ] = 'publish';
	$GLOBALS['d0_events'][ $event_id ]   = array(
		'id'                 => $event_id,
		'title'              => $title,
		'types'              => array( 'Atelier', 'Stage' ),
		'next_date_value'    => 'Next ' . (string) $event_id,
		'next_time_value'    => '10:00',
		'display_date_value' => 'Display ' . (string) $event_id,
		'display_time_value' => '10:00 - 12:00',
		'place'              => array( 'name' => 'Place ' . (string) $event_id ),
		'description'        => '<p>Description ' . (string) $event_id . '</p>',
	);
}

function d0_post( $post_id, $type = 'page', $status = 'publish' ) {
	$GLOBALS['d0_types'][ absint( $post_id ) ]    = (string) $type;
	$GLOBALS['d0_statuses'][ absint( $post_id ) ] = (string) $status;
}

function d0_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

function d0_case( $label, $callback ) {
	$callback();
	$GLOBALS['d0_case_count']++;
	echo 'OK ' . (string) $GLOBALS['d0_case_count'] . ' - ' . $label . PHP_EOL;
}

function d0_bind( $field, $context = array() ) {
	return wp_seed_events_gutenberg_block_binding_value(
		array( 'field' => $field ),
		new WP_Block( $context ),
		'content'
	);
}

function d0_uncached_value( $field, $event_id ) {
	$event = wp_seed_events_get_event_data( $event_id );

	if ( array() === $event ) {
		return '';
	}

	switch ( $field ) {
		case 'title':
			return trim( wp_strip_all_tags( (string) ( $event['title'] ?? '' ) ) );
		case 'next_date':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_next_date_line( $event ) ) );
		case 'display_date':
			return trim( wp_strip_all_tags( wp_seed_events_public_event_display_date_line( $event ) ) );
		case 'place':
			return empty( $event['place']['name'] ) ? '' : trim( wp_strip_all_tags( (string) $event['place']['name'] ) );
		case 'description':
			return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) ( $event['description'] ?? '' ) ) ) ) );
		default:
			return '';
	}
}

function d0_percentile( $values, $ratio ) {
	sort( $values, SORT_NUMERIC );
	$index = max( 0, min( count( $values ) - 1, (int) ceil( count( $values ) * $ratio ) - 1 ) );

	return $values[ $index ];
}

function d0_benchmark( $cached, $samples = 80 ) {
	$fields        = array( 'title', 'next_date', 'display_date', 'place', 'description', 'next_date' );
	$durations     = array();
	$memory_deltas = array();
	$data_calls    = 0;
	$occ_calls     = 0;

	for ( $sample = 0; $sample < $samples; $sample++ ) {
		$base = ( $cached ? 30000 : 20000 ) + ( $sample * 10 );

		for ( $event = 1; $event <= 7; $event++ ) {
			d0_event( $base + $event );
		}

		$before_data   = $GLOBALS['d0_data_calls'];
		$before_occ    = $GLOBALS['d0_occ_calls'];
		$before_memory = memory_get_usage();
		$start         = hrtime( true );

		for ( $event = 1; $event <= 7; $event++ ) {
			foreach ( $fields as $field ) {
				$cached
					? wp_seed_events_dynamic_data_get_value( $field, $base + $event )
					: d0_uncached_value( $field, $base + $event );
			}
		}

		$durations[]     = ( hrtime( true ) - $start ) / 1000000;
		$memory_deltas[] = max( 0, memory_get_usage() - $before_memory );
		$data_calls     += $GLOBALS['d0_data_calls'] - $before_data;
		$occ_calls      += $GLOBALS['d0_occ_calls'] - $before_occ;
	}

	return array(
		'samples'                  => $samples,
		'event_data_calls_total'   => $data_calls,
		'event_data_calls_request' => $data_calls / $samples,
		'occurrence_calls_total'   => $occ_calls,
		'occurrence_calls_request' => $occ_calls / $samples,
		'mean_ms'                  => array_sum( $durations ) / count( $durations ),
		'p95_ms'                   => d0_percentile( $durations, 0.95 ),
		'mean_memory_bytes'        => array_sum( $memory_deltas ) / count( $memory_deltas ),
	);
}

if ( in_array( '--request-probe', $argv ?? array(), true ) ) {
	d0_event( 9001, 'Request probe' );
	wp_seed_events_dynamic_data_get_value( 'title', 9001 );
	wp_seed_events_dynamic_data_get_value( 'next_date', 9001 );
	echo wp_json_encode(
		array(
			'calls' => $GLOBALS['d0_data_calls'],
			'value' => wp_seed_events_dynamic_data_get_value( 'title', 9001 ),
		)
	) . PHP_EOL;
	exit( 0 );
}

/* Cache: 13 required cases. */
d0_case( 'cache first valid call computes once', function () {
	d0_event( 101, 'Alpha' );
	$before = $GLOBALS['d0_data_calls'];
	d0_assert( 'Alpha' === wp_seed_events_dynamic_data_get_value( 'title', 101 ), 'first value' );
	d0_assert( 1 === $GLOBALS['d0_data_calls'] - $before, 'first count' );
} );

d0_case( 'cache second call reuses the event', function () {
	$before = $GLOBALS['d0_data_calls'];
	d0_assert( 'Next 101' === wp_seed_events_dynamic_data_get_value( 'next_date', 101 ), 'second value' );
	d0_assert( 0 === $GLOBALS['d0_data_calls'] - $before, 'second count' );
} );

d0_case( 'cache multiple fields compute once', function () {
	d0_event( 102 );
	$before = $GLOBALS['d0_data_calls'];
	foreach ( array_keys( wp_seed_events_dynamic_data_fields() ) as $field ) {
		wp_seed_events_dynamic_data_get_value( $field, 102 );
	}
	d0_assert( 1 === $GLOBALS['d0_data_calls'] - $before, 'multi-field count' );
} );

d0_case( 'cache two events compute twice', function () {
	d0_event( 103 );
	d0_event( 104 );
	$before = $GLOBALS['d0_data_calls'];
	wp_seed_events_dynamic_data_get_value( 'title', 103 );
	wp_seed_events_dynamic_data_get_value( 'title', 104 );
	d0_assert( 2 === $GLOBALS['d0_data_calls'] - $before, 'two-event count' );
} );

d0_case( 'cache empty invalid result', function () {
	d0_post( 199, 'wp_seed_event', 'draft' );
	$before = $GLOBALS['d0_data_calls'];
	d0_assert( '' === wp_seed_events_dynamic_data_get_value( 'title', 199 ), 'empty title' );
	d0_assert( '' === wp_seed_events_dynamic_data_get_value( 'description', 199 ), 'empty description' );
	d0_assert( 1 === $GLOBALS['d0_data_calls'] - $before, 'empty count' );
} );

d0_case( 'cache incompatible page never reads global event', function () {
	global $wp_seed_events_public_event_id;
	d0_event( 105, 'Global' );
	d0_post( 205, 'page' );
	$wp_seed_events_public_event_id = 105;
	$before = $GLOBALS['d0_data_calls'];
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 205, 'postType' => 'page' ) ), 'page first' );
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 205, 'postType' => 'page' ) ), 'page repeat' );
	d0_assert( 0 === $GLOBALS['d0_data_calls'] - $before, 'page count' );
} );

d0_case( 'cache has no cross-ID leak', function () {
	d0_event( 106, 'First' );
	d0_event( 107, 'Second' );
	d0_assert( 'First' === wp_seed_events_dynamic_data_get_value( 'title', 106 ), 'first ID' );
	d0_assert( 'Second' === wp_seed_events_dynamic_data_get_value( 'title', 107 ), 'second ID' );
} );

d0_case( 'cache call order is neutral', function () {
	d0_event( 108 );
	d0_event( 109 );
	$first = array(
		wp_seed_events_dynamic_data_get_value( 'place', 109 ),
		wp_seed_events_dynamic_data_get_value( 'title', 108 ),
		wp_seed_events_dynamic_data_get_value( 'description', 109 ),
	);
	$again = array(
		wp_seed_events_dynamic_data_get_value( 'place', 109 ),
		wp_seed_events_dynamic_data_get_value( 'title', 108 ),
		wp_seed_events_dynamic_data_get_value( 'description', 109 ),
	);
	d0_assert( $first === $again, 'order changed values' );
} );

d0_case( 'cache preserves all existing values', function () {
	d0_event( 110, '<strong>Stable</strong>' );
	$expected = array(
		'title' => 'Stable', 'types' => 'Atelier, Stage', 'next_date' => 'Next 110',
		'next_time' => '10:00', 'display_date' => 'Display 110', 'display_time' => '10:00 - 12:00',
		'place' => 'Place 110', 'description' => 'Description 110',
	);
	foreach ( $expected as $field => $value ) {
		d0_assert( $value === wp_seed_events_dynamic_data_get_value( $field, 110 ), 'changed ' . $field );
	}
} );

d0_case( 'cache is isolated between PHP requests', function () {
	d0_assert( function_exists( 'exec' ), 'exec unavailable' );
	$results = array();
	for ( $request = 0; $request < 2; $request++ ) {
		$output = array();
		$status = 0;
		exec( escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __FILE__ ) . ' --request-probe', $output, $status );
		d0_assert( 0 === $status, 'request probe failed' );
		$results[] = json_decode( (string) end( $output ), true );
	}
	d0_assert( 1 === $results[0]['calls'] && 1 === $results[1]['calls'], 'request cache persisted' );
} );

$registry_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/data-registry.php' );

d0_case( 'cache uses no transient', function () use ( $registry_source ) {
	d0_assert( false === strpos( $registry_source, 'transient' ), 'transient found' );
} );

d0_case( 'cache performs no write', function () use ( $registry_source ) {
	foreach ( array( 'update_post_meta', 'delete_post_meta', 'update_option', 'delete_option', 'wp_insert_post', 'wp_update_post' ) as $name ) {
		d0_assert( false === strpos( $registry_source, $name ), 'write found: ' . $name );
	}
} );

d0_case( 'cache needs no external object cache', function () use ( $registry_source ) {
	d0_assert( false === strpos( $registry_source, 'wp_cache_' ), 'object cache found' );
} );

/* Gutenberg guard: 14 required cases. */
d0_case( 'guard explicit event', function () {
	d0_event( 301, 'Explicit' );
	d0_assert( 'Explicit' === d0_bind( 'title', array( 'postId' => 301, 'postType' => 'wp_seed_event' ) ), 'explicit event' );
} );

d0_case( 'guard explicit page', function () {
	d0_post( 401, 'page' );
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 401, 'postType' => 'page' ) ), 'page leak' );
} );

d0_case( 'guard explicit post', function () {
	d0_post( 402, 'post' );
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 402, 'postType' => 'post' ) ), 'post leak' );
} );

d0_case( 'guard nonexistent ID', function () {
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 4999, 'postType' => 'wp_seed_event' ) ), 'missing ID' );
} );

d0_case( 'guard postId-only event', function () {
	d0_event( 303, 'ID only' );
	d0_assert( 'ID only' === d0_bind( 'title', array( 'postId' => 303 ) ), 'ID-only event' );
} );

d0_case( 'guard postId-only page', function () {
	d0_post( 403, 'page' );
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 403 ) ), 'ID-only page' );
} );

d0_case( 'guard postType-only context', function () {
	d0_assert( '' === d0_bind( 'title', array( 'postType' => 'wp_seed_event' ) ), 'type-only context' );
} );

d0_case( 'guard no explicit context uses current event', function () {
	global $wp_seed_events_public_event_id;
	$wp_seed_events_public_event_id = 0;
	d0_event( 304, 'Current' );
	$GLOBALS['d0_current_id'] = 304;
	d0_assert( 'Current' === d0_bind( 'title' ), 'current fallback' );
} );

d0_case( 'guard model page uses public event', function () {
	global $wp_seed_events_public_event_id;
	d0_event( 305, 'Model' );
	d0_post( 405, 'page' );
	$wp_seed_events_public_event_id = 305;
	$GLOBALS['d0_current_id'] = 405;
	d0_assert( 'Model' === d0_bind( 'title' ), 'model fallback' );
} );

d0_case( 'guard event Query Loop exposes five bindings', function () {
	d0_event( 306, 'Loop' );
	$context = array( 'postId' => 306, 'postType' => 'wp_seed_event' );
	$values = array();
	foreach ( wp_seed_events_gutenberg_block_binding_fields() as $field ) {
		$values[ $field ] = d0_bind( $field, $context );
	}
	d0_assert( array(
		'title' => 'Loop', 'next_date' => 'Next 306', 'display_date' => 'Display 306',
		'place' => 'Place 306', 'description' => 'Description 306',
	) === $values, 'loop values' );
} );

d0_case( 'guard pages Query Loop nested in event is empty', function () {
	global $wp_seed_events_public_event_id;
	d0_event( 307, 'Outer' );
	d0_post( 407, 'page' );
	$wp_seed_events_public_event_id = 307;
	d0_assert( '' === d0_bind( 'title', array( 'postId' => 407, 'postType' => 'page' ) ), 'nested page leak' );
} );

d0_case( 'guard several bindings in one card compute once', function () {
	d0_event( 308, 'Card' );
	$context = array( 'postId' => 308, 'postType' => 'wp_seed_event' );
	$before = $GLOBALS['d0_data_calls'];
	foreach ( wp_seed_events_gutenberg_block_binding_fields() as $field ) {
		d0_bind( $field, $context );
	}
	d0_assert( 1 === $GLOBALS['d0_data_calls'] - $before, 'single card count' );
} );

d0_case( 'guard several cards stay isolated', function () {
	d0_event( 309, 'Card A' );
	d0_event( 310, 'Card B' );
	$before = $GLOBALS['d0_data_calls'];
	foreach ( array( 309, 310 ) as $event_id ) {
		foreach ( wp_seed_events_gutenberg_block_binding_fields() as $field ) {
			d0_bind( $field, array( 'postId' => $event_id, 'postType' => 'wp_seed_event' ) );
		}
	}
	d0_assert( 2 === $GLOBALS['d0_data_calls'] - $before, 'multi-card count' );
	d0_assert( 'Card A' === d0_bind( 'title', array( 'postId' => 309, 'postType' => 'wp_seed_event' ) ), 'card A' );
	d0_assert( 'Card B' === d0_bind( 'title', array( 'postId' => 310, 'postType' => 'wp_seed_event' ) ), 'card B' );
} );

d0_case( 'guard incompatible context never uses global event', function () {
	global $wp_seed_events_public_event_id;
	d0_event( 311, 'Forbidden global' );
	d0_post( 411, 'page' );
	$wp_seed_events_public_event_id = 311;
	$before = $GLOBALS['d0_data_calls'];
	foreach ( wp_seed_events_gutenberg_block_binding_fields() as $field ) {
		d0_assert( '' === d0_bind( $field, array( 'postId' => 411, 'postType' => 'page' ) ), 'global leak: ' . $field );
	}
	d0_assert( '' === wp_seed_events_gutenberg_block_binding_value(
		array( 'field' => 'title', 'eventId' => 311 ),
		new WP_Block( array( 'postId' => 411, 'postType' => 'page' ) ),
		'content'
	), 'legacy eventId bypassed explicit context' );

	d0_assert( 0 === $GLOBALS['d0_data_calls'] - $before, 'global Event Data call' );
} );

$before = d0_benchmark( false );
$after  = d0_benchmark( true );

d0_assert( 42 === (int) $before['event_data_calls_request'], 'baseline benchmark calls' );
d0_assert( 7 === (int) $after['event_data_calls_request'], 'cached benchmark calls' );

echo 'BENCHMARK ' . wp_json_encode(
	array( 'before' => $before, 'after' => $after ),
	JSON_UNESCAPED_SLASHES
) . PHP_EOL;
echo 'PASS: ' . (string) $GLOBALS['d0_case_count'] . ' D0 cases.' . PHP_EOL;
