<?php
/**
 * Plugin Name: WP Seed Events
 * Description: Autonomous event publishing foundation for WordPress.
 * Version: 0.1.0-dev
 * Author: WP Seed
 * Text Domain: wp-seed-events
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_SEED_EVENTS_VERSION', '0.1.0-dev' );

add_action( 'init', 'wp_seed_events_register_event_post_type' );
add_action( 'admin_menu', 'wp_seed_events_register_plugin_admin_menu', 99 );
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
add_action( 'save_post_wp_seed_place', 'wp_seed_events_save_place_address' );
add_action( 'admin_enqueue_scripts', 'wp_seed_events_enqueue_media_admin' );
add_action( 'edit_form_after_title', 'wp_seed_events_render_media_before_description', 5 );
add_filter( 'wp_editor_settings', 'wp_seed_events_disable_description_media_buttons', 10, 2 );

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
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-calendar-alt',
			'menu_position' => 57,
			'supports'     => array( 'title', 'thumbnail' ),
			'show_in_rest' => false,
		)
	);

	register_post_type(
		'wp_seed_place',
		array(
			'labels'       => array(
				'name'          => 'Lieux',
				'singular_name' => 'Lieu',
				'menu_name'     => 'Tous les lieux',
				'add_new_item'  => 'Ajouter un lieu',
				'edit_item'     => 'Modifier le lieu',
				'all_items'     => 'Tous les lieux',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => 'edit.php?post_type=wp_seed_event',
			'supports'     => array( 'title' ),
			'show_in_rest' => false,
		)
	);
}

function wp_seed_events_register_plugin_admin_menu() {
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

function wp_seed_events_render_settings_page() {
	?>
	<div class="wrap">
		<h1>WP Seed Events - Paramètres</h1>
		<p>Les réglages du plugin seront ajoutés progressivement.</p>
	</div>
	<?php
}

function wp_seed_events_render_display_page() {
	?>
	<div class="wrap">
		<h1>WP Seed Events - Affichage</h1>
		<p>Les options d’affichage seront ajoutées progressivement.</p>
	</div>
	<?php
}

function wp_seed_events_render_media_before_description( $post ) {
	if ( ! $post || 'wp_seed_event' !== $post->post_type ) {
		return;
	}
	?>
	<div class="postbox" id="wp_seed_events_media">
		<div class="postbox-header">
			<h2 class="hndle">Illustrations / Flyer</h2>
			<div class="handle-actions hide-if-no-js">
				<button type="button" class="handlediv" aria-expanded="true">
					<span class="screen-reader-text">Afficher ou masquer les illustrations et le flyer</span>
					<span class="toggle-indicator" aria-hidden="true"></span>
				</button>
			</div>
		</div>
		<div class="inside">
			<?php wp_seed_events_render_media_meta_box( $post ); ?>
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

function wp_seed_events_event_type_options() {
	return array_merge( wp_seed_events_default_event_type_options(), wp_seed_events_custom_event_type_options() );
}

function wp_seed_events_event_type_key_from_label( $type_label, $existing_options ) {
	$base_key = sanitize_key( sanitize_title( $type_label ) );

	if ( '' === $base_key ) {
		return '';
	}

	$type_key = $base_key;
	$index    = 2;

	while ( isset( $existing_options[ $type_key ] ) && $existing_options[ $type_key ] !== $type_label ) {
		$type_key = $base_key . '-' . $index;
		$index++;
	}

	return $type_key;
}

function wp_seed_events_render_event_type_box( $post ) {
	$event_types = get_post_meta( $post->ID, '_wp_seed_event_types', true );

	if ( ! is_array( $event_types ) ) {
		$legacy_event_type = get_post_meta( $post->ID, '_wp_seed_event_type', true );
		$event_types       = '' !== $legacy_event_type ? array( $legacy_event_type ) : array();
	}

	$is_pinned = '1' === get_post_meta( $post->ID, '_wp_seed_event_pinned', true );

	wp_nonce_field( 'wp_seed_events_save_event_type', 'wp_seed_events_event_type_nonce' );
	?>
	<div data-wp-seed-event-type>
		<fieldset>
			<legend>Types d’évènement</legend>
			<div data-wp-seed-event-type-options>
				<?php foreach ( wp_seed_events_event_type_options() as $type_key => $type_label ) : ?>
					<p>
						<label>
							<input type="checkbox" name="wp_seed_event_types[]" value="<?php echo esc_attr( $type_key ); ?>" <?php checked( in_array( $type_key, $event_types, true ) ); ?> />
							<?php echo esc_html( $type_label ); ?>
						</label>
					</p>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button" data-wp-seed-event-type-add>+ Nouveau type</button>
			</p>
			<p data-wp-seed-event-type-new-panel hidden>
				<label>
					Nouveau type<br />
					<input type="text" data-wp-seed-event-type-new-label value="" />
				</label>
				<input type="hidden" name="wp_seed_new_event_type" data-wp-seed-event-type-new-value value="" />
				<button type="button" class="button" data-wp-seed-event-type-save-new>Ajouter</button>
			</p>
		</fieldset>

		<p>
			<label>
				<input type="checkbox" name="wp_seed_event_pinned" value="1" <?php checked( $is_pinned ); ?> />
				📌 Épingler cet évènement
			</label>
		</p>
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

	if ( array() !== $selected_types ) {
		update_post_meta( $post_id, '_wp_seed_event_types', $selected_types );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_types' );
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

		$occurrences[] = array(
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
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_occurrences', $occurrences );
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
	$suggestions       = wp_seed_events_get_place_suggestions();

	if ( ! $selected_place || 'wp_seed_place' !== $selected_place->post_type ) {
		$selected_place_id = 0;
		$selected_place    = null;
		$place_address     = '';
	}

	wp_nonce_field( 'wp_seed_events_save_event_place', 'wp_seed_events_place_nonce' );
	?>
	<div data-wp-seed-place>
		<input type="hidden" name="wp_seed_event_place_id" data-wp-seed-place-field="place_id" value="<?php echo esc_attr( (string) $selected_place_id ); ?>" />
		<input type="hidden" name="wp_seed_new_place_name" data-wp-seed-place-field="new_name" value="" />
		<input type="hidden" name="wp_seed_new_place_address" data-wp-seed-place-field="new_address" value="" />
		<input type="hidden" name="wp_seed_update_place_id" data-wp-seed-place-field="update_id" value="" />
		<input type="hidden" name="wp_seed_update_place_name" data-wp-seed-place-field="update_name" value="" />
		<input type="hidden" name="wp_seed_update_place_address" data-wp-seed-place-field="update_address" value="" />
		<input type="hidden" name="wp_seed_delete_place_id" data-wp-seed-place-field="delete_id" value="" />

		<div data-wp-seed-place-summary>
			<?php if ( $selected_place ) : ?>
				<p style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
					<strong>📍 <span data-wp-seed-place-summary-name><?php echo esc_html( $selected_place->post_title ); ?></span></strong><br />
					<span data-wp-seed-place-summary-address><?php echo esc_html( $place_address ); ?></span>
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
						<button type="button" class="button-link" data-wp-seed-place-remove>Retirer de cet évènement</button>
						<span aria-hidden="true"> · </span>
						<button type="button" class="button-link-delete" data-wp-seed-place-delete>Supprimer ce lieu</button>
					</span>
				</p>
			<?php else : ?>
				<p data-wp-seed-place-empty>📍 Aucun lieu</p>
				<p>
					<button type="button" class="button" data-wp-seed-place-choose>Choisir un lieu</button>
					<button type="button" class="button" data-wp-seed-place-create>Créer un lieu</button>
				</p>
			<?php endif; ?>
		</div>

		<div data-wp-seed-place-panel hidden>
			<h4 data-wp-seed-place-panel-title>Créer un lieu</h4>
			<p>
				<label>
					Nom du lieu<br />
					<input type="text" data-wp-seed-place-panel-field="name" value="" />
				</label>
			</p>
			<?php if ( array() !== $suggestions ) : ?>
				<p>Suggestions</p>
				<ul>
					<?php foreach ( $suggestions as $place ) : ?>
						<?php $suggestion_address = get_post_meta( $place->ID, '_wp_seed_place_address', true ); ?>
						<li>
							<button
								type="button"
								class="button-link"
								data-wp-seed-place-suggestion
								data-wp-seed-place-id="<?php echo esc_attr( (string) $place->ID ); ?>"
								data-wp-seed-place-name="<?php echo esc_attr( $place->post_title ); ?>"
								data-wp-seed-place-address="<?php echo esc_attr( $suggestion_address ); ?>"
							>
								<?php echo esc_html( $place->post_title ); ?>
							</button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<p>
				<label>
					Adresse<br />
					<input type="text" data-wp-seed-place-panel-field="address" value="" />
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

	wp_nonce_field( 'wp_seed_events_save_place_address', 'wp_seed_events_place_address_nonce' );
	?>
	<p>
		<label>
			Adresse<br />
			<input type="text" name="wp_seed_place_address" value="<?php echo esc_attr( $address ); ?>" />
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
	$update_place_id      = isset( $_POST['wp_seed_update_place_id'] ) ? (int) $_POST['wp_seed_update_place_id'] : 0;
	$update_place_name    = isset( $_POST['wp_seed_update_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_name'] ) ) : '';
	$update_place_address = isset( $_POST['wp_seed_update_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_update_place_address'] ) ) : '';
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

	if ( '' === $address ) {
		delete_post_meta( $post_id, '_wp_seed_place_address' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_place_address', $address );
}

function wp_seed_events_contact_roles() {
	return array(
		'organizer'            => 'Organisateur',
		'speaker'              => 'Intervenant',
		'registration_contact' => 'Contact inscription',
		'information_contact'  => 'Contact information',
	);
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
	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => 'any',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'exclude'        => array( $current_post_id ),
			'orderby'        => 'modified',
			'order'          => 'DESC',
		)
	);

	$suggestions = array();

	foreach ( $event_ids as $event_id ) {
		$contacts = get_post_meta( $event_id, '_wp_seed_event_contacts', true );

		if ( ! is_array( $contacts ) ) {
			continue;
		}

		foreach ( $contacts as $contact ) {
			if ( ! is_array( $contact ) || empty( $contact['name'] ) ) {
				continue;
			}

			$suggestions[] = array(
				'name'  => $contact['name'] ?? '',
				'phone' => $contact['phone'] ?? '',
				'email' => $contact['email'] ?? '',
				'link'  => $contact['link'] ?? '',
			);
		}
	}

	return wp_seed_events_limit_reusable_items( $suggestions, 'name', $limit );
}

function wp_seed_events_render_contacts_meta_box( $post ) {
	$contacts    = get_post_meta( $post->ID, '_wp_seed_event_contacts', true );
	$roles       = wp_seed_events_contact_roles();
	$suggestions = wp_seed_events_get_contact_suggestions( $post->ID );

	if ( ! is_array( $contacts ) ) {
		$contacts = array();
	}

	wp_nonce_field( 'wp_seed_events_save_contacts', 'wp_seed_events_contacts_nonce' );
	?>
	<div data-wp-seed-people data-next-index="<?php echo esc_attr( (string) count( $contacts ) ); ?>">
		<div data-wp-seed-people-list>
			<?php foreach ( $contacts as $index => $contact ) : ?>
				<?php
				if ( ! is_array( $contact ) || empty( $contact['name'] ) ) {
					continue;
				}

				$contact_role_keys = wp_seed_events_contact_role_keys( $contact, $roles );
				?>
				<div data-wp-seed-person-item style="margin: 0 0 12px; padding: 0 0 12px; border-bottom: 1px solid #dcdcde;">
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
					Téléphone<br />
					<input type="tel" data-wp-seed-person-panel-field="phone" />
				</label>
			</p>
			<p>
				<label>
					Email<br />
					<input type="email" data-wp-seed-person-panel-field="email" />
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
		$email = isset( $raw_contact['email'] ) ? sanitize_email( $raw_contact['email'] ) : '';
		$link  = isset( $raw_contact['link'] ) ? esc_url_raw( $raw_contact['link'] ) : '';

		if ( '' === $name ) {
			continue;
		}

		$contacts[] = array(
			'role'  => $contact_roles[0] ?? '',
			'roles' => $contact_roles,
			'name'  => $name,
			'phone' => $phone,
			'email' => $email,
			'link'  => $link,
		);
	}

	if ( array() === $contacts ) {
		delete_post_meta( $post_id, '_wp_seed_event_contacts' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_contacts', $contacts );
}
function wp_seed_events_media_fields() {
	return array(
		'_wp_seed_event_flyer_pdf_id' => array(
			'label' => 'Flyer PDF',
			'type'  => 'application/pdf',
		),
	);
}

function wp_seed_events_add_media_meta_box() {
	add_meta_box(
		'wp_seed_events_media',
		'Illustrations / Flyer',
		'wp_seed_events_render_media_meta_box',
		'wp_seed_event',
		'normal',
		'default'
	);
}

function wp_seed_events_render_media_meta_box( $post ) {
	$illustration_ids = get_post_meta( $post->ID, '_wp_seed_event_illustration_ids', true );

	if ( ! is_array( $illustration_ids ) ) {
		$illustration_ids = array();
	}

	wp_nonce_field( 'wp_seed_events_save_media', 'wp_seed_events_media_nonce' );

	foreach ( wp_seed_events_media_fields() as $meta_key => $field ) {
		$attachment_id = (int) get_post_meta( $post->ID, $meta_key, true );
		$label         = $attachment_id ? get_the_title( $attachment_id ) : 'Aucun fichier choisi';
		?>
		<p>
			<strong><?php echo esc_html( $field['label'] ); ?></strong><br />
			<span data-wp-seed-media-label="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></span><br />
			<input type="hidden" name="wp_seed_event_media[<?php echo esc_attr( $meta_key ); ?>]" value="<?php echo esc_attr( (string) $attachment_id ); ?>" data-wp-seed-media-input="<?php echo esc_attr( $meta_key ); ?>" />
			<button type="button" class="button" data-wp-seed-media-select="<?php echo esc_attr( $meta_key ); ?>" data-media-type="<?php echo esc_attr( $field['type'] ); ?>" data-title="<?php echo esc_attr( $field['label'] ); ?>">Choisir</button>
			<button type="button" class="button" data-wp-seed-media-remove="<?php echo esc_attr( $meta_key ); ?>">Retirer</button>
		</p>
		<?php
	}
	?>
	<p><strong>Illustrations</strong></p>
	<div data-wp-seed-illustrations-list>
		<?php foreach ( $illustration_ids as $illustration_id ) : ?>
			<?php
			$illustration_id = absint( $illustration_id );

			if ( ! $illustration_id || 'attachment' !== get_post_type( $illustration_id ) ) {
				continue;
			}
			?>
			<p data-wp-seed-illustration-item>
				<span><?php echo esc_html( get_the_title( $illustration_id ) ); ?></span>
				<input type="hidden" name="wp_seed_event_illustrations[]" value="<?php echo esc_attr( (string) $illustration_id ); ?>" />
				<button type="button" class="button" data-wp-seed-illustration-remove>Retirer</button>
			</p>
		<?php endforeach; ?>
	</div>
	<p><button type="button" class="button" data-wp-seed-illustrations-select>Ajouter une illustration</button></p>
	<?php
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
	function wpSeedAddIllustration(attachment){
		var label=attachment.title||attachment.filename||attachment.url;
		var item=$('<p data-wp-seed-illustration-item></p>');
		item.append($('<span></span>').text(label));
		item.append(' ');
		item.append($('<input type="hidden" name="wp_seed_event_illustrations[]" />').val(attachment.id));
		item.append(' ');
		item.append($('<button type="button" class="button" data-wp-seed-illustration-remove>Retirer</button>'));
		$('[data-wp-seed-illustrations-list]').append(item);
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
			$('[data-wp-seed-media-input="'+key+'"]').val(attachment.id);
			$('[data-wp-seed-media-label="'+key+'"]').text(attachment.title||attachment.filename||attachment.url);
		});

		frame.open();
	});

	$(document).on('click','[data-wp-seed-media-remove]',function(e){
		e.preventDefault();
		var key=$(this).data('wp-seed-media-remove');
		$('[data-wp-seed-media-input="'+key+'"]').val('');
		$('[data-wp-seed-media-label="'+key+'"]').text('Aucun fichier choisi');
	});

	$(document).on('click','[data-wp-seed-illustrations-select]',function(e){
		e.preventDefault();
		var frame=wp.media({
			title:'Ajouter une illustration',
			button:{text:'Ajouter'},
			library:{type:'image'},
			multiple:true
		});

		frame.on('select',function(){
			frame.state().get('selection').each(function(attachment){
				wpSeedAddIllustration(attachment.toJSON());
			});
		});

		frame.open();
	});

	$(document).on('click','[data-wp-seed-illustration-remove]',function(e){
		e.preventDefault();
		$(this).closest('[data-wp-seed-illustration-item]').remove();
	});
});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){
	$(document).on('click','[data-wp-seed-event-type-add]',function(e){
		e.preventDefault();
		var root=$(this).closest('[data-wp-seed-event-type]');
		root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',false);
		root.find('[data-wp-seed-event-type-new-label]').trigger('focus');
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

		var key=label.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]+/g,'_').replace(/^_+|_+$/g,'');
		var options=root.find('[data-wp-seed-event-type-options]');

		if(!key){
			key='type_'+Date.now();
		}

		while(options.find('input[value="'+key+'"]').length){
			key=key+'_'+Date.now();
		}

		options.append(
			$('<p></p>').append(
				$('<label></label>')
					.append($('<input type="checkbox" name="wp_seed_event_types[]" checked />').val(key))
					.append(' '+label)
			)
		);
		root.find('[data-wp-seed-event-type-new-value]').val(label);
		root.find('[data-wp-seed-event-type-new-panel]').prop('hidden',true);
		input.val('');
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

	function writePanelRoles(personPanel,roles){
		roleFields(personPanel).prop('checked',false);
		$.each(roles||[],function(_,role){
			roleFields(personPanel).filter('[value="'+role+'"]').prop('checked',true);
		});
	}

	function readItem(item){
		return{
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
		field(personPanel,'name').val(data.name||'');
		field(personPanel,'phone').val(data.phone||'');
		field(personPanel,'email').val(data.email||'');
		field(personPanel,'link').val(data.link||'');
	}

	function openPanel(root,item){
		var personPanel=panel(root);
		var data=item?readItem(item):{roles:[],name:'',phone:'',email:'',link:''};
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
		var data={name:field(personPanel,'name').val(),phone:field(personPanel,'phone').val(),email:field(personPanel,'email').val(),link:field(personPanel,'link').val(),roles:readPanelRoles(personPanel)};
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
			start_date:item.find('[data-wp-seed-date-field="start_date"]').val(),
			end_date:item.find('[data-wp-seed-date-field="end_date"]').val(),
			start_time:item.find('[data-wp-seed-date-field="start_time"]').val(),
			end_time:item.find('[data-wp-seed-date-field="end_time"]').val(),
			all_day:item.find('[data-wp-seed-date-field="all_day"]').val(),
			cancelled:item.find('[data-wp-seed-date-field="cancelled"]').val()
		};
	}

	function wpSeedDateWriteItem(item,data){
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
		var fields=['start_date','end_date','start_time','end_time','all_day','cancelled'];
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
		var data={start_date:wpSeedDateField(panel,'start_date').val(),end_date:wpSeedDateField(panel,'end_date').val(),start_time:wpSeedDateField(panel,'start_time').val(),end_time:wpSeedDateField(panel,'end_time').val(),all_day:wpSeedDateField(panel,'all_day').prop('checked')?'1':'',cancelled:''};
		var item=panel.data('wpSeedDateItem');
		if(!data.start_date){
			wpSeedDateField(panel,'start_date').trigger('focus');
			return;
		}
		if(item){
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

	$raw_media = isset( $_POST['wp_seed_event_media'] ) && is_array( $_POST['wp_seed_event_media'] ) ? wp_unslash( $_POST['wp_seed_event_media'] ) : array();

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
}
