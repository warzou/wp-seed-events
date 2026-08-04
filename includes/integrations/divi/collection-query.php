<?php
/**
 * Thin Divi 5 Loop Builder adapter for canonical event collections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stable virtual fields accepted by Divi's native Meta Query controls.
 *
 * These names are a public query contract. They are never persisted as post
 * meta and are removed before WordPress executes the query.
 *
 * @return array
 */
function wp_seed_events_divi_collection_virtual_fields() {
	return array(
		'wp_seed_events_type'   => 'type',
		'wp_seed_events_status' => 'status',
		'wp_seed_events_pinned' => 'pinned',
	);
}

function wp_seed_events_divi_collection_orderby() {
	return 'wp_seed_events_business_date';
}

/**
 * Read the dedicated Events controls saved in one Divi loop attribute group.
 *
 * @param array $loop_values Divi loop values.
 * @return array
 */
function wp_seed_events_divi_collection_controls_from_loop_values( $loop_values ) {
	$loop_values = wp_seed_events_divi_normalize_event_loop_values( $loop_values );

	return array(
		'types_present'  => array_key_exists( 'wpSeedEventTypes', $loop_values ),
		'types'          => $loop_values['wpSeedEventTypes'] ?? array(),
		'pinned_present' => array_key_exists( 'wpSeedEventPinned', $loop_values ),
		'pinned'         => (string) ( $loop_values['wpSeedEventPinned'] ?? 'all' ),
	);
}

/**
 * Read dedicated Events controls from the server-rendered Divi block.
 *
 * @param array $block_attrs Divi block attributes.
 * @return array
 */
function wp_seed_events_divi_collection_controls_from_block_attrs( $block_attrs ) {
	$loop_values = $block_attrs['module']['advanced']['loop']['desktop']['value'] ?? array();

	return wp_seed_events_divi_collection_controls_from_loop_values( $loop_values );
}

/**
 * Read dedicated Events controls from the Visual Builder REST request.
 *
 * @param array $params REST parameters.
 * @return array
 */
function wp_seed_events_divi_collection_controls_from_rest_params( $params ) {
	$params = is_array( $params ) ? $params : array();

	return array(
		'types_present'  => array_key_exists( 'wp_seed_event_types', $params ),
		'types'          => $params['wp_seed_event_types'] ?? array(),
		'pinned_present' => array_key_exists( 'wp_seed_event_pinned', $params ),
		'pinned'         => (string) ( $params['wp_seed_event_pinned'] ?? 'all' ),
	);
}

/**
 * Normalize Divi 5.9's plural post type parameter for its order-by endpoint.
 *
 * The Visual Builder sends `post_types`, while the REST controller reads the
 * singular `post_type` parameter. Keep this compatibility bridge restricted
 * to an event-only loop so other Divi queries retain their native behavior.
 *
 * @param mixed           $response Early REST response.
 * @param array           $handler  Matched REST handler.
 * @param WP_REST_Request $request  Current REST request.
 * @return mixed
 */
function wp_seed_events_divi_normalize_collection_orderby_request( $response, $handler, $request ) {
	if (
		! is_object( $request )
		|| ! is_callable( array( $request, 'get_route' ) )
		|| ! is_callable( array( $request, 'get_param' ) )
		|| ! is_callable( array( $request, 'set_param' ) )
		|| '/divi/v1/loop/query-order-by' !== $request->get_route()
		|| 'post_types' !== sanitize_key( (string) $request->get_param( 'query_type' ) )
		|| 'wp_seed_event' !== sanitize_key( (string) $request->get_param( 'post_types' ) )
	) {
		return $response;
	}

	$request->set_param( 'post_type', 'wp_seed_event' );

	return $response;
}
add_filter( 'rest_request_before_callbacks', 'wp_seed_events_divi_normalize_collection_orderby_request', 10, 3 );

/**
 * Add the business date to Divi's event-loop ordering choices.
 *
 * @param array $options Divi order choices.
 * @return array
 */
function wp_seed_events_divi_collection_order_options( $options ) {
	$options = is_array( $options ) ? $options : array();

	foreach ( $options as $option ) {
		if ( is_array( $option ) && wp_seed_events_divi_collection_orderby() === ( $option['value'] ?? '' ) ) {
			return $options;
		}
	}

	$options[] = array(
		'value' => wp_seed_events_divi_collection_orderby(),
		'label' => '1re date de l’événement',
	);

	return $options;
}
add_filter( 'et_builder_loop_order_by_options_wp_seed_event', 'wp_seed_events_divi_collection_order_options' );

