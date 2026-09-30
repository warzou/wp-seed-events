<?php
require __DIR__ . '/visuals-renderer-harness.php';
function wp_get_attachment_image_src( $id, $size ) {
	$dimensions = array( 1 => array( 875, 1241 ), 2 => array( 1600, 900 ), 3 => array( 1000, 1000 ), 4 => array( 600, 2400 ) );
	$d = $dimensions[$id] ?? array( 1200, 800 );
	if ( 'near-crop' === $size ) return array( 'https://example.test/crop.jpg', 990, 1000 );
	return array( 'https://example.test/image.jpg', 'thumbnail' === $size ? 300 : $d[0], 'thumbnail' === $size ? 300 : $d[1] );
}
foreach ( array( 1, 2, 3, 4 ) as $id ) {
	wp_seed_events_visuals_assert( ( 3 === $id ? 'thumbnail' : 'full' ) === wp_seed_events_public_visuals_uncropped_size( $id, 'thumbnail' ), 'Cropped image selected: ' . $id );
	wp_seed_events_visuals_assert( 'large' === wp_seed_events_public_visuals_uncropped_size( $id, 'large' ), 'Uncropped image rejected.' );
}
foreach ( array( 'none', 'original' ) as $action ) {
	$GLOBALS['wp_seed_events_lightbox_calls'] = array();
	$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( wp_seed_events_visuals_media( 1 ) ) ), array( 'click_action' => $action ) );
	wp_seed_events_visuals_assert( array() === $GLOBALS['wp_seed_events_lightbox_calls'], 'Explicit action was ignored.' );
	wp_seed_events_visuals_assert( ( 'original' === $action ) === str_contains( $html, '<a ' ), 'Source-link choice ignored.' );
}
echo "Portrait, landscape, square, tall, explicit off/original: PASS\n";

wp_seed_events_visuals_assert( 'full' === wp_seed_events_public_visuals_uncropped_size( 3, 'near-crop' ), 'Even a one-percent crop must use full.' );
