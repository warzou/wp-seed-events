<?php
/**
 * Gutenberg adapter for canonical rich event content.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_gutenberg_event_content_block_context( $block ) {
	return is_object( $block ) && isset( $block->context ) && is_array( $block->context )
		? $block->context
		: array();
}

function wp_seed_events_gutenberg_event_content_resolve_event_id( $context = array(), $allow_non_public = false ) {
	$context                   = is_array( $context ) ? $context : array();
	$has_explicit_post_context = array_key_exists( 'postId', $context ) || array_key_exists( 'postType', $context );
	$post_id                   = absint( $context['postId'] ?? 0 );
	$post_type                 = is_scalar( $context['postType'] ?? null ) ? sanitize_key( (string) $context['postType'] ) : '';

	if ( $has_explicit_post_context ) {
		return ( '' === $post_type || 'wp_seed_event' === $post_type )
			&& 'wp_seed_event' === get_post_type( $post_id )
			&& ( $allow_non_public || 'publish' === get_post_status( $post_id ) )
			? $post_id
			: 0;
	}

	global $wp_seed_events_public_event_id;

	$public_event_id = absint( $wp_seed_events_public_event_id ?? 0 );
	if ( 0 !== $public_event_id && 'wp_seed_event' === get_post_type( $public_event_id ) && 'publish' === get_post_status( $public_event_id ) ) {
		return $public_event_id;
	}

	$current_post_id = absint( get_the_ID() );

	return 0 !== $current_post_id
		&& 'wp_seed_event' === get_post_type( $current_post_id )
		&& 'publish' === get_post_status( $current_post_id )
		? $current_post_id
		: 0;
}

function wp_seed_events_gutenberg_event_content_contains_self( $content ) {
	$content = (string) $content;

	if ( function_exists( 'has_block' ) ) {
		return has_block( 'wp-seed-events/event-content-block', $content );
	}

	return false !== strpos( $content, '<!-- wp:wp-seed-events/event-content-block' );
}

function wp_seed_events_gutenberg_event_content_render( $context = array(), $allow_non_public = false ) {
	static $rendering = array();

	$event_id = wp_seed_events_gutenberg_event_content_resolve_event_id( $context, $allow_non_public );
	if ( 0 === $event_id || isset( $rendering[ $event_id ] ) ) {
		return '';
	}

	$event       = wp_seed_events_get_event_data( $event_id );
	$description = array() === $event ? '' : (string) ( $event['description'] ?? '' );

	if ( $allow_non_public && 'publish' !== get_post_status( $event_id ) ) {
		$post        = get_post( $event_id );
		$description = $post instanceof WP_Post ? (string) $post->post_content : '';
	}

	if ( '' === trim( $description ) || wp_seed_events_gutenberg_event_content_contains_self( $description ) ) {
		return '';
	}

	$rendering[ $event_id ] = true;

	try {
		return wp_seed_events_render_rich_content( $description );
	} finally {
		unset( $rendering[ $event_id ] );
	}
}

function wp_seed_events_render_gutenberg_event_content_block( $attributes, $content, $block ) {
	unset( $attributes, $content );

	$html = wp_seed_events_gutenberg_event_content_render(
		wp_seed_events_gutenberg_event_content_block_context( $block )
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

				return 0 !== $event_id && 'wp_seed_event' === get_post_type( $event_id ) && current_user_can( 'edit_post', $event_id );
			},
			'callback'            => static function ( $request ) {
				$html = wp_seed_events_gutenberg_event_content_render( (array) $request->get_param( 'context' ), true );

				return rest_ensure_response( array( 'html' => $html, 'empty' => '' === trim( $html ) ) );
			},
		)
	);
}
add_action( 'rest_api_init', 'wp_seed_events_register_event_content_preview_route' );
