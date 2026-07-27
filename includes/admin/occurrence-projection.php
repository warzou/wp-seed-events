<?php
/**
 * Reconstructible occurrence projection used by lifecycle v3.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_occurrence_projection_table_name() {
	global $wpdb;

	return $wpdb->prefix . 'wp_seed_event_occurrences';
}

function wp_seed_events_occurrence_projection_schema() {
	global $wpdb;

	$table_name      = wp_seed_events_occurrence_projection_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	return "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		event_id bigint(20) unsigned NOT NULL,
		occurrence_uid varchar(64) NOT NULL,
		occurrence_index int(10) unsigned NOT NULL DEFAULT 0,
		promotion_id bigint(20) unsigned NOT NULL DEFAULT 0,
		parcours_year tinyint(3) unsigned NOT NULL DEFAULT 0,
		start_raw varchar(16) NOT NULL DEFAULT '',
		end_raw varchar(16) NOT NULL DEFAULT '',
		start_sort varchar(16) NOT NULL DEFAULT '',
		end_sort varchar(16) NOT NULL DEFAULT '',
		is_cancelled tinyint(1) unsigned NOT NULL DEFAULT 0,
		event_type varchar(64) NOT NULL DEFAULT '',
		event_status varchar(20) NOT NULL DEFAULT '',
		is_pinned tinyint(1) unsigned NOT NULL DEFAULT 0,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY event_occurrence (event_id,occurrence_uid),
		KEY event_id (event_id),
		KEY promotion_id (promotion_id),
		KEY parcours_year (parcours_year),
		KEY start_sort (start_sort),
		KEY is_cancelled (is_cancelled),
		KEY event_type (event_type),
		KEY promotion_year_start (promotion_id,parcours_year,is_cancelled,start_sort),
		KEY collection_filter (event_type,event_status,is_cancelled,start_sort)
	) {$charset_collate};";
}

function wp_seed_events_occurrence_projection_table_exists() {
	global $wpdb;

	$table_name = wp_seed_events_occurrence_projection_table_name();
	$found      = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

	return $table_name === $found;
}

function wp_seed_events_install_occurrence_projection_table() {
	if ( ! function_exists( 'dbDelta' ) ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	}

	dbDelta( wp_seed_events_occurrence_projection_schema() );

	return wp_seed_events_occurrence_projection_table_exists();
}

/**
 * Return the canonical event type projected on each occurrence row.
 *
 * Secondary types remain available through the existing event-level index.
 */
function wp_seed_events_occurrence_projection_event_type( $event_id ) {
	$event_type = wp_seed_events_primary_type_for_event( $event_id );

	if ( '' !== $event_type ) {
		return sanitize_key( $event_type );
	}

	$event_types = wp_seed_events_event_type_keys_for_event( $event_id );

	return array() === $event_types
		? sanitize_key( wp_seed_events_default_event_type_key() )
		: sanitize_key( reset( $event_types ) );
}

/**
 * Build a stable projection UID without mutating legacy occurrence storage.
 *
 * Persisted UUIDs remain authoritative. Legacy occurrences use a content
 * fingerprint; only strictly identical duplicates require an ordinal suffix.
 */
function wp_seed_events_occurrence_projection_uid( $occurrence, $event_id, $duplicate_ordinal = 1 ) {
	$uid = wp_seed_events_sanitize_occurrence_uid( $occurrence['uid'] ?? '' );

	if ( '' !== $uid ) {
		return $uid;
	}

	$fingerprint = array(
		'event_id'      => absint( $event_id ),
		'start_date'    => (string) ( $occurrence['start_date'] ?? '' ),
		'end_date'      => (string) ( $occurrence['end_date'] ?? '' ),
		'start_time'    => (string) ( $occurrence['start_time'] ?? '' ),
		'end_time'      => (string) ( $occurrence['end_time'] ?? '' ),
		'all_day'       => ! empty( $occurrence['all_day'] ) ? '1' : '',
		'cancelled'     => ! empty( $occurrence['is_cancelled'] ) ? '1' : '',
		'promotion_id'  => absint( $occurrence['promotion_id'] ?? 0 ),
		'parcours_year' => absint( $occurrence['parcours_year'] ?? 0 ),
	);
	$hash        = substr( hash( 'sha256', wp_json_encode( $fingerprint ) ), 0, 48 );

	return 'legacy-' . $hash . '-' . max( 1, absint( $duplicate_ordinal ) );
}