/**
 * Remove virtual clauses while extracting their canonical values.
 *
 * @param array $meta_query Divi/WordPress meta query.
 * @param array $options    Extracted collection options.
 * @param bool  $found      Whether a virtual clause was found.
 * @return array
 */
function wp_seed_events_divi_extract_collection_meta_query( $meta_query, &$options, &$found ) {
	if ( ! is_array( $meta_query ) ) {
		return array();
	}

	$virtual_fields = wp_seed_events_divi_collection_virtual_fields();
	$clean          = array();
	$relation       = isset( $meta_query['relation'] ) && 'OR' === strtoupper( (string) $meta_query['relation'] ) ? 'OR' : 'AND';

	foreach ( $meta_query as $key => $clause ) {
		if ( 'relation' === $key || ! is_array( $clause ) ) {
			continue;
		}

		$meta_key = sanitize_key( (string) ( $clause['key'] ?? $clause['metaKey'] ?? '' ) );

		if ( isset( $virtual_fields[ $meta_key ] ) ) {
			$option              = $virtual_fields[ $meta_key ];
			$options[ $option ]  = (string) ( $clause['value'] ?? $clause['metaValue'] ?? '' );
			$found               = true;
			continue;
		}

		$clean[] = $clause;
	}

	if ( count( $clean ) > 1 ) {
		$clean['relation'] = $relation;
	}

	return $clean;
}

function wp_seed_events_divi_is_event_collection_query( $query_args ) {
	$post_types = isset( $query_args['post_type'] ) ? (array) $query_args['post_type'] : array();
	$post_types = array_values( array_unique( array_map( 'sanitize_key', $post_types ) ) );

	return array( 'wp_seed_event' ) === $post_types;
}

/**
 * Flatten the categorized or legacy values saved by Divi's tag input.
 *
 * @param mixed $raw_values Raw field value.
 * @return array
 */
function wp_seed_events_divi_flatten_term_values( $raw_values ) {
	if ( is_string( $raw_values ) ) {
		$decoded    = json_decode( $raw_values, true );
		$raw_values = is_array( $decoded ) ? $decoded : explode( ',', $raw_values );
	}

	$queue  = is_array( $raw_values ) ? array_values( $raw_values ) : array( $raw_values );
	$values = array();

	while ( array() !== $queue ) {
		$raw_value = array_shift( $queue );

		if ( is_array( $raw_value ) && isset( $raw_value['selectedOptions'] ) && is_array( $raw_value['selectedOptions'] ) ) {
			array_push( $queue, ...array_values( $raw_value['selectedOptions'] ) );
			continue;
		}

		if ( is_array( $raw_value ) ) {
			$raw_value = $raw_value['value'] ?? '';
		}

		$value = trim( (string) $raw_value );

		if ( '' !== $value ) {
			$values[] = $value;
		}
	}

	return array_values( array_unique( $values ) );
}

/**
 * Resolve the taxonomy belonging to one saved Divi term value.
 *
 * @param mixed  $value             Saved term ID or slug.
 * @param string $category_taxonomy Taxonomy supplied by Divi's categorized value.
 * @return string
 */
function wp_seed_events_divi_term_value_taxonomy( $value, $category_taxonomy = '' ) {
	$category_taxonomy = sanitize_key( (string) $category_taxonomy );

	if ( '' !== $category_taxonomy ) {
		return $category_taxonomy;
	}

	$value = trim( (string) $value );

	if ( '' === $value ) {
		return '';
	}

	if ( ctype_digit( $value ) ) {
		$term = get_term( absint( $value ) );

		return $term && ! is_wp_error( $term ) ? sanitize_key( (string) $term->taxonomy ) : '';
	}

	foreach ( array( 'wp_seed_event_type', 'wp_seed_event_flag' ) as $taxonomy ) {
		$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );

		if ( $term && ! is_wp_error( $term ) ) {
			return $taxonomy;
		}
	}

	return '';
}

/**
 * Split one native Divi term selection into Events controls and unrelated terms.
 *
 * @param mixed $raw_values Native Divi tag-input value.
 * @return array
 */
