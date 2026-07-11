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

	$occurrences        = wp_seed_events_get_event_occurrences( $event_id );
	$active_occurrences = wp_seed_events_get_event_occurrences(
		$event_id,
		array(
			'include_cancelled' => false,
			'only_active'       => true,
		)
	);
	$next_occurrence    = wp_seed_events_get_next_active_occurrence( $event_id );
	$last_occurrence    = wp_seed_events_get_last_active_occurrence( $event_id );
	$display_occurrence = array() !== $next_occurrence ? $next_occurrence : $last_occurrence;
	$lifecycle          = wp_seed_events_get_event_lifecycle( $event_id );
	$media              = wp_seed_events_get_event_media( $event_id );
	$featured_image_id  = absint( $media['featured_image']['id'] ?? 0 );
	$primary_image_id   = absint( $media['communication_visual']['id'] ?? 0 );
	$illustration_ids   = array_values(
		array_map(
			'absint',
			array_column( $media['communication_visuals'], 'id' )
		)
	);
	$flyer_pdf_id       = absint( $media['event_document']['id'] ?? 0 );
	$description        = trim( (string) $post->post_content );

	return array(
		'id'                => $event_id,
		'title'             => get_the_title( $event_id ),
		'url'               => get_permalink( $event_id ),
		'types'             => wp_seed_events_event_type_labels_for_event( $event_id ),
		'occurrences'        => $occurrences,
		'active_occurrences' => $active_occurrences,
		'next_occurrence'    => $next_occurrence,
		'last_occurrence'    => $last_occurrence,
		'display_occurrence' => $display_occurrence,
		'lifecycle'          => $lifecycle,
		'place'             => wp_seed_events_public_event_place_data( $event_id ),
		'people'            => wp_seed_events_public_event_people_data( $event_id ),
		'description'       => $description,
		'excerpt'           => wp_seed_events_public_event_excerpt( $description ),
		'featured_image'        => $media['featured_image'],
		'communication_visual'  => $media['communication_visual'],
		'communication_visuals' => $media['communication_visuals'],
		'other_visuals'         => $media['other_visuals'],
		'event_document'        => $media['event_document'],

		// Legacy media ID aliases are derived from the normalized media objects.
		'primary_image_id'      => $primary_image_id,
		'featured_image_id'     => $featured_image_id,
		'illustration_ids'      => $illustration_ids,
		'flyer_pdf_id'          => $flyer_pdf_id,
	);
}
