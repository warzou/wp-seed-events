<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/context.php';

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

/**
 * Register the Divi 5 event dates module in the official dependency tree.
 */
function wp_seed_events_divi_register_event_dates_module( $dependency_tree ) {
	if (
		! function_exists( 'et_builder_d5_enabled' )
		|| ! et_builder_d5_enabled()
		|| ! interface_exists( '\\ET\\Builder\\Framework\\DependencyManagement\\Interfaces\\DependencyInterface' )
		|| ! class_exists( '\\ET\\Builder\\Packages\\ModuleLibrary\\ModuleRegistration' )
	) {
		return;
	}

	require_once __DIR__ . '/class-event-dates-module.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Event_Dates_Module', false ) ) {
		return;
	}

	$dependency_tree->add_dependency( new WP_Seed_Events_Divi_Event_Dates_Module() );
}
add_action( 'divi_module_library_modules_dependency_tree', 'wp_seed_events_divi_register_event_dates_module' );

/**
 * Enqueue the compiled module only inside the Divi 5 Visual Builder.
 */
function wp_seed_events_divi_enqueue_event_dates_module_assets() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\\ET\\Builder\\VisualBuilder\\Assets\\PackageBuildManager' )
	) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-event-dates-module',
			'version' => WP_SEED_EVENTS_VERSION,
			'script'  => array(
				'src'                => plugins_url( 'event-dates-module/visual-builder/build/wp-seed-events-event-dates.js', __FILE__ ),
				'deps'               => array(
					'divi-module-library',
					'divi-vendor-wp-hooks',
					'divi-rest',
				),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_dates_module_assets' );