function wp_seed_events_divi_split_native_term_selection( $raw_values ) {
	if ( is_string( $raw_values ) ) {
		$decoded    = json_decode( $raw_values, true );
		$raw_values = is_array( $decoded ) ? $decoded : explode( ',', $raw_values );
	}

	$items     = is_array( $raw_values ) ? array_values( $raw_values ) : array( $raw_values );
	$types     = array();
	$featured  = false;
	$unrelated = array();

	foreach ( $items as $item ) {
		if ( is_array( $item ) && isset( $item['selectedOptions'] ) && is_array( $item['selectedOptions'] ) ) {
			$taxonomy = sanitize_key( (string) ( $item['categoryId'] ?? '' ) );
			$kept     = array();

			foreach ( $item['selectedOptions'] as $option ) {
				$value           = is_array( $option ) ? ( $option['value'] ?? '' ) : $option;
				$option_taxonomy = wp_seed_events_divi_term_value_taxonomy( $value, $taxonomy );

				if ( 'wp_seed_event_type' === $option_taxonomy ) {
					$types[] = $option;
				} elseif ( 'wp_seed_event_flag' === $option_taxonomy ) {
					$term = ctype_digit( (string) $value )
						? get_term( absint( $value ), 'wp_seed_event_flag' )
						: get_term_by( 'slug', sanitize_title( (string) $value ), 'wp_seed_event_flag' );

					if ( $term && ! is_wp_error( $term ) && 'featured' === (string) $term->slug ) {
						$featured = true;
					} else {
						$kept[] = $option;
					}
				} else {
					$kept[] = $option;
				}
			}

			if ( array() !== $kept ) {
				$item['selectedOptions'] = $kept;
				$unrelated[]             = $item;
			}
			continue;
		}

		$value    = is_array( $item ) ? ( $item['value'] ?? '' ) : $item;
		$taxonomy = wp_seed_events_divi_term_value_taxonomy( $value );

		if ( 'wp_seed_event_type' === $taxonomy ) {
			$types[] = $item;
		} elseif ( 'wp_seed_event_flag' === $taxonomy ) {
			$term = ctype_digit( (string) $value )
				? get_term( absint( $value ), $taxonomy )
				: get_term_by( 'slug', sanitize_title( (string) $value ), $taxonomy );

			if ( $term && ! is_wp_error( $term ) && 'featured' === (string) $term->slug ) {
				$featured = true;
			} else {
				$unrelated[] = $item;
			}
		} elseif ( '' !== trim( (string) $value ) ) {
			$unrelated[] = $item;
		}
	}

	return array(
		'types'     => $types,
		'featured'  => $featured,
		'unrelated' => $unrelated,
	);
}

/**
 * Make dedicated Events controls authoritative for one Divi loop value.
 *
 * @param array $loop_values Saved Divi loop values.
 * @return array
 */
function wp_seed_events_divi_normalize_event_loop_values( $loop_values ) {
	$loop_values = is_array( $loop_values ) ? $loop_values : array();
	$include     = wp_seed_events_divi_split_native_term_selection( $loop_values['includePostWithSpecificTerms'] ?? array() );
	$exclude     = wp_seed_events_divi_split_native_term_selection( $loop_values['excludePostWithSpecificTerms'] ?? array() );

	if ( ! array_key_exists( 'wpSeedEventTypes', $loop_values ) && array() !== $include['types'] ) {
		$loop_values['wpSeedEventTypes'] = array(
			array(
				'categoryId'      => 'wp_seed_event_type',
				'categoryName'    => 'Types d’événement',
				'selectedOptions' => $include['types'],
			),
	);
	}

	if ( ! array_key_exists( 'wpSeedEventPinned', $loop_values ) && ( array() !== $include['types'] || $include['featured'] || $exclude['featured'] ) ) {
		$loop_values['wpSeedEventPinned'] = $exclude['featured']
			? 'exclude_featured'
			: ( $include['featured'] ? 'featured_only' : 'all' );
	}

	if ( array_key_exists( 'includePostWithSpecificTerms', $loop_values ) ) {
		$loop_values['includePostWithSpecificTerms'] = $include['unrelated'];
	}

	if ( array_key_exists( 'excludePostWithSpecificTerms', $loop_values ) ) {
		$loop_values['excludePostWithSpecificTerms'] = $exclude['unrelated'];
	}

	return $loop_values;
}

/**
 * Detect an event-only Divi post loop from its saved values.
 *
 * @param array $loop_values Saved Divi loop values.
 * @return bool
 */
