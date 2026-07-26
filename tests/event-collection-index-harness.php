<?php
/** Standalone structural assertions for the indexed collection selector. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['index_event_data_calls'] = array();
$GLOBALS['index_cases']            = 0;

class WP_Error {
	private $code;
	public function __construct( $code = '' ) { $this->code = $code; }
	public function get_error_code() { return $this->code; }
}

class Collection_Index_Wpdb {
	public $posts = 'wp_posts';
	public $postmeta = 'wp_postmeta';
	public $last_error = '';
	public $queries = array();
	public $ids = array( 5, 7 );
	public $total = 100;
	public $fail = false;

	public function get_var( $query ) {
		$this->queries[] = $query;
		$this->last_error = $this->fail ? 'failed' : '';
		return $this->fail ? null : $this->total;
	}

	public function get_col( $query ) {
		$this->queries[] = $query;
		$this->last_error = $this->fail ? 'failed' : '';
		return $this->fail ? array() : $this->ids;
	}
}

$GLOBALS['wpdb'] = new Collection_Index_Wpdb();

function is_wp_error( $value ) { return $value instanceof WP_Error; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function esc_sql( $value ) { return addslashes( (string) $value ); }
function current_time() { return '2026-07-26'; }
function wp_seed_events_is_lifecycle_index_ready() { return true; }
function wp_seed_events_event_type_options() { return array( 'atelier' => 'Atelier', 'conference' => 'Conférence' ); }
function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['index_event_data_calls'][] = absint( $event_id );
	return array( 'id' => absint( $event_id ), 'title' => 'Event ' . absint( $event_id ) );
}
function get_posts() { return array( 5, 7 ); }
function get_post_meta( $event_id, $key ) { return 5 === absint( $event_id ) && '_wp_seed_event_pinned' === $key ? '1' : ''; }
function wp_seed_events_event_type_keys_for_event() { return array( 'atelier' ); }
function wp_seed_events_event_type_public_slug( $key ) { return sanitize_title( $key ); }

require dirname( __DIR__ ) . '/includes/public/collections.php';

function index_assert( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
}

function index_case( $label, $callback ) {
	$GLOBALS['index_cases']++;
	$GLOBALS['wpdb']->queries = array();
	$GLOBALS['wpdb']->fail = false;
	$GLOBALS['index_event_data_calls'] = array();
	$callback();
	echo 'ok ' . $GLOBALS['index_cases'] . ' - ' . $label . PHP_EOL;
}

index_case( 'public collection hydrates only selected page IDs', function () {
	$result = wp_seed_events_query_event_collection( array( 'type' => 'atelier', 'status' => 'upcoming', 'per_page' => 2, 'page' => 3 ) );
	index_assert( array( 5, 7 ) === $result['ids'], 'Selected IDs differ.' );
	index_assert( array( 5, 7 ) === $GLOBALS['index_event_data_calls'], 'Event Data was not bounded to the page.' );
	index_assert( 2 === count( $GLOBALS['wpdb']->queries ), 'Indexed selector must issue count and page queries.' );
	index_assert( false !== strpos( $GLOBALS['wpdb']->queries[1], 'LIMIT 2 OFFSET 4' ), 'SQL pagination is missing.' );
} );

index_case( 'upcoming selection uses the next active projection', function () {
	wp_seed_events_query_indexed_event_collection( array( 'status' => 'upcoming' ), false );
	index_assert( false !== strpos( $GLOBALS['wpdb']->queries[0], 'MIN(CASE WHEN occurrence_meta.meta_value >=' ), 'Upcoming aggregate is absent.' );
	index_assert( false !== strpos( $GLOBALS['wpdb']->queries[0], 'IS NOT NULL' ), 'Upcoming HAVING differs.' );
} );

index_case( 'past selection excludes events that still have a future date', function () {
	wp_seed_events_query_indexed_event_collection( array( 'status' => 'past' ), false );
	$sql = $GLOBALS['wpdb']->queries[0];
	index_assert( false !== strpos( $sql, 'IS NULL AND MAX(' ) && false !== strpos( $sql, 'IS NOT NULL' ), 'Past HAVING differs.' );
} );

index_case( 'all selection keeps undated events after dated events', function () {
	wp_seed_events_query_indexed_event_collection( array( 'status' => 'all', 'order' => 'desc' ), false );
	$sql = $GLOBALS['wpdb']->queries[1];
	index_assert( false !== strpos( $sql, 'CASE WHEN COALESCE(' ) && false !== strpos( $sql, 'THEN 1 ELSE 0 END ASC' ), 'Undated ordering differs.' );
	index_assert( false !== strpos( $sql, ' DESC,' ), 'Descending business order is absent.' );
} );

index_case( 'pinned priority precedes business date', function () {
	wp_seed_events_query_indexed_event_collection( array( 'status' => 'all' ), false );
	$sql = $GLOBALS['wpdb']->queries[1];
	$pin = strpos( $sql, 'pinned_meta.post_id IS NULL' );
	$date = strpos( $sql, 'CASE WHEN COALESCE(' );
	index_assert( false !== $pin && false !== $date && $pin < $date, 'Pinned priority moved after date.' );
} );

index_case( 'pinned-only is constrained in SQL', function () {
	wp_seed_events_query_indexed_event_collection( array( 'pinned' => 'only', 'status' => 'all' ), false );
	index_assert( false !== strpos( $GLOBALS['wpdb']->queries[0], 'pinned_meta.post_id IS NOT NULL' ), 'Pinned-only SQL filter is absent.' );
} );

index_case( 'type slug resolves to a projected canonical key', function () {
	wp_seed_events_query_indexed_event_collection( array( 'type' => 'atelier', 'status' => 'all' ), false );
	index_assert( false !== strpos( $GLOBALS['wpdb']->queries[0], "type_meta.meta_value IN ('atelier')" ), 'Type projection differs.' );
} );

index_case( 'unknown type fails closed without SQL', function () {
	$result = wp_seed_events_query_indexed_event_collection( array( 'type' => 'missing', 'status' => 'all' ), false );
	index_assert( array() === $result['ids'] && 0 === count( $GLOBALS['wpdb']->queries ), 'Unknown type did not fail closed.' );
} );

index_case( 'builder bridge requests IDs without Event Data hydration', function () {
	$query = wp_seed_events_apply_collection_to_query_args( array( 'posts_per_page' => 2 ), array( 'status' => 'all' ) );
	index_assert( array( 5, 7 ) === $query['post__in'], 'Builder IDs differ.' );
	index_assert( array() === $GLOBALS['index_event_data_calls'], 'Builder bridge hydrated Event Data.' );
} );

index_case( 'database error returns an explicit error from indexed selector', function () {
	$GLOBALS['wpdb']->fail = true;
	$result = wp_seed_events_query_indexed_event_collection( array( 'status' => 'all' ), false );
	index_assert( is_wp_error( $result ) && 'event_collection_index_query_failed' === $result->get_error_code(), 'Database failure did not fail explicitly.' );
} );

index_case( 'public entry point falls back safely after database error', function () {
	$GLOBALS['wpdb']->fail = true;
	$result = wp_seed_events_query_event_collection( array( 'status' => 'all' ) );
	index_assert( array( 5, 7 ) === $result['ids'], 'Legacy fallback did not preserve IDs.' );
} );

index_case( 'index source contains no full catalogue Event Data loop', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/public/collections.php' );
	$indexed = substr( $source, strpos( $source, 'function wp_seed_events_query_indexed_event_collection' ) );
	$end     = strpos( $indexed, "/**\n * Backward-compatible" );
	index_assert( false !== $end, 'Indexed selector boundary was not found.' );
	$indexed = substr( $indexed, 0, $end );
	index_assert( false === strpos( $indexed, "'posts_per_page' => -1" ), 'Indexed selector still loads the full catalogue with get_posts.' );
} );

echo 'Event collection index harness: ' . $GLOBALS['index_cases'] . '/' . $GLOBALS['index_cases'] . ' OK' . PHP_EOL;
