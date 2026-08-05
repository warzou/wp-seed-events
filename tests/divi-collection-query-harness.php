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

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
}

function wp_slash( $value ) {
	return is_array( $value ) ? array_map( 'wp_slash', $value ) : addslashes( (string) $value );
}

function parse_blocks( $content ) {
	$decoded = json_decode( (string) $content, true );
	return is_array( $decoded ) ? $decoded : array();
}

function serialize_blocks( $blocks ) {
	return json_encode( $blocks, JSON_UNESCAPED_SLASHES );
}
function sanitize_title( $value ) {
	return trim( preg_replace( '/[^a-z0-9-]+/', '-', strtolower( (string) $value ) ), '-' );
}

function is_wp_error( $value ) {
	return false;
}

function divi_collection_term( $term_id, $slug, $taxonomy ) {
	return (object) compact( 'term_id', 'slug', 'taxonomy' );
}

function get_term( $term_id, $taxonomy = '' ) {
	$terms = array(
		11 => divi_collection_term( 11, 'atelier', 'wp_seed_event_type' ),
		12 => divi_collection_term( 12, 'stage', 'wp_seed_event_type' ),
		13 => divi_collection_term( 13, 'journee-decouverte', 'wp_seed_event_type' ),
		14 => divi_collection_term( 14, 'reunion-information', 'wp_seed_event_type' ),
		21 => divi_collection_term( 21, 'featured', 'wp_seed_event_flag' ),
		30 => divi_collection_term( 30, 'actualites', 'category' ),
	);
	$term = $terms[ (int) $term_id ] ?? false;

	return $term && ( '' === $taxonomy || $taxonomy === $term->taxonomy ) ? $term : false;
}

function get_term_by( $field, $value, $taxonomy ) {
	if ( 'slug' !== $field || ( 'featured' === $value && empty( $GLOBALS['divi_featured_term_exists'] ) ) ) {
		return false;
	}

	foreach ( array( 11, 12, 13, 14, 21, 30 ) as $term_id ) {
		$term = get_term( $term_id, $taxonomy );

		if ( $term && $value === $term->slug ) {
			return $term;
		}
	}

	return false;
}

$GLOBALS['divi_featured_term_exists'] = true;

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
	foreach ( array( 'rest_request_before_callbacks', 'et_builder_loop_order_by_options_wp_seed_event', 'divi_loop_data_before_execution', 'divi_module_options_loop_post_type_results_query_args', 'wp_insert_post_data', 'divi_loop_no_results_output', 'divi_loop_rendered_output', 'divi_module_wrapper_render' ) as $hook ) {
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

divi_collection_case( 'one dedicated type creates one native clause', function () {
	$query = array( 'post_type' => array( 'wp_seed_event' ), 'orderby' => 'date' );
	$args  = wp_seed_events_divi_apply_collection_query(
		$query,
		'',
		array( 'types_present' => true, 'types' => array( array( 'value' => 'atelier' ) ) )
	);
	$clause = $args['tax_query'][0];
	divi_collection_assert( 'wp_seed_event_type' === $clause['taxonomy'], 'Type taxonomy differs.' );
	divi_collection_assert( array( 11 ) === $clause['terms'], 'Type slug was not normalized.' );
	divi_collection_assert( 'IN' === $clause['operator'], 'Type operator differs.' );
} );

divi_collection_case( 'two dedicated types share one OR-compatible IN clause', function () {
	$args = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ), 'orderby' => 'date' ),
		'',
		array( 'types_present' => true, 'types' => '13,14' )
	);
	divi_collection_assert( array( 13, 14 ) === $args['tax_query'][0]['terms'], 'Multiple type IDs differ.' );
	divi_collection_assert( 'IN' === $args['tax_query'][0]['operator'], 'Multiple types do not use OR-compatible IN.' );
} );

