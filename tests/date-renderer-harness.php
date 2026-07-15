<?php
/**
 * Standalone assertions for the shared public event dates renderer and shortcode adapter.
 *
 * Run with: php tests/date-renderer-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['wp_seed_events_harness_event_data']       = array();
$GLOBALS['wp_seed_events_harness_event_data_calls'] = 0;
$GLOBALS['wp_seed_events_harness_occurrence_calls'] = 0;
$GLOBALS['wp_seed_events_harness_case_count']       = 0;
$GLOBALS['wp_seed_events_harness_current_post_id']  = 0;
$GLOBALS['wp_seed_events_harness_post_types']       = array();

function absint( $value ) {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$out = $pairs;

	foreach ( is_array( $atts ) ? $atts : array() as $name => $value ) {
		if ( array_key_exists( $name, $pairs ) ) {
			$out[ $name ] = $value;
		}
	}

	return $out;
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_attr( $value ) {
	return esc_html( $value );
}

function esc_url( $value ) {
	return esc_attr( $value );
}

function esc_url_raw( $value ) {
	return (string) $value;
}

function esc_html__( $value ) {
	return esc_html( $value );
}

function esc_attr__( $value ) {
	return esc_attr( $value );
}

function wp_kses_post( $value ) {
	return (string) $value;
}

function admin_url( $path = '' ) {
	return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
}

function add_query_arg( $args, $url ) {
	return (string) $url . ( false === strpos( (string) $url, '?' ) ? '?' : '&' ) . http_build_query( $args );
}

function wp_timezone() {
	return new DateTimeZone( 'UTC' );
}

function get_the_ID() {
	return (int) $GLOBALS['wp_seed_events_harness_current_post_id'];
}

function get_post_type( $post_id = 0 ) {
	return $GLOBALS['wp_seed_events_harness_post_types'][ absint( $post_id ) ] ?? false;
}

function date_i18n( $format, $timestamp ) {
	return gmdate( (string) $format, (int) $timestamp );
}

function strip_shortcodes( $value ) {
	return (string) $value;
}

function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}

function wp_seed_events_format_occurrence_date( $date ) {
	return (string) $date;
}

function wp_seed_events_format_occurrence_date_line( $occurrence ) {
	$start = (string) ( $occurrence['start_date'] ?? '' );
	$end   = (string) ( $occurrence['end_date'] ?? '' );

	return '' !== $end && $end !== $start ? $start . ' -> ' . $end : $start;
}

function wp_seed_events_format_occurrence_time_line( $occurrence ) {
	if ( ! empty( $occurrence['all_day'] ) ) {
		return 'Toute la journee';
	}

	$start = (string) ( $occurrence['start_time'] ?? '' );
	$end   = (string) ( $occurrence['end_time'] ?? '' );

	if ( '' !== $start && '' !== $end ) {
		return $start . ' -> ' . $end;
	}

	if ( '' !== $start ) {
		return 'A partir de ' . $start;
	}

	return '';
}

function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['wp_seed_events_harness_event_data_calls']++;

	return $GLOBALS['wp_seed_events_harness_event_data'][ absint( $event_id ) ] ?? array();
}

function wp_seed_events_get_event_occurrences( $event_id, $args = array() ) {
	$GLOBALS['wp_seed_events_harness_occurrence_calls']++;

	return array();
}

require dirname( __DIR__ ) . '/includes/public/calendar.php';
require dirname( __DIR__ ) . '/includes/public/rendering.php';

function wp_seed_events_harness_occurrence( $id, $date, $state = 'future', $cancelled = false, $options = array() ) {
	$options = array_merge(
		array(
			'end_date'   => '',
			'start_time' => '',
			'end_time'   => '',
			'all_day'    => false,
		),
		$options
	);
	$is_future = 'future' === $state;
	$is_past   = 'past' === $state;

	return array(
		'id'             => (string) $id,
		'uid'            => (string) $id,
		'start_date'     => (string) $date,
		'end_date'       => (string) $options['end_date'],
		'start_time'     => (string) $options['start_time'],
		'end_time'       => (string) $options['end_time'],
		'all_day'        => $options['all_day'] ? '1' : '',
		'cancelled'      => $cancelled ? '1' : '',
		'is_active'      => ! $cancelled,
		'is_date_future' => $is_future,
		'is_date_past'   => $is_past,
		'is_future'      => ! $cancelled && $is_future,
		'is_past'        => ! $cancelled && $is_past,
		'is_cancelled'   => $cancelled,
	);
}

function wp_seed_events_harness_event( $occurrences ) {
	return array(
		'id'          => 42,
		'title'       => 'Test event',
		'url'         => 'https://example.test/events/test-event/',
		'description' => 'Description',
		'place'       => array(
			'name'    => 'Test place',
			'address' => '1 Test street',
		),
		'occurrences' => $occurrences,
	);
}

function wp_seed_events_harness_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function wp_seed_events_harness_contains( $needle, $haystack, $message ) {
	wp_seed_events_harness_assert( false !== strpos( $haystack, $needle ), $message );
}

function wp_seed_events_harness_not_contains( $needle, $haystack, $message ) {
	wp_seed_events_harness_assert( false === strpos( $haystack, $needle ), $message );
}

function wp_seed_events_harness_case( $name, $callback ) {
	try {
		$callback();
		$GLOBALS['wp_seed_events_harness_case_count']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

$future       = wp_seed_events_harness_occurrence( 'future-1', '2026-07-31', 'future', false, array( 'start_time' => '19:30' ) );
$future_two   = wp_seed_events_harness_occurrence( 'future-2', '2026-08-13', 'future', false, array( 'start_time' => '20:00', 'end_time' => '22:00' ) );
$past         = wp_seed_events_harness_occurrence( 'past-1', '2026-01-05', 'past', false, array( 'start_time' => '18:00' ) );
$cancelled_up = wp_seed_events_harness_occurrence( 'cancelled-future', '2026-09-10', 'future', true );
$cancelled_old = wp_seed_events_harness_occurrence( 'cancelled-past', '2025-12-10', 'past', true );
$all_day      = wp_seed_events_harness_occurrence( 'all-day', '2026-10-10', 'future', false, array( 'all_day' => true ) );

wp_seed_events_harness_case(
	'1. no occurrence or invalid occurrence',
	function () {
		wp_seed_events_harness_assert( '' === wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array() ) ), 'Empty event must not render a container.' );
		$invalid = array( 'start_date' => '2026-99-99' );
		wp_seed_events_harness_assert( '' === wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $invalid ) ) ), 'Invalid date must not render a partial container.' );
		$invalid_end = wp_seed_events_harness_occurrence( 'invalid-end', '2026-07-31', 'future', false, array( 'end_date' => '2026-99-99' ) );
		wp_seed_events_harness_assert( '' === wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $invalid_end ) ) ), 'Invalid end date must not render a partial container.' );
	}
);

wp_seed_events_harness_case(
	'2. one active future occurrence',
	function () use ( $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ) );
		wp_seed_events_harness_contains( 'is-future', $html, 'Future state class is missing.' );
		wp_seed_events_harness_contains( 'datetime="2026-07-31"', $html, 'DateTime attribute is missing.' );
		wp_seed_events_harness_contains( 'occurrence_uid=future-1', $html, 'Individual calendar link is missing.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-calendar-link--all', $html, 'Global link must not appear for one date.' );
	}
);

wp_seed_events_harness_case(
	'3. several active future occurrences',
	function () use ( $future, $future_two ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future, $future_two ) ) );
		wp_seed_events_harness_contains( 'wp-seed-event-calendar-link--all', $html, 'Global calendar link is missing.' );
		wp_seed_events_harness_assert( 2 === substr_count( $html, 'occurrence_uid=' ), 'Each future occurrence needs one individual link.' );
	}
);

wp_seed_events_harness_case(
	'4. past only',
	function () use ( $past ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $past ) ) );
		wp_seed_events_harness_contains( 'is-past', $html, 'Past state class is missing.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-calendar-link', $html, 'Past occurrence must not expose calendar links.' );
	}
);

wp_seed_events_harness_case(
	'5. canonical past then future order is preserved',
	function () use ( $past, $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $past, $future ) ) );
		wp_seed_events_harness_assert( strpos( $html, '2026-01-05' ) < strpos( $html, '2026-07-31' ), 'Renderer changed canonical API order.' );
	}
);

wp_seed_events_harness_case(
	'6. cancelled future only',
	function () use ( $cancelled_up ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $cancelled_up ) ), array( 'scope' => 'upcoming' ) );
		wp_seed_events_harness_contains( 'is-future', $html, 'Cancelled future occurrence must remain chronologically future.' );
		wp_seed_events_harness_contains( 'is-cancelled', $html, 'Cancelled state class is missing.' );
		wp_seed_events_harness_contains( 'wp-seed-event-date__status', $html, 'Visible cancelled status is missing.' );
		wp_seed_events_harness_contains( 'Annul', $html, 'Visible cancelled label is missing.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-calendar-link', $html, 'Cancelled occurrence must not expose calendar links.' );
	}
);

wp_seed_events_harness_case(
	'7. cancelled past only',
	function () use ( $cancelled_old ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $cancelled_old ) ), array( 'scope' => 'past' ) );
		wp_seed_events_harness_contains( 'is-past', $html, 'Cancelled past occurrence must remain chronologically past.' );
		wp_seed_events_harness_contains( 'is-cancelled', $html, 'Cancelled past state is missing.' );
	}
);

wp_seed_events_harness_case(
	'8. active and cancelled occurrences',
	function () use ( $future, $cancelled_up ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future, $cancelled_up ) ) );
		wp_seed_events_harness_assert( 2 === substr_count( $html, '<li class="wp-seed-event-date' ), 'Both occurrences must render.' );
		wp_seed_events_harness_assert( 1 === substr_count( $html, 'occurrence_uid=' ), 'Only active future occurrence is exportable.' );
	}
);

wp_seed_events_harness_case(
	'9. all-day occurrence',
	function () use ( $all_day ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $all_day ) ) );
		wp_seed_events_harness_contains( 'is-all-day', $html, 'All-day state class is missing.' );
		wp_seed_events_harness_contains( 'Toute la journee', $html, 'All-day label is missing.' );
	}
);

wp_seed_events_harness_case(
	'10. start time only',
	function () use ( $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ) );
		wp_seed_events_harness_contains( 'A partir de 19:30', $html, 'Existing start-only time formatter is not reused.' );
	}
);

wp_seed_events_harness_case(
	'11. time range',
	function () use ( $future_two ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future_two ) ) );
		wp_seed_events_harness_contains( '20:00 -&gt; 22:00', $html, 'Existing time range formatter is not reused.' );
	}
);

wp_seed_events_harness_case(
	'12. today uses neutral future projection',
	function () {
		$today = wp_seed_events_harness_occurrence( 'today', '2026-07-14', 'future' );
		$html  = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $today ) ), array( 'scope' => 'upcoming' ) );
		wp_seed_events_harness_contains( 'is-future', $html, 'Today must be rendered as date-future.' );
	}
);

wp_seed_events_harness_case(
	'13. scope all',
	function () use ( $past, $future, $cancelled_up ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $past, $future, $cancelled_up ) ), array( 'scope' => 'all' ) );
		wp_seed_events_harness_assert( 3 === substr_count( $html, '<li class="wp-seed-event-date' ), 'Scope all must retain every valid occurrence.' );
	}
);

wp_seed_events_harness_case(
	'14. scope upcoming includes cancelled future',
	function () use ( $past, $future, $cancelled_up ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $past, $future, $cancelled_up ) ), array( 'scope' => 'upcoming' ) );
		wp_seed_events_harness_not_contains( '2026-01-05', $html, 'Past occurrence leaked into upcoming scope.' );
		wp_seed_events_harness_contains( '2026-07-31', $html, 'Active future occurrence is missing.' );
		wp_seed_events_harness_contains( '2026-09-10', $html, 'Cancelled future occurrence is missing.' );
	}
);

wp_seed_events_harness_case(
	'15. scope past includes cancelled past',
	function () use ( $past, $future, $cancelled_old ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $past, $future, $cancelled_old ) ), array( 'scope' => 'past' ) );
		wp_seed_events_harness_not_contains( '2026-07-31', $html, 'Future occurrence leaked into past scope.' );
		wp_seed_events_harness_contains( '2026-01-05', $html, 'Active past occurrence is missing.' );
		wp_seed_events_harness_contains( '2025-12-10', $html, 'Cancelled past occurrence is missing.' );
	}
);

wp_seed_events_harness_case(
	'16. show_cancelled false',
	function () use ( $future, $cancelled_up ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future, $cancelled_up ) ), array( 'show_cancelled' => false ) );
		wp_seed_events_harness_not_contains( '2026-09-10', $html, 'Cancelled occurrence was not excluded.' );
		wp_seed_events_harness_assert( '' === wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $cancelled_up ) ), array( 'show_cancelled' => false ) ), 'Cancelled-only filtered result must not render a container.' );
	}
);

wp_seed_events_harness_case(
	'17. show_times false',
	function () use ( $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ), array( 'show_times' => false ) );
		wp_seed_events_harness_not_contains( 'wp-seed-event-date__time', $html, 'Time element must be absent.' );
		wp_seed_events_harness_not_contains( '19:30', $html, 'Time text must be absent.' );
		$legacy = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ), array( 'show_time' => false ) );
		wp_seed_events_harness_not_contains( 'wp-seed-event-date__time', $legacy, 'Legacy show_time alias must remain compatible.' );
	}
);

wp_seed_events_harness_case(
	'18. show_calendar_links false',
	function () use ( $future, $future_two ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future, $future_two ) ), array( 'show_calendar_links' => false ) );
		wp_seed_events_harness_not_contains( 'wp-seed-event-calendar-link', $html, 'All calendar links must be absent.' );
	}
);

wp_seed_events_harness_case(
	'19. empty title',
	function () use ( $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ), array( 'title' => '' ) );
		wp_seed_events_harness_not_contains( 'wp-seed-event-dates__title', $html, 'Heading must be absent.' );
		wp_seed_events_harness_contains( 'aria-label=', $html, 'Untitled section needs an accessible label.' );
	}
);

wp_seed_events_harness_case(
	'20. invalid heading and escaped title',
	function () use ( $future ) {
		$html = wp_seed_events_render_public_event_dates_section( wp_seed_events_harness_event( array( $future ) ), array( 'heading_level' => 'h1', 'title' => '<script>Dates</script>' ) );
		wp_seed_events_harness_contains( '<h2 class="wp-seed-event-dates__title">', $html, 'Invalid heading must fall back to h2.' );
		wp_seed_events_harness_contains( '&lt;script&gt;Dates&lt;/script&gt;', $html, 'Title must be escaped.' );
		wp_seed_events_harness_not_contains( '<script>', $html, 'Raw title HTML must not be rendered.' );
	}
);

wp_seed_events_harness_case(
	'Event Data is resolved once and occurrences are not reread',
	function () use ( $future, $future_two ) {
		$GLOBALS['wp_seed_events_harness_event_data'][99] = wp_seed_events_harness_event( array( $future, $future_two ) );
		$GLOBALS['wp_seed_events_harness_event_data'][99]['id'] = 99;
		$GLOBALS['wp_seed_events_harness_event_data_calls'] = 0;
		$GLOBALS['wp_seed_events_harness_occurrence_calls'] = 0;
		wp_seed_events_render_public_event_dates_section( 99 );
		wp_seed_events_harness_assert( 1 === $GLOBALS['wp_seed_events_harness_event_data_calls'], 'Renderer must resolve Event Data exactly once.' );
		wp_seed_events_harness_assert( 0 === $GLOBALS['wp_seed_events_harness_occurrence_calls'], 'Calendar helper reread occurrences.' );
	}
);

wp_seed_events_harness_case(
	'ICS format remains compatible and excludes ineligible occurrences',
	function () use ( $future, $future_two, $past, $cancelled_up ) {
		$ics = wp_seed_events_generate_occurrences_ics( wp_seed_events_harness_event( array() ), array( $future, $past, $cancelled_up, $future_two ) );
		wp_seed_events_harness_assert( 2 === substr_count( $ics, 'BEGIN:VEVENT' ), 'ICS must contain only the two active future VEVENT entries.' );
		wp_seed_events_harness_contains( 'UID:future-1', $ics, 'First stable UID is missing.' );
		wp_seed_events_harness_contains( 'UID:future-2', $ics, 'Second stable UID is missing.' );
		wp_seed_events_harness_not_contains( 'UID:past-1', $ics, 'Past occurrence leaked into ICS.' );
		wp_seed_events_harness_not_contains( 'UID:cancelled-future', $ics, 'Cancelled occurrence leaked into ICS.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode resolves an explicit event once',
	function () use ( $future, $future_two ) {
		$GLOBALS['wp_seed_events_harness_event_data'][99] = wp_seed_events_harness_event( array( $future, $future_two ) );
		$GLOBALS['wp_seed_events_harness_event_data'][99]['id'] = 99;
		$GLOBALS['wp_seed_events_harness_event_data_calls'] = 0;
		$html = wp_seed_events_event_dates_shortcode( array( 'id' => '99' ) );
		wp_seed_events_harness_assert( 1 === $GLOBALS['wp_seed_events_harness_event_data_calls'], 'Shortcode must resolve Event Data exactly once.' );
		wp_seed_events_harness_contains( '<h2 class="wp-seed-event-dates__title">Dates</h2>', $html, 'Default shortcode title or heading is incorrect.' );
		wp_seed_events_harness_assert( 1 === substr_count( $html, 'wp-seed-event-section--dates' ), 'Shortcode duplicated the renderer section.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode uses the public event context',
	function () use ( $future ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $future ) );
		$GLOBALS['wp_seed_events_public_event_id'] = 42;
		$html = wp_seed_events_event_dates_shortcode( array() );
		$GLOBALS['wp_seed_events_public_event_id'] = 0;
		wp_seed_events_harness_contains( '2026-07-31', $html, 'Public event context was not resolved.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode uses the current event post context',
	function () use ( $past ) {
		$GLOBALS['wp_seed_events_harness_event_data'][77] = wp_seed_events_harness_event( array( $past ) );
		$GLOBALS['wp_seed_events_harness_event_data'][77]['id'] = 77;
		$GLOBALS['wp_seed_events_harness_current_post_id'] = 77;
		$GLOBALS['wp_seed_events_harness_post_types'][77] = 'wp_seed_event';
		$html = wp_seed_events_event_dates_shortcode( array() );
		$GLOBALS['wp_seed_events_harness_current_post_id'] = 0;
		wp_seed_events_harness_contains( '2026-01-05', $html, 'Current event post context was not resolved.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode returns empty without an event context',
	function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 0;
		$GLOBALS['wp_seed_events_harness_current_post_id'] = 12;
		$GLOBALS['wp_seed_events_harness_post_types'][12] = 'page';
		$html = wp_seed_events_event_dates_shortcode( array() );
		$GLOBALS['wp_seed_events_harness_current_post_id'] = 0;
		wp_seed_events_harness_assert( '' === $html, 'Ordinary page without event context must render nothing.' );
	}
);

wp_seed_events_harness_case(
	'Invalid explicit ID does not fall back to context',
	function () use ( $future ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $future ) );
		$GLOBALS['wp_seed_events_public_event_id'] = 42;
		$html = wp_seed_events_event_dates_shortcode( array( 'id' => 'invalid' ) );
		$GLOBALS['wp_seed_events_public_event_id'] = 0;
		wp_seed_events_harness_assert( '' === $html, 'Invalid explicit ID must not fall back to another event.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode maps scope and cancellation options',
	function () use ( $past, $future, $cancelled_up ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $past, $future, $cancelled_up ) );
		$html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'scope' => 'upcoming', 'show_cancelled' => 'no' ) );
		wp_seed_events_harness_not_contains( '2026-01-05', $html, 'Past occurrence leaked through shortcode scope.' );
		wp_seed_events_harness_not_contains( '2026-09-10', $html, 'Cancelled occurrence leaked through shortcode option.' );
		wp_seed_events_harness_contains( '2026-07-31', $html, 'Future occurrence is missing from shortcode output.' );
		$past_html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'scope' => 'past' ) );
		wp_seed_events_harness_contains( '2026-01-05', $past_html, 'Past occurrence is missing from shortcode past scope.' );
		wp_seed_events_harness_not_contains( '2026-07-31', $past_html, 'Future occurrence leaked into shortcode past scope.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode maps title heading times and calendar options',
	function () use ( $future, $future_two ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $future, $future_two ) );
		$html = wp_seed_events_event_dates_shortcode(
			array(
				'id'                  => '42',
				'title'               => 'Agenda',
				'heading_level'       => 'h4',
				'show_times'          => 'no',
				'show_calendar_links' => 'no',
			)
		);
		wp_seed_events_harness_contains( '<h4 class="wp-seed-event-dates__title">Agenda</h4>', $html, 'Custom title or heading was not mapped.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-date__time', $html, 'show_times=no was ignored.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-calendar-link', $html, 'show_calendar_links=no was ignored.' );

		foreach ( array( 'h3', 'h4', 'h5', 'h6' ) as $heading_level ) {
			$heading_html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'heading_level' => $heading_level ) );
			wp_seed_events_harness_contains( '<' . $heading_level . ' class="wp-seed-event-dates__title">', $heading_html, 'Allowed shortcode heading was not preserved.' );
		}
	}
);

wp_seed_events_harness_case(
	'Shortcode fallbacks are safe',
	function () use ( $past, $future ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $past, $future ) );
		$html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'scope' => 'invalid', 'heading_level' => 'h1', 'show_times' => 'invalid' ) );
		wp_seed_events_harness_contains( '2026-01-05', $html, 'Invalid scope did not fall back to all.' );
		wp_seed_events_harness_contains( '2026-07-31', $html, 'Invalid scope did not keep future occurrence.' );
		wp_seed_events_harness_contains( '<h2 class="wp-seed-event-dates__title">', $html, 'Invalid heading did not fall back to h2.' );
		wp_seed_events_harness_contains( 'wp-seed-event-date__time', $html, 'Invalid yes/no value did not use the safe default.' );
	}
);

wp_seed_events_harness_case(
	'Non-scalar shortcode attributes use safe fallbacks without warnings',
	function () use ( $future ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $future ) );
		set_error_handler(
			function ( $severity, $message ) {
				throw new ErrorException( $message, 0, $severity );
			}
		);
		try {
			$html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'scope' => array(), 'heading_level' => array(), 'show_times' => array(), 'format' => array() ) );
		} finally {
			restore_error_handler();
		}
		wp_seed_events_harness_contains( '<h2 class="wp-seed-event-dates__title">', $html, 'Non-scalar attributes did not use safe defaults.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode keeps historical aliases with new attribute precedence',
	function () use ( $future ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $future ) );
		$legacy = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'format' => 'short', 'show_time' => 'no' ) );
		wp_seed_events_harness_contains( '31/07/2026', $legacy, 'Historical format attribute is no longer supported.' );
		wp_seed_events_harness_not_contains( 'wp-seed-event-date__time', $legacy, 'Historical show_time alias is no longer supported.' );
		$precedence = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'show_time' => 'no', 'show_times' => 'yes' ) );
		wp_seed_events_harness_contains( 'wp-seed-event-date__time', $precedence, 'New show_times attribute must override show_time.' );
	}
);

wp_seed_events_harness_case(
	'Shortcode supports an empty title and empty filtered results',
	function () use ( $cancelled_up ) {
		$GLOBALS['wp_seed_events_harness_event_data'][42] = wp_seed_events_harness_event( array( $cancelled_up ) );
		$html = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'title' => '' ) );
		wp_seed_events_harness_not_contains( 'wp-seed-event-dates__title', $html, 'Empty shortcode title must hide the heading.' );
		wp_seed_events_harness_contains( 'aria-label=', $html, 'Empty shortcode title needs an accessible label.' );
		$empty = wp_seed_events_event_dates_shortcode( array( 'id' => '42', 'show_cancelled' => 'no' ) );
		wp_seed_events_harness_assert( '' === $empty, 'Filtered shortcode without occurrences must render nothing.' );
	}
);

echo sprintf( '%d test groups passed.%s', $GLOBALS['wp_seed_events_harness_case_count'], PHP_EOL );
