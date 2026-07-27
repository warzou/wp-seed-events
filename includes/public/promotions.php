<?php
/**
 * Public Promotion domain and REST API.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize a Promotion business status.
 *
 * @param mixed $status Candidate status.
 * @return string
 */
function wp_seed_events_normalize_promotion_status( $status ) {
	return 'archived' === sanitize_key( (string) $status ) ? 'archived' : 'active';
}

/**
 * Normalize a Promotion start year.
 *
 * @param mixed $year Candidate year.
 * @return int
 */
function wp_seed_events_normalize_promotion_start_year( $year ) {
	$year = absint( $year );

	return 1000 <= $year && 9999 >= $year ? $year : 0;
}

/**
 * Normalize a parcours year.
 *
 * @param mixed $year Candidate parcours year.
 * @return int
 */
function wp_seed_events_normalize_parcours_year( $year ) {
	$year = absint( $year );

	return 1 <= $year && 4 >= $year ? $year : 0;
}

/**
 * Return the public label for a parcours year.
 *
 * @param mixed $year Candidate parcours year.
 * @return string
 */
function wp_seed_events_parcours_year_label( $year ) {
	$year = wp_seed_events_normalize_parcours_year( $year );

	if ( 0 === $year ) {
		return '';
	}

	return 1 === $year ? '1re année' : $year . 'e année';
}

/**
 * Return a normalized public Promotion object by ID or slug.
 *
 * Archived promotions remain readable so historical occurrences keep their
 * public meaning. Listing and assignment policies are handled separately.
 *
 * @param int|string $id_or_slug Promotion post ID or slug.
 * @return array
 */
function wp_seed_events_get_promotion( $id_or_slug ) {
	$cache_key = is_numeric( $id_or_slug )
		? 'id:' . absint( $id_or_slug )
		: 'slug:' . sanitize_title( (string) $id_or_slug );

	if ( isset( $GLOBALS['wp_seed_events_promotion_cache'][ $cache_key ] ) ) {
		return $GLOBALS['wp_seed_events_promotion_cache'][ $cache_key ];
	}

	$post = null;

	if ( is_numeric( $id_or_slug ) ) {
		$post = get_post( absint( $id_or_slug ) );
	} else {
		$slug = sanitize_title( (string) $id_or_slug );
		$post = '' !== $slug ? get_page_by_path( $slug, OBJECT, 'wp_seed_promotion' ) : null;
	}

	if (
		! $post instanceof WP_Post
		|| 'wp_seed_promotion' !== $post->post_type
		|| 'publish' !== $post->post_status
	) {
		$GLOBALS['wp_seed_events_promotion_cache'][ $cache_key ] = array();
		return array();
	}

	$promotion = array(
		'id'          => (int) $post->ID,
		'name'        => sanitize_text_field( (string) $post->post_title ),
		'slug'        => sanitize_title( (string) $post->post_name ),
		'start_year'  => wp_seed_events_normalize_promotion_start_year( get_post_meta( $post->ID, '_wp_seed_promotion_start_year', true ) ),
		'status'      => wp_seed_events_normalize_promotion_status( get_post_meta( $post->ID, '_wp_seed_promotion_status', true ) ),
		'order'       => (int) get_post_meta( $post->ID, '_wp_seed_promotion_order', true ),
		'description' => wp_kses_post( (string) $post->post_content ),
	);

	$GLOBALS['wp_seed_events_promotion_cache'][ 'id:' . $post->ID ]       = $promotion;
	$GLOBALS['wp_seed_events_promotion_cache'][ 'slug:' . $post->post_name ] = $promotion;

	return $promotion;
}

/**
 * Return public Promotion objects.
 *
 * @param array $args List arguments: status, order and orderby.
 * @return array
 */