divi_collection_case( 'empty dedicated types mean all types', function () {
	$query = array(
		'post_type' => array( 'wp_seed_event' ),
		'tax_query' => array( array( 'taxonomy' => 'wp_seed_event_type', 'terms' => array( 11 ) ) ),
	);
	$args = wp_seed_events_divi_apply_collection_query( $query, '', array( 'types_present' => true, 'types' => array() ) );
	divi_collection_assert( ! isset( $args['tax_query'] ), 'Cleared type control retained a generic type clause.' );
} );

divi_collection_case( 'empty categorized selection means all types', function () {
	$types = array( array( 'categoryId' => 'wp_seed_event_type', 'selectedOptions' => array() ) );
	$args = wp_seed_events_divi_apply_collection_query(
		array(
			'post_type' => array( 'wp_seed_event' ),
			'tax_query' => array( array( 'taxonomy' => 'wp_seed_event_type', 'terms' => array( 11 ) ) ),
		),
		'',
		array( 'types_present' => true, 'types' => $types )
	);
	divi_collection_assert( ! isset( $args['post__in'] ), 'Empty category wrapper forced an empty query.' );
	divi_collection_assert( ! isset( $args['tax_query'] ), 'Empty category wrapper retained a type clause.' );
} );

divi_collection_case( 'featured only creates a native IN clause', function () {
	$args = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'pinned_present' => true, 'pinned' => 'featured_only' )
	);
	$clause = $args['tax_query'][0];
	divi_collection_assert( 'wp_seed_event_flag' === $clause['taxonomy'], 'Featured taxonomy differs.' );
	divi_collection_assert( array( 21 ) === $clause['terms'] && 'IN' === $clause['operator'], 'Featured-only clause differs.' );
} );

divi_collection_case( 'featured exclusion creates a native NOT IN clause', function () {
	$args = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'pinned_present' => true, 'pinned' => 'exclude_featured' )
	);
	divi_collection_assert( 'NOT IN' === $args['tax_query'][0]['operator'], 'Featured exclusion differs.' );
} );

divi_collection_case( 'types and featured combine with AND', function () {
	$args = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array(
			'types_present'  => true,
			'types'          => array( 11, 12 ),
			'pinned_present' => true,
			'pinned'         => 'featured_only',
		)
	);
	divi_collection_assert( 'AND' === $args['tax_query']['relation'], 'Type and featured relation differs.' );
	divi_collection_assert( 'wp_seed_event_type' === $args['tax_query'][0]['taxonomy'], 'Type clause missing.' );
	divi_collection_assert( 'wp_seed_event_flag' === $args['tax_query'][1]['taxonomy'], 'Featured clause missing.' );
} );

divi_collection_case( 'dedicated controls override contradictory generic clauses', function () {
	$query = array(
		'post_type' => array( 'wp_seed_event' ),
		'tax_query' => array(
			array( 'taxonomy' => 'wp_seed_event_type', 'field' => 'term_id', 'terms' => array( 11 ) ),
			array( 'taxonomy' => 'wp_seed_event_flag', 'field' => 'term_id', 'terms' => array( 21 ) ),
			array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => array( 30 ) ),
			'relation' => 'OR',
		),
	);
	$args = wp_seed_events_divi_apply_collection_query(
		$query,
		'',
		array(
			'types_present'  => true,
			'types'          => array( 13, 14 ),
			'pinned_present' => true,
			'pinned'         => 'exclude_featured',
		)
	);
	$serialized = serialize( $args['tax_query'] );
	divi_collection_assert( 1 === substr_count( $serialized, 'wp_seed_event_type' ), 'Generic type clause survived.' );
	divi_collection_assert( 1 === substr_count( $serialized, 'wp_seed_event_flag' ), 'Generic featured clause survived.' );
	divi_collection_assert( false !== strpos( $serialized, 'category' ), 'Unrelated taxonomy clause was removed.' );
	divi_collection_assert( false !== strpos( $serialized, 'NOT IN' ), 'Dedicated exclusion was not authoritative.' );
} );

