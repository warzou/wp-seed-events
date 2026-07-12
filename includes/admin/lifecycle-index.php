<?php
/**
 * Technical lifecycle index for the event admin list.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_action( 'save_post_wp_seed_event', 'wp_seed_events_maybe_update_lifecycle_index', 20 );

function wp_seed_events_calculate_lifecycle_index( $event_id ) {
	$event_id = absint( $event_id );

	if ( 0 === $event_id ) {
		return array();
	}

	$event = get_post( $event_id );

	if ( ! $event instanceof WP_Post || 'wp_seed_event' !== $event->post_type ) {
		return array();
	}

	$occurrences      = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => true,
			'status'            => 'all',
		)
	);
	$last_active_date = '';

	foreach ( $occurrences as $occurrence ) {
		if ( empty( $occurrence['is_active'] ) || empty( $occurrence['start_date'] ) ) {
			continue;
		}

		$start_date = (string) $occurrence['start_date'];

		if ( '' === $last_active_date || $start_date > $last_active_date ) {
			$last_active_date = $start_date;
		}
	}

	return array(
		'dated_count'      => count( $occurrences ),
		'last_active_date' => $last_active_date,
	);
}

function wp_seed_events_update_lifecycle_index( $event_id ) {
	$event_id = absint( $event_id );
	$index    = wp_seed_events_calculate_lifecycle_index( $event_id );

	if ( ! isset( $index['dated_count'], $index['last_active_date'] ) ) {
		return false;
	}

	$values = array(
		'_wp_seed_event_lifecycle_index_dated_count'     => (string) max( 0, (int) $index['dated_count'] ),
		'_wp_seed_event_lifecycle_index_last_active_date' => (string) $index['last_active_date'],
	);

	foreach ( $values as $meta_key => $value ) {
		if ( metadata_exists( 'post', $event_id, $meta_key ) && get_post_meta( $event_id, $meta_key, true ) === $value ) {
			continue;
		}

		update_post_meta( $event_id, $meta_key, $value );
	}

	return $index;
}

function wp_seed_events_maybe_update_lifecycle_index( $post_id ) {
	$post_id = absint( $post_id );

	if ( 0 === $post_id ) {
		return;
	}

	$occurrences_changed = isset( $_POST['wp_seed_event_occurrences_changed'] ) && is_scalar( $_POST['wp_seed_event_occurrences_changed'] )
		? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_occurrences_changed'] ) )
		: '';

	if ( '1' !== $occurrences_changed ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || 'wp_seed_event' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['wp_seed_events_occurrences_nonce'] ) || ! is_scalar( $_POST['wp_seed_events_occurrences_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_occurrences_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_occurrences' ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	wp_seed_events_update_lifecycle_index( $post_id );
}
