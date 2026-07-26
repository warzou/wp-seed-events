<?php
/**
 * Contract checks for the event featured-image administration.
 *
 * @package WPSeedEvents
 */

$root = dirname( __DIR__ );
$main  = file_get_contents( $root . '/wp-seed-events.php' );
$media = file_get_contents( $root . '/includes/public/media.php' );

if ( false === $main || false === $media ) {
	fwrite( STDERR, "Unable to read the event media sources.\n" );
	exit( 1 );
}

$assertions = 0;

function wp_seed_events_featured_image_assert( $condition, $message ) {
	global $assertions;

	++$assertions;

	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

wp_seed_events_featured_image_assert(
	false !== strpos(
		$main,
		"add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_remove_native_featured_image_meta_box', 100 );"
	),
	'The event-only metabox hook is missing.'
);
wp_seed_events_featured_image_assert(
	1 === preg_match( '/function wp_seed_events_remove_native_featured_image_meta_box\(\)\s*\{(?<body>.*?)\n\}/s', $main, $matches ),
	'The featured-image metabox callback is missing.'
);

$callback = $matches['body'] ?? '';

wp_seed_events_featured_image_assert(
	false !== strpos( $callback, "remove_meta_box( 'postimagediv', 'wp_seed_event', 'side' );" ),
	'The callback does not remove the native event featured-image metabox.'
);
wp_seed_events_featured_image_assert(
	1 === substr_count( $callback, 'remove_meta_box(' ),
	'The callback removes more than one metabox.'
);
wp_seed_events_featured_image_assert(
	false === strpos( $callback, "'page'" ) && false === strpos( $callback, "'post'" ),
	'The callback targets another WordPress content type.'
);
wp_seed_events_featured_image_assert(
	false === strpos( $main, "remove_post_type_support( 'wp_seed_event', 'thumbnail'" ),
	'Event thumbnail support is removed.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "'supports'           => array( 'title', 'thumbnail' )" ),
	'The event post type no longer declares thumbnail support.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "add_theme_support( 'post-thumbnails', array( 'wp_seed_event' ) );" ),
	'Theme thumbnail support for events is missing.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_media' )" ),
	'The event media save hook changed.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "update_post_meta( \$post_id, '_wp_seed_event_illustration_ids', \$illustration_ids );" ),
	'The ordered communication visuals are no longer persisted.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, 'set_post_thumbnail( $post_id, $illustration_ids[0] );' ),
	'The first communication visual is no longer projected to the thumbnail.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "delete_post_meta( \$post_id, '_wp_seed_event_illustration_ids' );" ),
	'The empty communication visual list is no longer removed.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, 'delete_post_thumbnail( $post_id );' ),
	'The thumbnail is no longer removed with the final communication visual.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $media, "wp_seed_events_get_media_object( get_post_thumbnail_id( \$event_id ), 'image/' )" ),
	'The public media normalizer no longer reads the projected thumbnail.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "'show_in_rest'       => true" ),
	'The event post type is no longer exposed through REST.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, 'wp_seed_events_render_media_meta_box( $post, $event_media );' ),
	'The communication visuals panel is no longer rendered.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, 'Le premier visuel est utilis' ),
	'The communication visuals guidance is missing.'
);
wp_seed_events_featured_image_assert(
	false === strpos( $callback, '<style' ) && false === strpos( $callback, 'display:none' ),
	'The native metabox is hidden with CSS.'
);
wp_seed_events_featured_image_assert(
	false === strpos( $main, 'wp_delete_attachment(' ) && false === strpos( $main, 'wp_delete_post( $attachment' ),
	'The event media workflow deletes attachments.'
);
wp_seed_events_featured_image_assert(
	false !== strpos( $main, "require_once __DIR__ . '/includes/integrations/gutenberg/block-bindings.php';" )
	&& false !== strpos( $main, "require_once __DIR__ . '/includes/integrations/divi/bootstrap.php';" ),
	'Builder integrations are no longer loaded.'
);

echo "Featured image admin contract: {$assertions}/{$assertions}\n";