divi_collection_case( 'missing featured term is deterministic', function () {
	$GLOBALS['divi_featured_term_exists'] = false;
	$only = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'pinned_present' => true, 'pinned' => 'featured_only' )
	);
	$exclude = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'pinned_present' => true, 'pinned' => 'exclude_featured' )
	);
	$GLOBALS['divi_featured_term_exists'] = true;
	divi_collection_assert( array( 0 ) === $only['post__in'], 'Missing featured term expanded only-filter results.' );
	divi_collection_assert( ! isset( $exclude['tax_query'] ), 'Missing featured term constrained exclusion results.' );
} );

divi_collection_case( 'categorized Divi selections resolve their selected options', function () {
	$types = array(
		array(
			'categoryId'      => 'wp_seed_event_type',
			'categoryName'    => 'Types d’événement',
			'selectedOptions' => array(
				array( 'value' => '13', 'label' => 'Journée découverte' ),
				array( 'value' => '14', 'label' => 'Réunion d’information' ),
			),
		),
	);
	$args = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ), 'orderby' => 'date' ),
		'',
		array( 'types_present' => true, 'types' => $types, 'pinned_present' => true, 'pinned' => 'all' )
	);
	divi_collection_assert( ! isset( $args['post__in'] ), 'Categorized values forced an empty query.' );
	divi_collection_assert( array( 13, 14 ) === $args['tax_query'][0]['terms'], 'Categorized type IDs differ.' );
	divi_collection_assert( 'IN' === $args['tax_query'][0]['operator'], 'Categorized types do not use OR-compatible IN.' );
	divi_collection_assert( 1 === count( $args['tax_query'] ), 'Pinned all added a flag clause.' );
} );

divi_collection_case( 'standard and Hero loop instances produce identical pre-cache queries', function () {
	$values = array(
		'wpSeedEventTypes' => array(
			array(
				'categoryId'      => 'wp_seed_event_type',
				'selectedOptions' => array( array( 'value' => '13' ), array( 'value' => '14' ) ),
			),
		),
		'wpSeedEventPinned' => 'all',
	);
	$base = array( 'query_args' => array( 'post_type' => array( 'wp_seed_event' ), 'orderby' => 'date' ) );
	$standard = wp_seed_events_divi_filter_collection_loop_data(
		$base,
		array( 'id' => 'standard-loop', 'module' => array( 'advanced' => array( 'loop' => array( 'desktop' => array( 'value' => $values ) ) ) ) )
	);
	$hero = wp_seed_events_divi_filter_collection_loop_data(
		$base,
		array( 'id' => 'hero-loop', 'module' => array( 'advanced' => array( 'loop' => array( 'desktop' => array( 'value' => $values ) ) ) ) )
	);
	divi_collection_assert( $standard['query_args'] === $hero['query_args'], 'Container context changed identical loop filters.' );
	divi_collection_assert( array( 13, 14 ) === $hero['query_args']['tax_query'][0]['terms'], 'Hero terms differ.' );
	divi_collection_assert( ! isset( $hero['query_args']['post__in'] ), 'Hero query was forced empty.' );
	divi_collection_assert( empty( $GLOBALS['divi_collection_filters']['divi_loop_data_after_execution'] ), 'Post-cache hook remains registered.' );
} );

divi_collection_case( 'frontend and REST controls produce identical clauses', function () {
	$loop_values = array(
		'wpSeedEventTypes'  => array( array( 'value' => '11' ), array( 'value' => '12' ) ),
		'wpSeedEventPinned' => 'exclude_featured',
	);
	$loop = wp_seed_events_divi_filter_collection_loop_data(
		array( 'query_args' => array( 'post_type' => array( 'wp_seed_event' ) ) ),
		array( 'module' => array( 'advanced' => array( 'loop' => array( 'desktop' => array( 'value' => $loop_values ) ) ) ) )
	);
	$rest = wp_seed_events_divi_filter_collection_rest_query_args(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		array( 'wp_seed_event_types' => '11,12', 'wp_seed_event_pinned' => 'exclude_featured' )
	);
	divi_collection_assert( $loop['query_args']['tax_query'] === $rest['tax_query'], 'Frontend and REST clauses differ.' );
} );

