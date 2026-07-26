<?php
/**
 * Technical lifecycle index for the event admin list.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_action( 'save_post_wp_seed_event', 'wp_seed_events_maybe_update_lifecycle_index', 20 );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_maybe_refresh_collection_index', 30, 3 );
add_action( 'wp_after_insert_post', 'wp_seed_events_maybe_initialize_lifecycle_index', 20, 4 );
add_action( 'before_delete_post', 'wp_seed_events_delete_lifecycle_index', 10, 2 );

function wp_seed_events_calculate_lifecycle_index( $event_id ) {
	$event_id = absint( $event_id );

	if ( 0 === $event_id ) {
		return array();
	}

	$event = get_post( $event_id );

	if ( ! $event instanceof WP_Post || 'wp_seed_event' !== $event->post_type ) {
		return array();
	}

	$occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => true,
			'status'            => 'all',
		)
	);
	$last_active_date        = '';
	$active_occurrence_sorts = array();

	foreach ( $occurrences as $occurrence ) {
		if ( empty( $occurrence['is_active'] ) || empty( $occurrence['start_date'] ) ) {
			continue;
		}

		$start_date = (string) $occurrence['start_date'];

		if ( '' === $last_active_date || $start_date > $last_active_date ) {
			$last_active_date = $start_date;
		}

		if ( ! empty( $occurrence['start_sort'] ) ) {
			$active_occurrence_sorts[] = (string) $occurrence['start_sort'];
		}
	}

	return array(
		'dated_count'            => count( $occurrences ),
		'last_active_date'       => $last_active_date,
		'active_occurrence_sorts' => array_values( array_unique( $active_occurrence_sorts ) ),
		'type_keys'              => wp_seed_events_event_type_keys_for_event( $event_id ),
	);
}

function wp_seed_events_replace_lifecycle_index_values( $event_id, $meta_key, $values ) {
	$values  = array_values( array_unique( array_filter( array_map( 'strval', (array) $values ) ) ) );
	$current = array_values( array_unique( array_filter( array_map( 'strval', (array) get_post_meta( $event_id, $meta_key, false ) ) ) ) );

	sort( $values, SORT_STRING );
	sort( $current, SORT_STRING );

	if ( $values === $current ) {
		return;
	}

	delete_post_meta( $event_id, $meta_key );

	foreach ( $values as $value ) {
		add_post_meta( $event_id, $meta_key, $value, false );
	}
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

	wp_seed_events_replace_lifecycle_index_values(
		$event_id,
		'_wp_seed_event_collection_occurrence_sort',
		$index['active_occurrence_sorts'] ?? array()
	);
	wp_seed_events_replace_lifecycle_index_values(
		$event_id,
		'_wp_seed_event_collection_type',
		$index['type_keys'] ?? array()
	);

	return $index;
}
function wp_seed_events_maybe_refresh_collection_index( $post_id, $post, $update ) {
	$post_id             = absint( $post_id );
	$occurrences_changed = isset( $_POST['wp_seed_event_occurrences_changed'] ) && is_scalar( $_POST['wp_seed_event_occurrences_changed'] )
		? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_occurrences_changed'] ) )
		: '';

	if (
		0 === $post_id
		|| ! $post instanceof WP_Post
		|| 'wp_seed_event' !== $post->post_type
		|| wp_is_post_revision( $post_id )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| '1' === $occurrences_changed
		|| ! wp_seed_events_is_lifecycle_index_ready()
	) {
		return;
	}

	wp_seed_events_update_lifecycle_index( $post_id );
}

function wp_seed_events_delete_lifecycle_index( $post_id, $post ) {
	if ( ! $post instanceof WP_Post || 'wp_seed_event' !== $post->post_type ) {
		return;
	}

	foreach (
		array(
			'_wp_seed_event_lifecycle_index_dated_count',
			'_wp_seed_event_lifecycle_index_last_active_date',
			'_wp_seed_event_collection_occurrence_sort',
			'_wp_seed_event_collection_type',
		) as $meta_key
	) {
		delete_post_meta( $post_id, $meta_key );
	}
}

function wp_seed_events_maybe_initialize_lifecycle_index( $post_id, $post, $update, $post_before ) {
	$post_id = absint( $post_id );

	if ( 0 === $post_id || ! $post instanceof WP_Post || $post_id !== (int) $post->ID || 'wp_seed_event' !== $post->post_type ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || ! in_array( $post->post_status, array( 'draft', 'pending', 'publish', 'future', 'private' ), true ) ) {
		return;
	}

	$is_direct_insert   = ! $update;
	$is_first_user_save = $update && $post_before instanceof WP_Post && 'auto-draft' === $post_before->post_status;

	if ( ! $is_direct_insert && ! $is_first_user_save ) {
		return;
	}

	if ( ! function_exists( 'wp_seed_events_is_lifecycle_index_ready' ) || ! wp_seed_events_is_lifecycle_index_ready() ) {
		return;
	}

	$dated_count_exists      = metadata_exists( 'post', $post_id, '_wp_seed_event_lifecycle_index_dated_count' );
	$last_active_date_exists = metadata_exists( 'post', $post_id, '_wp_seed_event_lifecycle_index_last_active_date' );

	if ( $dated_count_exists && $last_active_date_exists ) {
		return;
	}

	wp_seed_events_update_lifecycle_index( $post_id );
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
