<?php
/**
 * Standalone occurrence collection, grouping and REST contract assertions.
 *
 * Run with: php tests/occurrence-collection-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'OBJECT', 'OBJECT' );

$GLOBALS['occurrence_collection_assertions'] = 0;
$GLOBALS['occurrence_collection_ready']      = false;
$GLOBALS['occurrence_collection_table']      = true;
$GLOBALS['occurrence_collection_routes']     = array();
$GLOBALS['occurrence_collection_promotions'] = array(
	10 => array( 'id' => 10, 'name' => 'Promotion 2026', 'slug' => 'promotion-2026', 'start_year' => 2026, 'status' => 'active', 'order' => 20, 'description' => '' ),
	11 => array( 'id' => 11, 'name' => 'Promotion archivee', 'slug' => 'promotion-archivee', 'start_year' => 2024, 'status' => 'archived', 'order' => 10, 'description' => '' ),
	12 => array( 'id' => 12, 'name' => 'Promotion 2027', 'slug' => 'promotion-2027', 'start_year' => 2027, 'status' => 'active', 'order' => 30, 'description' => '' ),
);

class WP_Error {
	private $code;
	private $message;
	private $data;
	public function __construct( $code, $message, $data = array() ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code() { return $this->code; }
	public function get_error_data() { return $this->data; }
}

class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public $post_title;
	public $post_name;
	public function __construct( $id, $title, $slug, $status = 'publish' ) {
		$this->ID = $id; $this->post_type = 'wp_seed_event'; $this->post_status = $status; $this->post_title = $title; $this->post_name = $slug;
	}
}

class WP_REST_Server { const READABLE = 'GET'; }
class WP_REST_Request {
	private $params;
	public function __construct( $params = array() ) { $this->params = $params; }
	public function get_params() { return $this->params; }
}
class WP_REST_Response {
	public $data;
	public $headers = array();
	public function __construct( $data ) { $this->data = $data; }
	public function header( $name, $value ) { $this->headers[ $name ] = $value; }
}

$GLOBALS['occurrence_collection_posts'] = array(
	100 => new WP_Post( 100, 'Theme souffle', 'theme-souffle' ),
	101 => new WP_Post( 101, 'Theme ancrage', 'theme-ancrage' ),
	102 => new WP_Post( 102, 'Conference publique', 'conference-publique' ),
	103 => new WP_Post( 103, 'Brouillon prive', 'brouillon-prive', 'draft' ),
	104 => new WP_Post( 104, 'Evenement prive', 'evenement-prive', 'private' ),
	105 => new WP_Post( 105, 'Atelier hors parcours', 'atelier-hors-parcours' ),
	106 => new WP_Post( 106, 'Evenement sans occurrence', 'sans-occurrence' ),
);
$GLOBALS['occurrence_collection_types'] = array(
	100 => array( 'atelier' ), 101 => array( 'atelier' ), 102 => array( 'conference' ), 103 => array( 'atelier' ), 104 => array( 'atelier' ), 105 => array( 'atelier' ), 106 => array( 'atelier' ),
);

function occurrence_collection_row( $event_id, $uid, $start, $promotion_id = 0, $year = 0, $cancelled = false, $pinned = false, $type = 'atelier', $index = 0, $end = '' ) {
	return array(
		'event_id' => $event_id, 'occurrence_uid' => $uid, 'occurrence_index' => $index,
		'promotion_id' => $promotion_id, 'parcours_year' => $year,
		'start_raw' => $start, 'end_raw' => '' !== $end ? $end : $start,
		'start_sort' => $start, 'end_sort' => '' !== $end ? $end : $start,
		'is_cancelled' => $cancelled ? 1 : 0, 'event_type' => $type,
		'event_status' => $GLOBALS['occurrence_collection_posts'][ $event_id ]->post_status,
		'is_pinned' => $pinned ? 1 : 0,
	);
}

$GLOBALS['occurrence_collection_rows'] = array(
	occurrence_collection_row( 100, 'a', '2026-02-01 09:00', 10, 1, false, true, 'atelier', 0 ),
	occurrence_collection_row( 100, 'b', '2026-03-01 09:00', 10, 1, true, true, 'atelier', 1 ),
	occurrence_collection_row( 100, 'c', '2025-01-10 09:00', 11, 2, false, true, 'atelier', 2 ),
	occurrence_collection_row( 100, 'i', '2027-05-01 09:00', 12, 2, false, true, 'atelier', 3 ),
	occurrence_collection_row( 101, 'd', '2026-02-01 09:00', 10, 1, false, false, 'atelier', 0 ),
	occurrence_collection_row( 101, 'e', '2027-04-01 09:00', 12, 1, false, false, 'atelier', 1 ),
	occurrence_collection_row( 102, 'f', '2026-05-01 09:00', 10, 2, false, false, 'conference', 0, '2026-05-02 17:00' ),
	occurrence_collection_row( 103, 'g', '2026-01-15 09:00', 10, 1, false, false, 'atelier', 0 ),
	occurrence_collection_row( 104, 'private', '2026-01-20 09:00', 10, 1, false, false, 'atelier', 0 ),
	occurrence_collection_row( 105, 'h', '2026-06-01 09:00', 0, 0, false, false, 'atelier', 0 ),
);

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ); }
function sanitize_title( $value ) { return trim( strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $value ) ), '-' ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function current_time( $type, $gmt = false ) { return 'Y-m-d' === $type ? '2026-01-01' : ( 'mysql' === $type ? '2026-01-01 00:00:00' : 0 ); }
function update_meta_cache( $type, $ids ) { return true; }
function get_post( $id ) { return $GLOBALS['occurrence_collection_posts'][ absint( $id ) ] ?? null; }
function get_posts( $args ) {
	$ids = array();
	foreach ( $GLOBALS['occurrence_collection_posts'] as $id => $post ) {
		if ( 'publish' === $post->post_status ) { $ids[] = $id; }
	}
	return $ids;
}
function wp_seed_events_normalize_parcours_year( $year ) { $year = absint( $year ); return 1 <= $year && 4 >= $year ? $year : 0; }
function wp_seed_events_parcours_year_label( $year ) { $year = wp_seed_events_normalize_parcours_year( $year ); return 0 === $year ? '' : ( 1 === $year ? '1re annee' : $year . 'e annee' ); }
function wp_seed_events_get_promotion( $selector ) {
	if ( is_numeric( $selector ) ) { return $GLOBALS['occurrence_collection_promotions'][ absint( $selector ) ] ?? array(); }
	foreach ( $GLOBALS['occurrence_collection_promotions'] as $promotion ) { if ( sanitize_title( $selector ) === $promotion['slug'] ) { return $promotion; } }
	return array();
}
function wp_seed_events_event_type_options() { return array( 'atelier' => 'Atelier', 'conference' => 'Conference' ); }
function wp_seed_events_event_type_keys_for_event( $event_id ) { return $GLOBALS['occurrence_collection_types'][ absint( $event_id ) ] ?? array(); }
function wp_seed_events_public_collection_type_keys( $type ) { $type = sanitize_title( $type ); return '' === $type ? array() : ( array_key_exists( $type, wp_seed_events_event_type_options() ) ? array( $type ) : false ); }
function wp_seed_events_is_lifecycle_index_ready() { return $GLOBALS['occurrence_collection_ready']; }
function wp_seed_events_occurrence_projection_table_exists() { return $GLOBALS['occurrence_collection_table']; }
function wp_seed_events_occurrence_projection_table_name() { return 'wp_wp_seed_event_occurrences'; }
function wp_seed_events_get_occurrence_projection_rows( $event_id, $prefer_index = true ) {
	return array_values( array_filter( $GLOBALS['occurrence_collection_rows'], static function ( $row ) use ( $event_id ) { return (int) $row['event_id'] === (int) $event_id; } ) );
}
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['occurrence_collection_routes'][ $namespace . $route ] = $args; }
function rest_ensure_response( $value ) { return $value instanceof WP_REST_Response ? $value : new WP_REST_Response( $value ); }
class Occurrence_Collection_Wpdb {
	public $prefix = 'wp_';
	public $posts = 'wp_posts';
	public $postmeta = 'wp_postmeta';
	public $last_error = '';

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) { $args = $args[0]; }
		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'";
			$query = preg_replace( '/%[sd]/', $replacement, $query, 1 );
		}
		return $query;
	}

	private function selected_rows( $query ) {
		$rows = array_values( array_filter( $GLOBALS['occurrence_collection_rows'], static function ( $row ) {
			$post = get_post( $row['event_id'] );
			return $post instanceof WP_Post && 'publish' === $post->post_status && 'publish' === $row['event_status'];
		} ) );

		$checks = array(
			'/projection\.promotion_id = ([0-9]+)/' => 'promotion_id',
			'/projection\.parcours_year = ([0-9]+)/' => 'parcours_year',
			'/projection\.event_id = ([0-9]+)/' => 'event_id',
		);
		foreach ( $checks as $pattern => $key ) {
			if ( preg_match( $pattern, $query, $match ) ) {
				$expected = (int) $match[1];
				$rows = array_values( array_filter( $rows, static function ( $row ) use ( $key, $expected ) { return (int) $row[ $key ] === $expected; } ) );
			}
		}
		if ( false !== strpos( $query, 'projection.is_cancelled = 0' ) ) {
			$rows = array_values( array_filter( $rows, static function ( $row ) { return empty( $row['is_cancelled'] ); } ) );
		}
		if ( false !== strpos( $query, 'projection.promotion_id > 0' ) ) {
			$rows = array_values( array_filter( $rows, static function ( $row ) { return 0 < (int) $row['promotion_id']; } ) );
		}
		if ( false !== strpos( $query, 'projection.is_pinned = 1' ) ) {
			$rows = array_values( array_filter( $rows, static function ( $row ) { return ! empty( $row['is_pinned'] ); } ) );
		}
		if ( preg_match( '/meta_value IN \(([^)]+)\)/', $query, $match ) ) {
			$types = array_map( static function ( $value ) { return trim( $value, " '\"" ); }, explode( ',', $match[1] ) );
			$rows = array_values( array_filter( $rows, static function ( $row ) use ( $types ) { return array() !== array_intersect( $types, wp_seed_events_event_type_keys_for_event( $row['event_id'] ) ); } ) );
		}
		$bounds = array(
			'/projection\.start_sort >= \'([^\']+)\'/' => array( 'start_sort', '>=' ),
			'/projection\.start_sort < \'([^\']+)\'/' => array( 'start_sort', '<' ),
			'/projection\.end_sort >= \'([^\']+)\'/' => array( 'end_sort', '>=' ),
			'/projection\.start_sort <= \'([^\']+)\'/' => array( 'start_sort', '<=' ),
		);
		foreach ( $bounds as $pattern => $definition ) {
			if ( preg_match( $pattern, $query, $match ) ) {
				list( $key, $operator ) = $definition;
				$value = $match[1];
				$rows = array_values( array_filter( $rows, static function ( $row ) use ( $key, $operator, $value ) {
					if ( '>=' === $operator ) { return $row[ $key ] >= $value; }
					if ( '<=' === $operator ) { return $row[ $key ] <= $value; }
					return $row[ $key ] < $value;
				} ) );
			}
		}
		return $rows;
	}

	public function get_var( $query ) {
		$this->last_error = '';
		if ( false !== strpos( $query, 'SHOW TABLES LIKE' ) ) { return $GLOBALS['occurrence_collection_table'] ? 'wp_wp_seed_event_occurrences' : null; }
		return count( $this->selected_rows( $query ) );
	}

	public function get_results( $query, $format ) {
		$this->last_error = '';
		$rows = $this->selected_rows( $query );
		$order = false !== strpos( $query, 'projection.start_sort DESC' ) ? 'chronological_desc' : 'chronological';
		usort( $rows, static function ( $first, $second ) use ( $order ) { return wp_seed_events_occurrence_collection_compare_rows( $first, $second, $order ); } );
		$limit = count( $rows );
		$offset = 0;
		if ( preg_match( '/LIMIT ([0-9]+) OFFSET ([0-9]+)/', $query, $match ) ) { $limit = (int) $match[1]; $offset = (int) $match[2]; }
		return array_slice( $rows, $offset, $limit );
	}
}
$GLOBALS['wpdb'] = new Occurrence_Collection_Wpdb();

require_once dirname( __DIR__ ) . '/includes/public/occurrence-collections.php';

function occurrence_collection_assert( $condition, $message ) {
	++$GLOBALS['occurrence_collection_assertions'];
	if ( ! $condition ) { fwrite( STDERR, "FAIL: {$message}\n" ); exit( 1 ); }
}
function occurrence_collection_query( $args = array(), $ready = false ) {
	$GLOBALS['occurrence_collection_ready'] = $ready;
	return wp_seed_events_query_occurrence_collection( $args );
}
function occurrence_collection_ids( $result ) { return array_column( $result['items'], 'occurrence_uid' ); }
function occurrence_collection_assert_parity( $args, $label ) {
	$fallback = occurrence_collection_query( $args, false );
	$indexed  = occurrence_collection_query( $args, true );
	occurrence_collection_assert( ! is_wp_error( $fallback ) && ! is_wp_error( $indexed ), $label . ' returns results' );
	occurrence_collection_assert( $fallback === $indexed, $label . ' index/fallback parity' );
}
$default = occurrence_collection_query();
occurrence_collection_assert( 6 === $default['total_items'], 'default excludes cancelled, drafts and private events' );
occurrence_collection_assert( array( 'a', 'i', 'd', 'f', 'h', 'e' ) === occurrence_collection_ids( $default ), 'default order is pinned then chronological and deterministic' );
occurrence_collection_assert( 1 === $default['page'] && 20 === $default['per_page'], 'default pagination' );
occurrence_collection_assert( 1 === $default['total_pages'] && ! $default['has_previous'] && ! $default['has_next'], 'default pagination metadata' );
occurrence_collection_assert( array() === array_intersect( array( 'updated_at', 'lifecycle', 'table' ), array_keys( $default['items'][0] ) ), 'internal projection state is not public' );

$cancelled = occurrence_collection_query( array( 'include_cancelled' => true ) );
occurrence_collection_assert( in_array( 'b', occurrence_collection_ids( $cancelled ), true ), 'cancelled occurrence can be included explicitly' );
occurrence_collection_assert( 7 === $cancelled['total_items'], 'cancelled inclusion count' );

$promotion_id = occurrence_collection_query( array( 'promotion_id' => 10 ) );
$promotion_slug = occurrence_collection_query( array( 'promotion_slug' => 'promotion-2026' ) );
occurrence_collection_assert( occurrence_collection_ids( $promotion_id ) === occurrence_collection_ids( $promotion_slug ), 'Promotion ID and slug are equivalent' );
occurrence_collection_assert( array( 'a', 'd', 'f' ) === occurrence_collection_ids( $promotion_id ), 'Promotion filter' );
occurrence_collection_assert( array( 'i', 'f' ) === occurrence_collection_ids( occurrence_collection_query( array( 'parcours_year' => 2 ) ) ), 'parcours year filter' );
occurrence_collection_assert( array( 'a', 'i' ) === occurrence_collection_ids( occurrence_collection_query( array( 'event_id' => 100 ) ) ), 'event/theme filter' );
occurrence_collection_assert( ! in_array( 'f', occurrence_collection_ids( occurrence_collection_query( array( 'type' => 'atelier' ) ) ), true ), 'event type filter' );
occurrence_collection_assert( array( 'c' ) === occurrence_collection_ids( occurrence_collection_query( array( 'status' => 'past' ) ) ), 'past status filter' );
occurrence_collection_assert( array( 'a', 'i' ) === occurrence_collection_ids( occurrence_collection_query( array( 'pinned' => 'only' ) ) ), 'pinned-only filter' );
occurrence_collection_assert( array( 'f', 'h' ) === occurrence_collection_ids( occurrence_collection_query( array( 'from' => '2026-05-01', 'to' => '2026-06-01' ) ) ), 'date overlap bounds' );
occurrence_collection_assert( array( 'i', 'a', 'e', 'h', 'f', 'd' ) === occurrence_collection_ids( occurrence_collection_query( array( 'order' => 'chronological_desc' ) ) ), 'descending deterministic order with pinned priority' );

$page_two = occurrence_collection_query( array( 'page' => 2, 'per_page' => 2 ) );
occurrence_collection_assert( array( 'd', 'f' ) === occurrence_collection_ids( $page_two ), 'middle page slice' );
occurrence_collection_assert( 3 === $page_two['total_pages'] && $page_two['has_previous'] && $page_two['has_next'], 'middle page metadata' );
$last_page = occurrence_collection_query( array( 'page' => 3, 'per_page' => 2 ) );
occurrence_collection_assert( array( 'h', 'e' ) === occurrence_collection_ids( $last_page ) && ! $last_page['has_next'], 'last page' );
$empty_page = occurrence_collection_query( array( 'page' => 8, 'per_page' => 2 ) );
occurrence_collection_assert( array() === $empty_page['items'] && 6 === $empty_page['total_items'], 'out-of-range page is an empty non-error result' );

$invalid_cases = array(
	array( array( 'promotion' => 'unknown' ), 'wp_seed_events_occurrence_collection_invalid_promotion' ),
	array( array( 'promotion_id' => 10, 'promotion_slug' => 'promotion-2027' ), 'wp_seed_events_occurrence_collection_conflicting_promotion' ),
	array( array( 'parcours_year' => 5 ), 'wp_seed_events_occurrence_collection_invalid_parcours_year' ),
	array( array( 'event_id' => 0 ), 'wp_seed_events_occurrence_collection_invalid_event' ),
	array( array( 'type' => 'unknown' ), 'wp_seed_events_occurrence_collection_invalid_type' ),
	array( array( 'status' => 'future' ), 'wp_seed_events_occurrence_collection_invalid_status' ),
	array( array( 'pinned' => 'maybe' ), 'wp_seed_events_occurrence_collection_invalid_pinned' ),
	array( array( 'include_cancelled' => 'maybe' ), 'wp_seed_events_occurrence_collection_invalid_include_cancelled' ),
	array( array( 'order' => 'random' ), 'wp_seed_events_occurrence_collection_invalid_order' ),
	array( array( 'page' => 0 ), 'wp_seed_events_occurrence_collection_invalid_page' ),
	array( array( 'per_page' => 101 ), 'wp_seed_events_occurrence_collection_invalid_per_page' ),
	array( array( 'from' => '2026/01/01' ), 'wp_seed_events_occurrence_collection_invalid_date' ),
	array( array( 'from' => '2027-01-01', 'to' => '2026-01-01' ), 'wp_seed_events_occurrence_collection_incoherent_combination' ),
);
foreach ( $invalid_cases as $invalid_case ) {
	$error = occurrence_collection_query( $invalid_case[0] );
	occurrence_collection_assert( is_wp_error( $error ) && $invalid_case[1] === $error->get_error_code(), 'stable error ' . $invalid_case[1] );
}

$parity_matrix = array(
	array(),
	array( 'promotion_id' => 10 ),
	array( 'parcours_year' => 1, 'type' => 'atelier' ),
	array( 'status' => 'all', 'include_cancelled' => true ),
	array( 'pinned' => 'only', 'order' => 'chronological_desc' ),
	array( 'from' => '2026-02-01', 'to' => '2027-04-01', 'page' => 2, 'per_page' => 2 ),
);
foreach ( $parity_matrix as $index => $parity_args ) { occurrence_collection_assert_parity( $parity_args, 'matrix ' . $index ); }
$GLOBALS['occurrence_collection_ready'] = true;
$GLOBALS['occurrence_collection_table'] = false;
$table_fallback = wp_seed_events_query_occurrence_collection( array( 'promotion_id' => 10 ) );
occurrence_collection_assert( array( 'a', 'd', 'f' ) === occurrence_collection_ids( $table_fallback ), 'missing table uses exact fallback' );
$GLOBALS['occurrence_collection_table'] = true;
$grouped = wp_seed_events_query_grouped_occurrence_collection();
occurrence_collection_assert( ! is_wp_error( $grouped ), 'grouped collection succeeds' );
occurrence_collection_assert( array( 10, 12 ) === array_column( array_column( $grouped['promotions'], 'promotion' ), 'id' ), 'empty archived Promotion omitted from upcoming groups' );
occurrence_collection_assert( 5 === $grouped['total_items'] && 5 === $grouped['returned_items'], 'grouped counters exclude out-of-parcours occurrence' );
occurrence_collection_assert( 'canonical_path' === $grouped['args']['order'], 'grouped order is explicit' );
occurrence_collection_assert( ! isset( $grouped['args']['page'], $grouped['args']['per_page'] ), 'grouped contract has no ambiguous pagination' );

$all_grouped = wp_seed_events_query_grouped_occurrence_collection( array( 'status' => 'all' ) );
$promotion_ids = array_column( array_column( $all_grouped['promotions'], 'promotion' ), 'id' );
occurrence_collection_assert( array( 11, 10, 12 ) === $promotion_ids, 'Promotions use manual order, start year, name and ID' );
occurrence_collection_assert( 'archived' === $all_grouped['promotions'][0]['promotion']['status'], 'archived Promotion remains queryable for history' );
$promotion_10 = $all_grouped['promotions'][1];
occurrence_collection_assert( array( 1, 2 ) === array_column( $promotion_10['years'], 'parcours_year' ), 'years are ordered 1 to 4' );
occurrence_collection_assert( array( 100, 101 ) === array_column( array_column( $promotion_10['years'][0]['themes'], 'event' ), 'id' ), 'themes use first occurrence then pinned/title/ID' );
occurrence_collection_assert( array( 'a' ) === array_column( $promotion_10['years'][0]['themes'][0]['occurrences'], 'occurrence_uid' ), 'theme occurrences are nested once per matching path' );
occurrence_collection_assert( 3 === $promotion_10['count'], 'Promotion count is coherent' );
occurrence_collection_assert( 2 === $promotion_10['years'][0]['count'], 'year count is coherent' );
occurrence_collection_assert( 1 === $promotion_10['years'][0]['themes'][0]['count'], 'theme count is coherent' );
occurrence_collection_assert( 3 === count( array_filter( $all_grouped['promotions'], static function ( $group ) { foreach ( $group['years'] as $year ) { foreach ( $year['themes'] as $theme ) { if ( 100 === $theme['event']['id'] ) { return true; } } } return false; } ) ), 'same theme can appear in several Promotion paths' );

$grouped_cancelled = wp_seed_events_query_grouped_occurrence_collection( array( 'include_cancelled' => true ) );
occurrence_collection_assert( 6 === $grouped_cancelled['total_items'], 'grouped cancelled inclusion is explicit' );
$limited = wp_seed_events_query_grouped_occurrence_collection( array( 'limit' => 2 ) );
occurrence_collection_assert( 2 === $limited['returned_items'] && $limited['is_limited'], 'grouped global limit is bounded and explicit' );
$group_page_error = wp_seed_events_query_grouped_occurrence_collection( array( 'page' => 1 ) );
occurrence_collection_assert( is_wp_error( $group_page_error ) && 'wp_seed_events_occurrence_collection_incoherent_combination' === $group_page_error->get_error_code(), 'grouped pagination is rejected explicitly' );
$group_limit_error = wp_seed_events_query_grouped_occurrence_collection( array( 'limit' => 501 ) );
occurrence_collection_assert( is_wp_error( $group_limit_error ) && 'wp_seed_events_occurrence_collection_invalid_limit' === $group_limit_error->get_error_code(), 'grouped limit is bounded' );

wp_seed_events_register_occurrence_collection_rest_routes();
occurrence_collection_assert( isset( $GLOBALS['occurrence_collection_routes']['wp-seed-events/v1/occurrences'] ), 'flat REST route registered' );
occurrence_collection_assert( isset( $GLOBALS['occurrence_collection_routes']['wp-seed-events/v1/occurrences/grouped'] ), 'grouped REST route registered' );
$flat_route = $GLOBALS['occurrence_collection_routes']['wp-seed-events/v1/occurrences'];
occurrence_collection_assert( '__return_true' === $flat_route[0]['permission_callback'], 'flat route is public read-only' );
occurrence_collection_assert( WP_REST_Server::READABLE === $flat_route[0]['methods'], 'flat route only reads' );
occurrence_collection_assert( isset( $flat_route[0]['args']['promotion_id'], $flat_route[0]['args']['page'], $flat_route['schema'] ), 'flat route exposes filters, pagination and schema' );
$rest_response = wp_seed_events_rest_get_occurrence_collection( new WP_REST_Request( array( 'promotion_id' => 10, 'per_page' => 2 ) ) );
occurrence_collection_assert( $rest_response instanceof WP_REST_Response, 'flat REST callback returns a response' );
occurrence_collection_assert( 3 === $rest_response->headers['X-WP-Total'] && 2 === $rest_response->headers['X-WP-TotalPages'], 'REST pagination headers' );
occurrence_collection_assert( ! in_array( 'g', occurrence_collection_ids( $rest_response->data ), true ) && ! in_array( 'private', occurrence_collection_ids( $rest_response->data ), true ), 'REST never leaks draft or private events' );
$rest_grouped = wp_seed_events_rest_get_grouped_occurrence_collection( new WP_REST_Request( array( 'promotion_slug' => 'promotion-2026' ) ) );
occurrence_collection_assert( $rest_grouped instanceof WP_REST_Response && 1 === count( $rest_grouped->data['promotions'] ), 'grouped REST delegates to canonical query' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/public/occurrence-collections.php' );
occurrence_collection_assert( 1 === substr_count( $source, "'/occurrences'" ), 'flat route is registered once' );
occurrence_collection_assert( 1 === substr_count( $source, "'/occurrences/grouped'" ), 'grouped route is registered once' );
occurrence_collection_assert( false === strpos( $source, 'get_post_meta' ), 'REST and collection layer do not read business meta directly' );
occurrence_collection_assert( false === strpos( json_encode( $rest_response->data ), 'updated_at' ), 'REST omits projection timestamps' );
occurrence_collection_assert( false === strpos( json_encode( $rest_response->data ), 'wp_seed_event_occurrences' ), 'REST omits lifecycle storage details' );

echo 'Occurrence Collections: ' . $GLOBALS['occurrence_collection_assertions'] . '/' . $GLOBALS['occurrence_collection_assertions'] . " assertions passed.\n";