divi_collection_case( 'two dedicated loops remain isolated', function () {
	$first = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'types_present' => true, 'types' => array( 11 ), 'pinned_present' => true, 'pinned' => 'featured_only' )
	);
	$second = wp_seed_events_divi_apply_collection_query(
		array( 'post_type' => array( 'wp_seed_event' ) ),
		'',
		array( 'types_present' => true, 'types' => array( 14 ), 'pinned_present' => true, 'pinned' => 'exclude_featured' )
	);
	divi_collection_assert( array( 11 ) === $first['tax_query'][0]['terms'], 'First loop type changed.' );
	divi_collection_assert( 'IN' === $first['tax_query'][1]['operator'], 'First loop pinned state changed.' );
	divi_collection_assert( array( 14 ) === $second['tax_query'][0]['terms'], 'Second loop type changed.' );
	divi_collection_assert( 'NOT IN' === $second['tax_query'][1]['operator'], 'Second loop pinned state changed.' );
} );


divi_collection_case( 'legacy native selections migrate to dedicated controls', function () {
	$values = array(
		'queryType' => 'post_types',
		'subTypes'  => array( array( 'value' => 'wp_seed_event' ) ),
		'includePostWithSpecificTerms' => array(
			array(
				'categoryId'      => 'wp_seed_event_type',
				'selectedOptions' => array(
					array( 'value' => '13', 'label' => 'Journée découverte' ),
					array( 'value' => '14', 'label' => 'Réunion information' ),
				),
			),
			array(
				'categoryId'      => 'wp_seed_event_flag',
				'selectedOptions' => array( array( 'value' => '21', 'label' => 'Événement épinglé' ) ),
			),
			array(
				'categoryId'      => 'category',
				'selectedOptions' => array( array( 'value' => '30', 'label' => 'Actualités' ) ),
			),
		),
	);
	$normalized = wp_seed_events_divi_normalize_event_loop_values( $values );
	divi_collection_assert( array( '13', '14' ) === wp_seed_events_divi_flatten_term_values( $normalized['wpSeedEventTypes'] ), 'Legacy types differ.' );
	divi_collection_assert( 'featured_only' === $normalized['wpSeedEventPinned'], 'Legacy featured inclusion differs.' );
	divi_collection_assert( 1 === count( $normalized['includePostWithSpecificTerms'] ), 'Unrelated taxonomy was not preserved.' );
	divi_collection_assert( 'category' === $normalized['includePostWithSpecificTerms'][0]['categoryId'], 'Wrong native taxonomy survived.' );
} );

divi_collection_case( 'legacy featured exclusion migrates to dedicated exclusion', function () {
	$normalized = wp_seed_events_divi_normalize_event_loop_values(
		array(
			'excludePostWithSpecificTerms' => array(
				array(
					'categoryId'      => 'wp_seed_event_flag',
					'selectedOptions' => array( array( 'value' => '21' ) ),
				),
			),
		)
	);
	divi_collection_assert( 'exclude_featured' === $normalized['wpSeedEventPinned'], 'Featured exclusion differs.' );
	divi_collection_assert( array() === $normalized['excludePostWithSpecificTerms'], 'Featured remained in native exclusion.' );
} );

divi_collection_case( 'dedicated controls override old native Events terms', function () {
	$normalized = wp_seed_events_divi_normalize_event_loop_values(
		array(
			'wpSeedEventTypes' => array( array( 'value' => '12' ) ),
			'wpSeedEventPinned' => 'all',
			'includePostWithSpecificTerms' => array(
				array( 'categoryId' => 'wp_seed_event_type', 'selectedOptions' => array( array( 'value' => '13' ) ) ),
				array( 'categoryId' => 'wp_seed_event_flag', 'selectedOptions' => array( array( 'value' => '21' ) ) ),
			),
		)
	);
	divi_collection_assert( array( '12' ) === wp_seed_events_divi_flatten_term_values( $normalized['wpSeedEventTypes'] ), 'Dedicated types lost priority.' );
	divi_collection_assert( 'all' === $normalized['wpSeedEventPinned'], 'Dedicated pinned state lost priority.' );
	divi_collection_assert( array() === $normalized['includePostWithSpecificTerms'], 'Controlled native terms survived.' );
} );

