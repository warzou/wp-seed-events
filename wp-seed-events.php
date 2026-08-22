<?php
/**
 * Plugin Name: WP Seed Events
 * Description: Autonomous event publishing foundation for WordPress.
 * Version: 0.2.0-beta.9
 * Author: WP Seed
 * Update URI: https://github.com/warzou/wp-seed-events
 * Text Domain: wp-seed-events
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WP_SEED_EVENTS_VERSION' ) ) {
	$wp_seed_events_version = '';

	if ( function_exists( 'get_file_data' ) ) {
		$wp_seed_events_plugin_data = get_file_data(
			__FILE__,
			array(
				'Version' => 'Version',
			),
			'plugin'
		);

		$wp_seed_events_version = isset( $wp_seed_events_plugin_data['Version'] ) ? trim( (string) $wp_seed_events_plugin_data['Version'] ) : '';
	}

	if ( '' === $wp_seed_events_version ) {
		$wp_seed_events_source = file_get_contents( __FILE__ );

		if ( false !== $wp_seed_events_source && preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $wp_seed_events_source, $wp_seed_events_matches ) ) {
			$wp_seed_events_version = trim( $wp_seed_events_matches[1] );
		}
	}

	define( 'WP_SEED_EVENTS_VERSION', '' !== $wp_seed_events_version ? $wp_seed_events_version : '0.0.0-dev' );
}

if ( ! defined( 'WP_SEED_EVENTS_REWRITE_VERSION' ) ) {
	define( 'WP_SEED_EVENTS_REWRITE_VERSION', '2026-08-05-type-scoped-event-rewrites-v2' );
}

require_once __DIR__ . '/includes/public/occurrences.php';
require_once __DIR__ . '/includes/public/classifications.php';
require_once __DIR__ . '/includes/public/promotions.php';
require_once __DIR__ . '/includes/admin/promotions.php';
require_once __DIR__ . '/includes/admin/occurrence-projection.php';
require_once __DIR__ . '/includes/admin/lifecycle-index.php';
require_once __DIR__ . '/includes/admin/lifecycle-index-backfill.php';
require_once __DIR__ . '/includes/admin/lifecycle-filter.php';
require_once __DIR__ . '/includes/admin/github-updater.php';
require_once __DIR__ . '/includes/admin/people-suggestions.php';
require_once __DIR__ . '/includes/admin/contact-migration.php';
require_once __DIR__ . '/includes/public/media.php';
require_once __DIR__ . '/includes/public/descriptions.php';
require_once __DIR__ . '/includes/public/person-types.php';
require_once __DIR__ . '/includes/public/event-data.php';
require_once __DIR__ . '/includes/public/people.php';
require_once __DIR__ . '/includes/public/calendar.php';
require_once __DIR__ . '/includes/public/sharing.php';
require_once __DIR__ . '/includes/public/lightbox.php';
require_once __DIR__ . '/includes/public/collections.php';
require_once __DIR__ . '/includes/public/occurrence-collections.php';
require_once __DIR__ . '/includes/public/occurrence-context.php';
require_once __DIR__ . '/includes/public/rendering.php';
require_once __DIR__ . '/includes/public/data-registry.php';
require_once __DIR__ . '/includes/integrations/gutenberg/block-bindings.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-dates-block.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-visuals-block.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-document-block.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-people-block.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-collection-query.php';
require_once __DIR__ . '/includes/integrations/gutenberg/event-collection-patterns.php';
require_once __DIR__ . '/includes/integrations/gutenberg/occurrence-collection-block.php';
require_once __DIR__ . '/includes/integrations/divi/bootstrap.php';

register_activation_hook( __FILE__, 'wp_seed_events_activate' );

add_action( 'init', 'wp_seed_events_register_event_post_type' );
add_action( 'rest_api_init', 'wp_seed_events_register_promotion_rest_routes' );
add_action( 'rest_api_init', 'wp_seed_events_register_occurrence_collection_rest_routes' );
add_action( 'admin_init', 'wp_seed_events_register_permalink_settings' );
add_action( 'admin_init', 'wp_seed_events_maybe_save_permalink_settings' );
add_action( 'admin_menu', 'wp_seed_events_register_plugin_admin_menu', 99 );
add_action( 'admin_notices', 'wp_seed_events_render_title_required_notice' );
add_action( 'admin_notices', 'wp_seed_events_promotion_admin_notice' );
add_filter( 'wp_insert_post_data', 'wp_seed_events_prepare_event_title_and_slug', 10, 2 );
add_filter( 'wp_insert_post_data', 'wp_seed_events_prepare_promotion_post_data', 10, 2 );
add_filter( 'redirect_post_location', 'wp_seed_events_title_required_redirect', 10, 2 );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_event_type_meta_box', 5 );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_occurrences_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_place_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_contacts_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_description_meta_box', 20 );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_remove_native_featured_image_meta_box', 100 );
add_action( 'add_meta_boxes_wp_seed_place', 'wp_seed_events_add_place_address_meta_box' );
add_action( 'add_meta_boxes_wp_seed_promotion', 'wp_seed_events_add_promotion_meta_box' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_occurrences' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_event_place' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_contacts' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_media' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_event_type' );
add_action( 'admin_post_wp_seed_events_save_event_types', 'wp_seed_events_handle_event_types_admin_form' );
add_action( 'admin_post_wp_seed_events_save_person_types', 'wp_seed_events_handle_person_types_admin_form' );
add_action( 'admin_post_wp_seed_events_save_people', 'wp_seed_events_handle_people_admin_form' );
add_action( 'admin_post_wp_seed_events_save_places', 'wp_seed_events_handle_places_admin_form' );
add_action( 'admin_post_wp_seed_events_save_display_settings', 'wp_seed_events_handle_display_settings_form' );
add_action( 'admin_post_wp_seed_events_download_occurrence_ics', 'wp_seed_events_handle_occurrence_ics_download' );
add_action( 'admin_post_nopriv_wp_seed_events_download_occurrence_ics', 'wp_seed_events_handle_occurrence_ics_download' );
add_action( 'admin_post_wp_seed_events_download_event_ics', 'wp_seed_events_handle_event_ics_download' );
add_action( 'admin_post_nopriv_wp_seed_events_download_event_ics', 'wp_seed_events_handle_event_ics_download' );
add_action( 'wp_footer', 'wp_seed_events_render_public_share_script', 99 );
add_action( 'enqueue_block_assets', 'wp_seed_events_enqueue_public_visuals_style' );
add_action( 'save_post_wp_seed_place', 'wp_seed_events_save_place_address' );
add_action( 'save_post_wp_seed_promotion', 'wp_seed_events_save_promotion' );
add_action( 'admin_enqueue_scripts', 'wp_seed_events_enqueue_media_admin' );
add_action( 'edit_form_after_title', 'wp_seed_events_render_media_before_description', 5 );
add_filter( 'wp_editor_settings', 'wp_seed_events_disable_description_media_buttons', 10, 2 );
add_filter( 'manage_wp_seed_event_posts_columns', 'wp_seed_events_event_admin_columns' );
add_action( 'manage_wp_seed_event_posts_custom_column', 'wp_seed_events_render_event_admin_column', 10, 2 );
add_filter( 'manage_wp_seed_promotion_posts_columns', 'wp_seed_events_promotion_admin_columns' );
add_action( 'manage_wp_seed_promotion_posts_custom_column', 'wp_seed_events_render_promotion_admin_column', 10, 2 );
add_filter( 'manage_edit-wp_seed_promotion_sortable_columns', 'wp_seed_events_promotion_sortable_columns' );
add_action( 'pre_get_posts', 'wp_seed_events_apply_promotion_admin_order' );
add_filter( 'post_row_actions', 'wp_seed_events_promotion_row_actions', 10, 2 );
add_filter( 'pre_trash_post', 'wp_seed_events_prevent_referenced_promotion_deletion', 10, 2 );
add_filter( 'pre_delete_post', 'wp_seed_events_prevent_referenced_promotion_deletion', 10, 2 );
add_filter( 'redirect_post_location', 'wp_seed_events_promotion_delete_blocked_redirect' );
add_action( 'admin_head-edit.php', 'wp_seed_events_event_admin_column_styles' );
add_filter( 'the_title', 'wp_seed_events_prefix_pinned_event_admin_title', 10, 2 );
add_filter( 'the_content', 'wp_seed_events_render_public_event_content' );
add_filter( 'template_include', 'wp_seed_events_public_template_include', 99 );
add_filter( 'body_class', 'wp_seed_events_public_body_class' );
add_filter( 'post_type_link', 'wp_seed_events_event_post_type_link', 10, 4 );
add_filter( 'query_vars', 'wp_seed_events_permalink_query_vars' );
add_action( 'init', 'wp_seed_events_maybe_flush_rewrite_rules', 99 );
add_action( 'template_redirect', 'wp_seed_events_redirect_event_to_canonical_url', 1 );
add_shortcode( 'wp_seed_event_card', 'wp_seed_events_event_card_shortcode' );
add_shortcode( 'wp_seed_events', 'wp_seed_events_event_collection_shortcode' );
add_shortcode( 'wp_seed_event', 'wp_seed_events_event_shortcode' );
add_shortcode( 'wp_seed_event_field', 'wp_seed_events_event_field_shortcode' );
add_shortcode( 'wp_seed_event_dates', 'wp_seed_events_event_dates_shortcode' );
add_shortcode( 'wp_seed_event_visuals', 'wp_seed_events_event_visuals_shortcode' );
add_shortcode( 'wp_seed_event_document', 'wp_seed_events_event_document_shortcode' );
add_shortcode( 'wp_seed_event_people', 'wp_seed_events_event_people_shortcode' );
add_shortcode( 'wp_seed_event_place', 'wp_seed_events_event_place_shortcode' );
add_shortcode( 'wp_seed_event_practical_info', 'wp_seed_events_event_practical_info_shortcode' );

function wp_seed_events_activate() {
	wp_seed_events_register_native_classifications();
	wp_seed_events_register_event_post_type();
	wp_seed_events_install_occurrence_projection_table();
	wp_seed_events_run_lifecycle_index_backfill_batch( true );
	flush_rewrite_rules();
	update_option( 'wp_seed_events_rewrite_version', WP_SEED_EVENTS_REWRITE_VERSION, false );
}

function wp_seed_events_enqueue_public_visuals_style() {
	$dates_stylesheet       = __DIR__ . '/includes/public/event-dates.css';
	$visuals_stylesheet     = __DIR__ . '/includes/public/event-visuals.css';
	$visuals_script         = __DIR__ . '/includes/public/event-visuals-divi.js';
	$lists_stylesheet       = __DIR__ . '/includes/public/event-lists.css';
	$description_stylesheet = __DIR__ . '/includes/public/event-descriptions.css';
	$dates_version          = WP_SEED_EVENTS_VERSION;
	$visuals_version        = WP_SEED_EVENTS_VERSION;
	$visuals_script_version = WP_SEED_EVENTS_VERSION;
	$lists_version          = WP_SEED_EVENTS_VERSION;
	$description_version    = WP_SEED_EVENTS_VERSION;

	if ( is_readable( $visuals_stylesheet ) ) {
		$visuals_hash = hash_file( 'sha256', $visuals_stylesheet );
		if ( is_string( $visuals_hash ) && '' !== $visuals_hash ) {
			$visuals_version .= '-' . substr( $visuals_hash, 0, 12 );
		}
	}

	if ( is_readable( $visuals_script ) ) {
		$visuals_script_hash = hash_file( 'sha256', $visuals_script );
		if ( is_string( $visuals_script_hash ) && '' !== $visuals_script_hash ) {
			$visuals_script_version .= '-' . substr( $visuals_script_hash, 0, 12 );
		}
	}

	if ( is_readable( $dates_stylesheet ) ) {
		$dates_hash = hash_file( 'sha256', $dates_stylesheet );

		if ( is_string( $dates_hash ) && '' !== $dates_hash ) {
			$dates_version .= '-' . substr( $dates_hash, 0, 12 );
		}
	}

	if ( is_readable( $description_stylesheet ) ) {
		$description_hash = hash_file( 'sha256', $description_stylesheet );

		if ( is_string( $description_hash ) && '' !== $description_hash ) {
			$description_version .= '-' . substr( $description_hash, 0, 12 );
		}
	}

	if ( is_readable( $lists_stylesheet ) ) {
		$lists_hash = hash_file( 'sha256', $lists_stylesheet );
		if ( is_string( $lists_hash ) && '' !== $lists_hash ) {
			$lists_version .= '-' . substr( $lists_hash, 0, 12 );
		}
	}

	wp_enqueue_style(
		'wp-seed-events-public-visuals',
		plugins_url( 'includes/public/event-visuals.css', __FILE__ ),
		array(),
		$visuals_version
	);
	wp_register_script(
		'wp-seed-events-divi-visuals-lightbox',
		plugins_url( 'includes/public/event-visuals-divi.js', __FILE__ ),
		array( 'jquery', 'magnific-popup' ),
		$visuals_script_version,
		true
	);
	wp_enqueue_style(
		'wp-seed-events-public-dates',
		plugins_url( 'includes/public/event-dates.css', __FILE__ ),
		array(),
		$dates_version
	);
	wp_enqueue_style(
		'wp-seed-events-public-lists',
		plugins_url( 'includes/public/event-lists.css', __FILE__ ),
		array(),
		$lists_version
	);
	wp_enqueue_style(
		'wp-seed-events-public-descriptions',
		plugins_url( 'includes/public/event-descriptions.css', __FILE__ ),
		array(),
		$description_version
	);
}


function wp_seed_events_prepare_event_title_and_slug( $data, $postarr ) {
	if ( empty( $data['post_type'] ) || 'wp_seed_event' !== $data['post_type'] ) {
		return $data;
	}

	$title   = isset( $data['post_title'] ) ? trim( wp_strip_all_tags( (string) $data['post_title'] ) ) : '';
	$status  = isset( $data['post_status'] ) ? (string) $data['post_status'] : '';
	$post_id = ! empty( $postarr['ID'] ) ? absint( $postarr['ID'] ) : 0;

	if ( '' === $title && in_array( $status, array( 'publish', 'future' ), true ) ) {
		$data['post_status'] = 'draft';
		$GLOBALS['wp_seed_events_title_required_notice'] = true;
		return $data;
	}

	if ( '' !== $title && wp_seed_events_event_slug_is_provisional( $data, $post_id ) ) {
		$data['post_name'] = wp_unique_post_slug( sanitize_title( $title ), $post_id, $status, 'wp_seed_event', 0 );
	}

	return $data;
}

function wp_seed_events_event_slug_is_provisional( $data, $post_id ) {
	$slug = isset( $data['post_name'] ) ? trim( (string) $data['post_name'] ) : '';
	$post = 0 < $post_id ? get_post( $post_id ) : null;

	if ( 0 < $post_id && ( ! ( $post instanceof WP_Post ) || 'auto-draft' !== $post->post_status ) ) {
		return false;
	}

	if ( '' === $slug ) {
		return true;
	}

	if ( 0 < $post_id && (string) $post_id === $slug ) {
		return true;
	}

	$provisional_bases = array(
		'auto-draft',
		'sans-titre',
		'untitled',
		sanitize_title( __( 'Auto Draft' ) ),
	);

	if ( $post instanceof WP_Post && '' !== trim( (string) $post->post_title ) ) {
		$provisional_bases[] = sanitize_title( (string) $post->post_title );
	}

	foreach ( array_unique( array_filter( $provisional_bases ) ) as $base ) {
		if ( preg_match( '/^' . preg_quote( $base, '/' ) . '(?:-[0-9]+)?$/D', $slug ) ) {
			return true;
		}
	}

	return false;
}

function wp_seed_events_title_required_redirect( $location, $post_id ) {
	if ( empty( $GLOBALS['wp_seed_events_title_required_notice'] ) ) {
		return $location;
	}

	$location = remove_query_arg( 'message', $location );

	return add_query_arg( 'wp_seed_events_title_required', '1', $location );
}

function wp_seed_events_render_title_required_notice() {
	if ( empty( $_GET['wp_seed_events_title_required'] ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'wp_seed_event' !== $screen->post_type ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'Ajoutez un titre à l’événement avant de le publier.', 'wp-seed-events' )
	);
}

function wp_seed_events_register_event_post_type() {
	wp_seed_events_enable_event_thumbnail_support();

	register_post_type(
		'wp_seed_event',
		array(
			'labels'       => array(
				'name'          => 'Évènements',
				'singular_name' => 'Évènement',
				'menu_name'     => 'Évènements',
				'add_new_item'  => 'Ajouter un évènement',
				'edit_item'     => 'Modifier l’évènement',
				'all_items'     => 'Tous les évènements',
				'featured_image' => 'Image principale',
				'set_featured_image' => 'Choisir l’image principale',
				'remove_featured_image' => 'Retirer l’image principale',
				'use_featured_image' => 'Utiliser comme image principale',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'exclude_from_search' => true,
			'has_archive'        => false,
			'rewrite'            => array(
				'slug'       => 'wp_seed_event',
				'with_front' => false,
			),
			'query_var'          => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => 57,
			'supports'           => array( 'title', 'thumbnail' ),
			'show_in_rest'       => true,
		)
	);

	register_post_type(
		'wp_seed_place',
		array(
			'labels'       => array(
				'name'          => 'Lieux',
				'singular_name' => 'Lieu',
				'menu_name'     => 'Tous les lieux d’évènements',
				'add_new_item'  => 'Ajouter un lieu',
				'edit_item'     => 'Modifier le lieu',
				'all_items'     => 'Tous les lieux d’évènements',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'supports'     => array( 'title' ),
			'show_in_rest' => false,
		)
	);
	wp_seed_events_register_promotion_post_type();
	wp_seed_events_add_event_rewrite_rules();
}


function wp_seed_events_default_permalink_prefix() {
	return 'evenements';
}

function wp_seed_events_permalink_prefix() {
	$prefix = get_option( 'wp_seed_events_permalink_prefix', wp_seed_events_default_permalink_prefix() );
	$prefix = trim( (string) $prefix, "/ \t\n\r\0\x0B" );

	return sanitize_title( $prefix );
}

function wp_seed_events_permalink_includes_primary_type() {
	return '1' === get_option( 'wp_seed_events_permalink_include_primary_type', '1' );
}

function wp_seed_events_maybe_flush_rewrite_rules() {
	if ( function_exists( 'wp_installing' ) && wp_installing() ) {
		return;
	}

	if ( WP_SEED_EVENTS_REWRITE_VERSION === get_option( 'wp_seed_events_rewrite_version', '' ) ) {
		return;
	}

	flush_rewrite_rules( false );
	update_option( 'wp_seed_events_rewrite_version', WP_SEED_EVENTS_REWRITE_VERSION, false );
}

function wp_seed_events_event_type_public_slug_for_key( $type_key ) {
	$type_key = sanitize_key( $type_key );

	if ( '' === $type_key ) {
		return '';
	}

	return function_exists( 'wp_seed_events_native_event_type_slug' )
		? wp_seed_events_native_event_type_slug( $type_key )
		: sanitize_title( str_replace( '_', '-', $type_key ) );
}

function wp_seed_events_public_event_type_rewrite_slugs() {
	$slugs         = array();
	$reserved_roots = wp_seed_events_reserved_root_slugs();

	foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) {
		$slug = wp_seed_events_event_type_public_slug( $type_key );

		if ( '' !== $slug && ! wp_seed_events_event_type_slug_has_root_conflict( $slug, $reserved_roots ) ) {
			$slugs[ $slug ] = sanitize_key( $type_key );
		}
	}

	ksort( $slugs, SORT_STRING );

	return $slugs;
}

function wp_seed_events_reserved_root_slugs() {
	$reserved = array(
		'atom',
		'attachment',
		'author',
		'category',
		'comments',
		'comment-page',
		'embed',
		'favicon.ico',
		'feed',
		'index.php',
		'page',
		'rdf',
		'robots.txt',
		'rss',
		'rss2',
		's',
		'search',
		'sitemap.xml',
		'tag',
		'wp-admin',
		'wp-content',
		'wp-includes',
		'wp-json',
		'wp-login.php',
		'wp-sitemap.xml',
		'xmlrpc.php',
	);

	global $wp_post_types, $wp_taxonomies, $wp_rewrite;

	foreach ( array( $wp_post_types, $wp_taxonomies ) as $objects ) {
		if ( ! is_array( $objects ) ) {
			continue;
		}

		foreach ( $objects as $name => $object ) {
			if ( 'wp_seed_event' === $name || empty( $object->rewrite ) ) {
				continue;
			}

			$rewrite_slug = is_array( $object->rewrite ) && isset( $object->rewrite['slug'] ) ? $object->rewrite['slug'] : $name;
			$root         = strtok( trim( (string) $rewrite_slug, '/' ), '/' );

			if ( false !== $root && '' !== $root ) {
				$reserved[] = sanitize_title( $root );
			}
		}
	}

	if ( is_object( $wp_rewrite ) ) {
		foreach ( array( 'author_base', 'comments_base', 'feed_base', 'pagination_base', 'search_base' ) as $property ) {
			if ( ! empty( $wp_rewrite->{$property} ) ) {
				$reserved[] = sanitize_title( $wp_rewrite->{$property} );
			}
		}
	}

	if ( function_exists( 'get_pages' ) && function_exists( 'get_page_uri' ) ) {
		$pages = get_pages(
			array(
				'post_status'     => array( 'publish', 'private' ),
				'sort_column'     => 'post_name',
				'suppress_filters' => true,
			)
		);

		foreach ( $pages as $page ) {
			$page_path = trim( (string) get_page_uri( $page ), '/' );
			$parts     = array_values( array_filter( explode( '/', $page_path ) ) );

			if ( 1 < count( $parts ) ) {
				$reserved[] = sanitize_title( $parts[0] );
			}
		}
	}

	$reserved = array_values( array_unique( array_filter( array_map( 'sanitize_title', $reserved ) ) ) );

	return apply_filters( 'wp_seed_events_reserved_root_slugs', $reserved );
}

function wp_seed_events_event_type_slug_has_root_conflict( $slug, $reserved_roots = null ) {
	$slug           = sanitize_title( $slug );
	$reserved_roots = null === $reserved_roots ? wp_seed_events_reserved_root_slugs() : $reserved_roots;

	return '' !== $slug && in_array( $slug, $reserved_roots, true );
}

function wp_seed_events_event_type_key_has_root_conflict( $type_key ) {
	$slug = wp_seed_events_event_type_public_slug_for_key( $type_key );

	return wp_seed_events_event_type_slug_has_root_conflict( $slug );
}

function wp_seed_events_event_type_rewrite_slug_conflicts() {
	$conflicts      = array();
	$reserved_roots = wp_seed_events_reserved_root_slugs();

	foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) {
		$slug = wp_seed_events_event_type_public_slug( $type_key );

		if ( wp_seed_events_event_type_slug_has_root_conflict( $slug, $reserved_roots ) ) {
			$conflicts[ $slug ] = sanitize_key( $type_key );
		}
	}

	ksort( $conflicts, SORT_STRING );

	return $conflicts;
}

function wp_seed_events_permalink_path_parts( $post ) {
	$post = get_post( $post );

	if ( ! $post || 'wp_seed_event' !== $post->post_type ) {
		return array();
	}

	$parts  = array();
	$prefix = wp_seed_events_permalink_prefix();

	if ( '' !== $prefix ) {
		$parts[] = $prefix;
	}

	$primary_type = wp_seed_events_primary_type_for_event( $post->ID );

	if ( wp_seed_events_permalink_includes_primary_type() && '' !== $primary_type ) {
		$primary_slug = wp_seed_events_event_type_public_slug( $primary_type );

		if ( '' !== $primary_slug ) {
			$parts[] = $primary_slug;
		}
	}

	$parts[] = $post->post_name;

	return array_values( array_filter( $parts ) );
}

function wp_seed_events_event_post_type_link( $post_link, $post, $leavename, $sample ) {
	if ( ! $post instanceof WP_Post || 'wp_seed_event' !== $post->post_type ) {
		return $post_link;
	}

	$parts = wp_seed_events_permalink_path_parts( $post );

	if ( array() === $parts ) {
		return $post_link;
	}

	return home_url( user_trailingslashit( implode( '/', array_map( 'rawurlencode', $parts ) ) ) );
}

function wp_seed_events_add_event_rewrite_rules() {
	$prefix = wp_seed_events_permalink_prefix();

	if ( '' !== $prefix ) {
		$prefix_pattern = preg_quote( $prefix, '#' );
		add_rewrite_rule( '^' . $prefix_pattern . '/([^/]+)/([^/]+)/?$', 'index.php?post_type=wp_seed_event&name=$matches[2]&wp_seed_event_primary_type=$matches[1]', 'top' );
		add_rewrite_rule( '^' . $prefix_pattern . '/([^/]+)/?$', 'index.php?post_type=wp_seed_event&name=$matches[1]', 'top' );
		return;
	}

	if ( wp_seed_events_permalink_includes_primary_type() ) {
		foreach ( wp_seed_events_public_event_type_rewrite_slugs() as $type_slug => $type_key ) {
			add_rewrite_rule( '^' . preg_quote( $type_slug, '#' ) . '/([^/]+)/?$', 'index.php?post_type=wp_seed_event&name=$matches[1]&wp_seed_event_primary_type=' . rawurlencode( $type_key ), 'top' );
		}
	}

	add_rewrite_rule( '^([^/]+)/?$', 'index.php?post_type=wp_seed_event&name=$matches[1]', 'bottom' );
}

function wp_seed_events_permalink_query_vars( $query_vars ) {
	$query_vars[] = 'wp_seed_event_primary_type';

	return $query_vars;
}

function wp_seed_events_normalize_url_path( $url ) {
	$path = wp_parse_url( $url, PHP_URL_PATH );

	if ( null === $path || false === $path ) {
		return '';
	}

	$path = rawurldecode( (string) $path );
	$path = '/' . ltrim( $path, '/' );

	return untrailingslashit( $path );
}

function wp_seed_events_redirect_event_to_canonical_url() {
	if ( is_admin() || wp_doing_ajax() || is_preview() || ! is_singular( 'wp_seed_event' ) ) {
		return;
	}

	$event_id = get_queried_object_id();

	if ( ! $event_id ) {
		return;
	}

	$canonical_url = get_permalink( $event_id );

	if ( ! $canonical_url ) {
		return;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$request_url = home_url( $request_uri );

	if ( wp_seed_events_normalize_url_path( $request_url ) === wp_seed_events_normalize_url_path( $canonical_url ) ) {
		return;
	}

	wp_safe_redirect( $canonical_url, 301 );
	exit;
}

function wp_seed_events_register_permalink_settings() {
	add_settings_section(
		'wp_seed_events_permalink_section',
		'WP Seed Events',
		'wp_seed_events_render_permalink_section_intro',
		'permalink'
	);

	add_settings_field(
		'wp_seed_events_permalink_prefix',
		'Préfixe public des évènements',
		'wp_seed_events_render_permalink_prefix_field',
		'permalink',
		'wp_seed_events_permalink_section'
	);

	add_settings_field(
		'wp_seed_events_permalink_structure',
		'Structure des fiches évènement',
		'wp_seed_events_render_permalink_structure_field',
		'permalink',
		'wp_seed_events_permalink_section'
	);
}

function wp_seed_events_render_permalink_section_intro() {
	echo '<p>Ces réglages contrôlent les URL publiques des fiches évènement.</p>';
}

function wp_seed_events_render_permalink_prefix_field() {
	$prefix        = wp_seed_events_permalink_prefix();
	$stored_prefix = get_option( 'wp_seed_events_permalink_prefix', null );
	?>
	<input name="wp_seed_events_permalink_prefix" type="text" class="regular-text code" value="<?php echo esc_attr( $prefix ); ?>" placeholder="evenements" />
	<p class="description">Valeur effective : <code><?php echo '' === $prefix ? 'aucun préfixe' : esc_html( $prefix ); ?></code>. Par défaut : <code>evenements</code>.</p>
	<?php if ( null === $stored_prefix ) : ?>
		<p class="description">Le champ affiche actuellement la valeur par défaut. Enregistrez une valeur vide pour supprimer réellement le préfixe.</p>
	<?php endif; ?>
	<p class="description">Laisser vide supprime le préfixe, mais augmente les risques de conflit avec des pages existantes.</p>
	<?php
}

function wp_seed_events_render_permalink_structure_field() {
	$prefix         = wp_seed_events_permalink_prefix();
	$display_prefix = '' === $prefix ? '' : '/' . $prefix;
	$with_type      = wp_seed_events_permalink_includes_primary_type();
	?>
	<fieldset>
		<label>
			<input type="radio" name="wp_seed_events_permalink_include_primary_type" value="0" <?php checked( ! $with_type ); ?> />
			<code><?php echo esc_html( $display_prefix ); ?>/nom-de-l-evenement/</code>
		</label>
		<br />
		<label>
			<input type="radio" name="wp_seed_events_permalink_include_primary_type" value="1" <?php checked( $with_type ); ?> />
			<code><?php echo esc_html( $display_prefix ); ?>/type-principal/nom-de-l-evenement/</code>
		</label>
		<p class="description">Si aucun type principal n’est défini, l’URL utilise automatiquement la structure sans type.</p>
	</fieldset>
	<?php
}

function wp_seed_events_maybe_save_permalink_settings() {
	global $pagenow;

	if ( 'options-permalink.php' !== $pagenow || 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-permalink' ) ) {
		return;
	}

	$old_prefix    = wp_seed_events_permalink_prefix();
	$old_with_type = wp_seed_events_permalink_includes_primary_type();
	$raw_prefix    = isset( $_POST['wp_seed_events_permalink_prefix'] ) ? wp_unslash( $_POST['wp_seed_events_permalink_prefix'] ) : wp_seed_events_default_permalink_prefix();
	$new_prefix    = sanitize_title( trim( (string) $raw_prefix, "/ \t\n\r\0\x0B" ) );
	$new_with_type = ! empty( $_POST['wp_seed_events_permalink_include_primary_type'] );

	if ( '' === $new_prefix && $new_with_type && array() !== wp_seed_events_event_type_rewrite_slug_conflicts() ) {
		add_settings_error(
			'wp_seed_events_permalink_prefix',
			'wp_seed_events_permalink_prefix_root_conflict',
			'Le prefixe Events ne peut pas etre vide tant qu un type d evenement utilise une racine WordPress existante.',
			'error'
		);
		return;
	}

	if ( '' === $new_prefix ) {
		update_option( 'wp_seed_events_permalink_prefix', '', false );
	} elseif ( wp_seed_events_default_permalink_prefix() === $new_prefix ) {
		delete_option( 'wp_seed_events_permalink_prefix' );
	} else {
		update_option( 'wp_seed_events_permalink_prefix', $new_prefix, false );
	}

	if ( $new_with_type ) {
		delete_option( 'wp_seed_events_permalink_include_primary_type' );
	} else {
		update_option( 'wp_seed_events_permalink_include_primary_type', '0', false );
	}

	if ( $old_prefix !== $new_prefix || $old_with_type !== $new_with_type ) {
		flush_rewrite_rules( false );
	}
}

function wp_seed_events_event_type_public_slug( $type_key ) {
	$type_key = sanitize_key( $type_key );
	$options  = wp_seed_events_event_type_options();

	if ( ! isset( $options[ $type_key ] ) ) {
		return '';
	}

	return wp_seed_events_event_type_public_slug_for_key( $type_key );
}

function wp_seed_events_event_type_keys_for_event( $post_id ) {
	$event_types = get_post_meta( $post_id, '_wp_seed_event_types', true );

	if ( ! is_array( $event_types ) ) {
		$legacy_event_type = get_post_meta( $post_id, '_wp_seed_event_type', true );
		$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
	}

	$options = wp_seed_events_event_type_options();

	return array_values(
		array_filter(
			array_map( 'sanitize_key', $event_types ),
			function ( $type_key ) use ( $options ) {
				return isset( $options[ $type_key ] );
			}
		)
	);
}

function wp_seed_events_primary_type_for_event( $post_id ) {
	$selected_types = wp_seed_events_event_type_keys_for_event( $post_id );
	$default_type   = wp_seed_events_default_event_type_key();
	$primary_type   = sanitize_key( (string) get_post_meta( $post_id, '_wp_seed_event_primary_type', true ) );

	if ( '' !== $primary_type && $default_type !== $primary_type && in_array( $primary_type, $selected_types, true ) ) {
		return $primary_type;
	}

	$public_types = array_values(
		array_filter(
			$selected_types,
			function ( $type_key ) use ( $default_type ) {
				return $default_type !== $type_key;
			}
		)
	);

	return 1 === count( $public_types ) ? $public_types[0] : '';
}

function wp_seed_events_permalink_example_url() {
	$prefix    = wp_seed_events_permalink_prefix();
	$with_type = wp_seed_events_permalink_includes_primary_type();
	$parts     = array();

	if ( '' !== $prefix ) {
		$parts[] = $prefix;
	}

	if ( $with_type ) {
		$parts[] = 'atelier';
	}

	$parts[] = 'nom-de-l-evenement';

	return home_url( user_trailingslashit( implode( '/', $parts ) ) );
}
function wp_seed_events_register_plugin_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=wp_seed_event',
		'Tous les types d’évènements',
		'Tous les types d’évènements',
		'edit_posts',
		'wp-seed-event-types',
		'wp_seed_events_render_event_types_admin_page',
		20
	);

	add_submenu_page(
		'edit.php?post_type=wp_seed_event',
		'Toutes les personnes',
		'Toutes les personnes',
		'edit_posts',
		'wp-seed-event-people',
		'wp_seed_events_render_people_admin_page',
		30
	);

	add_submenu_page(
		'edit.php?post_type=wp_seed_event',
		'Tous les types de personnes',
		'Tous les types de personnes',
		'edit_posts',
		'wp-seed-person-types',
		'wp_seed_events_render_person_types_admin_page',
		35
	);

	add_submenu_page(
		'edit.php?post_type=wp_seed_event',
		'Tous les lieux d’évènements',
		'Tous les lieux d’évènements',
		'edit_posts',
		'wp-seed-event-places',
		'wp_seed_events_render_places_admin_page',
		40
	);

	add_menu_page(
		'WP Seed Events',
		'WP Seed Events',
		'manage_options',
		'wp-seed-events-admin',
		'wp_seed_events_render_settings_page',
		'dashicons-screenoptions',
		56
	);

	add_submenu_page(
		'wp-seed-events-admin',
		'Paramètres',
		'Paramètres',
		'manage_options',
		'wp-seed-events-admin',
		'wp_seed_events_render_settings_page'
	);

	add_submenu_page(
		'wp-seed-events-admin',
		'Affichage',
		'Affichage',
		'manage_options',
		'wp-seed-events-display',
		'wp_seed_events_render_display_page'
	);
}

function wp_seed_events_event_admin_columns( $columns ) {
	$new_columns = array();

	if ( isset( $columns['cb'] ) ) {
		$new_columns['cb'] = $columns['cb'];
	}

	$new_columns['wp_seed_event_flyer'] = 'Flyer recto';
	$new_columns['title'] = $columns['title'] ?? 'Titre';
	$new_columns['wp_seed_event_types'] = 'Type(s)';
	$new_columns['wp_seed_event_next_date'] = 'Dates';
	$new_columns['wp_seed_event_place'] = 'Lieu';
	$new_columns['wp_seed_event_status'] = 'Publication';

	if ( isset( $columns['date'] ) ) {
		$new_columns['date'] = $columns['date'];
	}

	return $new_columns;
}

function wp_seed_events_event_admin_column_styles() {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'edit-wp_seed_event' !== $screen->id ) {
		return;
	}
	?>
	<style>
		.fixed .column-wp_seed_event_flyer {
			width: 76px;
		}

		.column-wp_seed_event_flyer img {
			display: block;
			width: 60px;
			height: 60px;
			object-fit: cover;
		}
	</style>
	<?php
}

function wp_seed_events_event_admin_sortable_columns( $columns ) {
	$columns['wp_seed_event_next_date'] = 'wp_seed_event_next_date';

	return $columns;
}

function wp_seed_events_sort_event_admin_by_next_date( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'wp_seed_event' !== $query->get( 'post_type' ) || 'wp_seed_event_next_date' !== $query->get( 'orderby' ) ) {
		return;
	}

	$query->set( 'orderby', 'wp_seed_next_occurrence' );
	$query->set( 'wp_seed_next_occurrence_missing', 'last' );
}

function wp_seed_events_prefix_pinned_event_admin_title( $title, $post_id ) {
	if ( ! is_admin() || 'wp_seed_event' !== get_post_type( $post_id ) ) {
		return $title;
	}

	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'edit-wp_seed_event' !== $screen->id ) {
		return $title;
	}

	if ( '1' !== get_post_meta( $post_id, '_wp_seed_event_pinned', true ) ) {
		return $title;
	}

	return '📌 ' . $title;
}
function wp_seed_events_render_event_admin_column( $column_name, $post_id ) {
	if ( 'wp_seed_event_flyer' === $column_name ) {
		$event_media          = wp_seed_events_get_event_media( $post_id );
		$communication_visual = isset( $event_media['communication_visual'] ) && is_array( $event_media['communication_visual'] )
			? $event_media['communication_visual']
			: null;
		$attachment_id        = $communication_visual ? absint( $communication_visual['id'] ?? 0 ) : 0;
		$attached_file        = $attachment_id ? get_attached_file( $attachment_id ) : false;
		$image                 = '';

		if ( $attachment_id && $attached_file && is_readable( $attached_file ) ) {
			$alt = trim( (string) ( $communication_visual['alt'] ?? '' ) );

			if ( '' === $alt ) {
				$alt = trim( (string) get_post_field( 'post_title', $post_id ) );
			}

			if ( '' === $alt ) {
				$alt = __( 'Visuel de l’événement', 'wp-seed-events' );
			}

			$image = wp_get_attachment_image(
				$attachment_id,
				'thumbnail',
				false,
				array(
					'alt'     => $alt,
					'loading' => 'lazy',
				)
			);
		}

		if ( '' === $image ) {
			echo '<span aria-hidden="true">&mdash;</span><span class="screen-reader-text">' . esc_html__( 'Aucun visuel', 'wp-seed-events' ) . '</span>';
			return;
		}

		$edit_link = get_edit_post_link( $post_id, 'raw' );

		if ( $edit_link ) {
			$post_title = trim( (string) get_post_field( 'post_title', $post_id ) );
			$link_label = '' !== $post_title
				? sprintf( __( 'Modifier l’événement « %s »', 'wp-seed-events' ), $post_title )
				: __( 'Modifier cet événement', 'wp-seed-events' );

			echo '<a href="' . esc_url( $edit_link ) . '" aria-label="' . esc_attr( $link_label ) . '">' . wp_kses_post( $image ) . '</a>';
			return;
		}

		echo wp_kses_post( $image );
		return;
	}
	if ( 'wp_seed_event_types' === $column_name ) {
		$type_labels = wp_seed_events_event_type_labels_for_event( $post_id );
		echo array() === $type_labels ? '—' : esc_html( implode( ' • ', $type_labels ) );
		return;
	}

	if ( 'wp_seed_event_next_date' === $column_name ) {
		echo wp_kses_post( wp_seed_events_format_event_admin_dates( $post_id ) );
		return;
	}

	if ( 'wp_seed_event_place' === $column_name ) {
		$place_name = wp_seed_events_place_name_for_event( $post_id );
		echo '' === $place_name ? '—' : esc_html( $place_name );
		return;
	}

	if ( 'wp_seed_event_status' === $column_name ) {
		$status = get_post_status_object( get_post_status( $post_id ) );
		echo $status ? esc_html( $status->label ) : '—';
	}
}

function wp_seed_events_format_event_admin_dates( $post_id ) {
	$lifecycle = wp_seed_events_get_event_lifecycle( $post_id );

	if ( 'undated' === $lifecycle ) {
		return esc_html__( 'Sans date', 'wp-seed-events' );
	}

	if ( 'cancelled_only' === $lifecycle ) {
		return esc_html__( 'Annulé', 'wp-seed-events' );
	}

	$reference_occurrence = 'upcoming' === $lifecycle
		? wp_seed_events_get_next_active_occurrence( $post_id )
		: wp_seed_events_get_last_active_occurrence( $post_id );

	if ( array() === $reference_occurrence ) {
		return '—';
	}

	$active_occurrences = wp_seed_events_get_event_occurrences(
		$post_id,
		array(
			'include_cancelled' => false,
			'only_active'       => true,
			'status'            => 'all',
		)
	);
	$active_count       = count( $active_occurrences );
	$start_date         = $reference_occurrence['start_date'] ?? '';
	$timestamp          = '' !== $start_date ? strtotime( $start_date . ' 12:00:00' ) : false;
	$date_line          = false === $timestamp ? $start_date : date_i18n( 'd/m/Y', $timestamp );
	$details            = array( 'upcoming' === $lifecycle ? __( 'À venir', 'wp-seed-events' ) : __( 'Passé', 'wp-seed-events' ) );
	$time_label         = ! empty( $reference_occurrence['all_day'] )
		? __( 'Journée entière', 'wp-seed-events' )
		: trim( (string) ( $reference_occurrence['time_label'] ?? '' ) );

	if ( '' !== $time_label ) {
		$details[] = $time_label;
	}

	if ( $active_count > 1 ) {
		$details[] = sprintf(
			_n( '%s occurrence', '%s occurrences', $active_count, 'wp-seed-events' ),
			number_format_i18n( $active_count )
		);
	}

	return esc_html( $date_line ) . '<br /><span class="description">' . esc_html( implode( ' · ', $details ) ) . '</span>';
}

function wp_seed_events_event_type_labels_for_event( $post_id ) {
	$event_types = get_post_meta( $post_id, '_wp_seed_event_types', true );

	if ( ! is_array( $event_types ) ) {
		$legacy_event_type = get_post_meta( $post_id, '_wp_seed_event_type', true );
		$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
	}

	$options = wp_seed_events_event_type_options();
	$labels  = array();

	foreach ( $event_types as $event_type ) {
		$event_type = sanitize_key( $event_type );

		if ( isset( $options[ $event_type ] ) ) {
			$labels[] = $options[ $event_type ];
		}
	}

	return $labels;
}

function wp_seed_events_format_admin_next_date( $occurrence ) {
	$start_date = $occurrence['start_date'] ?? '';

	if ( '' === $start_date ) {
		return '—';
	}

	$timestamp = strtotime( $start_date . ' 12:00:00' );
	$date_line = false === $timestamp ? $start_date : date_i18n( 'd/m/Y', $timestamp );
	$time_line = wp_seed_events_format_occurrence_time_line( $occurrence );

	if ( '' === $time_line ) {
		return esc_html( $date_line );
	}

	return esc_html( $date_line ) . '<br />' . esc_html( $time_line );
}

function wp_seed_events_next_occurrence_sort_value( $occurrences ) {
	if ( ! is_array( $occurrences ) ) {
		return '';
	}

	$valid_occurrences = array_filter(
		$occurrences,
		function ( $occurrence ) {
			return is_array( $occurrence )
				&& ! empty( $occurrence['start_date'] )
				&& empty( $occurrence['cancelled'] )
				&& empty( $occurrence['is_cancelled'] );
		}
	);

	if ( array() === $valid_occurrences ) {
		return '';
	}

	$valid_occurrences = wp_seed_events_sort_occurrences_for_display( $valid_occurrences );
	$now               = current_time( 'Y-m-d H:i' );

	foreach ( $valid_occurrences as $occurrence ) {
		$sort_value = wp_seed_events_occurrence_sort_value( $occurrence );

		if ( $sort_value >= $now ) {
			return $sort_value;
		}
	}

	return '';
}
function wp_seed_events_initialize_missing_next_occurrence_sort_meta() {
	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_wp_seed_event_next_occurrence_sort',
					'compare' => 'NOT EXISTS',
				),
			),
		)
	);

	foreach ( $event_ids as $event_id ) {
		$occurrences = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );
		$sort_value  = wp_seed_events_next_occurrence_sort_value( $occurrences );

		if ( '' !== $sort_value ) {
			update_post_meta( $event_id, '_wp_seed_event_next_occurrence_sort', $sort_value );
		}
	}
}
function wp_seed_events_next_occurrence_for_event( $post_id ) {
	$occurrences = get_post_meta( $post_id, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $occurrences ) ) {
		return array();
	}

	$valid_occurrences = array_filter(
		$occurrences,
		function ( $occurrence ) {
			return is_array( $occurrence )
				&& ! empty( $occurrence['start_date'] )
				&& empty( $occurrence['cancelled'] )
				&& empty( $occurrence['is_cancelled'] );
		}
	);

	if ( array() === $valid_occurrences ) {
		return array();
	}

	$valid_occurrences = wp_seed_events_sort_occurrences_for_display( $valid_occurrences );
	$now               = current_time( 'Y-m-d H:i' );

	foreach ( $valid_occurrences as $occurrence ) {
		if ( wp_seed_events_occurrence_sort_value( $occurrence ) >= $now ) {
			return $occurrence;
		}
	}

	return array();
}

function wp_seed_events_place_name_for_event( $post_id ) {
	$place_id = (int) get_post_meta( $post_id, '_wp_seed_event_place_id', true );

	if ( 0 === $place_id ) {
		return '';
	}

	$place = get_post( $place_id );

	if ( ! $place || 'wp_seed_place' !== $place->post_type ) {
		return '';
	}

	return get_the_title( $place );
}

function wp_seed_events_event_render_mode() {
	$mode = get_option( 'wp_seed_events_event_render_mode', 'theme' );

	return 'full_model' === $mode ? 'full_model' : 'theme';
}

function wp_seed_events_use_full_model_template() {
	return 'full_model' === wp_seed_events_event_render_mode();
}

function wp_seed_events_public_template_include( $template ) {
	if ( ! is_singular( 'wp_seed_event' ) || ! wp_seed_events_use_full_model_template() ) {
		return $template;
	}

	$event_template = __DIR__ . '/templates/single-wp-seed-event.php';

	return file_exists( $event_template ) ? $event_template : $template;
}

function wp_seed_events_public_body_class( $classes ) {
	if ( is_singular( 'wp_seed_event' ) && wp_seed_events_use_full_model_template() ) {
		$classes[] = 'wp-seed-events-template-full-model';
	}

	return $classes;
}

function wp_seed_events_render_public_event_content( $content ) {
	static $rendering = false;

	if ( $rendering || ! is_singular( 'wp_seed_event' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$rendering = true;
	$output    = wp_seed_events_render_public_event_single( get_the_ID(), true );
	$rendering = false;

	return '' === $output ? $content : $output;
}

function wp_seed_events_event_card_shortcode( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => 0,
		),
		$atts,
		'wp_seed_event_card'
	);

	$post_id = wp_seed_events_public_shortcode_event_id( $atts['id'] );

	return wp_seed_events_render_event_card( $post_id );
}

function wp_seed_events_render_event_card( $post_id ) {
	return wp_seed_events_render_public_event_card( $post_id );
}

function wp_seed_events_render_settings_page() {
	$template_page_id = wp_seed_events_event_template_page_id();
	$render_mode      = wp_seed_events_event_render_mode();
	$contact_roles    = wp_seed_events_contact_roles();
	$default_roles    = wp_seed_events_default_contact_roles();
	?>
	<div class="wrap">
		<h1>WP Seed Events - Paramètres</h1>

		<?php if ( isset( $_GET['message'] ) && 'saved' === sanitize_key( wp_unslash( $_GET['message'] ) ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Réglages enregistrés.</p></div>
		<?php endif; ?>

		<div class="card" style="max-width: 760px;">
			<h2>URL publiques des évènements</h2>
			<p>Les URL des fiches évènement se règlent dans <strong>Réglages &gt; Permaliens</strong>.</p>
			<p>Exemple actuel : <code><?php echo esc_html( wp_seed_events_permalink_example_url() ); ?></code></p>
			<p>Un changement d’URL peut nécessiter une régénération des permaliens. Si des fiches sont déjà indexées, prévoyez des redirections.</p>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'options-permalink.php' ) ); ?>">Modifier les permaliens</a></p>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="wp_seed_events_save_display_settings" />
			<?php wp_nonce_field( 'wp_seed_events_save_display_settings', 'wp_seed_events_display_settings_nonce' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="wp-seed-event-template-page-id">Page modèle d’un événement</label></th>
						<td>
							<?php
							wp_dropdown_pages(
								array(
									'name'              => 'wp_seed_event_template_page_id',
									'id'                => 'wp-seed-event-template-page-id',
									'selected'          => $template_page_id,
									'show_option_none'  => 'Aucune page modèle',
									'option_none_value' => 0,
									'post_status'       => array( 'publish', 'private', 'draft' ),
								)
							);
							?>
							<p class="description">Cette page peut contenir des shortcodes WP Seed Events. Elle sera utilisée comme modèle pour les fiches publiques d’événements.</p>
							<?php if ( 0 < $template_page_id ) : ?>
								<?php
								$template_page_title = get_the_title( $template_page_id );
								$template_status     = get_post_status( $template_page_id );
								$template_view_url   = 'draft' === $template_status ? get_preview_post_link( $template_page_id ) : get_permalink( $template_page_id );
								$template_edit_url   = get_edit_post_link( $template_page_id, '' );
								?>
								<p class="description">
									Page modèle actuelle : <strong><?php echo esc_html( $template_page_title ); ?></strong>
									<?php if ( $template_view_url ) : ?>
										<br /><a href="<?php echo esc_url( $template_view_url ); ?>" target="_blank" rel="noopener">Voir la page</a>
									<?php endif; ?>
									<?php if ( $template_edit_url ) : ?>
										 · <a href="<?php echo esc_url( $template_edit_url ); ?>">Modifier la page</a>
									<?php endif; ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">Rendu des fiches événement</th>
						<td>
							<fieldset>
								<label>
									<input type="radio" name="wp_seed_events_event_render_mode" value="theme" <?php checked( 'theme', $render_mode ); ?> />
									Utiliser le template du thème
								</label>
								<br />
								<label>
									<input type="radio" name="wp_seed_events_event_render_mode" value="full_model" <?php checked( 'full_model', $render_mode ); ?> />
									Utiliser la page modèle comme page complète
								</label>
								<div class="description" style="margin-top:8px;">
									<p><strong>Template du thème :</strong> conserve le template single du thème, avec son enveloppe article normale. Le thème peut afficher le titre, les métadonnées, la barre latérale ou les éléments prévus par le template. WP Seed Events insère alors le contenu de l’événement dans ce cadre.</p>
									<p><strong>Page modèle comme page complète :</strong> garde l’en-tête et le pied de page du thème, puis utilise la page modèle comme contenu principal. Ce mode évite l’enveloppe article, les métadonnées et la barre latérale du template single. Il est utile pour composer une fiche événement avec Divi, Gutenberg, Spectra ou le thème.</p>
								</div>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row">Rôles par défaut des nouvelles personnes</th>
						<td>
							<fieldset>
								<?php foreach ( $contact_roles as $role_key => $role_label ) : ?>
									<label>
										<input type="checkbox" name="wp_seed_events_default_contact_roles[]" value="<?php echo esc_attr( $role_key ); ?>" <?php checked( in_array( $role_key, $default_roles, true ) ); ?> />
										<?php echo esc_html( $role_label ); ?>
									</label><br />
								<?php endforeach; ?>
								<p class="description">Ces rôles sont cochés automatiquement uniquement lorsqu’une nouvelle personne est ajoutée à un événement.</p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<?php submit_button( 'Enregistrer' ); ?>
		</form>

		<?php wp_seed_events_render_lifecycle_index_backfill_panel(); ?>
	</div>
	<?php
}

function wp_seed_events_render_display_page() {
	$template_shortcodes = array(
		array(
			'name'        => 'Fiche complète',
			'description' => 'Affiche la fiche complète de l’événement courant.',
			'example'     => '[wp_seed_event]',
			'options'     => 'Aucune option spécifique.',
		),
		array(
			'name'        => 'Champ isolé',
			'description' => 'Affiche une seule information de l’événement courant. next_date et next_time concernent uniquement la prochaine occurrence active ; display_date et display_time utilisent la date de référence.',
			'example'     => '[wp_seed_event_field field="next_date"]',
			'options'     => 'field : title, url, types, next_date, next_time, display_date, display_time, place, place_address, place_link, phone, email, person_link, description, excerpt, image, flyer.',
		),
		array(
			'name'        => 'Dates',
			'description' => 'Affiche les dates de l’événement courant avec le renderer public partagé.',
			'example'     => '[wp_seed_event_dates mode="next"]',
			'options'     => 'title : Dates par défaut, chaîne vide autorisée. heading_level : h2 à h6. mode/scope : next+upcoming = Prochaine date ; first ou last avec la période choisie ; all+upcoming = Toutes les prochaines dates ; all+past = Toutes les dates passées ; all+all = Toutes les dates. format : long ou short. show_cancelled, show_times et show_calendar_links : yes ou no. Compatibilité : show_time yes/no.',
		),
		array(
			'name'        => 'Personnes',
			'description' => 'Affiche les personnes liées à l’événement courant.',
			'example'     => '[wp_seed_event_people roles="organizer,speaker"]',
			'options'     => 'roles : all ou liste séparée par des virgules parmi organizer, speaker, registration_contact et information_contact. role reste compatible. show_name, show_roles, show_phone, show_email, show_link, link_phone, link_email et link_url : yes ou no. details : yes ou no. layout : list ou grid.',
		),
		array(
			'name'        => 'Lieu',
			'description' => 'Affiche le lieu de l’événement courant.',
			'example'     => '[wp_seed_event_place]',
			'options'     => 'Aucune option spécifique.',
		),
		array(
			'name'        => 'Informations pratiques',
			'description' => 'Affiche les informations complémentaires propres à cet événement.',
			'example'     => '[wp_seed_event_practical_info]',
			'options'     => 'Aucune option spécifique.',
		),
	);

	$advanced_shortcodes = array(
		array(
			'name'        => 'Carte d’un événement précis',
			'description' => 'Affiche une carte pour un événement choisi sur une page classique.',
			'example'     => '[wp_seed_event_card id="123"]',
			'options'     => 'id : remplacez 123 par l’identifiant réel de l’événement.',
		),
		array(
			'name'        => 'Champ d’un événement précis',
			'description' => 'Affiche une information d’un événement choisi sur une page classique.',
			'example'     => '[wp_seed_event_field id="123" field="next_date"]',
			'options'     => 'id : remplacez 123 par l’identifiant réel. field : voir la liste des champs ci-dessus.',
		),
		array(
			'name'        => 'Dates d’un événement précis',
			'description' => 'Affiche les dates d’un événement choisi sur une page classique.',
			'example'     => '[wp_seed_event_dates id="123" heading_level="h3"]',
			'options'     => 'id : remplacez 123 par l’identifiant réel. Les autres options sont identiques au shortcode Dates.',
		),
	);
	?>
	<div class="wrap">
		<h1>WP Seed Events - Affichage</h1>
		<p>La page modèle d’un événement peut être construite avec Divi, Gutenberg, Spectra ou le thème actif.</p>
		<p><strong>Sur une fiche événement, l’événement courant est détecté automatiquement. N’ajoutez pas d’id dans la page modèle.</strong></p>
		<p>Sur une page classique, ajoutez <code>id="123"</code> en remplaçant <code>123</code> par l’identifiant réel de l’événement.</p>

		<h2>Composer une fiche événement</h2>
		<?php wp_seed_events_render_shortcode_help_table( $template_shortcodes ); ?>

		<h2>Afficher un événement précis ailleurs</h2>
		<?php wp_seed_events_render_shortcode_help_table( $advanced_shortcodes ); ?>
	</div>

	<script>
	document.addEventListener('click', function(event) {
		var button = event.target.closest('[data-wp-seed-copy-shortcode]');

		if (!button) {
			return;
		}

		var shortcode = button.getAttribute('data-wp-seed-copy-shortcode') || '';

		if (!shortcode) {
			return;
		}

		var markCopied = function() {
			var label = button.textContent;
			button.textContent = 'Copié';
			window.setTimeout(function() {
				button.textContent = label;
			}, 1500);
		};

		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(shortcode).then(markCopied);
			return;
		}

		var textarea = document.createElement('textarea');
		textarea.value = shortcode;
		textarea.setAttribute('readonly', 'readonly');
		textarea.style.position = 'absolute';
		textarea.style.left = '-9999px';
		document.body.appendChild(textarea);
		textarea.select();
		document.execCommand('copy');
		document.body.removeChild(textarea);
		markCopied();
	});
	</script>
	<?php
}

function wp_seed_events_render_shortcode_help_table( $shortcodes ) {
	?>
	<table class="widefat striped" style="max-width: 1100px; margin-bottom: 24px;">
		<thead>
			<tr>
				<th scope="col">Shortcode</th>
				<th scope="col">Description</th>
				<th scope="col">Exemple</th>
				<th scope="col">Options</th>
				<th scope="col">Action</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $shortcodes as $shortcode ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $shortcode['name'] ); ?></strong></td>
					<td><?php echo esc_html( $shortcode['description'] ); ?></td>
					<td><code><?php echo esc_html( $shortcode['example'] ); ?></code></td>
					<td><?php echo esc_html( $shortcode['options'] ); ?></td>
					<td><button type="button" class="button" data-wp-seed-copy-shortcode="<?php echo esc_attr( $shortcode['example'] ); ?>">Copier</button></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php
}

function wp_seed_events_handle_display_settings_form() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Vous n’avez pas les droits suffisants pour modifier ces réglages.', 'wp-seed-events' ) );
	}

	if (
		! isset( $_POST['wp_seed_events_display_settings_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_seed_events_display_settings_nonce'] ) ), 'wp_seed_events_save_display_settings' )
	) {
		wp_die( esc_html__( 'La vérification de sécurité a échoué.', 'wp-seed-events' ) );
	}

	$template_page_id = isset( $_POST['wp_seed_event_template_page_id'] ) ? absint( $_POST['wp_seed_event_template_page_id'] ) : 0;
	$render_mode      = isset( $_POST['wp_seed_events_event_render_mode'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_events_event_render_mode'] ) ) : 'theme';
	$contact_roles    = wp_seed_events_contact_roles();
	$default_roles    = isset( $_POST['wp_seed_events_default_contact_roles'] ) && is_array( $_POST['wp_seed_events_default_contact_roles'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['wp_seed_events_default_contact_roles'] ) ) : array();

	if ( 0 === $template_page_id || 'page' !== get_post_type( $template_page_id ) ) {
		delete_option( 'wp_seed_events_event_template_page_id' );
	} else {
		update_option( 'wp_seed_events_event_template_page_id', $template_page_id, false );
	}

	if ( 'full_model' === $render_mode ) {
		update_option( 'wp_seed_events_event_render_mode', 'full_model', false );
	} else {
		delete_option( 'wp_seed_events_event_render_mode' );
	}

	$default_roles = array_map( 'wp_seed_events_canonical_contact_role', $default_roles );
	$default_roles = array_values(
		array_filter(
			array_unique( $default_roles ),
			function ( $role_key ) use ( $contact_roles ) {
				return isset( $contact_roles[ $role_key ] );
			}
		)
	);

	if ( array() === $default_roles ) {
		delete_option( 'wp_seed_events_default_contact_roles' );
	} else {
		update_option( 'wp_seed_events_default_contact_roles', $default_roles, false );
	}

	wp_safe_redirect( admin_url( 'admin.php?page=wp-seed-events-admin&message=saved' ) );
	exit;
}

function wp_seed_events_render_media_before_description( $post ) {
	if ( ! $post || 'wp_seed_event' !== $post->post_type ) {
		return;
	}

	$event_media = wp_seed_events_get_event_media( $post->ID );
	?>
	<div class="postbox" id="wp_seed_events_media">
		<div class="postbox-header">
			<h2 class="hndle">Visuels de communication</h2>
			<div class="handle-actions hide-if-no-js">
				<button type="button" class="handlediv" aria-expanded="true">
					<span class="screen-reader-text">Afficher ou masquer les visuels de communication</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
		</div>
		<div class="inside">
			<?php wp_seed_events_render_media_meta_box( $post, $event_media ); ?>
		</div>
	</div>
	<div class="postbox" id="wp_seed_events_document">
		<div class="postbox-header">
			<h2 class="hndle">Document complémentaire</h2>
			<div class="handle-actions hide-if-no-js">
				<button type="button" class="handlediv" aria-expanded="true">
					<span class="screen-reader-text">Afficher ou masquer le document complémentaire</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
		</div>
		<div class="inside">
			<?php wp_seed_events_render_media_document_panel( $event_media ); ?>
		</div>
	</div>
	<?php
}

function wp_seed_events_add_event_type_meta_box() {
	add_meta_box(
		'wp_seed_events_event_type',
		'Type(s) d’évènement',
		'wp_seed_events_render_event_type_box',
		'wp_seed_event',
		'normal',
		'high'
	);
}

function wp_seed_events_add_description_meta_box() {
	add_meta_box(
		'wp_seed_events_description',
		'Description',
		'wp_seed_events_render_description_meta_box',
		'wp_seed_event',
		'normal',
		'low'
	);
}

function wp_seed_events_render_description_meta_box( $post ) {
	wp_editor(
		$post->post_content,
		'content',
		array(
			'textarea_name' => 'content',
			'textarea_rows' => 8,
			'media_buttons' => false,
		)
	);

	wp_seed_events_render_short_description_field( $post );
}

function wp_seed_events_default_event_type_options() {
	return array(
		'non_classe' => 'Non classé',
	);
}

function wp_seed_events_legacy_event_type_options() {
	return array(
		'atelier'             => 'Atelier',
		'stage'               => 'Stage',
		'journee_decouverte'  => 'Journée découverte',
		'reunion_information' => 'Réunion d’information',
	);
}
function wp_seed_events_custom_event_type_options() {
	$custom_types = get_option( 'wp_seed_events_custom_event_types', array() );

	if ( ! is_array( $custom_types ) ) {
		return array();
	}

	$clean_types = array();

	foreach ( $custom_types as $type_key => $type_label ) {
		$type_key   = sanitize_key( $type_key );
		$type_label = sanitize_text_field( $type_label );

		if ( '' !== $type_key && '' !== $type_label ) {
			$clean_types[ $type_key ] = $type_label;
		}
	}

	return $clean_types;
}

function wp_seed_events_event_type_label_overrides() {
	$label_overrides = get_option( 'wp_seed_events_event_type_label_overrides', array() );

	if ( ! is_array( $label_overrides ) ) {
		return array();
	}

	$clean_overrides = array();

	foreach ( $label_overrides as $type_key => $type_label ) {
		$type_key   = sanitize_key( $type_key );
		$type_label = sanitize_text_field( $type_label );

		if ( '' !== $type_key && '' !== $type_label ) {
			$clean_overrides[ $type_key ] = $type_label;
		}
	}

	return $clean_overrides;
}

function wp_seed_events_removed_default_event_type_keys() {
	$removed_type_keys = get_option( 'wp_seed_events_removed_default_event_type_keys', array() );

	if ( ! is_array( $removed_type_keys ) ) {
		return array();
	}

	return array_values( array_unique( array_map( 'sanitize_key', $removed_type_keys ) ) );
}
function wp_seed_events_event_type_options() {
	$default_options    = wp_seed_events_default_event_type_options();
	$legacy_options     = wp_seed_events_legacy_event_type_options();
	$label_overrides    = wp_seed_events_event_type_label_overrides();
	$removed_type_keys  = wp_seed_events_removed_default_event_type_keys();
	$available_defaults = array();

	foreach ( $default_options as $type_key => $type_label ) {
		$available_defaults[ $type_key ] = $label_overrides[ $type_key ] ?? $type_label;
	}

	foreach ( $legacy_options as $type_key => $type_label ) {
		if ( in_array( $type_key, $removed_type_keys, true ) ) {
			continue;
		}

		if ( isset( $label_overrides[ $type_key ] ) || 0 < wp_seed_events_event_type_usage_count( $type_key ) ) {
			$available_defaults[ $type_key ] = $label_overrides[ $type_key ] ?? $type_label;
		}
	}

	return array_merge( $available_defaults, wp_seed_events_custom_event_type_options() );
}
function wp_seed_events_default_event_type_key() {
	return 'non_classe';
}

function wp_seed_events_normalized_event_type_label( $type_label ) {
	return sanitize_title( sanitize_text_field( $type_label ) );
}

function wp_seed_events_event_type_key_from_label( $type_label, $existing_options ) {
	$normalized_label = wp_seed_events_normalized_event_type_label( $type_label );

	if ( '' === $normalized_label ) {
		return '';
	}

	foreach ( $existing_options as $existing_type_key => $existing_type_label ) {
		if ( $normalized_label === wp_seed_events_normalized_event_type_label( $existing_type_label ) ) {
			return sanitize_key( $existing_type_key );
		}
	}

	$base_key = sanitize_key( sanitize_title( $type_label ) );

	if ( '' === $base_key ) {
		return '';
	}

	$type_key = $base_key;
	$index    = 2;

	while ( isset( $existing_options[ $type_key ] ) ) {
		$type_key = $base_key . '-' . $index;
		$index++;
	}

	return $type_key;
}

function wp_seed_events_event_ids_for_type( $type_key ) {
	$type_key = sanitize_key( $type_key );

	if ( '' === $type_key ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_seed_event_types',
					'value'   => '"' . $type_key . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => '_wp_seed_event_type',
					'value'   => $type_key,
					'compare' => '=',
				),
			),
		)
	);
}

function wp_seed_events_event_type_usage_count( $type_key ) {
	return count( wp_seed_events_event_ids_for_type( $type_key ) );
}

function wp_seed_events_reclassify_event_type_to_default( $type_key ) {
	$type_key         = sanitize_key( $type_key );
	$default_type_key = wp_seed_events_default_event_type_key();

	if ( '' === $type_key || $default_type_key === $type_key ) {
		return;
	}

	foreach ( wp_seed_events_event_ids_for_type( $type_key ) as $event_id ) {
		$event_types = get_post_meta( $event_id, '_wp_seed_event_types', true );

		if ( ! is_array( $event_types ) ) {
			$legacy_event_type = get_post_meta( $event_id, '_wp_seed_event_type', true );
			$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
		}

		$event_types = array_values(
			array_filter(
				array_map( 'sanitize_key', $event_types ),
				function ( $event_type_key ) use ( $type_key ) {
					return '' !== $event_type_key && $type_key !== $event_type_key;
				}
			)
		);

		if ( ! in_array( $default_type_key, $event_types, true ) ) {
			$event_types[] = $default_type_key;
		}

		update_post_meta( $event_id, '_wp_seed_event_types', array_values( array_unique( $event_types ) ) );

		if ( $type_key === get_post_meta( $event_id, '_wp_seed_event_primary_type', true ) ) {
			delete_post_meta( $event_id, '_wp_seed_event_primary_type' );
		}

		delete_post_meta( $event_id, '_wp_seed_event_type' );

		wp_seed_events_update_lifecycle_index( $event_id );
	}
}
function wp_seed_events_remap_event_type_key( $from_type_key, $to_type_key ) {
	$from_type_key = sanitize_key( $from_type_key );
	$to_type_key   = sanitize_key( $to_type_key );

	if ( '' === $from_type_key || '' === $to_type_key || $from_type_key === $to_type_key ) {
		return;
	}

	foreach ( wp_seed_events_event_ids_for_type( $from_type_key ) as $event_id ) {
		$event_types = get_post_meta( $event_id, '_wp_seed_event_types', true );

		if ( ! is_array( $event_types ) ) {
			$legacy_event_type = get_post_meta( $event_id, '_wp_seed_event_type', true );
			$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
		}

		$event_types = array_map(
			function ( $event_type_key ) use ( $from_type_key, $to_type_key ) {
				$event_type_key = sanitize_key( $event_type_key );

				return $from_type_key === $event_type_key ? $to_type_key : $event_type_key;
			},
			$event_types
		);
		$event_types = array_values( array_unique( array_filter( $event_types ) ) );

		if ( array() !== $event_types ) {
			update_post_meta( $event_id, '_wp_seed_event_types', $event_types );
		} else {
			delete_post_meta( $event_id, '_wp_seed_event_types' );
		}

		if ( $from_type_key === get_post_meta( $event_id, '_wp_seed_event_primary_type', true ) ) {
			if ( wp_seed_events_default_event_type_key() === $to_type_key ) {
				delete_post_meta( $event_id, '_wp_seed_event_primary_type' );
			} else {
				update_post_meta( $event_id, '_wp_seed_event_primary_type', $to_type_key );
			}
		}

		if ( $from_type_key === get_post_meta( $event_id, '_wp_seed_event_type', true ) ) {
			update_post_meta( $event_id, '_wp_seed_event_type', $to_type_key );
		}

		wp_seed_events_update_lifecycle_index( $event_id );
	}
}

function wp_seed_events_cleanup_duplicate_custom_event_types() {
	if ( ! is_admin() || ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	$default_options    = wp_seed_events_default_event_type_options();
	$legacy_options     = wp_seed_events_legacy_event_type_options();
	$label_overrides    = wp_seed_events_event_type_label_overrides();
	$removed_type_keys  = wp_seed_events_removed_default_event_type_keys();
	$available_defaults = array();

	foreach ( $default_options as $type_key => $type_label ) {
		$available_defaults[ $type_key ] = $label_overrides[ $type_key ] ?? $type_label;
	}

	foreach ( $legacy_options as $type_key => $type_label ) {
		if ( in_array( $type_key, $removed_type_keys, true ) ) {
			continue;
		}

		if ( isset( $label_overrides[ $type_key ] ) || 0 < wp_seed_events_event_type_usage_count( $type_key ) ) {
			$available_defaults[ $type_key ] = $label_overrides[ $type_key ] ?? $type_label;
		}
	}

	$custom_types  = wp_seed_events_custom_event_type_options();
	$known_labels  = array();
	$duplicate_map = array();

	foreach ( $available_defaults as $type_key => $type_label ) {
		$normalized_label = wp_seed_events_normalized_event_type_label( $type_label );

		if ( '' !== $normalized_label && ! isset( $known_labels[ $normalized_label ] ) ) {
			$known_labels[ $normalized_label ] = $type_key;
		}
	}

	foreach ( $custom_types as $type_key => $type_label ) {
		$normalized_label = wp_seed_events_normalized_event_type_label( $type_label );

		if ( '' === $normalized_label ) {
			continue;
		}

		if ( isset( $known_labels[ $normalized_label ] ) ) {
			$duplicate_map[ $type_key ] = $known_labels[ $normalized_label ];
			unset( $custom_types[ $type_key ] );
			continue;
		}

		$known_labels[ $normalized_label ] = $type_key;
	}

	if ( array() === $duplicate_map ) {
		return;
	}

	foreach ( $duplicate_map as $duplicate_type_key => $canonical_type_key ) {
		wp_seed_events_remap_event_type_key( $duplicate_type_key, $canonical_type_key );
	}

	if ( array() !== $custom_types ) {
		update_option( 'wp_seed_events_custom_event_types', $custom_types, false );
	} else {
		delete_option( 'wp_seed_events_custom_event_types' );
	}
}
add_action( 'admin_init', 'wp_seed_events_cleanup_duplicate_custom_event_types' );
function wp_seed_events_event_types_admin_url( $message = '' ) {
	$args = array(
		'post_type' => 'wp_seed_event',
		'page'      => 'wp-seed-event-types',
	);

	if ( '' !== $message ) {
		$args['wp_seed_events_message'] = $message;
	}

	return add_query_arg( $args, admin_url( 'edit.php' ) );
}

function wp_seed_events_person_types_admin_url( $message = '' ) {
	$args = array(
		'post_type' => 'wp_seed_event',
		'page'      => 'wp-seed-person-types',
	);
	if ( '' !== $message ) {
		$args['wp_seed_events_message'] = $message;
	}
	return add_query_arg( $args, admin_url( 'edit.php' ) );
}

function wp_seed_events_render_person_types_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les types de personnes.' );
	}
	$types    = wp_seed_events_person_type_options();
	$defaults = wp_seed_events_default_person_type_options();
	?>
	<div class="wrap" data-wp-seed-person-types-admin>
		<h1>Tous les types de personnes</h1>
		<?php if ( isset( $_GET['wp_seed_events_message'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p>Types de personnes enregistrés.</p></div>
		<?php endif; ?>
		<div id="col-container" class="wp-clearfix">
			<div id="col-left"><div class="col-wrap">
				<h2>Ajouter un type</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="wp_seed_events_save_person_types" />
					<input type="hidden" name="wp_seed_person_type_admin_action" value="add" />
					<?php wp_nonce_field( 'wp_seed_events_save_person_types', 'wp_seed_events_person_types_nonce' ); ?>
					<div class="form-field term-name-wrap"><label for="wp-seed-new-person-type-label">Nom</label><input id="wp-seed-new-person-type-label" type="text" name="wp_seed_new_person_type_label" /></div>
					<?php submit_button( 'Ajouter un type' ); ?>
				</form>
			</div></div>
			<div id="col-right"><div class="col-wrap">
				<h2>Types existants</h2>
				<table class="wp-list-table widefat fixed striped table-view-list tags"><thead><tr><th>Nom</th><th>Identifiant stable</th></tr></thead><tbody>
				<?php foreach ( $types as $key => $label ) : ?>
					<tr><td><strong><?php echo esc_html( $label ); ?></strong><div class="row-actions">
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
							<input type="hidden" name="action" value="wp_seed_events_save_person_types" /><input type="hidden" name="wp_seed_person_type_admin_action" value="rename" /><input type="hidden" name="wp_seed_person_type_key" value="<?php echo esc_attr( $key ); ?>" />
							<?php wp_nonce_field( 'wp_seed_events_save_person_types', 'wp_seed_events_person_types_nonce' ); ?>
							<label class="screen-reader-text" for="wp-seed-person-type-<?php echo esc_attr( $key ); ?>">Renommer</label><input id="wp-seed-person-type-<?php echo esc_attr( $key ); ?>" name="wp_seed_person_type_label" value="<?php echo esc_attr( $label ); ?>" /> <?php submit_button( 'Enregistrer', 'small', 'submit', false ); ?>
						</form>
						<?php if ( ! isset( $defaults[ $key ] ) ) : ?> | <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline"><input type="hidden" name="action" value="wp_seed_events_save_person_types" /><input type="hidden" name="wp_seed_person_type_admin_action" value="delete" /><input type="hidden" name="wp_seed_person_type_key" value="<?php echo esc_attr( $key ); ?>" /><?php wp_nonce_field( 'wp_seed_events_save_person_types', 'wp_seed_events_person_types_nonce' ); ?><button class="button-link delete" type="submit" onclick="return confirm('Supprimer ce type de personne ?');">Supprimer</button></form>
						<?php endif; ?>
					</div></td><td><code><?php echo esc_html( $key ); ?></code></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			</div></div>
		</div>
	</div>
	<?php
}

function wp_seed_events_handle_person_types_admin_form() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les types de personnes.' );
	}
	$nonce = isset( $_POST['wp_seed_events_person_types_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_events_person_types_nonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_person_types' ) ) {
		wp_die( 'La vérification de sécurité a échoué.' );
	}
	$action = isset( $_POST['wp_seed_person_type_admin_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_person_type_admin_action'] ) ) : '';
	$key    = isset( $_POST['wp_seed_person_type_key'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_person_type_key'] ) ) : '';
	$label  = isset( $_POST['wp_seed_person_type_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_type_label'] ) ) : '';
	if ( 'add' === $action ) {
		$label = isset( $_POST['wp_seed_new_person_type_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_person_type_label'] ) ) : '';
		wp_seed_events_add_person_type( $label );
	} elseif ( 'rename' === $action ) {
		wp_seed_events_rename_person_type( $key, $label );
	} elseif ( 'delete' === $action ) {
		wp_seed_events_delete_person_type( $key );
	}
	wp_safe_redirect( wp_seed_events_person_types_admin_url( 'types_saved' ) );
	exit;
}

function wp_seed_events_render_event_types_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les types d’évènements.' );
	}

	$message          = isset( $_GET['wp_seed_events_message'] ) ? sanitize_key( wp_unslash( $_GET['wp_seed_events_message'] ) ) : '';
	$types            = wp_seed_events_event_type_options();
	$default_type_key = wp_seed_events_default_event_type_key();
	?>
	<div class="wrap" data-wp-seed-event-types-admin>
		<h1>Tous les types d’évènements</h1>

		<?php if ( 'types_saved' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p>Types enregistrés.</p></div>

		<?php elseif ( 'type_is_default' === $message ) : ?>
			<div class="notice notice-warning is-dismissible"><p>Ce type système ne peut pas être supprimé.</p></div>

		<?php elseif ( 'type_slug_conflict' === $message ) : ?>
			<div class="notice notice-error is-dismissible"><p>Ce slug de type entre en conflit avec une racine WordPress existante. Definissez un prefixe Events ou choisissez un autre nom.</p></div>
		<?php endif; ?>

		<div id="col-container" class="wp-clearfix">
			<div id="col-left">
				<div class="col-wrap">
					<h2>Ajouter un type</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wp_seed_events_save_event_types" />
						<input type="hidden" name="wp_seed_event_type_admin_action" value="add" />
						<?php wp_nonce_field( 'wp_seed_events_save_event_types', 'wp_seed_events_event_types_nonce' ); ?>

						<div class="form-field term-name-wrap">
							<label for="wp-seed-new-event-type-label">Nom</label>
							<input id="wp-seed-new-event-type-label" type="text" name="wp_seed_new_event_type_label" value="" />
							<p>Disponible dans l’éditeur.</p>
						</div>

						<p class="submit">
							<?php submit_button( 'Ajouter un type', 'primary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

			<div id="col-right">
				<div class="col-wrap">
					<h2>Types existants</h2>

					<?php if ( array() === $types ) : ?>
						<p>Aucun type.</p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped table-view-list tags">
							<thead>
								<tr>
									<th scope="col" class="manage-column column-name column-primary">Nom</th>
									<th scope="col" class="manage-column column-posts num">Utilisation</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $types as $type_key => $type_label ) : ?>
									<?php
									$usage_count       = wp_seed_events_event_type_usage_count( $type_key );
									$is_system_default = $default_type_key === $type_key;
									$field_id            = 'wp-seed-event-type-label-' . $type_key;
									$delete_confirmation = 0 < $usage_count ? 'Supprimer ce type ? Les évènements concernés seront reclassés en Non classé.' : 'Supprimer ce type ?';
									?>
									<tr data-wp-seed-event-type-item>
										<td class="name column-name has-row-actions column-primary" data-colname="Nom">
											<strong><?php echo esc_html( $type_label ); ?></strong>

											<div class="row-actions">
												<span class="edit"><button type="button" class="button-link" data-wp-seed-event-type-edit>Modifier</button></span>

												<?php if ( $is_system_default ) : ?>
											<span class="delete"> | <span class="description">Non supprimable</span></span>
										<?php else : ?>
											<span class="delete"> | </span>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
												<input type="hidden" name="action" value="wp_seed_events_save_event_types" />
												<input type="hidden" name="wp_seed_event_type_admin_action" value="delete" />
												<input type="hidden" name="wp_seed_event_type_key" value="<?php echo esc_attr( $type_key ); ?>" />
												<?php wp_nonce_field( 'wp_seed_events_save_event_types', 'wp_seed_events_event_types_nonce' ); ?>
												<button type="submit" class="button-link delete" onclick="return confirm('<?php echo esc_js( $delete_confirmation ); ?>');">Supprimer<?php echo 0 < $usage_count ? ' — reclasser en Non classé' : ''; ?></button>
											</form>
										<?php endif; ?>
											</div>

											<div data-wp-seed-event-type-edit-panel hidden style="margin-top: 8px;">
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
													<input type="hidden" name="action" value="wp_seed_events_save_event_types" />
													<input type="hidden" name="wp_seed_event_type_admin_action" value="rename" />
													<input type="hidden" name="wp_seed_event_type_key" value="<?php echo esc_attr( $type_key ); ?>" />
													<?php wp_nonce_field( 'wp_seed_events_save_event_types', 'wp_seed_events_event_types_nonce' ); ?>
													<label class="screen-reader-text" for="<?php echo esc_attr( $field_id ); ?>">Renommer <?php echo esc_html( $type_label ); ?></label>
													<input id="<?php echo esc_attr( $field_id ); ?>" type="text" name="wp_seed_event_type_label" value="<?php echo esc_attr( $type_label ); ?>" />
													<?php submit_button( 'Enregistrer', 'primary small', 'submit', false ); ?>
													<button type="button" class="button button-small" data-wp-seed-event-type-cancel>Annuler</button>
												</form>
											</div>

											<button type="button" class="toggle-row"><span class="screen-reader-text">Afficher plus de détails</span></button>
										</td>
										<td class="posts column-posts num" data-colname="Utilisation">
											<?php if ( $is_system_default ) : ?>
												Système
											<?php else : ?>
												<?php echo esc_html( (string) $usage_count ); ?>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<script>
	jQuery(function($){
		$(document).on('click','[data-wp-seed-event-type-edit]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-event-type-item]').find('[data-wp-seed-event-type-edit-panel]').prop('hidden',false).find('input[type="text"]').trigger('focus');
		});

		$(document).on('click','[data-wp-seed-event-type-cancel]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-event-type-edit-panel]').prop('hidden',true);
		});
	});
	</script>
	<?php
}

function wp_seed_events_handle_event_types_admin_form() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les types d’évènements.' );
	}

	$nonce = isset( $_POST['wp_seed_events_event_types_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_events_event_types_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_event_types' ) ) {
		wp_die( 'La vérification de sécurité a échoué.' );
	}

	$admin_action      = isset( $_POST['wp_seed_event_type_admin_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_event_type_admin_action'] ) ) : '';
	$type_key          = isset( $_POST['wp_seed_event_type_key'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_event_type_key'] ) ) : '';
	$default_options   = wp_seed_events_default_event_type_options();
	$legacy_options    = wp_seed_events_legacy_event_type_options();
	$custom_types      = wp_seed_events_custom_event_type_options();
	$label_overrides   = wp_seed_events_event_type_label_overrides();
	$removed_type_keys = wp_seed_events_removed_default_event_type_keys();
	$rewrite_needs_flush = false;

	if ( 'add' === $admin_action ) {
		$new_type_label = isset( $_POST['wp_seed_new_event_type_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_event_type_label'] ) ) : '';

		if ( '' !== $new_type_label ) {
			$type_key = wp_seed_events_event_type_key_from_label( $new_type_label, wp_seed_events_event_type_options() );

			if ( '' !== $type_key && '' === wp_seed_events_permalink_prefix() && wp_seed_events_event_type_key_has_root_conflict( $type_key ) ) {
				wp_safe_redirect( wp_seed_events_event_types_admin_url( 'type_slug_conflict' ) );
				exit;
			}

			if ( '' !== $type_key ) {
				$rewrite_needs_flush = true;
				if ( isset( $default_options[ $type_key ] ) && in_array( $type_key, $removed_type_keys, true ) ) {
					$base_type_key = $type_key;
					$index         = 2;

					do {
						$type_key = $base_type_key . '-' . $index;
						$index++;
					} while ( isset( $default_options[ $type_key ] ) || isset( $custom_types[ $type_key ] ) );

					$custom_types[ $type_key ] = $new_type_label;
				} elseif ( isset( $default_options[ $type_key ] ) ) {
					if ( $new_type_label !== $default_options[ $type_key ] ) {
						$label_overrides[ $type_key ] = $new_type_label;
					}
				} else {
					$custom_types[ $type_key ] = $new_type_label;
				}
			}
		}
	} elseif ( 'rename' === $admin_action && '' !== $type_key ) {
		$type_label = isset( $_POST['wp_seed_event_type_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_type_label'] ) ) : '';

		if ( '' !== $type_label ) {
			if ( isset( $default_options[ $type_key ] ) || isset( $legacy_options[ $type_key ] ) ) {
				$base_type_label = $default_options[ $type_key ] ?? $legacy_options[ $type_key ];

				if ( $type_label === $base_type_label ) {
					unset( $label_overrides[ $type_key ] );
				} else {
					$label_overrides[ $type_key ] = $type_label;
				}
			} elseif ( isset( $custom_types[ $type_key ] ) ) {
				$custom_types[ $type_key ] = $type_label;
			}
		}
	} elseif ( 'delete' === $admin_action && '' !== $type_key ) {
		$rewrite_needs_flush = true;

		if ( wp_seed_events_default_event_type_key() === $type_key ) {
			wp_safe_redirect( wp_seed_events_event_types_admin_url( 'type_is_default' ) );
			exit;
		}

		wp_seed_events_reclassify_event_type_to_default( $type_key );

		if ( isset( $default_options[ $type_key ] ) || isset( $legacy_options[ $type_key ] ) ) {
			$removed_type_keys[] = $type_key;
			unset( $label_overrides[ $type_key ] );
		} else {
			unset( $custom_types[ $type_key ] );
		}
	}

	if ( array() !== $custom_types ) {
		update_option( 'wp_seed_events_custom_event_types', $custom_types, false );
	} else {
		delete_option( 'wp_seed_events_custom_event_types' );
	}

	if ( array() !== $label_overrides ) {
		update_option( 'wp_seed_events_event_type_label_overrides', $label_overrides, false );
	} else {
		delete_option( 'wp_seed_events_event_type_label_overrides' );
	}

	$removed_type_keys = array_values( array_unique( array_map( 'sanitize_key', $removed_type_keys ) ) );

	if ( array() !== $removed_type_keys ) {
		update_option( 'wp_seed_events_removed_default_event_type_keys', $removed_type_keys, false );
	} else {
		delete_option( 'wp_seed_events_removed_default_event_type_keys' );
	}

	foreach ( wp_seed_events_event_type_options() as $active_type_key => $active_type_label ) {
		wp_seed_events_ensure_native_event_type_term( $active_type_key, $active_type_label );
	}

	if ( $rewrite_needs_flush ) {
		flush_rewrite_rules( false );
		update_option( 'wp_seed_events_rewrite_version', WP_SEED_EVENTS_REWRITE_VERSION, false );
	}

	wp_safe_redirect( wp_seed_events_event_types_admin_url( 'types_saved' ) );
	exit;
}
function wp_seed_events_render_event_type_box( $post ) {
	$event_types = get_post_meta( $post->ID, '_wp_seed_event_types', true );

	if ( ! is_array( $event_types ) ) {
		$legacy_event_type = get_post_meta( $post->ID, '_wp_seed_event_type', true );
		$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
	}

	$event_type_options = wp_seed_events_event_type_options();
	$default_type_key   = wp_seed_events_default_event_type_key();

	$event_types = array_values(
		array_filter(
			array_unique( array_map( 'sanitize_key', $event_types ) ),
			function ( $type_key ) use ( $event_type_options ) {
				return isset( $event_type_options[ $type_key ] );
			}
		)
	);

	if ( array() === $event_types && isset( $event_type_options[ $default_type_key ] ) ) {
		$event_types[] = $default_type_key;
	}

	$primary_type = sanitize_key( (string) get_post_meta( $post->ID, '_wp_seed_event_primary_type', true ) );

	if ( '' === $primary_type || ! isset( $event_type_options[ $primary_type ] ) || ! in_array( $primary_type, $event_types, true ) ) {
		$primary_type = wp_seed_events_primary_type_for_event( $post->ID );
	}

	if ( '' === $primary_type && in_array( $default_type_key, $event_types, true ) ) {
		$primary_type = $default_type_key;
	}

	if ( '' === $primary_type && array() !== $event_types ) {
		$primary_type = $event_types[0];
	}

	if ( '' === $primary_type && isset( $event_type_options[ $default_type_key ] ) ) {
		$primary_type  = $default_type_key;
		$event_types[] = $default_type_key;
	}

	if ( '' !== $primary_type && ! in_array( $primary_type, $event_types, true ) ) {
		$event_types[] = $primary_type;
	}

	$is_pinned = '1' === get_post_meta( $post->ID, '_wp_seed_event_pinned', true );

	wp_nonce_field( 'wp_seed_events_save_event_type', 'wp_seed_events_event_type_nonce' );
	?>
	<div data-wp-seed-event-type data-default-type="<?php echo esc_attr( $default_type_key ); ?>">
		<p>
			<label for="wp-seed-event-primary-type"><strong>Type principal</strong></label><br />
			<span class="description">Catégorie principale de l'événement.</span><br />
			<select id="wp-seed-event-primary-type" name="wp_seed_event_primary_type" data-wp-seed-event-primary-type required>
				<?php foreach ( $event_type_options as $type_key => $type_label ) : ?>
					<option value="<?php echo esc_attr( $type_key ); ?>" data-normalized-label="<?php echo esc_attr( wp_seed_events_normalized_event_type_label( $type_label ) ); ?>" <?php selected( $primary_type, $type_key ); ?>><?php echo esc_html( $type_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<br />
			<span class="description">Si les URL par type sont activées, ce type sera également utilisé dans l'adresse publique.</span>
		</p>

		<hr />

		<details data-wp-seed-event-secondary-types>
			<summary>Types secondaires</summary>
			<p class="description">Les types secondaires permettent de classer un même événement dans plusieurs catégories.</p>
			<div data-wp-seed-event-type-options>
				<?php foreach ( $event_type_options as $type_key => $type_label ) : ?>
					<?php
					$is_primary = $type_key === $primary_type;
					$is_checked = $is_primary || in_array( $type_key, $event_types, true );
					?>
					<p data-wp-seed-event-type-option data-type-key="<?php echo esc_attr( $type_key ); ?>" data-normalized-label="<?php echo esc_attr( wp_seed_events_normalized_event_type_label( $type_label ) ); ?>" <?php echo $is_primary ? 'hidden style="display:none;"' : ''; ?>>
						<label>
							<input type="checkbox" name="wp_seed_event_types[]" value="<?php echo esc_attr( $type_key ); ?>" <?php checked( $is_checked ); ?> />
							<?php echo esc_html( $type_label ); ?>
						</label>
					</p>
				<?php endforeach; ?>
			</div>
		</details>

		<hr />

		<fieldset>
			<legend>Mise en avant</legend>
			<label>
				<input type="checkbox" name="wp_seed_event_pinned" value="1" <?php checked( $is_pinned ); ?> />
				📌 Épingler cet événement
			</label>
			<p class="description">Les événements épinglés apparaissent en priorité dans les collections publiques.</p>
		</fieldset>
	</div>
	<?php
}

function wp_seed_events_save_event_type( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_event_type_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_event_type_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_event_type' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$options        = wp_seed_events_event_type_options();
	$selected_types = isset( $_POST['wp_seed_event_types'] ) && is_array( $_POST['wp_seed_event_types'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['wp_seed_event_types'] ) ) : array();
	$primary_type   = isset( $_POST['wp_seed_event_primary_type'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_event_primary_type'] ) ) : '';
	$default_type   = wp_seed_events_default_event_type_key();

	$selected_types = array_values(
		array_unique(
			array_filter(
				$selected_types,
				function ( $type_key ) use ( $options ) {
					return isset( $options[ $type_key ] );
				}
			)
		)
	);

	if ( '' !== $primary_type && isset( $options[ $primary_type ] ) && ! in_array( $primary_type, $selected_types, true ) ) {
		$selected_types[] = $primary_type;
	}

	if ( array() === $selected_types && isset( $options[ $default_type ] ) ) {
		$selected_types[] = $default_type;
	}

	$public_types = array_values(
		array_filter(
			$selected_types,
			function ( $type_key ) use ( $default_type ) {
				return $default_type !== $type_key;
			}
		)
	);

	if ( '' === $primary_type && 1 === count( $public_types ) ) {
		$primary_type = $public_types[0];
	}

	if ( '' === $primary_type && in_array( $default_type, $selected_types, true ) ) {
		$primary_type = $default_type;
	}

	if ( '' !== $primary_type && isset( $options[ $primary_type ] ) && ! in_array( $primary_type, $selected_types, true ) ) {
		$selected_types[] = $primary_type;
	}

	if ( array() !== $selected_types ) {
		update_post_meta( $post_id, '_wp_seed_event_types', array_values( array_unique( $selected_types ) ) );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_types' );
	}

	if ( '' !== $primary_type && $default_type !== $primary_type && in_array( $primary_type, $selected_types, true ) ) {
		update_post_meta( $post_id, '_wp_seed_event_primary_type', $primary_type );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_primary_type' );
	}

	delete_post_meta( $post_id, '_wp_seed_event_type' );

	if ( ! empty( $_POST['wp_seed_event_pinned'] ) ) {
		update_post_meta( $post_id, '_wp_seed_event_pinned', '1' );
		return;
	}

	delete_post_meta( $post_id, '_wp_seed_event_pinned' );
}
function wp_seed_events_disable_description_media_buttons( $settings, $editor_id ) {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( $screen && 'wp_seed_event' === $screen->post_type && 'content' === $editor_id ) {
		$settings['media_buttons'] = false;
	}

	return $settings;
}
function wp_seed_events_enable_event_thumbnail_support() {
	if ( current_theme_supports( 'post-thumbnails', 'wp_seed_event' ) ) {
		return;
	}

	$post_thumbnail_support = get_theme_support( 'post-thumbnails' );

	if ( is_array( $post_thumbnail_support ) && isset( $post_thumbnail_support[0] ) && is_array( $post_thumbnail_support[0] ) ) {
		$post_types   = $post_thumbnail_support[0];
		$post_types[] = 'wp_seed_event';

		add_theme_support( 'post-thumbnails', array_values( array_unique( $post_types ) ) );
		return;
	}

	add_theme_support( 'post-thumbnails', array( 'wp_seed_event' ) );
}
function wp_seed_events_add_occurrences_meta_box() {
	add_meta_box(
		'wp_seed_events_occurrences',
		'Quand a lieu mon évènement ?',
		'wp_seed_events_render_occurrences_meta_box',
		'wp_seed_event',
		'normal',
		'high'
	);
}

function wp_seed_events_format_occurrence_date( $date ) {
	if ( '' === $date ) {
		return 'Date sans jour défini';
	}

	$timestamp = strtotime( $date . ' 12:00:00' );

	if ( false === $timestamp ) {
		return $date;
	}

	return ucfirst( date_i18n( 'l j F Y', $timestamp ) );
}

function wp_seed_events_format_occurrence_time( $time ) {
	if ( '' === $time ) {
		return '';
	}

	return str_replace( ':', 'h', $time );
}

function wp_seed_events_format_occurrence_date_line( $occurrence ) {
	$start_date = $occurrence['start_date'] ?? '';
	$end_date   = $occurrence['end_date'] ?? '';

	if ( '' !== $end_date && $end_date !== $start_date ) {
		return wp_seed_events_format_occurrence_date( $start_date ) . ' → ' . wp_seed_events_format_occurrence_date( $end_date );
	}

	return wp_seed_events_format_occurrence_date( $start_date );
}

function wp_seed_events_format_occurrence_time_line( $occurrence ) {
	$start_time = $occurrence['start_time'] ?? '';
	$end_time   = $occurrence['end_time'] ?? '';

	if ( ! empty( $occurrence['all_day'] ) ) {
		return 'Toute la journée';
	}

	if ( '' !== $start_time && '' !== $end_time ) {
		return wp_seed_events_format_occurrence_time( $start_time ) . ' → ' . wp_seed_events_format_occurrence_time( $end_time );
	}

	if ( '' !== $start_time ) {
		return 'À partir de ' . wp_seed_events_format_occurrence_time( $start_time );
	}

	if ( '' !== $end_time ) {
		return 'Jusqu’à ' . wp_seed_events_format_occurrence_time( $end_time );
	}

	return '';
}

function wp_seed_events_occurrence_sort_value( $occurrence ) {
	if ( ! is_array( $occurrence ) ) {
		return '';
	}

	$start_date = $occurrence['start_date'] ?? '';
	$start_time = ! empty( $occurrence['all_day'] ) ? '00:00' : ( $occurrence['start_time'] ?? '00:00' );

	return $start_date . ' ' . $start_time;
}

function wp_seed_events_sort_occurrences_for_display( $occurrences ) {
	uasort(
		$occurrences,
		function ( $first_occurrence, $second_occurrence ) {
			return strcmp( wp_seed_events_occurrence_sort_value( $first_occurrence ), wp_seed_events_occurrence_sort_value( $second_occurrence ) );
		}
	);

	return $occurrences;
}
function wp_seed_events_render_occurrences_meta_box( $post ) {
	$occurrences = get_post_meta( $post->ID, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $occurrences ) ) {
		$occurrences = array();
	}

	$display_occurrences     = wp_seed_events_sort_occurrences_for_display( $occurrences );
	$promotions              = wp_seed_events_get_promotions( array( 'status' => 'all' ) );
	$has_display_occurrences = false;

	wp_nonce_field( 'wp_seed_events_save_occurrences', 'wp_seed_events_occurrences_nonce' );
	?>
	<input type="hidden" name="wp_seed_event_occurrences_changed" value="0" data-wp-seed-occurrences-changed />
	<div data-wp-seed-dates data-next-index="<?php echo esc_attr( (string) count( $occurrences ) ); ?>">
		<div data-wp-seed-dates-list>
			<?php foreach ( $display_occurrences as $index => $occurrence ) : ?>
				<?php
				if ( ! is_array( $occurrence ) || empty( $occurrence['start_date'] ) ) {
					continue;
				}

				$has_display_occurrences = true;
				$is_cancelled            = ! empty( $occurrence['cancelled'] );
				$promotion_id            = absint( $occurrence['promotion_id'] ?? 0 );
				$promotion               = 0 < $promotion_id ? wp_seed_events_get_promotion( $promotion_id ) : array();
				$parcours_year           = wp_seed_events_normalize_parcours_year( $occurrence['parcours_year'] ?? 0 );
				?>
				<div data-wp-seed-date-item data-wp-seed-date-sort="<?php echo esc_attr( wp_seed_events_occurrence_sort_value( $occurrence ) ); ?>" style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
					<input type="hidden" data-wp-seed-date-field="uid" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][uid]" value="<?php echo esc_attr( $occurrence['uid'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="start_date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_date]" value="<?php echo esc_attr( $occurrence['start_date'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="end_date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_date]" value="<?php echo esc_attr( $occurrence['end_date'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="start_time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_time]" value="<?php echo esc_attr( $occurrence['start_time'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="end_time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_time]" value="<?php echo esc_attr( $occurrence['end_time'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="all_day" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][all_day]" value="<?php echo ! empty( $occurrence['all_day'] ) ? '1' : ''; ?>" />
					<input type="hidden" data-wp-seed-date-field="cancelled" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][cancelled]" value="<?php echo $is_cancelled ? '1' : ''; ?>" />
					<input type="hidden" data-wp-seed-date-field="promotion_id" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][promotion_id]" value="<?php echo esc_attr( 0 < $promotion_id ? (string) $promotion_id : '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="parcours_year" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][parcours_year]" value="<?php echo esc_attr( 0 < $parcours_year ? (string) $parcours_year : '' ); ?>" />
					<p style="margin: 0;">
						<strong data-wp-seed-date-day><?php echo esc_html( wp_seed_events_format_occurrence_date_line( $occurrence ) ); ?></strong>
						<span data-wp-seed-date-cancelled-label style="margin-left: 8px; color: #b32d2e; font-weight: 600;" <?php echo $is_cancelled ? '' : 'hidden'; ?>>ANNULÉE</span><br />
						<span data-wp-seed-date-time><?php echo esc_html( wp_seed_events_format_occurrence_time_line( $occurrence ) ); ?></span><br />
						<span data-wp-seed-date-parcours <?php echo array() === $promotion ? 'hidden' : ''; ?>>
							<?php echo array() !== $promotion ? esc_html( $promotion['name'] . ' — ' . wp_seed_events_parcours_year_label( $parcours_year ) ) : ''; ?><br />
						</span>
						<span style="font-size: 12px;">
							<button type="button" class="button-link" data-wp-seed-date-edit>Modifier</button>
							<span aria-hidden="true"> · </span>
							<button type="button" class="button-link" data-wp-seed-date-toggle><?php echo $is_cancelled ? 'Réactiver' : 'Marquer comme annulée'; ?></button>
							<span aria-hidden="true"> · </span>
							<button type="button" class="button-link-delete" data-wp-seed-date-remove>Supprimer</button>
						</span>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<p data-wp-seed-dates-empty <?php echo $has_display_occurrences ? 'hidden' : ''; ?>>Aucune date ajoutée.</p>
		<p><button type="button" class="button" data-wp-seed-date-add>+ Ajouter une date</button></p>

		<div data-wp-seed-date-panel hidden>
			<h4 data-wp-seed-date-panel-title>Ajouter une date</h4>
			<p>
				<label>
					Date de début<br />
					<input type="date" data-wp-seed-date-panel-field="start_date" />
				</label>
			</p>
			<p>
				<label>
					Date de fin (facultative)<br />
					<input type="date" data-wp-seed-date-panel-field="end_date" />
				</label>
			</p>
			<p>
				<label>
					Heure de début (facultative)<br />
					<input type="time" data-wp-seed-date-panel-field="start_time" />
				</label>
			</p>
			<p>
				<label>
					Heure de fin (facultative)<br />
					<input type="time" data-wp-seed-date-panel-field="end_time" />
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" data-wp-seed-date-panel-field="all_day" value="1" />
					Journée entière
				</label>
			</p>
			<fieldset style="margin: 16px 0; padding: 12px; border: 1px solid #dcdcde;">
				<legend><strong>Parcours (facultatif)</strong></legend>
				<p>
					<label>
						Promotion<br />
						<select data-wp-seed-date-panel-field="promotion_id">
							<option value="">Aucune promotion</option>
							<?php foreach ( $promotions as $promotion ) : ?>
								<option
									value="<?php echo esc_attr( (string) $promotion['id'] ); ?>"
									data-promotion-status="<?php echo esc_attr( $promotion['status'] ); ?>"
									<?php disabled( 'archived', $promotion['status'] ); ?>
								>
									<?php echo esc_html( $promotion['name'] . ( 'archived' === $promotion['status'] ? ' (archivée)' : '' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</p>
				<p>
					<label>
						Année du parcours<br />
						<select data-wp-seed-date-panel-field="parcours_year" disabled>
							<option value="">Choisir une année</option>
							<option value="1">1re année</option>
							<option value="2">2e année</option>
							<option value="3">3e année</option>
							<option value="4">4e année</option>
						</select>
					</label>
				</p>
				<p class="description">La promotion et l’année sont toujours enregistrées ensemble. Aucune année n’est déduite automatiquement.</p>
			</fieldset>
			<p>
				<button type="button" class="button button-primary" data-wp-seed-date-save>Enregistrer la date</button>
				<button type="button" class="button" data-wp-seed-date-cancel>Annuler</button>
			</p>
		</div>
		<p class="description" data-wp-seed-date-save-guidance>
			Après avoir enregistré la date, pensez à mettre à jour l’événement pour conserver vos modifications.
		</p>
	</div>
	<?php
}

function wp_seed_events_save_occurrences( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_occurrences_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_occurrences_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_occurrences' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$changed_is_present = isset( $_POST['wp_seed_event_occurrences_changed'] );
	$changed            = $changed_is_present
		? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_occurrences_changed'] ) )
		: '';

	if ( ( $changed_is_present && '1' !== $changed ) || ( ! $changed_is_present && ! isset( $_POST['wp_seed_events_occurrences'] ) ) ) {
		return;
	}

	$stored_occurrences = get_post_meta( $post_id, '_wp_seed_event_occurrences', true );
	$stored_occurrences = is_array( $stored_occurrences ) ? $stored_occurrences : array();
	$stored_by_uid      = array();

	foreach ( $stored_occurrences as $stored_index => $stored_occurrence ) {
		if ( ! is_array( $stored_occurrence ) ) {
			continue;
		}

		$stored_uid = wp_seed_events_sanitize_occurrence_uid( $stored_occurrence['uid'] ?? '' );
		$stored_by_uid[ '' !== $stored_uid ? $stored_uid : 'index:' . $stored_index ] = $stored_occurrence;
	}

	$raw_occurrences = isset( $_POST['wp_seed_events_occurrences'] ) && is_array( $_POST['wp_seed_events_occurrences'] ) ? wp_unslash( $_POST['wp_seed_events_occurrences'] ) : array();
	$occurrences     = array();

	foreach ( $raw_occurrences as $occurrence_index => $raw_occurrence ) {
		if ( ! is_array( $raw_occurrence ) ) {
			$GLOBALS['wp_seed_events_occurrences_validation_error'] = true;
			return;
		}

		$start_date = isset( $raw_occurrence['start_date'] ) ? sanitize_text_field( $raw_occurrence['start_date'] ) : '';
		$end_date   = isset( $raw_occurrence['end_date'] ) ? sanitize_text_field( $raw_occurrence['end_date'] ) : '';
		$start_time = isset( $raw_occurrence['start_time'] ) ? sanitize_text_field( $raw_occurrence['start_time'] ) : '';
		$end_time   = isset( $raw_occurrence['end_time'] ) ? sanitize_text_field( $raw_occurrence['end_time'] ) : '';
		$cancelled  = ! empty( $raw_occurrence['cancelled'] ) ? '1' : '';
		$uid        = isset( $raw_occurrence['uid'] ) ? wp_seed_events_sanitize_occurrence_uid( $raw_occurrence['uid'] ) : '';
		$promotion_id = absint( $raw_occurrence['promotion_id'] ?? 0 );
		$parcours_year = absint( $raw_occurrence['parcours_year'] ?? 0 );

		if ( '' === $start_date ) {
			$GLOBALS['wp_seed_events_occurrences_validation_error'] = true;
			return;
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			$GLOBALS['wp_seed_events_occurrences_validation_error'] = true;
			return;
		}

		if ( '' !== $end_date && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			$end_date = '';
		}

		if ( '' !== $start_time && ! preg_match( '/^\d{2}:\d{2}$/', $start_time ) ) {
			$start_time = '';
		}

		if ( '' !== $end_time && ! preg_match( '/^\d{2}:\d{2}$/', $end_time ) ) {
			$end_time = '';
		}

		if ( '' === $uid ) {
			$uid = wp_seed_events_generate_occurrence_uid();
		}

		$existing_key        = isset( $stored_by_uid[ $uid ] ) ? $uid : 'index:' . $occurrence_index;
		$existing_occurrence = $stored_by_uid[ $existing_key ] ?? array();
		$allow_archived      = (
			is_array( $existing_occurrence )
			&& 0 < $promotion_id
			&& $promotion_id === absint( $existing_occurrence['promotion_id'] ?? 0 )
		);
		$parcours_validation = wp_seed_events_validate_occurrence_parcours( $promotion_id, $parcours_year, $allow_archived );

		if ( is_wp_error( $parcours_validation ) ) {
			$GLOBALS['wp_seed_events_occurrences_validation_error'] = true;
			return;
		}

		$occurrences[] = array(
			'uid'        => $uid,
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'start_time' => $start_time,
			'end_time'   => $end_time,
			'all_day'    => ! empty( $raw_occurrence['all_day'] ) ? '1' : '',
			'cancelled'  => $cancelled,
			'promotion_id' => $promotion_id,
			'parcours_year' => wp_seed_events_normalize_parcours_year( $parcours_year ),
		);
	}

	if ( array() === $occurrences ) {
		delete_post_meta( $post_id, '_wp_seed_event_occurrences' );
		delete_post_meta( $post_id, '_wp_seed_event_next_occurrence_sort' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_occurrences', $occurrences );

	$next_occurrence_sort = wp_seed_events_next_occurrence_sort_value( $occurrences );

	if ( '' === $next_occurrence_sort ) {
		delete_post_meta( $post_id, '_wp_seed_event_next_occurrence_sort' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_next_occurrence_sort', $next_occurrence_sort );
}


function wp_seed_events_places() {
	$places = get_posts(
		array(
			'post_type'      => 'wp_seed_place',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	return is_array( $places ) ? $places : array();
}

function wp_seed_events_event_ids_for_place( $place_id ) {
	$place_id = absint( $place_id );

	if ( 0 === $place_id ) {
		return array();
	}

	return get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_wp_seed_event_place_id',
					'value'   => $place_id,
					'compare' => '=',
				),
			),
		)
	);
}

function wp_seed_events_place_usage_count( $place_id ) {
	return count( wp_seed_events_event_ids_for_place( $place_id ) );
}

function wp_seed_events_remove_place_from_events( $place_id ) {
	$place_id = absint( $place_id );

	if ( 0 === $place_id ) {
		return;
	}

	foreach ( wp_seed_events_event_ids_for_place( $place_id ) as $event_id ) {
		delete_post_meta( $event_id, '_wp_seed_event_place_id' );
	}
}

function wp_seed_events_places_admin_url( $message = '' ) {
	$args = array(
		'post_type' => 'wp_seed_event',
		'page'      => 'wp-seed-event-places',
	);

	if ( '' !== $message ) {
		$args['wp_seed_events_message'] = $message;
	}

	return add_query_arg( $args, admin_url( 'edit.php' ) );
}

function wp_seed_events_render_places_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les lieux d’évènements.' );
	}

	$message = isset( $_GET['wp_seed_events_message'] ) ? sanitize_key( wp_unslash( $_GET['wp_seed_events_message'] ) ) : '';
	$places  = wp_seed_events_places();
	?>
	<div class="wrap" data-wp-seed-places-admin>
		<h1>Tous les lieux d’évènements</h1>

		<?php if ( 'places_saved' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p>Lieux enregistrés.</p></div>
		<?php endif; ?>

		<div id="col-container" class="wp-clearfix">
			<div id="col-left">
				<div class="col-wrap">
					<h2>Ajouter un lieu</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wp_seed_events_save_places" />
						<input type="hidden" name="wp_seed_place_admin_action" value="add" />
						<?php wp_nonce_field( 'wp_seed_events_save_places', 'wp_seed_events_places_nonce' ); ?>

						<div class="form-field term-name-wrap">
							<label for="wp-seed-new-place-name">Nom</label>
							<input id="wp-seed-new-place-name" type="text" name="wp_seed_place_name" value="" />
						</div>

						<div class="form-field">
							<label for="wp-seed-new-place-address">Adresse (facultatif)</label>
							<input id="wp-seed-new-place-address" type="text" name="wp_seed_place_address" value="" />
						</div>

						<div class="form-field">
							<label for="wp-seed-new-place-link">URL (facultative)</label>
							<input id="wp-seed-new-place-link" type="url" name="wp_seed_place_link" value="" />
						</div>

						<div class="form-field">
							<label><input type="checkbox" name="wp_seed_place_link_visible" value="1" checked /> Afficher cette URL publiquement</label>
						</div>

						<div class="form-field">
							<label for="wp-seed-new-place-details">Informations complémentaires (facultatives)</label>
							<textarea id="wp-seed-new-place-details" name="wp_seed_place_details" rows="3"></textarea>
						</div>

						<p class="submit">
							<?php submit_button( 'Ajouter le lieu', 'primary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

			<div id="col-right">
				<div class="col-wrap">
					<h2>Liste des lieux</h2>

					<?php if ( array() === $places ) : ?>
						<p>Aucun lieu.</p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped table-view-list tags">
							<thead>
								<tr>
									<th scope="col" class="manage-column column-name column-primary">Nom</th>
									<th scope="col" class="manage-column">Adresse</th>
									<th scope="col" class="manage-column">URL</th>
									<th scope="col" class="manage-column column-posts num">Utilisation</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $places as $place ) : ?>
									<?php
									$place_id            = (int) $place->ID;
									$address             = get_post_meta( $place_id, '_wp_seed_place_address', true );
									$link                = get_post_meta( $place_id, '_wp_seed_place_link', true );
									$link_visible        = wp_seed_events_place_url_is_visible( $place_id );
									$details             = get_post_meta( $place_id, '_wp_seed_place_details', true );
									$usage_count         = wp_seed_events_place_usage_count( $place_id );
									$field_id            = 'wp-seed-place-name-' . $place_id;
									$delete_confirmation = 0 < $usage_count ? 'Supprimer ce lieu ? Il sera retiré des évènements concernés.' : 'Supprimer ce lieu ?';
									?>
									<tr data-wp-seed-place-admin-item>
										<td class="name column-name has-row-actions column-primary" data-colname="Nom">
											<strong><?php echo esc_html( get_the_title( $place ) ); ?></strong>

											<div class="row-actions">
												<span class="edit"><button type="button" class="button-link" data-wp-seed-place-admin-edit>Modifier</button></span>
												<span class="delete"> | </span>
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
													<input type="hidden" name="action" value="wp_seed_events_save_places" />
													<input type="hidden" name="wp_seed_place_admin_action" value="delete" />
													<input type="hidden" name="wp_seed_place_id" value="<?php echo esc_attr( (string) $place_id ); ?>" />
													<?php wp_nonce_field( 'wp_seed_events_save_places', 'wp_seed_events_places_nonce' ); ?>
													<button type="submit" class="button-link delete" onclick="return confirm('<?php echo esc_js( $delete_confirmation ); ?>');">Supprimer<?php echo 0 < $usage_count ? ' — retirer des évènements' : ''; ?></button>
												</form>
											</div>

											<div data-wp-seed-place-admin-edit-panel hidden style="margin-top: 8px;">
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
													<input type="hidden" name="action" value="wp_seed_events_save_places" />
													<input type="hidden" name="wp_seed_place_admin_action" value="update" />
													<input type="hidden" name="wp_seed_place_id" value="<?php echo esc_attr( (string) $place_id ); ?>" />
													<?php wp_nonce_field( 'wp_seed_events_save_places', 'wp_seed_events_places_nonce' ); ?>
													<p><label for="<?php echo esc_attr( $field_id ); ?>">Nom</label><br /><input id="<?php echo esc_attr( $field_id ); ?>" type="text" name="wp_seed_place_name" value="<?php echo esc_attr( get_the_title( $place ) ); ?>" /></p>
													<p><label>Adresse (facultatif)<br /><input type="text" name="wp_seed_place_address" value="<?php echo esc_attr( $address ); ?>" /></label></p>
											<p><label>URL (facultative)<br /><input type="url" name="wp_seed_place_link" value="<?php echo esc_attr( $link ); ?>" /></label></p>
											<p><label><input type="checkbox" name="wp_seed_place_link_visible" value="1" <?php checked( $link_visible ); ?> /> Afficher cette URL publiquement</label></p>
											<p><label>Informations complémentaires (facultatives)<br /><textarea name="wp_seed_place_details" rows="3"><?php echo esc_textarea( $details ); ?></textarea></label></p>
													<?php submit_button( 'Enregistrer', 'primary small', 'submit', false ); ?>
													<button type="button" class="button button-small" data-wp-seed-place-admin-cancel>Annuler</button>
												</form>
											</div>

											<button type="button" class="toggle-row"><span class="screen-reader-text">Afficher plus de détails</span></button>
										</td>
										<td data-colname="Adresse"><?php echo esc_html( $address ); ?></td>
								<td data-colname="URL">
											<?php if ( '' !== $link ) : ?>
												<a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link ); ?></a>
											<?php endif; ?>
										</td>
										<td class="posts column-posts num" data-colname="Utilisation"><?php echo esc_html( (string) $usage_count ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<script>
	jQuery(function($){
		$(document).on('click','[data-wp-seed-place-admin-edit]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-place-admin-item]').find('[data-wp-seed-place-admin-edit-panel]').prop('hidden',false).find('input[type="text"]').first().trigger('focus');
		});

		$(document).on('click','[data-wp-seed-place-admin-cancel]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-place-admin-edit-panel]').prop('hidden',true);
		});
	});
	</script>
	<?php
}

function wp_seed_events_handle_places_admin_form() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les lieux d’évènements.' );
	}

	$nonce = isset( $_POST['wp_seed_events_places_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_events_places_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_places' ) ) {
		wp_die( 'La vérification de sécurité a échoué.' );
	}

	$admin_action = isset( $_POST['wp_seed_place_admin_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_place_admin_action'] ) ) : '';
	$place_id     = isset( $_POST['wp_seed_place_id'] ) ? absint( wp_unslash( $_POST['wp_seed_place_id'] ) ) : 0;
	$place_name   = isset( $_POST['wp_seed_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_place_name'] ) ) : '';
	$address      = isset( $_POST['wp_seed_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_place_address'] ) ) : '';
	$link         = isset( $_POST['wp_seed_place_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_place_link'] ) ) : '';
	$link_visible = isset( $_POST['wp_seed_place_link_visible'] ) && '1' === (string) wp_unslash( $_POST['wp_seed_place_link_visible'] );
	$details      = isset( $_POST['wp_seed_place_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wp_seed_place_details'] ) ) : '';

	if ( 'add' === $admin_action && '' !== $place_name ) {
		$new_place_id = wp_insert_post(
			array(
				'post_type'   => 'wp_seed_place',
				'post_status' => 'publish',
				'post_title'  => $place_name,
				'post_author' => get_current_user_id(),
			)
		);

		if ( ! is_wp_error( $new_place_id ) && $new_place_id ) {
			if ( '' !== $address ) {
				update_post_meta( (int) $new_place_id, '_wp_seed_place_address', $address );
			}

			if ( '' !== $link ) {
				update_post_meta( (int) $new_place_id, '_wp_seed_place_link', $link );
			}
			update_post_meta( (int) $new_place_id, '_wp_seed_place_link_visible', $link_visible && '' !== $link ? '1' : '0' );
			if ( '' !== $details ) {
				update_post_meta( (int) $new_place_id, '_wp_seed_place_details', $details );
			}
		}
	} elseif ( 'update' === $admin_action && $place_id > 0 && 'wp_seed_place' === get_post_type( $place_id ) && current_user_can( 'edit_post', $place_id ) ) {
		if ( '' !== $place_name ) {
			wp_update_post(
				array(
					'ID'         => $place_id,
					'post_title' => $place_name,
				)
			);
		}

		if ( '' !== $address ) {
			update_post_meta( $place_id, '_wp_seed_place_address', $address );
		} else {
			delete_post_meta( $place_id, '_wp_seed_place_address' );
		}

		if ( '' !== $link ) {
			update_post_meta( $place_id, '_wp_seed_place_link', $link );
		} else {
			delete_post_meta( $place_id, '_wp_seed_place_link' );
		}
		update_post_meta( $place_id, '_wp_seed_place_link_visible', $link_visible && '' !== $link ? '1' : '0' );
		if ( '' !== $details ) {
			update_post_meta( $place_id, '_wp_seed_place_details', $details );
		} else {
			delete_post_meta( $place_id, '_wp_seed_place_details' );
		}
	} elseif ( 'delete' === $admin_action && $place_id > 0 && 'wp_seed_place' === get_post_type( $place_id ) && current_user_can( 'delete_post', $place_id ) ) {
		wp_seed_events_remove_place_from_events( $place_id );
		wp_delete_post( $place_id, true );
	}

	wp_safe_redirect( wp_seed_events_places_admin_url( 'places_saved' ) );
	exit;
}

function wp_seed_events_add_place_meta_box() {
	add_meta_box(
		'wp_seed_events_place',
		'Où a lieu mon évènement ?',
		'wp_seed_events_render_place_meta_box',
		'wp_seed_event',
		'normal',
		'default'
	);
}

function wp_seed_events_get_place_suggestions( $limit = -1 ) {
	$places = get_posts(
		array(
			'post_type'      => 'wp_seed_place',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);
	$usage_counts = array();

	foreach ( $places as $place ) {
		$usage_counts[ $place->ID ] = wp_seed_events_place_usage_count( $place->ID );
	}

	usort(
		$places,
		static function ( $left, $right ) use ( $usage_counts ) {
			$usage_order = ( $usage_counts[ $right->ID ] ?? 0 ) <=> ( $usage_counts[ $left->ID ] ?? 0 );

			if ( 0 !== $usage_order ) {
				return $usage_order;
			}

			$left_name  = strtolower( remove_accents( (string) $left->post_title ) );
			$right_name = strtolower( remove_accents( (string) $right->post_title ) );
			$name_order = strcmp( $left_name, $right_name );

			return 0 !== $name_order ? $name_order : ( (int) $left->ID <=> (int) $right->ID );
		}
	);

	$limit = (int) $limit;

	return 0 < $limit ? array_slice( $places, 0, $limit ) : $places;
}

function wp_seed_events_render_remove_from_event_button( $association ) {
	$attributes = array(
		'person' => 'data-wp-seed-person-remove',
		'place'  => 'data-wp-seed-place-remove',
	);

	if ( ! isset( $attributes[ $association ] ) ) {
		return;
	}

	printf(
		'<button type="button" class="button-link button-link-delete" %1$s>%2$s</button>',
		$attributes[ $association ],
		esc_html__( 'Retirer de cet événement', 'wp-seed-events' )
	);
}

function wp_seed_events_render_place_meta_box( $post ) {
	$selected_place_id = (int) get_post_meta( $post->ID, '_wp_seed_event_place_id', true );
	$place_details     = get_post_meta( $post->ID, '_wp_seed_event_place_details', true );
	$selected_place    = $selected_place_id ? get_post( $selected_place_id ) : null;
	$place_address     = $selected_place ? get_post_meta( $selected_place_id, '_wp_seed_place_address', true ) : '';
	$place_link        = $selected_place ? get_post_meta( $selected_place_id, '_wp_seed_place_link', true ) : '';
	$place_link_visible = $selected_place ? wp_seed_events_place_url_is_visible( $selected_place_id ) : true;
	$suggestions       = wp_seed_events_get_place_suggestions();

	if ( ! $selected_place || 'wp_seed_place' !== $selected_place->post_type ) {
		$selected_place_id = 0;
		$selected_place    = null;
		$place_address     = '';
		$place_link        = '';
		$place_link_visible = true;
	}

	wp_nonce_field( 'wp_seed_events_save_event_place', 'wp_seed_events_place_nonce' );
	?>
	<div data-wp-seed-place data-wp-seed-place-link-visible="<?php echo $place_link_visible ? '1' : '0'; ?>">
		<input type="hidden" name="wp_seed_event_place_id" data-wp-seed-place-field="place_id" value="<?php echo esc_attr( (string) $selected_place_id ); ?>" />
		<input type="hidden" name="wp_seed_new_place_name" data-wp-seed-place-field="new_name" value="" />
		<input type="hidden" name="wp_seed_new_place_address" data-wp-seed-place-field="new_address" value="" />
		<input type="hidden" name="wp_seed_new_place_link" data-wp-seed-place-field="new_link" value="" />
		<input type="hidden" name="wp_seed_new_place_link_visible" data-wp-seed-place-field="new_link_visible" value="1" />
		<input type="hidden" name="wp_seed_update_place_id" data-wp-seed-place-field="update_id" value="" />
		<input type="hidden" name="wp_seed_update_place_name" data-wp-seed-place-field="update_name" value="" />
		<input type="hidden" name="wp_seed_update_place_address" data-wp-seed-place-field="update_address" value="" />
		<input type="hidden" name="wp_seed_update_place_link" data-wp-seed-place-field="update_link" value="" />
		<input type="hidden" name="wp_seed_update_place_link_visible" data-wp-seed-place-field="update_link_visible" value="" />

		<div data-wp-seed-place-summary>
			<?php if ( $selected_place ) : ?>
				<p style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
					<strong>📍 <span data-wp-seed-place-summary-name><?php echo esc_html( $selected_place->post_title ); ?></span></strong><br />
					<span data-wp-seed-place-summary-address><?php echo esc_html( $place_address ); ?></span>
					<?php if ( '' !== $place_link ) : ?>
						<br /><a data-wp-seed-place-summary-link href="<?php echo esc_url( $place_link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $place_link ); ?></a>
					<?php else : ?>
						<span data-wp-seed-place-summary-link hidden></span>
					<?php endif; ?>
					<?php if ( '' !== $place_details ) : ?>
						<br /><br />
						<strong>Informations complémentaires</strong><br />
						<span data-wp-seed-place-summary-details><?php echo nl2br( esc_html( $place_details ) ); ?></span>
					<?php else : ?>
						<span data-wp-seed-place-summary-details hidden></span>
					<?php endif; ?>
					<br />
					<span style="font-size: 12px;" data-wp-seed-place-summary-actions>
						<button type="button" class="button-link" data-wp-seed-place-edit>Modifier</button>
						<span aria-hidden="true"> · </span>
						<button type="button" class="button-link" data-wp-seed-place-choose>Changer de lieu</button>
						<span aria-hidden="true"> · </span>
						<?php wp_seed_events_render_remove_from_event_button( 'place' ); ?>
					</span>
				</p>
			<?php else : ?>
				<p data-wp-seed-place-empty>📍 Aucun lieu</p>
				<p>
					<button type="button" class="button" data-wp-seed-place-choose>Choisir ou créer un lieu</button>
				</p>
			<?php endif; ?>
		</div>

		<div data-wp-seed-place-panel hidden>
			<h4 data-wp-seed-place-panel-title>Choisir ou créer un lieu</h4>
			<p>
				<label>
					Nom<br />
					<input type="search" data-wp-seed-place-panel-field="name" data-wp-seed-place-autocomplete autocomplete="off" value="" />
				</label>
			</p>
			<?php if ( array() !== $suggestions ) : ?>
				<ul data-wp-seed-place-suggestions>
					<?php foreach ( $suggestions as $place ) : ?>
						<?php
						$suggestion_address = get_post_meta( $place->ID, '_wp_seed_place_address', true );
						$suggestion_link    = get_post_meta( $place->ID, '_wp_seed_place_link', true );
						$suggestion_link_visible = wp_seed_events_place_url_is_visible( $place->ID );
						$suggestion_details = get_post_meta( $place->ID, '_wp_seed_place_details', true );
						$suggestion_search  = implode( ' ', array( $place->post_title, $suggestion_address ) );
						?>
						<li data-wp-seed-place-suggestion-item data-wp-seed-place-search="<?php echo esc_attr( $suggestion_search ); ?>">
							<button
								type="button"
								class="button-link"
								data-wp-seed-place-suggestion
								data-wp-seed-place-id="<?php echo esc_attr( (string) $place->ID ); ?>"
								data-wp-seed-place-name="<?php echo esc_attr( $place->post_title ); ?>"
								data-wp-seed-place-address="<?php echo esc_attr( $suggestion_address ); ?>"
							data-wp-seed-place-link="<?php echo esc_attr( $suggestion_link ); ?>"
							data-wp-seed-place-link-visible="<?php echo $suggestion_link_visible ? '1' : '0'; ?>"
							data-wp-seed-place-details="<?php echo esc_attr( $suggestion_details ); ?>"
							>
								<?php echo esc_html( $place->post_title ); ?>
							</button>
						</li>
					<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			<p>
				<label>
					Adresse (facultatif)<br />
					<input type="text" data-wp-seed-place-panel-field="address" value="" />
				</label>
			</p>
			<p>
				<label>
					URL (facultative)<br />
					<input type="url" data-wp-seed-place-panel-field="link" value="" />
				</label>
			</p>
			<p>
				<strong>Affichage</strong><br />
				<label><input type="checkbox" data-wp-seed-place-panel-field="link_visible" value="1" <?php checked( $place_link_visible ); ?> /> Afficher cette URL publiquement</label>
			</p>
			<p>
				<label>
					Informations complémentaires pour cet événement<br />
					<textarea name="wp_seed_event_place_details" data-wp-seed-place-panel-field="details" rows="3" style="width: 100%;"><?php echo esc_textarea( $place_details ); ?></textarea>
				</label>
			</p>
			<p>
				<button type="button" class="button button-primary" data-wp-seed-place-save>Enregistrer le lieu</button>
				<button type="button" class="button" data-wp-seed-place-cancel>Annuler</button>
			</p>
		</div>
	</div>
	<?php
}
function wp_seed_events_add_place_address_meta_box() {
	add_meta_box(
		'wp_seed_events_place_address',
		'Adresse',
		'wp_seed_events_render_place_address_meta_box',
		'wp_seed_place',
		'normal',
		'default'
	);
}

function wp_seed_events_render_place_address_meta_box( $post ) {
	$address = get_post_meta( $post->ID, '_wp_seed_place_address', true );
	$link    = get_post_meta( $post->ID, '_wp_seed_place_link', true );
	$link_visible = wp_seed_events_place_url_is_visible( $post->ID );
	$details      = get_post_meta( $post->ID, '_wp_seed_place_details', true );

	wp_nonce_field( 'wp_seed_events_save_place_address', 'wp_seed_events_place_address_nonce' );
	?>
	<p>
		<label>
			Adresse<br />
			<input type="text" name="wp_seed_place_address" value="<?php echo esc_attr( $address ); ?>" />
		</label>
	</p>
	<p>
		<label>
			URL<br />
			<input type="url" name="wp_seed_place_link" value="<?php echo esc_attr( $link ); ?>" />
		</label>
	</p>
	<p>
		<strong>Affichage</strong><br />
		<label><input type="checkbox" name="wp_seed_place_link_visible" value="1" <?php checked( $link_visible ); ?> /> Afficher cette URL publiquement</label>
	</p>
	<p>
		<label>
			Informations complémentaires (facultatives)<br />
			<textarea name="wp_seed_place_details" rows="3"><?php echo esc_textarea( $details ); ?></textarea>
		</label>
	</p>
	<?php
}

function wp_seed_events_save_event_place( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_place_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_place_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_event_place' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$place_id             = isset( $_POST['wp_seed_event_place_id'] ) ? (int) $_POST['wp_seed_event_place_id'] : 0;
	$place_name           = isset( $_POST['wp_seed_new_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_place_name'] ) ) : '';
	$address              = isset( $_POST['wp_seed_new_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_place_address'] ) ) : '';
	$link                 = isset( $_POST['wp_seed_new_place_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_new_place_link'] ) ) : '';
	$link_visible         = isset( $_POST['wp_seed_new_place_link_visible'] ) && '1' === (string) wp_unslash( $_POST['wp_seed_new_place_link_visible'] );
	$update_place_id      = isset( $_POST['wp_seed_update_place_id'] ) ? (int) $_POST['wp_seed_update_place_id'] : 0;
	$update_place_name    = isset( $_POST['wp_seed_update_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_name'] ) ) : '';
	$update_place_address = isset( $_POST['wp_seed_update_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_address'] ) ) : '';
	$update_place_link    = isset( $_POST['wp_seed_update_place_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_update_place_link'] ) ) : '';
	$update_link_visible  = isset( $_POST['wp_seed_update_place_link_visible'] ) && '1' === (string) wp_unslash( $_POST['wp_seed_update_place_link_visible'] );
	$place_details        = isset( $_POST['wp_seed_event_place_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wp_seed_event_place_details'] ) ) : '';

	if ( '' !== $place_details ) {
		update_post_meta( $post_id, '_wp_seed_event_place_details', $place_details );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_place_details' );
	}

	if ( $update_place_id > 0 && 'wp_seed_place' === get_post_type( $update_place_id ) && current_user_can( 'edit_post', $update_place_id ) ) {
		if ( '' !== $update_place_name ) {
			wp_update_post(
				array(
					'ID'         => $update_place_id,
					'post_title' => $update_place_name,
				)
			);
		}

		if ( '' !== $update_place_address ) {
			update_post_meta( $update_place_id, '_wp_seed_place_address', $update_place_address );
		} else {
			delete_post_meta( $update_place_id, '_wp_seed_place_address' );
		}

		if ( '' !== $update_place_link ) {
			update_post_meta( $update_place_id, '_wp_seed_place_link', $update_place_link );
		} else {
			delete_post_meta( $update_place_id, '_wp_seed_place_link' );
		}
		update_post_meta( $update_place_id, '_wp_seed_place_link_visible', $update_link_visible && '' !== $update_place_link ? '1' : '0' );

		$place_id = $update_place_id;
	}

	if ( '' !== $place_name ) {
		$new_place_id = wp_insert_post(
			array(
				'post_type'   => 'wp_seed_place',
				'post_status' => 'publish',
				'post_title'  => $place_name,
				'post_author' => get_current_user_id(),
			)
		);

		if ( ! is_wp_error( $new_place_id ) && $new_place_id ) {
			$place_id = (int) $new_place_id;

			if ( '' !== $address ) {
				update_post_meta( $place_id, '_wp_seed_place_address', $address );
			}

			if ( '' !== $link ) {
				update_post_meta( $place_id, '_wp_seed_place_link', $link );
			}
			update_post_meta( $place_id, '_wp_seed_place_link_visible', $link_visible && '' !== $link ? '1' : '0' );
			if ( '' !== $place_details ) {
				update_post_meta( $place_id, '_wp_seed_place_details', $place_details );
			}
		}
	}

	if ( $place_id > 0 && 'wp_seed_place' === get_post_type( $place_id ) ) {
		update_post_meta( $post_id, '_wp_seed_event_place_id', $place_id );
		return;
	}

	delete_post_meta( $post_id, '_wp_seed_event_place_id' );
}

function wp_seed_events_save_place_address( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_place_address_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_place_address_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_place_address' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$address = isset( $_POST['wp_seed_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_place_address'] ) ) : '';
	$link    = isset( $_POST['wp_seed_place_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_place_link'] ) ) : '';
	$link_visible = isset( $_POST['wp_seed_place_link_visible'] ) && '1' === (string) wp_unslash( $_POST['wp_seed_place_link_visible'] );
	$details      = isset( $_POST['wp_seed_place_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wp_seed_place_details'] ) ) : '';

	if ( '' === $address ) {
		delete_post_meta( $post_id, '_wp_seed_place_address' );
	} else {
		update_post_meta( $post_id, '_wp_seed_place_address', $address );
	}

	if ( '' === $link ) {
		delete_post_meta( $post_id, '_wp_seed_place_link' );
	} else {
		update_post_meta( $post_id, '_wp_seed_place_link', $link );
	}
	update_post_meta( $post_id, '_wp_seed_place_link_visible', $link_visible && '' !== $link ? '1' : '0' );
	if ( '' === $details ) {
		delete_post_meta( $post_id, '_wp_seed_place_details' );
	} else {
		update_post_meta( $post_id, '_wp_seed_place_details', $details );
	}
}

function wp_seed_events_contact_roles() {
	return wp_seed_events_person_type_options();
}

function wp_seed_events_default_contact_roles() {
	$available_roles = wp_seed_events_contact_roles();
	$default_roles   = get_option( 'wp_seed_events_default_contact_roles', array( 'speaker' ) );

	if ( ! is_array( $default_roles ) ) {
		$default_roles = array( 'speaker' );
	}

	$default_roles = array_map( 'wp_seed_events_canonical_contact_role', $default_roles );
	$default_roles = array_values(
		array_filter(
			array_unique( array_map( 'sanitize_key', $default_roles ) ),
			function ( $role_key ) use ( $available_roles ) {
				return isset( $available_roles[ $role_key ] );
			}
		)
	);

	return array() === $default_roles ? array( 'speaker' ) : $default_roles;
}

function wp_seed_events_person_key_from_name( $person_name, $existing_people = array() ) {
	$base_key = sanitize_key( sanitize_title( $person_name ) );

	if ( '' === $base_key ) {
		$base_key = 'personne';
	}

	$person_key = $base_key;
	$index      = 2;

	while ( isset( $existing_people[ $person_key ] ) ) {
		$person_key = $base_key . '-' . $index;
		$index++;
	}

	return $person_key;
}

function wp_seed_events_sanitize_person( $person, $person_key = '' ) {
	if ( ! is_array( $person ) ) {
		return array();
	}

	$person_key = '' !== $person_key ? sanitize_key( $person_key ) : sanitize_key( $person['person_key'] ?? '' );
	$name       = isset( $person['name'] ) ? sanitize_text_field( $person['name'] ) : '';

	if ( '' === $name ) {
		return array();
	}

	if ( '' === $person_key ) {
		$person_key = wp_seed_events_person_key_from_name( $name );
	}

	$coordinates = wp_seed_events_normalize_person_coordinates( $person );

	return array(
		'person_key'    => $person_key,
		'name'          => $name,
		'phone'         => $coordinates['phone'],
		'email'         => $coordinates['email'],
		'link'          => $coordinates['link'],
		'website_label' => wp_seed_events_normalize_person_website_label( $person['website_label'] ?? '' ),
	);
}

function wp_seed_events_stored_people() {
	$people = get_option( 'wp_seed_events_people', array() );

	if ( ! is_array( $people ) ) {
		return array();
	}

	$clean_people = array();

	foreach ( $people as $person_key => $person ) {
		$person_key = sanitize_key( $person_key );
		$person     = wp_seed_events_sanitize_person( $person, $person_key );

		if ( array() !== $person ) {
			$clean_people[ $person['person_key'] ] = $person;
		}
	}

	return $clean_people;
}

function wp_seed_events_save_people( $people ) {
	$clean_people = array();

	foreach ( $people as $person_key => $person ) {
		$person = wp_seed_events_sanitize_person( $person, $person_key );

		if ( array() !== $person ) {
			$clean_people[ $person['person_key'] ] = $person;
		}
	}

	if ( array() === $clean_people ) {
		delete_option( 'wp_seed_events_people' );
		return;
	}

	update_option( 'wp_seed_events_people', $clean_people, false );
}

function wp_seed_events_contact_person_key( $contact ) {
	return isset( $contact['person_key'] ) ? sanitize_key( $contact['person_key'] ) : '';
}

function wp_seed_events_people() {
	$people = wp_seed_events_stored_people();

	uasort(
		$people,
		function ( $first_person, $second_person ) {
			return strcasecmp( $first_person['name'], $second_person['name'] );
		}
	);

	return $people;
}
function wp_seed_events_update_person_in_events( $person_key, $person ) {
	$person_key = sanitize_key( $person_key );
	$person     = wp_seed_events_sanitize_person( $person, $person_key );

	if ( '' === $person_key || array() === $person ) {
		return;
	}

	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => array(
				array(
					'key'     => '_wp_seed_event_contacts',
					'compare' => 'EXISTS',
				),
			),
		)
	);

	foreach ( $event_ids as $event_id ) {
		$contacts = get_post_meta( $event_id, '_wp_seed_event_contacts', true );

		if ( ! is_array( $contacts ) ) {
			continue;
		}

		$has_changes = false;
		$has_association = false;

		foreach ( $contacts as &$contact ) {
			if ( ! is_array( $contact ) ) {
				continue;
			}

			$contact_person_key = isset( $contact['person_key'] ) ? sanitize_key( $contact['person_key'] ) : '';

			if ( $person_key !== $contact_person_key ) {
				continue;
			}

			$has_association = true;

			if ( (string) ( $contact['name'] ?? '' ) !== $person['name'] ) {
				$contact['name'] = $person['name'];
				$has_changes     = true;
			}

			$stored_coordinates = wp_seed_events_normalize_person_coordinates( $contact );

			foreach ( wp_seed_events_contact_publication_fields() as $coordinate_key => $publication_key ) {
				if ( 'link' === $coordinate_key ) {
					if ( '' !== $stored_coordinates['link'] && $stored_coordinates['link'] !== $person['link'] ) {
						$contact[ $publication_key ] = false;
					}
					if ( array_key_exists( 'link', $contact ) ) {
						unset( $contact['link'] );
						$has_changes = true;
					}
					continue;
				}
				if ( $stored_coordinates[ $coordinate_key ] === $person[ $coordinate_key ] ) {
					continue;
				}

				$contact[ $coordinate_key ]  = $person[ $coordinate_key ];
				$contact[ $publication_key ] = false;
				$has_changes                 = true;
			}
		}
		unset( $contact );

		if ( $has_changes ) {
			update_post_meta( $event_id, '_wp_seed_event_contacts', $contacts );
		}

		if ( $has_association && function_exists( 'wp_seed_events_dynamic_data_invalidate_event_cache' ) ) {
			wp_seed_events_dynamic_data_invalidate_event_cache( $event_id );
		}
	}
}
function wp_seed_events_people_admin_url( $message = '' ) {
	$args = array(
		'post_type' => 'wp_seed_event',
		'page'      => 'wp-seed-event-people',
	);

	if ( '' !== $message ) {
		$args['wp_seed_events_message'] = $message;
	}

	return add_query_arg( $args, admin_url( 'edit.php' ) );
}

function wp_seed_events_render_people_admin_page() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les personnes.' );
	}

	$message = isset( $_GET['wp_seed_events_message'] ) ? sanitize_key( wp_unslash( $_GET['wp_seed_events_message'] ) ) : '';
	$people  = wp_seed_events_people();
	?>
	<div class="wrap" data-wp-seed-people-admin>
		<h1>Toutes les personnes</h1>

		<?php if ( 'people_saved' === $message ) : ?>
			<div class="notice notice-success is-dismissible"><p>Personnes enregistrées.</p></div>

		<?php endif; ?>

		<div id="col-container" class="wp-clearfix">
			<div id="col-left">
				<div class="col-wrap">
					<h2>Ajouter une personne</h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wp_seed_events_save_people" />
						<input type="hidden" name="wp_seed_person_admin_action" value="add" />
						<?php wp_nonce_field( 'wp_seed_events_save_people', 'wp_seed_events_people_nonce' ); ?>

						<div class="form-field term-name-wrap">
							<label for="wp-seed-new-person-name">Nom</label>
							<input id="wp-seed-new-person-name" type="text" name="wp_seed_person_name" value="" />
						</div>

						<fieldset>
							<legend>Coordonnées</legend>
							<div class="form-field">
								<label for="wp-seed-new-person-phone">Téléphone (facultatif)</label>
								<input id="wp-seed-new-person-phone" type="text" name="wp_seed_person_phone" value="" />
							</div>

							<div class="form-field">
								<label for="wp-seed-new-person-email">Email (facultatif)</label>
								<input id="wp-seed-new-person-email" type="text" name="wp_seed_person_email" value="" />
							</div>

							<fieldset>
								<legend>Site</legend>
								<div class="form-field">
									<label for="wp-seed-new-person-link">URL (facultative)</label>
									<input id="wp-seed-new-person-link" type="url" name="wp_seed_person_link" value="" />
								</div>

								<div class="form-field">
									<label for="wp-seed-new-person-website-label">Nom du site / Texte affiché (facultatif)</label>
									<input id="wp-seed-new-person-website-label" type="text" name="wp_seed_person_website_label" value="" />
									<p>Si ce champ est vide, l’URL est affichée. Une URL est obligatoire lorsque ce champ est renseigné.</p>
								</div>
							</fieldset>
						</fieldset>

						<p class="submit">
							<?php submit_button( 'Ajouter la personne', 'primary', 'submit', false ); ?>
						</p>
					</form>
				</div>
			</div>

			<div id="col-right">
				<div class="col-wrap">
					<h2>Liste des personnes</h2>

					<?php if ( array() === $people ) : ?>
						<p>Aucune personne.</p>
					<?php else : ?>
						<table class="wp-list-table widefat fixed striped table-view-list tags">
							<thead>
								<tr>
									<th scope="col" class="manage-column column-name column-primary">Nom</th>
									<th scope="col" class="manage-column">Téléphone</th>
									<th scope="col" class="manage-column">Email</th>

								</tr>
							</thead>
							<tbody>
								<?php foreach ( $people as $person_key => $person ) : ?>
									<?php

									$field_id    = 'wp-seed-person-name-' . $person_key;
									?>
									<tr data-wp-seed-person-admin-item>
										<td class="name column-name has-row-actions column-primary" data-colname="Nom">
											<strong><?php echo esc_html( $person['name'] ); ?></strong>

											<div class="row-actions">
												<span class="edit"><button type="button" class="button-link" data-wp-seed-person-admin-edit>Modifier</button></span>

										<span class="delete"> | </span>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display: inline;">
											<input type="hidden" name="action" value="wp_seed_events_save_people" />
											<input type="hidden" name="wp_seed_person_admin_action" value="delete" />
											<input type="hidden" name="wp_seed_person_key" value="<?php echo esc_attr( $person_key ); ?>" />
											<?php wp_nonce_field( 'wp_seed_events_save_people', 'wp_seed_events_people_nonce' ); ?>
											<button type="submit" class="button-link delete" onclick="return confirm('Retirer cette personne des suggestions ? Les évènements existants ne seront pas modifiés.');">Supprimer</button>
										</form>
											</div>

											<div data-wp-seed-person-admin-edit-panel hidden style="margin-top: 8px;">
												<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
													<input type="hidden" name="action" value="wp_seed_events_save_people" />
													<input type="hidden" name="wp_seed_person_admin_action" value="update" />
													<input type="hidden" name="wp_seed_person_key" value="<?php echo esc_attr( $person_key ); ?>" />
													<?php wp_nonce_field( 'wp_seed_events_save_people', 'wp_seed_events_people_nonce' ); ?>
													<p><label for="<?php echo esc_attr( $field_id ); ?>">Nom</label><br /><input id="<?php echo esc_attr( $field_id ); ?>" type="text" name="wp_seed_person_name" value="<?php echo esc_attr( $person['name'] ); ?>" /></p>
											<fieldset>
												<legend>Coordonnées</legend>
												<p><label>Téléphone (facultatif)<br /><input type="text" name="wp_seed_person_phone" value="<?php echo esc_attr( $person['phone'] ); ?>" /></label></p>
												<p><label>Email (facultatif)<br /><input type="text" name="wp_seed_person_email" value="<?php echo esc_attr( $person['email'] ); ?>" /></label></p>
												<fieldset>
													<legend>Site</legend>
													<p><label>URL (facultative)<br /><input type="url" name="wp_seed_person_link" value="<?php echo esc_attr( $person['link'] ); ?>" /></label></p>
													<p><label>Nom du site / Texte affiché (facultatif)<br /><input type="text" name="wp_seed_person_website_label" value="<?php echo esc_attr( $person['website_label'] ?? '' ); ?>" /></label><br /><span class="description">Si ce champ est vide, l’URL est affichée. Une URL est obligatoire lorsque ce champ est renseigné.</span></p>
												</fieldset>
											</fieldset>
													<?php submit_button( 'Enregistrer', 'primary small', 'submit', false ); ?>
													<button type="button" class="button button-small" data-wp-seed-person-admin-cancel>Annuler</button>
												</form>
											</div>

											<button type="button" class="toggle-row"><span class="screen-reader-text">Afficher plus de détails</span></button>
										</td>
										<td data-colname="Téléphone"><?php echo esc_html( $person['phone'] ); ?></td>
										<td data-colname="Email"><?php echo esc_html( $person['email'] ); ?></td>

									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<script>
	jQuery(function($){
		$(document).on('click','[data-wp-seed-person-admin-edit]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-person-admin-item]').find('[data-wp-seed-person-admin-edit-panel]').prop('hidden',false).find('input[type="text"]').first().trigger('focus');
		});

		$(document).on('click','[data-wp-seed-person-admin-cancel]',function(e){
			e.preventDefault();
			$(this).closest('[data-wp-seed-person-admin-edit-panel]').prop('hidden',true);
		});
	});
	</script>
	<?php
}

function wp_seed_events_handle_people_admin_form() {
	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_die( 'Vous n’avez pas l’autorisation de gérer les personnes.' );
	}

	$nonce = isset( $_POST['wp_seed_events_people_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_events_people_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_people' ) ) {
		wp_die( 'La vérification de sécurité a échoué.' );
	}

	$admin_action = isset( $_POST['wp_seed_person_admin_action'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_person_admin_action'] ) ) : '';
	$person_key   = isset( $_POST['wp_seed_person_key'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_person_key'] ) ) : '';
	$people       = wp_seed_events_stored_people();

	if ( 'add' === $admin_action || 'update' === $admin_action ) {
		$submitted_link  = isset( $_POST['wp_seed_person_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_person_link'] ) ) : '';
		$submitted_label = isset( $_POST['wp_seed_person_website_label'] ) ? wp_seed_events_normalize_person_website_label( wp_unslash( $_POST['wp_seed_person_website_label'] ) ) : '';
		if ( ! wp_seed_events_website_pair_is_valid( $submitted_link, $submitted_label ) ) {
			wp_die( 'Une URL est obligatoire lorsque le nom du site ou le texte affiché est renseigné.' );
		}
		$person = wp_seed_events_sanitize_person(
			array(
				'name'  => isset( $_POST['wp_seed_person_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_name'] ) ) : '',
				'phone' => isset( $_POST['wp_seed_person_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_phone'] ) ) : '',
				'email' => isset( $_POST['wp_seed_person_email'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_email'] ) ) : '',
				'link'  => $submitted_link,
				'website_label' => $submitted_label,
			),
			'update' === $admin_action ? $person_key : ''
		);

		if ( array() !== $person ) {
			if ( 'add' === $admin_action ) {
				$person['person_key'] = wp_seed_events_person_key_from_name( $person['name'], $people );
				$people[ $person['person_key'] ] = $person;
			} elseif ( '' !== $person_key ) {
				$people[ $person_key ] = $person;
				wp_seed_events_update_person_in_events( $person_key, $person );
			}
		}
	} elseif ( 'delete' === $admin_action && '' !== $person_key ) {
		unset( $people[ $person_key ] );
	}

	wp_seed_events_save_people( $people );
	wp_safe_redirect( wp_seed_events_people_admin_url( 'people_saved' ) );
	exit;
}
function wp_seed_events_add_contacts_meta_box() {
	add_meta_box(
		'wp_seed_events_contacts',
		'Qui contacter ou qui intervient ?',
		'wp_seed_events_render_contacts_meta_box',
		'wp_seed_event',
		'normal',
		'default'
	);
}

function wp_seed_events_contact_role_keys( $contact, $available_roles ) {
	$role_keys = array();
	$raw_roles = array();

	if ( isset( $contact['roles'] ) && is_array( $contact['roles'] ) ) {
		$raw_roles = $contact['roles'];
	} elseif ( isset( $contact['role'] ) && '' !== $contact['role'] ) {
		$raw_roles = array( $contact['role'] );
	}

	foreach ( $raw_roles as $raw_role ) {
		$role = wp_seed_events_canonical_contact_role( $raw_role );

		if ( '' !== $role && ! in_array( $role, $role_keys, true ) ) {
			$role_keys[] = $role;
		}
	}

	return $role_keys;
}

function wp_seed_events_contact_name( $contact ) {
	$name = $contact['name'] ?? '';

	return '' === $name ? 'Personne sans nom' : $name;
}

function wp_seed_events_contact_role_labels( $contact, $roles ) {
	$role_keys   = wp_seed_events_contact_role_keys( $contact, $roles );
	$role_labels = array();

	foreach ( $role_keys as $role_key ) {
		$role_labels[] = isset( $roles[ $role_key ] ) ? $roles[ $role_key ] : wp_seed_events_person_type_label( $role_key );
	}

	return $role_labels;
}
function wp_seed_events_normalize_reusable_label( $label ) {
	return strtolower( trim( remove_accents( $label ) ) );
}

function wp_seed_events_limit_reusable_items( $items, $label_key, $limit ) {
	$unique_items = array();
	$seen_labels  = array();

	foreach ( $items as $item ) {
		if ( ! is_array( $item ) || empty( $item[ $label_key ] ) ) {
			continue;
		}

		$normalized_label = wp_seed_events_normalize_reusable_label( $item[ $label_key ] );

		if ( '' === $normalized_label || isset( $seen_labels[ $normalized_label ] ) ) {
			continue;
		}

		$seen_labels[ $normalized_label ] = true;
		$unique_items[]                  = $item;

		if ( count( $unique_items ) >= $limit ) {
			break;
		}
	}

	return $unique_items;
}

function wp_seed_events_render_contacts_meta_box( $post ) {
	$contacts      = get_post_meta( $post->ID, '_wp_seed_event_contacts', true );
	$roles         = wp_seed_events_contact_roles();
	$default_roles = wp_seed_events_default_contact_roles();
	$suggestions   = wp_seed_events_get_contact_suggestions( $post->ID );
	$stored_people = wp_seed_events_stored_people();

	if ( ! is_array( $contacts ) ) {
		$contacts = array();
	}

	wp_nonce_field( 'wp_seed_events_save_contacts', 'wp_seed_events_contacts_nonce' );
	?>
	<input type="hidden" name="wp_seed_event_people_changed" value="0" data-wp-seed-people-changed />
	<input type="hidden" name="wp_seed_event_people_payload_present" value="1" />
	<div data-wp-seed-people data-next-index="<?php echo esc_attr( (string) count( $contacts ) ); ?>" data-default-roles="<?php echo esc_attr( implode( ',', $default_roles ) ); ?>">
		<div data-wp-seed-people-list>
			<?php foreach ( $contacts as $index => $contact ) : ?>
				<?php
				if ( ! is_array( $contact ) ) {
					continue;
				}
				$person_key   = wp_seed_events_contact_person_key( $contact );
				$stored_person = isset( $stored_people[ $person_key ] ) && is_array( $stored_people[ $person_key ] )
					? $stored_people[ $person_key ]
					: array();
				$contact       = array_merge( $contact, $stored_person );
				if ( empty( $contact['name'] ) ) {
					continue;
				}

				$publication_state  = wp_seed_events_contact_publication_state( $contact );
				$publication_labels = array();

				if ( $publication_state['publish_email'] ) {
					$publication_labels[] = 'Email public';
				}

				if ( $publication_state['publish_phone'] ) {
					$publication_labels[] = 'Téléphone public';
				}

				if ( $publication_state['publish_link'] ) {
					$publication_labels[] = 'Lien public';
				}

				if ( array() === $publication_labels ) {
					$publication_labels[] = 'Coordonnées privées';
				}

				$contact_role_keys = wp_seed_events_contact_role_keys( $contact, $roles );
				$website_label     = isset( $stored_people[ $person_key ] ) ? ( $stored_people[ $person_key ]['website_label'] ?? '' ) : '';
				?>
				<div data-wp-seed-person-item style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
					<input type="hidden" data-wp-seed-person-field="person_key" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][person_key]" value="<?php echo esc_attr( $person_key ); ?>" />
					<input type="hidden" data-wp-seed-person-field="role" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][role]" value="<?php echo esc_attr( $contact_role_keys[0] ?? '' ); ?>" />
					<span data-wp-seed-person-roles>
						<?php foreach ( $contact_role_keys as $role_key ) : ?>
							<input type="hidden" data-wp-seed-person-role name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][roles][]" value="<?php echo esc_attr( $role_key ); ?>" />
						<?php endforeach; ?>
					</span>
					<input type="hidden" data-wp-seed-person-field="name" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( $contact['name'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-person-field="phone" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][phone]" value="<?php echo esc_attr( $contact['phone'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-person-field="email" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][email]" value="<?php echo esc_attr( $contact['email'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-person-field="link" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][link]" value="<?php echo esc_attr( $contact['link'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-person-field="website_label" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][website_label]" value="<?php echo esc_attr( $website_label ); ?>" />
					<input type="hidden" data-wp-seed-person-field="publish_email" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][publish_email]" value="<?php echo $publication_state['publish_email'] ? '1' : '0'; ?>" />
					<input type="hidden" data-wp-seed-person-field="publish_phone" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][publish_phone]" value="<?php echo $publication_state['publish_phone'] ? '1' : '0'; ?>" />
					<input type="hidden" data-wp-seed-person-field="publish_link" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][publish_link]" value="<?php echo $publication_state['publish_link'] ? '1' : '0'; ?>" />
					<input type="hidden" data-wp-seed-person-field="phone_action" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][phone_action]" value="<?php echo esc_attr( wp_seed_events_contact_phone_action_is_explicit( $contact ) ? wp_seed_events_normalize_contact_phone_action( $contact['phone_action'], 'none' ) : '' ); ?>" />
					<input type="hidden" data-wp-seed-person-field="phone_action_explicit" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][phone_action_explicit]" value="<?php echo wp_seed_events_contact_phone_action_is_explicit( $contact ) ? '1' : '0'; ?>" />
					<p style="margin: 0;">
						<strong data-wp-seed-person-name><?php echo esc_html( wp_seed_events_contact_name( $contact ) ); ?></strong><br />
						<span data-wp-seed-person-role-labels>
							<?php foreach ( wp_seed_events_contact_role_labels( $contact, $roles ) as $role_label ) : ?>
								<span><?php echo esc_html( $role_label ); ?></span><br />
							<?php endforeach; ?>
						</span>
						<span data-wp-seed-person-publication-status style="font-size: 12px;"><?php echo esc_html( implode( ' · ', $publication_labels ) ); ?></span><br />
						<span style="font-size: 12px;">
							<button type="button" class="button-link" data-wp-seed-person-move-up <?php disabled( 0 === (int) $index ); ?>>Monter</button>
							<span aria-hidden="true"> · </span>
							<button type="button" class="button-link" data-wp-seed-person-move-down <?php disabled( count( $contacts ) - 1 === (int) $index ); ?>>Descendre</button>
							<span aria-hidden="true"> · </span>
							<button type="button" class="button-link" data-wp-seed-person-edit>Modifier</button>
							<span aria-hidden="true"> · </span>
							<?php wp_seed_events_render_remove_from_event_button( 'person' ); ?>
						</span>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<p data-wp-seed-people-empty <?php echo array() === $contacts ? '' : 'hidden'; ?>>Aucune personne</p>
		<p><button type="button" class="button" data-wp-seed-person-add>+ Ajouter une personne</button></p>
		<div data-wp-seed-person-panel-home></div>

		<div data-wp-seed-person-panel hidden>
			<h4 data-wp-seed-person-panel-title>Ajouter une personne</h4>
			<fieldset>
				<legend>Identité</legend>
			<p>
				<label>
					Nom<br />
					<input type="hidden" data-wp-seed-person-panel-field="person_key" />
					<input type="search" data-wp-seed-person-panel-field="name" data-wp-seed-person-autocomplete autocomplete="off" />
				</label>
			</p>
			<?php if ( array() !== $suggestions ) : ?>
				<p>Suggestions</p>
				<ul data-wp-seed-person-suggestions>
					<?php foreach ( $suggestions as $suggestion ) : ?>
						<li data-wp-seed-person-suggestion-item>
							<button
								type="button"
								class="button-link"
								data-wp-seed-reusable-suggestion
								data-wp-seed-suggestion-key="<?php echo esc_attr( $suggestion['person_key'] ?? '' ); ?>"
								data-wp-seed-suggestion-name="<?php echo esc_attr( $suggestion['name'] ); ?>"
								data-wp-seed-suggestion-phone="<?php echo esc_attr( $suggestion['phone'] ); ?>"
								data-wp-seed-suggestion-email="<?php echo esc_attr( $suggestion['email'] ); ?>"
								data-wp-seed-suggestion-link="<?php echo esc_attr( $suggestion['link'] ); ?>"
								data-wp-seed-suggestion-website-label="<?php echo esc_attr( $suggestion['website_label'] ?? '' ); ?>"
							>
								<?php echo esc_html( $suggestion['name'] ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			</fieldset>
			<fieldset>
				<legend>Coordonnées</legend>
				<p>
					<label>
						Téléphone (facultatif)<br />
						<input type="tel" data-wp-seed-person-panel-field="phone" />
					</label>
				</p>
				<p data-wp-seed-person-publication-field="publish_phone" hidden>
					<input id="wp-seed-person-publish-phone" type="checkbox" data-wp-seed-person-panel-publication="publish_phone" aria-describedby="wp-seed-person-publication-help" disabled />
					<label for="wp-seed-person-publish-phone">Afficher ce téléphone sur la fiche publique de cet événement</label>
				</p>
				<p data-wp-seed-person-phone-action hidden>
					<label for="wp-seed-person-phone-action">Action du téléphone</label><br />
					<select id="wp-seed-person-phone-action" data-wp-seed-person-panel-field="phone_action">
						<option value="none">Aucun</option>
						<option value="call">Appel</option>
						<option value="sms">SMS</option>
					</select>
					<span class="description">Ce choix est propre à cet événement et reste indépendant de l’autorisation de publication.</span>
				</p>
				<p>
					<label>
						Email (facultatif)<br />
						<input type="email" data-wp-seed-person-panel-field="email" />
					</label>
				</p>
				<p data-wp-seed-person-publication-field="publish_email" hidden>
					<input id="wp-seed-person-publish-email" type="checkbox" data-wp-seed-person-panel-publication="publish_email" aria-describedby="wp-seed-person-publication-help" disabled />
					<label for="wp-seed-person-publish-email">Afficher cet email sur la fiche publique de cet événement</label>
				</p>
			</fieldset>
			<fieldset>
				<legend>Site</legend>
				<p>
					<label>
						URL (facultative)<br />
						<input type="url" data-wp-seed-person-panel-field="link" />
					</label>
				</p>
				<p>
					<label>
						Nom du site / Texte affiché (facultatif)<br />
						<input type="text" data-wp-seed-person-panel-field="website_label" />
					</label><br />
					<span class="description">Une URL est obligatoire lorsque ce champ est renseigné.</span>
				</p>
				<p data-wp-seed-person-publication-field="publish_link" hidden>
					<input id="wp-seed-person-publish-link" type="checkbox" data-wp-seed-person-panel-publication="publish_link" aria-describedby="wp-seed-person-publication-help" disabled />
					<label for="wp-seed-person-publish-link">Afficher ce site sur la fiche publique de cet événement</label>
				</p>
			</fieldset>
			<p id="wp-seed-person-publication-help" class="description">Les choix de publication sont propres à cet événement.</p>
			<fieldset>
				<legend>Rôles pour cet événement</legend>
				<?php foreach ( $roles as $role_value => $role_label ) : ?>
					<label>
						<input type="checkbox" data-wp-seed-person-panel-role value="<?php echo esc_attr( $role_value ); ?>" />
						<?php echo esc_html( $role_label ); ?>
					</label><br />
				<?php endforeach; ?>
			</fieldset>
			<fieldset>
				<legend>Actions</legend>
				<button type="button" class="button button-primary" data-wp-seed-person-save>Enregistrer la personne</button>
				<button type="button" class="button" data-wp-seed-person-cancel>Annuler</button>
			</fieldset>
		</div>
		<p class="screen-reader-text" data-wp-seed-person-publication-live aria-live="polite" aria-atomic="true"></p>
		<style>
			[data-wp-seed-people] input[type="checkbox"]:focus-visible,
			[data-wp-seed-people] button:focus-visible {
				outline: 2px solid #2271b1;
				outline-offset: 2px;
			}
		</style>
	</div>
	<?php
}
function wp_seed_events_save_contacts( $post_id ) {
	if ( 'wp_seed_event' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! isset( $_POST['wp_seed_events_contacts_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_contacts_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_contacts' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( ! wp_seed_events_people_submission_has_complete_payload( $_POST ) ) {
		return;
	}

	$raw_contacts      = isset( $_POST['wp_seed_events_contacts'] ) && is_array( $_POST['wp_seed_events_contacts'] ) ? wp_unslash( $_POST['wp_seed_events_contacts'] ) : array();
	$existing_contacts = get_post_meta( $post_id, '_wp_seed_event_contacts', true );
	$available_roles   = wp_seed_events_contact_roles();
	$people            = wp_seed_events_stored_people();
	$contacts          = array();

	if ( ! is_array( $existing_contacts ) ) {
		$existing_contacts = array();
	}

	foreach ( $raw_contacts as $raw_index => $raw_contact ) {
		if ( ! is_array( $raw_contact ) ) {
			continue;
		}

		$raw_roles = array();

		if ( isset( $raw_contact['roles'] ) && is_array( $raw_contact['roles'] ) ) {
			$raw_roles = $raw_contact['roles'];
		} elseif ( isset( $raw_contact['role'] ) && '' !== $raw_contact['role'] ) {
			$raw_roles = array( $raw_contact['role'] );
		}

		$contact_roles = array();

		foreach ( $raw_roles as $raw_role ) {
			$role = wp_seed_events_canonical_contact_role( $raw_role );

			if ( isset( $available_roles[ $role ] ) && ! in_array( $role, $contact_roles, true ) ) {
				$contact_roles[] = $role;
			}
		}

		$name = isset( $raw_contact['name'] ) ? sanitize_text_field( $raw_contact['name'] ) : '';

		if ( '' === $name ) {
			continue;
		}

		$person_key = isset( $raw_contact['person_key'] ) ? sanitize_key( $raw_contact['person_key'] ) : '';

		if ( '' === $person_key ) {
			foreach ( $people as $existing_person_key => $existing_person ) {
				if ( wp_seed_events_normalize_reusable_label( $name ) === wp_seed_events_normalize_reusable_label( $existing_person['name'] ) ) {
					$person_key = $existing_person_key;
					break;
				}
			}
		}

		if ( '' === $person_key ) {
			$person_key = wp_seed_events_person_key_from_name( $name, $people );
		}

		$submitted_contact = array_merge(
			$raw_contact,
			array(
				'person_key' => $person_key,
				'name'       => $name,
			)
		);
		$existing_contact = wp_seed_events_find_existing_contact_association( $submitted_contact, $existing_contacts, $raw_index );
		$is_existing_association = wp_seed_events_contacts_identify_same_association( $submitted_contact, $existing_contact );
		$publication             = wp_seed_events_normalize_contact_publication_for_storage(
			$submitted_contact,
			$existing_contact,
			$is_existing_association
		);
		$phone_action = wp_seed_events_contact_phone_action_for_storage(
			$submitted_contact,
			$existing_contact,
			$is_existing_association
		);

		$stored_person = isset( $people[ $person_key ] ) && is_array( $people[ $person_key ] ) ? $people[ $person_key ] : array();
		$website_label = array_key_exists( 'website_label', $raw_contact )
			? wp_seed_events_normalize_person_website_label( $raw_contact['website_label'] )
			: wp_seed_events_normalize_person_website_label( $stored_person['website_label'] ?? '' );
		if ( ! wp_seed_events_website_pair_is_valid( $publication['link'], $website_label ) ) {
			return;
		}
		$people[ $person_key ] = array(
			'person_key'    => $person_key,
			'name'          => $name,
			'phone'         => $publication['phone'],
			'email'         => $publication['email'],
			'link'          => $publication['link'],
			'website_label' => $website_label,
		);

		$stored_contact = array(
			'person_key'    => $person_key,
			'role'          => $contact_roles[0] ?? '',
			'roles'         => $contact_roles,
			'name'          => $name,
			'phone'         => $publication['phone'],
			'email'         => $publication['email'],
			'link'          => $publication['link'],
			'publish_email' => $publication['publish_email'],
			'publish_phone' => $publication['publish_phone'],
			'publish_link'  => $publication['publish_link'],
		);
		if ( null !== $phone_action ) {
			$stored_contact['phone_action'] = $phone_action;
		}
		$contacts[] = wp_seed_events_person_association_for_storage( $stored_contact );
	}

	if ( array() === $contacts ) {
		delete_post_meta( $post_id, '_wp_seed_event_contacts' );
		return;
	}

	wp_seed_events_save_people( $people );
	update_post_meta( $post_id, '_wp_seed_event_contacts', $contacts );
}
function wp_seed_events_media_fields() {
	return array(
		'_wp_seed_event_flyer_pdf_id' => array(
			'label' => 'Document complémentaire',
			'type'  => 'application/pdf',
		),
	);
}

function wp_seed_events_add_media_meta_box() {
	add_meta_box(
		'wp_seed_events_media',
		'Visuels de communication',
		'wp_seed_events_render_media_meta_box',
		'wp_seed_event',
		'normal',
		'default'
	);
}

function wp_seed_events_remove_native_featured_image_meta_box() {
	remove_meta_box( 'postimagediv', 'wp_seed_event', 'side' );
}

function wp_seed_events_render_media_visual_item( $visual, $featured_image_id ) {
	if ( ! is_array( $visual ) ) {
		return;
	}

	$visual_id = absint( $visual['id'] ?? 0 );

	if ( ! $visual_id ) {
		return;
	}

	$thumbnail         = wp_get_attachment_image( $visual_id, 'thumbnail', false, array( 'style' => 'width:72px;height:72px;object-fit:cover;display:block;' ) );
	$title             = isset( $visual['title'] ) && '' !== $visual['title'] ? (string) $visual['title'] : get_the_title( $visual_id );
	$is_featured_image = $featured_image_id === $visual_id;
	?>
	<p data-wp-seed-illustration-item data-wp-seed-illustration-id="<?php echo esc_attr( (string) $visual_id ); ?>" style="display:flex;gap:12px;align-items:center;margin:0 0 12px;padding:0 0 12px;border-bottom:1px solid #dcdcde;">
		<?php if ( $thumbnail ) : ?>
			<span style="width:72px;min-width:72px;"><?php echo wp_kses_post( $thumbnail ); ?></span>
		<?php endif; ?>
		<span>
			<strong><?php echo esc_html( $title ); ?></strong><br />
			<span data-wp-seed-featured-image-label <?php echo $is_featured_image ? '' : 'hidden'; ?>><strong>Flyer recto</strong></span>
			<button type="button" class="button-link" data-wp-seed-featured-image-set <?php echo $is_featured_image ? 'hidden' : ''; ?>>Définir comme flyer recto</button>
			<button type="button" class="button-link" data-wp-seed-visual-up hidden>Monter</button>
			<button type="button" class="button-link" data-wp-seed-visual-down hidden>Descendre</button>
			<button type="button" class="button-link" data-wp-seed-illustration-remove>Retirer</button>
		</span>
		<input type="hidden" name="wp_seed_event_illustrations[]" value="<?php echo esc_attr( (string) $visual_id ); ?>" />
	</p>
	<?php
}

function wp_seed_events_render_media_meta_box( $post, $event_media = null ) {
	if ( ! is_array( $event_media ) ) {
		$event_media = wp_seed_events_get_event_media( $post->ID );
	}

	$communication_visual = is_array( $event_media['communication_visual'] ?? null ) ? $event_media['communication_visual'] : null;
	$other_visuals        = is_array( $event_media['other_visuals'] ?? null ) ? $event_media['other_visuals'] : array();
	$featured_image_id    = absint( $communication_visual['id'] ?? 0 );

	wp_nonce_field( 'wp_seed_events_save_media', 'wp_seed_events_media_nonce' );
	?>
	<input type="hidden" name="wp_seed_event_media_changed" value="0" data-wp-seed-media-changed />
	<input type="hidden" name="wp_seed_event_visuals_changed" value="0" data-wp-seed-visuals-changed />
	<input type="hidden" name="wp_seed_event_document_changed" value="0" data-wp-seed-document-changed />
	<input type="hidden" name="wp_seed_event_illustrations[]" value="" data-wp-seed-illustrations-payload />
	<input type="hidden" name="wp_seed_event_visuals_empty" value="<?php echo $communication_visual ? '0' : '1'; ?>" data-wp-seed-visuals-empty />
	<h3>Flyer recto</h3>
	<p class="description">Le premier visuel est utilisé comme image principale de l’événement.</p>
	<input type="hidden" name="wp_seed_event_featured_image_id" value="<?php echo esc_attr( (string) $featured_image_id ); ?>" data-wp-seed-featured-image-input />
	<div data-wp-seed-flyer-recto>
		<?php if ( $communication_visual ) : ?>
			<?php wp_seed_events_render_media_visual_item( $communication_visual, $featured_image_id ); ?>
		<?php else : ?>
			<p class="description">Aucun flyer recto choisi.</p>
		<?php endif; ?>
	</div>

	<h3>Autres visuels</h3>
	<p class="description">Ajoutez les autres images de communication de l’événement.</p>
	<div data-wp-seed-illustrations-list>
		<?php if ( $other_visuals ) : ?>
			<?php foreach ( $other_visuals as $visual ) : ?>
				<?php wp_seed_events_render_media_visual_item( $visual, $featured_image_id ); ?>
			<?php endforeach; ?>
		<?php else : ?>
			<p class="description">Aucun autre visuel.</p>
		<?php endif; ?>
	</div>
	<p><button type="button" class="button" data-wp-seed-illustrations-select>Ajouter des visuels</button></p>
	<?php
}

function wp_seed_events_render_media_document_panel( $event_media ) {
	$document_display_name = is_array( $event_media['event_document'] ?? null )
		? sanitize_text_field( (string) ( $event_media['event_document']['display_name_explicit'] ?? '' ) )
		: '';
	?>
	<p class="description">Ajoutez un document PDF complémentaire lié à l’événement.</p>
	<?php
	foreach ( wp_seed_events_media_fields() as $meta_key => $field ) {
		$media_object  = '_wp_seed_event_flyer_pdf_id' === $meta_key && is_array( $event_media['event_document'] ?? null ) ? $event_media['event_document'] : null;
		$attachment_id = absint( $media_object['id'] ?? 0 );
		$document_url  = $attachment_id ? (string) ( $media_object['url'] ?? '' ) : '';
		$document_path = $document_url ? (string) wp_parse_url( $document_url, PHP_URL_PATH ) : '';
		$label         = $document_path ? wp_basename( $document_path ) : (string) ( $media_object['title'] ?? '' );

		if ( $attachment_id && '' === $label ) {
			$label = $document_url;
		}
		?>
		<div data-wp-seed-document-state="<?php echo esc_attr( $meta_key ); ?>">
			<input type="hidden" name="wp_seed_event_media[<?php echo esc_attr( $meta_key ); ?>]" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-wp-seed-media-input="<?php echo esc_attr( $meta_key ); ?>" />
			<p class="description" data-wp-seed-media-empty="<?php echo esc_attr( $meta_key ); ?>" <?php echo $attachment_id ? 'hidden' : ''; ?>>Aucun document sélectionné.</p>
			<p data-wp-seed-media-current="<?php echo esc_attr( $meta_key ); ?>" <?php echo $attachment_id ? '' : 'hidden'; ?>><strong data-wp-seed-media-label="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></strong></p>
			<p>
				<button type="button" class="button" data-wp-seed-media-select="<?php echo esc_attr( $meta_key ); ?>" data-media-type="<?php echo esc_attr( $field['type'] ); ?>" data-title="<?php echo esc_attr( $field['label'] ); ?>"><?php echo $attachment_id ? 'Remplacer le document' : 'Choisir un document PDF'; ?></button>
				<button type="button" class="button-link" data-wp-seed-media-remove="<?php echo esc_attr( $meta_key ); ?>" <?php echo $attachment_id ? '' : 'hidden'; ?>>Retirer</button>
			</p>
			<p class="description">PDF uniquement.</p>
			<p>
				<label for="wp-seed-event-document-display-name">Nom d'affichage (facultatif)</label><br />
				<input id="wp-seed-event-document-display-name" type="text" name="wp_seed_event_document_display_name" value="<?php echo esc_attr( $document_display_name ); ?>" data-wp-seed-document-display-name />
				<br /><span class="description">Si ce champ est vide, le nom du fichier nettoyé est utilisé.</span>
			</p>
		</div>
		<?php
	}
}

function wp_seed_events_enqueue_media_admin( $hook_suffix ) {
	if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || 'wp_seed_event' !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_script( 'jquery' );
	wp_add_inline_script(
		'jquery',
		<<<'JS'
window.wpSeedEventsAdmin=window.wpSeedEventsAdmin||{};
window.wpSeedEventsAdmin.removeFromEventButton=function(attribute){
	return jQuery('<button></button>',{
		type:'button',
		'class':'button-link button-link-delete',
		text:'Retirer de cet événement'
	}).attr(attribute,'');
};
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	var wpSeedCommunicationVisuals=[];

	function wpSeedMarkMediaChanged(){
		$('[data-wp-seed-media-changed]').val('1');
	}

	function wpSeedMarkVisualsChanged(){
		wpSeedMarkMediaChanged();
		$('[data-wp-seed-visuals-changed]').val('1');
	}

	function wpSeedMarkDocumentChanged(){
		wpSeedMarkMediaChanged();
		$('[data-wp-seed-document-changed]').val('1');
	}

	function wpSeedVisualId(visual){
		return String(visual&&visual.id||'');
	}

	function wpSeedDeduplicateVisuals(visuals){
		var seen={};
		var result=[];

		$.each(visuals||[],function(index,visual){
			var id=wpSeedVisualId(visual);

			if(!id||seen[id]){
				return;
			}

			seen[id]=true;
			result.push(visual);
		});

		return result;
	}

	function wpSeedVisualListsEqual(first,second){
		if(first.length!==second.length){
			return false;
		}

		for(var index=0;index<first.length;index++){
			if(wpSeedVisualId(first[index])!==wpSeedVisualId(second[index])){
				return false;
			}
		}

		return true;
	}

	function wpSeedFindVisualIndex(id){
		id=String(id||'');

		for(var index=0;index<wpSeedCommunicationVisuals.length;index++){
			if(wpSeedVisualId(wpSeedCommunicationVisuals[index])===id){
				return index;
			}
		}

		return -1;
	}

	function wpSeedFocusVisualAction(id,preferredSelector){
		var index=wpSeedFindVisualIndex(id);

		if(index<0){
			return;
		}

		var item=wpSeedCommunicationVisuals[index].item;
		var control=item.find(preferredSelector).filter(function(){
			return !this.disabled&&!$(this).prop('hidden');
		}).first();

		if(!control.length){
			control=item.find('button').filter(function(){
				return !this.disabled&&!$(this).prop('hidden');
			}).first();
		}

		if(control.length){
			control.trigger('focus');
			return;
		}

		item.attr('tabindex','-1').trigger('focus');
	}

	function wpSeedRefreshDocumentState(key){
		var input=$('[data-wp-seed-media-input="'+key+'"]');
		var hasDocument=!!String(input.val()||'');

		$('[data-wp-seed-media-empty="'+key+'"]').prop('hidden',hasDocument).toggle(!hasDocument);
		$('[data-wp-seed-media-current="'+key+'"]').prop('hidden',!hasDocument).toggle(hasDocument);
		$('[data-wp-seed-media-remove="'+key+'"]').prop('hidden',!hasDocument).toggle(hasDocument);
		$('[data-wp-seed-media-select="'+key+'"]').text(hasDocument?'Remplacer le document':'Choisir un document PDF');
	}

	function wpSeedIllustrationThumbnail(attachment,label){
		var url='';

		if(attachment.sizes&&attachment.sizes.thumbnail&&attachment.sizes.thumbnail.url){
			url=attachment.sizes.thumbnail.url;
		}else if(attachment.url){
			url=attachment.url;
		}

		if(!url){
			return $();
		}

		return $('<span style="width:72px;min-width:72px;"></span>').append(
			$('<img />').attr({src:url,alt:label}).css({width:'72px',height:'72px',objectFit:'cover',display:'block'})
		);
	}

	function wpSeedCreateVisual(attachment){
		var id=String(attachment&&attachment.id||'');
		var mime=String(attachment&&attachment.mime||'');

		if(!id||(mime&&0!==mime.indexOf('image/'))){
			return null;
		}

		var label=attachment.title||attachment.filename||attachment.url;
		var item=$('<p data-wp-seed-illustration-item style="display:flex;gap:12px;align-items:center;margin:0 0 12px;padding:0 0 12px;border-bottom:1px solid #dcdcde;"></p>');
		var content=$('<span></span>');

		item.attr('data-wp-seed-illustration-id',id);
		item.append(wpSeedIllustrationThumbnail(attachment,label));
		content.append($('<strong></strong>').text(label));
		content.append('<br />');
		content.append($('<span data-wp-seed-featured-image-label hidden><strong>Flyer recto</strong></span>'));
		content.append($('<button type="button" class="button-link" data-wp-seed-featured-image-set>Définir comme flyer recto</button>'));
		content.append(' ');
		content.append($('<button type="button" class="button-link" data-wp-seed-visual-up hidden>Monter</button>'));
		content.append(' ');
		content.append($('<button type="button" class="button-link" data-wp-seed-visual-down hidden>Descendre</button>'));
		content.append(' ');
		content.append($('<button type="button" class="button-link" data-wp-seed-illustration-remove>Retirer</button>'));
		item.append(content);
		item.append($('<input type="hidden" name="wp_seed_event_illustrations[]" />').val(id));

		return {id:id,item:item};
	}

	function wpSeedRefreshCommunicationVisuals(){
		var rectoRoot=$('[data-wp-seed-flyer-recto]');
		var othersRoot=$('[data-wp-seed-illustrations-list]');
		var count=wpSeedCommunicationVisuals.length;
		var featuredInput=$('[data-wp-seed-featured-image-input]');

		rectoRoot.empty();
		othersRoot.empty();
		featuredInput.val(count?wpSeedVisualId(wpSeedCommunicationVisuals[0]):'');
		$('[data-wp-seed-visuals-empty]').val(count?'0':'1');

		if(!count){
			rectoRoot.append($('<p class="description"></p>').text('Aucun flyer recto choisi.'));
			othersRoot.append($('<p class="description"></p>').text('Aucun autre visuel.'));
			return;
		}

		$.each(wpSeedCommunicationVisuals,function(index,visual){
			var item=visual.item;
			var isRecto=0===index;
			var isFirstOther=1===index;
			var isLast=count-1===index;

			item.find('[data-wp-seed-featured-image-label]').prop('hidden',!isRecto).toggle(isRecto);
			item.find('[data-wp-seed-featured-image-set]').prop('hidden',isRecto).toggle(!isRecto);
			item.find('[data-wp-seed-visual-up]').prop('hidden',isRecto).toggle(!isRecto).prop('disabled',isFirstOther);
			item.find('[data-wp-seed-visual-down]').prop('hidden',isRecto).toggle(!isRecto).prop('disabled',isLast);
			item.find('[data-wp-seed-illustration-remove]').prop('disabled',false).removeAttr('aria-disabled');
			item.find('input[name="wp_seed_event_illustrations[]"]').val(visual.id);

			if(isRecto){
				rectoRoot.append(item);
			}else{
				othersRoot.append(item);
			}
		});

		if(1===count){
			othersRoot.append($('<p class="description"></p>').text('Aucun autre visuel.'));
		}
	}

	function wpSeedApplyCommunicationVisuals(nextVisuals){
		var normalized=wpSeedDeduplicateVisuals(nextVisuals);

		if(wpSeedVisualListsEqual(wpSeedCommunicationVisuals,normalized)){
			return false;
		}

		wpSeedCommunicationVisuals=normalized;
		wpSeedRefreshCommunicationVisuals();
		wpSeedMarkVisualsChanged();

		return true;
	}

	function wpSeedInitializeCommunicationVisuals(){
		var initial=[];

		$('[data-wp-seed-illustration-item]').each(function(){
			var item=$(this);
			var id=String(item.attr('data-wp-seed-illustration-id')||'');

			if(id){
				initial.push({id:id,item:item});
			}
		});

		wpSeedCommunicationVisuals=wpSeedDeduplicateVisuals(initial);
		wpSeedRefreshCommunicationVisuals();
	}

	$(document).on('click','[data-wp-seed-media-select]',function(e){
		e.preventDefault();
		var button=$(this);
		var key=button.data('wp-seed-media-select');
		var type=button.data('media-type');
		var frame=wp.media({
			title:button.data('title'),
			button:{text:'Choisir'},
			library:{type:type},
			multiple:false
		});

		frame.on('select',function(){
			var attachment=frame.state().get('selection').first().toJSON();

			if('application/pdf'===type&&attachment.mime&&'application/pdf'!==attachment.mime){
				window.alert('Le document complémentaire doit être un fichier PDF. Utilisez le bouton Ajouter des visuels pour les images.');
				return;
			}

			var input=$('[data-wp-seed-media-input="'+key+'"]');
			var previousId=String(input.val()||'');
			var attachmentId=String(attachment.id||'');

			input.val(attachment.id);
			$('[data-wp-seed-media-label="'+key+'"]').text(attachment.filename||attachment.title||attachment.url);
			wpSeedRefreshDocumentState(key);

			if(previousId!==attachmentId){
				wpSeedMarkDocumentChanged();
			}
		});

		frame.open();
	});

	$(document).on('click','[data-wp-seed-media-remove]',function(e){
		e.preventDefault();
		var key=$(this).data('wp-seed-media-remove');
		var input=$('[data-wp-seed-media-input="'+key+'"]');

		if(!String(input.val()||'')){
			return;
		}

		input.val('');
		$('[data-wp-seed-media-label="'+key+'"]').text('');
		wpSeedRefreshDocumentState(key);
		wpSeedMarkDocumentChanged();
	});

	$(document).on('input','[data-wp-seed-document-display-name]',function(){
		wpSeedMarkDocumentChanged();
	});

	$(document).on('click','[data-wp-seed-illustrations-select]',function(e){
		e.preventDefault();
		var frame=wp.media({
			title:'Ajouter des visuels',
			button:{text:'Ajouter'},
			library:{type:'image'},
			multiple:true
		});

		frame.on('select',function(){
			var next=wpSeedCommunicationVisuals.slice();

			frame.state().get('selection').each(function(attachment){
				var data=attachment.toJSON();
				var id=String(data.id||'');

				if(!id||-1!==wpSeedFindVisualIndex(id)){
					return;
				}

				var visual=wpSeedCreateVisual(data);

				if(visual){
					next.push(visual);
				}
			});

			wpSeedApplyCommunicationVisuals(next);
		});

		frame.open();
	});

	$(document).on('click','[data-wp-seed-featured-image-set]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-illustration-item]');
		var index=wpSeedFindVisualIndex(item.attr('data-wp-seed-illustration-id'));

		if(index<=0){
			return;
		}

		var next=wpSeedCommunicationVisuals.slice();
		var selected=next.splice(index,1)[0];

		next.unshift(selected);

		if(wpSeedApplyCommunicationVisuals(next)){
			wpSeedFocusVisualAction(selected.id,'[data-wp-seed-illustration-remove]');
		}
	});

	$(document).on('click','[data-wp-seed-visual-up]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-illustration-item]');
		var index=wpSeedFindVisualIndex(item.attr('data-wp-seed-illustration-id'));

		if(index<=1){
			return;
		}

		var next=wpSeedCommunicationVisuals.slice();
		var previous=next[index-1];

		next[index-1]=next[index];
		next[index]=previous;

		if(wpSeedApplyCommunicationVisuals(next)){
			wpSeedFocusVisualAction(next[index-1].id,2===index?'[data-wp-seed-visual-down]':'[data-wp-seed-visual-up]');
		}
	});

	$(document).on('click','[data-wp-seed-visual-down]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-illustration-item]');
		var index=wpSeedFindVisualIndex(item.attr('data-wp-seed-illustration-id'));

		if(index<1||index>=wpSeedCommunicationVisuals.length-1){
			return;
		}

		var next=wpSeedCommunicationVisuals.slice();
		var following=next[index+1];

		next[index+1]=next[index];
		next[index]=following;

		if(wpSeedApplyCommunicationVisuals(next)){
			wpSeedFocusVisualAction(next[index+1].id,index+1===next.length-1?'[data-wp-seed-visual-up]':'[data-wp-seed-visual-down]');
		}
	});

	$(document).on('click','[data-wp-seed-illustration-remove]',function(e){
		e.preventDefault();

		var item=$(this).closest('[data-wp-seed-illustration-item]');
		var index=wpSeedFindVisualIndex(item.attr('data-wp-seed-illustration-id'));

		if(index<0){
			return;
		}

		var next=wpSeedCommunicationVisuals.slice();

		next.splice(index,1);
		var focusVisual=next[Math.min(index,next.length-1)];

		if(wpSeedApplyCommunicationVisuals(next)&&focusVisual){
			wpSeedFocusVisualAction(focusVisual.id,'[data-wp-seed-illustration-remove]');
		}
	});

	wpSeedInitializeCommunicationVisuals();
	$('[data-wp-seed-media-input]').each(function(){
		wpSeedRefreshDocumentState($(this).data('wp-seed-media-input'));
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	function placeRoot(element){
		return $(element).closest('[data-wp-seed-place]');
	}

	function placePanel(root){
		return root.find('[data-wp-seed-place-panel]');
	}

	function hiddenField(root,key){
		return root.find('[data-wp-seed-place-field="'+key+'"]');
	}

	function panelField(panel,key){
		return panel.find('[data-wp-seed-place-panel-field="'+key+'"]');
	}

	function clearPlaceActions(root){
		hiddenField(root,'new_name').val('');
		hiddenField(root,'new_address').val('');
		hiddenField(root,'new_link').val('');
		hiddenField(root,'new_link_label').val('');
		hiddenField(root,'new_link_visible').val('1');
		hiddenField(root,'update_id').val('');
		hiddenField(root,'update_name').val('');
		hiddenField(root,'update_address').val('');
		hiddenField(root,'update_link').val('');
		hiddenField(root,'update_link_label').val('');
		hiddenField(root,'update_link_visible').val('');
	}

	function renderPlaceSummary(root,data){
		var summary=root.find('[data-wp-seed-place-summary]');
		summary.empty();

		if(!data.name&&!data.address&&!data.link&&!data.details){
			summary.append($('<p data-wp-seed-place-empty></p>').text('📍 Aucun lieu'));
			summary.append(
				$('<p></p>')
					.append($('<button type="button" class="button" data-wp-seed-place-choose>Choisir ou créer un lieu</button>'))
			);
			return;
		}

		var block=$('<p></p>').css({margin:'0 0 12px',padding:'0 0 12px',borderBottom:'1px solid #dcdcde'});
		block.append($('<strong></strong>').append('📍 ').append($('<span data-wp-seed-place-summary-name></span>').text(data.name||'Lieu sans nom')));
		block.append('<br />');
		block.append($('<span data-wp-seed-place-summary-address></span>').text(data.address||''));

		if(data.link){
			block.append('<br />');
			block.append($('<a data-wp-seed-place-summary-link target="_blank" rel="noopener noreferrer"></a>').attr('href',data.link).attr('data-wp-seed-place-summary-link-label',data.link_label||'').text(data.link_label||data.link));
		}else{
			block.append($('<span data-wp-seed-place-summary-link hidden></span>'));
		}

		if(data.details){
			block.append('<br /><br />');
			block.append($('<strong></strong>').text('Informations complémentaires'));
			block.append('<br />');
			block.append($('<span data-wp-seed-place-summary-details></span>').text(data.details));
		}else{
			block.append($('<span data-wp-seed-place-summary-details hidden></span>'));
		}

		block.append('<br />');
		block.append(
			$('<span></span>').css('fontSize','12px')
				.append($('<button type="button" class="button-link" data-wp-seed-place-edit>Modifier</button>'))
				.append(' · ')
				.append($('<button type="button" class="button-link" data-wp-seed-place-choose>Changer de lieu</button>'))
				.append(' · ')
				.append(window.wpSeedEventsAdmin.removeFromEventButton('data-wp-seed-place-remove'))
		);
		root.attr('data-wp-seed-place-link-visible',data.link_visible?'1':'0');
		summary.append(block);
	}

	function currentPlaceData(root){
		return{
			id:hiddenField(root,'place_id').val(),
			name:root.find('[data-wp-seed-place-summary-name]').text(),
			address:root.find('[data-wp-seed-place-summary-address]').text(),
			link:root.find('[data-wp-seed-place-summary-link]').attr('href')||'',
			link_label:root.find('[data-wp-seed-place-summary-link]').attr('data-wp-seed-place-summary-link-label')||'',
			link_visible:'1'===root.attr('data-wp-seed-place-link-visible'),
			details:root.find('[data-wp-seed-place-summary-details]').text()
		};
	}

	function normalizePlaceSearch(value){
		return String(value||'')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g,'')
			.toLocaleLowerCase();
	}

	function validPlaceLink(value){
		value=String(value||'').trim();
		if(!value){return false;}
		try{
			var parsed=new URL(value);
			return ('http:'===parsed.protocol||'https:'===parsed.protocol)&&!!parsed.hostname;
		}catch(error){
			return false;
		}
	}

	function filterPlaceSuggestions(panel){
		var query=normalizePlaceSearch(panelField(panel,'name').val());
		panel.find('[data-wp-seed-place-suggestion-item]').each(function(){
			var item=$(this);
			item.prop('hidden',!!query&&normalizePlaceSearch(item.attr('data-wp-seed-place-search')).indexOf(query)===-1);
		});
	}

	function openPlacePanel(root,mode,data){
		var panel=placePanel(root);
		data=data||{id:'',name:'',address:'',link:'',link_label:'',link_visible:true,details:''};
		panel.data('wpSeedPlaceMode',mode);
		panel.data('wpSeedPlaceId',data.id||'');
		panel.data('wpSeedSelectedPlaceName',data.id?String(data.name||''):'');
		panel.data('wpSeedPlaceOriginalId',hiddenField(root,'place_id').val()||'');
		panel.find('[data-wp-seed-place-panel-title]').text('edit'===mode?'Modifier le lieu':'Choisir ou créer un lieu');
		panelField(panel,'name').val(data.name||'');
		panelField(panel,'address').val(data.address||'');
		panelField(panel,'link').val(data.link||'');
		panelField(panel,'link_label').val(data.link_label||'');
		panelField(panel,'link_visible').prop('checked',false!==data.link_visible);
		panelField(panel,'details').val(data.details||'');
		panel.prop('hidden',false);
		filterPlaceSuggestions(panel);
		panelField(panel,'name').trigger('focus');
	}

	$(document).on('click','[data-wp-seed-place-choose]',function(e){
		e.preventDefault();
		var root=placeRoot(this);
		openPlacePanel(root,'choose',{id:'',name:'',address:'',link:'',link_label:'',link_visible:true,details:currentPlaceData(root).details||''});
	});

	$(document).on('click','[data-wp-seed-place-edit]',function(e){
		e.preventDefault();
		openPlacePanel(placeRoot(this),'edit',currentPlaceData(placeRoot(this)));
	});

	$(document).on('click','[data-wp-seed-place-suggestion]',function(e){
		e.preventDefault();
		var panel=placePanel(placeRoot(this));
		panel.data('wpSeedPlaceId',String($(this).attr('data-wp-seed-place-id')||''));
		var selectedName=$(this).attr('data-wp-seed-place-name')||'';
		panel.data('wpSeedSelectedPlaceName',String(selectedName));
		panelField(panel,'name').val(selectedName);
		panelField(panel,'address').val($(this).attr('data-wp-seed-place-address')||'');
		panelField(panel,'link').val($(this).attr('data-wp-seed-place-link')||'');
		panelField(panel,'link_label').val($(this).attr('data-wp-seed-place-link-label')||'');
		panelField(panel,'link_visible').prop('checked','1'===$(this).attr('data-wp-seed-place-link-visible'));
		filterPlaceSuggestions(panel);
	});

	$(document).on('input','[data-wp-seed-place-autocomplete]',function(){
		var panel=$(this).closest('[data-wp-seed-place-panel]');
		var selectedName=String(panel.data('wpSeedSelectedPlaceName')||'');

		if(panel.data('wpSeedPlaceId')&&String($(this).val()).trim()!==selectedName.trim()){
			panel.data('wpSeedPlaceId','');
			panel.data('wpSeedSelectedPlaceName','');
		}
		filterPlaceSuggestions(panel);
	});

	$(document).on('click','[data-wp-seed-place-save]',function(e){
		e.preventDefault();
		var root=placeRoot(this);
		var panel=placePanel(root);
		var data={
			id:String(panel.data('wpSeedPlaceId')||''),
			name:panelField(panel,'name').val(),
			address:panelField(panel,'address').val(),
			link:panelField(panel,'link').val(),
			link_label:panelField(panel,'link_label').val(),
			link_visible:panelField(panel,'link_visible').prop('checked'),
			details:panelField(panel,'details').val()
		};
		var mode=String(panel.data('wpSeedPlaceMode')||'choose');
		var originalId=String(panel.data('wpSeedPlaceOriginalId')||'');

		if(!data.name){
			panelField(panel,'name').trigger('focus');
			return;
		}

		if(String(data.link_label||'').trim()&&!validPlaceLink(data.link)){
			var linkInput=panelField(panel,'link').get(0);
			linkInput.setCustomValidity('Une URL est obligatoire lorsque le nom du site ou le texte affiché est renseigné.');
			linkInput.reportValidity();
			panelField(panel,'link').trigger('focus');
			return;
		}
		panelField(panel,'link').get(0).setCustomValidity('');

		clearPlaceActions(root);
		panelField(panel,'details').val(data.details);

		if(data.id){
			hiddenField(root,'place_id').val(data.id);

			if('edit'===mode&&data.id===originalId){
				hiddenField(root,'update_id').val(data.id);
				hiddenField(root,'update_name').val(data.name);
				hiddenField(root,'update_address').val(data.address);
				hiddenField(root,'update_link').val(data.link);
				hiddenField(root,'update_link_label').val(data.link_label);
				hiddenField(root,'update_link_visible').val(data.link_visible?'1':'0');
			}
		}else{
			hiddenField(root,'place_id').val('');
			hiddenField(root,'new_name').val(data.name);
			hiddenField(root,'new_address').val(data.address);
			hiddenField(root,'new_link').val(data.link);
			hiddenField(root,'new_link_label').val(data.link_label);
			hiddenField(root,'new_link_visible').val(data.link_visible?'1':'0');
		}

		renderPlaceSummary(root,data);
		panel.prop('hidden',true);
	});

	$(document).on('click','[data-wp-seed-place-cancel]',function(e){
		e.preventDefault();
		placePanel(placeRoot(this)).prop('hidden',true);
	});

	$(document).on('click','[data-wp-seed-place-remove]',function(e){
		e.preventDefault();
		var root=placeRoot(this);
		clearPlaceActions(root);
		hiddenField(root,'place_id').val('');
		panelField(placePanel(root),'details').val('');
		renderPlaceSummary(root,{id:'',name:'',address:'',link:'',link_label:'',link_visible:true,details:''});
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	function syncSecondaryTypes(root,previousPrimary){
		var defaultType=String(root.attr('data-default-type')||'');
		var primary=String(root.find('[data-wp-seed-event-primary-type]').val()||'');

		root.find('[data-wp-seed-event-type-option]').each(function(){
			var item=$(this);
			var key=String(item.attr('data-type-key')||'');
			var checkbox=item.find('input[type="checkbox"]');

			if(primary&&key===primary){
				checkbox.prop('checked',true);
				item.prop('hidden',true).hide();
			}else{
				item.prop('hidden',false).show();
			}
		});

		if(previousPrimary&&previousPrimary!==primary){
			root.find('[data-wp-seed-event-type-option]').filter(function(){
				return String($(this).attr('data-type-key')||'')===previousPrimary;
			}).find('input[type="checkbox"]').prop('checked',previousPrimary!==defaultType);
		}

		root.data('wpSeedPreviousPrimary',primary);
	}

	$('[data-wp-seed-event-type]').each(function(){
		syncSecondaryTypes($(this),'');
	});

	$(document).on('change','[data-wp-seed-event-primary-type]',function(){
		var root=$(this).closest('[data-wp-seed-event-type]');
		syncSecondaryTypes(root,String(root.data('wpSeedPreviousPrimary')||''));
	});

});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	var publicationConfig={
		publish_email:{
			coordinate:'email',
			message:'La publication de l’email a été désactivée après sa modification.'
		},
		publish_phone:{
			coordinate:'phone',
			message:'La publication du téléphone a été désactivée après sa modification.'
		},
		publish_link:{
			coordinate:'link',
			message:'La publication du lien a été désactivée après sa modification.'
		}
	};

	function root(element){
		return $(element).closest('[data-wp-seed-people]');
	}

	function panel(peopleRoot){
		return peopleRoot.find('[data-wp-seed-person-panel]');
	}

	function markChanged(peopleRoot){
		peopleRoot.closest('form').find('[data-wp-seed-people-changed]').val('1');
	}

	function announce(peopleRoot,message){
		var live=peopleRoot.find('[data-wp-seed-person-publication-live]');
		live.text('');
		window.setTimeout(function(){
			live.text(message);
		},0);
	}

	function field(personPanel,key){
		return personPanel.find('[data-wp-seed-person-panel-field="'+key+'"]');
	}

	function publicationField(personPanel,key){
		return personPanel.find('[data-wp-seed-person-panel-publication="'+key+'"]');
	}

	function roleFields(personPanel){
		return personPanel.find('[data-wp-seed-person-panel-role]');
	}

	function refreshEmpty(peopleRoot){
		peopleRoot.find('[data-wp-seed-people-empty]').prop('hidden',peopleRoot.find('[data-wp-seed-person-item]').length>0);
	}

	function refreshPeopleOrder(peopleRoot){
		var items=peopleRoot.find('[data-wp-seed-person-item]');
		items.each(function(index){
			var item=$(this);
			item.find('[data-wp-seed-person-field]').each(function(){
				var field=$(this);
				var key=field.attr('data-wp-seed-person-field');
				field.attr('name','wp_seed_events_contacts['+index+']['+key+']');
			});
			item.find('[data-wp-seed-person-role]').attr('name','wp_seed_events_contacts['+index+'][roles][]');
			item.find('[data-wp-seed-person-move-up]').prop('disabled',0===index);
			item.find('[data-wp-seed-person-move-down]').prop('disabled',items.length-1===index);
		});
		peopleRoot.attr('data-next-index',items.length);
	}

	function roleLabel(value){
		var option=$('[data-wp-seed-person-panel-role][value="'+value+'"]').first();
		return option.length?option.closest('label').text().trim():'';
	}

	function roleLabels(values){
		return $.map(values,function(value){
			return roleLabel(value);
		}).filter(function(label){
			return ''!==label;
		});
	}

	function writeRoleLabels(item,roles){
		var labelsContainer=item.find('[data-wp-seed-person-role-labels]');
		labelsContainer.empty();
		$.each(roleLabels(roles||[]),function(_,label){
			labelsContainer.append($('<span></span>').text(label)).append('<br />');
		});
	}

	function readRoles(item){
		var roles=[];
		item.find('[data-wp-seed-person-role]').each(function(){
			roles.push($(this).val());
		});
		if(!roles.length&&item.find('[data-wp-seed-person-field="role"]').val()){
			roles.push(item.find('[data-wp-seed-person-field="role"]').val());
		}
		return roles;
	}

	function readPanelRoles(personPanel){
		var roles=[];
		roleFields(personPanel).filter(':checked').each(function(){
			roles.push($(this).val());
		});
		return roles;
	}

	function defaultRoles(peopleRoot){
		return String(peopleRoot.attr('data-default-roles')||'').split(',').filter(function(role){
			return ''!==role;
		});
	}

	function writePanelRoles(personPanel,roles){
		roleFields(personPanel).prop('checked',false);
		$.each(roles||[],function(_,role){
			roleFields(personPanel).filter('[value="'+role+'"]').prop('checked',true);
		});
	}

	function isAuthorized(value){
		return true===value||1===value||'1'===String(value);
	}

	function validEmail(value){
		value=String(value||'').trim();
		return ''!==value&&/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
	}

	function validPhone(value){
		value=String(value||'').trim();
		if(''===value||!(/^\+?[0-9\s().\/-]+$/).test(value)){
			return false;
		}
		var digits=value.replace(/\D+/g,'');
		return digits.length>=6&&digits.length<=15;
	}

	function validLink(value){
		value=String(value||'').trim();
		if(''===value){
			return false;
		}
		try{
			var parsed=new URL(value);
			return ('http:'===parsed.protocol||'https:'===parsed.protocol)&&''!==parsed.hostname;
		}catch(error){
			return false;
		}
	}

	function coordinateIsValid(key,value){
		if('email'===key){
			return validEmail(value);
		}
		if('phone'===key){
			return validPhone(value);
		}
		return validLink(value);
	}

	function normalizeCoordinate(key,value){
		value=String(value||'').trim();
		if('phone'===key){
			return value.replace(/\s+/g,' ');
		}
		return value;
	}

	function normalizePhoneAction(value,fallback){
		value=String(value||'').toLowerCase();
		return ['none','call','sms'].indexOf(value)!==-1?value:(fallback||'none');
	}

	function readItem(item){
		return{
			person_key:item.find('[data-wp-seed-person-field="person_key"]').val()||'',
			roles:readRoles(item),
			name:item.find('[data-wp-seed-person-field="name"]').val()||'',
			phone:item.find('[data-wp-seed-person-field="phone"]').val()||'',
			email:item.find('[data-wp-seed-person-field="email"]').val()||'',
			link:item.find('[data-wp-seed-person-field="link"]').val()||'',
			website_label:item.find('[data-wp-seed-person-field="website_label"]').val()||'',
			publish_email:item.find('[data-wp-seed-person-field="publish_email"]').val()||'0',
			publish_phone:item.find('[data-wp-seed-person-field="publish_phone"]').val()||'0',
			publish_link:item.find('[data-wp-seed-person-field="publish_link"]').val()||'0',
			phone_action:item.find('[data-wp-seed-person-field="phone_action"]').val()||'',
			phone_action_explicit:item.find('[data-wp-seed-person-field="phone_action_explicit"]').val()||'0'
		};
	}

	function normalizedItem(data){
		return{
			person_key:String(data.person_key||''),
			roles:$.map(data.roles||[],function(role){return String(role);}),
			name:String(data.name||''),
			phone:String(data.phone||''),
			email:String(data.email||''),
			link:String(data.link||''),
			website_label:String(data.website_label||''),
			publish_email:isAuthorized(data.publish_email)?'1':'0',
			publish_phone:isAuthorized(data.publish_phone)?'1':'0',
			publish_link:isAuthorized(data.publish_link)?'1':'0',
			phone_action:isAuthorized(data.phone_action_explicit)?normalizePhoneAction(data.phone_action,'none'):'',
			phone_action_explicit:isAuthorized(data.phone_action_explicit)?'1':'0'
		};
	}

	function itemsEqual(first,second){
		return JSON.stringify(normalizedItem(first))===JSON.stringify(normalizedItem(second));
	}

	function publicationLabels(data){
		var labels=[];
		if(isAuthorized(data.publish_email)){
			labels.push('Email public');
		}
		if(isAuthorized(data.publish_phone)){
			labels.push('Téléphone public');
		}
		if(isAuthorized(data.publish_link)){
			labels.push('Lien public');
		}
		return labels.length?labels:['Coordonnées privées'];
	}

	function writePublicationStatus(item,data){
		item.find('[data-wp-seed-person-publication-status]').text(publicationLabels(data).join(' · '));
	}

	function writeItem(item,data){
		var roles=data.roles||[];
		var rolesContainer=item.find('[data-wp-seed-person-roles]');
		var nameInput=item.find('[data-wp-seed-person-field="name"]');
		var nameMatch=String(nameInput.attr('name')||'').match(/wp_seed_events_contacts\[(\d+)\]/);
		var index=nameMatch?nameMatch[1]:'0';

		item.find('[data-wp-seed-person-field="person_key"]').val(data.person_key||'');
		item.find('[data-wp-seed-person-field="role"]').val(roles[0]||'');
		rolesContainer.empty();
		$.each(roles,function(_,role){
			rolesContainer.append($('<input type="hidden" data-wp-seed-person-role />').attr('name','wp_seed_events_contacts['+index+'][roles][]').val(role));
		});
		$.each(['name','phone','email','link','website_label'],function(_,key){
			item.find('[data-wp-seed-person-field="'+key+'"]').val(data[key]||'');
		});
		$.each(publicationConfig,function(key){
			item.find('[data-wp-seed-person-field="'+key+'"]').val(isAuthorized(data[key])?'1':'0');
		});
		item.find('[data-wp-seed-person-field="phone_action"]').val(isAuthorized(data.phone_action_explicit)?normalizePhoneAction(data.phone_action,'none'):'');
		item.find('[data-wp-seed-person-field="phone_action_explicit"]').val(isAuthorized(data.phone_action_explicit)?'1':'0');
		item.find('[data-wp-seed-person-name]').text(data.name||'Personne sans nom');
		writeRoleLabels(item,roles);
		writePublicationStatus(item,data);
	}

	function createItem(index,data){
		var item=$('<div data-wp-seed-person-item></div>').css({margin:'0 0 12px',padding:'0 0 12px',borderBottom:'1px solid #dcdcde'});
		item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+'][person_key]').attr('data-wp-seed-person-field','person_key'));
		item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+'][role]').attr('data-wp-seed-person-field','role'));
		item.append($('<span data-wp-seed-person-roles></span>'));
		$.each(['name','phone','email','link','website_label','publish_email','publish_phone','publish_link','phone_action','phone_action_explicit'],function(_,key){
			item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+']['+key+']').attr('data-wp-seed-person-field',key));
		});
		item.append(
			$('<p></p>').css('margin','0')
				.append($('<strong data-wp-seed-person-name></strong>'))
				.append('<br />')
				.append($('<span data-wp-seed-person-role-labels></span>'))
				.append($('<span data-wp-seed-person-publication-status></span>').css('fontSize','12px'))
				.append('<br />')
				.append(
					$('<span></span>').css('fontSize','12px')
						.append($('<button type="button" class="button-link" data-wp-seed-person-move-up>Monter</button>'))
						.append(' · ')
						.append($('<button type="button" class="button-link" data-wp-seed-person-move-down>Descendre</button>'))
						.append(' · ')
						.append($('<button type="button" class="button-link" data-wp-seed-person-edit>Modifier</button>'))
						.append(' · ')
						.append(window.wpSeedEventsAdmin.removeFromEventButton('data-wp-seed-person-remove'))
				)
		);
		writeItem(item,data);
		return item;
	}

	function fillReusableFields(personPanel,data){
		field(personPanel,'person_key').val(data.person_key||'');
		field(personPanel,'name').val(data.name||'');
		field(personPanel,'phone').val(data.phone||'');
		field(personPanel,'email').val(data.email||'');
		field(personPanel,'link').val(data.link||'');
		field(personPanel,'website_label').val(data.website_label||'');
		field(personPanel,'phone_action').val(
			isAuthorized(data.phone_action_explicit)
				?normalizePhoneAction(data.phone_action,'none')
				:'call'
		);
		personPanel.data('wpSeedSelectedPersonName',data.person_key?String(data.name||''):'');
	}

	function writePanelPublication(personPanel,data){
		$.each(publicationConfig,function(key){
			publicationField(personPanel,key).prop('checked',isAuthorized(data[key]));
		});
	}

	function defaultPublicationForCoordinates(data){
		var publication={};
		$.each(publicationConfig,function(key,config){
			publication[key]=coordinateIsValid(config.coordinate,data[config.coordinate])?'1':'0';
		});
		return publication;
	}

	function refreshPublicationControl(personPanel,key){
		var config=publicationConfig[key];
		var checkbox=publicationField(personPanel,key);
		var wrapper=personPanel.find('[data-wp-seed-person-publication-field="'+key+'"]');
		var valid=coordinateIsValid(config.coordinate,field(personPanel,config.coordinate).val());
		var revoked=personPanel.data('wpSeedPublicationRevoked')||{};
		var disabled=!valid||true===revoked[key];

		if(!valid||true===revoked[key]){
			checkbox.prop('checked',false);
		}
		wrapper.prop('hidden',!valid);
		checkbox.prop('disabled',disabled);
		if('publish_phone'===key){
			personPanel.find('[data-wp-seed-person-phone-action]').prop('hidden',!valid);
		}
	}

	function refreshPublicationControls(personPanel){
		$.each(publicationConfig,function(key){
			refreshPublicationControl(personPanel,key);
		});
	}

	function readPanel(personPanel){
		var data={
			person_key:field(personPanel,'person_key').val()||'',
			name:field(personPanel,'name').val()||'',
			phone:field(personPanel,'phone').val()||'',
			email:field(personPanel,'email').val()||'',
			link:field(personPanel,'link').val()||'',
			website_label:field(personPanel,'website_label').val()||'',
			roles:readPanelRoles(personPanel)
		};
		$.each(publicationConfig,function(key){
			var checkbox=publicationField(personPanel,key);
			data[key]=!checkbox.prop('disabled')&&checkbox.prop('checked')?'1':'0';
		});
		var original=personPanel.data('wpSeedOriginalData')||{};
		var actionTouched=true===personPanel.data('wpSeedPhoneActionTouched');
		var isNew=!personPanel.data('wpSeedPersonItem');
		var actionExplicit=isNew||actionTouched||isAuthorized(original.phone_action_explicit);
		data.phone_action=actionExplicit?normalizePhoneAction(field(personPanel,'phone_action').val(),'none'):'';
		data.phone_action_explicit=actionExplicit?'1':'0';
		return data;
	}

	function parkPanel(peopleRoot){
		var personPanel=panel(peopleRoot);
		personPanel.prop('hidden',true);
		peopleRoot.find('[data-wp-seed-person-panel-home]').after(personPanel);
	}

	function openPanel(peopleRoot,item){
		var personPanel=panel(peopleRoot);
		var data=item?readItem(item):{
			person_key:'',
			roles:defaultRoles(peopleRoot),
			name:'',
			phone:'',
			email:'',
			link:'',
			website_label:'',
			publish_email:'0',
			publish_phone:'0',
			publish_link:'0',
			phone_action:'none',
			phone_action_explicit:'1'
		};
		personPanel.data('wpSeedPersonItem',item||null);
		personPanel.data('wpSeedOriginalData',$.extend(true,{},data));
		personPanel.data('wpSeedPublicationRevoked',{});
		personPanel.data('wpSeedPublicationTouched',{});
		personPanel.data('wpSeedPhoneActionTouched',false);
		personPanel.find('[data-wp-seed-person-panel-title]').text(item?'Modifier la personne':'Ajouter une personne');
		fillReusableFields(personPanel,data);
		writePanelRoles(personPanel,data.roles||[]);
		writePanelPublication(personPanel,data);
		refreshPublicationControls(personPanel);
		refreshSuggestionAvailability(peopleRoot,personPanel,item);
		if(item){
			item.after(personPanel);
		}else{
			peopleRoot.find('[data-wp-seed-person-panel-home]').after(personPanel);
		}
		personPanel.prop('hidden',false);
		field(personPanel,'name').trigger('focus');
	}

	$(document).on('click','[data-wp-seed-person-add]',function(e){
		e.preventDefault();
		openPanel(root(this),null);
	});

	$(document).on('click','[data-wp-seed-person-edit]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-person-item]');
		openPanel(root(this),item);
	});

	$(document).on('click','[data-wp-seed-reusable-suggestion]',function(e){
		e.preventDefault();
		var peopleRoot=root(this);
		var personPanel=panel(peopleRoot);
		var original=personPanel.data('wpSeedOriginalData')||{};
		var suggestion={
			person_key:String($(this).data('wp-seed-suggestion-key')||''),
			name:String($(this).data('wp-seed-suggestion-name')||''),
			phone:String($(this).data('wp-seed-suggestion-phone')||''),
			email:String($(this).data('wp-seed-suggestion-email')||''),
			link:String($(this).data('wp-seed-suggestion-link')||''),
			website_label:String($(this).attr('data-wp-seed-suggestion-website-label')||''),
			phone_action:'none',
			phone_action_explicit:'1'
		};
		var samePerson=suggestion.person_key&&suggestion.person_key===String(original.person_key||'');
		if(samePerson){
			suggestion.phone_action=original.phone_action||'';
			suggestion.phone_action_explicit=original.phone_action_explicit||'0';
		}

		fillReusableFields(personPanel,suggestion);
		if(!samePerson){
			writePanelPublication(personPanel,defaultPublicationForCoordinates(suggestion));
		}
		personPanel.data('wpSeedPublicationRevoked',{});
		personPanel.data('wpSeedPublicationTouched',{});
		personPanel.data('wpSeedPhoneActionTouched',false);
		refreshPublicationControls(personPanel);
	});

	$(document).on('change','[data-wp-seed-person-panel-publication]',function(){
		var personPanel=$(this).closest('[data-wp-seed-person-panel]');
		var touched=personPanel.data('wpSeedPublicationTouched')||{};
		touched[String($(this).attr('data-wp-seed-person-panel-publication')||'')]=true;
		personPanel.data('wpSeedPublicationTouched',touched);
	});

	$(document).on('change','[data-wp-seed-person-panel-field="phone_action"]',function(){
		$(this).closest('[data-wp-seed-person-panel]').data('wpSeedPhoneActionTouched',true);
	});

	function normalizeSuggestionText(value){
		return String(value||'')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g,'')
			.toLocaleLowerCase();
	}

	function filterSuggestionList(personPanel){
		var query=normalizeSuggestionText(field(personPanel,'name').val());
		personPanel.find('[data-wp-seed-person-suggestion-item]').each(function(){
			var item=$(this);
			var button=item.find('[data-wp-seed-reusable-suggestion]');
			var searchable=[
				button.attr('data-wp-seed-suggestion-name'),
				button.attr('data-wp-seed-suggestion-email'),
				button.attr('data-wp-seed-suggestion-phone')
			].join(' ');
			var attached=item.attr('data-wp-seed-suggestion-attached')==='1';
			item.prop('hidden',attached||(query&&normalizeSuggestionText(searchable).indexOf(query)===-1));
		});
	}

	function refreshSuggestionAvailability(peopleRoot,personPanel,currentItem){
		var currentKey=currentItem?String(readItem(currentItem).person_key||''):'';
		var attached={};
		peopleRoot.find('[data-wp-seed-person-item]').each(function(){
			var key=String(readItem($(this)).person_key||'');
			if(key&&key!==currentKey){
				attached[key]=true;
			}
		});
		personPanel.find('[data-wp-seed-person-suggestion-item]').each(function(){
			var item=$(this);
			var key=String(item.find('[data-wp-seed-reusable-suggestion]').attr('data-wp-seed-suggestion-key')||'');
			item.attr('data-wp-seed-suggestion-attached',key&&attached[key]?'1':'0');
		});
		filterSuggestionList(personPanel);
	}

	$(document).on('input','[data-wp-seed-person-autocomplete]',function(){
		var personPanel=$(this).closest('[data-wp-seed-person-panel]');
		var selectedName=String(personPanel.data('wpSeedSelectedPersonName')||'');
		if(field(personPanel,'person_key').val()&&String($(this).val()).trim()!==selectedName.trim()){
			field(personPanel,'person_key').val('');
			personPanel.data('wpSeedSelectedPersonName','');
		}
		filterSuggestionList(personPanel);
	});

	$(document).on('input change','[data-wp-seed-person-panel-field="phone"],[data-wp-seed-person-panel-field="email"],[data-wp-seed-person-panel-field="link"]',function(){
		var peopleRoot=root(this);
		var personPanel=panel(peopleRoot);
		var item=personPanel.data('wpSeedPersonItem');
		var coordinate=$(this).attr('data-wp-seed-person-panel-field');
		var original=personPanel.data('wpSeedOriginalData')||{};
		var revoked=personPanel.data('wpSeedPublicationRevoked')||{};
		var touched=personPanel.data('wpSeedPublicationTouched')||{};
		var publicationKey='publish_'+coordinate;
		var originalCoordinate=normalizeCoordinate(coordinate,original[coordinate]);
		var currentCoordinate=normalizeCoordinate(coordinate,$(this).val());
		var changed=!!item&&''!==originalCoordinate&&currentCoordinate!==originalCoordinate;

		if(changed&&!revoked[publicationKey]){
			revoked[publicationKey]=true;
			personPanel.data('wpSeedPublicationRevoked',revoked);
			publicationField(personPanel,publicationKey).prop('checked',false);
			announce(peopleRoot,publicationConfig[publicationKey].message);
		}
		if(!changed&&revoked[publicationKey]){
			revoked[publicationKey]=false;
			personPanel.data('wpSeedPublicationRevoked',revoked);
		}
		if(!changed&&''!==currentCoordinate&&!touched[publicationKey]){
			publicationField(personPanel,publicationKey).prop('checked',coordinateIsValid(coordinate,currentCoordinate));
		}
		if('phone'===coordinate&&''===originalCoordinate&&validPhone(currentCoordinate)&&!personPanel.data('wpSeedPhoneActionTouched')){
			field(personPanel,'phone_action').val('none');
			personPanel.data('wpSeedPhoneActionTouched',true);
		}
		refreshPublicationControl(personPanel,publicationKey);
	});

	$(document).on('click','[data-wp-seed-person-save]',function(e){
		e.preventDefault();
		var peopleRoot=root(this);
		var personPanel=panel(peopleRoot);
		var data=readPanel(personPanel);
		var item=personPanel.data('wpSeedPersonItem');

		if(!data.name){
			field(personPanel,'name').trigger('focus');
			return;
		}

		if(String(data.website_label||'').trim()&&!validLink(data.link)){
			var websiteInput=field(personPanel,'link').get(0);
			websiteInput.setCustomValidity('Une URL est obligatoire lorsque le nom du site ou le texte affiché est renseigné.');
			websiteInput.reportValidity();
			field(personPanel,'link').trigger('focus');
			return;
		}
		field(personPanel,'link').get(0).setCustomValidity('');

		if(item){
			var previous=readItem(item);
			if(itemsEqual(previous,data)){
				parkPanel(peopleRoot);
				return;
			}
			writeItem(item,data);
		}else{
			var index=parseInt(peopleRoot.attr('data-next-index'),10)||0;
			item=createItem(index,data);
			peopleRoot.attr('data-next-index',index+1);
			peopleRoot.find('[data-wp-seed-people-list]').append(item);
		}

		markChanged(peopleRoot);
		parkPanel(peopleRoot);
		refreshEmpty(peopleRoot);
		refreshPeopleOrder(peopleRoot);
	});

	$(document).on('click','[data-wp-seed-person-cancel]',function(e){
		e.preventDefault();
		parkPanel(root(this));
	});

	$(document).on('click','[data-wp-seed-person-move-up],[data-wp-seed-person-move-down]',function(e){
		e.preventDefault();
		var button=$(this);
		var peopleRoot=root(this);
		var item=button.closest('[data-wp-seed-person-item]');
		var sibling=button.is('[data-wp-seed-person-move-up]')?item.prev('[data-wp-seed-person-item]'):item.next('[data-wp-seed-person-item]');

		if(!sibling.length){
			return;
		}

		if(button.is('[data-wp-seed-person-move-up]')){
			item.insertBefore(sibling);
		}else{
			item.insertAfter(sibling);
		}
		refreshPeopleOrder(peopleRoot);
		markChanged(peopleRoot);
	});

	$(document).on('click','[data-wp-seed-person-remove]',function(e){
		e.preventDefault();
		var peopleRoot=root(this);
		var item=$(this).closest('[data-wp-seed-person-item]');
		if(!item.length){
			return;
		}
		item.remove();
		markChanged(peopleRoot);
		refreshEmpty(peopleRoot);
		refreshPeopleOrder(peopleRoot);
	});

	$('[data-wp-seed-people]').each(function(){
		refreshPeopleOrder($(this));
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	function wpSeedDateRoot(element){
		return $(element).closest('[data-wp-seed-dates]');
	}

	function wpSeedDatePanel(root){
		return root.find('[data-wp-seed-date-panel]');
	}

	function wpSeedDateMarkChanged(root){
		root.closest('form').find('[data-wp-seed-occurrences-changed]').val('1');
	}

	function wpSeedDateField(panel,key){
		return panel.find('[data-wp-seed-date-panel-field="'+key+'"]');
	}

	function wpSeedDateFormatDate(dateValue){
		if(!dateValue){
			return 'Date sans jour défini';
		}

		var parts=dateValue.split('-');
		var date=new Date(Number(parts[0]),Number(parts[1])-1,Number(parts[2]));

		if(isNaN(date.getTime())){
			return dateValue;
		}

		return new Intl.DateTimeFormat('fr-FR',{weekday:'long',day:'numeric',month:'long',year:'numeric'}).format(date).replace(/^./,function(letter){return letter.toUpperCase();});
	}

	function wpSeedDateFormatTime(timeValue){
		return timeValue?timeValue.replace(':','h'):'';
	}

	function wpSeedDateDayLine(data){
		if(data.end_date&&data.end_date!==data.start_date){
			return wpSeedDateFormatDate(data.start_date)+' → '+wpSeedDateFormatDate(data.end_date);
		}

		return wpSeedDateFormatDate(data.start_date);
	}

	function wpSeedDateTimeLine(data){
		if('1'===data.all_day){
			return 'Toute la journée';
		}

		if(data.start_time&&data.end_time){
			return wpSeedDateFormatTime(data.start_time)+' → '+wpSeedDateFormatTime(data.end_time);
		}

		if(data.start_time){
			return 'À partir de '+wpSeedDateFormatTime(data.start_time);
		}

		if(data.end_time){
			return 'Jusqu’à '+wpSeedDateFormatTime(data.end_time);
		}

		return '';
	}

	function wpSeedDateSortValue(data){
		return (data.start_date||'')+' '+('1'===data.all_day?'00:00':(data.start_time||'00:00'));
	}

	function wpSeedDateRefreshEmpty(root){
		root.find('[data-wp-seed-dates-empty]').prop('hidden',root.find('[data-wp-seed-date-item]').length>0);
	}

	function wpSeedDateSortItems(root){
		var list=root.find('[data-wp-seed-dates-list]');
		list.children('[data-wp-seed-date-item]').sort(function(first,second){
			return String($(first).attr('data-wp-seed-date-sort')||'').localeCompare(String($(second).attr('data-wp-seed-date-sort')||''));
		}).appendTo(list);
	}

	function wpSeedDateReadItem(item){
		return{
			uid:item.find('[data-wp-seed-date-field="uid"]').val(),
			start_date:item.find('[data-wp-seed-date-field="start_date"]').val(),
			end_date:item.find('[data-wp-seed-date-field="end_date"]').val(),
			start_time:item.find('[data-wp-seed-date-field="start_time"]').val(),
			end_time:item.find('[data-wp-seed-date-field="end_time"]').val(),
			all_day:item.find('[data-wp-seed-date-field="all_day"]').val(),
			cancelled:item.find('[data-wp-seed-date-field="cancelled"]').val(),
			promotion_id:item.find('[data-wp-seed-date-field="promotion_id"]').val(),
			parcours_year:item.find('[data-wp-seed-date-field="parcours_year"]').val(),
			promotion_label:String(item.find('[data-wp-seed-date-parcours]').text()||'').split(' — ')[0].trim()
		};
	}

	function wpSeedDateItemsEqual(first,second){
		var fields=['uid','start_date','end_date','start_time','end_time','all_day','cancelled','promotion_id','parcours_year'];

		return fields.every(function(field){
			return String(first[field]||'')===String(second[field]||'');
		});
	}

	function wpSeedDateWriteItem(item,data){
		item.find('[data-wp-seed-date-field="uid"]').val(data.uid||'');
		item.find('[data-wp-seed-date-field="start_date"]').val(data.start_date);
		item.find('[data-wp-seed-date-field="end_date"]').val(data.end_date);
		item.find('[data-wp-seed-date-field="start_time"]').val(data.start_time);
		item.find('[data-wp-seed-date-field="end_time"]').val(data.end_time);
		item.find('[data-wp-seed-date-field="all_day"]').val(data.all_day);
		item.find('[data-wp-seed-date-field="cancelled"]').val(data.cancelled);
		item.find('[data-wp-seed-date-field="promotion_id"]').val(data.promotion_id||'');
		item.find('[data-wp-seed-date-field="parcours_year"]').val(data.parcours_year||'');
		item.attr('data-wp-seed-date-sort',wpSeedDateSortValue(data));
		item.find('[data-wp-seed-date-day]').text(wpSeedDateDayLine(data));
		item.find('[data-wp-seed-date-time]').text(wpSeedDateTimeLine(data));
		item.find('[data-wp-seed-date-cancelled-label]').prop('hidden','1'!==data.cancelled);
		item.find('[data-wp-seed-date-toggle]').text('1'===data.cancelled?'Réactiver':'Marquer comme annulée');
		var parcours=item.find('[data-wp-seed-date-parcours]');
		var yearLabel='1'===String(data.parcours_year)?'1re année':String(data.parcours_year||'')+'e année';
		parcours.attr('data-promotion-label',data.promotion_label||'');
		parcours.text(data.promotion_id&&data.parcours_year?(data.promotion_label+' — '+yearLabel):'');
		parcours.append('<br />');
		parcours.prop('hidden',!(data.promotion_id&&data.parcours_year));
	}

	function wpSeedDateCreateItem(index,data){
		var item=$('<div data-wp-seed-date-item></div>').css({margin:'0 0 12px',padding:'0 0 12px',borderBottom:'1px solid #dcdcde'});
		var fields=['uid','start_date','end_date','start_time','end_time','all_day','cancelled','promotion_id','parcours_year'];
		$.each(fields,function(_,field){
			item.append($('<input type="hidden" />').attr('name','wp_seed_events_occurrences['+index+']['+field+']').attr('data-wp-seed-date-field',field));
		});
		item.append(
			$('<p></p>').css('margin','0')
				.append($('<strong data-wp-seed-date-day></strong>'))
				.append($('<span data-wp-seed-date-cancelled-label>ANNULÉE</span>').css({marginLeft:'8px',color:'#b32d2e',fontWeight:'600'}))
				.append('<br />')
				.append($('<span data-wp-seed-date-time></span>'))
				.append('<br />')
				.append($('<span data-wp-seed-date-parcours hidden></span>'))
				.append('<br />')
				.append(
					$('<span></span>').css('fontSize','12px')
						.append($('<button type="button" class="button-link" data-wp-seed-date-edit>Modifier</button>'))
						.append(' · ')
						.append($('<button type="button" class="button-link" data-wp-seed-date-toggle></button>'))
						.append(' · ')
						.append($('<button type="button" class="button-link-delete" data-wp-seed-date-remove>Supprimer</button>'))
				)
		);
		wpSeedDateWriteItem(item,data);
		return item;
	}

	function wpSeedDateOpenPanel(root,item){
		var panel=wpSeedDatePanel(root);
		var data=item?wpSeedDateReadItem(item):{start_date:'',end_date:'',start_time:'',end_time:'',all_day:'',cancelled:'',promotion_id:'',parcours_year:''};
		panel.data('wpSeedDateItem',item||null);
		panel.find('[data-wp-seed-date-panel-title]').text(item?'Modifier la date':'Ajouter une date');
		wpSeedDateField(panel,'start_date').val(data.start_date);
		wpSeedDateField(panel,'end_date').val(data.end_date);
		wpSeedDateField(panel,'start_time').val(data.start_time);
		wpSeedDateField(panel,'end_time').val(data.end_time);
		wpSeedDateField(panel,'all_day').prop('checked','1'===data.all_day);
		wpSeedDateField(panel,'promotion_id').val(data.promotion_id||'');
		wpSeedDateField(panel,'parcours_year').val(data.parcours_year||'');
		wpSeedDateUpdateParcoursState(panel);
		panel.prop('hidden',false);
		wpSeedDateField(panel,'start_date').trigger('focus');
	}

	$(document).on('click','[data-wp-seed-date-add]',function(e){
		e.preventDefault();
		wpSeedDateOpenPanel(wpSeedDateRoot(this),null);
	});

	$(document).on('click','[data-wp-seed-date-edit]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-date-item]');
		wpSeedDateOpenPanel(wpSeedDateRoot(this),item);
	});

	$(document).on('click','[data-wp-seed-date-save]',function(e){
		e.preventDefault();
		var root=wpSeedDateRoot(this);
		var panel=wpSeedDatePanel(root);
		var promotionField=wpSeedDateField(panel,'promotion_id');
		var data={uid:'',start_date:wpSeedDateField(panel,'start_date').val(),end_date:wpSeedDateField(panel,'end_date').val(),start_time:wpSeedDateField(panel,'start_time').val(),end_time:wpSeedDateField(panel,'end_time').val(),all_day:wpSeedDateField(panel,'all_day').prop('checked')?'1':'',cancelled:'',promotion_id:promotionField.val()||'',parcours_year:wpSeedDateField(panel,'parcours_year').val()||'',promotion_label:promotionField.find('option:selected').text().replace(/\s+\(archivée\)$/,'')};
		var item=panel.data('wpSeedDateItem');
		if((data.promotion_id&&!data.parcours_year)||(!data.promotion_id&&data.parcours_year)){
			(data.promotion_id?wpSeedDateField(panel,'parcours_year'):promotionField).trigger('focus');
			return;
		}
		if(!data.start_date){
			wpSeedDateField(panel,'start_date').trigger('focus');
			return;
		}
		if(item){
			var previous=wpSeedDateReadItem(item);
			data.uid=previous.uid;
			data.cancelled=previous.cancelled;
			if(wpSeedDateItemsEqual(previous,data)){
				panel.prop('hidden',true);
				return;
			}
			wpSeedDateWriteItem(item,data);
			wpSeedDateMarkChanged(root);
		}else{
			var index=parseInt(root.attr('data-next-index'),10)||0;
			item=wpSeedDateCreateItem(index,data);
			root.attr('data-next-index',index+1);
			root.find('[data-wp-seed-dates-list]').append(item);
			wpSeedDateMarkChanged(root);
		}
		wpSeedDateSortItems(root);
		panel.prop('hidden',true);
		wpSeedDateRefreshEmpty(root);
	});

	$(document).on('click','[data-wp-seed-date-cancel]',function(e){
		e.preventDefault();
		wpSeedDatePanel(wpSeedDateRoot(this)).prop('hidden',true);
	});

	$(document).on('click','[data-wp-seed-date-remove]',function(e){
		e.preventDefault();
		var root=wpSeedDateRoot(this);
		$(this).closest('[data-wp-seed-date-item]').remove();
		wpSeedDateMarkChanged(root);
		wpSeedDateRefreshEmpty(root);
	});

	$(document).on('click','[data-wp-seed-date-toggle]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-date-item]');
		var data=wpSeedDateReadItem(item);
		data.cancelled='1'===data.cancelled?'':'1';
		wpSeedDateWriteItem(item,data);
		wpSeedDateMarkChanged(wpSeedDateRoot(this));
	});

	function wpSeedDateUpdateParcoursState(panel){
		var promotionField=wpSeedDateField(panel,'promotion_id');
		var yearField=wpSeedDateField(panel,'parcours_year');
		var hasPromotion=Boolean(promotionField.val());
		if(!hasPromotion){
			yearField.val('');
		}
		yearField.prop('disabled',!hasPromotion);
	}

	$(document).on('change','[data-wp-seed-date-panel-field="promotion_id"]',function(){
		wpSeedDateUpdateParcoursState(wpSeedDatePanel(wpSeedDateRoot(this)));
	});
});
JS
	);
}

function wp_seed_events_save_media( $post_id ) {
	if ( ! isset( $_POST['wp_seed_events_media_nonce'] ) ) {
		return;
	}

	$nonce = sanitize_text_field( wp_unslash( $_POST['wp_seed_events_media_nonce'] ) );

	if ( ! wp_verify_nonce( $nonce, 'wp_seed_events_save_media' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) || 'wp_seed_event' !== get_post_type( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$media_changed = isset( $_POST['wp_seed_event_media_changed'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_media_changed'] ) ) : '';

	if ( '1' !== $media_changed ) {
		return;
	}

	$visuals_changed  = isset( $_POST['wp_seed_event_visuals_changed'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_visuals_changed'] ) ) : '';
	$document_changed = isset( $_POST['wp_seed_event_document_changed'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_document_changed'] ) ) : '';

	if ( '1' === $document_changed && isset( $_POST['wp_seed_event_media'] ) && is_array( $_POST['wp_seed_event_media'] ) ) {
		$raw_media = wp_unslash( $_POST['wp_seed_event_media'] );

		foreach ( wp_seed_events_media_fields() as $meta_key => $field ) {
			if ( ! array_key_exists( $meta_key, $raw_media ) || ! is_scalar( $raw_media[ $meta_key ] ) ) {
				continue;
			}

			$submitted_value = trim( (string) $raw_media[ $meta_key ] );

			if ( '' === $submitted_value ) {
				delete_post_meta( $post_id, $meta_key );
				continue;
			}

			$attachment_id = absint( $submitted_value );

			if ( 0 === $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
				continue;
			}

			$mime_type = get_post_mime_type( $attachment_id );

			if ( 'application/pdf' === $field['type'] && 'application/pdf' !== $mime_type ) {
				continue;
			}

			if ( absint( get_post_meta( $post_id, $meta_key, true ) ) !== $attachment_id ) {
				update_post_meta( $post_id, $meta_key, $attachment_id );
			}
		}

		$document_id = absint( get_post_meta( $post_id, '_wp_seed_event_flyer_pdf_id', true ) );
		$display_name = isset( $_POST['wp_seed_event_document_display_name'] )
			? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_document_display_name'] ) )
			: '';

		if ( 0 === $document_id || '' === $display_name ) {
			delete_post_meta( $post_id, WP_SEED_EVENTS_DOCUMENT_DISPLAY_NAME_META_KEY );
		} else {
			update_post_meta( $post_id, WP_SEED_EVENTS_DOCUMENT_DISPLAY_NAME_META_KEY, $display_name );
		}
	}

	if ( '1' !== $visuals_changed || ! isset( $_POST['wp_seed_event_illustrations'] ) || ! is_array( $_POST['wp_seed_event_illustrations'] ) ) {
		return;
	}

	$visuals_empty = '';

	if ( isset( $_POST['wp_seed_event_visuals_empty'] ) && is_scalar( $_POST['wp_seed_event_visuals_empty'] ) ) {
		$visuals_empty = sanitize_text_field( wp_unslash( $_POST['wp_seed_event_visuals_empty'] ) );
	}

	$raw_illustrations = wp_unslash( $_POST['wp_seed_event_illustrations'] );
	$illustration_ids  = array();
	$has_submitted_id  = false;

	foreach ( $raw_illustrations as $raw_illustration_id ) {
		if ( ! is_scalar( $raw_illustration_id ) ) {
			return;
		}

		$raw_illustration_id = trim( (string) $raw_illustration_id );

		if ( '' === $raw_illustration_id ) {
			continue;
		}

		$has_submitted_id = true;
		$attachment_id = absint( $raw_illustration_id );

		if ( 0 === $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			continue;
		}

		$mime_type = get_post_mime_type( $attachment_id );

		if ( 0 !== strpos( (string) $mime_type, 'image/' ) ) {
			continue;
		}

		if ( ! in_array( $attachment_id, $illustration_ids, true ) ) {
			$illustration_ids[] = $attachment_id;
		}
	}

	if ( '1' === $visuals_empty ) {
		if ( $has_submitted_id ) {
			return;
		}

		delete_post_meta( $post_id, '_wp_seed_event_illustration_ids' );
		delete_post_thumbnail( $post_id );
		return;
	}

	if ( '0' !== $visuals_empty || ! $has_submitted_id ) {
		return;
	}

	if ( array() === $illustration_ids ) {
		return;
	}

	$stored_illustration_ids = get_post_meta( $post_id, '_wp_seed_event_illustration_ids', true );

	if ( $stored_illustration_ids !== $illustration_ids ) {
		update_post_meta( $post_id, '_wp_seed_event_illustration_ids', $illustration_ids );
	}

	if ( absint( get_post_thumbnail_id( $post_id ) ) !== $illustration_ids[0] ) {
		set_post_thumbnail( $post_id, $illustration_ids[0] );
	}
}
