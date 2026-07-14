<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load the Divi 5 Dynamic Content source when its public API exists.
 */
function wp_seed_events_divi_load_next_date() {
	static $option = null;

	if ( null !== $option ) {
		return;
	}

	if (
		! class_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentElements' )
		|| ! class_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionBase' )
		|| ! interface_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionInterface' )
	) {
		return;
	}

	require_once __DIR__ . '/class-dynamic-content-next-date.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Dynamic_Content_Next_Date', false ) ) {
		return;
	}

	$option = new WP_Seed_Events_Divi_Dynamic_Content_Next_Date();
	$option->load();
}
add_action( 'init', 'wp_seed_events_divi_load_next_date', 10 );