divi_collection_case( 'flat native values are classified without category wrappers', function () {
	$normalized = wp_seed_events_divi_normalize_event_loop_values(
		array( 'includePostWithSpecificTerms' => array( 13, 21, 30 ) )
	);
	divi_collection_assert( array( '13' ) === wp_seed_events_divi_flatten_term_values( $normalized['wpSeedEventTypes'] ), 'Flat type was not migrated.' );
	divi_collection_assert( 'featured_only' === $normalized['wpSeedEventPinned'], 'Flat featured was not migrated.' );
	divi_collection_assert( array( 30 ) === $normalized['includePostWithSpecificTerms'], 'Flat unrelated term was not preserved.' );
} );

divi_collection_case( 'compact Divi column defaults to post types query', function () {
	$compact = array(
		'enable'   => 'on',
		'loopId'   => 'hero-events',
		'subTypes' => array( array( 'value' => 'wp_seed_event' ) ),
	);
	divi_collection_assert( wp_seed_events_divi_is_event_loop_values( $compact ), 'Compact event loop was not recognized.' );

	$mixed = $compact;
	$mixed['subTypes'][] = array( 'value' => 'post' );
	divi_collection_assert( ! wp_seed_events_divi_is_event_loop_values( $mixed ), 'Mixed compact loop was treated as event-only.' );

	$explicit_other = $compact;
	$explicit_other['queryType'] = 'terms';
	divi_collection_assert( ! wp_seed_events_divi_is_event_loop_values( $explicit_other ), 'Explicit non-post query was ignored.' );
} );
divi_collection_case( 'saved event block migration is idempotent', function () {
	$block = array(
		'attrs' => array(
			'module' => array(
				'advanced' => array(
					'loop' => array(
						'desktop' => array(
							'value' => array(
								'queryType' => 'post_types',
								'subTypes' => array( array( 'value' => 'wp_seed_event' ) ),
								'includePostWithSpecificTerms' => array(
									array( 'categoryId' => 'wp_seed_event_type', 'selectedOptions' => array( array( 'value' => '13' ), array( 'value' => '14' ) ) ),
								),
							),
						),
					),
				),
			),
		),
		'innerBlocks' => array(),
	);
	$changed = false;
	$first   = wp_seed_events_divi_migrate_event_loop_block( $block, $changed );
	divi_collection_assert( $changed, 'Legacy block was not migrated.' );
	divi_collection_assert( array( '13', '14' ) === wp_seed_events_divi_flatten_term_values( $first['attrs']['module']['advanced']['loop']['desktop']['value']['wpSeedEventTypes'] ), 'Saved types differ.' );
	divi_collection_assert( array() === $first['attrs']['module']['advanced']['loop']['desktop']['value']['includePostWithSpecificTerms'], 'Native field was not emptied.' );
	$changed = false;
	$second  = wp_seed_events_divi_migrate_event_loop_block( $first, $changed );
	divi_collection_assert( ! $changed && $first === $second, 'Migration is not idempotent.' );
} );

divi_collection_case( 'slashed post payload migrates before storage', function () {
	$block = array(
		'attrs' => array(
			'module' => array(
				'advanced' => array(
					'loop' => array(
						'desktop' => array(
							'value' => array(
								'queryType' => 'post_types',
								'subTypes' => array( array( 'value' => 'wp_seed_event' ) ),
								'includePostWithSpecificTerms' => array(
									array( 'categoryId' => 'wp_seed_event_type', 'selectedOptions' => array( array( 'value' => '13' ) ) ),
								),
							),
						),
					),
				),
			),
		),
		'innerBlocks' => array(),
	);
	$payload = array( 'post_type' => 'page', 'post_content' => wp_slash( serialize_blocks( array( $block ) ) ) );
	$result  = wp_seed_events_divi_migrate_event_loop_post_data( $payload );
	$stored  = parse_blocks( wp_unslash( $result['post_content'] ) );
	$values  = $stored[0]['attrs']['module']['advanced']['loop']['desktop']['value'];
	divi_collection_assert( array( '13' ) === wp_seed_events_divi_flatten_term_values( $values['wpSeedEventTypes'] ), 'Slashed types were not migrated.' );
	divi_collection_assert( 'all' === $values['wpSeedEventPinned'], 'Slashed pinned default differs.' );
	divi_collection_assert( array() === $values['includePostWithSpecificTerms'], 'Slashed native field was not emptied.' );
} );
divi_collection_case( 'revision payload remains byte-equivalent', function () {
	$payload = array( 'post_type' => 'revision', 'post_content' => 'wp_seed_event legacy content' );
	divi_collection_assert( $payload === wp_seed_events_divi_migrate_event_loop_post_data( $payload ), 'Revision payload changed.' );
} );

