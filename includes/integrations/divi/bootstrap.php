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
add_action( 'wp', 'wp_seed_events_divi_load_next_date', 5 );

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
 * Register the Divi 5 event share module in the official dependency tree.
 */
function wp_seed_events_divi_register_event_share_module( $dependency_tree ) {
	if (
		! function_exists( 'et_builder_d5_enabled' )
		|| ! et_builder_d5_enabled()
		|| ! interface_exists( '\ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface' )
		|| ! class_exists( '\ET\Builder\Packages\ModuleLibrary\ModuleRegistration' )
	) {
		return;
	}

	require_once __DIR__ . '/class-event-share-module.php';

	if ( ! class_exists( 'WP_Seed_Events_Divi_Event_Share_Module', false ) ) {
		return;
	}

	$dependency_tree->add_dependency( new WP_Seed_Events_Divi_Event_Share_Module() );
}
add_action( 'divi_module_library_modules_dependency_tree', 'wp_seed_events_divi_register_event_share_module' );

/** Return a content-addressed version for a public Divi asset. */
function wp_seed_events_divi_asset_version( $relative_path ) {
	$path = __DIR__ . '/' . ltrim( (string) $relative_path, '/' );
	$hash = is_readable( $path ) ? hash_file( 'sha256', $path ) : '';
	return is_string( $hash ) && '' !== $hash ? WP_SEED_EVENTS_VERSION . '-' . substr( $hash, 0, 12 ) : WP_SEED_EVENTS_VERSION;
}

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

	$script_path    = __DIR__ . '/event-dates-module/visual-builder/build/wp-seed-events-event-dates.js';
	$script_version = WP_SEED_EVENTS_VERSION;

	if ( is_readable( $script_path ) ) {
		$script_hash = hash_file( 'sha256', $script_path );

		if ( is_string( $script_hash ) && '' !== $script_hash ) {
			$script_version .= '-' . substr( $script_hash, 0, 12 );
		}
	}

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-event-dates-module',
			'version' => $script_version,
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
			'version' => wp_seed_events_divi_asset_version( 'event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js' ),
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

	$context_path = __DIR__ . '/visual-builder-event-context.js';
	$preview_path = __DIR__ . '/dynamic-event-data-preview.js';
	$context_hash = is_readable( $context_path ) ? substr( hash_file( 'sha256', $context_path ), 0, 12 ) : WP_SEED_EVENTS_VERSION;
	$preview_hash = is_readable( $preview_path ) ? substr( hash_file( 'sha256', $preview_path ), 0, 12 ) : WP_SEED_EVENTS_VERSION;

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-visual-builder-context',
			'version' => $context_hash,
			'script'  => array(
				'src'                => plugins_url( 'visual-builder-event-context.js', __FILE__ ),
				'deps'               => array( 'divi-module-library' ),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-dynamic-event-data-preview',
			'version' => $preview_hash,
			'script'  => array(
				'src'                => plugins_url( 'dynamic-event-data-preview.js', __FILE__ ),
				'deps'               => array( 'divi-module-library', 'divi-vendor-wp-hooks', 'wp-seed-events-visual-builder-context' ),
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			),
		)
	);
}
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_dynamic_event_image_preview_asset' );

/**
 * Add event-specific taxonomy controls to Divi's native loop group.
 */
function wp_seed_events_divi_enqueue_event_collection_filters_asset() {
	if (
		! function_exists( 'et_core_is_fb_enabled' )
		|| ! function_exists( 'et_builder_d5_enabled' )
		|| ! et_core_is_fb_enabled()
		|| ! et_builder_d5_enabled()
		|| ! class_exists( '\\ET\\Builder\\VisualBuilder\\Assets\\PackageBuildManager' )
	) {
		return;
	}

	$asset_path    = __DIR__ . '/event-collection-filters.js';
	$asset_version = is_readable( $asset_path )
		? substr( hash_file( 'sha256', $asset_path ), 0, 12 )
		: WP_SEED_EVENTS_VERSION;

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		array(
			'name'    => 'wp-seed-events-event-collection-filters',
			'version' => $asset_version,
			'script'  => array(
				'src'                => plugins_url( 'event-collection-filters.js', __FILE__ ),
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
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_collection_filters_asset' );

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
			'version' => wp_seed_events_divi_asset_version( 'event-people-module/visual-builder/build/wp-seed-events-event-people.js' ),
			'script'  => array(
				'src'                => plugins_url( 'event-people-module/visual-builder/build/wp-seed-events-event-people.js', __FILE__ ),
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
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_people_module_assets' );

/**
 * Enqueue the compiled share module only inside the Divi 5 Visual Builder.
 */
function wp_seed_events_divi_enqueue_event_share_module_assets() {
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
			'name'    => 'wp-seed-events-event-share-module',
			'version' => wp_seed_events_divi_asset_version( 'event-share-module/visual-builder/build/wp-seed-events-event-share.js' ),
			'script'  => array(
				'src'                => plugins_url( 'event-share-module/visual-builder/build/wp-seed-events-event-share.js', __FILE__ ),
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
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', 'wp_seed_events_divi_enqueue_event_share_module_assets' );

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
			'version' => wp_seed_events_divi_asset_version( 'occurrence-collection-module/visual-builder/build/wp-seed-events-occurrence-collection.js' ),
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
