<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Accept only object-like REST arguments.
 */
function wp_seed_events_gutenberg_event_visuals_preview_array_is_valid( $value ) {
	return is_array( $value );
}

/**
 * Normalize the request context without accepting arbitrary fields.
 */
function wp_seed_events_gutenberg_event_visuals_preview_context( $value ) {
	$value   = is_array( $value ) ? $value : array();
	$context = array();

	if ( array_key_exists( 'postId', $value ) ) {
		$context['postId'] = absint( $value['postId'] );
	}

	if ( array_key_exists( 'postType', $value ) && is_scalar( $value['postType'] ) ) {
		$context['postType'] = sanitize_key( (string) $value['postType'] );
	}

	if ( array_key_exists( 'queryId', $value ) ) {
		$context['queryId'] = absint( $value['queryId'] );
	}

	return $context;
}

/**
 * Restrict previews to authenticated users who can edit the requested post.
 */
function wp_seed_events_gutenberg_event_visuals_preview_permissions( WP_REST_Request $request ) {
	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'wp_seed_events_visuals_preview_authentication_required',
			__( 'Authentification requise.', 'wp-seed-events' ),
			array( 'status' => 401 )
		);
	}

	if ( ! current_user_can( 'edit_posts' ) ) {
		return new WP_Error(
			'wp_seed_events_visuals_preview_forbidden',
			__( 'Vous ne pouvez pas prévisualiser ces visuels.', 'wp-seed-events' ),
			array( 'status' => 403 )
		);
	}

	$context = wp_seed_events_gutenberg_event_visuals_preview_context( $request->get_param( 'context' ) );
	$post_id = absint( $context['postId'] ?? 0 );

	if ( 0 !== $post_id && ( null === get_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) ) {
		return new WP_Error(
			'wp_seed_events_visuals_preview_post_forbidden',
			__( 'Vous ne pouvez pas prévisualiser ce contenu.', 'wp-seed-events' ),
			array( 'status' => 403 )
		);
	}

	return true;
}

/**
 * Return shared renderer HTML for the Gutenberg editor preview.
 */
function wp_seed_events_gutenberg_event_visuals_preview( WP_REST_Request $request ) {
	$attributes = $request->get_param( 'attributes' );
	$context    = wp_seed_events_gutenberg_event_visuals_preview_context( $request->get_param( 'context' ) );
	$html       = wp_seed_events_gutenberg_event_visuals_render(
		$context,
		is_array( $attributes ) ? $attributes : array()
	);
	$empty      = '' === trim( $html );

	return rest_ensure_response(
		array(
			'html'    => $html,
			'empty'   => $empty,
			'message' => $empty ? __( 'Aucun visuel à afficher dans ce contexte.', 'wp-seed-events' ) : '',
		)
	);
}

/**
 * Register the authenticated, read-only Gutenberg preview route.
 */
function wp_seed_events_register_gutenberg_event_visuals_preview_route() {
	register_rest_route(
		'wp-seed-events/v1',
		'/gutenberg-event-visuals-preview',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'wp_seed_events_gutenberg_event_visuals_preview',
			'permission_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_permissions',
			'args'                => array(
				'attributes' => array(
					'default'           => array(),
					'validate_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_array_is_valid',
				),
				'context'    => array(
					'default'           => array(),
					'validate_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_array_is_valid',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'wp_seed_events_register_gutenberg_event_visuals_preview_route' );
