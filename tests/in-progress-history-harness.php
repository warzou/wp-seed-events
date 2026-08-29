<?php
/** Standalone contract for in-progress and undated events. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['iph_now']  = '2026-08-23 23:00';
$GLOBALS['iph_meta'] = array();
$GLOBALS['iph_cases'] = 0;

function add_action() {}
function add_filter() {}
function register_rest_field() {}
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function current_time( $format ) { return 'Y-m-d' === $format ? substr( $GLOBALS['iph_now'], 0, 10 ) : $GLOBALS['iph_now']; }
function get_post_meta( $event_id, $key, $single = true ) { unset( $single ); return $GLOBALS['iph_meta'][ (int) $event_id ][ $key ] ?? ''; }
function wp_seed_events_format_occurrence_date_line( $occurrence ) { return (string) $occurrence['start_date']; }
function wp_seed_events_format_occurrence_time_line( $occurrence ) { return (string) ( $occurrence['start_time'] ?? '' ); }

require dirname( __DIR__ ) . '/includes/public/occurrences.php';
require dirname( __DIR__ ) . '/includes/public/calendar.php';

function iph_assert( $condition, $message ) {
	++$GLOBALS['iph_cases'];
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$stage = array(
	'uid'        => '11111111-1111-4111-8111-111111111111',
	'start_date' => '2026-08-22',
	'end_date'   => '2026-08-28',
	'start_time' => '17:00',
	'end_time'   => '15:00',
);
$GLOBALS['iph_meta'][2339]['_wp_seed_event_occurrences'] = array( $stage );

$during = wp_seed_events_normalize_occurrence( $stage, 2339 );
iph_assert( $during['is_future'] && $during['is_in_progress'] && ! $during['is_past'], '22-28 August Stage must be upcoming on 23 August.' );
iph_assert( 'upcoming' === wp_seed_events_get_event_lifecycle( 2339 ), 'In-progress Stage lifecycle differs.' );
iph_assert( '2026-08-22 17:00' === wp_seed_events_get_next_active_occurrence( 2339 )['start_sort'], 'In-progress Stage is not the canonical next occurrence.' );

$GLOBALS['iph_now'] = '2026-08-28 15:00';
$at_boundary = wp_seed_events_normalize_occurrence( $stage, 2339 );
iph_assert( $at_boundary['is_future'] && $at_boundary['is_in_progress'] && ! $at_boundary['is_past'], 'An occurrence remains upcoming at its exact end boundary.' );

$GLOBALS['iph_now'] = '2026-08-29 00:00';
$after = wp_seed_events_normalize_occurrence( $stage, 2339 );
iph_assert( $after['is_past'] && ! $after['is_future'] && ! $after['is_in_progress'], 'Stage must be past after its end.' );
iph_assert( 'past' === wp_seed_events_get_event_lifecycle( 2339 ), 'Ended Stage lifecycle differs.' );

$GLOBALS['iph_now'] = '2026-08-23 12:00';
$all_day = wp_seed_events_normalize_occurrence( array( 'start_date' => '2026-08-23', 'all_day' => '1' ), 1 );
iph_assert( $all_day['is_future'] && ! $all_day['is_past'], 'All-day event must remain upcoming through 23:59.' );
$all_day_multiday = wp_seed_events_normalize_occurrence( array( 'start_date' => '2026-08-22', 'end_date' => '2026-08-23', 'all_day' => '1' ), 4 );
iph_assert( '2026-08-23 23:59' === $all_day_multiday['end_sort'] && $all_day_multiday['is_in_progress'], 'All-day multi-day event must use its final day at 23:59.' );
$ends_today = wp_seed_events_normalize_occurrence( array( 'start_date' => '2026-08-22', 'end_date' => '2026-08-23', 'start_time' => '09:00', 'end_time' => '17:30' ), 2 );
iph_assert( $ends_today['is_in_progress'], 'Timed event ending today must remain in progress before its end.' );
$tomorrow = wp_seed_events_normalize_occurrence( array( 'start_date' => '2026-08-24', 'start_time' => '09:00' ), 3 );
iph_assert( $tomorrow['is_future'] && ! $tomorrow['is_in_progress'], 'Tomorrow event classification differs.' );

$GLOBALS['iph_meta'][4]['_wp_seed_event_occurrences'] = array(
	array( 'start_date' => '2026-08-20', 'start_time' => '09:00', 'end_time' => '17:00' ),
	array( 'start_date' => '2026-08-22', 'end_date' => '2026-08-23', 'start_time' => '09:00', 'end_time' => '17:30' ),
	array( 'start_date' => '2026-08-24', 'start_time' => '09:00', 'end_time' => '17:00' ),
);
$multi_next = wp_seed_events_get_next_active_occurrence( 4 );
iph_assert( '2026-08-22 09:00' === $multi_next['start_sort'] && ! empty( $multi_next['is_in_progress'] ), 'Multiple occurrences must select the active occurrence before the future one.' );

$GLOBALS['iph_meta'][999]['_wp_seed_event_occurrences'] = array();
iph_assert( 'undated' === wp_seed_events_get_event_lifecycle( 999 ), 'Missing dates alone must remain undated.' );
iph_assert( array() === wp_seed_events_get_event_occurrences( 999 ), 'Missing dates manufactured an occurrence.' );
iph_assert( '' === wp_seed_events_generate_occurrences_ics( array( 'title' => 'Undated', 'url' => 'https://example.test/undated/' ), array() ), 'Missing dates manufactured an ICS payload.' );

echo 'In-progress events harness: ' . $GLOBALS['iph_cases'] . '/' . $GLOBALS['iph_cases'] . " PASS\n";
