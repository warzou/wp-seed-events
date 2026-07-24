<?php
/**
 * Gutenberg Query Loop adapter for canonical event collections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_gutenberg_collection_namespace() {
	return 'wp-seed-events/event-collection';
}

function wp_seed_events_gutenberg_collection_orderby() {
	return 'business_date';
}

function wp_seed_events_gutenberg_collection_is_enabled( $value ) {
	return true === $value || 1 === $value || '1' === $value || 'true' === strtolower( (string) $value );
}

/**
 * Validate the builder-owned query attributes before calling the contract.
 *
 * @param array $source Query block attributes or REST parameters.
 * @return array|null
 */
function wp_seed_events_gutenberg_collection_settings( $source ) {
	$source = is_array( $source ) ? $source : array();

	if (
		! wp_seed_events_gutenberg_collection_is_enabled( $source['wpSeedEventsCollection'] ?? false )
		|| wp_seed_events_gutenberg_collection_orderby() !== (string) ( $source['wpSeedEventsOrderBy'] ?? '' )
	) {
		return null;
	}

	$status = strtolower( trim( (string) ( $source['wpSeedEventsStatus'] ?? 'upcoming' ) ) );
	$pinned = strtolower( trim( (string) ( $source['wpSeedEventsPinned'] ?? 'all' ) ) );
	$order  = strtoupper( trim( (string) ( $source['wpSeedEventsOrder'] ?? 'ASC' ) ) );

	if (
		! in_array( $status, array( 'upcoming', 'past', 'all' ), true )
		|| ! in_array( $pinned, array( 'all', 'only' ), true )
		|| ! in_array( $order, array( 'ASC', 'DESC' ), true )
	) {
		return null;
	}

	return array(
		'type'   => sanitize_title( (string) ( $source['wpSeedEventsType'] ?? '' ) ),
		'status' => $status,
		'pinned' => $pinned,
		'order'  => $order,
	);
}

function wp_seed_events_gutenberg_collection_empty_query( $query_args ) {
	$query_args                         = is_array( $query_args ) ? $query_args : array();
	$query_args['post__in']             = array( 0 );
	$query_args['orderby']              = 'post__in';
	$query_args['order']                = 'ASC';
	$query_args['ignore_sticky_posts'] = true;

	return $query_args;
}

/**
 * Apply one validated Gutenberg collection to existing query arguments.
 *
 * @param array $query_args Existing WP_Query arguments.
 * @param array $source     Query block attributes or REST parameters.
 * @return array
 */
function wp_seed_events_gutenberg_apply_collection_query( $query_args, $source ) {
	$settings = wp_seed_events_gutenberg_collection_settings( $source );

	if ( null === $settings ) {
		return wp_seed_events_gutenberg_collection_empty_query( $query_args );
	}

	$query_args = wp_seed_events_apply_collection_to_query_args( $query_args, $settings );

	return $query_args;
}

/**
 * Keep the frontend Query Loop aligned with the canonical collection.
 *
 * @param array    $query Query arguments built by Core.
 * @param WP_Block $block Query block instance.
 * @param int      $page  Current pagination page.
 * @return array
 */
function wp_seed_events_gutenberg_filter_collection_query_vars( $query, $block, $page ) {
	$attributes = is_object( $block ) && isset( $block->parsed_block['attrs'] ) && is_array( $block->parsed_block['attrs'] )
		? $block->parsed_block['attrs']
		: array();
	$source     = isset( $attributes['query'] ) && is_array( $attributes['query'] ) ? $attributes['query'] : array();

	if ( is_object( $block ) && isset( $block->context['query'] ) && is_array( $block->context['query'] ) ) {
		$source = $block->context['query'];
	}

	$namespace = (string) ( $attributes['namespace'] ?? '' );

	if (
		( '' !== $namespace && wp_seed_events_gutenberg_collection_namespace() !== $namespace )
		|| 'wp_seed_event' !== sanitize_key( (string) ( $source['postType'] ?? '' ) )
		|| ! wp_seed_events_gutenberg_collection_is_enabled( $source['wpSeedEventsCollection'] ?? false )
	) {
		return $query;
	}

	return wp_seed_events_gutenberg_apply_collection_query( $query, $source );
}
add_filter( 'query_loop_block_query_vars', 'wp_seed_events_gutenberg_filter_collection_query_vars', 20, 3 );

/**
 * Register the custom parameters used by the Query Loop editor preview.
 *
 * @param mixed $value Raw REST parameter.
 * @return string
 */
