<?php
/**
 * Plugin Name: WP Seed Events
 * Description: Autonomous event publishing foundation for WordPress.
 * Version: 0.1.22-dev
 * Author: WP Seed
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

require_once __DIR__ . '/includes/public/occurrences.php';
require_once __DIR__ . '/includes/public/media.php';
require_once __DIR__ . '/includes/public/event-data.php';
require_once __DIR__ . '/includes/public/calendar.php';
require_once __DIR__ . '/includes/public/sharing.php';
require_once __DIR__ . '/includes/public/rendering.php';
require_once __DIR__ . '/includes/public/data-registry.php';
require_once __DIR__ . '/includes/integrations/gutenberg/block-bindings.php';

register_activation_hook( __FILE__, 'wp_seed_events_activate' );

add_action( 'init', 'wp_seed_events_register_event_post_type' );
add_action( 'admin_init', 'wp_seed_events_register_permalink_settings' );
add_action( 'admin_init', 'wp_seed_events_maybe_save_permalink_settings' );
add_action( 'admin_menu', 'wp_seed_events_register_plugin_admin_menu', 99 );
add_action( 'admin_notices', 'wp_seed_events_render_title_required_notice' );
add_filter( 'wp_insert_post_data', 'wp_seed_events_prepare_event_title_and_slug', 10, 2 );
add_filter( 'redirect_post_location', 'wp_seed_events_title_required_redirect', 10, 2 );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_event_type_meta_box', 5 );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_occurrences_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_place_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_contacts_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_description_meta_box', 20 );
add_action( 'add_meta_boxes_wp_seed_place', 'wp_seed_events_add_place_address_meta_box' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_occurrences' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_event_place' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_contacts' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_media' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_event_type' );
add_action( 'admin_post_wp_seed_events_save_event_types', 'wp_seed_events_handle_event_types_admin_form' );
add_action( 'admin_post_wp_seed_events_save_people', 'wp_seed_events_handle_people_admin_form' );
add_action( 'admin_post_wp_seed_events_save_places', 'wp_seed_events_handle_places_admin_form' );
add_action( 'admin_post_wp_seed_events_save_display_settings', 'wp_seed_events_handle_display_settings_form' );
add_action( 'admin_post_wp_seed_events_download_occurrence_ics', 'wp_seed_events_handle_occurrence_ics_download' );
add_action( 'admin_post_nopriv_wp_seed_events_download_occurrence_ics', 'wp_seed_events_handle_occurrence_ics_download' );
add_action( 'admin_post_wp_seed_events_download_event_ics', 'wp_seed_events_handle_event_ics_download' );
add_action( 'admin_post_nopriv_wp_seed_events_download_event_ics', 'wp_seed_events_handle_event_ics_download' );
add_action( 'wp_footer', 'wp_seed_events_render_public_share_script', 99 );
add_action( 'save_post_wp_seed_place', 'wp_seed_events_save_place_address' );
add_action( 'admin_enqueue_scripts', 'wp_seed_events_enqueue_media_admin' );
add_action( 'edit_form_after_title', 'wp_seed_events_render_media_before_description', 5 );
add_filter( 'wp_editor_settings', 'wp_seed_events_disable_description_media_buttons', 10, 2 );
add_filter( 'manage_wp_seed_event_posts_columns', 'wp_seed_events_event_admin_columns' );
add_action( 'manage_wp_seed_event_posts_custom_column', 'wp_seed_events_render_event_admin_column', 10, 2 );
add_filter( 'manage_edit-wp_seed_event_sortable_columns', 'wp_seed_events_event_admin_sortable_columns' );
add_action( 'pre_get_posts', 'wp_seed_events_sort_event_admin_by_next_date' );
add_filter( 'the_title', 'wp_seed_events_prefix_pinned_event_admin_title', 10, 2 );
add_filter( 'the_content', 'wp_seed_events_render_public_event_content' );
add_filter( 'template_include', 'wp_seed_events_public_template_include', 99 );
add_filter( 'body_class', 'wp_seed_events_public_body_class' );
add_filter( 'post_type_link', 'wp_seed_events_event_post_type_link', 10, 4 );
add_filter( 'query_vars', 'wp_seed_events_permalink_query_vars' );
add_action( 'template_redirect', 'wp_seed_events_redirect_event_to_canonical_url', 1 );
add_shortcode( 'wp_seed_event_card', 'wp_seed_events_event_card_shortcode' );
add_shortcode( 'wp_seed_events', 'wp_seed_events_event_collection_shortcode' );
add_shortcode( 'wp_seed_event', 'wp_seed_events_event_shortcode' );
add_shortcode( 'wp_seed_event_field', 'wp_seed_events_event_field_shortcode' );
add_shortcode( 'wp_seed_event_dates', 'wp_seed_events_event_dates_shortcode' );
add_shortcode( 'wp_seed_event_people', 'wp_seed_events_event_people_shortcode' );
add_shortcode( 'wp_seed_event_place', 'wp_seed_events_event_place_shortcode' );
add_shortcode( 'wp_seed_event_practical_info', 'wp_seed_events_event_practical_info_shortcode' );

function wp_seed_events_activate() {
	wp_seed_events_register_event_post_type();
	flush_rewrite_rules();
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

	if ( '' === $slug ) {
		return true;
	}

	if ( 0 < $post_id && (string) $post_id === $slug ) {
		return true;
	}

	if ( in_array( $slug, array( 'auto-draft', 'sans-titre', 'untitled' ), true ) ) {
		return true;
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
			'show_in_rest'       => false,
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

	add_rewrite_rule( '^([^/]+)/([^/]+)/?$', 'index.php?post_type=wp_seed_event&name=$matches[2]&wp_seed_event_primary_type=$matches[1]', 'top' );
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

	return sanitize_title( $options[ $type_key ] );
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

	$new_columns['title'] = $columns['title'] ?? 'Titre';
	$new_columns['wp_seed_event_types'] = 'Type(s)';
	$new_columns['wp_seed_event_next_date'] = 'Prochaine date';
	$new_columns['wp_seed_event_place'] = 'Lieu';
	$new_columns['wp_seed_event_status'] = 'Statut';

	if ( isset( $columns['date'] ) ) {
		$new_columns['date'] = $columns['date'];
	}

	return $new_columns;
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

	wp_seed_events_initialize_missing_next_occurrence_sort_meta();

	$order = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';

	$query->set(
		'meta_query',
		array(
			'relation'                => 'OR',
			'next_occurrence_sort'    => array(
				'key'     => '_wp_seed_event_next_occurrence_sort',
				'compare' => 'EXISTS',
			),
			'next_occurrence_missing' => array(
				'key'     => '_wp_seed_event_next_occurrence_sort',
				'compare' => 'NOT EXISTS',
			),
		)
	);
	$query->set( 'orderby', array( 'next_occurrence_sort' => $order ) );
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
	if ( 'wp_seed_event_types' === $column_name ) {
		$type_labels = wp_seed_events_event_type_labels_for_event( $post_id );
		echo array() === $type_labels ? '—' : esc_html( implode( ' • ', $type_labels ) );
		return;
	}

	if ( 'wp_seed_event_next_date' === $column_name ) {
		$next_occurrence = wp_seed_events_next_occurrence_for_event( $post_id );
		echo array() === $next_occurrence ? '—' : wp_kses_post( wp_seed_events_format_admin_next_date( $next_occurrence ) );
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
			return is_array( $occurrence ) && ! empty( $occurrence['start_date'] );
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

	$last_occurrence = end( $valid_occurrences );

	return wp_seed_events_occurrence_sort_value( $last_occurrence );
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
			return is_array( $occurrence ) && ! empty( $occurrence['start_date'] );
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

	return end( $valid_occurrences );
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

function wp_seed_events_event_card_excerpt( $post ) {
	$content = $post instanceof WP_Post ? $post->post_content : '';
	$content = wp_strip_all_tags( strip_shortcodes( $content ) );
	$content = trim( preg_replace( '/\s+/', ' ', $content ) );

	if ( '' === $content ) {
		return '';
	}

	return wp_trim_words( $content, 28, '…' );
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
			'description' => 'Affiche une seule information de l’événement courant.',
			'example'     => '[wp_seed_event_field field="next_date"]',
			'options'     => 'field : title, url, types, next_date, next_time, place, place_address, place_link, phone, email, person_link, description, excerpt, image, flyer.',
		),
		array(
			'name'        => 'Dates',
			'description' => 'Affiche les dates de l’événement courant.',
			'example'     => '[wp_seed_event_dates format="short" show_time="no"]',
			'options'     => 'format : long ou short. show_time : yes ou no. show_cancelled : yes ou no.',
		),
		array(
			'name'        => 'Personnes',
			'description' => 'Affiche les personnes liées à l’événement courant.',
			'example'     => '[wp_seed_event_people role="intervenant" details="no"]',
			'options'     => 'role : all, organisateur, intervenant, contact_inscription ou contact_information. details : yes ou no.',
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
			<h2 class="hndle">Document à télécharger</h2>
			<div class="handle-actions hide-if-no-js">
				<button type="button" class="handlediv" aria-expanded="true">
					<span class="screen-reader-text">Afficher ou masquer le document à télécharger</span>
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
		'Description de l’évènement (optionnel)',
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

	if ( 'add' === $admin_action ) {
		$new_type_label = isset( $_POST['wp_seed_new_event_type_label'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_event_type_label'] ) ) : '';

		if ( '' !== $new_type_label ) {
			$type_key = wp_seed_events_event_type_key_from_label( $new_type_label, wp_seed_events_event_type_options() );

			if ( '' !== $type_key ) {
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

		<p>
			<button type="button" class="button" data-wp-seed-event-type-add>+ Créer un nouveau type</button>
		</p>
		<div data-wp-seed-event-type-new-panel hidden>
			<p>
				<label>
					Nouveau type<br />
					<input type="text" data-wp-seed-event-type-new-label value="" />
				</label>
				<input type="hidden" name="wp_seed_new_event_type" data-wp-seed-event-type-new-value value="" />
				<button type="button" class="button" data-wp-seed-event-type-save-new>Ajouter</button>
			</p>
			<p>
				<strong>Types existants</strong><br />
				<span data-wp-seed-event-type-existing-list>
					<?php foreach ( $event_type_options as $type_key => $type_label ) : ?>
						<button type="button" class="button-link" data-wp-seed-event-type-existing-choice="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type_label ); ?></button><br />
					<?php endforeach; ?>
				</span>
			</p>
		</div>

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

	$new_type_label = isset( $_POST['wp_seed_new_event_type'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_event_type'] ) ) : '';
	$options        = wp_seed_events_event_type_options();
	$selected_types = isset( $_POST['wp_seed_event_types'] ) && is_array( $_POST['wp_seed_event_types'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['wp_seed_event_types'] ) ) : array();
	$primary_type   = isset( $_POST['wp_seed_event_primary_type'] ) ? sanitize_key( wp_unslash( $_POST['wp_seed_event_primary_type'] ) ) : '';
	$default_type   = wp_seed_events_default_event_type_key();

	if ( '' !== $new_type_label ) {
		$type_key = wp_seed_events_event_type_key_from_label( $new_type_label, $options );

		if ( '' !== $type_key ) {
			if ( ! isset( $options[ $type_key ] ) ) {
				$custom_types              = wp_seed_events_custom_event_type_options();
				$custom_types[ $type_key ] = $new_type_label;
				update_option( 'wp_seed_events_custom_event_types', $custom_types, false );
				$options = wp_seed_events_event_type_options();
			}

			$selected_types[] = $type_key;
		}
	}

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
	$has_display_occurrences = false;

	wp_nonce_field( 'wp_seed_events_save_occurrences', 'wp_seed_events_occurrences_nonce' );
	?>
	<div data-wp-seed-dates data-next-index="<?php echo esc_attr( (string) count( $occurrences ) ); ?>">
		<div data-wp-seed-dates-list>
			<?php foreach ( $display_occurrences as $index => $occurrence ) : ?>
				<?php
				if ( ! is_array( $occurrence ) || empty( $occurrence['start_date'] ) ) {
					continue;
				}

				$has_display_occurrences = true;
				$is_cancelled            = ! empty( $occurrence['cancelled'] );
				?>
				<div data-wp-seed-date-item data-wp-seed-date-sort="<?php echo esc_attr( wp_seed_events_occurrence_sort_value( $occurrence ) ); ?>" style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
					<input type="hidden" data-wp-seed-date-field="uid" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][uid]" value="<?php echo esc_attr( $occurrence['uid'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="start_date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_date]" value="<?php echo esc_attr( $occurrence['start_date'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="end_date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_date]" value="<?php echo esc_attr( $occurrence['end_date'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="start_time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_time]" value="<?php echo esc_attr( $occurrence['start_time'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="end_time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_time]" value="<?php echo esc_attr( $occurrence['end_time'] ?? '' ); ?>" />
					<input type="hidden" data-wp-seed-date-field="all_day" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][all_day]" value="<?php echo ! empty( $occurrence['all_day'] ) ? '1' : ''; ?>" />
					<input type="hidden" data-wp-seed-date-field="cancelled" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][cancelled]" value="<?php echo $is_cancelled ? '1' : ''; ?>" />
					<p style="margin: 0;">
						<strong data-wp-seed-date-day><?php echo esc_html( wp_seed_events_format_occurrence_date_line( $occurrence ) ); ?></strong>
						<span data-wp-seed-date-cancelled-label style="margin-left: 8px; color: #b32d2e; font-weight: 600;" <?php echo $is_cancelled ? '' : 'hidden'; ?>>ANNULÉE</span><br />
						<span data-wp-seed-date-time><?php echo esc_html( wp_seed_events_format_occurrence_time_line( $occurrence ) ); ?></span><br />
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
			<p>
				<button type="button" class="button button-primary" data-wp-seed-date-save>Enregistrer la date</button>
				<button type="button" class="button" data-wp-seed-date-cancel>Annuler</button>
			</p>
		</div>
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

	$raw_occurrences = isset( $_POST['wp_seed_events_occurrences'] ) && is_array( $_POST['wp_seed_events_occurrences'] ) ? wp_unslash( $_POST['wp_seed_events_occurrences'] ) : array();
	$occurrences     = array();

	foreach ( $raw_occurrences as $raw_occurrence ) {
		if ( ! is_array( $raw_occurrence ) ) {
			continue;
		}

		$start_date = isset( $raw_occurrence['start_date'] ) ? sanitize_text_field( $raw_occurrence['start_date'] ) : '';
		$end_date   = isset( $raw_occurrence['end_date'] ) ? sanitize_text_field( $raw_occurrence['end_date'] ) : '';
		$start_time = isset( $raw_occurrence['start_time'] ) ? sanitize_text_field( $raw_occurrence['start_time'] ) : '';
		$end_time   = isset( $raw_occurrence['end_time'] ) ? sanitize_text_field( $raw_occurrence['end_time'] ) : '';
		$cancelled  = ! empty( $raw_occurrence['cancelled'] ) ? '1' : '';
		$uid        = isset( $raw_occurrence['uid'] ) ? wp_seed_events_sanitize_occurrence_uid( $raw_occurrence['uid'] ) : '';

		if ( '' === $start_date ) {
			continue;
		}

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date ) ) {
			continue;
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

		$occurrences[] = array(
			'uid'        => $uid,
			'start_date' => $start_date,
			'end_date'   => $end_date,
			'start_time' => $start_time,
			'end_time'   => $end_time,
			'all_day'    => ! empty( $raw_occurrence['all_day'] ) ? '1' : '',
			'cancelled'  => $cancelled,
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
							<label for="wp-seed-new-place-link">Lien (facultatif)</label>
							<input id="wp-seed-new-place-link" type="url" name="wp_seed_place_link" value="" />
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
									<th scope="col" class="manage-column">Lien</th>
									<th scope="col" class="manage-column column-posts num">Utilisation</th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $places as $place ) : ?>
									<?php
									$place_id            = (int) $place->ID;
									$address             = get_post_meta( $place_id, '_wp_seed_place_address', true );
										$link                = get_post_meta( $place_id, '_wp_seed_place_link', true );
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
													<p><label>Lien (facultatif)<br /><input type="url" name="wp_seed_place_link" value="<?php echo esc_attr( $link ); ?>" /></label></p>
													<?php submit_button( 'Enregistrer', 'primary small', 'submit', false ); ?>
													<button type="button" class="button button-small" data-wp-seed-place-admin-cancel>Annuler</button>
												</form>
											</div>

											<button type="button" class="toggle-row"><span class="screen-reader-text">Afficher plus de détails</span></button>
										</td>
										<td data-colname="Adresse"><?php echo esc_html( $address ); ?></td>
										<td data-colname="Lien">
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

function wp_seed_events_get_place_suggestions( $limit = 8 ) {
	return get_posts(
		array(
			'post_type'      => 'wp_seed_place',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);
}

function wp_seed_events_render_place_meta_box( $post ) {
	$selected_place_id = (int) get_post_meta( $post->ID, '_wp_seed_event_place_id', true );
	$place_details     = get_post_meta( $post->ID, '_wp_seed_event_place_details', true );
	$selected_place    = $selected_place_id ? get_post( $selected_place_id ) : null;
	$place_address     = $selected_place ? get_post_meta( $selected_place_id, '_wp_seed_place_address', true ) : '';
	$place_link        = $selected_place ? get_post_meta( $selected_place_id, '_wp_seed_place_link', true ) : '';
	$suggestions       = wp_seed_events_get_place_suggestions();

	if ( ! $selected_place || 'wp_seed_place' !== $selected_place->post_type ) {
		$selected_place_id = 0;
		$selected_place    = null;
		$place_address     = '';
		$place_link        = '';
	}

	wp_nonce_field( 'wp_seed_events_save_event_place', 'wp_seed_events_place_nonce' );
	?>
	<div data-wp-seed-place>
		<input type="hidden" name="wp_seed_event_place_id" data-wp-seed-place-field="place_id" value="<?php echo esc_attr( (string) $selected_place_id ); ?>" />
		<input type="hidden" name="wp_seed_new_place_name" data-wp-seed-place-field="new_name" value="" />
		<input type="hidden" name="wp_seed_new_place_address" data-wp-seed-place-field="new_address" value="" />
		<input type="hidden" name="wp_seed_new_place_link" data-wp-seed-place-field="new_link" value="" />
		<input type="hidden" name="wp_seed_update_place_id" data-wp-seed-place-field="update_id" value="" />
		<input type="hidden" name="wp_seed_update_place_name" data-wp-seed-place-field="update_name" value="" />
		<input type="hidden" name="wp_seed_update_place_address" data-wp-seed-place-field="update_address" value="" />
		<input type="hidden" name="wp_seed_update_place_link" data-wp-seed-place-field="update_link" value="" />
		<input type="hidden" name="wp_seed_delete_place_id" data-wp-seed-place-field="delete_id" value="" />

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
					<span style="font-size: 12px;">
						<button type="button" class="button-link" data-wp-seed-place-edit>Modifier</button>
						<span aria-hidden="true"> · </span>
						<button type="button" class="button-link" data-wp-seed-place-choose>Changer de lieu</button>
						<span aria-hidden="true"> · </span>
						<button type="button" class="button-link" data-wp-seed-place-remove>Retirer de cet évènement</button>
						<span aria-hidden="true"> · </span>
						<button type="button" class="button-link-delete" data-wp-seed-place-delete>Supprimer ce lieu</button>
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
			<?php if ( array() !== $suggestions ) : ?>
				<p>Suggestions</p>
				<ul>
					<?php foreach ( $suggestions as $place ) : ?>
						<?php
						$suggestion_address = get_post_meta( $place->ID, '_wp_seed_place_address', true );
						$suggestion_link    = get_post_meta( $place->ID, '_wp_seed_place_link', true );
						?>
						<li>
							<button
								type="button"
								class="button-link"
								data-wp-seed-place-suggestion
								data-wp-seed-place-id="<?php echo esc_attr( (string) $place->ID ); ?>"
								data-wp-seed-place-name="<?php echo esc_attr( $place->post_title ); ?>"
								data-wp-seed-place-address="<?php echo esc_attr( $suggestion_address ); ?>"
								data-wp-seed-place-link="<?php echo esc_attr( $suggestion_link ); ?>"
							>
								<?php echo esc_html( $place->post_title ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<hr />
			<h4 data-wp-seed-place-form-title>Créer un nouveau lieu</h4>
			<p>
				<label>
					Nom<br />
					<input type="text" data-wp-seed-place-panel-field="name" value="" />
				</label>
			</p>
			<p>
				<label>
					Adresse (facultatif)<br />
					<input type="text" data-wp-seed-place-panel-field="address" value="" />
				</label>
			</p>
			<p>
				<label>
					Lien (facultatif)<br />
					<input type="url" data-wp-seed-place-panel-field="link" value="" />
				</label>
			</p>
			<p>
				<label>
					Informations complémentaires<br />
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
			Lien<br />
			<input type="url" name="wp_seed_place_link" value="<?php echo esc_attr( $link ); ?>" />
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
	$update_place_id      = isset( $_POST['wp_seed_update_place_id'] ) ? (int) $_POST['wp_seed_update_place_id'] : 0;
	$update_place_name    = isset( $_POST['wp_seed_update_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_name'] ) ) : '';
	$update_place_address = isset( $_POST['wp_seed_update_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_address'] ) ) : '';
	$update_place_link    = isset( $_POST['wp_seed_update_place_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_update_place_link'] ) ) : '';
	$delete_place_id      = isset( $_POST['wp_seed_delete_place_id'] ) ? (int) $_POST['wp_seed_delete_place_id'] : 0;
	$place_details        = isset( $_POST['wp_seed_event_place_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wp_seed_event_place_details'] ) ) : '';

	if ( '' !== $place_details ) {
		update_post_meta( $post_id, '_wp_seed_event_place_details', $place_details );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_place_details' );
	}

	if ( $delete_place_id > 0 && 'wp_seed_place' === get_post_type( $delete_place_id ) && current_user_can( 'delete_post', $delete_place_id ) ) {
		wp_delete_post( $delete_place_id, true );

		if ( $place_id === $delete_place_id ) {
			$place_id = 0;
		}
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
}

function wp_seed_events_contact_roles() {
	return array(
		'organizer'            => 'Organisateur',
		'speaker'              => 'Intervenant',
		'registration_contact' => 'Contact inscription',
		'information_contact'  => 'Contact information',
	);
}

function wp_seed_events_default_contact_roles() {
	$available_roles = wp_seed_events_contact_roles();
	$default_roles   = get_option( 'wp_seed_events_default_contact_roles', array( 'speaker' ) );

	if ( ! is_array( $default_roles ) ) {
		$default_roles = array( 'speaker' );
	}

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

	return array(
		'person_key' => $person_key,
		'name'       => $name,
		'phone'      => isset( $person['phone'] ) ? sanitize_text_field( $person['phone'] ) : '',
		'email'      => isset( $person['email'] ) ? sanitize_text_field( $person['email'] ) : '',
		'link'       => isset( $person['link'] ) ? esc_url_raw( $person['link'] ) : '',
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

		foreach ( $contacts as &$contact ) {
			if ( ! is_array( $contact ) ) {
				continue;
			}

			$contact_person_key = isset( $contact['person_key'] ) ? sanitize_key( $contact['person_key'] ) : '';

			if ( $person_key !== $contact_person_key ) {
				continue;
			}

			$contact['name']  = $person['name'];
			$contact['phone'] = $person['phone'];
			$contact['email'] = $person['email'];
			$contact['link']  = $person['link'];
			$has_changes      = true;
		}
		unset( $contact );

		if ( $has_changes ) {
			update_post_meta( $event_id, '_wp_seed_event_contacts', $contacts );
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

						<div class="form-field">
							<label for="wp-seed-new-person-phone">Téléphone (facultatif)</label>
							<input id="wp-seed-new-person-phone" type="text" name="wp_seed_person_phone" value="" />
						</div>

						<div class="form-field">
							<label for="wp-seed-new-person-email">Email (facultatif)</label>
							<input id="wp-seed-new-person-email" type="text" name="wp_seed_person_email" value="" />
						</div>

						<div class="form-field">
							<label for="wp-seed-new-person-link">Lien (facultatif)</label>
							<input id="wp-seed-new-person-link" type="url" name="wp_seed_person_link" value="" />
						</div>

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
													<p><label>Téléphone (facultatif)<br /><input type="text" name="wp_seed_person_phone" value="<?php echo esc_attr( $person['phone'] ); ?>" /></label></p>
													<p><label>Email (facultatif)<br /><input type="text" name="wp_seed_person_email" value="<?php echo esc_attr( $person['email'] ); ?>" /></label></p>
													<p><label>Lien (facultatif)<br /><input type="url" name="wp_seed_person_link" value="<?php echo esc_attr( $person['link'] ); ?>" /></label></p>
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
		$person = wp_seed_events_sanitize_person(
			array(
				'name'  => isset( $_POST['wp_seed_person_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_name'] ) ) : '',
				'phone' => isset( $_POST['wp_seed_person_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_phone'] ) ) : '',
				'email' => isset( $_POST['wp_seed_person_email'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_person_email'] ) ) : '',
				'link'  => isset( $_POST['wp_seed_person_link'] ) ? esc_url_raw( wp_unslash( $_POST['wp_seed_person_link'] ) ) : '',
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
		$role = sanitize_key( $raw_role );

		if ( isset( $available_roles[ $role ] ) && ! in_array( $role, $role_keys, true ) ) {
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
		$role_labels[] = $roles[ $role_key ];
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

function wp_seed_events_get_contact_suggestions( $current_post_id, $limit = 8 ) {
	unset( $current_post_id );

	return array_slice( array_values( wp_seed_events_people() ), 0, $limit );
}
function wp_seed_events_render_contacts_meta_box( $post ) {
	$contacts      = get_post_meta( $post->ID, '_wp_seed_event_contacts', true );
	$roles         = wp_seed_events_contact_roles();
	$default_roles = wp_seed_events_default_contact_roles();
	$suggestions   = wp_seed_events_get_contact_suggestions( $post->ID );

	if ( ! is_array( $contacts ) ) {
		$contacts = array();
	}

	wp_nonce_field( 'wp_seed_events_save_contacts', 'wp_seed_events_contacts_nonce' );
	?>
	<div data-wp-seed-people data-next-index="<?php echo esc_attr( (string) count( $contacts ) ); ?>" data-default-roles="<?php echo esc_attr( implode( ',', $default_roles ) ); ?>">
		<div data-wp-seed-people-list>
			<?php foreach ( $contacts as $index => $contact ) : ?>
				<?php
				if ( ! is_array( $contact ) || empty( $contact['name'] ) ) {
					continue;
				}

				$contact_role_keys = wp_seed_events_contact_role_keys( $contact, $roles );
				$person_key        = wp_seed_events_contact_person_key( $contact );
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
					<p style="margin: 0;">
						<strong data-wp-seed-person-name><?php echo esc_html( wp_seed_events_contact_name( $contact ) ); ?></strong><br />
						<span data-wp-seed-person-role-labels>
							<?php foreach ( wp_seed_events_contact_role_labels( $contact, $roles ) as $role_label ) : ?>
								<span><?php echo esc_html( $role_label ); ?></span><br />
							<?php endforeach; ?>
						</span>
						<span style="font-size: 12px;">
							<button type="button" class="button-link" data-wp-seed-person-edit>Modifier</button>
							<span aria-hidden="true"> · </span>
							<button type="button" class="button-link-delete" data-wp-seed-person-remove>Supprimer</button>
						</span>
					</p>
				</div>
			<?php endforeach; ?>
		</div>

		<p data-wp-seed-people-empty <?php echo array() === $contacts ? '' : 'hidden'; ?>>Aucune personne</p>
		<p><button type="button" class="button" data-wp-seed-person-add>+ Ajouter une personne</button></p>

		<div data-wp-seed-person-panel hidden>
			<h4 data-wp-seed-person-panel-title>Ajouter une personne</h4>
			<p>
				<label>
					Nom<br />
					<input type="hidden" data-wp-seed-person-panel-field="person_key" />
					<input type="text" data-wp-seed-person-panel-field="name" />
				</label>
			</p>
			<?php if ( array() !== $suggestions ) : ?>
				<p>Suggestions</p>
				<ul>
					<?php foreach ( $suggestions as $suggestion ) : ?>
						<li>
							<button
								type="button"
								class="button-link"
								data-wp-seed-reusable-suggestion
								data-wp-seed-suggestion-key="<?php echo esc_attr( $suggestion['person_key'] ?? '' ); ?>"
								data-wp-seed-suggestion-name="<?php echo esc_attr( $suggestion['name'] ); ?>"
								data-wp-seed-suggestion-phone="<?php echo esc_attr( $suggestion['phone'] ); ?>"
								data-wp-seed-suggestion-email="<?php echo esc_attr( $suggestion['email'] ); ?>"
								data-wp-seed-suggestion-link="<?php echo esc_attr( $suggestion['link'] ); ?>"
							>
								<?php echo esc_html( $suggestion['name'] ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p>
				<label>
					Téléphone (facultatif)<br />
					<input type="tel" data-wp-seed-person-panel-field="phone" />
				</label>
			</p>
			<p>
				<label>
					Email (facultatif)<br />
					<input type="text" data-wp-seed-person-panel-field="email" />
				</label>
			</p>
			<p>
				<label>
					Lien (facultatif)<br />
					<input type="url" data-wp-seed-person-panel-field="link" />
				</label>
			</p>
			<fieldset>
				<legend>Rôles pour cet évènement</legend>
				<?php foreach ( $roles as $role_value => $role_label ) : ?>
					<label>
						<input type="checkbox" data-wp-seed-person-panel-role value="<?php echo esc_attr( $role_value ); ?>" />
						<?php echo esc_html( $role_label ); ?>
					</label><br />
				<?php endforeach; ?>
			</fieldset>
			<p>
				<button type="button" class="button button-primary" data-wp-seed-person-save>Enregistrer la personne</button>
				<button type="button" class="button" data-wp-seed-person-cancel>Annuler</button>
			</p>
		</div>
	</div>
	<?php
}
function wp_seed_events_save_contacts( $post_id ) {
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

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$raw_contacts    = isset( $_POST['wp_seed_events_contacts'] ) && is_array( $_POST['wp_seed_events_contacts'] ) ? wp_unslash( $_POST['wp_seed_events_contacts'] ) : array();
	$available_roles = wp_seed_events_contact_roles();
	$people          = wp_seed_events_stored_people();
	$contacts        = array();

	foreach ( $raw_contacts as $raw_contact ) {
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
			$role = sanitize_key( $raw_role );

			if ( isset( $available_roles[ $role ] ) && ! in_array( $role, $contact_roles, true ) ) {
				$contact_roles[] = $role;
			}
		}

		$name  = isset( $raw_contact['name'] ) ? sanitize_text_field( $raw_contact['name'] ) : '';
		$phone = isset( $raw_contact['phone'] ) ? sanitize_text_field( $raw_contact['phone'] ) : '';
		$email = isset( $raw_contact['email'] ) ? sanitize_text_field( $raw_contact['email'] ) : '';
		$link  = isset( $raw_contact['link'] ) ? esc_url_raw( $raw_contact['link'] ) : '';

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

		$people[ $person_key ] = array(
			'person_key' => $person_key,
			'name'       => $name,
			'phone'      => $phone,
			'email'      => $email,
			'link'       => $link,
		);

		$contacts[] = array(
			'person_key' => $person_key,
			'role'       => $contact_roles[0] ?? '',
			'roles'      => $contact_roles,
			'name'       => $name,
			'phone'      => $phone,
			'email'      => $email,
			'link'       => $link,
		);
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
			'label' => 'Document complémentaire (PDF)',
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
	<h3>Flyer recto</h3>
	<p class="description">Le visuel principal qui représente l’événement.</p>
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
	?>
	<p class="description">Programme, brochure ou autre document PDF associé à l’événement.</p>
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
jQuery(function($){
	var wpSeedCommunicationVisuals=[];

	function wpSeedMarkMediaChanged(){
		$('[data-wp-seed-media-changed]').val('1');
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
			var cannotRemove=1===count;

			item.find('[data-wp-seed-featured-image-label]').prop('hidden',!isRecto).toggle(isRecto);
			item.find('[data-wp-seed-featured-image-set]').prop('hidden',isRecto).toggle(!isRecto);
			item.find('[data-wp-seed-visual-up]').prop('hidden',isRecto).toggle(!isRecto).prop('disabled',isFirstOther);
			item.find('[data-wp-seed-visual-down]').prop('hidden',isRecto).toggle(!isRecto).prop('disabled',isLast);
			item.find('[data-wp-seed-illustration-remove]').prop('disabled',cannotRemove).attr('aria-disabled',cannotRemove?'true':'false');
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
		wpSeedMarkMediaChanged();

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
				wpSeedMarkMediaChanged();
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
		wpSeedMarkMediaChanged();
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

		if(wpSeedCommunicationVisuals.length<=1){
			return;
		}

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
		hiddenField(root,'update_id').val('');
		hiddenField(root,'update_name').val('');
		hiddenField(root,'update_address').val('');
		hiddenField(root,'update_link').val('');
		hiddenField(root,'delete_id').val('');
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
			block.append($('<a data-wp-seed-place-summary-link target="_blank" rel="noopener noreferrer"></a>').attr('href',data.link).text(data.link));
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
				.append($('<button type="button" class="button-link" data-wp-seed-place-remove>Retirer de cet évènement</button>'))
				.append(' · ')
				.append($('<button type="button" class="button-link-delete" data-wp-seed-place-delete>Supprimer ce lieu</button>'))
		);
		summary.append(block);
	}

	function currentPlaceData(root){
		return{
			id:hiddenField(root,'place_id').val(),
			name:root.find('[data-wp-seed-place-summary-name]').text(),
			address:root.find('[data-wp-seed-place-summary-address]').text(),
			link:root.find('[data-wp-seed-place-summary-link]').text(),
			details:root.find('[data-wp-seed-place-summary-details]').text()
		};
	}

	function openPlacePanel(root,mode,data){
		var panel=placePanel(root);
		data=data||{id:'',name:'',address:'',link:'',details:''};
		panel.data('wpSeedPlaceMode',mode);
		panel.data('wpSeedPlaceId',data.id||'');
		panel.data('wpSeedPlaceOriginalId',hiddenField(root,'place_id').val()||'');
		panel.find('[data-wp-seed-place-panel-title]').text('edit'===mode?'Modifier le lieu':'Choisir ou créer un lieu');
		panelField(panel,'name').val(data.name||'');
		panelField(panel,'address').val(data.address||'');
		panelField(panel,'link').val(data.link||'');
		panelField(panel,'details').val(data.details||'');
		panel.prop('hidden',false);
		panelField(panel,'name').trigger('focus');
	}

	$(document).on('click','[data-wp-seed-place-choose]',function(e){
		e.preventDefault();
		openPlacePanel(placeRoot(this),'choose',{id:'',name:'',address:'',link:'',details:''});
	});

	$(document).on('click','[data-wp-seed-place-edit]',function(e){
		e.preventDefault();
		openPlacePanel(placeRoot(this),'edit',currentPlaceData(placeRoot(this)));
	});

	$(document).on('click','[data-wp-seed-place-suggestion]',function(e){
		e.preventDefault();
		var panel=placePanel(placeRoot(this));
		panel.data('wpSeedPlaceId',String($(this).attr('data-wp-seed-place-id')||''));
		panelField(panel,'name').val($(this).attr('data-wp-seed-place-name')||'');
		panelField(panel,'address').val($(this).attr('data-wp-seed-place-address')||'');
		panelField(panel,'link').val($(this).attr('data-wp-seed-place-link')||'');
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
			details:panelField(panel,'details').val()
		};
		var mode=String(panel.data('wpSeedPlaceMode')||'choose');
		var originalId=String(panel.data('wpSeedPlaceOriginalId')||'');

		if(!data.name){
			panelField(panel,'name').trigger('focus');
			return;
		}

		clearPlaceActions(root);
		panelField(panel,'details').val(data.details);

		if(data.id){
			hiddenField(root,'place_id').val(data.id);

			if('edit'===mode&&data.id===originalId){
				hiddenField(root,'update_id').val(data.id);
				hiddenField(root,'update_name').val(data.name);
				hiddenField(root,'update_address').val(data.address);
				hiddenField(root,'update_link').val(data.link);
			}
		}else{
			hiddenField(root,'place_id').val('');
			hiddenField(root,'new_name').val(data.name);
			hiddenField(root,'new_address').val(data.address);
			hiddenField(root,'new_link').val(data.link);
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
		renderPlaceSummary(root,{id:'',name:'',address:'',link:'',details:''});
	});

	$(document).on('click','[data-wp-seed-place-delete]',function(e){
		e.preventDefault();

		if(!confirm('Supprimer ce lieu ?')){
			return;
		}

		var root=placeRoot(this);
		var placeId=hiddenField(root,'place_id').val();
		clearPlaceActions(root);

		if(placeId){
			hiddenField(root,'delete_id').val(placeId);
		}

		hiddenField(root,'place_id').val('');
		panelField(placePanel(root),'details').val('');
		renderPlaceSummary(root,{id:'',name:'',address:'',link:'',details:''});
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	function normalizeTypeLabel(label){
		return String(label||'').trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
	}

	function existingTypeKey(root,label,key){
		var normalizedLabel=normalizeTypeLabel(label);
		var existingKey='';

		root.find('[data-wp-seed-event-primary-type] option').each(function(){
			var option=$(this);
			var optionKey=String(option.val()||'');
			var optionLabel=String(option.attr('data-normalized-label')||normalizeTypeLabel(option.text()));

			if((normalizedLabel&&optionLabel===normalizedLabel)||(key&&optionKey===key)){
				existingKey=optionKey;
				return false;
			}
		});

		return existingKey;
	}

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

	$(document).on('click','[data-wp-seed-event-type-add]',function(e){
		e.preventDefault();
		var root=$(this).closest('[data-wp-seed-event-type]');
		root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',false);
		root.find('[data-wp-seed-event-type-new-label]').trigger('focus');
	});

	$(document).on('click','[data-wp-seed-event-type-existing-choice]',function(e){
		e.preventDefault();
		var choice=$(this);
		var key=String(choice.attr('data-wp-seed-event-type-existing-choice')||'');
		var root=choice.closest('[data-wp-seed-event-type]');
		var primarySelect=root.find('[data-wp-seed-event-primary-type]');
		var currentPrimary=String(primarySelect.val()||'');

		if(!key){
			return;
		}

		primarySelect.val(key);
		root.find('[data-wp-seed-event-type-new-value]').val('');
		root.find('[data-wp-seed-event-type-new-label]').val('');
		root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',true);
		syncSecondaryTypes(root,currentPrimary);
	});

	$(document).on('click','[data-wp-seed-event-type-save-new]',function(e){
		e.preventDefault();
		var root=$(this).closest('[data-wp-seed-event-type]');
		var input=root.find('[data-wp-seed-event-type-new-label]');
		var label=String(input.val()||'').trim();

		if(!label){
			input.trigger('focus');
			return;
		}

		var normalizedLabel=normalizeTypeLabel(label);
		var key=normalizedLabel;
		var options=root.find('[data-wp-seed-event-type-options]');
		var primarySelect=root.find('[data-wp-seed-event-primary-type]');
		var currentPrimary=String(primarySelect.val()||'');
		var existingKey=existingTypeKey(root,label,key);

		if(existingKey){
			primarySelect.val(existingKey);
			root.find('[data-wp-seed-event-type-new-value]').val('');
			root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',true);
			input.val('');
			syncSecondaryTypes(root,currentPrimary);
			return;
		}

		if(!key){
			key='type_'+Date.now();
		}

		while(options.find('input[value="'+key+'"]').length){
			key=key+'_'+Date.now();
		}

		options.append(
			$('<p data-wp-seed-event-type-option></p>')
				.attr('data-type-key',key)
				.attr('data-normalized-label',normalizedLabel)
				.append(
					$('<label></label>')
						.append($('<input type="checkbox" name="wp_seed_event_types[]" checked />').val(key))
						.append(' '+label)
				)
		);
		primarySelect.append($('<option></option>').val(key).attr('data-normalized-label',normalizedLabel).text(label));
		root.find('[data-wp-seed-event-type-existing-list]').append($('<button type="button" class="button-link" data-wp-seed-event-type-existing-choice></button>').attr('data-wp-seed-event-type-existing-choice',key).text(label)).append('<br />');

		primarySelect.val(key);

		root.find('[data-wp-seed-event-type-new-value]').val(label);
		root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',true);
		input.val('');
		syncSecondaryTypes(root,currentPrimary);
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	function root(element){
		return $(element).closest('[data-wp-seed-people]');
	}

	function panel(root){
		return root.find('[data-wp-seed-person-panel]');
	}

	function field(panel,key){
		return panel.find('[data-wp-seed-person-panel-field="'+key+'"]');
	}

	function roleFields(personPanel){
		return personPanel.find('[data-wp-seed-person-panel-role]');
	}

	function refreshEmpty(root){
		root.find('[data-wp-seed-people-empty]').prop('hidden',root.find('[data-wp-seed-person-item]').length>0);
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

	function defaultRoles(root){
		return String(root.attr('data-default-roles')||'').split(',').filter(function(role){
			return ''!==role;
		});
	}

	function writePanelRoles(personPanel,roles){
		roleFields(personPanel).prop('checked',false);
		$.each(roles||[],function(_,role){
			roleFields(personPanel).filter('[value="'+role+'"]').prop('checked',true);
		});
	}

	function readItem(item){
		return{
			person_key:item.find('[data-wp-seed-person-field="person_key"]').val(),
			roles:readRoles(item),
			name:item.find('[data-wp-seed-person-field="name"]').val(),
			phone:item.find('[data-wp-seed-person-field="phone"]').val(),
			email:item.find('[data-wp-seed-person-field="email"]').val(),
			link:item.find('[data-wp-seed-person-field="link"]').val()
		};
	}

	function writeItem(item,data){
		var roles=data.roles||[];
		var rolesContainer=item.find('[data-wp-seed-person-roles]');
		var index=item.find('[data-wp-seed-person-field="name"]').attr('name').match(/wp_seed_events_contacts\[(\d+)\]/)[1];

		item.find('[data-wp-seed-person-field="person_key"]').val(data.person_key||'');
		item.find('[data-wp-seed-person-field="role"]').val(roles[0]||'');
		rolesContainer.empty();
		$.each(roles,function(_,role){
			rolesContainer.append($('<input type="hidden" data-wp-seed-person-role />').attr('name','wp_seed_events_contacts['+index+'][roles][]').val(role));
		});
		item.find('[data-wp-seed-person-field="name"]').val(data.name);
		item.find('[data-wp-seed-person-field="phone"]').val(data.phone);
		item.find('[data-wp-seed-person-field="email"]').val(data.email);
		item.find('[data-wp-seed-person-field="link"]').val(data.link);
		item.find('[data-wp-seed-person-name]').text(data.name||'Personne sans nom');
		writeRoleLabels(item,roles);
	}

	function createItem(index,data){
		var item=$('<div data-wp-seed-person-item></div>').css({margin:'0 0 12px',padding:'0 0 12px',borderBottom:'1px solid #dcdcde'});
		item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+'][person_key]').attr('data-wp-seed-person-field','person_key'));
		item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+'][role]').attr('data-wp-seed-person-field','role'));
		item.append($('<span data-wp-seed-person-roles></span>'));
		$.each(['name','phone','email','link'],function(_,key){
			item.append($('<input type="hidden" />').attr('name','wp_seed_events_contacts['+index+']['+key+']').attr('data-wp-seed-person-field',key));
		});
		item.append(
			$('<p></p>').css('margin','0')
				.append($('<strong data-wp-seed-person-name></strong>'))
				.append('<br />')
				.append($('<span data-wp-seed-person-role-labels></span>'))
				.append($('<span></span>').css('fontSize','12px').append($('<button type="button" class="button-link" data-wp-seed-person-edit>Modifier</button>')).append(' · ').append($('<button type="button" class="button-link-delete" data-wp-seed-person-remove>Supprimer</button>')))
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
	}

	function openPanel(root,item){
		var personPanel=panel(root);
		var data=item?readItem(item):{person_key:'',roles:defaultRoles(root),name:'',phone:'',email:'',link:''};
		personPanel.data('wpSeedPersonItem',item||null);
		personPanel.find('[data-wp-seed-person-panel-title]').text(item?'Modifier la personne':'Ajouter une personne');
		fillReusableFields(personPanel,data);
		writePanelRoles(personPanel,data.roles||[]);
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
		fillReusableFields(panel(root(this)),{
			person_key:$(this).data('wp-seed-suggestion-key'),
			name:$(this).data('wp-seed-suggestion-name'),
			phone:$(this).data('wp-seed-suggestion-phone'),
			email:$(this).data('wp-seed-suggestion-email'),
			link:$(this).data('wp-seed-suggestion-link')
		});
	});

	$(document).on('click','[data-wp-seed-person-save]',function(e){
		e.preventDefault();
		var peopleRoot=root(this);
		var personPanel=panel(peopleRoot);
		var data={person_key:field(personPanel,'person_key').val(),name:field(personPanel,'name').val(),phone:field(personPanel,'phone').val(),email:field(personPanel,'email').val(),link:field(personPanel,'link').val(),roles:readPanelRoles(personPanel)};
		var item=personPanel.data('wpSeedPersonItem');
		if(!data.name){
			field(personPanel,'name').trigger('focus');
			return;
		}
		if(item){
			writeItem(item,data);
		}else{
			var index=parseInt(peopleRoot.attr('data-next-index'),10)||0;
			item=createItem(index,data);
			peopleRoot.attr('data-next-index',index+1);
			peopleRoot.find('[data-wp-seed-people-list]').append(item);
		}
		personPanel.prop('hidden',true);
		refreshEmpty(peopleRoot);
	});

	$(document).on('click','[data-wp-seed-person-cancel]',function(e){
		e.preventDefault();
		panel(root(this)).prop('hidden',true);
	});

	$(document).on('click','[data-wp-seed-person-remove]',function(e){
		e.preventDefault();
		var peopleRoot=root(this);
		$(this).closest('[data-wp-seed-person-item]').remove();
		refreshEmpty(peopleRoot);
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
			cancelled:item.find('[data-wp-seed-date-field="cancelled"]').val()
		};
	}

	function wpSeedDateWriteItem(item,data){
		item.find('[data-wp-seed-date-field="uid"]').val(data.uid||'');
		item.find('[data-wp-seed-date-field="start_date"]').val(data.start_date);
		item.find('[data-wp-seed-date-field="end_date"]').val(data.end_date);
		item.find('[data-wp-seed-date-field="start_time"]').val(data.start_time);
		item.find('[data-wp-seed-date-field="end_time"]').val(data.end_time);
		item.find('[data-wp-seed-date-field="all_day"]').val(data.all_day);
		item.find('[data-wp-seed-date-field="cancelled"]').val(data.cancelled);
		item.attr('data-wp-seed-date-sort',wpSeedDateSortValue(data));
		item.find('[data-wp-seed-date-day]').text(wpSeedDateDayLine(data));
		item.find('[data-wp-seed-date-time]').text(wpSeedDateTimeLine(data));
		item.find('[data-wp-seed-date-cancelled-label]').prop('hidden','1'!==data.cancelled);
		item.find('[data-wp-seed-date-toggle]').text('1'===data.cancelled?'Réactiver':'Marquer comme annulée');
	}

	function wpSeedDateCreateItem(index,data){
		var item=$('<div data-wp-seed-date-item></div>').css({margin:'0 0 12px',padding:'0 0 12px',borderBottom:'1px solid #dcdcde'});
		var fields=['uid','start_date','end_date','start_time','end_time','all_day','cancelled'];
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
		var data=item?wpSeedDateReadItem(item):{start_date:'',end_date:'',start_time:'',end_time:'',all_day:'',cancelled:''};
		panel.data('wpSeedDateItem',item||null);
		panel.find('[data-wp-seed-date-panel-title]').text(item?'Modifier la date':'Ajouter une date');
		wpSeedDateField(panel,'start_date').val(data.start_date);
		wpSeedDateField(panel,'end_date').val(data.end_date);
		wpSeedDateField(panel,'start_time').val(data.start_time);
		wpSeedDateField(panel,'end_time').val(data.end_time);
		wpSeedDateField(panel,'all_day').prop('checked','1'===data.all_day);
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
		var data={uid:'',start_date:wpSeedDateField(panel,'start_date').val(),end_date:wpSeedDateField(panel,'end_date').val(),start_time:wpSeedDateField(panel,'start_time').val(),end_time:wpSeedDateField(panel,'end_time').val(),all_day:wpSeedDateField(panel,'all_day').prop('checked')?'1':'',cancelled:''};
		var item=panel.data('wpSeedDateItem');
		if(!data.start_date){
			wpSeedDateField(panel,'start_date').trigger('focus');
			return;
		}
		if(item){
			data.uid=item.find('[data-wp-seed-date-field="uid"]').val();
			data.cancelled=item.find('[data-wp-seed-date-field="cancelled"]').val();
			wpSeedDateWriteItem(item,data);
		}else{
			var index=parseInt(root.attr('data-next-index'),10)||0;
			item=wpSeedDateCreateItem(index,data);
			root.attr('data-next-index',index+1);
			root.find('[data-wp-seed-dates-list]').append(item);
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
		wpSeedDateRefreshEmpty(root);
	});

	$(document).on('click','[data-wp-seed-date-toggle]',function(e){
		e.preventDefault();
		var item=$(this).closest('[data-wp-seed-date-item]');
		var data=wpSeedDateReadItem(item);
		data.cancelled='1'===data.cancelled?'':'1';
		wpSeedDateWriteItem(item,data);
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

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$media_changed = isset( $_POST['wp_seed_event_media_changed'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_event_media_changed'] ) ) : '';

	if ( '1' !== $media_changed ) {
		return;
	}

	$raw_media         = isset( $_POST['wp_seed_event_media'] ) && is_array( $_POST['wp_seed_event_media'] ) ? wp_unslash( $_POST['wp_seed_event_media'] ) : array();
	$featured_image_id = isset( $_POST['wp_seed_event_featured_image_id'] ) ? absint( wp_unslash( $_POST['wp_seed_event_featured_image_id'] ) ) : 0;

	foreach ( wp_seed_events_media_fields() as $meta_key => $field ) {
		$attachment_id = isset( $raw_media[ $meta_key ] ) ? absint( $raw_media[ $meta_key ] ) : 0;

		if ( 0 === $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		$mime_type = get_post_mime_type( $attachment_id );

		if ( 'application/pdf' === $field['type'] && 'application/pdf' !== $mime_type ) {
			delete_post_meta( $post_id, $meta_key );
			continue;
		}

		update_post_meta( $post_id, $meta_key, $attachment_id );
	}

	$raw_illustrations = isset( $_POST['wp_seed_event_illustrations'] ) && is_array( $_POST['wp_seed_event_illustrations'] ) ? wp_unslash( $_POST['wp_seed_event_illustrations'] ) : array();
	$illustration_ids  = array();

	foreach ( $raw_illustrations as $raw_illustration_id ) {
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

	if ( array() === $illustration_ids ) {
		delete_post_meta( $post_id, '_wp_seed_event_illustration_ids' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_illustration_ids', $illustration_ids );

	if ( $featured_image_id && in_array( $featured_image_id, $illustration_ids, true ) && 'attachment' === get_post_type( $featured_image_id ) ) {
		$mime_type = get_post_mime_type( $featured_image_id );

		if ( 0 === strpos( (string) $mime_type, 'image/' ) ) {
			set_post_thumbnail( $post_id, $featured_image_id );
		}
	}
}
