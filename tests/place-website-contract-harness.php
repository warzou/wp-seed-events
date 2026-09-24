<?php
/**
 * Standalone assertions for the canonical place website pair.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['place_posts'] = array(
	91 => (object) array( 'ID' => 91, 'post_type' => 'wp_seed_place', 'post_title' => 'Centre Shania' ),
);
$GLOBALS['place_meta'] = array(
	41 => array(
		'_wp_seed_event_place_id'      => 91,
		'_wp_seed_event_place_details' => "Entrée côté cour",
	),
	91 => array(
		'_wp_seed_place_address'      => '3 rue du Test',
		'_wp_seed_place_link'         => 'https://example.test/shania',
		'_wp_seed_place_link_label'   => 'Découvrir le lieu',
		'_wp_seed_place_link_visible' => '1',
	),
);
$GLOBALS['place_rest_fields'] = array();

function add_action() {}
function absint( $value ) { return abs( (int) $value ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function get_post_meta( $post_id, $key ) { return $GLOBALS['place_meta'][ $post_id ][ $key ] ?? ''; }
function get_post( $post_id ) { return $GLOBALS['place_posts'][ $post_id ] ?? null; }
function get_the_title( $post_id ) { return $GLOBALS['place_posts'][ $post_id ]->post_title ?? ''; }
function register_rest_field( $type, $name, $args ) { $GLOBALS['place_rest_fields'][ $type ][ $name ] = $args; }
function wp_seed_events_sanitize_public_http_url( $value ) {
	$parts = parse_url( trim( (string) $value ) );
	return is_array( $parts ) && in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true )
		? trim( (string) $value )
		: '';
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';

function place_assert_same( $expected, $actual, $message ) {
	if ( $expected !== $actual ) {
		throw new RuntimeException( $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}

$place = wp_seed_events_public_event_place_data( 41 );
place_assert_same( 'https://example.test/shania', $place['place_url'], 'Canonical place URL differs.' );
place_assert_same( 'Découvrir le lieu', $place['place_url_label'], 'Canonical place label differs.' );
place_assert_same( "Entrée côté cour", $place['details'], 'Event-scoped details differ.' );

unset( $GLOBALS['place_meta'][91]['_wp_seed_place_link_label'] );
$fallback = wp_seed_events_public_event_place_data( 41 );
place_assert_same( 'https://example.test/shania', $fallback['place_url_label'], 'Historical URL fallback differs.' );

$GLOBALS['place_meta'][91]['_wp_seed_place_link_visible'] = '0';
$hidden = wp_seed_events_public_event_place_data( 41 );
place_assert_same( '', $hidden['place_url'], 'Hidden place URL leaked.' );
place_assert_same( '', $hidden['place_url_label'], 'Hidden place label leaked.' );

wp_seed_events_register_place_rest_fields();
$rest = $GLOBALS['place_rest_fields']['wp_seed_event']['wp_seed_event_place'] ?? array();
place_assert_same( 'wp_seed_events_place_rest_get', $rest['get_callback'] ?? '', 'Place REST callback differs.' );
place_assert_same( false, isset( $rest['update_callback'] ), 'Public place projection became writable.' );

echo "Place website/Event Data/REST contract: PASS\n";