function wp_seed_events_divi_is_event_loop_values( $loop_values ) {
	$loop_values = is_array( $loop_values ) ? $loop_values : array();

	if ( 'post_types' !== sanitize_key( (string) ( $loop_values['queryType'] ?? '' ) ) ) {
		return false;
	}

	$post_types = wp_seed_events_divi_flatten_term_values( $loop_values['subTypes'] ?? array() );
	$post_types = array_values( array_unique( array_map( 'sanitize_key', $post_types ) ) );

	return array( 'wp_seed_event' ) === $post_types;
}

/**
 * Normalize Events loop attributes recursively before WordPress stores content.
 *
 * @param array $block   Parsed block.
 * @param bool  $changed Whether an Events loop was migrated.
 * @return array
 */
function wp_seed_events_divi_migrate_event_loop_block( $block, &$changed ) {
	if ( ! is_array( $block ) ) {
		return $block;
	}

	$loop_values = $block['attrs']['module']['advanced']['loop']['desktop']['value'] ?? null;

	if ( is_array( $loop_values ) && wp_seed_events_divi_is_event_loop_values( $loop_values ) ) {
		$normalized = wp_seed_events_divi_normalize_event_loop_values( $loop_values );

		if ( $normalized !== $loop_values ) {
			$block['attrs']['module']['advanced']['loop']['desktop']['value'] = $normalized;
			$changed = true;
		}
	}

	if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
		foreach ( $block['innerBlocks'] as $index => $inner_block ) {
			$block['innerBlocks'][ $index ] = wp_seed_events_divi_migrate_event_loop_block( $inner_block, $changed );
		}
	}

	return $block;
}

/**
 * Persist the dedicated Events controls when an existing Divi page is saved.
 *
 * @param string $content Post content before storage.
 * @return string
 */
function wp_seed_events_divi_migrate_event_loop_content( $content ) {
	if ( ! is_string( $content ) || false === strpos( $content, 'wp_seed_event' ) || ! function_exists( 'parse_blocks' ) || ! function_exists( 'serialize_blocks' ) ) {
		return $content;
	}

	$blocks  = parse_blocks( $content );
	$changed = false;

	foreach ( $blocks as $index => $block ) {
		$blocks[ $index ] = wp_seed_events_divi_migrate_event_loop_block( $block, $changed );
	}

	return $changed ? serialize_blocks( $blocks ) : $content;
}
/**
 * Normalize Divi Events loop attributes in the final post payload.
 *
 * The generic content_save_pre filter can run before a builder has assembled its
 * final block attributes. wp_insert_post_data is the stable last write boundary.
 *
 * @param array $data Final sanitized post data.
 * @return array
 */
function wp_seed_events_divi_migrate_event_loop_post_data( $data ) {
	if ( ! is_array( $data ) || 'revision' === ( $data['post_type'] ?? '' ) || ! isset( $data['post_content'] ) ) {
		return $data;
	}

	$content              = wp_unslash( $data['post_content'] );
	$data['post_content'] = wp_slash( wp_seed_events_divi_migrate_event_loop_content( $content ) );

	return $data;
}
add_filter( 'wp_insert_post_data', 'wp_seed_events_divi_migrate_event_loop_post_data', 20 );

/**
 * Resolve Divi term IDs or slugs to native term IDs.
 *
 * @param mixed  $raw_values Raw field value.
 * @param string $taxonomy   Native taxonomy.
 * @return array
 */
function wp_seed_events_divi_normalize_term_ids( $raw_values, $taxonomy ) {
	$term_ids = array();

	foreach ( wp_seed_events_divi_flatten_term_values( $raw_values ) as $value ) {
		$term = ctype_digit( $value )
			? get_term( absint( $value ), $taxonomy )
			: get_term_by( 'slug', sanitize_title( $value ), $taxonomy );

		if ( $term && ! is_wp_error( $term ) && $taxonomy === (string) $term->taxonomy ) {
			$term_ids[] = absint( $term->term_id );
		}
	}

	return array_values( array_unique( array_filter( $term_ids ) ) );
}

/**
 * Remove clauses for taxonomies controlled by the dedicated Events fields.
 *
 * @param array $tax_query  Existing tax query.
 * @param array $taxonomies Taxonomies to remove.
 * @return array
 */
