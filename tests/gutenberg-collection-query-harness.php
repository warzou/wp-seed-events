<?php
/**
 * Standalone assertions for the Gutenberg event collection adapter.
 *
 * Run with: php tests/gutenberg-collection-query-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['gutenberg_collection_filters'] = array();
$GLOBALS['gutenberg_collection_actions'] = array();
$GLOBALS['gutenberg_collection_calls']   = array();
$GLOBALS['gutenberg_collection_cases']   = 0;

class WP_REST_Request {
	private $params;

	public function __construct( $params = array() ) {
		$this->params = $params;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['gutenberg_collection_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['gutenberg_collection_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function sanitize_title( $value ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' );
}

function wp_seed_events_apply_collection_to_query_args( $query_args, $settings ) {
	$GLOBALS['gutenberg_collection_calls'][] = $settings;
	$key = implode( ':', array( $settings['type'], $settings['status'], $settings['pinned'], $settings['order'] ) );
	$map = array(
		'atelier:upcoming:all:ASC' => array( 7, 2, 5 ),
		'atelier:upcoming:all:DESC' => array( 7, 5, 2 ),
		'atelier:past:all:ASC' => array( 9, 3 ),
		'atelier:all:all:ASC' => array( 7, 9, 3, 2, 5, 11 ),
		'atelier:all:only:ASC' => array( 7 ),
		'conference:all:all:ASC' => array( 20 ),
	);
	$query_args['post__in']             = $map[ $key ] ?? array( 0 );
	$query_args['orderby']              = 'post__in';
	$query_args['order']                = 'ASC';
	$query_args['ignore_sticky_posts'] = true;

	return $query_args;
}

require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-collection-query.php';

function gq_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function gq_case( $label, $callback ) {
	$GLOBALS['gutenberg_collection_cases']++;
	$callback();
	echo 'ok ' . $GLOBALS['gutenberg_collection_cases'] . ' - ' . $label . PHP_EOL;
}

function gq_source( $status = 'upcoming', $pinned = 'all', $order = 'ASC', $type = 'atelier' ) {
	return array(
		'postType'              => 'wp_seed_event',
		'wpSeedEventsCollection' => true,
		'wpSeedEventsType'       => $type,
		'wpSeedEventsStatus'     => $status,
		'wpSeedEventsPinned'     => $pinned,
		'wpSeedEventsOrder'      => $order,
		'wpSeedEventsOrderBy'    => 'business_date',
	);
}

function gq_block( $source, $namespace = 'wp-seed-events/event-collection' ) {
	return (object) array(
		'parsed_block' => array(
			'attrs' => array(
				'namespace' => $namespace,
				'query'     => $source,
			),
		),
		'context'      => array( 'query' => $source ),
	);
}

function gq_rest_request( $source ) {
	return new WP_REST_Request( $source );
}

gq_case( 'hooks are registered once', function () {
	foreach ( array( 'query_loop_block_query_vars', 'rest_wp_seed_event_collection_params', 'rest_wp_seed_event_query' ) as $hook ) {
		gq_assert( 1 === count( $GLOBALS['gutenberg_collection_filters'][ $hook ] ?? array() ), 'Hook differs: ' . $hook );
	}
	gq_assert( 1 === count( $GLOBALS['gutenberg_collection_actions']['enqueue_block_editor_assets'] ?? array() ), 'Editor enqueue differs.' );
} );

gq_case( 'REST parameters are complete and bounded', function () {
	$params = wp_seed_events_gutenberg_collection_rest_params( array( 'page' => array( 'type' => 'integer' ) ) );
	foreach ( array( 'wpSeedEventsCollection', 'wpSeedEventsType', 'wpSeedEventsStatus', 'wpSeedEventsPinned', 'wpSeedEventsOrder', 'wpSeedEventsOrderBy' ) as $key ) {
		gq_assert( isset( $params[ $key ] ), 'REST parameter missing: ' . $key );
	}
	gq_assert( array( 'upcoming', 'past', 'all' ) === $params['wpSeedEventsStatus']['enum'], 'Status schema differs.' );
	gq_assert( array( 'business_date' ) === $params['wpSeedEventsOrderBy']['enum'], 'Order schema differs.' );
} );

gq_case( 'ordinary Query Loop is unchanged', function () {
	$query = array( 'post_type' => 'post', 'orderby' => 'date', 'paged' => 2 );
	gq_assert( $query === wp_seed_events_gutenberg_filter_collection_query_vars( $query, gq_block( array( 'postType' => 'post' ), 'core/query' ), 2 ), 'Ordinary loop changed.' );
} );

gq_case( 'event Query Loop without namespace is unchanged', function () {
	$query = array( 'post_type' => 'wp_seed_event', 'orderby' => 'date' );
	gq_assert( $query === wp_seed_events_gutenberg_filter_collection_query_vars( $query, gq_block( gq_source(), 'another/query' ), 1 ), 'Unmarked event loop changed.' );
} );

gq_case( 'frontend upcoming query uses canonical IDs', function () {
	$query = array( 'post_type' => 'wp_seed_event', 'posts_per_page' => 2, 'paged' => 2, 'offset' => 2 );
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( $query, gq_block( gq_source() ), 2 );
	gq_assert( array( 7, 2, 5 ) === $result['post__in'], 'Upcoming IDs differ.' );
	gq_assert( 2 === $result['posts_per_page'] && 2 === $result['paged'], 'Pagination changed.' );
	gq_assert( 2 === $result['offset'], 'Core pagination offset changed.' );
} );

gq_case( 'past status reaches the canonical contract', function () {
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'past' ) ), 1 );
	gq_assert( array( 9, 3 ) === $result['post__in'], 'Past IDs differ.' );
} );

gq_case( 'all status reaches the canonical contract', function () {
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'all' ) ), 1 );
	gq_assert( array( 7, 9, 3, 2, 5, 11 ) === $result['post__in'], 'All IDs differ.' );
} );

gq_case( 'pinned-only reaches the canonical contract', function () {
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'all', 'only' ) ), 1 );
	gq_assert( array( 7 ) === $result['post__in'], 'Pinned IDs differ.' );
} );

gq_case( 'descending order reaches the canonical contract', function () {
	wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'upcoming', 'all', 'DESC' ) ), 1 );
	$call = end( $GLOBALS['gutenberg_collection_calls'] );
	gq_assert( 'DESC' === $call['order'], 'Descending order changed.' );
} );

gq_case( 'type reaches the canonical contract', function () {
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'all', 'all', 'ASC', 'conference' ) ), 1 );
	gq_assert( array( 20 ) === $result['post__in'], 'Type selection differs.' );
} );

gq_case( 'REST request without marker is unchanged', function () {
	$query = array( 'orderby' => 'date', 'posts_per_page' => 6 );
	gq_assert( $query === wp_seed_events_gutenberg_filter_collection_rest_query( $query, gq_rest_request( array() ) ), 'Unmarked REST query changed.' );
} );

gq_case( 'REST preview matches frontend IDs', function () {
	$source   = gq_source( 'all' );
	$frontend = wp_seed_events_gutenberg_filter_collection_query_vars( array( 'posts_per_page' => 2, 'paged' => 2 ), gq_block( $source ), 2 );
	$rest     = wp_seed_events_gutenberg_filter_collection_rest_query( array( 'posts_per_page' => 2, 'paged' => 2 ), gq_rest_request( $source ) );
	gq_assert( $frontend['post__in'] === $rest['post__in'], 'Editor/frontend IDs differ.' );
	gq_assert( $frontend['orderby'] === $rest['orderby'], 'Editor/frontend order differs.' );
} );

gq_case( 'invalid marked status fails closed', function () {
	$source = gq_source();
	$source['wpSeedEventsStatus'] = 'private';
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( $source ), 1 );
	gq_assert( array( 0 ) === $result['post__in'], 'Invalid status did not fail closed.' );
} );

gq_case( 'invalid business order fails closed', function () {
	$source = gq_source();
	$source['wpSeedEventsOrderBy'] = 'post_date';
	$result = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( $source ), 1 );
	gq_assert( array( 0 ) === $result['post__in'], 'Invalid order did not fail closed.' );
} );

gq_case( 'two event loops remain isolated', function () {
	$first  = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'upcoming' ) ), 1 );
	$second = wp_seed_events_gutenberg_filter_collection_query_vars( array(), gq_block( gq_source( 'past' ) ), 1 );
	gq_assert( array( 7, 2, 5 ) === $first['post__in'], 'First loop changed.' );
	gq_assert( array( 9, 3 ) === $second['post__in'], 'Second loop changed.' );
} );

gq_case( 'adapter contains no business storage or rendering access', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-collection-query.php' );
	foreach ( array( 'get_post_meta(', "'_wp_seed_event_", '"_wp_seed_event_', 'WP_Query(', 'do_shortcode(', '<article' ) as $forbidden ) {
		gq_assert( false === strpos( $source, $forbidden ), 'Forbidden dependency found: ' . $forbidden );
	}
} );

echo 'Gutenberg collection query harness: ' . $GLOBALS['gutenberg_collection_cases'] . '/' . $GLOBALS['gutenberg_collection_cases'] . ' OK' . PHP_EOL;
