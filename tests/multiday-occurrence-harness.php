<?php
/**
 * Focused contract for one continuous timed occurrence spanning several days.
 *
 * Run with: php tests/multiday-occurrence-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

function wp_timezone() {
	return new DateTimeZone( 'UTC' );
}

function wp_seed_events_format_occurrence_date( $date ) {
	return (string) $date;
}

function wp_seed_events_format_occurrence_time_line( $occurrence ) {
	return (string) $occurrence['start_time'] . ' → ' . (string) $occurrence['end_time'];
}

function strip_shortcodes( $value ) {
	return (string) $value;
}

function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}

function esc_url_raw( $value ) {
	return (string) $value;
}

require dirname( __DIR__ ) . '/includes/public/calendar.php';
require dirname( __DIR__ ) . '/includes/public/rendering.php';

function multiday_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$occurrence = array(
	'id'         => 'continuous-stage',
	'uid'        => 'continuous-stage',
	'start_date' => '2026-09-09',
	'end_date'   => '2026-09-10',
	'start_time' => '09:00',
	'end_time'   => '17:30',
	'all_day'    => '',
	'is_active'  => true,
	'is_future'  => true,
);
$event = array(
	'title'       => 'Continuous stage',
	'url'         => 'https://example.test/stage/continuous-stage/',
	'description' => 'Two-day continuous stage.',
	'place'       => array( 'name' => 'Test place', 'address' => 'Test address' ),
);

$date_line = wp_seed_events_public_event_occurrence_date_line( $occurrence, 'long' );
$time_line = wp_seed_events_public_event_occurrence_time_line( $occurrence );
$ics       = wp_seed_events_generate_occurrence_ics( $event, $occurrence );

multiday_assert( '2026-09-09 → 2026-09-10' === $date_line, 'Date renderer did not use one start/end range.' );
multiday_assert( '09:00 → 17:30' === $time_line, 'Time renderer did not use one start/end range.' );
multiday_assert( 1 === substr_count( $ics, 'BEGIN:VEVENT' ), 'Continuous occurrence generated more than one VEVENT.' );
multiday_assert( 1 === substr_count( $ics, 'END:VEVENT' ), 'Continuous occurrence generated an invalid VEVENT end.' );
multiday_assert( false !== strpos( $ics, 'DTSTART:20260909T090000Z' ), 'Timed multi-day DTSTART differs.' );
multiday_assert( false !== strpos( $ics, 'DTEND:20260910T173000Z' ), 'Timed multi-day DTEND differs.' );
multiday_assert( false === strpos( $ics, 'DTSTART;VALUE=DATE' ), 'Timed occurrence became all-day.' );
multiday_assert( false === strpos( $ics, 'DTEND;VALUE=DATE' ), 'Timed occurrence end became all-day.' );

echo "Multi-day occurrence harness: 8/8 OK\n";
