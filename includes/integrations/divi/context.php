<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether a post ID belongs to a WP Seed event.
 */
function wp_seed_events_divi_is_event( $post_id ) {
	$post_id = absint( $post_id );

	return 0 !== $post_id && 'wp_seed_event' === get_post_type( $post_id );
}

/**
 * Resolve the current event from a Divi rendering context.
 *
 * A real but incompatible loop item must not fall back to its holder page.
 */
function wp_seed_events_divi_resolve_event_id( $context = array() ) {
	$context = is_array( $context ) ? $context : array();
	$loop_id = absint( $context['loop_id'] ?? 0 );

	if ( 0 !== $loop_id ) {
		return wp_seed_events_divi_is_event( $loop_id ) ? $loop_id : 0;
	}

	$post_id = absint( $context['post_id'] ?? 0 );
	$post_type = isset( $context['post_type'] ) && is_scalar( $context['post_type'] )
		? sanitize_key( (string) $context['post_type'] )
		: '';
	$strict_post = ! empty( $context['strict_post'] );

	if ( $strict_post && '' !== $post_type && 'wp_seed_event' !== $post_type ) {
		return 0;
	}

	if ( wp_seed_events_divi_is_event( $post_id ) ) {
		return $post_id;
	}

	if ( $strict_post && 0 !== $post_id ) {
		return 0;
	}

	global $wp_seed_events_public_event_id;

	$public_event_id = absint( $wp_seed_events_public_event_id ?? 0 );

	if ( wp_seed_events_divi_is_event( $public_event_id ) ) {
		return $public_event_id;
	}

	$queried_post_id = absint( get_queried_object_id() );

	if ( wp_seed_events_divi_is_event( $queried_post_id ) ) {
		return $queried_post_id;
	}

	$current_post_id = absint( $context['current_post_id'] ?? get_the_ID() );

	return wp_seed_events_divi_is_event( $current_post_id ) ? $current_post_id : 0;
}
