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
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_occurrences_meta_box' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_occurrences' );

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

function wp_seed_events_render_occurrences_meta_box( $post ) {
	$occurrences = get_post_meta( $post->ID, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $occurrences ) ) {
		$occurrences = array();
	}

	wp_nonce_field( 'wp_seed_events_save_occurrences', 'wp_seed_events_occurrences_nonce' );

	for ( $index = 0; $index < 5; $index++ ) {
		$occurrence = isset( $occurrences[ $index ] ) && is_array( $occurrences[ $index ] ) ? $occurrences[ $index ] : array();
		$is_open    = 0 === $index || ! empty( $occurrence['start_date'] );
		$summary    = $is_open ? 'Occurrence ' . ( $index + 1 ) : 'Ajouter une occurrence ' . ( $index + 1 );
		?>
		<details <?php echo $is_open ? 'open' : ''; ?>>
			<summary><?php echo esc_html( $summary ); ?></summary>
			<p>
				<label>
					Date de début<br />
					<input type="date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_date]" value="<?php echo esc_attr( $occurrence['start_date'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Date de fin (facultative)<br />
					<input type="date" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_date]" value="<?php echo esc_attr( $occurrence['end_date'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Heure de début (facultative)<br />
					<input type="time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][start_time]" value="<?php echo esc_attr( $occurrence['start_time'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Heure de fin (facultative)<br />
					<input type="time" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][end_time]" value="<?php echo esc_attr( $occurrence['end_time'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					<input type="checkbox" name="wp_seed_events_occurrences[<?php echo esc_attr( (string) $index ); ?>][all_day]" value="1" <?php checked( ! empty( $occurrence['all_day'] ) ); ?> />
					Journée entière
				</label>
			</p>
		</details>
		<?php
	}
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
		);
	}

	if ( array() === $occurrences ) {
		delete_post_meta( $post_id, '_wp_seed_event_occurrences' );
		return;
	}

	update_post_meta( $post_id, '_wp_seed_event_occurrences', $occurrences );
}
