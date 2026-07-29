<?php
/**
 * Standalone assertions for the Divi occurrence collection module.
 *
 * Run with: php tests/divi-occurrence-collection-harness.php
 */

declare(strict_types=1);

namespace ET\Builder\Framework\DependencyManagement\Interfaces { interface DependencyInterface {} }
namespace ET\Builder\Framework\Utility { class HTMLUtility { public static function render( $args ) { return (string) ( $args['children'] ?? '' ); } } }
namespace ET\Builder\FrontEnd\Module { class Style { public static function add( $args ) {} } }
namespace ET\Builder\Packages\Module { class Module { public static function render( $args ) { return (string) ( $args['children'] ?? '' ); } } }
namespace ET\Builder\Packages\Module\Options\Element { class ElementClassnames { public static function classnames( $args ) { return array(); } } }
namespace ET\Builder\Packages\ModuleLibrary {
	class ModuleRegistration {
		public static $registrations = array();
		public static function register_module( $path, $args ) { self::$registrations[] = array( $path, $args ); }
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );
	$GLOBALS['doc_cases'] = 0;
	$GLOBALS['flat_calls'] = array();
	$GLOBALS['group_calls'] = array();
	$GLOBALS['api_mode'] = 'index';
	$GLOBALS['throw_item_filter'] = false;
	$GLOBALS['rest_route'] = array();

	class WP_Error {}
	class WP_REST_Server { const READABLE = 'GET'; }
	class WP_REST_Request {
		private $params;
		public function __construct( $params = array() ) { $this->params = $params; }
		public function get_param( $key ) { return $this->params[ $key ] ?? null; }
	}

