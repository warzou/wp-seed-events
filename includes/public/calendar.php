<?php
/**
 * Public calendar downloads for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_occurrence_calendar_url( $event, $occurrence ) {
	if (
		empty( $event['id'] ) ||
		empty( $occurrence['id'] ) ||
		empty( $occurrence['is_active'] ) ||
		empty( $occurrence['is_future'] )
	) {
		return '';
	}

	return add_query_arg(
		array(
			'action'         => 'wp_seed_events_download_occurrence_ics',
			'event_id'       => absint( $event['id'] ),
			'occurrence_uid' => (string) $occurrence['id'],
		),
		admin_url( 'admin-post.php' )
	);
}

function wp_seed_events_render_occurrence_calendar_link( $event, $occurrence ) {
	$url = wp_seed_events_occurrence_calendar_url( $event, $occurrence );

	if ( '' === $url ) {
		return '';
	}

	return sprintf(
		'<a class="wp-seed-event-calendar-link" href="%1$s"><span aria-hidden="true">&#x1F5D3;&#xFE0F;</span> %2$s</a>',
		esc_url( $url ),
		esc_html__( 'Ajouter cette date au calendrier', 'wp-seed-events' )
	);
}

function wp_seed_events_handle_occurrence_ics_download() {
	$event_id      = isset( $_GET['event_id'] ) ? absint( $_GET['event_id'] ) : 0;
	$occurrence_id = isset( $_GET['occurrence_uid'] ) ? sanitize_text_field( wp_unslash( $_GET['occurrence_uid'] ) ) : '';
	$event          = wp_seed_events_get_event_data( $event_id );

	if ( array() === $event || '' === $occurrence_id ) {
		wp_seed_events_calendar_download_not_found();
	}

	$occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => false,
			'only_active'       => true,
			'status'            => 'future',
		)
	);
	$occurrence = array();

	foreach ( $occurrences as $candidate ) {
		if ( ! empty( $candidate['id'] ) && hash_equals( (string) $candidate['id'], $occurrence_id ) ) {
			$occurrence = $candidate;
			break;
		}
	}

	if ( array() === $occurrence ) {
		wp_seed_events_calendar_download_not_found();
	}

	$ics = wp_seed_events_generate_occurrence_ics( $event, $occurrence );

	if ( '' === $ics ) {
		wp_seed_events_calendar_download_not_found();
	}

	$filename = sanitize_file_name(
		sprintf(
			'event-%d-%s.ics',
			$event_id,
			(string) $occurrence['start_date']
		)
	);

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $ics ) );

	echo $ics; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Validated iCalendar download.
	exit;
}

function wp_seed_events_calendar_download_not_found() {
	status_header( 404 );
	nocache_headers();
	exit;
}

function wp_seed_events_generate_occurrence_ics( $event, $occurrence ) {
	if (
		empty( $event['title'] ) ||
		empty( $event['url'] ) ||
		empty( $occurrence['id'] ) ||
		empty( $occurrence['start_date'] ) ||
		empty( $occurrence['is_active'] ) ||
		empty( $occurrence['is_future'] )
	) {
		return '';
	}

	$date_lines = wp_seed_events_occurrence_ics_date_lines( $occurrence );

	if ( array() === $date_lines ) {
		return '';
	}

	$place    = isset( $event['place'] ) && is_array( $event['place'] ) ? $event['place'] : array();
	$location = implode(
		', ',
		array_filter(
			array(
				trim( (string) ( $place['name'] ?? '' ) ),
				trim( (string) ( $place['address'] ?? '' ) ),
			)
		)
	);
	$description = wp_seed_events_calendar_plain_text( $event['description'] ?? '' );
	$lines       = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//WP Seed//WP Seed Events//FR',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'BEGIN:VEVENT',
		'UID:' . wp_seed_events_ics_escape_text( (string) $occurrence['id'] ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
	);

	$lines   = array_merge( $lines, $date_lines );
	$lines[] = 'SUMMARY:' . wp_seed_events_ics_escape_text( (string) $event['title'] );

	if ( '' !== $description ) {
		$lines[] = 'DESCRIPTION:' . wp_seed_events_ics_escape_text( $description );
	}

	if ( '' !== $location ) {
		$lines[] = 'LOCATION:' . wp_seed_events_ics_escape_text( $location );
	}

	$lines[] = 'URL:' . esc_url_raw( (string) $event['url'] );
	$lines[] = 'END:VEVENT';
	$lines[] = 'END:VCALENDAR';

	$lines = array_map( 'wp_seed_events_ics_fold_line', $lines );

	return implode( "\r\n", $lines ) . "\r\n";
}

function wp_seed_events_occurrence_ics_date_lines( $occurrence ) {
	$start_date = (string) ( $occurrence['start_date'] ?? '' );
	$end_date   = (string) ( $occurrence['end_date'] ?? '' );
	$start_time = (string) ( $occurrence['start_time'] ?? '' );
	$end_time   = (string) ( $occurrence['end_time'] ?? '' );
	$timezone   = wp_timezone();

	if ( ! empty( $occurrence['all_day'] ) || '' === $start_time ) {
		$start = DateTimeImmutable::createFromFormat( '!Y-m-d', $start_date, $timezone );
		$end   = DateTimeImmutable::createFromFormat( '!Y-m-d', '' !== $end_date ? $end_date : $start_date, $timezone );

		if ( false === $start || false === $end ) {
			return array();
		}

		return array(
			'DTSTART;VALUE=DATE:' . $start->format( 'Ymd' ),
			'DTEND;VALUE=DATE:' . $end->modify( '+1 day' )->format( 'Ymd' ),
		);
	}

	$start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $start_date . ' ' . $start_time, $timezone );
	$end   = DateTimeImmutable::createFromFormat(
		'!Y-m-d H:i',
		( '' !== $end_date ? $end_date : $start_date ) . ' ' . ( '' !== $end_time ? $end_time : $start_time ),
		$timezone
	);

	if ( false === $start || false === $end ) {
		return array();
	}

	if ( $end <= $start ) {
		$end = $start->modify( '+1 hour' );
	}

	$utc = new DateTimeZone( 'UTC' );

	return array(
		'DTSTART:' . $start->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
		'DTEND:' . $end->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
	);
}

function wp_seed_events_calendar_plain_text( $value ) {
	$value = strip_shortcodes( (string) $value );
	$value = wp_strip_all_tags( $value );
	$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	return trim( preg_replace( '/\s+/u', ' ', $value ) );
}

function wp_seed_events_ics_escape_text( $value ) {
	$value = str_replace( array( "\r\n", "\r" ), "\n", (string) $value );
	$value = str_replace( '\\', '\\\\', $value );
	$value = str_replace( array( ';', ',', "\n" ), array( '\\;', '\\,', '\\n' ), $value );

	return $value;
}

function wp_seed_events_ics_fold_line( $line ) {
	if ( strlen( $line ) <= 75 ) {
		return $line;
	}

	$characters = preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY );

	if ( false === $characters ) {
		return $line;
	}

	$folded       = array();
	$current      = '';
	$current_size = 0;
	$limit        = 75;

	foreach ( $characters as $character ) {
		$size = strlen( $character );

		if ( '' !== $current && $current_size + $size > $limit ) {
			$folded[]     = $current;
			$current      = ' ';
			$current_size = 1;
		}

		$current      .= $character;
		$current_size += $size;
	}

	if ( '' !== $current ) {
		$folded[] = $current;
	}

	return implode( "\r\n", $folded );
}
