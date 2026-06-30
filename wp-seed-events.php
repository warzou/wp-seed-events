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
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_place_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_contacts_meta_box' );
add_action( 'add_meta_boxes_wp_seed_event', 'wp_seed_events_add_media_meta_box' );
add_action( 'add_meta_boxes_wp_seed_place', 'wp_seed_events_add_place_address_meta_box' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_occurrences' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_event_place' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_contacts' );
add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_media' );
add_action( 'save_post_wp_seed_place', 'wp_seed_events_save_place_address' );
add_action( 'admin_enqueue_scripts', 'wp_seed_events_enqueue_media_admin' );

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
			'supports'     => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest' => false,
		)
	);

	register_post_type(
		'wp_seed_place',
		array(
			'labels'       => array(
				'name'          => 'Lieux',
				'singular_name' => 'Lieu',
				'menu_name'     => 'Lieux',
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

function wp_seed_events_render_occurrences_meta_box( $post ) {
	$occurrences = get_post_meta( $post->ID, '_wp_seed_event_occurrences', true );

	if ( ! is_array( $occurrences ) ) {
		$occurrences = array();
	}

	wp_nonce_field( 'wp_seed_events_save_occurrences', 'wp_seed_events_occurrences_nonce' );

	$previous_occurrence = array();
	$visible_occurrences = 0;

	for ( $index = 0; $index < 5; $index++ ) {
		$occurrence           = isset( $occurrences[ $index ] ) && is_array( $occurrences[ $index ] ) ? $occurrences[ $index ] : array();
		$has_saved_occurrence = array() !== $occurrence;

		if ( ! $has_saved_occurrence && 0 < $index && array() !== $previous_occurrence ) {
			$occurrence['start_time'] = $previous_occurrence['start_time'] ?? '';
			$occurrence['end_time']   = $previous_occurrence['end_time'] ?? '';
			$occurrence['all_day']    = $previous_occurrence['all_day'] ?? '';
		}

		$is_visible          = 0 === $index || $has_saved_occurrence;
		$summary             = 'Date ' . ( $index + 1 );
		$previous_occurrence = $occurrence;

		if ( $is_visible ) {
			$visible_occurrences++;
		}
		?>
		<details data-wp-seed-progressive-item="occurrence" data-wp-seed-progressive-index="<?php echo esc_attr( (string) $index ); ?>" <?php echo $is_visible ? 'open' : 'hidden'; ?>>
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
	?>
	<p><button type="button" class="button" data-wp-seed-progressive-add="occurrence" <?php echo 5 <= $visible_occurrences ? 'hidden' : ''; ?>>+ Ajouter une autre date</button></p>
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

function wp_seed_events_render_place_meta_box( $post ) {
	$selected_place_id = (int) get_post_meta( $post->ID, '_wp_seed_event_place_id', true );
	$place_details     = get_post_meta( $post->ID, '_wp_seed_event_place_details', true );
	$places            = get_posts(
		array(
			'post_type'      => 'wp_seed_place',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	wp_nonce_field( 'wp_seed_events_save_event_place', 'wp_seed_events_place_nonce' );
	?>
	<p>
		<label>
			Choisir un lieu existant<br />
			<select name="wp_seed_event_place_id">
				<option value="">Aucun lieu</option>
				<?php foreach ( $places as $place ) : ?>
					<?php $address = get_post_meta( $place->ID, '_wp_seed_place_address', true ); ?>
					<option value="<?php echo esc_attr( (string) $place->ID ); ?>" <?php selected( $selected_place_id, $place->ID ); ?>>
						<?php echo esc_html( $address ? $place->post_title . ' - ' . $address : $place->post_title ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</label>
	</p>
	<p>
		<label>
			Informations complémentaires<br />
			<textarea name="wp_seed_event_place_details" rows="3" style="width: 100%;"><?php echo esc_textarea( $place_details ); ?></textarea>
		</label>
	</p>
	<p><strong>Créer un nouveau lieu</strong></p>
	<p>
		<label>
			Nom du lieu<br />
			<input type="text" name="wp_seed_new_place_name" value="" />
		</label>
	</p>
	<p>
		<label>
			Adresse<br />
			<input type="text" name="wp_seed_new_place_address" value="" />
		</label>
	</p>
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

	$place_id      = isset( $_POST['wp_seed_event_place_id'] ) ? (int) $_POST['wp_seed_event_place_id'] : 0;
	$place_name    = isset( $_POST['wp_seed_new_place_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_place_name'] ) ) : '';
	$address       = isset( $_POST['wp_seed_new_place_address'] ) ? sanitize_text_field( wp_unslash( $_POST['wp_seed_new_place_address'] ) ) : '';
	$place_details = isset( $_POST['wp_seed_event_place_details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wp_seed_event_place_details'] ) ) : '';

	if ( '' !== $place_details ) {
		update_post_meta( $post_id, '_wp_seed_event_place_details', $place_details );
	} else {
		delete_post_meta( $post_id, '_wp_seed_event_place_details' );
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

function wp_seed_events_render_contacts_meta_box( $post ) {
	$contacts = get_post_meta( $post->ID, '_wp_seed_event_contacts', true );
	$roles    = wp_seed_events_contact_roles();

	if ( ! is_array( $contacts ) ) {
		$contacts = array();
	}

	wp_nonce_field( 'wp_seed_events_save_contacts', 'wp_seed_events_contacts_nonce' );

	$visible_contacts = 0;

	for ( $index = 0; $index < 5; $index++ ) {
		$contact           = isset( $contacts[ $index ] ) && is_array( $contacts[ $index ] ) ? $contacts[ $index ] : array();
		$has_saved_contact = array() !== $contact;
		$is_visible        = 0 === $index || $has_saved_contact;
		$summary           = $is_visible && ! empty( $contact['name'] ) ? $contact['name'] : 'Personne ' . ( $index + 1 );

		if ( $is_visible ) {
			$visible_contacts++;
		}
		?>
		<details data-wp-seed-progressive-item="contact" data-wp-seed-progressive-index="<?php echo esc_attr( (string) $index ); ?>" <?php echo $is_visible ? 'open' : 'hidden'; ?>>
			<summary><?php echo esc_html( $summary ); ?></summary>
			<p>
				<label>
					Nom<br />
					<input type="text" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][name]" value="<?php echo esc_attr( $contact['name'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Téléphone<br />
					<input type="tel" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][phone]" value="<?php echo esc_attr( $contact['phone'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Email<br />
					<input type="email" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][email]" value="<?php echo esc_attr( $contact['email'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Lien (facultatif)<br />
					<input type="url" name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][link]" value="<?php echo esc_attr( $contact['link'] ?? '' ); ?>" />
				</label>
			</p>
			<p>
				<label>
					Rôle<br />
					<select name="wp_seed_events_contacts[<?php echo esc_attr( (string) $index ); ?>][role]">
						<?php foreach ( $roles as $role_value => $role_label ) : ?>
							<option value="<?php echo esc_attr( $role_value ); ?>" <?php selected( $contact['role'] ?? '', $role_value ); ?>><?php echo esc_html( $role_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
			</p>
		</details>
		<?php
	}
	?>
	<p><button type="button" class="button" data-wp-seed-progressive-add="contact" <?php echo 5 <= $visible_contacts ? 'hidden' : ''; ?>>+ Ajouter une personne</button></p>
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

	$raw_contacts = isset( $_POST['wp_seed_events_contacts'] ) && is_array( $_POST['wp_seed_events_contacts'] ) ? wp_unslash( $_POST['wp_seed_events_contacts'] ) : array();
	$roles        = wp_seed_events_contact_roles();
	$contacts     = array();

	foreach ( $raw_contacts as $raw_contact ) {
		if ( ! is_array( $raw_contact ) ) {
			continue;
		}

		$role  = isset( $raw_contact['role'] ) ? sanitize_key( $raw_contact['role'] ) : 'organizer';
		$name  = isset( $raw_contact['name'] ) ? sanitize_text_field( $raw_contact['name'] ) : '';
		$phone = isset( $raw_contact['phone'] ) ? sanitize_text_field( $raw_contact['phone'] ) : '';
		$email = isset( $raw_contact['email'] ) ? sanitize_email( $raw_contact['email'] ) : '';
		$link  = isset( $raw_contact['link'] ) ? esc_url_raw( $raw_contact['link'] ) : '';

		if ( '' === $name ) {
			continue;
		}

		if ( ! isset( $roles[ $role ] ) ) {
			$role = 'organizer';
		}

		$contacts[] = array(
			'role'  => $role,
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
		'Flyer',
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
jQuery(function($){function wpSeedAddIllustration(attachment){var label=attachment.title||attachment.filename||attachment.url;var item=$('<p data-wp-seed-illustration-item></p>');item.append($('<span></span>').text(label));item.append(' ');item.append($('<input type="hidden" name="wp_seed_event_illustrations[]" />').val(attachment.id));item.append(' ');item.append($('<button type="button" class="button" data-wp-seed-illustration-remove>Retirer</button>'));$('[data-wp-seed-illustrations-list]').append(item);}$(document).on('click','[data-wp-seed-media-select]',function(e){e.preventDefault();var button=$(this);var key=button.data('wp-seed-media-select');var type=button.data('media-type');var frame=wp.media({title:button.data('title'),button:{text:'Choisir'},library:{type:type},multiple:false});frame.on('select',function(){var attachment=frame.state().get('selection').first().toJSON();$('[data-wp-seed-media-input="'+key+'"]').val(attachment.id);$('[data-wp-seed-media-label="'+key+'"]').text(attachment.title||attachment.filename||attachment.url);});frame.open();});$(document).on('click','[data-wp-seed-media-remove]',function(e){e.preventDefault();var key=$(this).data('wp-seed-media-remove');$('[data-wp-seed-media-input="'+key+'"]').val('');$('[data-wp-seed-media-label="'+key+'"]').text('Aucun fichier choisi');});$(document).on('click','[data-wp-seed-illustrations-select]',function(e){e.preventDefault();var frame=wp.media({title:'Ajouter une illustration',button:{text:'Ajouter'},library:{type:'image'},multiple:true});frame.on('select',function(){frame.state().get('selection').each(function(attachment){wpSeedAddIllustration(attachment.toJSON());});});frame.open();});$(document).on('click','[data-wp-seed-illustration-remove]',function(e){e.preventDefault();$(this).closest('[data-wp-seed-illustration-item]').remove();});});
JS
	);
	wp_add_inline_script(
		'jquery',
		<<<'JS'
jQuery(function($){function refreshButton(type){var hasHidden=$('[data-wp-seed-progressive-item="'+type+'"][hidden]').length>0;$('[data-wp-seed-progressive-add="'+type+'"]').prop('hidden',!hasHidden);}function copyPreviousOccurrenceValues(item){var previous=item.prevAll('[data-wp-seed-progressive-item="occurrence"]').first();if(!previous.length){return;}var startInput=item.find('input[name$="[start_time]"]');var endInput=item.find('input[name$="[end_time]"]');if(!startInput.val()){startInput.val(previous.find('input[name$="[start_time]"]').val());}if(!endInput.val()){endInput.val(previous.find('input[name$="[end_time]"]').val());}item.find('input[name$="[all_day]"]').prop('checked',previous.find('input[name$="[all_day]"]').prop('checked'));}$(document).on('click','[data-wp-seed-progressive-add]',function(e){e.preventDefault();var type=$(this).data('wp-seed-progressive-add');var item=$('[data-wp-seed-progressive-item="'+type+'"][hidden]').first();if(!item.length){refreshButton(type);return;}item.prop('hidden',false).prop('open',true);if('occurrence'===type){copyPreviousOccurrenceValues(item);}refreshButton(type);});});
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
