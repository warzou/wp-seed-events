<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/context.php';
require_once __DIR__ . '/collection-query.php';

/**
 * Load the Divi 5 Dynamic Content sources when its public API exists.
 */
function wp_seed_events_divi_load_next_date() {
	static $options = null;

	if ( null !== $options ) {
		return;
	}

	if (
		! class_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentElements' )
		|| ! class_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionBase' )
		|| ! interface_exists( '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentOptionInterface' )
	) {
		return;
	}

	require_once __DIR__ . '/class-dynamic-content-text.php';
	require_once __DIR__ . '/class-dynamic-content-url.php';
	require_once __DIR__ . '/class-dynamic-content-image.php';
	require_once __DIR__ . '/class-dynamic-content-next-date.php';

	if (
		! class_exists( 'WP_Seed_Events_Divi_Dynamic_Content_Text', false )
		|| ! class_exists( 'WP_Seed_Events_Divi_Dynamic_Content_URL', false )
		|| ! class_exists( 'WP_Seed_Events_Divi_Dynamic_Content_Image', false )
		|| ! class_exists( 'WP_Seed_Events_Divi_Dynamic_Content_Next_Date', false )
	) {
		return;
	}

	$options = array();

	foreach ( wp_seed_events_dynamic_data_fields() as $field => $definition ) {
		$type = (string) ( $definition['type'] ?? '' );

		if ( ! in_array( $type, array( 'text', 'url', 'image' ), true ) ) {
			continue;
		}

		if ( 'next_date' === $field ) {
			$option = new WP_Seed_Events_Divi_Dynamic_Content_Next_Date();
		} elseif ( 'url' === $type ) {
			$option = new WP_Seed_Events_Divi_Dynamic_Content_URL();

			if ( ! $option->configure( $field ) ) {
				continue;
			}
		} elseif ( 'image' === $type ) {
			$option = new WP_Seed_Events_Divi_Dynamic_Content_Image();

			if ( ! $option->configure( $field ) ) {
				continue;
			}
		} else {
			$option = new WP_Seed_Events_Divi_Dynamic_Content_Text();

			if ( ! $option->configure( $field ) ) {
				continue;
			}
		}

		$name = $option->get_name();

		if ( '' === $name || isset( $options[ $name ] ) ) {
			continue;
		}

		$option->load();
		$options[ $name ] = $option;
	}
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
 * Register the Divi 5 event visuals module in the official dependency tree.
 */
function wp_seed_events_divi_register_event_visuals_module( $dependency_tree ) {
	if (
		! function_exists( 'et_builder_d5_enabled' )
		|| ! et_builder_d5_enabled()
		|| ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' )
		|| ! class_exists( '\ET\Builder\Packages\ModuleLibrary\ModuleRegistration' )
	) {
		return;
	}

	require_once __DIR__ . '/class-event-visuals-module.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Event_Visuals_Module', false ) ) {
		return;
	}

	$dependency_tree->add_dependency( new WP_Seed_Events_Divi_Event_Visuals_Module() );
}
add_action( 'divi_module_library_modules_dependency_tree', 'wp_seed_events_divi_register_event_visuals_module' );

/**
 * Register the Divi 5 event people module in the official dependency tree.
 */
function wp_seed_events_divi_register_event_people_module( $dependency_tree ) {
	if (
		! function_exists( 'et_builder_d5_enabled' )
		|| ! et_builder_d5_enabled()
		|| ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' )
		|| ! class_exists( '\ET\Builder\Packages\ModuleLibrary\ModuleRegistration' )
	) {
		return;
	}

	require_once __DIR__ . '/class-event-people-module.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Event_People_Module', false ) ) {
		return;
	}

	$dependency_tree->add_dependency( new WP_Seed_Events_Divi_Event_People_Module() );
}
add_action( 'divi_module_library_modules_dependency_tree', 'wp_seed_events_divi_register_event_people_module' );

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

/**
 * Enqueue the compiled visuals module only inside the Divi 5 Visual Builder.
 */
function wp_seed_events_divi_enqueue_event_visuals_module_assets() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' )
	) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-event-visuals-module',
			'version' => WP_SEED_EVENTS_VERSION,
			'script'  => array(
				'src'                => plugins_url( 'event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js', __FILE__ ),
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
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_visuals_module_assets' );
/**
 * Resolve the historical event visual source inside native Divi loop previews.
 */
function wp_seed_events_divi_enqueue_dynamic_event_image_preview_asset() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' )
	) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-dynamic-event-image-preview',
			'version' => WP_SEED_EVENTS_VERSION,
			'script'  => array(
				'src'                => plugins_url( 'dynamic-event-image-preview.js', __FILE__ ),
				'deps'               => array(
					'divi-module-library',
					'divi-vendor-wp-hooks',
				),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_dynamic_event_image_preview_asset' );

/**
 * Enqueue the compiled people module only inside the Divi 5 Visual Builder.
 */
function wp_seed_events_divi_enqueue_event_people_module_assets() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' )
	) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-event-people-module',
			'version' => WP_SEED_EVENTS_VERSION,
			'script'  => array(
				'src'                => plugins_url( 'event-people-module/visual-builder/build/wp-seed-events-event-people.js', __FILE__ ),
				'deps'               => array(
					'divi-module-library',
					'divi-vendor-wp-hooks',
				),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_people_module_assets' );

/**
 * Register the dedicated Divi 5 occurrence collection module.
 */
function wp_seed_events_divi_register_occurrence_collection_module( $dependency_tree ) {
	if (
		! function_exists( 'et_builder_d5_enabled' )
		|| ! et_builder_d5_enabled()
		|| ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' )
		|| ! class_exists( '\ET\Builder\Packages\ModuleLibrary\ModuleRegistration' )
	) {
		return;
	}

	require_once __DIR__ . '/class-occurrence-collection-module.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Occurrence_Collection_Module', false ) ) {
		return;
	}

	$dependency_tree->add_dependency( new WP_Seed_Events_Divi_Occurrence_Collection_Module() );
}
add_action( 'divi_module_library_modules_dependency_tree', 'wp_seed_events_divi_register_occurrence_collection_module' );

/**
 * Enqueue the compiled occurrence collection module in the Divi app window.
 */
function wp_seed_events_divi_enqueue_occurrence_collection_module_assets() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\ET\Builder\VisualBuilder\Assets\PackageBuildManager' )
	) {
		return;
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-occurrence-collection-module',
			'version' => WP_SEED_EVENTS_VERSION,
			'script'  => array(
				'src'                => plugins_url( 'occurrence-collection-module/visual-builder/build/wp-seed-events-occurrence-collection.js', __FILE__ ),
				'deps'               => array( 'divi-module-library', 'divi-vendor-wp-hooks', 'divi-rest' ),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_occurrence_collection_module_assets' );