function wp_seed_events_occurrence_projection_raw_datetime( $date, $time, $fallback_time ) {
	$date = (string) $date;

	if ( '' === $date ) {
		return '';
	}

	$time = '' !== (string) $time ? (string) $time : (string) $fallback_time;

	return $date . ' ' . $time;
}

/**
 * Build exact projection rows from the canonical serialized occurrence array.
 */
function wp_seed_events_build_occurrence_projection_rows( $event_id ) {
	$event_id = absint( $event_id );
	$event    = get_post( $event_id );

	if ( 0 === $event_id || ! $event instanceof WP_Post || 'wp_seed_event' !== $event->post_type ) {
		return new WP_Error( 'occurrence_projection_invalid_event', 'Occurrence projection requires a valid event.' );
	}

	$raw_occurrences = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );
	$raw_occurrences = is_array( $raw_occurrences ) ? $raw_occurrences : array();
	$event_type      = wp_seed_events_occurrence_projection_event_type( $event_id );
	$is_pinned       = '1' === (string) get_post_meta( $event_id, '_wp_seed_event_pinned', true );
	$updated_at      = current_time( 'mysql', true );
	$duplicates      = array();
	$rows            = array();

	foreach ( $raw_occurrences as $occurrence_index => $raw_occurrence ) {
		$occurrence = wp_seed_events_normalize_occurrence( $raw_occurrence, $event_id, (int) $occurrence_index );

		if ( array() === $occurrence ) {
			continue;
		}

		$fingerprint_uid = wp_seed_events_occurrence_projection_uid( $occurrence, $event_id, 1 );
		$duplicates[ $fingerprint_uid ] = ( $duplicates[ $fingerprint_uid ] ?? 0 ) + 1;

		if ( '' !== (string) ( $occurrence['uid'] ?? '' ) && 1 < $duplicates[ $fingerprint_uid ] ) {
			return new WP_Error( 'occurrence_projection_duplicate_uid', 'Occurrence projection contains a duplicate UID.' );
		}

		$occurrence_uid = '' !== (string) ( $occurrence['uid'] ?? '' )
			? $fingerprint_uid
			: wp_seed_events_occurrence_projection_uid( $occurrence, $event_id, $duplicates[ $fingerprint_uid ] );
		$all_day       = ! empty( $occurrence['all_day'] );
		$start_time    = $all_day ? '00:00' : (string) ( $occurrence['start_time'] ?? '' );
		$end_time      = $all_day ? '23:59' : (string) ( $occurrence['end_time'] ?? '' );
		$start_raw     = wp_seed_events_occurrence_projection_raw_datetime( $occurrence['start_date'], $start_time, '00:00' );
		$end_raw       = wp_seed_events_occurrence_projection_raw_datetime(
			'' !== (string) $occurrence['end_date'] ? $occurrence['end_date'] : $occurrence['start_date'],
			$end_time,
			'' !== $start_time ? $start_time : '00:00'
		);

		$rows[] = array(
			'event_id'         => $event_id,
			'occurrence_uid'   => $occurrence_uid,
			'occurrence_index' => max( 0, (int) $occurrence_index ),
			'promotion_id'     => absint( $occurrence['promotion_id'] ?? 0 ),
			'parcours_year'    => absint( $occurrence['parcours_year'] ?? 0 ),
			'start_raw'        => $start_raw,
			'end_raw'          => $end_raw,
			'start_sort'       => (string) $occurrence['start_sort'],
			'end_sort'         => (string) $occurrence['end_sort'],
			'is_cancelled'     => ! empty( $occurrence['is_cancelled'] ) ? 1 : 0,
			'event_type'       => $event_type,
			'event_status'     => sanitize_key( $event->post_status ),
			'is_pinned'        => $is_pinned ? 1 : 0,
			'updated_at'       => $updated_at,
		);
	}

	return $rows;
}

/**
 * Atomically replace the projection rows belonging to one event.
 */
