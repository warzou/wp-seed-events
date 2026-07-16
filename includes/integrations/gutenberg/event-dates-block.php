<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Gutenberg event dates block from its compiled metadata.
 */
function wp_seed_events_register_event_dates_block() {
	static $registered = false;

	if ( $registered || ! function_exists( 'register_block_type_from_metadata' ) ) {
		return;
	}

	$build_path = __DIR__ . '/event-dates-block/build';

	if ( ! is_readable( $build_path . '/block.json' ) ) {
		return;
	}

	$registered = (bool) register_block_type_from_metadata( $build_path );
}
add_action( 'init', 'wp_seed_events_register_event_dates_block', 20 );
