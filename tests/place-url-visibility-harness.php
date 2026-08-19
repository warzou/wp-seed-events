<?php

declare(strict_types=1);

$GLOBALS['place_visibility_meta'] = array();

function get_post_meta( $post_id, $key, $single = false ) {
	return $GLOBALS['place_visibility_meta'][ $post_id ][ $key ] ?? '';
}

$source = (string) file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$start  = strpos( $source, 'function wp_seed_events_place_url_is_visible(' );
$end    = strpos( $source, 'function wp_seed_events_public_event_place_data(', $start );

if ( false === $start || false === $end ) {
	throw new RuntimeException( 'Place URL visibility helper not found.' );
}

eval( substr( $source, $start, $end - $start ) );

$GLOBALS['place_visibility_meta'][10]['_wp_seed_place_link_visible'] = '';
$GLOBALS['place_visibility_meta'][11]['_wp_seed_place_link_visible'] = '1';
$GLOBALS['place_visibility_meta'][12]['_wp_seed_place_link_visible'] = '0';

if ( ! wp_seed_events_place_url_is_visible( 10 ) ) {
	throw new RuntimeException( 'Historical place URL compatibility failed.' );
}
if ( ! wp_seed_events_place_url_is_visible( 11 ) ) {
	throw new RuntimeException( 'Explicit visible URL failed.' );
}
if ( wp_seed_events_place_url_is_visible( 12 ) ) {
	throw new RuntimeException( 'Explicit hidden URL failed.' );
}

echo "Place URL visibility harness: 3/3 PASS\n";
