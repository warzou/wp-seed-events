<?php
/** Standalone native event classification and WP_Query contract assertions. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['native_cases'] = 0;
$GLOBALS['native_posts'] = array();
$GLOBALS['native_meta'] = array();
$GLOBALS['native_types'] = array();
$GLOBALS['native_primary'] = array();
$GLOBALS['native_occurrences'] = array();
$GLOBALS['native_terms'] = array();
$GLOBALS['native_relationships'] = array();
$GLOBALS['native_taxonomies'] = array();
$GLOBALS['native_rest_fields'] = array();

class WP_Error {
	private $code;
	public function __construct( $code = '' ) { $this->code = (string) $code; }
	public function get_error_code() { return $this->code; }
}
class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public function __construct( $id, $status = 'publish' ) {
		$this->ID = (int) $id;
		$this->post_type = 'wp_seed_event';
		$this->post_status = $status;
	}
}
class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public function __construct( $id, $name, $slug ) {
		$this->term_id = (int) $id;
		$this->name = (string) $name;
		$this->slug = (string) $slug;
	}
}
class WP_Query {
	private $vars;
	public function __construct( $vars = array() ) { $this->vars = $vars; }
	public function get( $key ) { return $this->vars[ $key ] ?? null; }
	public function set( $key, $value ) { $this->vars[ $key ] = $value; }
}
class Native_Request {
	private $method;
	private $params;
	public function __construct( $method, $params = array() ) { $this->method = $method; $this->params = $params; }
	public function get_method() { return $this->method; }
	public function get_param( $key ) { return $this->params[ $key ] ?? null; }
}
class Native_Wpdb {
	public $postmeta = 'wp_postmeta';
	public $posts = 'wp_posts';
	public function prepare( $sql, $value ) { return str_replace( '%s', "'" . $value . "'", $sql ); }
}
$GLOBALS['wpdb'] = new Native_Wpdb();

function native_assert( $condition, $message ) {
	$GLOBALS['native_cases']++;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}
function add_action() {}
function add_filter() {}
function register_taxonomy( $taxonomy, $objects, $args ) { $GLOBALS['native_taxonomies'][ $taxonomy ] = $args; }
function register_rest_field( $type, $field, $args ) { $GLOBALS['native_rest_fields'][ $field ] = $args; }
function __( $value ) { return $value; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', (string) $value ) ); }
function sanitize_title( $value ) {
	$value = iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value );
	$value = strtolower( preg_replace( '/[^a-zA-Z0-9_]+/', '-', (string) $value ) );
	return trim( $value, '-' );
}
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_post( $id ) { return $GLOBALS['native_posts'][ (int) $id ] ?? null; }
function get_post_meta( $id, $key, $single = false ) { return $GLOBALS['native_meta'][ (int) $id ][ $key ] ?? ''; }
function update_post_meta( $id, $key, $value ) { $GLOBALS['native_meta'][ (int) $id ][ $key ] = $value; }
function delete_post_meta( $id, $key ) { unset( $GLOBALS['native_meta'][ (int) $id ][ $key ] ); }
function wp_seed_events_event_type_options() { return array( 'atelier' => 'Atelier', 'stage' => 'Stage', 'journee_decouverte' => 'Journée découverte', 'reunion_information' => 'Réunion d’information', 'seminaire' => 'Séminaire' ); }
function wp_seed_events_event_type_keys_for_event( $id ) { return $GLOBALS['native_types'][ (int) $id ] ?? array(); }
function wp_seed_events_primary_type_for_event( $id ) { return $GLOBALS['native_primary'][ (int) $id ] ?? ''; }
function wp_seed_events_lifecycle_index_post_statuses() { return array( 'publish', 'draft', 'private' ); }
function wp_seed_events_is_lifecycle_index_ready() { return ! empty( $GLOBALS['native_ready'] ); }
function get_term_by( $field, $value, $taxonomy ) {
	foreach ( $GLOBALS['native_terms'][ $taxonomy ] ?? array() as $term ) {
		if ( $term->$field === $value ) { return $term; }
	}
	return false;
}
function wp_insert_term( $name, $taxonomy, $args ) {
	$id = 1 + count( $GLOBALS['native_terms'][ $taxonomy ] ?? array() );
	$GLOBALS['native_terms'][ $taxonomy ][ $id ] = new WP_Term( $id, $name, $args['slug'] );
	return array( 'term_id' => $id );
}
function wp_update_term( $id, $taxonomy, $args ) {
	$GLOBALS['native_terms'][ $taxonomy ][ $id ]->name = $args['name'];
	return array( 'term_id' => $id );
}
function wp_set_object_terms( $id, $term_ids, $taxonomy ) {
	$GLOBALS['native_relationships'][ (int) $id ][ $taxonomy ] = array_values( array_map( 'intval', $term_ids ) );
	return $term_ids;
}
function wp_get_object_terms( $id, $taxonomy, $args ) {
	$values = array();
	foreach ( $GLOBALS['native_relationships'][ (int) $id ][ $taxonomy ] ?? array() as $term_id ) {
		$values[] = $GLOBALS['native_terms'][ $taxonomy ][ $term_id ]->slug;
	}
	return $values;
}
function has_term( $slug, $taxonomy, $id ) { return in_array( $slug, wp_get_object_terms( $id, $taxonomy, array() ), true ); }
function wp_seed_events_get_event_occurrences( $id, $args = array() ) {
	$rows = $GLOBALS['native_occurrences'][ (int) $id ] ?? array();
	$rows = array_values( array_filter( $rows, static function ( $row ) {
		return empty( $row['is_cancelled'] ) && ( $row['start_sort'] ?? '' ) >= '2026-08-03 12:00';
	} ) );
	usort( $rows, static function ( $a, $b ) { return strcmp( $a['start_sort'], $b['start_sort'] ); } );
	return $rows;
}
function get_posts( $args ) {
	$ids = array_keys( $GLOBALS['native_posts'] );
	sort( $ids, SORT_NUMERIC );
	return $ids;
}
function wp_seed_events_get_event_data( $id ) { return array( 'id' => $id ); }

require dirname( __DIR__ ) . '/includes/public/classifications.php';

wp_seed_events_register_native_classifications();
native_assert( isset( $GLOBALS['native_taxonomies']['wp_seed_event_type'] ), 'Type taxonomy was not registered.' );
native_assert( true === $GLOBALS['native_taxonomies']['wp_seed_event_type']['show_in_rest'], 'Type taxonomy is absent from REST.' );
native_assert( true === $GLOBALS['native_taxonomies']['wp_seed_event_type']['public'], 'Type taxonomy is hidden from native taxonomy consumers.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_type']['publicly_queryable'], 'Type taxonomy became publicly queryable.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_type']['rewrite'], 'Type taxonomy gained public rewrite rules.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_type']['meta_box_cb'], 'Raw type metabox is visible.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_type']['show_admin_column'], 'Raw taxonomy duplicated the business type column.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_flag']['show_ui'], 'Technical flag UI is visible.' );
native_assert( true === $GLOBALS['native_taxonomies']['wp_seed_event_flag']['public'], 'Featured taxonomy is hidden from native taxonomy consumers.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_flag']['publicly_queryable'], 'Featured taxonomy became publicly queryable.' );
native_assert( false === $GLOBALS['native_taxonomies']['wp_seed_event_flag']['rewrite'], 'Featured taxonomy gained public rewrite rules.' );
native_assert( 'journee-decouverte' === wp_seed_events_native_event_type_slug( 'journee_decouverte' ), 'Discovery-day slug changed.' );
native_assert( 'reunion-information' === wp_seed_events_native_event_type_slug( 'reunion_information' ), 'Information-meeting slug changed.' );
native_assert( 'seminaire' === wp_seed_events_native_event_type_slug( 'seminaire' ), 'Seminar slug changed.' );

$GLOBALS['native_posts'][10] = new WP_Post( 10 );
$GLOBALS['native_meta'][10]['_wp_seed_event_pinned'] = '1';
$GLOBALS['native_types'][10] = array( 'atelier', 'stage' );
$GLOBALS['native_primary'][10] = 'atelier';
$GLOBALS['native_occurrences'][10] = array(
	array( 'start_sort' => '2026-07-01 10:00', 'is_cancelled' => false ),
	array( 'start_sort' => '2026-08-10 09:00', 'is_cancelled' => true ),
	array( 'start_sort' => '2026-08-11 09:00', 'is_cancelled' => false ),
	array( 'start_sort' => '2026-09-01 09:00', 'is_cancelled' => false ),
);

native_assert( true === wp_seed_events_sync_native_event_classifications( 10 ), 'Native classifications did not synchronize.' );
native_assert( array( 'atelier', 'stage' ) === wp_get_object_terms( 10, 'wp_seed_event_type', array() ), 'All event types were not projected.' );
native_assert( has_term( 'featured', 'wp_seed_event_flag', 10 ), 'Featured term was not projected.' );
$type_data = wp_seed_events_event_type_data_for_event( 10 );
native_assert( 'atelier' === $type_data['primary_type']['key'], 'Primary type was not explicit.' );
native_assert( 'stage' === $type_data['secondary_types'][0]['key'], 'Secondary type was not explicit.' );
native_assert( 2 === count( $type_data['all_types'] ), 'All types projection differs.' );
native_assert( '2026-08-11 09:00' === wp_seed_events_calculate_next_occurrence_sort( 10 ), 'Cancelled or past occurrence affected next sort.' );
native_assert( false !== strpos( file_get_contents( dirname( __DIR__ ) . '/includes/public/classifications.php' ), "'status'            => 'future'" ), 'Canonical future occurrence filter is absent.' );
native_assert( '2026-08-11 09:00' === wp_seed_events_sync_next_occurrence_sort( 10 ), 'Next sort was not persisted.' );

unset( $GLOBALS['native_meta'][10]['_wp_seed_event_pinned'] );
native_assert( true === wp_seed_events_sync_native_event_classifications( 10 ), 'Unpin synchronization failed.' );
native_assert( ! has_term( 'featured', 'wp_seed_event_flag', 10 ), 'Featured term survived unpinning.' );

$query = new WP_Query( array( 'post_type' => 'wp_seed_event', 'orderby' => 'wp_seed_next_occurrence', 'order' => 'DESC', 'wp_seed_next_occurrence_missing' => 'exclude' ) );
$clauses = wp_seed_events_order_query_by_next_occurrence( array( 'join' => '', 'where' => '', 'orderby' => '', 'distinct' => '' ), $query );
native_assert( false !== strpos( $clauses['join'], '_wp_seed_event_next_occurrence_sort' ), 'WP_Query projection join is absent.' );
native_assert( false !== strpos( $clauses['where'], "meta_id IS NOT NULL" ) && false !== strpos( $clauses['where'], "meta_value <> ''" ), 'Missing-date exclusion is absent.' );
native_assert( false !== strpos( $clauses['orderby'], 'meta_value DESC' ), 'Descending next-occurrence order is absent.' );
native_assert( false !== strpos( $clauses['orderby'], 'wp_posts.ID ASC' ), 'Deterministic ID tie-breaker is absent.' );

$GLOBALS['native_posts'][20] = new WP_Post( 20 );
$GLOBALS['native_occurrences'][20] = array();
$GLOBALS['native_ready'] = false;
$fallback = new WP_Query( array( 'post_type' => 'wp_seed_event', 'post_status' => 'publish', 'orderby' => 'wp_seed_next_occurrence', 'order' => 'ASC' ) );
wp_seed_events_prepare_next_occurrence_query( $fallback );
native_assert( 'post__in' === $fallback->get( 'orderby' ), 'Fallback did not replace SQL ordering.' );
native_assert( array( 10, 20 ) === $fallback->get( 'post__in' ), 'Fallback did not place undated events last.' );

native_assert( true === wp_seed_events_verify_native_classification_integrity(), 'Native projection integrity failed.' );
wp_seed_events_register_native_classification_rest_fields();
native_assert( isset( $GLOBALS['native_rest_fields']['wp_seed_event_classifications'] ), 'REST classification field is absent.' );
$rest_read = wp_seed_events_reject_native_classification_rest_writes( new WP_Post( 10 ), new Native_Request( 'GET', array( 'wp_seed_event_type' => array( 1 ) ) ) );
native_assert( $rest_read instanceof WP_Post, 'REST reads were unexpectedly rejected.' );
$rest_write = wp_seed_events_reject_native_classification_rest_writes( new WP_Post( 10 ), new Native_Request( 'POST', array( 'wp_seed_event_type' => array( 1 ) ) ) );
native_assert( $rest_write instanceof WP_Error && 'event_classification_projection_read_only' === $rest_write->get_error_code(), 'REST projection write was not rejected.' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/public/classifications.php' );
foreach ( array( 'wp_seed_event_type', 'wp_seed_event_flag', 'featured', 'wp_seed_next_occurrence', 'wp_seed_next_occurrence_missing', 'rest_pre_insert_wp_seed_event' ) as $token ) {
	native_assert( false !== strpos( $source, $token ), 'Public contract token missing: ' . $token );
}
native_assert( false === strpos( $source, 'et_builder' ), 'Divi-specific code entered the native contract.' );

echo 'Native classifications harness: ' . $GLOBALS['native_cases'] . '/' . $GLOBALS['native_cases'] . ' OK' . PHP_EOL;
