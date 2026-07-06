<?php
/**
 * Event data API for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_get_event_data( $event_id ) {
	$event_id = absint( $event_id );
	$post     = get_post( $event_id );

	if ( ! $post || 'wp_seed_event' !== $post->post_type || 'publish' !== $post->post_status ) {
		return array();
	}

	$occurrences = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $occurrences ) ) {
		$occurrences = array();
	}

	$occurrences = array_values(
		array_filter(
			wp_seed_events_sort_occurrences_for_display( $occurrences ),
			function ( $occurrence ) {
				return is_array( $occurrence ) && ! empty( $occurrence['start_date'] );
			}
		)
	);

	$next_occurrence   = wp_seed_events_next_occurrence_for_event( $event_id );
	$featured_image_id = (int) get_post_thumbnail_id( $event_id );
	$illustration_ids  = get_post_meta( $event_id, '_wp_seed_event_illustration_ids', true );
	$illustration_ids  = is_array( $illustration_ids ) ? array_values( array_map( 'absint', $illustration_ids ) ) : array();
	$primary_image_id  = $featured_image_id;
	$flyer_pdf_id      = (int) get_post_meta( $event_id, '_wp_seed_event_flyer_pdf_id', true );
	$description       = trim( (string) $post->post_content );

	if ( 0 === $primary_image_id && array() !== $illustration_ids ) {
		$primary_image_id = (int) reset( $illustration_ids );
	}

	return array(
		'id'                => $event_id,
		'title'             => get_the_title( $event_id ),
		'url'               => get_permalink( $event_id ),
		'types'             => wp_seed_events_event_type_labels_for_event( $event_id ),
		'occurrences'       => $occurrences,
		'next_occurrence'   => $next_occurrence,
		'place'             => wp_seed_events_public_event_place_data( $event_id ),
		'people'            => wp_seed_events_public_event_people_data( $event_id ),
		'description'       => $description,
		'excerpt'           => wp_seed_events_public_event_excerpt( $description ),
		'primary_image_id'  => $primary_image_id,
		'featured_image_id' => $featured_image_id,
		'illustration_ids'  => $illustration_ids,
		'flyer_pdf_id'      => $flyer_pdf_id,
	);
}
