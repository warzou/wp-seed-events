<?php
/**
 * Divi condition backed by the canonical Events collection query.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_divi_event_results_condition_name() {
	return 'wpSeedEventsHasResults';
}

function wp_seed_events_divi_event_results_condition_label() {
	return 'WP Seed Events — Événements disponibles';
}

/** Normalize the query controls stored by the custom Divi condition. */
function wp_seed_events_divi_event_results_condition_settings( $settings ) {
	$settings = is_array( $settings ) ? $settings : array();
	$status   = sanitize_key( (string) ( $settings['eventStatus'] ?? 'upcoming' ) );
	$pinned   = sanitize_key( (string) ( $settings['eventPinned'] ?? 'all' ) );
	$types    = wp_seed_events_divi_flatten_term_values( $settings['eventTypes'] ?? array() );
	$operator = sanitize_key( (string) ( $settings['resultCountOperator'] ?? 'at_least' ) );
	$count    = absint( $settings['resultCount'] ?? 1 );

	if ( ! in_array( $status, array( 'upcoming', 'to_schedule', 'past', 'all' ), true ) ) {
		$status = 'upcoming';
	}

	if ( ! in_array( $pinned, array( 'all', 'featured_only', 'exclude_featured' ), true ) ) {
		$pinned = 'all';
	}

	if ( ! in_array( $operator, array( 'equals', 'at_least', 'greater_than' ), true ) ) {
		$operator = 'at_least';
	}

	return array(
		'status'   => $status,
		'types'    => $types,
		'pinned'   => $pinned,
		'operator' => $operator,
		'count'    => $count,
	);
}

/** Build the canonical collection query used by result-count conditions. */
function wp_seed_events_divi_event_results_condition_query( $settings, $limit ) {
	$args = array(
		'post_type'           => array( 'wp_seed_event' ),
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, absint( $limit ) ),
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'suppress_filters'    => false,
		'meta_query'          => array(
			array(
				'key'     => 'wp_seed_events_status',
				'value'   => $settings['status'],
				'compare' => '=',
			),
		),
	);
	$controls = array(
		'types_present'  => true,
		'types'          => $settings['types'],
		'pinned_present' => true,
		'pinned'         => $settings['pinned'],
	);

	return wp_seed_events_divi_apply_collection_query( $args, '', $controls );
}

/** Return the smallest query limit that can decide the requested comparison. */
function wp_seed_events_divi_event_results_condition_query_limit( $operator, $count ) {
	$count = absint( $count );

	if ( 'at_least' === $operator ) {
		return max( 1, $count );
	}

	return max( 1, $count + 1 );
}

/** Evaluate the optional cardinality rule against the canonical collection. */
function wp_seed_events_divi_event_results_condition_matches_count( $settings ) {
	$settings = wp_seed_events_divi_event_results_condition_settings( $settings );

	if ( 'at_least' === $settings['operator'] && 0 === $settings['count'] ) {
		return true;
	}

	$limit  = wp_seed_events_divi_event_results_condition_query_limit( $settings['operator'], $settings['count'] );
	$result = get_posts( wp_seed_events_divi_event_results_condition_query( $settings, $limit ) );
	$found  = is_array( $result ) ? count( $result ) : 0;

	if ( 'equals' === $settings['operator'] ) {
		return $found === $settings['count'];
	}

	if ( 'greater_than' === $settings['operator'] ) {
		return $found > $settings['count'];
	}

	return $found >= $settings['count'];
}

/**
 * Test whether the condition's Events query has at least one public result.
 *
 * The condition only translates Divi settings. Date, lifecycle and indexed
 * selection remain owned by the canonical collection pipeline.
 */
function wp_seed_events_divi_event_results_condition_has_results( $settings ) {
	$settings = wp_seed_events_divi_event_results_condition_settings( $settings );

	return array() !== get_posts( wp_seed_events_divi_event_results_condition_query( $settings, 1 ) );
}

/** Evaluate the custom condition during Divi's server render. */
function wp_seed_events_divi_evaluate_event_results_condition( $result, $condition_name, $condition_settings, $condition_id ) {
	unset( $condition_id );

	if ( wp_seed_events_divi_event_results_condition_name() !== $condition_name ) {
		return $result;
	}

	return wp_seed_events_divi_event_results_condition_matches_count( $condition_settings );
}
add_filter(
	'divi_module_options_conditions_is_custom_condition_true',
	'wp_seed_events_divi_evaluate_event_results_condition',
	10,
	4
);

/** Return only canonical event types for new Visual Builder conditions. */
function wp_seed_events_divi_event_results_condition_type_options() {
	$options = array();
	$taxonomy = wp_seed_events_event_type_taxonomy();

	foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) {
		$slug = wp_seed_events_native_event_type_slug( $type_key );
		$term = '' !== $slug ? get_term_by( 'slug', $slug, $taxonomy ) : false;

		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$options[] = array(
			'value' => (string) absint( $term->term_id ),
			'label' => (string) $type_label,
		);
	}

	return $options;
}

function wp_seed_events_divi_event_results_condition_asset_version() {
	$path = __DIR__ . '/event-results-condition/visual-builder.js';
	$hash = is_readable( $path ) ? hash_file( 'sha256', $path ) : false;

	return $hash ? substr( $hash, 0, 16 ) : WP_SEED_EVENTS_VERSION;
}

/** Register the condition editor only in the Divi Visual Builder app. */
function wp_seed_events_divi_register_event_results_condition_assets() {
	static $registered = false;

	if (
		$registered
		|| ! function_exists( 'et_core_is_fb_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! class_exists( '\\ET\\Builder\\VisualBuilder\\Assets\\PackageBuildManager' )
	) {
		return;
	}

	$registered = true;
	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-divi-event-results-condition',
			'version' => wp_seed_events_divi_event_results_condition_asset_version(),
			'script'  => array(
				'src'                => plugins_url( 'event-results-condition/visual-builder.js', __FILE__ ),
				'deps'               => array( 'divi-field-library', 'divi-vendor-wp-hooks' ),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
				'data_app_window'    => array(
					'eventTypes' => wp_seed_events_divi_event_results_condition_type_options(),
				),
			),
		)
	);
}
add_action(
	'divi_visual_builder_assets_before_enqueue_scripts',
	'wp_seed_events_divi_register_event_results_condition_assets'
);
