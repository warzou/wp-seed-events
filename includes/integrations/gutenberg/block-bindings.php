<?php
/**
 * Gutenberg Block Bindings prototype for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wp_seed_events_register_gutenberg_block_bindings_source' );

function wp_seed_events_register_gutenberg_block_bindings_source() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}

	register_block_bindings_source(
		'wp-seed-events/event-field',
		array(
			'label'              => 'WP Seed Events',
			'get_value_callback' => 'wp_seed_events_gutenberg_block_binding_value',
			'uses_context'       => array( 'postId', 'postType' ),
		)
	);
}

function wp_seed_events_gutenberg_block_binding_value( $source_args, $block_instance, $attribute_name ) {
	$field = isset( $source_args['field'] ) ? sanitize_key( (string) $source_args['field'] ) : '';

	if ( ! in_array( $field, wp_seed_events_gutenberg_block_binding_fields(), true ) ) {
		return '';
	}

	$event_id = wp_seed_events_gutenberg_block_binding_event_id( $source_args, $block_instance );

	if ( 0 === $event_id ) {
		return '';
	}

	$value = wp_seed_events_dynamic_data_get_value( $field, $event_id );

	return is_string( $value ) ? $value : '';
}

function wp_seed_events_gutenberg_block_binding_fields() {
	return array(
		'title',
		'next_date',
		'display_date',
		'place',
		'description',
	);
}

function wp_seed_events_gutenberg_block_binding_event_id( $source_args, $block_instance ) {
	$context = $block_instance instanceof WP_Block && isset( $block_instance->context ) && is_array( $block_instance->context )
		? $block_instance->context
		: array();

	if ( array_key_exists( 'postId', $context ) || array_key_exists( 'postType', $context ) ) {
		return wp_seed_events_gutenberg_event_dates_resolve_event_id( $context );
	}

	if ( isset( $source_args['eventId'] ) ) {
		$event_id = absint( $source_args['eventId'] );

		if ( 0 !== $event_id ) {
			return $event_id;
		}
	}

	if ( isset( $source_args['event_id'] ) ) {
		$event_id = absint( $source_args['event_id'] );

		if ( 0 !== $event_id ) {
			return $event_id;
		}
	}

	return wp_seed_events_gutenberg_event_dates_resolve_event_id( $context );
}