	function add_action( $hook, $callback, $priority = 10 ) {}
	function register_rest_route( $namespace, $route, $args ) { $GLOBALS['rest_route'] = array( $namespace, $route, $args ); }
	function rest_ensure_response( $value ) { return $value; }
	function current_user_can( $capability, ...$args ) { return true; }
	function is_wp_error( $value ) { return $value instanceof WP_Error; }
	function absint( $value ) { return abs( (int) $value ); }
	function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
	function sanitize_title( $value ) { return sanitize_key( str_replace( ' ', '-', (string) $value ) ); }
	function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
	function sanitize_html_class( $value ) { return sanitize_key( $value ); }
	function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
	function wp_unslash( $value ) { return $value; }
	function __( $value, $domain = null ) { return $value; }
	function esc_html__( $value, $domain = null ) { return esc_html( $value ); }
	function esc_attr__( $value, $domain = null ) { return esc_attr( $value ); }
	function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
	function esc_attr( $value ) { return esc_html( $value ); }
	function esc_url( $value ) { return esc_attr( $value ); }
	function add_query_arg( $key, $value ) { return 'https://example.test/page?' . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value ); }
	function apply_filters( $hook, $value, ...$args ) {
		if ( 'wp_seed_events_divi_occurrence_collection_item_html' === $hook && $GLOBALS['throw_item_filter'] ) {
			throw new RuntimeException( 'render failure' );
		}
		return $value;
	}
	function wp_seed_events_public_format_occurrence_date( $date, $format = 'long' ) { return 'short' === $format ? substr( $date, 8, 2 ) . '/' . substr( $date, 5, 2 ) . '/' . substr( $date, 0, 4 ) : $date; }
	function wp_seed_events_format_occurrence_time( $time ) { return str_replace( ':', ' h ', (string) $time ); }

	require dirname( __DIR__ ) . '/includes/public/occurrence-context.php';

	function doc_item( $uid, $promotion_id, $promotion_name, $start, $cancelled = false, $event_id = 100 ) {
		return array(
			'event_id' => $event_id, 'event_title' => 'Thème commun', 'event_slug' => 'theme-commun',
			'event_type' => 'seminaire', 'event_status' => 'publish', 'is_pinned' => true,
			'occurrence_uid' => $uid, 'start' => $start, 'end' => str_replace( '09:00', '17:00', $start),
			'is_cancelled' => $cancelled, 'promotion_id' => $promotion_id,
			'promotion' => 0 < $promotion_id ? array( 'id' => $promotion_id, 'name' => $promotion_name, 'slug' => sanitize_title( $promotion_name ), 'start_year' => $promotion_id, 'status' => 'active' ) : array(),
			'parcours_year' => 1, 'parcours_year_label' => 'Année 1',
		);
	}

	$GLOBALS['items'] = array(
		doc_item( 'occ-2026-a', 2026, 'Promotion 2026', '2026-02-01 09:00' ),
		doc_item( 'occ-2026-b', 2026, 'Promotion 2026', '2026-03-01 09:00' ),
		doc_item( 'occ-2027-a', 2027, 'Promotion 2027', '2027-02-01 09:00' ),
		doc_item( 'occ-cancelled', 2027, 'Promotion 2027', '2027-03-01 09:00', true ),
	);

	function wp_seed_events_query_occurrence_collection( $args ) {
		$GLOBALS['flat_calls'][] = array( 'args' => $args, 'origin' => $GLOBALS['api_mode'] );
		$items = $GLOBALS['items'];
		if ( empty( $args['include_cancelled'] ) ) {
			$items = array_values( array_filter( $items, static fn( $item ) => empty( $item['is_cancelled'] ) ) );
		}
		if ( isset( $args['event_id'] ) && null !== $args['event_id'] ) {
			$items = array_values( array_filter( $items, static fn( $item ) => $item['event_id'] === $args['event_id'] ) );
		}
		if ( 'empty' === $GLOBALS['api_mode'] ) { $items = array(); }
		return array(
			'items' => $items, 'page' => $args['page'], 'per_page' => $args['per_page'],
			'total_items' => count( $items ), 'total_pages' => 2,
			'has_previous' => 1 < $args['page'], 'has_next' => array() !== $items,
		);
	}

	function wp_seed_events_query_grouped_occurrence_collection( $args ) {
		$GLOBALS['group_calls'][] = array( 'args' => $args, 'origin' => $GLOBALS['api_mode'] );
		if ( 'empty' === $GLOBALS['api_mode'] ) { return array( 'promotions' => array() ); }
		$items = $GLOBALS['items'];
		return array( 'promotions' => array(
			array( 'promotion' => $items[0]['promotion'], 'years' => array( array( 'parcours_year_label' => 'Année 1', 'themes' => array( array( 'event' => array( 'title' => 'Thème commun' ), 'occurrences' => array( $items[0], $items[1] ) ) ) ) ) ),
			array( 'promotion' => $items[2]['promotion'], 'years' => array( array( 'parcours_year_label' => 'Année 1', 'themes' => array( array( 'event' => array( 'title' => 'Thème commun' ), 'occurrences' => array( $items[2] ) ) ) ) ) ),
		) );
	}

	require dirname( __DIR__ ) . '/includes/integrations/divi/class-occurrence-collection-module.php';

	function doc_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
	function doc_case( $name, $callback ) {
		try { $callback(); ++$GLOBALS['doc_cases']; echo '[OK] ' . $name . PHP_EOL; }
		catch ( Throwable $error ) { fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
	}
	function doc_contains( $needle, $haystack ) { return false !== strpos( (string) $haystack, (string) $needle ); }
	function doc_render( $values = array(), $instance = 'module-a' ) { return WP_Seed_Events_Divi_Occurrence_Collection_Module::render_collection( $values, $instance, false ); }

	$source = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/class-occurrence-collection-module.php' );
	$metadata = json_decode( file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/occurrence-collection-module/visual-builder/src/module.json' ), true );

	doc_case( '01 registration', function () { WP_Seed_Events_Divi_Occurrence_Collection_Module::register_module(); doc_assert( 1 === count( \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::$registrations ), 'Registration missing.' ); } );
	doc_case( '02 identifier', fn() => doc_assert( 'wp-seed-events/divi-occurrence-collection' === WP_Seed_Events_Divi_Occurrence_Collection_Module::MODULE_NAME, 'Identifier differs.' ) );
	doc_case( '03 label', fn() => doc_assert( 'WP Seed Events — Collection d’occurrences' === $metadata['title'], 'Label differs.' ) );
	doc_case( '04 REST route', function () { WP_Seed_Events_Divi_Occurrence_Collection_Module::register_rest_routes(); doc_assert( '/divi-occurrence-collection-preview' === $GLOBALS['rest_route'][1], 'Route differs.' ); } );
	doc_case( '05 permissions', fn() => doc_assert( WP_Seed_Events_Divi_Occurrence_Collection_Module::rest_preview_permissions(), 'Permission denied.' ) );
	doc_case( '06 zero filters normalize to absence', function () { $options = WP_Seed_Events_Divi_Occurrence_Collection_Module::normalize_options( array( 'parcours_year' => 0, 'event_id' => 0 ) ); doc_assert( null === $options['parcours_year'] && null === $options['event_id'], 'Zero filters survived.' ); } );
	doc_case( '07 flat defaults', function () { $options = WP_Seed_Events_Divi_Occurrence_Collection_Module::normalize_options( array() ); doc_assert( 'flat' === $options['mode'] && 20 === $options['per_page'], 'Flat defaults differ.' ); } );
	doc_case( '08 grouped limit default', function () { $options = WP_Seed_Events_Divi_Occurrence_Collection_Module::normalize_options( array( 'mode' => 'grouped' ) ); doc_assert( 200 === $options['grouped_limit'], 'Default limit differs.' ); } );
	doc_case( '09 grouped limit capped', function () { $options = WP_Seed_Events_Divi_Occurrence_Collection_Module::normalize_options( array( 'mode' => 'grouped', 'grouped_limit' => 900 ) ); doc_assert( 500 === $options['grouped_limit'], 'Limit not capped.' ); } );
	doc_case( '10 flat API', function () { $GLOBALS['flat_calls'] = array(); doc_render(); doc_assert( 1 === count( $GLOBALS['flat_calls'] ), 'Flat API call count differs.' ); } );
	doc_case( '11 grouped API', function () { $GLOBALS['group_calls'] = array(); doc_render( array( 'mode' => 'grouped' ) ); doc_assert( 1 === count( $GLOBALS['group_calls'] ), 'Grouped API call count differs.' ); } );
	doc_case( '12 canonical grouped order', function () { $GLOBALS['group_calls'] = array(); doc_render( array( 'mode' => 'grouped' ) ); doc_assert( 'canonical_path' === $GLOBALS['group_calls'][0]['args']['order'], 'Grouped order changed.' ); } );
	doc_case( '13 Promotion 2026', fn() => doc_assert( doc_contains( 'Promotion 2026', doc_render( array( 'mode' => 'grouped' ) ) ), 'Promotion 2026 missing.' ) );
	doc_case( '14 Promotion 2027', fn() => doc_assert( doc_contains( 'Promotion 2027', doc_render( array( 'mode' => 'grouped' ) ) ), 'Promotion 2027 missing.' ) );
	doc_case( '15 parcours year', fn() => doc_assert( 2 === substr_count( doc_render( array( 'mode' => 'grouped' ) ), '>Année 1</h3>' ), 'Year groups differ.' ) );
	doc_case( '16 same event repeated flat', fn() => doc_assert( 3 === substr_count( doc_render(), '>Thème commun</h3>' ), 'Same event did not repeat.' ) );
	doc_case( '17 same event repeated grouped', fn() => doc_assert( 3 === substr_count( doc_render( array( 'mode' => 'grouped' ) ), 'data-occurrence-key=' ), 'Grouped occurrences differ.' ) );
	doc_case( '18 cancellation excluded by default', fn() => doc_assert( ! doc_contains( 'occ-cancelled', doc_render() ), 'Cancelled item leaked.' ) );
	doc_case( '19 cancellation included', fn() => doc_assert( doc_contains( 'occ-cancelled', doc_render( array( 'include_cancelled' => 'on' ) ) ), 'Cancelled item missing.' ) );
	doc_case( '20 cancelled text', fn() => doc_assert( doc_contains( 'Annulée', doc_render( array( 'include_cancelled' => 'on' ) ) ), 'Cancelled label missing.' ) );
	doc_case( '21 empty state', function () { $GLOBALS['api_mode'] = 'empty'; $html = doc_render( array( 'empty_message' => 'Rien ici' ) ); $GLOBALS['api_mode'] = 'index'; doc_assert( doc_contains( 'role="status"', $html ) && doc_contains( 'Rien ici', $html ), 'Empty state differs.' ); } );
	doc_case( '22 pagination', fn() => doc_assert( doc_contains( 'aria-label="Pagination des occurrences"', doc_render() ), 'Pagination missing.' ) );
	doc_case( '23 isolated pagination keys', function () { doc_assert( WP_Seed_Events_Divi_Occurrence_Collection_Module::pagination_query_key( 'one' ) !== WP_Seed_Events_Divi_Occurrence_Collection_Module::pagination_query_key( 'two' ), 'Keys collide.' ); } );
	doc_case( '24 request page', function () { $key = WP_Seed_Events_Divi_Occurrence_Collection_Module::pagination_query_key( 'module-a' ); $_GET[ $key ] = 2; $options = WP_Seed_Events_Divi_Occurrence_Collection_Module::normalize_options( array() ); $args = WP_Seed_Events_Divi_Occurrence_Collection_Module::query_args( $options, 'module-a', true ); unset( $_GET[ $key ] ); doc_assert( 2 === $args['page'], 'Request page ignored.' ); } );
	doc_case( '25 persistent instance', fn() => doc_assert( 'custom-instance' === WP_Seed_Events_Divi_Occurrence_Collection_Module::instance_id( 'custom-instance', 'id', 1, 2 ), 'Persistent instance changed.' ) );
	doc_case( '26 duplicated modules isolate', function () { $one = WP_Seed_Events_Divi_Occurrence_Collection_Module::instance_id( '', 'id-a', 1, 2 ); $two = WP_Seed_Events_Divi_Occurrence_Collection_Module::instance_id( '', 'id-b', 1, 3 ); doc_assert( $one !== $two, 'Duplicate identities collide.' ); } );
	doc_case( '27 context restored flat', function () { doc_render(); doc_assert( array() === wp_seed_events_occurrence_context_current(), 'Flat context leaked.' ); } );
	doc_case( '28 context restored grouped', function () { doc_render( array( 'mode' => 'grouped' ) ); doc_assert( array() === wp_seed_events_occurrence_context_current(), 'Grouped context leaked.' ); } );
	doc_case( '29 context restored after exception', function () { $GLOBALS['throw_item_filter'] = true; try { doc_render(); } catch ( RuntimeException $error ) {} $GLOBALS['throw_item_filter'] = false; doc_assert( array() === wp_seed_events_occurrence_context_current(), 'Exception leaked context.' ); } );
	doc_case( '30 escaped event title', function () { $original = $GLOBALS['items'][0]['event_title']; $GLOBALS['items'][0]['event_title'] = '<script>alert(1)</script>'; $html = doc_render(); $GLOBALS['items'][0]['event_title'] = $original; doc_assert( ! doc_contains( '<script>', $html ), 'Title not escaped.' ); } );
	doc_case( '31 no duplicate IDs', fn() => doc_assert( 0 === preg_match( '/\sid="/', doc_render( array( 'mode' => 'grouped' ) ) ), 'Duplicate HTML ID risk.' ) );
	doc_case( '32 semantic items', fn() => doc_assert( 3 === substr_count( doc_render(), '<article ' ), 'Article structure differs.' ) );
	doc_case( '33 labels can hide', fn() => doc_assert( ! doc_contains( '<dt ', doc_render( array( 'show_labels' => 'off' ) ) ), 'Labels remained visible.' ) );
	doc_case( '34 fields can hide', fn() => doc_assert( ! doc_contains( 'event-type', doc_render( array( 'show_event_type' => 'off' ) ) ), 'Hidden field rendered.' ) );
	doc_case( '35 promotion filter forwarded', function () { $GLOBALS['flat_calls'] = array(); doc_render( array( 'promotion' => 'promotion-2026' ) ); doc_assert( 'promotion-2026' === $GLOBALS['flat_calls'][0]['args']['promotion'], 'Promotion filter changed.' ); } );
	doc_case( '36 date filters forwarded', function () { $GLOBALS['flat_calls'] = array(); doc_render( array( 'from' => '2026-01-01', 'to' => '2026-12-31' ) ); doc_assert( '2026-01-01' === $GLOBALS['flat_calls'][0]['args']['from'] && '2026-12-31' === $GLOBALS['flat_calls'][0]['args']['to'], 'Date bounds changed.' ); } );
	doc_case( '37 index-ready adapter path', function () { $GLOBALS['api_mode'] = 'index'; $html = doc_render(); doc_assert( doc_contains( 'occ-2026-a', $html ), 'Index result missing.' ); } );
	doc_case( '38 lifecycle fallback adapter path', function () { $GLOBALS['api_mode'] = 'fallback'; $html = doc_render(); $GLOBALS['api_mode'] = 'index'; doc_assert( doc_contains( 'occ-2026-a', $html ), 'Fallback result missing.' ); } );
	doc_case( '39 no direct storage', fn() => doc_assert( ! doc_contains( 'get_post_meta', $source ) && ! doc_contains( '$wpdb', $source ), 'Direct storage access found.' ) );
	doc_case( '40 public APIs only once', fn() => doc_assert( 1 === substr_count( $source, 'wp_seed_events_query_occurrence_collection( $args )' ) && 1 === substr_count( $source, 'wp_seed_events_query_grouped_occurrence_collection( $args )' ), 'Public API call sites differ.' ) );
	doc_case( '41 REST preview', function () { $request = new WP_REST_Request( array( 'mode' => 'flat', 'per_page' => 2, 'module_id' => 'preview' ) ); $response = WP_Seed_Events_Divi_Occurrence_Collection_Module::rest_preview( $request ); doc_assert( doc_contains( 'data-collection-id=', $response['html'] ), 'REST preview empty.' ); } );
	doc_case( '42 two modules stay isolated', function () { $one = doc_render( array(), 'one' ); $two = doc_render( array( 'mode' => 'grouped' ), 'two' ); doc_assert( doc_contains( 'data-collection-id="one"', $one ) && doc_contains( 'data-collection-id="two"', $two ) && array() === wp_seed_events_occurrence_context_current(), 'Module state leaked.' ); } );

	doc_assert( 42 === $GLOBALS['doc_cases'], 'Harness case count differs.' );
	echo '[OK] Divi occurrence collection module: 42/42 cases passed.' . PHP_EOL;
}
