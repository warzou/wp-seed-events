<?php
/**
 * Lifecycle filter for the event admin list.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

add_action( 'restrict_manage_posts', 'wp_seed_events_render_lifecycle_filter', 10, 2 );
add_action( 'pre_get_posts', 'wp_seed_events_filter_event_admin_list_by_lifecycle', 99 );
add_filter( 'views_edit-wp_seed_event', 'wp_seed_events_preserve_lifecycle_in_admin_views' );

function wp_seed_events_lifecycle_filter_values() {
	return array( 'upcoming', 'past', 'undated', 'cancelled_only' );
}

function wp_seed_events_requested_lifecycle() {
	if ( ! isset( $_GET['wp_seed_event_lifecycle'] ) || ! is_scalar( $_GET['wp_seed_event_lifecycle'] ) ) {
		return '';
	}

	$lifecycle = sanitize_key( wp_unslash( (string) $_GET['wp_seed_event_lifecycle'] ) );

	return in_array( $lifecycle, wp_seed_events_lifecycle_filter_values(), true ) ? $lifecycle : '';
}

function wp_seed_events_add_lifecycle_to_admin_view_link( $view, $lifecycle ) {
	if ( ! is_string( $view ) || ! preg_match( '/\bhref=(["\'])(.*?)\1/i', $view, $matches, PREG_OFFSET_CAPTURE ) ) {
		return $view;
	}

	$url = add_query_arg(
		'wp_seed_event_lifecycle',
		$lifecycle,
		wp_specialchars_decode( $matches[2][0], ENT_QUOTES )
	);
	$url = esc_url( $url );

	if ( '' === $url ) {
		return $view;
	}

	return substr_replace( $view, $url, $matches[2][1], strlen( $matches[2][0] ) );
}

function wp_seed_events_preserve_lifecycle_in_admin_views( $views ) {
	if ( ! is_array( $views ) ) {
		return $views;
	}

	if ( ! function_exists( 'wp_seed_events_is_lifecycle_index_ready' ) || ! wp_seed_events_is_lifecycle_index_ready() ) {
		return $views;
	}

	$lifecycle = wp_seed_events_requested_lifecycle();

	if ( '' === $lifecycle ) {
		return $views;
	}

	foreach ( $views as $key => $view ) {
		$views[ $key ] = wp_seed_events_add_lifecycle_to_admin_view_link( $view, $lifecycle );
	}

	return $views;
}

function wp_seed_events_render_lifecycle_filter( $post_type, $which = 'top' ) {
	if ( 'wp_seed_event' !== $post_type || 'top' !== $which ) {
		return;
	}

	if ( ! function_exists( 'wp_seed_events_is_lifecycle_index_ready' ) || ! wp_seed_events_is_lifecycle_index_ready() ) {
		return;
	}

	$selected = wp_seed_events_requested_lifecycle();
	$options  = array(
		''               => __( 'Toutes les dates', 'wp-seed-events' ),
		'upcoming'       => __( 'À venir', 'wp-seed-events' ),
		'past'           => __( 'Passés', 'wp-seed-events' ),
		'undated'        => __( 'Sans date', 'wp-seed-events' ),
		'cancelled_only' => __( 'Annulés', 'wp-seed-events' ),
	);

	echo '<label class="screen-reader-text" for="wp-seed-event-lifecycle-filter">' . esc_html__( 'Filtrer les événements par date', 'wp-seed-events' ) . '</label>';
	echo '<select name="wp_seed_event_lifecycle" id="wp-seed-event-lifecycle-filter">';

	foreach ( $options as $value => $label ) {
		printf(
			'<option value="%1$s"%2$s>%3$s</option>',
			esc_attr( $value ),
			selected( $selected, $value, false ),
			esc_html( $label )
		);
	}

	echo '</select>';
}

function wp_seed_events_lifecycle_meta_query( $lifecycle, $today ) {
	$dated_count_key      = '_wp_seed_event_lifecycle_index_dated_count';
	$last_active_date_key = '_wp_seed_event_lifecycle_index_last_active_date';

	switch ( $lifecycle ) {
		case 'upcoming':
			return array(
				array(
					'key'     => $last_active_date_key,
					'value'   => $today,
					'compare' => '>=',
					'type'    => 'DATE',
				),
			);

		case 'past':
			return array(
				'relation' => 'AND',
				array(
					'key'     => $last_active_date_key,
					'value'   => '',
					'compare' => '!=',
					'type'    => 'CHAR',
				),
				array(
					'key'     => $last_active_date_key,
					'value'   => $today,
					'compare' => '<',
					'type'    => 'DATE',
				),
			);

		case 'undated':
			return array(
				array(
					'key'     => $dated_count_key,
					'value'   => 0,
					'compare' => '=',
					'type'    => 'NUMERIC',
				),
			);

		case 'cancelled_only':
			return array(
				'relation' => 'AND',
				array(
					'key'     => $dated_count_key,
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
				array(
					'key'     => $last_active_date_key,
					'value'   => '',
					'compare' => '=',
					'type'    => 'CHAR',
				),
			);
	}

	return array();
}

function wp_seed_events_combine_lifecycle_meta_query( $existing_meta_query, $lifecycle_meta_query ) {
	if ( ! is_array( $existing_meta_query ) || array() === $existing_meta_query ) {
		return $lifecycle_meta_query;
	}

	return array(
		'relation' => 'AND',
		$existing_meta_query,
		$lifecycle_meta_query,
	);
}

function wp_seed_events_filter_event_admin_list_by_lifecycle( $query ) {
	global $pagenow;

	if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query instanceof WP_Query || ! $query->is_main_query() ) {
		return;
	}

	if ( 'wp_seed_event' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! function_exists( 'wp_seed_events_is_lifecycle_index_ready' ) || ! wp_seed_events_is_lifecycle_index_ready() ) {
		return;
	}

	$lifecycle = wp_seed_events_requested_lifecycle();

	if ( '' === $lifecycle ) {
		return;
	}

	$lifecycle_meta_query = wp_seed_events_lifecycle_meta_query( $lifecycle, current_datetime()->format( 'Y-m-d' ) );

	if ( array() === $lifecycle_meta_query ) {
		return;
	}

	$query->set(
		'meta_query',
		wp_seed_events_combine_lifecycle_meta_query(
			$query->get( 'meta_query' ),
			$lifecycle_meta_query
		)
	);
}
