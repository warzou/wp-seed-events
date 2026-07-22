<?php
/**
 * Standalone assertions for the Divi event collection query adapter.
 *
 * Run with: php tests/divi-collection-query-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['divi_collection_filters'] = array();
$GLOBALS['divi_collection_calls']   = array();
$GLOBALS['divi_collection_cases']   = 0;

class WP_REST_Request {
	private $route;
	private $params;

	public function __construct( $route, $params ) {
		$this->route  = $route;
		$this->params = $params;
	}

	public function get_route() {
		return $this->route;
	}

	public function get_param( $key ) {
		return $this->params[ $key ] ?? null;
	}

	public function set_param( $key, $value ) {
		$this->params[ $key ] = $value;
	}
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['divi_collection_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function wp_seed_events_query_event_collection( $args ) {
	$GLOBALS['divi_collection_calls'][] = $args;
	$key = (string) $args['type'] . ':' . (string) $args['status'] . ':' . (string) $args['pinned'];
	$map = array(
		'atelier:upcoming:all' => array( 7, 2, 5 ),
		'atelier:past:all'     => array( 9, 3 ),
		'atelier:all:all'      => array( 7, 9, 3, 2, 5, 11 ),
		'atelier:all:only'     => array( 7 ),
		':all:all'             => array( 7, 9, 3, 2, 5, 11, 20 ),
	);

	return array( 'ids' => $map[ $key ] ?? array() );
}

require dirname( __DIR__ ) . '/includes/integrations/divi/collection-query.php';

function divi_collection_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function divi_collection_case( $label, $callback ) {
	$GLOBALS['divi_collection_cases']++;
	$callback();
	echo 'ok ' . $GLOBALS['divi_collection_cases'] . ' - ' . $label . PHP_EOL;
}

function divi_collection_query( $status = 'all', $pinned = 'all' ) {
	return array(
		'post_type'      => array( 'wp_seed_event' ),
		'posts_per_page' => 2,
		'paged'          => 2,
		'orderby'        => 'wp_seed_events_business_date',
		'order'          => 'ASC',
		'meta_query'     => array(
			array( 'key' => 'wp_seed_events_type', 'value' => 'atelier', 'compare' => '=' ),
			array( 'key' => 'wp_seed_events_status', 'value' => $status, 'compare' => '=' ),
			array( 'key' => 'wp_seed_events_pinned', 'value' => $pinned, 'compare' => '=' ),
			'relation' => 'OR',
		),
	);
}

divi_collection_case( 'hooks are registered once', function () {
	foreach ( array( 'rest_request_before_callbacks', 'et_builder_loop_order_by_options_wp_seed_event', 'divi_loop_data_after_execution', 'divi_module_options_loop_post_type_results_query_args' ) as $hook ) {
		divi_collection_assert( 1 === count( $GLOBALS['divi_collection_filters'][ $hook ] ?? array() ), 'Hook registration differs: ' . $hook );
	}
} );

divi_collection_case( 'Divi plural event post type is normalized for order options', function () {
	$request = new WP_REST_Request(
		'/divi/v1/loop/query-order-by',
		array(
			'query_type' => 'post_types',
			'post_type'  => 'post',
			'post_types' => 'wp_seed_event',
		)
	);

	$response = wp_seed_events_divi_normalize_collection_orderby_request( null, array(), $request );

	divi_collection_assert( null === $response, 'REST response changed.' );
	divi_collection_assert( 'wp_seed_event' === $request->get_param( 'post_type' ), 'Event post type was not normalized.' );
} );

divi_collection_case( 'order option normalization is isolated from other Divi loops', function () {
	foreach (
		array(
			new WP_REST_Request( '/divi/v1/loop/query-order-by', array( 'query_type' => 'post_types', 'post_type' => 'post', 'post_types' => 'post' ) ),
			new WP_REST_Request( '/divi/v1/loop/query-order-by', array( 'query_type' => 'post_types', 'post_type' => 'post', 'post_types' => 'post,wp_seed_event' ) ),
			new WP_REST_Request( '/divi/v1/loop/query-results', array( 'query_type' => 'post_types', 'post_type' => 'post', 'post_types' => 'wp_seed_event' ) ),
		) as $request
	) {
		wp_seed_events_divi_normalize_collection_orderby_request( null, array(), $request );
		divi_collection_assert( 'post' === $request->get_param( 'post_type' ), 'An unrelated Divi request changed.' );
	}
} );

divi_collection_case( 'business order option is exposed', function () {
	$options = wp_seed_events_divi_collection_order_options( array() );
	divi_collection_assert( 'wp_seed_events_business_date' === $options[0]['value'], 'Order value differs.' );
	divi_collection_assert( '1re date de l’événement' === $options[0]['label'], 'Order label differs.' );
	$options = wp_seed_events_divi_collection_order_options( $options );
	divi_collection_assert( 1 === count( $options ), 'Order option was duplicated.' );
} );

divi_collection_case( 'frontend query uses canonical IDs', function () {
	$loop = wp_seed_events_divi_filter_collection_loop_data( array( 'query_args' => divi_collection_query( 'upcoming' ) ) );
	$args = $loop['query_args'];
	divi_collection_assert( array( 7, 2, 5 ) === $args['post__in'], 'Frontend IDs differ.' );
	divi_collection_assert( 'post__in' === $args['orderby'], 'Frontend order is not canonical.' );
	divi_collection_assert( 2 === $args['posts_per_page'] && 2 === $args['paged'], 'Frontend pagination changed.' );
	divi_collection_assert( ! isset( $args['meta_query'] ), 'Virtual clauses reached WordPress.' );
} );

divi_collection_case( 'REST preview matches frontend', function () {
	$base     = divi_collection_query( 'past' );
	$frontend = wp_seed_events_divi_filter_collection_loop_data( array( 'query_args' => $base ) )['query_args'];
	$rest     = $base;
	unset( $rest['orderby'] );
	$rest = wp_seed_events_divi_filter_collection_rest_query_args( $rest, array( 'order_by' => 'wp_seed_events_business_date' ) );
	divi_collection_assert( $frontend['post__in'] === $rest['post__in'], 'REST and frontend IDs differ.' );
	divi_collection_assert( $frontend['orderby'] === $rest['orderby'], 'REST and frontend ordering differ.' );
} );

divi_collection_case( 'type status and pinned are combined', function () {
	$args = wp_seed_events_divi_apply_collection_query( divi_collection_query( 'all', 'only' ) );
	divi_collection_assert( array( 7 ) === $args['post__in'], 'Combined selection differs.' );
	$call = end( $GLOBALS['divi_collection_calls'] );
	divi_collection_assert( 'atelier' === $call['type'] && 'all' === $call['status'] && 'only' === $call['pinned'], 'Canonical options differ.' );
} );

divi_collection_case( 'descending order reaches canonical query', function () {
	$query          = divi_collection_query( 'upcoming' );
	$query['order'] = 'DESC';
	wp_seed_events_divi_apply_collection_query( $query );
	$call = end( $GLOBALS['divi_collection_calls'] );
	divi_collection_assert( 'DESC' === $call['order'], 'Descending order was lost.' );
} );

divi_collection_case( 'native post inclusion is intersected', function () {
	$query             = divi_collection_query( 'upcoming' );
	$query['post__in'] = array( 2, 5, 99 );
	$args               = wp_seed_events_divi_apply_collection_query( $query );
	divi_collection_assert( array( 2, 5 ) === $args['post__in'], 'Native inclusion was not preserved.' );
} );

divi_collection_case( 'native exclusion is preserved', function () {
	$query                 = divi_collection_query( 'upcoming' );
	$query['post__not_in'] = array( 2 );
	$args                   = wp_seed_events_divi_apply_collection_query( $query );
	divi_collection_assert( array( 7, 5 ) === $args['post__in'], 'Native exclusion was not preserved.' );
} );

divi_collection_case( 'nonvirtual meta clauses remain active', function () {
	$query                 = divi_collection_query( 'upcoming' );
	$query['meta_query'][] = array( 'key' => 'public_fixture', 'value' => 'yes', 'compare' => '=' );
	$args                   = wp_seed_events_divi_apply_collection_query( $query );
	divi_collection_assert( 1 === count( $args['meta_query'] ), 'Ordinary meta clause was removed.' );
	divi_collection_assert( 'public_fixture' === $args['meta_query'][0]['key'], 'Ordinary meta clause changed.' );
} );

divi_collection_case( 'empty canonical result cannot expand to all posts', function () {
	$query = divi_collection_query( 'invalid' );
	$args  = wp_seed_events_divi_apply_collection_query( $query );
	divi_collection_assert( array( 0 ) === $args['post__in'], 'Empty result is unsafe.' );
} );

divi_collection_case( 'unmarked event loops remain unchanged', function () {
	$query = array( 'post_type' => array( 'wp_seed_event' ), 'orderby' => 'date', 'order' => 'DESC' );
	divi_collection_assert( $query === wp_seed_events_divi_apply_collection_query( $query ), 'Unmarked event query changed.' );
} );

divi_collection_case( 'ordinary WordPress loops remain unchanged', function () {
	$query = array( 'post_type' => array( 'post' ), 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => array( array( 'key' => 'wp_seed_events_status', 'value' => 'upcoming' ) ) );
	divi_collection_assert( $query === wp_seed_events_divi_apply_collection_query( $query ), 'Ordinary WordPress query changed.' );
} );

divi_collection_case( 'two loops remain isolated', function () {
	$future = wp_seed_events_divi_apply_collection_query( divi_collection_query( 'upcoming' ) );
	$past   = wp_seed_events_divi_apply_collection_query( divi_collection_query( 'past' ) );
	divi_collection_assert( array( 7, 2, 5 ) === $future['post__in'], 'First loop changed.' );
	divi_collection_assert( array( 9, 3 ) === $past['post__in'], 'Second loop changed.' );
} );

divi_collection_case( 'adapter has no storage or rendering dependency', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/collection-query.php' );
	foreach ( array( 'get_post_meta(', 'update_post_meta(', 'WP_Query(', 'do_shortcode(', '<article' ) as $forbidden ) {
		divi_collection_assert( false === strpos( $source, $forbidden ), 'Forbidden dependency found: ' . $forbidden );
	}
} );

echo 'Divi collection query harness: ' . $GLOBALS['divi_collection_cases'] . '/' . $GLOBALS['divi_collection_cases'] . ' OK' . PHP_EOL;
