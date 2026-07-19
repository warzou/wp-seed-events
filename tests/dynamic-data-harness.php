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

function wp_seed_events_sanitize_public_http_url( $url ) {
	$url   = trim( (string) $url );
	$parts = '' !== $url ? parse_url( $url ) : false;

	if (
		! is_array( $parts )
		|| empty( $parts['scheme'] )
		|| empty( $parts['host'] )
		|| ! in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true )
	) {
		return '';
	}

	return $url;
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
		'display_date_value'      => 'Display ' . (string) $event_id,
		'display_time_value'      => '10:00 - 12:00',
		'place'                   => array( 'name' => 'Place ' . (string) $event_id ),
		'place_address'           => 'Address ' . (string) $event_id,
		'description'             => '<p>Description ' . (string) $event_id . '</p>',
		'excerpt'                 => 'Excerpt ' . (string) $event_id,
		'practical_info'          => "Line one\nLine two",
		'event_document_filename' => 'programme-' . (string) $event_id . '.pdf',
		'url'                     => 'https://example.test/events/event-' . (string) $event_id . '/',
		'place_url'               => 'http://places.example.test/place-' . (string) $event_id . '/',
		'event_document_url'      => 'https://cdn.example.test/programme-' . (string) $event_id . '.pdf',
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

function d0_bind( $field, $context = array(), $attribute_name = 'content' ) {
	return wp_seed_events_gutenberg_block_binding_value(
		array( 'field' => $field ),
		new WP_Block( $context ),
		$attribute_name
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
			return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( strip_shortcodes( (string) ( $event['description'] ?? '' ) ) ) ) );
		case 'excerpt':
			return trim( wp_strip_all_tags( (string) ( $event['excerpt'] ?? '' ) ) );
		case 'practical_info':
			return wp_seed_events_dynamic_data_multiline_text( $event['practical_info'] ?? '' );
		case 'event_document_filename':
			return trim( wp_strip_all_tags( (string) ( $event['event_document_filename'] ?? '' ) ) );
		case 'url':
		case 'place_url':
		case 'event_document_url':
			return wp_seed_events_sanitize_public_http_url( $event[ $field ] ?? '' );
		default:
			return '';
	}
}

function d0_percentile( $values, $ratio ) {
	sort( $values, SORT_NUMERIC );
	$index = max( 0, min( count( $values ) - 1, (int) ceil( count( $values ) * $ratio ) - 1 ) );

	return $values[ $index ];
}