function wp_seed_events_sync_occurrence_projection( $event_id ) {
	global $wpdb;

	$event_id = absint( $event_id );

	if ( 0 === $event_id ) {
		return new WP_Error( 'occurrence_projection_invalid_event', 'Occurrence projection requires a valid event.' );
	}

	if ( ! wp_seed_events_occurrence_projection_table_exists() && ! wp_seed_events_install_occurrence_projection_table() ) {
		return new WP_Error( 'occurrence_projection_storage_unavailable', 'Occurrence projection storage is unavailable.' );
	}

	$rows = wp_seed_events_build_occurrence_projection_rows( $event_id );

	if ( is_wp_error( $rows ) ) {
		return $rows;
	}

	$table_name = wp_seed_events_occurrence_projection_table_name();

	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error( 'occurrence_projection_transaction_failed', 'Occurrence projection transaction could not start.' );
	}

	try {
		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE event_id = %d", $event_id ) ) ) {
			throw new RuntimeException( 'Occurrence projection replacement failed.' );
		}

		foreach ( $rows as $row ) {
			$inserted = $wpdb->insert(
				$table_name,
				$row,
				array( '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
			);

			if ( false === $inserted ) {
				throw new RuntimeException( 'Occurrence projection insertion failed.' );
			}
		}
		$stored_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE event_id = %d",
				$event_id
			)
		);

		if ( null === $stored_count || count( $rows ) !== absint( $stored_count ) ) {
			throw new RuntimeException( 'Occurrence projection verification failed.' );
		}

		if ( false === $wpdb->query( 'COMMIT' ) ) {
			throw new RuntimeException( 'Occurrence projection commit failed.' );
		}
	} catch ( Throwable $error ) {
		$wpdb->query( 'ROLLBACK' );

		return new WP_Error( 'occurrence_projection_sync_failed', 'Occurrence projection could not be synchronized.' );
	}

	return $rows;
}

function wp_seed_events_delete_occurrence_projection( $event_id ) {
	global $wpdb;

	$event_id = absint( $event_id );

	if ( 0 === $event_id || ! wp_seed_events_occurrence_projection_table_exists() ) {
		return true;
	}

	$table_name = wp_seed_events_occurrence_projection_table_name();

	return false !== $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE event_id = %d", $event_id ) );
}

function wp_seed_events_clear_occurrence_projection() {
	global $wpdb;

	if ( ! wp_seed_events_install_occurrence_projection_table() ) {
		return false;
	}

	return false !== $wpdb->query( 'DELETE FROM ' . wp_seed_events_occurrence_projection_table_name() );
}

/**
 * Internal exact reader with a canonical PHP fallback while v3 is not ready.
 */
function wp_seed_events_get_occurrence_projection_rows( $event_id, $prefer_index = true ) {
	global $wpdb;

	$event_id = absint( $event_id );

	if (
		$prefer_index
		&& function_exists( 'wp_seed_events_is_lifecycle_index_ready' )
		&& wp_seed_events_is_lifecycle_index_ready()
		&& wp_seed_events_occurrence_projection_table_exists()
	) {
		$table_name       = wp_seed_events_occurrence_projection_table_name();
		$wpdb->last_error = '';
		$rows             = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT event_id, occurrence_uid, occurrence_index, promotion_id, parcours_year, start_raw, end_raw, start_sort, end_sort, is_cancelled, event_type, event_status, is_pinned, updated_at
				FROM {$table_name}
				WHERE event_id = %d
				ORDER BY occurrence_index ASC, id ASC",
				$event_id
			),
			ARRAY_A
		);

		if ( '' === $wpdb->last_error && is_array( $rows ) ) {
			return $rows;
		}
	}

	return wp_seed_events_build_occurrence_projection_rows( $event_id );
}

function wp_seed_events_verify_occurrence_projection_integrity() {
	global $wpdb;

	if ( ! wp_seed_events_occurrence_projection_table_exists() ) {
		return new WP_Error( 'occurrence_projection_missing', 'Occurrence projection table is missing.' );
	}

	$table_name = wp_seed_events_occurrence_projection_table_name();
	$duplicates = absint(
		$wpdb->get_var(
			"SELECT COUNT(*) FROM (
				SELECT event_id, occurrence_uid
				FROM {$table_name}
				GROUP BY event_id, occurrence_uid
				HAVING COUNT(*) > 1
			) duplicate_rows"
		)
	);
	$orphans = absint(
		$wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$table_name} projection
			LEFT JOIN {$wpdb->posts} events ON events.ID = projection.event_id
			WHERE events.ID IS NULL OR events.post_type <> 'wp_seed_event'"
		)
	);
	$invalid_pairs = absint(
		$wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$table_name}
			WHERE (promotion_id = 0 AND parcours_year <> 0)
				OR (promotion_id <> 0 AND parcours_year NOT BETWEEN 1 AND 4)"
		)
	);

	if ( 0 < $duplicates || 0 < $orphans || 0 < $invalid_pairs ) {
		return new WP_Error( 'occurrence_projection_integrity_failed', 'Occurrence projection integrity check failed.' );
	}

	return true;
}
