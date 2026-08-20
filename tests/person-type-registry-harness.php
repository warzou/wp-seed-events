<?php
define( 'ABSPATH', __DIR__ );
$GLOBALS['p_types_options'] = array();
$GLOBALS['p_types_routes']  = array();
function __( $value ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', (string) $value ) ); }
function sanitize_title( $value ) { return trim( preg_replace( '/-+/', '-', preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ) ), '-' ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function get_option( $key, $default = false ) { return array_key_exists( $key, $GLOBALS['p_types_options'] ) ? $GLOBALS['p_types_options'][ $key ] : $default; }
function update_option( $key, $value ) { $GLOBALS['p_types_options'][ $key ] = $value; return true; }
function add_action( $hook, $callback ) {}
function register_rest_route( $namespace, $route, $args ) { $GLOBALS['p_types_routes'][ $namespace . $route ] = $args; }
function rest_ensure_response( $value ) { return $value; }
class WP_Error {
	public function __construct( public $code, public $message ) {}
}
class WP_REST_Server { const READABLE = 'GET'; }

$source = getenv( 'WP_SEED_EVENTS_PERSON_TYPES_SOURCE' );
$source = is_string( $source ) && '' !== $source ? $source : dirname( __DIR__ ) . '/includes/public/person-types.php';
require $source;

$passed = 0;
function p_types_case( $name, $callback ) {
	global $passed;
	$callback();
	$passed++;
	echo "ok {$passed} - {$name}\n";
}
function p_types_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }

p_types_case( 'default registry is canonical', function () {
	p_types_assert( array( 'organizer', 'speaker', 'contact' ) === array_keys( wp_seed_events_person_type_options() ), 'Default registry differs.' );
} );
p_types_case( 'create appears immediately', function () {
	$key = wp_seed_events_add_person_type( 'Accompagnateur temporaire' );
	p_types_assert( 'accompagnateur-temporaire' === $key, 'Stable key differs.' );
	p_types_assert( 'Accompagnateur temporaire' === wp_seed_events_person_type_options()[ $key ], 'Created type missing.' );
} );
p_types_case( 'rename appears immediately without changing key', function () {
	wp_seed_events_rename_person_type( 'accompagnateur-temporaire', 'Facilitateur temporaire' );
	p_types_assert( 'Facilitateur temporaire' === wp_seed_events_person_type_options()['accompagnateur-temporaire'], 'Renamed label missing.' );
} );
p_types_case( 'REST reads the current registry without a second cache', function () {
	wp_seed_events_register_person_type_rest_route();
	$route = $GLOBALS['p_types_routes']['wp-seed-events/v1/person-types'];
	$body  = $route['callback']();
	$found = array_values( array_filter( $body['types'], fn( $type ) => 'accompagnateur-temporaire' === $type['key'] ) );
	p_types_assert( 1 === count( $found ) && 'Facilitateur temporaire' === $found[0]['label'], 'REST registry is stale.' );
} );
p_types_case( 'delete removes new choices but archives the label', function () {
	wp_seed_events_delete_person_type( 'accompagnateur-temporaire' );
	p_types_assert( ! isset( wp_seed_events_person_type_options()['accompagnateur-temporaire'] ), 'Deleted type remains active.' );
	p_types_assert( 'Facilitateur temporaire' === wp_seed_events_person_type_label( 'accompagnateur-temporaire' ), 'Historical label was remapped.' );
} );
p_types_case( 'required canonical types are protected', function () {
	p_types_assert( wp_seed_events_delete_person_type( 'contact' ) instanceof WP_Error, 'Required type was deleted.' );
} );

p_types_assert( 6 === $passed, 'Unexpected test count.' );
echo "Person type registry harness: 6/6 OK\n";
