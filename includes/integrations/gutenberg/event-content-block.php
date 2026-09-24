<?php
/**
 * Gutenberg adapter for canonical rich event content.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_gutenberg_event_content_render( $context = array() ) {
	$event_id = wp_seed_events_gutenberg_event_people_resolve_event_id( $context );

	if ( 0 === $event_id ) {
		return '';
	}

	$event = wp_seed_events_get_event_data( $event_id );

	return array() === $event ? '' : wp_seed_events_render_rich_content( $event['description'] ?? '' );
}

function wp_seed_events_render_gutenberg_event_content_block( $attributes, $content, $block ) {
	unset( $attributes, $content );
	$html = wp_seed_events_gutenberg_event_content_render(
		wp_seed_events_gutenberg_event_people_block_context( $block )
	);

	if ( '' === trim( $html ) || ! function_exists( 'get_block_wrapper_attributes' ) ) {
		return '';
	}

	return sprintf(
		'<div %1$s>%2$s</div>',
		get_block_wrapper_attributes( array( 'class' => 'wp-seed-events-rich-content' ) ),
		$html
	);
}

function wp_seed_events_register_event_content_block() {
	static $registered = false;

	if ( $registered || ! function_exists( 'register_block_type_from_metadata' ) ) {
		return;
	}

	$build_path = __DIR__ . '/event-content-block/build';

	if ( ! is_readable( $build_path . '/block.json' ) ) {
		return;
	}

	$registered = (bool) register_block_type_from_metadata(
		$build_path,
		array( 'render_callback' => 'wp_seed_events_render_gutenberg_event_content_block' )
	);
}
add_action( 'init', 'wp_seed_events_register_event_content_block', 20 );

function wp_seed_events_register_event_content_preview_route() {
	register_rest_route(
		'wp-seed-events/v1',
		'/gutenberg-event-content-preview',
		array(
			'methods'             => 'POST',
			'permission_callback' => static function ( $request ) {
				$context  = (array) $request->get_param( 'context' );
				$event_id = absint( $context['postId'] ?? 0 );

				return 0 !== $event_id && current_user_can( 'edit_post', $event_id );
			},
			'callback'            => static function ( $request ) {
				$html = wp_seed_events_gutenberg_event_content_render( (array) $request->get_param( 'context' ) );

				return rest_ensure_response( array( 'html' => $html, 'empty' => '' === trim( $html ) ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'wp_seed_events_register_event_content_preview_route' );
