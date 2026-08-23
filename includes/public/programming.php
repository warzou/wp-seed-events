<?php
/**
 * Canonical event programming state.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_SEED_EVENTS_PROGRAMMING_STATUS_META_KEY' ) ) {
	define( 'WP_SEED_EVENTS_PROGRAMMING_STATUS_META_KEY', '_wp_seed_event_programming_status' );
}

if ( ! defined( 'WP_SEED_EVENTS_PROGRAMMING_TEXT_META_KEY' ) ) {
	define( 'WP_SEED_EVENTS_PROGRAMMING_TEXT_META_KEY', '_wp_seed_event_programming_text' );
}

if ( ! defined( 'WP_SEED_EVENTS_PROGRAMMING_VISIBLE_UNTIL_META_KEY' ) ) {
	define( 'WP_SEED_EVENTS_PROGRAMMING_VISIBLE_UNTIL_META_KEY', '_wp_seed_event_programming_visible_until' );
}

add_action( 'rest_api_init', 'wp_seed_events_register_programming_rest_field' );
add_filter( 'redirect_post_location', 'wp_seed_events_programming_notice_redirect', 20, 2 );
add_action( 'admin_notices', 'wp_seed_events_programming_admin_notice' );

/** Normalize the two-value programming state. */
function wp_seed_events_normalize_programming_status( $value, $default = 'scheduled' ) {
	$value = is_scalar( $value ) ? sanitize_key( (string) $value ) : '';

	return in_array( $value, array( 'scheduled', 'to_schedule' ), true ) ? $value : $default;
}

/** Read the canonical state, retaining scheduled as the legacy runtime default. */
function wp_seed_events_get_programming_status( $event_id ) {
	$event_id = absint( $event_id );
	$value    = 0 < $event_id ? get_post_meta( $event_id, WP_SEED_EVENTS_PROGRAMMING_STATUS_META_KEY, true ) : '';

	return wp_seed_events_normalize_programming_status( $value, 'scheduled' );
}

/** Read all programming fields through one canonical projection. */
function wp_seed_events_get_programming_data( $event_id ) {
	$event_id      = absint( $event_id );
	$status        = wp_seed_events_get_programming_status( $event_id );
	$text          = 0 < $event_id ? (string) get_post_meta( $event_id, WP_SEED_EVENTS_PROGRAMMING_TEXT_META_KEY, true ) : '';
	$visible_until = 0 < $event_id ? (string) get_post_meta( $event_id, WP_SEED_EVENTS_PROGRAMMING_VISIBLE_UNTIL_META_KEY, true ) : '';

	return array(
		'status'        => $status,
		'text'          => sanitize_textarea_field( $text ),
		'visible_until' => wp_seed_events_is_valid_programming_date( $visible_until ) ? $visible_until : '',
	);
}

/** Validate an editorial listing cutoff date. */
function wp_seed_events_is_valid_programming_date( $value ) {
	$value = is_scalar( $value ) ? trim( (string) $value ) : '';

	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
		return false;
	}

	$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value );

	return $date instanceof DateTimeImmutable && $date->format( 'Y-m-d' ) === $value;
}

/** Whether the event deliberately has no dates yet. */
function wp_seed_events_event_is_to_schedule( $event_id ) {
	return 'to_schedule' === wp_seed_events_get_programming_status( $event_id );
}

/** Whether a to-schedule event remains eligible for public listings today. */
function wp_seed_events_programming_is_publicly_listable( $event_id, $today = '' ) {
	$data = wp_seed_events_get_programming_data( $event_id );

	if ( 'to_schedule' !== $data['status'] || '' === $data['visible_until'] ) {
		return false;
	}

	$today = wp_seed_events_is_valid_programming_date( $today ) ? $today : current_time( 'Y-m-d' );

	return $data['visible_until'] >= $today;
}

/** Public label for Dynamic Data and read-only consumers. */
function wp_seed_events_programming_status_label( $value ) {
	$value = wp_seed_events_normalize_programming_status( $value, '' );

	return 'scheduled' === $value ? 'Programmé' : ( 'to_schedule' === $value ? 'À programmer' : '' );
}

/** Validate a requested state without mutating occurrences or programming fields. */
function wp_seed_events_validate_programming_state( $status, $text, $visible_until, $occurrences ) {
	$status = wp_seed_events_normalize_programming_status( $status, '' );

	if ( '' === $status ) {
		return 'invalid_status';
	}

	if ( 'scheduled' === $status ) {
		return '';
	}

	if ( is_array( $occurrences ) && array() !== $occurrences ) {
		return 'has_occurrences';
	}

	if ( '' === trim( sanitize_textarea_field( (string) $text ) ) ) {
		return 'missing_text';
	}

	return wp_seed_events_is_valid_programming_date( $visible_until ) ? '' : 'missing_cutoff';
}

/** Expose the canonical programming contract on event REST responses. */
function wp_seed_events_register_programming_rest_field() {
	if ( ! function_exists( 'register_rest_field' ) ) {
		return;
	}

	register_rest_field(
		'wp_seed_event',
		'wp_seed_events_programming',
		array(
			'get_callback' => static function ( $prepared ) {
				$event_id = is_array( $prepared ) ? absint( $prepared['id'] ?? 0 ) : absint( $prepared->ID ?? 0 );

				return 0 < $event_id ? wp_seed_events_get_programming_data( $event_id ) : null;
			},
			'schema'       => array(
				'description' => 'Canonical event programming state.',
				'type'        => 'object',
				'context'     => array( 'view', 'edit' ),
				'readonly'    => true,
				'properties'  => array(
					'status'        => array( 'type' => 'string', 'enum' => array( 'scheduled', 'to_schedule' ) ),
					'text'          => array( 'type' => 'string' ),
					'visible_until' => array( 'type' => 'string' ),
				),
			),
		)
	);
}

/** Carry save validation state across the normal WordPress edit redirect. */
function wp_seed_events_programming_notice_redirect( $location, $post_id ) {
	unset( $post_id );
	$notice = sanitize_key( (string) ( $GLOBALS['wp_seed_events_programming_notice'] ?? '' ) );

	return '' === $notice ? $location : add_query_arg( 'wp_seed_programming_notice', $notice, $location );
}

/** Render the precise programming-state save result. */
function wp_seed_events_programming_admin_notice() {
	$notice = isset( $_GET['wp_seed_programming_notice'] )
		? sanitize_key( wp_unslash( $_GET['wp_seed_programming_notice'] ) )
		: '';
	$messages = array(
		'has_occurrences' => array( 'error', 'Cet événement possède encore des dates programmées. Retirez-les avant de le passer à À programmer.' ),
		'missing_text'    => array( 'error', 'Renseignez le texte de programmation avant de passer cet événement à À programmer.' ),
		'missing_cutoff'  => array( 'error', 'Renseignez une date « Visible jusqu’au » valide avant de passer cet événement à À programmer.' ),
		'scheduled_empty' => array( 'warning', 'Événement programmé sans date' ),
	);

	if ( ! isset( $messages[ $notice ] ) ) {
		return;
	}

	list( $type, $message ) = $messages[ $notice ];
	echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}