function wp_seed_events_divi_remove_controlled_taxonomies( $tax_query, $taxonomies ) {
	if ( ! is_array( $tax_query ) ) {
		return array();
	}

	$relation = isset( $tax_query['relation'] ) && 'OR' === strtoupper( (string) $tax_query['relation'] ) ? 'OR' : 'AND';
	$clean    = array();

	foreach ( $tax_query as $key => $clause ) {
		if ( 'relation' === $key || ! is_array( $clause ) ) {
			continue;
		}

		if ( isset( $clause['taxonomy'] ) ) {
			if ( ! in_array( sanitize_key( (string) $clause['taxonomy'] ), $taxonomies, true ) ) {
				$clean[] = $clause;
			}
			continue;
		}

		$nested = wp_seed_events_divi_remove_controlled_taxonomies( $clause, $taxonomies );

		if ( array() !== $nested ) {
			$clean[] = $nested;
		}
	}

	if ( count( $clean ) > 1 ) {
		$clean['relation'] = $relation;
	}

	return $clean;
}

/**
 * Combine existing native clauses with dedicated Events clauses using AND.
 *
 * @param array $existing Existing tax query.
 * @param array $clauses  Dedicated clauses.
 * @return array
 */
function wp_seed_events_divi_combine_tax_queries( $existing, $clauses ) {
	$existing = is_array( $existing ) ? $existing : array();
	$clauses  = is_array( $clauses ) ? $clauses : array();

	if ( array() === $existing ) {
		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'AND';
		}
		return $clauses;
	}

	if ( array() === $clauses ) {
		return $existing;
	}

	$combined = array( 'relation' => 'AND' );
	$combined[] = $existing;

	foreach ( $clauses as $clause ) {
		$combined[] = $clause;
	}

	return $combined;
}

/**
 * Apply dedicated Divi controls as standard WordPress taxonomy clauses.
 *
 * @param array $query_args Query arguments.
 * @param array $controls   Dedicated control values and presence flags.
 * @return array
 */
function wp_seed_events_divi_apply_taxonomy_controls( $query_args, $controls ) {
	$controls    = is_array( $controls ) ? $controls : array();
	$taxonomies  = array();
	$new_clauses = array();

	if ( ! empty( $controls['types_present'] ) ) {
		$taxonomies[] = 'wp_seed_event_type';
		$raw_types    = $controls['types'] ?? array();
		$type_ids     = wp_seed_events_divi_normalize_term_ids( $raw_types, 'wp_seed_event_type' );
		$has_values   = array() !== wp_seed_events_divi_flatten_term_values( $raw_types );

		if ( $has_values && array() === $type_ids ) {
			$query_args['post__in'] = array( 0 );
		} elseif ( array() !== $type_ids ) {
			$new_clauses[] = array(
				'taxonomy' => 'wp_seed_event_type',
				'field'    => 'term_id',
				'terms'    => $type_ids,
				'operator' => 'IN',
			);
		}
	}

	if ( ! empty( $controls['pinned_present'] ) ) {
		$taxonomies[] = 'wp_seed_event_flag';
		$pinned       = sanitize_key( (string) ( $controls['pinned'] ?? 'all' ) );

		if ( ! in_array( $pinned, array( 'all', 'featured_only', 'exclude_featured' ), true ) ) {
			$query_args['post__in'] = array( 0 );
		} elseif ( 'all' !== $pinned ) {
			$featured = get_term_by( 'slug', 'featured', 'wp_seed_event_flag' );

			if ( ! $featured || is_wp_error( $featured ) ) {
				if ( 'featured_only' === $pinned ) {
					$query_args['post__in'] = array( 0 );
				}
			} else {
				$new_clauses[] = array(
					'taxonomy' => 'wp_seed_event_flag',
					'field'    => 'term_id',
					'terms'    => array( absint( $featured->term_id ) ),
					'operator' => 'featured_only' === $pinned ? 'IN' : 'NOT IN',
				);
			}
		}
	}

	if ( array() === $taxonomies ) {
		return $query_args;
	}

	$existing = wp_seed_events_divi_remove_controlled_taxonomies(
		$query_args['tax_query'] ?? array(),
		$taxonomies
	);
	$tax_query = wp_seed_events_divi_combine_tax_queries( $existing, $new_clauses );

	if ( array() === $tax_query ) {
		unset( $query_args['tax_query'] );
	} else {
		$query_args['tax_query'] = $tax_query;
	}

	return $query_args;
}

/**
 * Apply the canonical collection selection to one bounded Divi query.
 *
 * @param array  $query_args WordPress query arguments generated by Divi.
 * @param string $requested_orderby Raw order choice when REST sanitization removed it.
 * @return array
 */
