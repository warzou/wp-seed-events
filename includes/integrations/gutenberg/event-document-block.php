<?php

defined( 'ABSPATH' ) || exit;

function wp_seed_events_gutenberg_event_document_options( $attributes = array() ) {
	$attributes = is_array( $attributes ) ? $attributes : array();
	return array(
		'show_document' => is_bool( $attributes['show_document'] ?? null ) ? $attributes['show_document'] : true,
		'link_text'     => sanitize_text_field( (string) ( $attributes['link_text'] ?? 'Télécharger le document' ) ),
		'name_display'  => sanitize_key( (string) ( $attributes['name_display'] ?? 'text_name' ) ),
		'name_position' => sanitize_key( (string) ( $attributes['name_position'] ?? 'inline' ) ),
	);
}

function wp_seed_events_gutenberg_event_document_render( $context = array(), $attributes = array() ) {
	$event_id = wp_seed_events_gutenberg_event_visuals_resolve_event_id( $context );
	$event    = $event_id ? wp_seed_events_get_event_data( $event_id ) : array();
	return array() === $event ? '' : (string) wp_seed_events_render_public_event_document_section( $event, wp_seed_events_gutenberg_event_document_options( $attributes ) );
}

function wp_seed_events_render_gutenberg_event_document_block( $attributes, $content, $block ) {
	$context = is_object( $block ) && is_array( $block->context ?? null ) ? $block->context : array();
	$html    = wp_seed_events_gutenberg_event_document_render( $context, $attributes );
	if ( '' === trim( $html ) || ! function_exists( 'get_block_wrapper_attributes' ) ) {
		return '';
	}
	return sprintf( '<div %1$s>%2$s</div>', get_block_wrapper_attributes( array( 'class' => 'wp-seed-events-event-document-block' ) ), $html );
}

function wp_seed_events_register_event_document_block() {
	$build_path = __DIR__ . '/event-document-block/build';
	if ( function_exists( 'register_block_type_from_metadata' ) && is_readable( $build_path . '/block.json' ) ) {
		register_block_type_from_metadata( $build_path, array( 'render_callback' => 'wp_seed_events_render_gutenberg_event_document_block' ) );
	}
}
add_action( 'init', 'wp_seed_events_register_event_document_block', 20 );

function wp_seed_events_register_gutenberg_event_document_preview_route() {
	register_rest_route(
		'wp-seed-events/v1', '/gutenberg-event-document-preview',
		array(
			'methods' => WP_REST_Server::CREATABLE,
			'permission_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_permissions',
			'callback' => function( WP_REST_Request $request ) {
				$context = wp_seed_events_gutenberg_event_visuals_preview_context( $request->get_param( 'context' ) );
				$html = wp_seed_events_gutenberg_event_document_render( $context, $request->get_param( 'attributes' ) );
				return rest_ensure_response( array( 'html' => $html, 'empty' => '' === trim( $html ), 'message' => '' === trim( $html ) ? 'Aucun document à afficher dans ce contexte.' : '' ) );
			},
			'args' => array(
				'attributes' => array( 'default' => array(), 'validate_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_array_is_valid' ),
				'context' => array( 'default' => array(), 'validate_callback' => 'wp_seed_events_gutenberg_event_visuals_preview_array_is_valid' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'wp_seed_events_register_gutenberg_event_document_preview_route' );