function wp_seed_events_gutenberg_collection_rest_sanitize_slug( $value ) {
	if ( ! is_scalar( $value ) ) {
		return '';
	}

	return sanitize_title( (string) $value );
}

/**
 * Register the custom parameters used by the Query Loop editor preview.
 *
 * @param array $params REST collection parameters.
 * @return array
 */
function wp_seed_events_gutenberg_collection_rest_params( $params ) {
	$params['wpSeedEventsCollection'] = array(
		'description' => 'Identify a WP Seed Events collection Query Loop.',
		'type'        => 'boolean',
	);
	$params['wpSeedEventsType']       = array(
		'description'       => 'Filter the event collection by public type.',
		'type'              => 'string',
		'sanitize_callback' => 'wp_seed_events_gutenberg_collection_rest_sanitize_slug',
	);
	$params['wpSeedEventsStatus']     = array(
		'description' => 'Filter the event collection by public lifecycle.',
		'type'        => 'string',
		'enum'        => array( 'upcoming', 'past', 'all' ),
	);
	$params['wpSeedEventsPinned']     = array(
		'description' => 'Filter the event collection by pinned state.',
		'type'        => 'string',
		'enum'        => array( 'all', 'only' ),
	);
	$params['wpSeedEventsOrder']      = array(
		'description' => 'Order the canonical business date.',
		'type'        => 'string',
		'enum'        => array( 'ASC', 'DESC' ),
	);
	$params['wpSeedEventsOrderBy']    = array(
		'description' => 'Identify the canonical event business-date order.',
		'type'        => 'string',
		'enum'        => array( 'business_date' ),
	);

	return $params;
}
add_filter( 'rest_wp_seed_event_collection_params', 'wp_seed_events_gutenberg_collection_rest_params' );

/**
 * Keep the Query Loop editor REST preview aligned with the frontend query.
 *
 * @param array           $query_args REST WP_Query arguments.
 * @param WP_REST_Request $request    REST request.
 * @return array
 */
function wp_seed_events_gutenberg_filter_collection_rest_query( $query_args, $request ) {
	$source = array();

	foreach ( array( 'wpSeedEventsCollection', 'wpSeedEventsType', 'wpSeedEventsStatus', 'wpSeedEventsPinned', 'wpSeedEventsOrder', 'wpSeedEventsOrderBy' ) as $key ) {
		$source[ $key ] = $request->get_param( $key );
	}

	if ( ! wp_seed_events_gutenberg_collection_is_enabled( $source['wpSeedEventsCollection'] ) ) {
		return $query_args;
	}

	return wp_seed_events_gutenberg_apply_collection_query( $query_args, $source );
}
add_filter( 'rest_wp_seed_event_query', 'wp_seed_events_gutenberg_filter_collection_rest_query', 20, 2 );

/**
 * Enqueue the Query Loop variation and its inspector controls.
 */
function wp_seed_events_enqueue_gutenberg_event_collection_query() {
	$build_path = __DIR__ . '/event-collection-query/build';
	$asset_path = $build_path . '/index.asset.php';
	$script_path = $build_path . '/index.js';

	if ( ! is_readable( $asset_path ) || ! is_readable( $script_path ) ) {
		return;
	}

	$asset  = require $asset_path;
	$handle = 'wp-seed-events-event-collection-query';
	$types  = array(
		array(
			'label' => 'Tous les types',
			'value' => '',
		),
	);
	$seen   = array( '' => true );

	foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) {
		$value = wp_seed_events_event_type_public_slug( $type_key );

		if ( '' === $value || isset( $seen[ $value ] ) ) {
			continue;
		}

		$seen[ $value ] = true;
		$types[]        = array(
			'label' => wp_strip_all_tags( (string) $type_label ),
			'value' => $value,
		);
	}

	wp_enqueue_script(
		$handle,
		plugins_url( 'event-collection-query/build/index.js', __FILE__ ),
		$asset['dependencies'] ?? array(),
		$asset['version'] ?? WP_SEED_EVENTS_VERSION,
		true
	);

	wp_add_inline_script(
		$handle,
		'window.wpSeedEventsCollectionQuerySettings = ' . wp_json_encode( array( 'eventTypes' => $types ) ) . ';',
		'before'
	);
	wp_set_script_translations( $handle, 'wp-seed-events' );
}
add_action( 'enqueue_block_editor_assets', 'wp_seed_events_enqueue_gutenberg_event_collection_query' );