divi_collection_case( 'ordinary saved loop remains byte-equivalent', function () {
	$block = array(
		'attrs' => array(
			'module' => array(
				'advanced' => array(
					'loop' => array(
						'desktop' => array(
							'value' => array(
								'queryType' => 'post_types',
								'subTypes'  => array( array( 'value' => 'post' ) ),
							),
						),
					),
				),
			),
		),
		'innerBlocks' => array(),
	);
	$changed = false;
	$result  = wp_seed_events_divi_migrate_event_loop_block( $block, $changed );
	divi_collection_assert( ! $changed && $block === $result, 'Ordinary loop was changed.' );
} );

function divi_collection_empty_attrs( $behavior = null, $message = null, $no_results = true, $post_type = 'wp_seed_event' ) {
	$values = array(
		'queryType' => 'post_types',
		'subTypes'  => array( array( 'value' => $post_type ) ),
	);

	if ( null !== $behavior ) {
		$values['wpSeedEventEmptyBehavior'] = $behavior;
	}

	if ( null !== $message ) {
		$values['wpSeedEventEmptyMessage'] = $message;
	}

	$attrs = array(
		'module' => array(
			'advanced' => array(
				'loop' => array(
					'desktop' => array( 'value' => $values ),
				),
			),
		),
	);

	if ( $no_results ) {
		$attrs['__loop_no_results'] = true;
	}

	return $attrs;
}

divi_collection_case( 'missing empty behavior preserves Divi output', function () {
	$attrs  = divi_collection_empty_attrs();
	$native = '<div class="entry"><h2>No Results Found.</h2></div>';

	divi_collection_assert( 'divi_default' === wp_seed_events_divi_normalize_empty_behavior( '' ), 'Missing behavior is not Divi default.' );
	divi_collection_assert( $native === wp_seed_events_divi_filter_no_results_output( $native, $attrs ), 'Legacy loop output changed.' );
	divi_collection_assert( $native === wp_seed_events_divi_filter_loop_rendered_output( $native, $attrs ), 'Legacy loop owner changed.' );
} );

divi_collection_case( 'empty Events row is removed in hide mode', function () {
	$attrs = divi_collection_empty_attrs( 'hide', '' );

	divi_collection_assert( '' === wp_seed_events_divi_filter_no_results_output( 'No Results Found.', $attrs ), 'Native empty content flashed.' );
	divi_collection_assert( '' === wp_seed_events_divi_filter_loop_rendered_output( '<div class="et_pb_row">row</div>', $attrs ), 'Empty row remained.' );
	divi_collection_assert(
		'' === wp_seed_events_divi_filter_module_wrapper( '<div class="et_pb_row">row</div>', array( 'attrs' => $attrs, 'name' => 'divi/row' ) ),
		'Structural row remained.'
	);
} );

divi_collection_case( 'empty Events column is removed without touching its parent', function () {
	$attrs        = divi_collection_empty_attrs( 'hide', '' );
	$parent_attrs = array( '__loop_no_results' => true );

	divi_collection_assert(
		'' === wp_seed_events_divi_filter_module_wrapper( '<div class="et_pb_column">column</div>', array( 'attrs' => $attrs, 'name' => 'divi/column' ) ),
		'Empty column remained.'
	);
	divi_collection_assert(
		'<section>parent</section>' === wp_seed_events_divi_filter_module_wrapper(
			'<section>parent</section>',
			array( 'attrs' => $parent_attrs, 'parentAttrs' => $attrs, 'name' => 'divi/section' )
		),
		'Non-owning parent was removed.'
	);
} );

