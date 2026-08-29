<?php
/**
 * Dynamic Gutenberg collection block for public event occurrences.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return one request-stable collection instance ID.
 *
 * @param array $attributes Block attributes.
 * @return string
 */
function wp_seed_events_occurrence_collection_block_instance_id( $attributes ) {
	static $instances = array();

	$requested = sanitize_key( (string) ( $attributes['collectionInstanceId'] ?? '' ) );
	$base      = '' !== $requested ? $requested : 'occurrence-collection';

	if ( ! isset( $instances[ $base ] ) ) {
		$instances[ $base ] = 1;
		return $base;
	}

	++$instances[ $base ];

	return $base . '-' . $instances[ $base ];
}

/**
 * Convert block attributes to the canonical public collection arguments.
 *
 * @param array  $attributes Block attributes.
 * @param string $instance   Collection instance ID.
 * @return array
 */
function wp_seed_events_occurrence_collection_block_query_args( $attributes, $instance ) {
	$args = array(
		'event_id'          => 0 < absint( $attributes['eventId'] ?? 0 ) ? absint( $attributes['eventId'] ) : null,
		'type'              => $attributes['eventType'] ?? '',
		'status'            => $attributes['status'] ?? 'upcoming',
		'pinned'            => $attributes['pinned'] ?? 'all',
		'include_cancelled' => ! empty( $attributes['includeCancelled'] ),
		'from'              => $attributes['from'] ?? '',
		'to'                => $attributes['to'] ?? '',
	);

	$query_key = 'wpseed_occurrence_page_' . sanitize_key( str_replace( '-', '_', $instance ) );
	$page      = max( 1, absint( $attributes['page'] ?? 1 ) );

	if ( isset( $_GET[ $query_key ] ) && is_scalar( $_GET[ $query_key ] ) ) {
		$page = max( 1, absint( wp_unslash( $_GET[ $query_key ] ) ) );
	}

	$args['order']    = $attributes['order'] ?? 'chronological';
	$args['page']     = $page;
	$args['per_page'] = min( 100, max( 1, absint( $attributes['perPage'] ?? 20 ) ) );

	return $args;
}

/**
 * Render the saved child template under one explicit occurrence context.
 *
 * @param array $parsed_inner_blocks Saved block template.
 * @param array $context             Canonical occurrence context.
 * @return string
 */
function wp_seed_events_render_occurrence_inner_blocks( $parsed_inner_blocks, $context ) {
	if ( ! is_array( $parsed_inner_blocks ) || array() === $parsed_inner_blocks || ! class_exists( 'WP_Block' ) ) {
		return '';
	}

	return (string) wp_seed_events_with_occurrence_context(
		$context,
		static function () use ( $parsed_inner_blocks, $context ) {
			$html              = '';
			$available_context = array(
				'postId'                   => absint( $context['event_id'] ?? 0 ),
				'postType'                 => 'wp_seed_event',
				'wpSeedEvents/occurrence' => $context,
			);

			foreach ( $parsed_inner_blocks as $parsed_block ) {
				if ( ! is_array( $parsed_block ) ) {
					continue;
				}

				$block = new WP_Block( $parsed_block, $available_context );
				$html .= $block->render();
			}

			return $html;
		}
	);
}

/**
 * Minimal neutral fallback when a block has no saved child template.
 *
 * @param array $context Canonical occurrence context.
 * @return string
 */
function wp_seed_events_render_occurrence_collection_fallback_item( $context ) {
	$title = wp_seed_events_occurrence_context_value( 'event_title', $context );
	$date  = wp_seed_events_occurrence_context_value( 'occurrence_start_date', $context );

	if ( '' === $title && '' === $date ) {
		return '';
	}

	return sprintf(
		'<p><strong>%1$s</strong>%2$s</p>',
		esc_html( $title ),
		'' !== $date ? '<br>' . esc_html( $date ) : ''
	);
}

/**
 * Render one occurrence item without imposing a visual card.
 *
 * @param array  $item                Public collection item.
 * @param array  $parsed_inner_blocks Saved block template.
 * @param string $instance            Collection instance ID.
 * @param int    $index               Zero-based item index.
 * @return string
 */
function wp_seed_events_render_occurrence_collection_item( $item, $parsed_inner_blocks, $instance, $index ) {
	$context = wp_seed_events_occurrence_context_from_item( $item, $instance, $index );

	if ( array() === $context ) {
		return '';
	}

	$content = wp_seed_events_render_occurrence_inner_blocks( $parsed_inner_blocks, $context );

	if ( '' === trim( $content ) ) {
		$content = wp_seed_events_render_occurrence_collection_fallback_item( $context );
	}

	if ( '' === trim( $content ) ) {
		return '';
	}

	return sprintf(
		'<div class="wp-seed-events-occurrence-collection__item" data-occurrence-key="%1$s">%2$s</div>',
		esc_attr( $context['item_key'] ),
		$content
	);
}

