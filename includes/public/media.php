<?php
/**
 * Read-only event media normalization for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_empty_event_media() {
	return array(
		'featured_image'        => null,
		'communication_visual'  => null,
		'communication_visuals' => array(),
		'other_visuals'         => array(),
		'event_document'        => null,
	);
}

function wp_seed_events_get_media_object( $attachment_id, $expected_mime = '' ) {
	$attachment_id = absint( $attachment_id );
	$attachment    = get_post( $attachment_id );

	if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
		return null;
	}

	$mime_type = (string) get_post_mime_type( $attachment_id );

	if ( '' === $mime_type ) {
		return null;
	}

	if ( '' !== $expected_mime ) {
		$is_prefix = '/' === substr( $expected_mime, -1 );
		$is_valid  = $is_prefix
			? 0 === strpos( $mime_type, $expected_mime )
			: $expected_mime === $mime_type;

		if ( ! $is_valid ) {
			return null;
		}
	}

	$url = wp_get_attachment_url( $attachment_id );

	if ( ! $url ) {
		return null;
	}

	$metadata = wp_get_attachment_metadata( $attachment_id );
	$metadata = is_array( $metadata ) ? $metadata : array();
	$width    = isset( $metadata['width'] ) && is_numeric( $metadata['width'] ) ? absint( $metadata['width'] ) : null;
	$height   = isset( $metadata['height'] ) && is_numeric( $metadata['height'] ) ? absint( $metadata['height'] ) : null;

	return array(
		'id'        => $attachment_id,
		'url'       => (string) $url,
		'mime_type' => $mime_type,
		'title'     => (string) $attachment->post_title,
		'alt'       => (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ),
		'width'     => $width,
		'height'    => $height,
	);
}

function wp_seed_events_normalize_communication_visuals( $featured_image, $illustrations ) {
	$visuals = array();
	$seen    = array();
	$media   = array_merge(
		is_array( $featured_image ) ? array( $featured_image ) : array(),
		is_array( $illustrations ) ? $illustrations : array()
	);

	foreach ( $media as $item ) {
		if ( ! is_array( $item ) || empty( $item['id'] ) ) {
			continue;
		}

		$attachment_id = absint( $item['id'] );

		if ( 0 === $attachment_id || isset( $seen[ $attachment_id ] ) ) {
			continue;
		}

		$seen[ $attachment_id ] = true;
		$visuals[]              = $item;
	}

	return $visuals;
}

function wp_seed_events_get_event_media( $event_id ) {
	$event_id = absint( $event_id );
	$event    = get_post( $event_id );

	if ( ! $event || 'wp_seed_event' !== $event->post_type ) {
		return wp_seed_events_empty_event_media();
	}

	$featured_image   = wp_seed_events_get_media_object( get_post_thumbnail_id( $event_id ), 'image/' );
	$illustration_ids = get_post_meta( $event_id, '_wp_seed_event_illustration_ids', true );
	$illustration_ids = is_array( $illustration_ids ) ? $illustration_ids : array();
	$illustrations    = array();

	foreach ( $illustration_ids as $illustration_id ) {
		$illustration = wp_seed_events_get_media_object( $illustration_id, 'image/' );

		if ( is_array( $illustration ) ) {
			$illustrations[] = $illustration;
		}
	}

	$communication_visuals = wp_seed_events_normalize_communication_visuals( $featured_image, $illustrations );
	$event_document        = wp_seed_events_get_media_object(
		get_post_meta( $event_id, '_wp_seed_event_flyer_pdf_id', true ),
		'application/pdf'
	);

	return array(
		'featured_image'        => $featured_image,
		'communication_visual'  => $communication_visuals[0] ?? null,
		'communication_visuals' => $communication_visuals,
		'other_visuals'         => array_slice( $communication_visuals, 1 ),
		'event_document'        => $event_document,
	);
}