divi_collection_case( 'custom empty message is escaped and replaces Divi markup', function () {
	$attrs   = divi_collection_empty_attrs( 'custom_message', '<script>alert(1)</script>' );
	$message = wp_seed_events_divi_filter_no_results_output( 'No Results Found.', $attrs );
	$wrapper = wp_seed_events_divi_filter_module_wrapper(
		'<div class="et_pb_row"><div class="entry"><h2>No Results Found.</h2><p>Native.</p></div></div>',
		array( 'attrs' => $attrs, 'name' => 'divi/row' )
	);

	divi_collection_assert( false === strpos( $message, '<script>' ), 'Message was not escaped.' );
	divi_collection_assert( false !== strpos( $message, '&lt;script&gt;' ), 'Escaped message is missing.' );
	divi_collection_assert( false === strpos( $wrapper, 'No Results Found.' ), 'Divi generic heading remained.' );
	divi_collection_assert( false !== strpos( $wrapper, 'wp-seed-events-loop-empty-message' ), 'Custom message wrapper is missing.' );
} );

divi_collection_case( 'explicit empty custom message emits no generic fallback', function () {
	$attrs = divi_collection_empty_attrs( 'custom_message', '' );

	divi_collection_assert( '' === wp_seed_events_divi_filter_no_results_output( 'No Results Found.', $attrs ), 'Empty message fell back to Divi.' );
	divi_collection_assert(
		'<div class="et_pb_row"></div>' === wp_seed_events_divi_filter_module_wrapper(
			'<div class="et_pb_row"><div class="entry"><h2>No Results Found.</h2></div></div>',
			array( 'attrs' => $attrs, 'name' => 'divi/row' )
		),
		'Empty structural message retained generic markup.'
	);
} );

divi_collection_case( 'results and non-Events loops are unchanged', function () {
	$with_results = divi_collection_empty_attrs( 'hide', '', false );
	$ordinary     = divi_collection_empty_attrs( 'hide', '', true, 'post' );
	$output       = '<div>result</div>';

	divi_collection_assert( $output === wp_seed_events_divi_filter_loop_rendered_output( $output, $with_results ), 'Non-empty Events loop was hidden.' );
	divi_collection_assert( $output === wp_seed_events_divi_filter_loop_rendered_output( $output, $ordinary ), 'Ordinary empty loop was hidden.' );
	divi_collection_assert(
		$output === wp_seed_events_divi_filter_module_wrapper( $output, array( 'attrs' => $ordinary ) ),
		'Ordinary structural loop changed.'
	);
} );

divi_collection_case( 'two empty Events loops keep independent behavior', function () {
	$hidden  = divi_collection_empty_attrs( 'hide', '' );
	$message = divi_collection_empty_attrs( 'custom_message', 'Autre programmation.' );

	divi_collection_assert( '' === wp_seed_events_divi_filter_loop_rendered_output( '<div>one</div>', $hidden ), 'Hidden loop was rendered.' );
	divi_collection_assert(
		false !== strpos( wp_seed_events_divi_filter_no_results_output( 'native', $message ), 'Autre programmation.' ),
		'Message loop did not retain its own behavior.'
	);
} );

divi_collection_case( 'adapter has no storage or rendering dependency', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/collection-query.php' );
	foreach ( array( 'get_post_meta(', 'update_post_meta(', 'WP_Query(', 'do_shortcode(', '<article' ) as $forbidden ) {
		divi_collection_assert( false === strpos( $source, $forbidden ), 'Forbidden dependency found: ' . $forbidden );
	}
} );

echo 'Divi collection query harness: ' . $GLOBALS['divi_collection_cases'] . '/' . $GLOBALS['divi_collection_cases'] . ' OK' . PHP_EOL;