function wp_seed_events_get_promotions( $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'status'  => 'active',
			'order'   => 'ASC',
			'orderby' => 'order',
		)
	);

	$status  = in_array( $args['status'], array( 'active', 'archived', 'all' ), true ) ? $args['status'] : 'active';
	$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';
	$orderby = in_array( $args['orderby'], array( 'order', 'start_year', 'name' ), true ) ? $args['orderby'] : 'order';
	$posts   = get_posts(
		array(
			'post_type'      => 'wp_seed_promotion',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
	$promotions = array();

	foreach ( is_array( $posts ) ? $posts : array() as $post ) {
		$promotion = wp_seed_events_get_promotion( $post->ID );

		if ( array() === $promotion || ( 'all' !== $status && $status !== $promotion['status'] ) ) {
			continue;
		}

		$promotions[] = $promotion;
	}

	usort(
		$promotions,
		function ( $first, $second ) use ( $orderby, $order ) {
			if ( 'name' === $orderby ) {
				$result = strnatcasecmp( (string) $first['name'], (string) $second['name'] );
			} elseif ( 'start_year' === $orderby ) {
				$result = (int) $first['start_year'] <=> (int) $second['start_year'];
			} else {
				$result = (int) $first['order'] <=> (int) $second['order'];
			}

			if ( 0 === $result && 'order' !== $orderby ) {
				$result = (int) $first['order'] <=> (int) $second['order'];
			}

			if ( 0 === $result ) {
				$result = strnatcasecmp( (string) $first['name'], (string) $second['name'] );
			}

			if ( 0 === $result ) {
				$result = (int) $first['id'] <=> (int) $second['id'];
			}

			return 'DESC' === $order ? -$result : $result;
		}
	);

	return $promotions;
}

/**
 * Register public read-only Promotion and occurrence routes.
 */
function wp_seed_events_register_promotion_rest_routes() {
	register_rest_route(
		'wp-seed-events/v1',
		'/promotions',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wp_seed_events_rest_get_promotions',
			'permission_callback' => '__return_true',
			'args'                => array(
				'status'  => array(
					'default'           => 'active',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => function ( $value ) {
						return in_array( $value, array( 'active', 'archived', 'all' ), true );
					},
				),
				'order'   => array(
					'default'           => 'ASC',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => function ( $value ) {
						return in_array( strtoupper( (string) $value ), array( 'ASC', 'DESC' ), true );
					},
				),
				'orderby' => array(
					'default'           => 'order',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => function ( $value ) {
						return in_array( $value, array( 'order', 'start_year', 'name' ), true );
					},
				),
			),
		)
	);

	register_rest_route(
		'wp-seed-events/v1',
		'/promotions/(?P<identifier>[a-zA-Z0-9_-]+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wp_seed_events_rest_get_promotion',
			'permission_callback' => '__return_true',
		)
	);

	register_rest_route(
		'wp-seed-events/v1',
		'/events/(?P<event_id>\d+)/occurrences',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'wp_seed_events_rest_get_event_occurrences',
			'permission_callback' => 'wp_seed_events_rest_can_read_event_occurrences',
			'args'                => array(
				'event_id'          => array(
					'sanitize_callback' => 'absint',
					'validate_callback' => function ( $value ) {
						return 0 < absint( $value );
					},
				),
				'include_cancelled' => array(
					'default'           => true,
					'sanitize_callback' => 'rest_sanitize_boolean',
				),
				'status'            => array(
					'default'           => 'all',
					'sanitize_callback' => 'sanitize_key',
					'validate_callback' => function ( $value ) {
						return in_array( $value, array( 'all', 'future', 'past' ), true );
					},
				),
			),
		)
	);
}

/**
 * REST callback for the Promotion collection.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function wp_seed_events_rest_get_promotions( $request ) {
	return rest_ensure_response(
		wp_seed_events_get_promotions(
			array(
				'status'  => $request->get_param( 'status' ),
				'order'   => $request->get_param( 'order' ),
				'orderby' => $request->get_param( 'orderby' ),
			)
		)
	);
}

/**
 * REST callback for one Promotion.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function wp_seed_events_rest_get_promotion( $request ) {
	$identifier = (string) $request->get_param( 'identifier' );
	$promotion  = wp_seed_events_get_promotion( ctype_digit( $identifier ) ? absint( $identifier ) : $identifier );

	if ( array() === $promotion ) {
		return new WP_Error( 'wp_seed_events_promotion_not_found', 'Promotion introuvable.', array( 'status' => 404 ) );
	}

	return rest_ensure_response( $promotion );
}

/**
 * Permission callback for public event occurrences.
 *
 * @param WP_REST_Request $request Request.
 * @return bool
 */
function wp_seed_events_rest_can_read_event_occurrences( $request ) {
	$event = get_post( absint( $request->get_param( 'event_id' ) ) );

	if ( ! $event instanceof WP_Post || 'wp_seed_event' !== $event->post_type ) {
		return false;
	}

	return 'publish' === $event->post_status || current_user_can( 'edit_post', $event->ID );
}

/**
 * REST callback for enriched event occurrences.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function wp_seed_events_rest_get_event_occurrences( $request ) {
	return rest_ensure_response(
		wp_seed_events_get_event_occurrences(
			absint( $request->get_param( 'event_id' ) ),
			array(
				'include_cancelled' => rest_sanitize_boolean( $request->get_param( 'include_cancelled' ) ),
				'status'            => $request->get_param( 'status' ),
			)
		)
	);
}
