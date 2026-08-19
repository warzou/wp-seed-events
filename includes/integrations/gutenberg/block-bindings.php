<?php
/**
 * Gutenberg Block Bindings prototype for WP Seed Events.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wp_seed_events_register_gutenberg_block_bindings_source' );
add_action( 'rest_api_init', 'wp_seed_events_register_gutenberg_block_bindings_rest_field' );

if ( function_exists( 'add_filter' ) ) {
	add_filter( 'render_block', 'wp_seed_events_gutenberg_multiline_excerpt_block', 10, 2 );
}

function wp_seed_events_register_gutenberg_block_bindings_source() {
	if ( ! function_exists( 'register_block_bindings_source' ) ) {
		return;
	}

	register_block_bindings_source(
		'wp-seed-events/event-field',
		array(
			'label'              => 'WP Seed Events',
			'get_value_callback' => 'wp_seed_events_gutenberg_block_binding_value',
			'uses_context'       => array( 'postId', 'postType', 'queryId' ),
		)
	);

	register_block_bindings_source(
		'wp-seed-events/occurrence-field',
		array(
			'label'              => 'WP Seed Events — Occurrence',
			'get_value_callback' => 'wp_seed_events_gutenberg_occurrence_block_binding_value',
			'uses_context'       => array( 'wpSeedEvents/occurrence' ),
		)
	);
}

function wp_seed_events_gutenberg_block_binding_preview_fields() {
	return array( 'types', 'status', 'display_date', 'place', 'contact', 'excerpt', 'url' );
}

function wp_seed_events_gutenberg_multiline_excerpt_block( $block_content, $block ) {
	$binding = is_array( $block )
		? ( $block['attrs']['metadata']['bindings']['content'] ?? array() )
		: array();

	if (
		! is_array( $binding )
		|| 'wp-seed-events/event-field' !== ( $binding['source'] ?? '' )
		|| 'excerpt' !== ( $binding['args']['field'] ?? '' )
		|| ! class_exists( 'WP_HTML_Tag_Processor' )
	) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( (string) $block_content );

	if ( $processor->next_tag() ) {
		$processor->add_class( 'wp-seed-events-multiline-text' );
		return $processor->get_updated_html();
	}

	return $block_content;
}

function wp_seed_events_register_gutenberg_block_bindings_rest_field() {
	if ( ! function_exists( 'register_rest_field' ) ) {
		return;
	}

	$properties = array();

	foreach ( wp_seed_events_gutenberg_block_binding_preview_fields() as $field ) {
		$properties[ $field ] = array(
			'type'     => 'string',
			'readonly' => true,
		);
	}

	register_rest_field(
		'wp_seed_event',
		'wp_seed_events_public_fields',
		array(
			'get_callback' => 'wp_seed_events_gutenberg_block_bindings_rest_values',
			'schema'       => array(
				'description' => 'Public WP Seed Events values used by the block editor preview.',
				'type'        => 'object',
				'context'     => array( 'edit' ),
				'readonly'    => true,
				'properties'  => $properties,
			),
		)
	);
}

function wp_seed_events_gutenberg_block_bindings_rest_values( $prepared, $field_name, $request ) {
	$prepared_id = is_array( $prepared )
		? ( $prepared['id'] ?? 0 )
		: ( is_object( $prepared ) ? ( $prepared->ID ?? 0 ) : 0 );
	$event_id = absint( $prepared_id );
	$context  = is_object( $request ) && method_exists( $request, 'get_param' )
		? sanitize_key( (string) $request->get_param( 'context' ) )
		: '';

	if (
		0 === $event_id
		|| 'edit' !== $context
		|| ! current_user_can( 'edit_post', $event_id )
	) {
		return null;
	}

	$values = array();

	foreach ( wp_seed_events_gutenberg_block_binding_preview_fields() as $field ) {
		$value = wp_seed_events_dynamic_data_get_value( $field, $event_id );
		$values[ $field ] = is_string( $value ) ? $value : '';
	}

	return $values;
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

	$fields = wp_seed_events_dynamic_data_fields();

	if ( 'image' === ( $fields[ $field ]['type'] ?? '' ) ) {
		return wp_seed_events_dynamic_data_get_image_attribute( $field, $attribute_name, $event_id );
	}

	$value = wp_seed_events_dynamic_data_get_value( $field, $event_id );

	return is_string( $value ) ? $value : '';
}

function wp_seed_events_gutenberg_block_binding_fields() {
	return array_keys( wp_seed_events_dynamic_data_fields() );
}

/**
 * Resolve a public occurrence field from an explicit block or request context.
 *
 * @param array    $source_args    Binding source arguments.
 * @param WP_Block $block_instance Current block.
 * @param string   $attribute_name Bound attribute.
 * @return string
 */
function wp_seed_events_gutenberg_occurrence_block_binding_value( $source_args, $block_instance, $attribute_name ) {
	unset( $attribute_name );

	$field = isset( $source_args['field'] ) ? sanitize_key( (string) $source_args['field'] ) : '';

	if ( ! array_key_exists( $field, wp_seed_events_occurrence_dynamic_data_fields() ) ) {
		return '';
	}

	$context = array();

	if (
		class_exists( 'WP_Block' )
		&& $block_instance instanceof WP_Block
		&& isset( $block_instance->context['wpSeedEvents/occurrence'] )
		&& is_array( $block_instance->context['wpSeedEvents/occurrence'] )
	) {
		$context = $block_instance->context['wpSeedEvents/occurrence'];
	}

	return wp_seed_events_occurrence_context_value( $field, array() !== $context ? $context : null );
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