/**
 * Render accessible flat collection pagination isolated by block instance.
 *
 * @param array  $result   Public flat collection result.
 * @param string $instance Collection instance ID.
 * @return string
 */
function wp_seed_events_render_occurrence_collection_pagination( $result, $instance ) {
	if ( empty( $result['has_previous'] ) && empty( $result['has_next'] ) ) {
		return '';
	}

	$page      = max( 1, absint( $result['page'] ?? 1 ) );
	$query_key = 'wpseed_occurrence_page_' . sanitize_key( str_replace( '-', '_', $instance ) );
	$links     = array();

	if ( ! empty( $result['has_previous'] ) ) {
		$links[] = sprintf(
			'<a class="wp-seed-events-occurrence-collection__previous" href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $query_key, $page - 1 ) ),
			esc_html__( 'Occurrences précédentes', 'wp-seed-events' )
		);
	}

	if ( ! empty( $result['has_next'] ) ) {
		$links[] = sprintf(
			'<a class="wp-seed-events-occurrence-collection__next" href="%1$s">%2$s</a>',
			esc_url( add_query_arg( $query_key, $page + 1 ) ),
			esc_html__( 'Occurrences suivantes', 'wp-seed-events' )
		);
	}

	return sprintf(
		'<nav class="wp-seed-events-occurrence-collection__pagination" aria-label="%1$s">%2$s</nav>',
		esc_attr__( 'Pagination des occurrences', 'wp-seed-events' ),
		implode( '', $links )
	);
}

/**
 * Render a flat occurrence collection.
 *
 * @param array  $result              Public collection result.
 * @param array  $parsed_inner_blocks Saved block template.
 * @param string $instance            Collection instance ID.
 * @return string
 */
function wp_seed_events_render_flat_occurrence_collection( $result, $parsed_inner_blocks, $instance ) {
	$html = '';

	foreach ( $result['items'] ?? array() as $index => $item ) {
		$html .= wp_seed_events_render_occurrence_collection_item( $item, $parsed_inner_blocks, $instance, $index );
	}

	return $html . wp_seed_events_render_occurrence_collection_pagination( $result, $instance );
}

/**
 * Render the occurrence collection block.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Pre-rendered content, intentionally unused.
 * @param WP_Block $block      Block instance.
 * @return string
 */
function wp_seed_events_render_gutenberg_occurrence_collection_block( $attributes, $content, $block ) {
	unset( $content );

	$attributes = is_array( $attributes ) ? $attributes : array();
	$instance   = wp_seed_events_occurrence_collection_block_instance_id( $attributes );
	$args       = wp_seed_events_occurrence_collection_block_query_args( $attributes, $instance );
	$result     = wp_seed_events_query_occurrence_collection( $args );

	if ( is_wp_error( $result ) ) {
		return sprintf(
			'<div class="wp-seed-events-occurrence-collection__error" role="alert">%s</div>',
			esc_html__( 'La collection d’occurrences ne peut pas être affichée.', 'wp-seed-events' )
		);
	}

	$parsed_inner_blocks = class_exists( 'WP_Block' ) && $block instanceof WP_Block && isset( $block->parsed_block['innerBlocks'] )
		? $block->parsed_block['innerBlocks']
		: array();
	$html                = wp_seed_events_render_flat_occurrence_collection( $result, $parsed_inner_blocks, $instance );

	if ( '' === trim( $html ) ) {
		$empty_message = trim( wp_strip_all_tags( (string) ( $attributes['emptyMessage'] ?? '' ) ) );
		$empty_message = '' !== $empty_message ? $empty_message : __( 'Aucune occurrence à afficher.', 'wp-seed-events' );
		$html          = sprintf(
			'<p class="wp-seed-events-occurrence-collection__empty" role="status">%s</p>',
			esc_html( $empty_message )
		);
	}

	if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
		return $html;
	}

	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class'                => 'wp-seed-events-occurrence-collection wp-seed-events-occurrence-collection--flat',
			'data-collection-id'   => $instance,
			'data-collection-mode' => 'flat',
		)
	);

	return sprintf( '<div %1$s>%2$s</div>', $wrapper_attributes, $html );
}

/**
 * Register the dynamic Gutenberg occurrence collection block.
 */
function wp_seed_events_register_occurrence_collection_block() {
	static $registered = false;

	if ( $registered || ! function_exists( 'register_block_type_from_metadata' ) ) {
		return;
	}

	$build_path = __DIR__ . '/occurrence-collection-block/build';

	if ( ! is_readable( $build_path . '/block.json' ) ) {
		return;
	}

	$registered = (bool) register_block_type_from_metadata(
		$build_path,
		array(
			'render_callback' => 'wp_seed_events_render_gutenberg_occurrence_collection_block',
		)
	);
}
add_action( 'init', 'wp_seed_events_register_occurrence_collection_block', 20 );
