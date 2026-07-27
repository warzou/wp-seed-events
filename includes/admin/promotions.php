<?php
/**
 * Promotion administration.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the internal Promotion entity.
 */
function wp_seed_events_register_promotion_post_type() {
	register_post_type(
		'wp_seed_promotion',
		array(
			'labels'              => array(
				'name'               => 'Promotions',
				'singular_name'      => 'Promotion',
				'menu_name'          => 'Promotions',
				'add_new_item'       => 'Ajouter une promotion',
				'edit_item'          => 'Modifier la promotion',
				'new_item'           => 'Nouvelle promotion',
				'view_item'          => 'Voir la promotion',
				'search_items'       => 'Rechercher des promotions',
				'not_found'          => 'Aucune promotion trouvée.',
				'not_found_in_trash' => 'Aucune promotion dans la corbeille.',
				'all_items'          => 'Toutes les promotions',
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=wp_seed_event',
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'supports'            => array( 'title', 'editor' ),
			'map_meta_cap'        => true,
		)
	);
}

/**
 * Add Promotion fields.
 */
function wp_seed_events_add_promotion_meta_box() {
	add_meta_box(
		'wp-seed-promotion-settings',
		'Paramètres de la promotion',
		'wp_seed_events_render_promotion_meta_box',
		'wp_seed_promotion',
		'normal',
		'high'
	);
}
/**
 * Normalize and uniquify the editable Promotion slug.
 *
 * @param array $data Prepared post data.
 * @param array $postarr Raw post data.
 * @return array
 */
function wp_seed_events_prepare_promotion_post_data( $data, $postarr ) {
	if ( empty( $data['post_type'] ) || 'wp_seed_promotion' !== $data['post_type'] ) {
		return $data;
	}

	$requested_slug = '';

	if ( isset( $_POST['wp_seed_promotion_slug'] ) && is_scalar( $_POST['wp_seed_promotion_slug'] ) ) {
		$requested_slug = sanitize_title( wp_unslash( $_POST['wp_seed_promotion_slug'] ) );
	}

	$current_slug = sanitize_title( (string) ( $data['post_name'] ?? '' ) );
	$title_slug   = sanitize_title( (string) ( $data['post_title'] ?? '' ) );
	$slug         = '' !== $requested_slug ? $requested_slug : ( '' !== $current_slug ? $current_slug : $title_slug );

	if ( '' === $slug ) {
		return $data;
	}

	$data['post_name'] = wp_unique_post_slug(
		$slug,
		absint( $postarr['ID'] ?? 0 ),
		'publish',
		'wp_seed_promotion',
		0
	);

	return $data;
}


/**
 * Render Promotion fields.
 *
 * @param WP_Post $post Promotion.
 */
function wp_seed_events_render_promotion_meta_box( $post ) {
	$start_year = wp_seed_events_normalize_promotion_start_year( get_post_meta( $post->ID, '_wp_seed_promotion_start_year', true ) );
	$status     = wp_seed_events_normalize_promotion_status( get_post_meta( $post->ID, '_wp_seed_promotion_status', true ) );
	$order      = (int) get_post_meta( $post->ID, '_wp_seed_promotion_order', true );

	wp_nonce_field( 'wp_seed_events_save_promotion', 'wp_seed_events_promotion_nonce' );
	?>
	<p>
		<label for="wp-seed-promotion-slug"><strong>Slug</strong></label><br />
		<input id="wp-seed-promotion-slug" name="wp_seed_promotion_slug" type="text" class="regular-text" value="<?php echo esc_attr( (string) $post->post_name ); ?>" />
	</p>
	<p>
		<label for="wp-seed-promotion-start-year"><strong>Année de début</strong></label><br />
		<input id="wp-seed-promotion-start-year" name="wp_seed_promotion_start_year" type="number" min="1000" max="9999" step="1" value="<?php echo esc_attr( 0 < $start_year ? (string) $start_year : '' ); ?>" />
	</p>
	<p>
		<label for="wp-seed-promotion-status"><strong>Statut</strong></label><br />
		<select id="wp-seed-promotion-status" name="wp_seed_promotion_status">
			<option value="active" <?php selected( 'active', $status ); ?>>Active</option>
			<option value="archived" <?php selected( 'archived', $status ); ?>>Archivée</option>
		</select>
	</p>
	<p>
		<label for="wp-seed-promotion-order"><strong>Ordre</strong></label><br />
		<input id="wp-seed-promotion-order" name="wp_seed_promotion_order" type="number" step="1" value="<?php echo esc_attr( (string) $order ); ?>" />
	</p>
	<p class="description">Une promotion archivée reste visible sur les anciennes occurrences, mais ne peut plus être attribuée à une nouvelle occurrence.</p>
	<?php if ( wp_seed_events_promotion_is_referenced( $post->ID ) ) : ?>
		<p class="notice notice-warning inline">Cette promotion est utilisée par au moins une occurrence. Archivez-la plutôt que de la supprimer.</p>
	<?php endif; ?>
	<?php
}

/**
 * Save Promotion fields.
 *
 * @param int $post_id Promotion post ID.
 */
function wp_seed_events_save_promotion( $post_id ) {
	if (
		! isset( $_POST['wp_seed_events_promotion_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wp_seed_events_promotion_nonce'] ) ), 'wp_seed_events_save_promotion' )
		|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		|| ! current_user_can( 'edit_post', $post_id )
	) {
		return;
	}

	$start_year = wp_seed_events_normalize_promotion_start_year( $_POST['wp_seed_promotion_start_year'] ?? 0 );
	$status     = wp_seed_events_normalize_promotion_status( $_POST['wp_seed_promotion_status'] ?? 'active' );
	$order      = isset( $_POST['wp_seed_promotion_order'] ) ? (int) wp_unslash( $_POST['wp_seed_promotion_order'] ) : 0;

	if ( 0 < $start_year ) {
		update_post_meta( $post_id, '_wp_seed_promotion_start_year', $start_year );
	} else {
		delete_post_meta( $post_id, '_wp_seed_promotion_start_year' );
	}

	update_post_meta( $post_id, '_wp_seed_promotion_status', $status );
	update_post_meta( $post_id, '_wp_seed_promotion_order', $order );

	unset( $GLOBALS['wp_seed_events_promotion_cache'] );
}

