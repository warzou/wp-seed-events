<?php
/** Standalone contract tests for scheduled and to-schedule events. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['programming_meta']  = array();
$GLOBALS['programming_posts'] = array();
$GLOBALS['programming_cases'] = 0;

class WP_Post {
	public $ID;
	public $post_type = 'wp_seed_event';
	public $post_status = 'publish';

	public function __construct( $id ) {
		$this->ID = (int) $id;
	}
}

function add_action() {}
function add_filter() {}
function register_rest_field() {}
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( str_replace( array( "\r\n", "\r" ), "\n", (string) $value ) ) ); }
function current_time( $format ) { return 'Y-m-d' === $format ? '2026-08-23' : '2026-08-23 12:00:00'; }
function get_post_meta( $event_id, $key, $single = true ) { unset( $single ); return $GLOBALS['programming_meta'][ (int) $event_id ][ $key ] ?? ''; }
function get_post( $event_id ) { return $GLOBALS['programming_posts'][ (int) $event_id ] ?? null; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function wp_seed_events_normalize_parcours_year() { return 0; }
function wp_seed_events_parcours_year_label() { return ''; }
function wp_seed_events_get_promotion() { return array(); }
function wp_seed_events_format_occurrence_date_line( $occurrence ) { return (string) ( $occurrence['start_date'] ?? '' ); }
function wp_seed_events_format_occurrence_time_line() { return ''; }
function is_wp_error() { return false; }

require dirname( __DIR__ ) . '/includes/public/programming.php';
require dirname( __DIR__ ) . '/includes/public/occurrences.php';
require dirname( __DIR__ ) . '/includes/public/collections.php';
require dirname( __DIR__ ) . '/includes/admin/occurrence-projection.php';

function programming_set_meta( $event_id, $key, $value ) {
	$GLOBALS['programming_meta'][ $event_id ][ $key ] = $value;
}

function programming_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function programming_case( $label, $callback ) {
	try {
		$callback();
		$GLOBALS['programming_cases']++;
		echo '[OK] ' . $label . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $label . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

$GLOBALS['programming_posts'][1] = new WP_Post( 1 );
$GLOBALS['programming_posts'][2] = new WP_Post( 2 );
$GLOBALS['programming_posts'][3] = new WP_Post( 3 );

programming_set_meta( 1, '_wp_seed_event_occurrences', array( array( 'start_date' => '2026-10-01', 'uid' => '11111111-1111-4111-8111-111111111111' ) ) );
programming_set_meta( 2, WP_SEED_EVENTS_PROGRAMMING_STATUS_META_KEY, 'to_schedule' );
programming_set_meta( 2, WP_SEED_EVENTS_PROGRAMMING_TEXT_META_KEY, "Deux jeudis\nDates à confirmer" );
programming_set_meta( 2, WP_SEED_EVENTS_PROGRAMMING_VISIBLE_UNTIL_META_KEY, '2026-12-31' );
programming_set_meta( 2, '_wp_seed_event_occurrences', array( array( 'start_date' => '2099-01-01' ) ) );
programming_set_meta( 3, WP_SEED_EVENTS_PROGRAMMING_STATUS_META_KEY, 'to_schedule' );
programming_set_meta( 3, WP_SEED_EVENTS_PROGRAMMING_VISIBLE_UNTIL_META_KEY, '2026-08-22' );

programming_case( 'legacy absent state remains scheduled until explicit migration', function () {
	programming_assert( 'scheduled' === wp_seed_events_get_programming_status( 1 ), 'legacy default changed' );
	programming_assert( 1 === count( wp_seed_events_get_event_occurrences( 1 ) ), 'scheduled occurrence missing' );
} );

programming_case( 'to-schedule is a hard occurrence and projection barrier', function () {
	programming_assert( array() === wp_seed_events_get_event_occurrences( 2 ), 'occurrence leaked' );
	programming_assert( array() === wp_seed_events_build_occurrence_projection_rows( 2 ), 'projection leaked' );
} );

programming_case( 'visible-until controls listing only', function () {
	programming_assert( wp_seed_events_programming_is_publicly_listable( 2 ), 'future cutoff missing' );
	programming_assert( ! wp_seed_events_programming_is_publicly_listable( 3 ), 'expired event listed' );
	$timing = wp_seed_events_public_collection_event_timing(
		array( 'id' => 2, 'programming_status' => 'to_schedule', 'occurrences' => array(), 'lifecycle' => 'undated' )
	);
	programming_assert( ! empty( $timing['is_to_schedule'] ) && empty( $timing['has_date'] ), 'cutoff became a business date' );
} );

programming_case( 'programming fields normalize without inventing a date', function () {
	$data = wp_seed_events_get_programming_data( 2 );
	programming_assert( 'to_schedule' === $data['status'], 'status differs' );
	programming_assert( "Deux jeudis\nDates à confirmer" === $data['text'], 'multiline text differs' );
	programming_assert( '2026-12-31' === $data['visible_until'], 'cutoff differs' );
	programming_assert( 'À programmer' === wp_seed_events_programming_status_label( 'to_schedule' ), 'label differs' );
} );

programming_case( 'date validation rejects impossible and malformed cutoffs', function () {
	programming_assert( wp_seed_events_is_valid_programming_date( '2026-12-31' ), 'valid date rejected' );
	programming_assert( ! wp_seed_events_is_valid_programming_date( '2026-02-30' ), 'impossible date accepted' );
	programming_assert( ! wp_seed_events_is_valid_programming_date( '31/12/2026' ), 'localized date accepted' );
} );

programming_case( 'transitions reject contradictory or incomplete to-schedule state', function () {
	programming_assert( 'has_occurrences' === wp_seed_events_validate_programming_state( 'to_schedule', 'Texte', '2026-12-31', array( array( 'start_date' => '2026-09-01' ) ) ), 'dated transition accepted' );
	programming_assert( 'missing_text' === wp_seed_events_validate_programming_state( 'to_schedule', '', '2026-12-31', array() ), 'empty text accepted' );
	programming_assert( 'missing_cutoff' === wp_seed_events_validate_programming_state( 'to_schedule', 'Texte', '', array() ), 'empty cutoff accepted' );
	programming_assert( '' === wp_seed_events_validate_programming_state( 'scheduled', '', '', array() ), 'scheduled zero-occurrence state rejected' );
	programming_assert( '' === wp_seed_events_validate_programming_state( 'to_schedule', 'Texte', '2026-12-31', array() ), 'valid transition rejected' );
} );

echo 'Programming state harness: ' . $GLOBALS['programming_cases'] . '/6 PASS' . PHP_EOL;
