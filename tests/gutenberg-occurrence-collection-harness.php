<?php
/**
 * Server rendering tests for the Gutenberg occurrence collection block.
 */

define( 'ABSPATH', __DIR__ );

class WP_Error {}

class WP_Block {
	public $parsed_block = array();
	public $context = array();

	public function __construct( $parsed = array(), $context = array() ) {
		$this->parsed_block = $parsed;
		$this->context      = $context;
	}

	public function render() {
		$context = $this->context['wpSeedEvents/occurrence'] ?? array();

		return sprintf(
			'<span class="bound">%s|%s|%s</span>',
			htmlspecialchars( wp_seed_events_occurrence_context_value( 'event_title', $context ), ENT_QUOTES, 'UTF-8' ),
			htmlspecialchars( wp_seed_events_occurrence_context_value( 'promotion_name', $context ), ENT_QUOTES, 'UTF-8' ),
			htmlspecialchars( wp_seed_events_occurrence_context_value( 'occurrence_uid', $context ), ENT_QUOTES, 'UTF-8' )
		);
	}
}

function absint( $value ) {
	return abs( (int) $value );
}
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_title( $value ) {
	return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( (string) $value ) ), '-' );
}
function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}
function wp_seed_events_public_format_occurrence_date( $value ) {
	return $value;
}
function wp_seed_events_format_occurrence_time( $value ) {
	return $value;
}
function wp_unslash( $value ) {
	return $value;
}
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}
function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function esc_attr( $value ) {
	return esc_html( $value );
}
function esc_url( $value ) {
	return esc_html( $value );
}
function esc_html__( $value ) {
	return $value;
}
function esc_attr__( $value ) {
	return $value;
}
function __( $value ) {
	return $value;
}
function add_query_arg( $key, $value ) {
	return '/current/?' . rawurlencode( $key ) . '=' . rawurlencode( (string) $value );
}
function get_block_wrapper_attributes( $attributes ) {
	$parts = array();
	foreach ( $attributes as $key => $value ) {
		$parts[] = $key . '="' . esc_attr( $value ) . '"';
	}
	return implode( ' ', $parts );
}
function add_action() {}
function register_block_type_from_metadata() {
	return true;
}

require_once dirname( __DIR__ ) . '/includes/public/occurrence-context.php';
require_once dirname( __DIR__ ) . '/includes/integrations/gutenberg/occurrence-collection-block.php';

$assertions = 0;
$flat_calls = array();
$group_calls = array();
$flat_response = 'items';

