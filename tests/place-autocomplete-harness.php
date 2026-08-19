<?php

declare(strict_types=1);

$GLOBALS['place_usage'] = array( 10 => 1, 11 => 3, 12 => 3, 13 => 0 );
$GLOBALS['places'] = array(
	(object) array( 'ID' => 13, 'post_title' => 'Zoo' ),
	(object) array( 'ID' => 10, 'post_title' => 'École' ),
	(object) array( 'ID' => 12, 'post_title' => 'atelier B' ),
	(object) array( 'ID' => 11, 'post_title' => 'Atelier A' ),
);

function get_posts( $args ) {
	if ( -1 !== $args['posts_per_page'] ) {
		throw new RuntimeException( 'Places query is capped.' );
	}
	return $GLOBALS['places'];
}
function wp_seed_events_place_usage_count( $id ) { return $GLOBALS['place_usage'][ $id ] ?? 0; }
function remove_accents( $value ) { return strtr( $value, array( 'É' => 'E', 'é' => 'e' ) ); }

$source = (string) file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );
$start  = strpos( $source, 'function wp_seed_events_get_place_suggestions(' );
$end    = strpos( $source, 'function wp_seed_events_render_place_meta_box(', $start );
if ( false === $start || false === $end ) {
	throw new RuntimeException( 'Place suggestion function not found.' );
}
eval( substr( $source, $start, $end - $start ) );

$ids = array_map( static fn( $place ) => $place->ID, wp_seed_events_get_place_suggestions() );
if ( array( 11, 12, 10, 13 ) !== $ids ) {
	throw new RuntimeException( 'Usage/alphabetical order differs: ' . json_encode( $ids ) );
}
if ( array( 11, 12 ) !== array_map( static fn( $place ) => $place->ID, wp_seed_events_get_place_suggestions( 2 ) ) ) {
	throw new RuntimeException( 'Explicit caller limit differs.' );
}
if ( false === strpos( $source, "hiddenField(root,'new_name').val(data.name)" ) || false === strpos( $source, "panel.data('wpSeedPlaceId','')" ) ) {
	throw new RuntimeException( 'Create/dissociate contract missing.' );
}

echo "Place autocomplete harness: 3/3 OK\n";
