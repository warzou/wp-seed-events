<?php

define( 'ABSPATH', __DIR__ . '/' );

function add_action() {}
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return (string) $value; }
function get_post_type() { return ''; }
function get_post_status() { return ''; }
function get_the_ID() { return 0; }
function wp_seed_events_public_heading_level_option( $value ) { return $value; }
function wp_seed_events_public_date_scope_option( $value ) { return $value; }
function wp_seed_events_public_date_mode_option( $value ) { return $value; }
function wp_seed_events_public_date_format_option( $value ) { return $value; }

require_once dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-dates-block.php';

$cases = 0;

function gutenberg_dates_calendar_assert_same( $expected, $actual, $message ) {
	global $cases;
	++$cases;

	if ( $expected !== $actual ) {
		throw new RuntimeException( $message );
	}
}

function gutenberg_dates_calendar_option( $attributes ) {
	$options = wp_seed_events_gutenberg_event_dates_options( $attributes );

	return $options['show_calendar_links'];
}

gutenberg_dates_calendar_assert_same( true, gutenberg_dates_calendar_option( array() ), 'Old implicit default must remain enabled.' );
gutenberg_dates_calendar_assert_same( true, gutenberg_dates_calendar_option( array( 'show_calendar_links' => true ) ), 'Old explicit ON must remain enabled.' );
gutenberg_dates_calendar_assert_same( false, gutenberg_dates_calendar_option( array( 'show_calendar_links' => false ) ), 'Old explicit OFF must remain disabled.' );
gutenberg_dates_calendar_assert_same( false, gutenberg_dates_calendar_option( array( 'calendar_behavior_version' => 2 ) ), 'New block must disable integrated calendar actions.' );
gutenberg_dates_calendar_assert_same( true, gutenberg_dates_calendar_option( array( 'calendar_behavior_version' => '2' ) ), 'A malformed string sentinel must keep the legacy default.' );
gutenberg_dates_calendar_assert_same( true, gutenberg_dates_calendar_option( array( 'calendar_behavior_version' => 1 ) ), 'Unknown historical versions must keep the legacy default.' );

echo 'Gutenberg Dates calendar deprecation harness: ' . $cases . '/' . $cases . ' OK' . PHP_EOL;