function d0_benchmark( $cached, $samples = 80, $fields = array(), $base_offset = 0 ) {
	$fields        = array() === $fields ? array( 'title', 'next_date', 'display_date', 'place', 'description', 'next_date' ) : $fields;
	$durations     = array();
	$memory_deltas = array();
	$data_calls    = 0;
	$occ_calls     = 0;

	for ( $sample = 0; $sample < $samples; $sample++ ) {
		$base = ( $cached ? 30000 : 20000 ) + absint( $base_offset ) + ( $sample * 10 );

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

d0_case( 'registry declares the exact D2 keys once', function () {
	$expected = array(
		'title', 'types', 'next_date', 'next_time', 'display_date', 'display_time',
		'place', 'place_address', 'description', 'excerpt', 'practical_info',
		'event_document_filename', 'url', 'place_url', 'event_document_url',
	);
	$url_fields = array( 'url', 'place_url', 'event_document_url' );
	$fields     = wp_seed_events_dynamic_data_fields();
	$keys       = array_keys( $fields );

	d0_assert( $expected === $keys, 'registry keys differ' );
	d0_assert( count( $keys ) === count( array_unique( $keys ) ), 'duplicate registry key' );
	d0_assert( $keys === wp_seed_events_gutenberg_block_binding_fields(), 'Gutenberg parity differs' );

	foreach ( $fields as $key => $definition ) {
		d0_assert( $key === $definition['key'], 'registry key alias differs: ' . $key );
		$expected_type = in_array( $key, $url_fields, true ) ? 'url' : 'text';
		d0_assert( $expected_type === $definition['type'], 'wrong registry type: ' . $key );
	}
} );

d0_case( 'new D1 values are present and use their exact projections', function () {
	d0_event( 111 );
	$expected = array(
		'excerpt'                 => 'Excerpt 111',
		'practical_info'          => "Line one\nLine two",
		'place_address'           => 'Address 111',
		'event_document_filename' => 'programme-111.pdf',
	);

	foreach ( $expected as $field => $value ) {
		d0_assert( $value === wp_seed_events_dynamic_data_get_value( $field, 111 ), 'new value differs: ' . $field );
	}
} );

d0_case( 'new D1 values use empty fallbacks', function () {
	d0_event( 112 );
	$GLOBALS['d0_events'][112]['place']                   = array();
	$GLOBALS['d0_events'][112]['place_address']           = '';
	$GLOBALS['d0_events'][112]['excerpt']                 = '';
	$GLOBALS['d0_events'][112]['practical_info']          = '';
	$GLOBALS['d0_events'][112]['event_document_filename'] = '';

	foreach ( array( 'excerpt', 'practical_info', 'place_address', 'event_document_filename' ) as $field ) {
		d0_assert( '' === wp_seed_events_dynamic_data_get_value( $field, 112 ), 'missing value is not empty: ' . $field );
	}
} );

d0_case( 'D2 URL projections preserve valid HTTP and HTTPS destinations', function () {
	d0_event( 115 );
	d0_assert( 'https://example.test/events/event-115/' === wp_seed_events_dynamic_data_get_value( 'url', 115 ), 'event URL differs' );
	d0_assert( 'http://places.example.test/place-115/' === wp_seed_events_dynamic_data_get_value( 'place_url', 115 ), 'place URL differs' );
	d0_assert( 'https://cdn.example.test/programme-115.pdf' === wp_seed_events_dynamic_data_get_value( 'event_document_url', 115 ), 'document URL differs' );
} );

d0_case( 'D2 URL projections use empty fallbacks', function () {
	d0_event( 116 );
	$GLOBALS['d0_events'][116]['url']                = '';
	$GLOBALS['d0_events'][116]['place_url']          = '';
	$GLOBALS['d0_events'][116]['event_document_url'] = '';

	foreach ( array( 'url', 'place_url', 'event_document_url' ) as $field ) {
		d0_assert( '' === wp_seed_events_dynamic_data_get_value( $field, 116 ), 'missing URL is not empty: ' . $field );
	}
} );

d0_case( 'D2 URL projections reject unsafe and relative protocols', function () {
	$unsafe = array(
		'javascript:alert(1)',
		'data:text/html,unsafe',
		'file:///var/www/private.pdf',
		'mailto:test@example.test',
		'tel:+3212345678',
		'/relative/path/',
		'//example.test/protocol-relative',
		'not a URL',
	);

	foreach ( $unsafe as $index => $url ) {
		$event_id = 117 + $index;
		d0_event( $event_id );
		$GLOBALS['d0_events'][ $event_id ]['url'] = $url;
		d0_assert( '' === wp_seed_events_dynamic_data_get_value( 'url', $event_id ), 'unsafe URL accepted: ' . $url );
	}
} );

d0_case( 'Gutenberg core button URL binding uses the existing source', function () {
	d0_event( 130 );
	$context = array( 'postId' => 130, 'postType' => 'wp_seed_event' );

	foreach ( array( 'url', 'place_url', 'event_document_url' ) as $field ) {
		d0_assert(
			wp_seed_events_dynamic_data_get_value( $field, 130 ) === d0_bind( $field, $context, 'url' ),
			'core/button URL binding differs: ' . $field
		);
	}
} );
d0_case( 'multiline text special characters and HTML stay safe', function () {
	d0_event( 113 );
	$GLOBALS['d0_events'][113]['title']          = 'Été & cœur';
	$GLOBALS['d0_events'][113]['types']          = array( 'Atelier', 'Stage spécial' );
	$GLOBALS['d0_events'][113]['excerpt']        = '<strong>Résumé & cœur</strong>';
	$GLOBALS['d0_events'][113]['place_address']  = '<em>3 rue du Test</em>';
	$GLOBALS['d0_events'][113]['practical_info'] = "  Première ligne  \r\nDeuxième   ligne\n\n[shortcode]Troisième <b>ligne</b>[/shortcode]";

	d0_assert( 'Été & cœur' === wp_seed_events_dynamic_data_get_value( 'title', 113 ), 'special title differs' );
	d0_assert( 'Atelier, Stage spécial' === wp_seed_events_dynamic_data_get_value( 'types', 113 ), 'type order differs' );
	d0_assert( 'Résumé & cœur' === wp_seed_events_dynamic_data_get_value( 'excerpt', 113 ), 'excerpt is not plain text' );
	d0_assert( '3 rue du Test' === wp_seed_events_dynamic_data_get_value( 'place_address', 113 ), 'address is not plain text' );
	d0_assert(
		"Première ligne\nDeuxième ligne\n\nTroisième ligne",
		wp_seed_events_dynamic_data_get_value( 'practical_info', 113 ),
		'multiline formatting differs'
	);

	foreach ( wp_seed_events_dynamic_data_fields() as $field => $definition ) {
		$value = wp_seed_events_dynamic_data_get_value( $field, 113 );
		d0_assert( false === strpos( $value, '<' ), 'HTML found in ' . $field );
	}
} );

d0_case( 'all D2 fields share one cached Event Data result', function () {
	d0_event( 114 );
	$before = $GLOBALS['d0_data_calls'];

	foreach ( wp_seed_events_dynamic_data_fields() as $field => $definition ) {
		wp_seed_events_dynamic_data_get_value( $field, 114 );
		wp_seed_events_dynamic_data_get_value( $field, 114 );
	}

	d0_assert( 1 === $GLOBALS['d0_data_calls'] - $before, 'D1 fields bypassed request cache' );
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

d0_case( 'D1 adapters do not read storage or write data', function () {
	$paths = array(
		dirname( __DIR__ ) . '/includes/public/event-data.php',
		dirname( __DIR__ ) . '/includes/public/data-registry.php',
		dirname( __DIR__ ) . '/includes/integrations/gutenberg/block-bindings.php',
		dirname( __DIR__ ) . '/includes/integrations/divi/bootstrap.php',
		dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-text.php',
		dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-url.php',
		dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-next-date.php',
	);
	$source = '';

	foreach ( $paths as $path ) {
		$source .= file_get_contents( $path );
	}

	foreach ( array( 'get_post_meta(', 'update_post_meta(', 'delete_post_meta(', 'wp_insert_post(', 'wp_update_post(', 'wpdb' ) as $primitive ) {
		d0_assert( false === strpos( $source, $primitive ), 'storage primitive found: ' . $primitive );
	}
} );

/* Gutenberg guard: explicit contexts and all registry text fields. */
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

d0_case( 'guard event Query Loop exposes every text binding', function () {
	d0_event( 306, 'Loop' );
	$context = array( 'postId' => 306, 'postType' => 'wp_seed_event' );
	$values = array();
	foreach ( wp_seed_events_gutenberg_block_binding_fields() as $field ) {
		$values[ $field ] = d0_bind( $field, $context );
	}
	d0_assert( array(
		'title' => 'Loop', 'types' => 'Atelier, Stage', 'next_date' => 'Next 306',
		'next_time' => '10:00', 'display_date' => 'Display 306', 'display_time' => '10:00 - 12:00',
		'place' => 'Place 306', 'place_address' => 'Address 306', 'description' => 'Description 306',
		'excerpt' => 'Excerpt 306', 'practical_info' => "Line one\nLine two",
		'event_document_filename' => 'programme-306.pdf',
		'url' => 'https://example.test/events/event-306/',
		'place_url' => 'http://places.example.test/place-306/',
		'event_document_url' => 'https://cdn.example.test/programme-306.pdf',
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
$d1_fields = array(
	'title', 'types', 'next_date', 'next_date', 'next_time', 'display_date',
	'place', 'place_address', 'description', 'excerpt', 'practical_info', 'event_document_filename',
);
$d1_before = d0_benchmark( false, 80, $d1_fields, 100000 );
$d1_after  = d0_benchmark( true, 80, $d1_fields, 100000 );
$d2_fields = array(
	'title', 'types', 'next_date', 'next_time', 'display_date', 'display_time',
	'place', 'place_address', 'description', 'excerpt', 'practical_info', 'event_document_filename',
	'url', 'place_url', 'event_document_url',
);
$d2_before = d0_benchmark( false, 80, $d2_fields, 200000 );
$d2_after  = d0_benchmark( true, 80, $d2_fields, 200000 );

d0_assert( 42 === (int) $before['event_data_calls_request'], 'baseline benchmark calls' );
d0_assert( 7 === (int) $after['event_data_calls_request'], 'cached benchmark calls' );
d0_assert( 84 === (int) $d1_before['event_data_calls_request'], 'D1 baseline benchmark calls' );
d0_assert( 7 === (int) $d1_after['event_data_calls_request'], 'D1 cached benchmark calls' );
d0_assert( 105 === (int) $d2_before['event_data_calls_request'], 'D2 baseline benchmark calls' );
d0_assert( 7 === (int) $d2_after['event_data_calls_request'], 'D2 cached benchmark calls' );
d0_assert( 525 === (int) $d2_before['occurrence_calls_request'], 'D2 baseline occurrence passes' );
d0_assert( 35 === (int) $d2_after['occurrence_calls_request'], 'D2 cached occurrence passes' );

echo 'BENCHMARK ' . wp_json_encode(
	array(
		'd0' => array( 'before' => $before, 'after' => $after ),
		'd1' => array( 'before' => $d1_before, 'after' => $d1_after ),
		'd2' => array( 'before' => $d2_before, 'after' => $d2_after ),
	),
	JSON_UNESCAPED_SLASHES
) . PHP_EOL;
echo 'PASS: ' . (string) $GLOBALS['d0_case_count'] . ' D0 cases.' . PHP_EOL;