function wp_seed_events_divi_apply_collection_query( $query_args, $requested_orderby = '', $controls = array() ) {
	if ( ! is_array( $query_args ) || ! wp_seed_events_divi_is_event_collection_query( $query_args ) ) {
		return $query_args;
	}

	$options = array(
		'type'   => '',
		'status' => 'all',
		'pinned' => 'all',
	);
	$found   = false;

	$query_args['meta_query'] = wp_seed_events_divi_extract_collection_meta_query(
		$query_args['meta_query'] ?? array(),
		$options,
		$found
	);

	if ( array() === $query_args['meta_query'] ) {
		unset( $query_args['meta_query'] );
	}

	$controls = is_array( $controls ) ? $controls : array();

	if ( ! empty( $controls['types_present'] ) ) {
		$options['type'] = '';
	}

	if ( ! empty( $controls['pinned_present'] ) ) {
		$options['pinned'] = 'all';
	}

	$query_args = wp_seed_events_divi_apply_taxonomy_controls( $query_args, $controls );

	$orderby          = sanitize_key( '' !== $requested_orderby ? $requested_orderby : ( $query_args['orderby'] ?? '' ) );
	$business_orderby = wp_seed_events_divi_collection_orderby();

	if ( $business_orderby === $orderby ) {
		$found = true;
	}

	if ( ! $found ) {
		return $query_args;
	}

	$options['status'] = strtolower( trim( (string) $options['status'] ) );

	if ( ! in_array( $options['status'], array( 'upcoming', 'past', 'all' ), true ) ) {
		$query_args['post__in'] = array( 0 );
		$query_args['orderby']  = 'post__in';
		$query_args['order']    = 'ASC';
		return $query_args;
	}

	$result = wp_seed_events_query_event_collection(
		array(
			'type'     => $options['type'],
			'status'   => $options['status'],
			'pinned'   => $options['pinned'],
			'order'    => $query_args['order'] ?? 'ASC',
			'per_page' => -1,
		)
	);

	$event_ids = $result['ids'];

	if ( ! empty( $query_args['post__in'] ) ) {
		$allowed   = array_map( 'absint', (array) $query_args['post__in'] );
		$event_ids = array_values( array_filter( $event_ids, static function ( $event_id ) use ( $allowed ) {
			return in_array( $event_id, $allowed, true );
		} ) );
	}

	if ( ! empty( $query_args['post__not_in'] ) ) {
		$excluded  = array_map( 'absint', (array) $query_args['post__not_in'] );
		$event_ids = array_values( array_diff( $event_ids, $excluded ) );
	}

	$query_args['post__in'] = array() === $event_ids ? array( 0 ) : $event_ids;
	$query_args['orderby']  = 'post__in';
	$query_args['order']    = 'ASC';

	return $query_args;
}

/**
 * Adapt one frontend Loop Builder query before Divi caches or executes it.
 *
 * @param array $loop_data Divi loop data.
 * @return array
 */
function wp_seed_events_divi_filter_collection_loop_data( $loop_data, $block_attrs = array() ) {
	if ( ! is_array( $loop_data ) || empty( $loop_data['query_args'] ) || ! is_array( $loop_data['query_args'] ) ) {
		return $loop_data;
	}

	$controls = wp_seed_events_divi_collection_controls_from_block_attrs( $block_attrs );
	$loop_data['query_args'] = wp_seed_events_divi_apply_collection_query( $loop_data['query_args'], '', $controls );

	return $loop_data;
}
add_filter( 'divi_loop_data_before_execution', 'wp_seed_events_divi_filter_collection_loop_data', 20, 3 );

/**
 * Keep the Visual Builder REST query aligned with the frontend loop query.
 *
 * @param array $query_args Divi REST query arguments.
 * @param array $params     Sanitized REST parameters.
 * @return array
 */
function wp_seed_events_divi_filter_collection_rest_query_args( $query_args, $params ) {
	$requested_orderby = is_array( $params ) ? (string) ( $params['order_by'] ?? '' ) : '';
	$controls          = wp_seed_events_divi_collection_controls_from_rest_params( $params );

	return wp_seed_events_divi_apply_collection_query( $query_args, $requested_orderby, $controls );
}
add_filter( 'divi_module_options_loop_post_type_results_query_args', 'wp_seed_events_divi_filter_collection_rest_query_args', 20, 2 );
