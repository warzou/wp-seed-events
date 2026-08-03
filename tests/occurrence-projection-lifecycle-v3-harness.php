<?php
/**
 * Standalone lifecycle v3 and occurrence projection assertions.
 *
 * Run with: php tests/occurrence-projection-lifecycle-v3-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );

$GLOBALS['v3_assertions'] = 0;
$GLOBALS['v3_options']    = array();
$GLOBALS['v3_posts']      = array();
$GLOBALS['v3_meta']       = array();
$GLOBALS['v3_types']      = array();
$GLOBALS['v3_primary']    = array();
$GLOBALS['v3_now']        = 1700000000;

class WP_Error {
	private $code;
	public function __construct( $code = '' ) { $this->code = (string) $code; }
	public function get_error_code() { return $this->code; }
}

class WP_Post {
	public $ID;
	public $post_type;
	public $post_status;
	public function __construct( $id, $type = 'wp_seed_event', $status = 'publish' ) {
		$this->ID          = (int) $id;
		$this->post_type   = (string) $type;
		$this->post_status = (string) $status;
	}
}

class V3_Wpdb {
	public $prefix = 'wp_';
	public $posts = 'wp_posts';
	public $last_error = '';
	public $table_exists = false;
	public $rows = array();
	public $queries = array();
	public $snapshot = null;
	public $next_id = 1;
	public $fail_insert = false;
	public $integrity_duplicates = 0;
	public $integrity_orphans = 0;
	public $integrity_invalid_pairs = 0;

	public function get_charset_collate() { return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'; }

	public function prepare( $query, ...$args ) {
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}

		foreach ( $args as $arg ) {
			$replacement = is_int( $arg ) ? (string) $arg : "'" . addslashes( (string) $arg ) . "'";
			$query       = preg_replace( '/%[sd]/', $replacement, $query, 1 );
		}

		return $query;
	}

	public function get_var( $query ) {
		$this->queries[] = $query;
		$this->last_error = '';

		if ( false !== strpos( $query, 'SHOW TABLES LIKE' ) ) {
			return $this->table_exists ? $this->prefix . 'wp_seed_event_occurrences' : null;
		}

		if ( preg_match( '/SELECT COUNT\\(\\*\\) FROM [^ ]+ WHERE event_id = ([0-9]+)/', $query, $matches ) ) {
			$event_id = (int) $matches[1];

			return count(
				array_filter(
					$this->rows,
					static function ( $row ) use ( $event_id ) {
						return (int) $row['event_id'] === $event_id;
					}
				)
			);
		}
		if ( false !== strpos( $query, 'COUNT(ID)' ) ) {
			return count( $GLOBALS['v3_posts'] );
		}

		if ( false !== strpos( $query, 'duplicate_rows' ) ) {
			return $this->integrity_duplicates;
		}

		if ( false !== strpos( $query, 'LEFT JOIN' ) ) {
			return $this->integrity_orphans;
		}

		if ( false !== strpos( $query, 'promotion_id = 0' ) ) {
			return $this->integrity_invalid_pairs;
		}

		return 0;
	}

	public function get_col( $query ) {
		$this->queries[] = $query;
		$this->last_error = '';
		preg_match( '/ID > ([0-9]+)/', $query, $after_match );
		preg_match( '/LIMIT ([0-9]+)/', $query, $limit_match );
		$after = isset( $after_match[1] ) ? (int) $after_match[1] : 0;
		$limit = isset( $limit_match[1] ) ? (int) $limit_match[1] : 100;
		$ids   = array_values(
			array_filter(
				array_keys( $GLOBALS['v3_posts'] ),
				static function ( $id ) use ( $after ) {
					return (int) $id > $after;
				}
			)
		);
		sort( $ids, SORT_NUMERIC );

		return array_slice( $ids, 0, $limit );
	}

	public function query( $query ) {
		$this->queries[] = $query;
		$this->last_error = '';
		$trimmed = trim( $query );

		if ( 'START TRANSACTION' === $trimmed ) {
			$this->snapshot = $this->rows;
			return 0;
		}

		if ( 'COMMIT' === $trimmed ) {
			$this->snapshot = null;
			return 0;
		}

		if ( 'ROLLBACK' === $trimmed ) {
			if ( is_array( $this->snapshot ) ) {
				$this->rows = $this->snapshot;
			}
			$this->snapshot = null;
			return 0;
		}

		if ( preg_match( '/DELETE FROM [^ ]+ WHERE event_id = ([0-9]+)/', $trimmed, $matches ) ) {
			$event_id   = (int) $matches[1];
			$this->rows = array_values(
				array_filter(
					$this->rows,
					static function ( $row ) use ( $event_id ) {
						return (int) $row['event_id'] !== $event_id;
					}
				)
			);
			return 1;
		}

		if ( 0 === strpos( $trimmed, 'DELETE FROM ' ) ) {
			$this->rows = array();
			return 1;
		}

		return 0;
	}

	public function insert( $table, $row, $formats ) {
		if ( $this->fail_insert ) {
			$this->fail_insert = false;
			$this->last_error  = 'controlled failure';
			return false;
		}

		foreach ( $this->rows as $existing ) {
			if ( (int) $existing['event_id'] === (int) $row['event_id'] && $existing['occurrence_uid'] === $row['occurrence_uid'] ) {
				return false;
			}
		}

		$row['id']     = $this->next_id++;
		$this->rows[]  = $row;
		return 1;
	}

	public function get_results( $query, $format ) {
		$this->queries[] = $query;
		$this->last_error = '';
		preg_match( '/event_id = ([0-9]+)/', $query, $matches );
		$event_id = isset( $matches[1] ) ? (int) $matches[1] : 0;
		$rows     = array_values(
			array_filter(
				$this->rows,
				static function ( $row ) use ( $event_id ) {
					return (int) $row['event_id'] === $event_id;
				}
			)
		);
		usort(
			$rows,
			static function ( $first, $second ) {
				return (int) $first['occurrence_index'] <=> (int) $second['occurrence_index'];
			}
		);

		foreach ( $rows as &$row ) {
			unset( $row['id'] );
		}

		return $rows;
	}
}

$GLOBALS['wpdb'] = new V3_Wpdb();

function v3_assert( $condition, $message ) {
	$GLOBALS['v3_assertions']++;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function v3_same( $expected, $actual, $message ) {
	v3_assert( $expected === $actual, $message );
}

function is_wp_error( $value ) { return $value instanceof WP_Error; }
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function wp_unslash( $value ) { return $value; }
function wp_json_encode( $value ) { return json_encode( $value, JSON_UNESCAPED_SLASHES ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function add_action() {}
function wp_is_post_revision() { return false; }
function current_user_can() { return true; }
function wp_verify_nonce() { return true; }
function current_time( $format, $gmt = false ) { return 'mysql' === $format ? '2026-07-27 10:00:00' : '2026-07-27'; }
function wp_generate_uuid4() { return 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa'; }
function dbDelta( $schema ) { $GLOBALS['wpdb']->table_exists = false !== strpos( $schema, 'wp_seed_event_occurrences' ); }

function get_post( $event_id ) {
	return $GLOBALS['v3_posts'][ absint( $event_id ) ] ?? null;
}

function get_post_type( $event_id ) {
	$post = get_post( $event_id );
	return $post instanceof WP_Post ? $post->post_type : '';
}

function get_post_meta( $event_id, $key, $single = true ) {
	$value = $GLOBALS['v3_meta'][ absint( $event_id ) ][ $key ] ?? ( $single ? '' : array() );
	return $value;
}

function metadata_exists( $type, $event_id, $key ) {
	return array_key_exists( $key, $GLOBALS['v3_meta'][ absint( $event_id ) ] ?? array() );
}

function update_post_meta( $event_id, $key, $value ) {
	$GLOBALS['v3_meta'][ absint( $event_id ) ][ $key ] = $value;
	return true;
}

function delete_post_meta( $event_id, $key ) {
	unset( $GLOBALS['v3_meta'][ absint( $event_id ) ][ $key ] );
	return true;
}

function add_post_meta( $event_id, $key, $value ) {
	$current = $GLOBALS['v3_meta'][ absint( $event_id ) ][ $key ] ?? array();
	$current = is_array( $current ) ? $current : array( $current );
	$current[] = $value;
	$GLOBALS['v3_meta'][ absint( $event_id ) ][ $key ] = $current;
	return true;
}

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['v3_options'] ) ? $GLOBALS['v3_options'][ $name ] : $default;
}

function update_option( $name, $value ) {
	$GLOBALS['v3_options'][ $name ] = $value;
	return true;
}

function delete_option( $name ) {
	unset( $GLOBALS['v3_options'][ $name ] );
	return true;
}

function add_option( $name, $value ) {
	if ( array_key_exists( $name, $GLOBALS['v3_options'] ) ) {
		return false;
	}
	$GLOBALS['v3_options'][ $name ] = $value;
	return true;
}

function wp_seed_events_event_type_keys_for_event( $event_id ) {
	return $GLOBALS['v3_types'][ absint( $event_id ) ] ?? array( 'seminaire' );
}

function wp_seed_events_primary_type_for_event( $event_id ) {
	return $GLOBALS['v3_primary'][ absint( $event_id ) ] ?? '';
}

function wp_seed_events_default_event_type_key() { return 'non_classe'; }

function wp_seed_events_sanitize_occurrence_uid( $uid ) {
	$uid = strtolower( trim( (string) $uid ) );
	return preg_match( '/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/', $uid ) ? $uid : '';
}

function wp_seed_events_normalize_occurrence( $raw, $event_id, $index = 0 ) {
	if ( ! is_array( $raw ) || empty( $raw['start_date'] ) ) {
		return array();
	}
	$start_date = (string) $raw['start_date'];
	$end_date   = (string) ( $raw['end_date'] ?? '' );
	$start_time = (string) ( $raw['start_time'] ?? '' );
	$end_time   = (string) ( $raw['end_time'] ?? '' );
	$all_day    = ! empty( $raw['all_day'] );
	$cancelled  = ! empty( $raw['cancelled'] );

	return array(
		'uid'            => wp_seed_events_sanitize_occurrence_uid( $raw['uid'] ?? '' ),
		'start_date'     => $start_date,
		'end_date'       => $end_date,
		'start_time'     => $start_time,
		'end_time'       => $end_time,
		'all_day'        => $all_day ? '1' : '',
		'promotion_id'   => absint( $raw['promotion_id'] ?? 0 ),
		'parcours_year'  => absint( $raw['parcours_year'] ?? 0 ),
		'start_sort'     => $start_date . ' ' . ( $all_day ? '00:00' : ( '' !== $start_time ? $start_time : '00:00' ) ),
		'end_sort'       => ( '' !== $end_date ? $end_date : $start_date ) . ' ' . ( $all_day ? '23:59' : ( '' !== $end_time ? $end_time : ( '' !== $start_time ? $start_time : '00:00' ) ) ),
		'is_active'      => ! $cancelled,
		'is_cancelled'   => $cancelled,
	);
}

function wp_seed_events_get_event_occurrences( $event_id ) {
	$raw = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );
	$raw = is_array( $raw ) ? $raw : array();
	$result = array();
	foreach ( $raw as $index => $occurrence ) {
		$normalized = wp_seed_events_normalize_occurrence( $occurrence, $event_id, $index );
		if ( array() !== $normalized ) {
			$result[] = $normalized;
		}
	}
	return $result;
}

function wp_seed_events_sync_native_event_query_projection( $event_id ) {
	return '';
}
function wp_seed_events_verify_native_classification_integrity() {
	return true;
}
require dirname( __DIR__ ) . '/includes/admin/occurrence-projection.php';
require dirname( __DIR__ ) . '/includes/admin/lifecycle-index.php';
require dirname( __DIR__ ) . '/includes/admin/lifecycle-index-backfill.php';

$projection_source = file_get_contents( dirname( __DIR__ ) . '/includes/admin/occurrence-projection.php' );
$backfill_source   = file_get_contents( dirname( __DIR__ ) . '/includes/admin/lifecycle-index-backfill.php' );
$main_source       = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );

foreach ( array( 'event_id', 'occurrence_uid', 'promotion_id', 'parcours_year', 'start_sort', 'end_sort', 'is_cancelled', 'event_type', 'event_status', 'is_pinned', 'updated_at' ) as $column ) {
	v3_assert( false !== strpos( wp_seed_events_occurrence_projection_schema(), $column ), 'Schema column missing: ' . $column );
}
foreach ( array( 'UNIQUE KEY event_occurrence', 'KEY promotion_year_start', 'KEY collection_filter' ) as $index ) {
	v3_assert( false !== strpos( wp_seed_events_occurrence_projection_schema(), $index ), 'Schema index missing: ' . $index );
}
foreach ( array( 'person', 'email', 'phone', 'place', 'media', 'html' ) as $private_column ) {
	v3_assert( false === strpos( wp_seed_events_occurrence_projection_schema(), $private_column ), 'Unnecessary projection data found: ' . $private_column );
}
v3_same( 4, wp_seed_events_lifecycle_index_expected_version(), 'Lifecycle version is not 4.' );
v3_same( 25, wp_seed_events_lifecycle_index_batch_size(), 'Batch size changed.' );
v3_same( 300, wp_seed_events_lifecycle_index_lock_ttl(), 'Lock TTL changed.' );

$GLOBALS['wpdb']->table_exists = false;
v3_assert( wp_seed_events_install_occurrence_projection_table(), 'Fresh table installation failed.' );
v3_assert( $GLOBALS['wpdb']->table_exists, 'Fresh table was not created.' );

$event_id = 101;
$uuid_a   = '11111111-1111-4111-8111-111111111111';
$uuid_b   = '22222222-2222-4222-8222-222222222222';
$GLOBALS['v3_posts'][ $event_id ] = new WP_Post( $event_id );
$GLOBALS['v3_types'][ $event_id ] = array( 'seminaire', 'atelier' );
$GLOBALS['v3_primary'][ $event_id ] = 'seminaire';
$GLOBALS['v3_meta'][ $event_id ] = array(
	'_wp_seed_event_pinned'      => '1',
	'_wp_seed_event_occurrences' => array(
		array( 'uid' => $uuid_a, 'start_date' => '2026-09-10', 'start_time' => '09:00', 'end_time' => '17:00', 'promotion_id' => 10, 'parcours_year' => 1 ),
		array( 'start_date' => '2026-10-15', 'start_time' => '09:00', 'promotion_id' => 11, 'parcours_year' => 1 ),
		array( 'uid' => $uuid_b, 'start_date' => '2026-11-20', 'all_day' => '1', 'cancelled' => '1' ),
	),
);

$rows = wp_seed_events_build_occurrence_projection_rows( $event_id );
v3_same( 3, count( $rows ), 'One row per occurrence was not built.' );
v3_same( $uuid_a, $rows[0]['occurrence_uid'], 'Persisted UUID changed.' );
v3_assert( 0 === strpos( $rows[1]['occurrence_uid'], 'legacy-' ), 'Legacy UID is not deterministic.' );
v3_same( 10, $rows[0]['promotion_id'], 'Promotion 2026 was not projected.' );
v3_same( 11, $rows[1]['promotion_id'], 'Promotion 2027 was not projected.' );
v3_same( 1, $rows[0]['parcours_year'], 'Parcours year was not projected.' );
v3_same( 1, $rows[2]['is_cancelled'], 'Cancelled occurrence was not projected.' );
v3_same( 'seminaire', $rows[0]['event_type'], 'Primary type was not projected.' );
v3_same( 1, $rows[0]['is_pinned'], 'Pinned state was not projected.' );
v3_same( 'publish', $rows[0]['event_status'], 'Event status was not projected.' );
v3_same( '2026-11-20 23:59', $rows[2]['end_sort'], 'All-day end sort differs.' );

$legacy_before = array_column( $rows, 'occurrence_uid', 'start_raw' );
$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array_reverse( $GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] );
$legacy_after_rows = wp_seed_events_build_occurrence_projection_rows( $event_id );
$legacy_after = array_column( $legacy_after_rows, 'occurrence_uid', 'start_raw' );
v3_same( $legacy_before['2026-10-15 09:00'], $legacy_after['2026-10-15 09:00'], 'Legacy UID changed after reordering.' );
v3_same( $uuid_a, $legacy_after['2026-09-10 09:00'], 'Persisted UUID changed after reordering.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array(
	array( 'start_date' => '2026-12-01' ),
	array( 'start_date' => '2026-12-01' ),
);
$duplicate_legacy = wp_seed_events_build_occurrence_projection_rows( $event_id );
v3_assert( $duplicate_legacy[0]['occurrence_uid'] !== $duplicate_legacy[1]['occurrence_uid'], 'Identical legacy occurrences collided.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array(
	array( 'uid' => $uuid_a, 'start_date' => '2026-09-10' ),
	array( 'uid' => $uuid_a, 'start_date' => '2026-10-10' ),
);
$duplicate_uuid = wp_seed_events_build_occurrence_projection_rows( $event_id );
v3_assert( is_wp_error( $duplicate_uuid ) && 'occurrence_projection_duplicate_uid' === $duplicate_uuid->get_error_code(), 'Duplicate UUID did not fail explicitly.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array(
	array( 'uid' => $uuid_a, 'start_date' => '2026-09-10', 'promotion_id' => 10, 'parcours_year' => 1 ),
	array( 'uid' => $uuid_b, 'start_date' => '2026-10-10', 'promotion_id' => 10, 'parcours_year' => 2 ),
);
$synced = wp_seed_events_sync_occurrence_projection( $event_id );
v3_assert( ! is_wp_error( $synced ), 'Targeted projection sync failed.' );
v3_same( 2, count( $GLOBALS['wpdb']->rows ), 'Targeted projection row count differs.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array(
	array( 'uid' => $uuid_a, 'start_date' => '2027-01-15', 'promotion_id' => 11, 'parcours_year' => 1 ),
);
wp_seed_events_sync_occurrence_projection( $event_id );
v3_same( 1, count( $GLOBALS['wpdb']->rows ), 'Removed occurrence projection remains.' );
v3_same( '2027-01-15 00:00', $GLOBALS['wpdb']->rows[0]['start_sort'], 'Changed date was not projected.' );
v3_same( 11, $GLOBALS['wpdb']->rows[0]['promotion_id'], 'Changed Promotion was not projected.' );

$current_occurrences = $GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'];
$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array();
wp_seed_events_sync_occurrence_projection( $event_id );
v3_same( 0, count( $GLOBALS['wpdb']->rows ), 'Undated event retained projection rows.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = $current_occurrences;
$GLOBALS['v3_posts'][ $event_id ]->post_status = 'trash';
$GLOBALS['v3_primary'][ $event_id ] = 'atelier';
$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_pinned'] = '';
wp_seed_events_sync_occurrence_projection( $event_id );
v3_same( 'trash', $GLOBALS['wpdb']->rows[0]['event_status'], 'Trash status was not refreshed.' );
v3_same( 'atelier', $GLOBALS['wpdb']->rows[0]['event_type'], 'Changed event type was not refreshed.' );
v3_same( 0, $GLOBALS['wpdb']->rows[0]['is_pinned'], 'Changed pinned state was not refreshed.' );

$GLOBALS['v3_posts'][ $event_id ]->post_status = 'publish';
$GLOBALS['v3_primary'][ $event_id ] = 'seminaire';
$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_pinned'] = '1';
wp_seed_events_sync_occurrence_projection( $event_id );
v3_same( 'publish', $GLOBALS['wpdb']->rows[0]['event_status'], 'Restored event status was not refreshed.' );
v3_same( 'seminaire', $GLOBALS['wpdb']->rows[0]['event_type'], 'Restored event type was not refreshed.' );
v3_same( 1, $GLOBALS['wpdb']->rows[0]['is_pinned'], 'Restored pinned state was not refreshed.' );

$source_before_sync = $GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'];
wp_seed_events_sync_occurrence_projection( $event_id );
v3_same( $source_before_sync, $GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'], 'Projection synchronization changed canonical occurrence storage.' );


$saved_rows = $GLOBALS['wpdb']->rows;
$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'][] = array( 'uid' => $uuid_b, 'start_date' => '2027-02-15' );
$GLOBALS['wpdb']->fail_insert = true;
$failed_sync = wp_seed_events_sync_occurrence_projection( $event_id );
v3_assert( is_wp_error( $failed_sync ), 'Controlled insert failure was not reported.' );
v3_same( $saved_rows, $GLOBALS['wpdb']->rows, 'Failed sync left a partial projection.' );

$GLOBALS['v3_options'] = array();
v3_assert( ! wp_seed_events_is_lifecycle_index_ready(), 'Unversioned lifecycle is unexpectedly ready.' );
$fallback = wp_seed_events_get_occurrence_projection_rows( $event_id );
v3_same( 2, count( $fallback ), 'Canonical fallback did not return current occurrences.' );

$GLOBALS['v3_meta'][ $event_id ]['_wp_seed_event_occurrences'] = array(
	array( 'uid' => $uuid_a, 'start_date' => '2027-01-15' ),
);
wp_seed_events_sync_occurrence_projection( $event_id );
$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_version_option_name() ] = 4;
$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_progress_option_name() ] = array(
	'version' => 4,
	'status'  => 'complete',
	'errors'  => 0,
);
v3_assert( wp_seed_events_is_lifecycle_index_ready(), 'Completed v4 lifecycle is not ready.' );
v3_same( 1, count( wp_seed_events_get_occurrence_projection_rows( $event_id ) ), 'Ready index reader differs.' );

$GLOBALS['wpdb']->fail_insert = true;
$failed_update = wp_seed_events_update_lifecycle_index( $event_id );
v3_assert( false === $failed_update, 'Targeted lifecycle failure was hidden.' );
v3_assert( ! array_key_exists( wp_seed_events_lifecycle_index_version_option_name(), $GLOBALS['v3_options'] ), 'Failed targeted sync left lifecycle ready.' );
$failed_progress = wp_seed_events_get_lifecycle_index_progress();
v3_same( 'failed', $failed_progress['status'], 'Failed targeted sync did not record failed state.' );
v3_assert( in_array( $event_id, $failed_progress['error_ids'], true ), 'Failed targeted sync did not queue an exact retry.' );

wp_seed_events_sync_occurrence_projection( $event_id );
$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_version_option_name() ] = 4;
$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_progress_option_name() ] = array(
	'version' => 4,
	'status'  => 'complete',
	'errors'  => 0,
);


wp_seed_events_delete_occurrence_projection( $event_id );
v3_same( 0, count( $GLOBALS['wpdb']->rows ), 'Event projection deletion failed.' );

$GLOBALS['v3_options'] = array(
	wp_seed_events_lifecycle_index_version_option_name() => 2,
	wp_seed_events_lifecycle_index_progress_option_name() => array(
		'version'   => 2,
		'status'    => 'complete',
		'cursor_id' => 101,
		'processed' => 1,
		'errors'    => 0,
	),
);
$GLOBALS['wpdb']->table_exists = true;
$migrated = wp_seed_events_run_lifecycle_index_backfill_batch();
v3_assert( is_array( $migrated ) && 'complete' === $migrated['status'], 'V2 to V3 migration did not complete.' );
v3_same( 4, get_option( wp_seed_events_lifecycle_index_version_option_name() ), 'V4 version was not stored.' );
v3_assert( wp_seed_events_is_lifecycle_index_ready(), 'Migrated lifecycle is not ready.' );
v3_same( 1, count( $GLOBALS['wpdb']->rows ), 'Migration did not rebuild exact rows.' );

$rows_after_migration = $GLOBALS['wpdb']->rows;
$second_pass = wp_seed_events_run_lifecycle_index_backfill_batch();
v3_same( $rows_after_migration, $GLOBALS['wpdb']->rows, 'Repeated activation/backfill is not idempotent.' );
v3_same( 'complete', $second_pass['status'], 'Ready backfill did not remain complete.' );

$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_lock_option_name() ] = array(
	'token'      => 'active',
	'expires_at' => time() + 120,
);
$locked = wp_seed_events_run_lifecycle_index_backfill_batch( true );
v3_assert( is_wp_error( $locked ) && 'lifecycle_index_locked' === $locked->get_error_code(), 'Concurrent lock was not enforced.' );

$GLOBALS['v3_options'][ wp_seed_events_lifecycle_index_lock_option_name() ] = array(
	'token'      => 'expired',
	'expires_at' => time() - 1,
);
$resumed = wp_seed_events_run_lifecycle_index_backfill_batch( true );
v3_assert( is_array( $resumed ) && 'complete' === $resumed['status'], 'Expired lock was not reclaimed.' );
v3_assert( ! array_key_exists( wp_seed_events_lifecycle_index_lock_option_name(), $GLOBALS['v3_options'] ), 'Lock was not released.' );

$GLOBALS['wpdb']->table_exists = false;
$GLOBALS['wpdb']->rows = array();
$recreated = wp_seed_events_run_lifecycle_index_backfill_batch();
v3_assert( is_array( $recreated ) && 'complete' === $recreated['status'], 'Missing table was not reconstructed.' );
v3_assert( $GLOBALS['wpdb']->table_exists, 'Missing table was not recreated.' );

$GLOBALS['wpdb']->integrity_orphans = 1;
$integrity = wp_seed_events_verify_occurrence_projection_integrity();
v3_assert( is_wp_error( $integrity ), 'Orphan integrity failure was not detected.' );
$GLOBALS['wpdb']->integrity_orphans = 0;

$GLOBALS['wp_seed_events_occurrences_validation_error'] = true;
$query_count = count( $GLOBALS['wpdb']->queries );
wp_seed_events_maybe_update_lifecycle_index( $event_id );
v3_same( $query_count, count( $GLOBALS['wpdb']->queries ), 'Invalid occurrence save wrote a projection.' );
unset( $GLOBALS['wp_seed_events_occurrences_validation_error'] );

$GLOBALS['v3_options'] = array();
$GLOBALS['v3_posts']   = array();
$GLOBALS['v3_meta']    = array();
$GLOBALS['v3_types']   = array();
$GLOBALS['v3_primary'] = array();
$GLOBALS['wpdb']->rows = array();
$empty_batch = wp_seed_events_run_lifecycle_index_backfill_batch( true );
v3_same( 'complete', $empty_batch['status'], 'Empty catalogue did not complete.' );
v3_same( 0, $empty_batch['total'], 'Empty catalogue total differs.' );
v3_assert( wp_seed_events_is_lifecycle_index_ready(), 'Empty catalogue is not ready.' );

$GLOBALS['v3_options'] = array();
for ( $batch_event_id = 1; $batch_event_id <= 27; $batch_event_id++ ) {
	$GLOBALS['v3_posts'][ $batch_event_id ] = new WP_Post( $batch_event_id );
	$GLOBALS['v3_meta'][ $batch_event_id ]  = array(
		'_wp_seed_event_occurrences' => array(),
	);
	$GLOBALS['v3_types'][ $batch_event_id ]   = array( 'seminaire' );
	$GLOBALS['v3_primary'][ $batch_event_id ] = 'seminaire';
}

$first_batch = wp_seed_events_run_lifecycle_index_backfill_batch( true );
v3_same( 'running', $first_batch['status'], 'First bounded batch did not remain running.' );
v3_same( 25, $first_batch['processed'], 'First bounded batch size differs.' );
v3_same( 25, $first_batch['cursor_id'], 'First bounded batch cursor differs.' );
v3_assert( ! wp_seed_events_is_lifecycle_index_ready(), 'Interrupted migration became ready too early.' );
v3_assert( ! array_key_exists( wp_seed_events_lifecycle_index_version_option_name(), $GLOBALS['v3_options'] ), 'Interrupted migration stored the v4 version.' );

$last_batch = wp_seed_events_run_lifecycle_index_backfill_batch();
v3_same( 'complete', $last_batch['status'], 'Last partial batch did not complete.' );
v3_same( 27, $last_batch['processed'], 'Last partial batch processed count differs.' );
v3_same( 27, $last_batch['cursor_id'], 'Last partial batch cursor differs.' );
v3_assert( wp_seed_events_is_lifecycle_index_ready(), 'Resumed migration is not ready.' );

$GLOBALS['v3_options'] = array();
$GLOBALS['v3_posts']   = array( 301 => new WP_Post( 301 ) );
$GLOBALS['v3_meta']    = array(
	301 => array(
		'_wp_seed_event_occurrences' => array(
			array( 'uid' => '30130130-1301-4301-8301-301301301301', 'start_date' => '2028-03-01' ),
		),
	),
);
$GLOBALS['v3_types']   = array( 301 => array( 'seminaire' ) );
$GLOBALS['v3_primary'] = array( 301 => 'seminaire' );
$GLOBALS['wpdb']->fail_insert = true;
$failed_batch = wp_seed_events_run_lifecycle_index_backfill_batch( true );
v3_assert( is_wp_error( $failed_batch ), 'Controlled batch error was not reported.' );
$failed_batch_progress = wp_seed_events_get_lifecycle_index_progress();
v3_same( 'failed', $failed_batch_progress['status'], 'Controlled batch error state differs.' );
v3_same( array( 301 ), $failed_batch_progress['error_ids'], 'Controlled batch error ID differs.' );
v3_assert( ! wp_seed_events_is_lifecycle_index_ready(), 'Failed batch became ready.' );
foreach ( array( 'START TRANSACTION', 'ROLLBACK', 'wp_seed_events_sync_occurrence_projection', 'before_delete_post' ) as $token ) {
	v3_assert( false !== strpos( $projection_source . file_get_contents( dirname( __DIR__ ) . '/includes/admin/lifecycle-index.php' ), $token ), 'Atomic lifecycle token missing: ' . $token );
}
foreach ( array( 'cursor_id', 'error_ids', 'expires_at', 'wp_seed_events_lifecycle_index_batch_size', 'occurrence_projection_table_exists' ) as $token ) {
	v3_assert( false !== strpos( $backfill_source, $token ), 'Backfill contract token missing: ' . $token );
}
foreach ( array( 'grouped', 'register_block', 'et_builder', 'shortcode' ) as $out_of_scope ) {
	v3_assert( false === strpos( $projection_source, $out_of_scope ), 'Out-of-scope integration found: ' . $out_of_scope );
}
v3_assert( false !== strpos( $main_source, "require_once __DIR__ . '/includes/admin/occurrence-projection.php';" ), 'Projection bootstrap is absent.' );
v3_assert( false !== strpos( $main_source, 'wp_seed_events_install_occurrence_projection_table();' ), 'Fresh activation does not install the table.' );
v3_assert( false !== strpos( $main_source, 'wp_seed_events_run_lifecycle_index_backfill_batch( true );' ), 'Fresh activation does not initialize lifecycle v3.' );

echo 'Occurrence projection lifecycle v3 harness: ' . $GLOBALS['v3_assertions'] . '/' . $GLOBALS['v3_assertions'] . ' OK' . PHP_EOL;