function occurrence_block_assert( $condition, $message ) {
	global $assertions;
	++$assertions;

	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function occurrence_block_item( $uid, $promotion_id, $promotion_name, $start, $cancelled = false ) {
	return array(
		'event_id'            => 100,
		'event_title'         => 'Theme commun',
		'event_slug'          => 'theme-commun',
		'event_type'          => 'seminaire',
		'event_status'        => 'publish',
		'is_pinned'           => false,
		'occurrence_uid'      => $uid,
		'start'               => $start,
		'end'                 => $start,
		'is_cancelled'        => $cancelled,
		'promotion_id'        => $promotion_id,
		'promotion'           => array(
			'id'         => $promotion_id,
			'name'       => $promotion_name,
			'slug'       => sanitize_title( $promotion_name ),
			'start_year' => $promotion_id,
			'status'     => 'active',
		),
		'parcours_year'       => 1,
		'parcours_year_label' => 'Année 1',
	);
}

$items = array(
	occurrence_block_item( 'occ-2026-a', 2026, 'Promotion 2026', '2026-02-01 09:00' ),
	occurrence_block_item( 'occ-2026-b', 2026, 'Promotion 2026', '2026-03-01 09:00' ),
	occurrence_block_item( 'occ-2027-a', 2027, 'Promotion 2027', '2027-02-01 09:00' ),
);

function wp_seed_events_query_occurrence_collection( $args ) {
	global $flat_calls, $flat_response, $items;
	$flat_calls[] = $args;

	if ( 'error' === $flat_response ) {
		return new WP_Error();
	}

	$response_items = 'empty' === $flat_response ? array() : $items;

	return array(
		'items'        => $response_items,
		'page'         => $args['page'],
		'per_page'     => $args['per_page'],
		'total_items'  => count( $response_items ),
		'total_pages'  => array() === $response_items ? 0 : 2,
		'has_previous' => false,
		'has_next'     => array() !== $response_items,
	);
}

function wp_seed_events_query_grouped_occurrence_collection( $args ) {
	global $group_calls, $items;
	$group_calls[] = $args;

	return array(
		'promotions' => array(
			array(
				'promotion' => $items[0]['promotion'],
				'years'     => array(
					array(
						'parcours_year_label' => 'Année 1',
						'themes'              => array(
							array(
								'event'       => array( 'title' => 'Theme commun' ),
								'occurrences' => array( $items[0], $items[1] ),
							),
						),
					),
				),
			),
			array(
				'promotion' => $items[2]['promotion'],
				'years'     => array(
					array(
						'parcours_year_label' => 'Année 1',
						'themes'              => array(
							array(
								'event'       => array( 'title' => 'Theme commun' ),
								'occurrences' => array( $items[2] ),
							),
						),
					),
				),
			),
			array(
				'promotion' => array( 'name' => 'Promotion vide' ),
				'years'     => array(),
			),
		),
	);
}

$parsed = array( array( 'blockName' => 'core/paragraph', 'attrs' => array(), 'innerBlocks' => array() ) );
$block  = new WP_Block( array( 'innerBlocks' => $parsed ) );
$default_args = wp_seed_events_occurrence_collection_block_query_args(
	array(
		'parcoursYear' => 0,
		'eventId'      => 0,
	),
	'flat',
	'defaults'
);
occurrence_block_assert( null === $default_args['parcours_year'], 'Empty year does not become an invalid filter.' );
occurrence_block_assert( null === $default_args['event_id'], 'Empty event does not become an invalid filter.' );

$bounded_args = wp_seed_events_occurrence_collection_block_query_args(
	array( 'groupedLimit' => 900 ),
	'grouped',
	'bounded'
);
occurrence_block_assert( 500 === $bounded_args['limit'], 'Grouped limit is capped at 500.' );

$cancelled_args = wp_seed_events_occurrence_collection_block_query_args(
	array( 'includeCancelled' => true ),
	'flat',
	'cancelled'
);
occurrence_block_assert( true === $cancelled_args['include_cancelled'], 'Cancelled option is forwarded explicitly.' );

$without_promotion = occurrence_block_item( 'without-promotion', 0, '', '2026-04-01 09:00' );
$without_promotion['promotion'] = array();
$without_promotion_context = wp_seed_events_occurrence_context_from_item( $without_promotion, 'without-promotion', 0 );
occurrence_block_assert( '' === wp_seed_events_occurrence_context_value( 'promotion_id', $without_promotion_context ), 'Missing Promotion ID stays empty.' );
occurrence_block_assert( '' === wp_seed_events_occurrence_context_value( 'promotion_name', $without_promotion_context ), 'Missing Promotion name stays empty.' );

$html   = wp_seed_events_render_gutenberg_occurrence_collection_block(
	array(
		'mode'                 => 'flat',
		'promotion'            => 'promotion-2026',
		'parcoursYear'         => 1,
		'eventId'              => 100,
		'eventType'            => 'seminaire',
		'status'               => 'all',
		'pinned'               => 'all',
		'includeCancelled'     => false,
		'order'                => 'chronological',
		'page'                 => 1,
		'perPage'              => 2,
		'emptyMessage'         => 'Vide',
		'collectionInstanceId' => 'flat-a',
	),
	'ignored',
	$block
);

occurrence_block_assert( 1 === count( $flat_calls ), 'Flat public API called once.' );
occurrence_block_assert( 'promotion-2026' === $flat_calls[0]['promotion'], 'Promotion forwarded.' );
occurrence_block_assert( 1 === $flat_calls[0]['parcours_year'], 'Year forwarded.' );
occurrence_block_assert( 100 === $flat_calls[0]['event_id'], 'Event ID forwarded.' );
occurrence_block_assert( false === $flat_calls[0]['include_cancelled'], 'Cancelled filter forwarded.' );
occurrence_block_assert( 2 === $flat_calls[0]['per_page'], 'Pagination forwarded.' );
occurrence_block_assert( 3 === substr_count( $html, 'class="bound"' ), 'Three occurrence templates rendered.' );
occurrence_block_assert( false !== strpos( $html, 'occ-2026-a' ), 'First occurrence identity.' );
occurrence_block_assert( false !== strpos( $html, 'occ-2026-b' ), 'Second same-event identity.' );
occurrence_block_assert( false !== strpos( $html, 'occ-2027-a' ), 'Second Promotion identity.' );
occurrence_block_assert( false !== strpos( $html, 'Occurrences suivantes' ), 'Accessible next pagination.' );
occurrence_block_assert( array() === wp_seed_events_occurrence_context_current(), 'Flat render restores context.' );

$grouped = wp_seed_events_render_gutenberg_occurrence_collection_block(
	array(
		'mode'         => 'grouped',
		'status'       => 'all',
		'groupedLimit' => 200,
	),
	'',
	$block
);

occurrence_block_assert( 1 === count( $group_calls ), 'Grouped public API called once.' );
occurrence_block_assert( 'canonical_path' === $group_calls[0]['order'], 'Canonical grouped order.' );
occurrence_block_assert( 200 === $group_calls[0]['limit'], 'Grouped limit forwarded.' );
occurrence_block_assert( 2 === substr_count( $grouped, 'wp-seed-events-occurrence-collection__promotion' ), 'Two Promotion groups.' );
occurrence_block_assert( 2 === substr_count( $grouped, '<h3>Année 1</h3>' ), 'Two year groups.' );
occurrence_block_assert( 3 === substr_count( $grouped, 'class="bound"' ), 'Grouped occurrences rendered once.' );
occurrence_block_assert( array() === wp_seed_events_occurrence_context_current(), 'Grouped render restores context.' );
occurrence_block_assert( 0 === preg_match( '/\sid="/', $html ), 'No automatic duplicate HTML ID.' );
occurrence_block_assert( false === strpos( $grouped, 'get_post_meta' ), 'No storage output.' );

$duplicate = wp_seed_events_render_gutenberg_occurrence_collection_block(
	array(
		'mode'                 => 'flat',
		'collectionInstanceId' => 'flat-a',
	),
	'',
	$block
);
preg_match( '/data-collection-id="([^"]+)"/', $html, $first_instance );
preg_match( '/data-collection-id="([^"]+)"/', $duplicate, $second_instance );
occurrence_block_assert( ! empty( $first_instance[1] ), 'First persisted instance ID is rendered.' );
occurrence_block_assert( ! empty( $second_instance[1] ), 'Duplicated instance ID is rendered.' );
occurrence_block_assert( $first_instance[1] !== $second_instance[1], 'Duplicated blocks cannot collide.' );
occurrence_block_assert( array() === wp_seed_events_occurrence_context_current(), 'Two blocks leave no context behind.' );

$flat_response = 'empty';
$empty = wp_seed_events_render_gutenberg_occurrence_collection_block(
	array(
		'mode'         => 'flat',
		'emptyMessage' => 'Collection vide',
	),
	'',
	$block
);
occurrence_block_assert( false !== strpos( $empty, 'Collection vide' ), 'Empty state is distinct and configurable.' );
occurrence_block_assert( false === strpos( $empty, 'class="bound"' ), 'Empty state renders no occurrence template.' );

$flat_response = 'error';
$error = wp_seed_events_render_gutenberg_occurrence_collection_block( array( 'mode' => 'flat' ), '', $block );
occurrence_block_assert( false !== strpos( $error, 'role="alert"' ), 'Error state is distinct and accessible.' );
occurrence_block_assert( array() === wp_seed_events_occurrence_context_current(), 'Error state leaves no context behind.' );

echo 'Gutenberg occurrence collection renderer: ' . $assertions . '/' . $assertions . " assertions passed.\n";
