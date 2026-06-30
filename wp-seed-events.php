<?php
/**
 * Plugin Name: WP Seed Events
 * Description: Autonomous event publishing foundation for WordPress.
 * Version: 0.1.0
 * Author: WP Seed
 * Text Domain: wp-seed-events
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_SEED_EVENTS_VERSION', '0.1.0' );

add_action( 'init', 'wp_seed_events_register_event_post_type' );

function wp_seed_events_register_event_post_type() {
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
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'menu_icon'    => 'dashicons-calendar-alt',
			'supports'     => array( 'title' ),
			'show_in_rest' => false,
		)
	);
}
