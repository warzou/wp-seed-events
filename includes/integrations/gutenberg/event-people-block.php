<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether a post ID belongs to a public WP Seed event.
 */
function wp_seed_events_gutenberg_event_people_is_event( $post_id ) {
	$post_id = absint( $post_id );

	return 0 !== $post_id
		&& 'wp_seed_event' === get_post_type( $post_id )
		&& 'publish' === get_post_status( $post_id );
}

/**
 * Read the public context inherited by the dynamic block.
 */
function wp_seed_events_gutenberg_event_people_block_context( $block ) {
	return is_object( $block ) && isset( $block->context ) && is_array( $block->context )
		? $block->context
		: array();
}

/**
 * Resolve the current event without persisting an event ID in block attributes.
 */
function wp_seed_events_gutenberg_event_people_resolve_event_id( $context = array() ) {
	$context                   = is_array( $context ) ? $context : array();
	$has_explicit_post_context = array_key_exists( 'postId', $context ) || array_key_exists( 'postType', $context );
	$post_id                   = absint( $context['postId'] ?? 0 );
	$post_type                 = is_scalar( $context['postType'] ?? null ) ? sanitize_key( (string) $context['postType'] ) : '';

	if ( $has_explicit_post_context ) {
		if ( ( '' === $post_type || 'wp_seed_event' === $post_type ) && wp_seed_events_gutenberg_event_people_is_event( $post_id ) ) {
			return $post_id;
		}

		return 0;
	}

	global $wp_seed_events_public_event_id;

	$public_event_id = absint( $wp_seed_events_public_event_id ?? 0 );

	if ( wp_seed_events_gutenberg_event_people_is_event( $public_event_id ) ) {
		return $public_event_id;
	}

	$current_post_id = absint( get_the_ID() );

	return wp_seed_events_gutenberg_event_people_is_event( $current_post_id ) ? $current_post_id : 0;
}

/**
 * Keep a valid boolean block attribute or use its declared default.
 */
function wp_seed_events_gutenberg_event_people_boolean_option( $value, $default = true ) {
	return is_bool( $value ) ? $value : (bool) $default;
}

/**
 * Normalize block attributes to the shared public renderer contract.
 */
function wp_seed_events_gutenberg_event_people_options( $attributes = array() ) {
	$attributes = is_array( $attributes ) ? $attributes : array();
	$title      = isset( $attributes['title'] ) && is_string( $attributes['title'] )
		? $attributes['title']
		: 'Contacts et intervenants';

	$role  = wp_seed_events_public_people_role_option( $attributes['role'] ?? 'all' );
	$roles = wp_seed_events_public_people_roles_option( $attributes['roles'] ?? array() );

	if ( array() === $roles && 'all' !== $role ) {
		$roles = array( $role );
	}

	return array(
		'title'         => $title,
		'heading_level' => wp_seed_events_public_heading_level_option( $attributes['heading_level'] ?? 'h2' ),
		'roles'         => $roles,
		'role'          => $role,
		'show_name'     => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_name'] ?? true ),
		'show_roles'    => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_roles'] ?? true ),
		'show_email'    => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_email'] ?? true ),
		'show_phone'    => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_phone'] ?? true ),
		'show_link'     => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_link'] ?? true ),
		'contract'      => 'composable-v2',
		'legacy_phone_action' => array_key_exists( 'link_phone', $attributes ) && false === $attributes['link_phone'] ? 'none' : null,
		'link_email'    => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['link_email'] ?? true ),
		'link_url'      => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['link_url'] ?? true ),
		'contact_layouts' => array(
			'desktop' => wp_seed_events_public_people_contact_layout_option( $attributes['contact_layout'] ?? 'stacked' ),
			'tablet'  => wp_seed_events_public_people_contact_layout_option( $attributes['contact_layout'] ?? 'stacked' ),
			'phone'   => wp_seed_events_public_people_contact_layout_option( $attributes['contact_layout'] ?? 'stacked' ),
		),
		'show_contact_separator' => wp_seed_events_gutenberg_event_people_boolean_option( $attributes['show_contact_separator'] ?? false, false ),
		'contact_separator' => wp_seed_events_public_date_separator_character_option( $attributes['contact_separator'] ?? "\u{2014}" ),
		'layout'        => wp_seed_events_public_event_people_layout_option( $attributes['layout'] ?? 'list' ),
	);
}

/**
 * Render the shared people section for a Gutenberg context.
 */
function wp_seed_events_gutenberg_event_people_render( $context = array(), $attributes = array() ) {
	$event_id = wp_seed_events_gutenberg_event_people_resolve_event_id( $context );

	if ( 0 === $event_id ) {
		return '';
	}

	$event = wp_seed_events_get_event_data( $event_id );

	if ( array() === $event ) {
		return '';
	}

	return (string) wp_seed_events_render_public_event_people_section(
		$event,
		wp_seed_events_gutenberg_event_people_options( $attributes )
	);
}

/**
 * Render the dynamic block through the Event Data API and shared people renderer.
 */
function wp_seed_events_render_gutenberg_event_people_block( $attributes, $content, $block ) {
	$html = wp_seed_events_gutenberg_event_people_render(
		wp_seed_events_gutenberg_event_people_block_context( $block ),
		$attributes
	);

	if ( '' === trim( $html ) || ! function_exists( 'get_block_wrapper_attributes' ) ) {
		return '';
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'wp-seed-events-event-people-block',
		)
	);

	return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $html );
}

/**
 * Register the Gutenberg event people block from its compiled metadata.
 */
function wp_seed_events_register_event_people_block() {
	static $registered = false;

	if ( $registered || ! function_exists( 'register_block_type_from_metadata' ) ) {
		return;
	}

	$build_path = __DIR__ . '/event-people-block/build';

	if ( ! is_readable( $build_path . '/block.json' ) ) {
		return;
	}

	$registered = (bool) register_block_type_from_metadata(
		$build_path,
		array(
			'render_callback' => 'wp_seed_events_render_gutenberg_event_people_block',
		)
	);
}
add_action( 'init', 'wp_seed_events_register_event_people_block', 20 );
