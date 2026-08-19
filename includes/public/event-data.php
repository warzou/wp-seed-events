<?php
/**
 * Event data API for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keep only absolute public HTTP or HTTPS URLs.
 *
 * Relative URLs are rejected deliberately: Dynamic Data consumers need a
 * complete public destination and must not infer a site or request context.
 *
 * @param mixed $url Candidate public URL.
 * @return string
 */
function wp_seed_events_sanitize_public_http_url( $url ) {
	$url = trim( (string) $url );

	if ( '' === $url ) {
		return '';
	}

	$url   = esc_url_raw( $url, array( 'http', 'https' ) );
	$parts = '' !== $url ? wp_parse_url( $url ) : false;

	if (
		! is_array( $parts )
		|| empty( $parts['scheme'] )
		|| empty( $parts['host'] )
		|| ! in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true )
	) {
		return '';
	}

	return $url;
}

function wp_seed_events_get_event_data( $event_id ) {
	$event_id = absint( $event_id );
	$post     = get_post( $event_id );

	if ( ! $post || 'wp_seed_event' !== $post->post_type || 'publish' !== $post->post_status ) {
		return array();
	}

	$occurrences        = wp_seed_events_get_event_occurrences( $event_id );
	$promotions         = array();
	$promotion_ids      = array();
	$parcours_years     = array();

	foreach ( $occurrences as $occurrence ) {
		$promotion_id = absint( $occurrence['promotion_id'] ?? 0 );
		$parcours_year = wp_seed_events_normalize_parcours_year( $occurrence['parcours_year'] ?? 0 );

		if ( 0 < $promotion_id && ! isset( $promotion_ids[ $promotion_id ] ) && ! empty( $occurrence['promotion'] ) ) {
			$promotion_ids[ $promotion_id ] = true;
			$promotions[] = $occurrence['promotion'];
		}

		if ( 0 < $parcours_year ) {
			$parcours_years[ $parcours_year ] = $parcours_year;
		}
	}

	sort( $parcours_years, SORT_NUMERIC );
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
	$place              = wp_seed_events_public_event_place_data( $event_id );
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
	$short_description  = (string) get_post_meta( $event_id, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY, true );
	$short_description_effective = wp_seed_events_resolve_short_description( $description, $short_description );
	$place_address      = is_array( $place ) ? sanitize_text_field( (string) ( $place['address'] ?? '' ) ) : '';
	$practical_info     = is_array( $place ) ? sanitize_textarea_field( (string) ( $place['details'] ?? '' ) ) : '';
	$document_filename  = is_array( $media['event_document'] )
		? sanitize_file_name( (string) ( $media['event_document']['filename'] ?? '' ) )
		: '';
	$event_url          = wp_seed_events_sanitize_public_http_url( get_permalink( $event_id ) );
	$place_url          = is_array( $place )
		? wp_seed_events_sanitize_public_http_url( $place['link'] ?? '' )
		: '';
	$event_document_url = (
		is_array( $media['event_document'] )
		&& 'application/pdf' === strtolower( trim( (string) ( $media['event_document']['mime_type'] ?? '' ) ) )
	)
		? wp_seed_events_sanitize_public_http_url( $media['event_document']['url'] ?? '' )
		: '';
	$type_data = function_exists( 'wp_seed_events_event_type_data_for_event' )
		? wp_seed_events_event_type_data_for_event( $event_id )
		: array(
			'primary_type'   => null,
			'secondary_types' => array(),
			'all_types'       => array(),
		);
	$is_pinned = function_exists( 'wp_seed_events_event_is_pinned' )
		? wp_seed_events_event_is_pinned( $event_id )
		: false;

	return array(
		'id'                => $event_id,
		'slug'              => (string) $post->post_name,
		'title'             => get_the_title( $event_id ),
		'url'               => $event_url,
		'types'             => wp_seed_events_event_type_labels_for_event( $event_id ),
		'primary_type'      => $type_data['primary_type'],
		'secondary_types'   => $type_data['secondary_types'],
		'all_types'         => $type_data['all_types'],
		'is_pinned'         => $is_pinned,
		'occurrences'        => $occurrences,
		'promotions'         => $promotions,
		'parcours_years'     => array_values( $parcours_years ),
		'active_occurrences' => $active_occurrences,
		'next_occurrence'    => $next_occurrence,
		'last_occurrence'    => $last_occurrence,
		'display_occurrence' => $display_occurrence,
		'lifecycle'          => $lifecycle,
		'place'              => $place,
		'place_address'      => $place_address,
		'place_url'          => $place_url,
		'people'             => wp_seed_events_public_event_people_data( $event_id ),
		'description'                 => $description,
		'short_description'           => $short_description,
		'short_description_effective' => $short_description_effective,
		'excerpt'                     => $short_description_effective,
		'practical_info'              => $practical_info,
		'event_document_filename' => $document_filename,
		'event_document_url'      => $event_document_url,
		'featured_image'          => $media['featured_image'],
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
