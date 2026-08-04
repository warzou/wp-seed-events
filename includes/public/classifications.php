<?php
/**
 * Native WordPress projections for event classifications and ordering.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_event_type_taxonomy() {
	return 'wp_seed_event_type';
}

function wp_seed_events_event_flag_taxonomy() {
	return 'wp_seed_event_flag';
}

function wp_seed_events_featured_term_slug() {
	return 'featured';
}

function wp_seed_events_next_occurrence_sort_meta_key() {
	return '_wp_seed_event_next_occurrence_sort';
}

function wp_seed_events_register_native_classifications() {
	register_taxonomy(
		wp_seed_events_event_type_taxonomy(),
		array( 'wp_seed_event' ),
		array(
			'labels'             => array(
				'name'          => __( 'Types d’événement', 'wp-seed-events' ),
				'singular_name' => __( 'Type d’événement', 'wp-seed-events' ),
			),
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'meta_box_cb'        => false,
			'show_admin_column'  => false,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => true,
		)
	);

	register_taxonomy(
		wp_seed_events_event_flag_taxonomy(),
		array( 'wp_seed_event' ),
		array(
			'labels'             => array(
				'name'          => __( 'Indicateurs d’événement', 'wp-seed-events' ),
				'singular_name' => __( 'Indicateur d’événement', 'wp-seed-events' ),
			),
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_admin_column'  => false,
			'show_in_rest'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => false,
			'hierarchical'       => false,
			'rewrite'            => false,
			'query_var'          => true,
		)
	);
}

function wp_seed_events_native_event_type_slug( $type_key ) {
	$type_key = sanitize_key( $type_key );
	$stable   = array(
		'journee_decouverte'  => 'journee-decouverte',
		'reunion_information' => 'reunion-information',
	);

	return '' === $type_key ? '' : ( $stable[ $type_key ] ?? sanitize_title( str_replace( '_', '-', $type_key ) ) );
}

function wp_seed_events_ensure_native_event_type_term( $type_key, $label ) {
	$taxonomy = wp_seed_events_event_type_taxonomy();
	$slug     = wp_seed_events_native_event_type_slug( $type_key );

	if ( '' === $slug ) {
		return new WP_Error( 'event_type_term_invalid', 'Event type term is invalid.' );
	}

	$term = get_term_by( 'slug', $slug, $taxonomy );

	if ( $term instanceof WP_Term ) {
		if ( (string) $term->name !== (string) $label ) {
			$updated = wp_update_term( $term->term_id, $taxonomy, array( 'name' => sanitize_text_field( $label ) ) );
			if ( is_wp_error( $updated ) ) {
				return $updated;
			}
		}
		return absint( $term->term_id );
	}

	$created = wp_insert_term( sanitize_text_field( $label ), $taxonomy, array( 'slug' => $slug ) );

	return is_wp_error( $created ) ? $created : absint( $created['term_id'] ?? 0 );
}

function wp_seed_events_ensure_featured_term() {
	$taxonomy = wp_seed_events_event_flag_taxonomy();
	$slug     = wp_seed_events_featured_term_slug();
	$term     = get_term_by( 'slug', $slug, $taxonomy );

	if ( $term instanceof WP_Term ) {
		return absint( $term->term_id );
	}

	$created = wp_insert_term(
		__( 'Événement épinglé', 'wp-seed-events' ),
		$taxonomy,
		array(
			'slug'        => $slug,
			'description' => __( 'Projection technique de la case Épingler cet événement.', 'wp-seed-events' ),
		)
	);

	return is_wp_error( $created ) ? $created : absint( $created['term_id'] ?? 0 );
}

function wp_seed_events_event_type_data_for_event( $event_id ) {
	$options = wp_seed_events_event_type_options();
	$primary_key = wp_seed_events_primary_type_for_event( $event_id );
	$all_types = array();
	foreach ( wp_seed_events_event_type_keys_for_event( $event_id ) as $key ) {
		if ( isset( $options[ $key ] ) ) {
			$all_types[] = array(
				'key' => $key,
				'slug' => wp_seed_events_native_event_type_slug( $key ),
				'label' => (string) $options[ $key ],
			);
		}
	}
	$primary = null;
	$secondary = array();
	foreach ( $all_types as $type ) {
		if ( $type['key'] === $primary_key ) {
			$primary = $type;
		} else {
			$secondary[] = $type;
		}
	}
	return array(
		'primary_type' => $primary,
		'secondary_types' => $secondary,
		'all_types' => $all_types,
	);
}
function wp_seed_events_event_is_pinned( $event_id ) {
	return '1' === (string) get_post_meta( absint( $event_id ), '_wp_seed_event_pinned', true );
}
function wp_seed_events_sync_native_event_classifications( $event_id ) {
	$event_id = absint( $event_id );
	$post     = get_post( $event_id );

	if ( ! $post instanceof WP_Post || 'wp_seed_event' !== $post->post_type ) {
		return new WP_Error( 'event_classification_invalid_event', 'Event classifications require a valid event.' );
	}

	$options  = wp_seed_events_event_type_options();
	$type_ids = array();

	foreach ( wp_seed_events_event_type_keys_for_event( $event_id ) as $type_key ) {
		if ( ! isset( $options[ $type_key ] ) ) {
			continue;
		}
		$term_id = wp_seed_events_ensure_native_event_type_term( $type_key, $options[ $type_key ] );
		if ( is_wp_error( $term_id ) ) {
			return $term_id;
		}
		$type_ids[] = absint( $term_id );
	}

	$result = wp_set_object_terms( $event_id, array_values( array_unique( $type_ids ) ), wp_seed_events_event_type_taxonomy(), false );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$flag_ids = array();
	if ( '1' === (string) get_post_meta( $event_id, '_wp_seed_event_pinned', true ) ) {
		$featured_id = wp_seed_events_ensure_featured_term();
		if ( is_wp_error( $featured_id ) ) {
			return $featured_id;
		}
		$flag_ids[] = absint( $featured_id );
	}

	$result = wp_set_object_terms( $event_id, $flag_ids, wp_seed_events_event_flag_taxonomy(), false );

	return is_wp_error( $result ) ? $result : true;
}

function wp_seed_events_calculate_next_occurrence_sort( $event_id ) {
	$occurrences = wp_seed_events_get_event_occurrences(
		absint( $event_id ),
		array(
			'include_cancelled' => false,
			'only_active'       => true,
			'status'            => 'future',
		)
	);

	foreach ( $occurrences as $occurrence ) {
		if ( ! empty( $occurrence['start_sort'] ) ) {
			return (string) $occurrence['start_sort'];
		}
	}
	return '';
}

function wp_seed_events_sync_next_occurrence_sort( $event_id ) {
	$event_id = absint( $event_id );
	$value    = wp_seed_events_calculate_next_occurrence_sort( $event_id );
	$key      = wp_seed_events_next_occurrence_sort_meta_key();

	if ( '' === $value ) {
		delete_post_meta( $event_id, $key );
		return '';
	}

	update_post_meta( $event_id, $key, $value );
	return $value;
}

function wp_seed_events_sync_native_event_query_projection( $event_id ) {
	$result = wp_seed_events_sync_native_event_classifications( $event_id );

	return is_wp_error( $result ) ? $result : wp_seed_events_sync_next_occurrence_sort( $event_id );
}

function wp_seed_events_native_event_query_vars( $vars ) {
	$vars[] = 'wp_seed_next_occurrence_missing';
	return array_values( array_unique( $vars ) );
}

function wp_seed_events_query_uses_next_occurrence_order( $query ) {
	if ( ! $query instanceof WP_Query || 'wp_seed_next_occurrence' !== $query->get( 'orderby' ) ) {
		return false;
	}
	$post_type = $query->get( 'post_type' );
	return 'wp_seed_event' === $post_type || ( is_array( $post_type ) && in_array( 'wp_seed_event', $post_type, true ) );
}

function wp_seed_events_prepare_next_occurrence_query( $query ) {
	if ( ! wp_seed_events_query_uses_next_occurrence_order( $query ) || wp_seed_events_is_lifecycle_index_ready() ) {
		return;
	}
	$ids = get_posts(
		array(
			'post_type' => 'wp_seed_event',
			'post_status' => $query->get( 'post_status' ) ?: 'publish',
			'posts_per_page' => -1,
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'suppress_filters' => true,
		)
	);
	$allowed = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) );
	if ( array() !== $allowed ) {
		$ids = array_values( array_intersect( $ids, $allowed ) );
	}
	$excluded = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__not_in' ) ) ) );
	if ( array() !== $excluded ) {
		$ids = array_values( array_diff( $ids, $excluded ) );
	}
	$ordered = array();
	foreach ( $ids as $event_id ) {
		$sort = wp_seed_events_calculate_next_occurrence_sort( $event_id );
		if ( '' === $sort && 'exclude' === sanitize_key( (string) $query->get( 'wp_seed_next_occurrence_missing' ) ) ) {
			continue;
		}
		$ordered[] = array( 'id' => absint( $event_id ), 'sort' => $sort );
	}
	$order = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';
	usort(
		$ordered,
		static function ( $first, $second ) use ( $order ) {
			if ( ( '' === $first['sort'] ) !== ( '' === $second['sort'] ) ) {
				return '' === $first['sort'] ? 1 : -1;
			}
			$comparison = strcmp( $first['sort'], $second['sort'] );
			return 0 !== $comparison && 'DESC' === $order ? -$comparison : ( 0 !== $comparison ? $comparison : $first['id'] <=> $second['id'] );
		}
	);
	$query->set( 'post__in', array() === $ordered ? array( 0 ) : array_column( $ordered, 'id' ) );
	$query->set( 'orderby', 'post__in' );
	$query->set( 'order', 'ASC' );
}
function wp_seed_events_order_query_by_next_occurrence( $clauses, $query ) {
	global $wpdb;

	if ( ! wp_seed_events_query_uses_next_occurrence_order( $query ) ) {
		return $clauses;
	}

	$alias = 'wp_seed_next_occurrence_meta';
	if ( false === strpos( $clauses['join'], $alias ) ) {
		$clauses['join'] .= $wpdb->prepare(
			" LEFT JOIN {$wpdb->postmeta} {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = %s)",
			wp_seed_events_next_occurrence_sort_meta_key()
		);
	}

	if ( 'exclude' === sanitize_key( (string) $query->get( 'wp_seed_next_occurrence_missing' ) ) ) {
		$clauses['where'] .= " AND {$alias}.meta_id IS NOT NULL AND {$alias}.meta_value <> ''";
	}

	$order               = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';
	$clauses['orderby']  = "CASE WHEN {$alias}.meta_value IS NULL OR {$alias}.meta_value = '' THEN 1 ELSE 0 END ASC, {$alias}.meta_value {$order}, {$wpdb->posts}.ID ASC";
	$clauses['distinct'] = 'DISTINCT';

	return $clauses;
}

function wp_seed_events_verify_native_classification_integrity() {
	$event_ids = get_posts(
		array(
			'post_type' => 'wp_seed_event',
			'post_status' => wp_seed_events_lifecycle_index_post_statuses(),
			'posts_per_page' => -1,
			'fields' => 'ids',
			'orderby' => 'ID',
			'order' => 'ASC',
			'suppress_filters' => true,
		)
	);
	foreach ( $event_ids as $event_id ) {
		$expected_types = array_map( 'wp_seed_events_native_event_type_slug', wp_seed_events_event_type_keys_for_event( $event_id ) );
		$actual_types = wp_get_object_terms( $event_id, wp_seed_events_event_type_taxonomy(), array( 'fields' => 'slugs' ) );
		if ( is_wp_error( $actual_types ) ) {
			return $actual_types;
		}
		sort( $expected_types, SORT_STRING );
		sort( $actual_types, SORT_STRING );
		if ( $expected_types !== $actual_types ) {
			return new WP_Error( 'event_type_projection_integrity_failed', 'Event type projection integrity failed.' );
		}
		$expected_featured = '1' === (string) get_post_meta( $event_id, '_wp_seed_event_pinned', true );
		$actual_featured = has_term( wp_seed_events_featured_term_slug(), wp_seed_events_event_flag_taxonomy(), $event_id );
		if ( $expected_featured !== $actual_featured ) {
			return new WP_Error( 'event_flag_projection_integrity_failed', 'Event flag projection integrity failed.' );
		}
		$expected_sort = wp_seed_events_calculate_next_occurrence_sort( $event_id );
		$actual_sort = (string) get_post_meta( $event_id, wp_seed_events_next_occurrence_sort_meta_key(), true );
		if ( $expected_sort !== $actual_sort ) {
			return new WP_Error( 'event_sort_projection_integrity_failed', 'Event next occurrence projection integrity failed.' );
		}
	}
	return true;
}
function wp_seed_events_register_native_classification_rest_fields() {
	register_rest_field(
		'wp_seed_event',
		'wp_seed_event_classifications',
		array(
			'get_callback' => static function ( $object ) {
				$event_id = absint( $object['id'] ?? 0 );
				$event    = wp_seed_events_get_event_data( $event_id );
				if ( array() === $event ) {
					return null;
				}
				return array(
					'primary_type'         => $event['primary_type'] ?? null,
					'secondary_types'      => $event['secondary_types'] ?? array(),
					'all_types'            => $event['all_types'] ?? array(),
					'is_featured'          => ! empty( $event['is_pinned'] ),
					'next_occurrence'      => $event['next_occurrence'] ?? array(),
					'next_occurrence_sort' => wp_seed_events_calculate_next_occurrence_sort( $event_id ),
				);
			},
			'schema'       => array(
				'description' => 'Public native event classifications and next occurrence.',
				'type'        => array( 'object', 'null' ),
				'readonly'    => true,
			),
		)
	);
}

function wp_seed_events_reject_native_classification_rest_writes( $prepared_post, $request ) {
	if ( ! is_object( $request ) || ! is_callable( array( $request, 'get_method' ) ) || 'GET' === $request->get_method() ) {
		return $prepared_post;
	}

	foreach ( array( wp_seed_events_event_type_taxonomy(), wp_seed_events_event_flag_taxonomy() ) as $taxonomy ) {
		if ( null !== $request->get_param( $taxonomy ) ) {
			return new WP_Error(
				'event_classification_projection_read_only',
				__( 'Les classifications natives sont gérées par l’éditeur WP Seed Events.', 'wp-seed-events' ),
				array( 'status' => 400 )
			);
		}
	}
	return $prepared_post;
}
add_action( 'init', 'wp_seed_events_register_native_classifications', 5 );
add_action( 'rest_api_init', 'wp_seed_events_register_native_classification_rest_fields' );
add_filter( 'rest_pre_insert_wp_seed_event', 'wp_seed_events_reject_native_classification_rest_writes', 10, 2 );
add_filter( 'query_vars', 'wp_seed_events_native_event_query_vars' );
add_action( 'pre_get_posts', 'wp_seed_events_prepare_next_occurrence_query', 5 );
add_filter( 'posts_clauses', 'wp_seed_events_order_query_by_next_occurrence', 20, 2 );