/**
 * Return whether a Promotion is referenced by any stored occurrence.
 *
 * @param int $promotion_id Promotion post ID.
 * @return bool
 */
function wp_seed_events_promotion_is_referenced( $promotion_id ) {
	$promotion_id = absint( $promotion_id );

	if ( 0 === $promotion_id ) {
		return false;
	}

	$event_ids = get_posts(
		array(
			'post_type'      => 'wp_seed_event',
			'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private', 'trash' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	);

	foreach ( is_array( $event_ids ) ? $event_ids : array() as $event_id ) {
		$occurrences = get_post_meta( $event_id, '_wp_seed_event_occurrences', true );

		foreach ( is_array( $occurrences ) ? $occurrences : array() as $occurrence ) {
			if ( is_array( $occurrence ) && $promotion_id === absint( $occurrence['promotion_id'] ?? 0 ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Block trashing or deleting a referenced Promotion.
 *
 * @param mixed   $delete Existing pre-filter value.
 * @param WP_Post $post Post being removed.
 * @return mixed
 */
function wp_seed_events_prevent_referenced_promotion_deletion( $delete, $post ) {
	if (
		$post instanceof WP_Post
		&& 'wp_seed_promotion' === $post->post_type
		&& wp_seed_events_promotion_is_referenced( $post->ID )
	) {
		$GLOBALS['wp_seed_events_promotion_delete_blocked'] = true;
		return false;
	}

	return $delete;
}

/**
 * Add a deletion warning to the post redirect.
 *
 * @param string $location Redirect URL.
 * @return string
 */
function wp_seed_events_promotion_delete_blocked_redirect( $location ) {
	if ( ! empty( $GLOBALS['wp_seed_events_promotion_delete_blocked'] ) ) {
		$location = add_query_arg( 'wp_seed_events_promotion_delete_blocked', '1', $location );
	}

	if ( ! empty( $GLOBALS['wp_seed_events_occurrences_validation_error'] ) ) {
		$location = add_query_arg( 'wp_seed_events_occurrences_validation_error', '1', $location );
	}

	return $location;
}

/**
 * Render a controlled deletion warning.
 */
function wp_seed_events_promotion_admin_notice() {
	if ( ! empty( $_GET['wp_seed_events_promotion_delete_blocked'] ) ) {
		echo '<div class="notice notice-error"><p>Cette promotion est encore utilisée par une occurrence. Archivez-la ou retirez d’abord toutes ses associations.</p></div>';
	}

	if ( ! empty( $_GET['wp_seed_events_occurrences_validation_error'] ) ) {
		echo '<div class="notice notice-error"><p>Les dates n’ont pas été modifiées : vérifiez que chaque promotion possède une année du parcours valide et qu’aucune nouvelle association ne cible une promotion archivée.</p></div>';
	}
}

/**
 * Remove destructive row actions for referenced Promotions.
 *
 * @param array   $actions Row actions.
 * @param WP_Post $post Promotion.
 * @return array
 */
function wp_seed_events_promotion_row_actions( $actions, $post ) {
	if (
		$post instanceof WP_Post
		&& 'wp_seed_promotion' === $post->post_type
		&& wp_seed_events_promotion_is_referenced( $post->ID )
	) {
		unset( $actions['trash'], $actions['delete'] );
	}

	return $actions;
}

/**
 * Define Promotion list columns.
 *
 * @param array $columns Native columns.
 * @return array
 */
function wp_seed_events_promotion_admin_columns( $columns ) {
	return array(
		'cb'                           => $columns['cb'] ?? '<input type="checkbox" />',
		'title'                        => 'Promotion',
		'wp_seed_promotion_start_year' => 'Année de début',
		'wp_seed_promotion_status'     => 'Statut',
		'wp_seed_promotion_order'      => 'Ordre',
		'date'                         => $columns['date'] ?? 'Date',
	);
}

/**
 * Make Promotion business columns sortable.
 *
 * @param array $columns Sortable columns.
 * @return array
 */
function wp_seed_events_promotion_sortable_columns( $columns ) {
	$columns['wp_seed_promotion_start_year'] = 'wp_seed_promotion_start_year';
	$columns['wp_seed_promotion_status']     = 'wp_seed_promotion_status';
	$columns['wp_seed_promotion_order']      = 'wp_seed_promotion_order';

	return $columns;
}

/**
 * Apply Promotion-only ordering to the main administration query.
 *
 * @param WP_Query $query Administration query.
 */
function wp_seed_events_apply_promotion_admin_order( $query ) {
	if (
		! is_admin()
		|| ! $query->is_main_query()
		|| 'wp_seed_promotion' !== $query->get( 'post_type' )
	) {
		return;
	}

	$orderby = $query->get( 'orderby' );

	if ( 'wp_seed_promotion_start_year' === $orderby ) {
		$query->set( 'meta_key', '_wp_seed_promotion_start_year' );
		$query->set( 'orderby', 'meta_value_num' );
		return;
	}

	if ( 'wp_seed_promotion_status' === $orderby ) {
		$query->set( 'meta_key', '_wp_seed_promotion_status' );
		$query->set( 'orderby', 'meta_value' );
		return;
	}

	if ( 'wp_seed_promotion_order' === $orderby ) {
		$query->set( 'meta_key', '_wp_seed_promotion_order' );
		$query->set( 'orderby', 'meta_value_num' );
	}
}

/**
 * Render Promotion list columns.
 *
 * @param string $column Column key.
 * @param int    $post_id Promotion post ID.
 */
function wp_seed_events_render_promotion_admin_column( $column, $post_id ) {
	if ( 'wp_seed_promotion_start_year' === $column ) {
		$year = wp_seed_events_normalize_promotion_start_year( get_post_meta( $post_id, '_wp_seed_promotion_start_year', true ) );
		echo 0 < $year ? esc_html( (string) $year ) : '—';
		return;
	}

	if ( 'wp_seed_promotion_status' === $column ) {
		echo 'archived' === wp_seed_events_normalize_promotion_status( get_post_meta( $post_id, '_wp_seed_promotion_status', true ) )
			? 'Archivée'
			: 'Active';
		return;
	}

	if ( 'wp_seed_promotion_order' === $column ) {
		echo esc_html( (string) (int) get_post_meta( $post_id, '_wp_seed_promotion_order', true ) );
	}
}
