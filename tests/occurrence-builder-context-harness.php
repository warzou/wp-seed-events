<?php
/**
 * Contract tests for request-local occurrence builder context.
 */

define( 'ABSPATH', __DIR__ );

class WP_Block {
	public $context = array();

	public function __construct( $parsed = array(), $context = array() ) {
		unset( $parsed );
		$this->context = $context;
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
	return 'DATE:' . $value;
}
function wp_seed_events_format_occurrence_time( $value ) {
	return 'TIME:' . $value;
}
function add_action() {}
function register_block_bindings_source() {}

require_once dirname( __DIR__ ) . '/includes/public/occurrence-context.php';
require_once dirname( __DIR__ ) . '/includes/integrations/gutenberg/block-bindings.php';

$assertions = 0;

function occurrence_context_assert( $condition, $message ) {
	global $assertions;
	++$assertions;

	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$item_a = array(
	'event_id'            => 100,
	'event_title'         => '<b>Theme A</b>',
	'event_slug'          => 'theme-a',
	'event_type'          => 'seminaire',
	'event_status'        => 'publish',
	'is_pinned'           => true,
	'occurrence_uid'      => 'occ-a',
	'start'               => '2026-02-03 09:30',
	'end'                 => '2026-02-03 12:00',
	'is_cancelled'        => false,
	'promotion_id'        => 201,
	'promotion'           => array(
		'name'       => '<i>Promotion 2026</i>',
		'slug'       => 'promotion-2026',
		'start_year' => 2026,
		'status'     => 'active',
	),
	'parcours_year'       => 1,
	'parcours_year_label' => 'Année 1',
);
$item_b = array_merge(
	$item_a,
	array(
		'occurrence_uid' => 'occ-b',
		'start'          => '2026-03-04 14:00',
		'promotion_id'   => 202,
		'promotion'      => array(
			'name'       => 'Promotion 2027',
			'slug'       => 'promotion-2027',
			'start_year' => 2027,
			'status'     => 'active',
		),
	)
);

$context_a = wp_seed_events_occurrence_context_from_item( $item_a, 'collection-a', 0 );
$context_b = wp_seed_events_occurrence_context_from_item( $item_b, 'collection-b', 1 );

occurrence_context_assert( 'collection-a:100:occ-a:0' === $context_a['item_key'], 'Composite key A.' );
occurrence_context_assert( 'collection-b:100:occ-b:1' === $context_b['item_key'], 'Composite key B.' );
occurrence_context_assert( $context_a['item_key'] !== $context_b['item_key'], 'Repeated event keeps distinct identity.' );
occurrence_context_assert( array() === wp_seed_events_occurrence_context_current(), 'Context starts empty.' );

$outer = wp_seed_events_with_occurrence_context(
	$context_a,
	static function () use ( $context_b ) {
		occurrence_context_assert( 'occ-a' === wp_seed_events_occurrence_context_value( 'occurrence_uid' ), 'Outer context is visible.' );

		return wp_seed_events_with_occurrence_context(
			$context_b,
			static function () {
				occurrence_context_assert( 'occ-b' === wp_seed_events_occurrence_context_value( 'occurrence_uid' ), 'Nested context is visible.' );
				return wp_seed_events_occurrence_context_value( 'promotion_name' );
			}
		);
	}
);
occurrence_context_assert( 'Promotion 2027' === $outer, 'Nested callback result.' );
occurrence_context_assert( array() === wp_seed_events_occurrence_context_current(), 'Nested context is restored.' );

try {
	wp_seed_events_with_occurrence_context(
		$context_a,
		static function () {
			throw new RuntimeException( 'expected' );
		}
	);
} catch ( RuntimeException $error ) {
	occurrence_context_assert( 'expected' === $error->getMessage(), 'Exception is propagated.' );
}
occurrence_context_assert( array() === wp_seed_events_occurrence_context_current(), 'Exception restores context.' );

$expected = array(
	'event_title'             => 'Theme A',
	'event_slug'              => 'theme-a',
	'event_type'              => 'seminaire',
	'event_status'            => 'publish',
	'event_is_pinned'         => '1',
	'occurrence_uid'          => 'occ-a',
	'occurrence_start'        => '2026-02-03 09:30',
	'occurrence_end'          => '2026-02-03 12:00',
	'occurrence_start_date'   => 'DATE:2026-02-03',
	'occurrence_end_date'     => 'DATE:2026-02-03',
	'occurrence_start_time'   => 'TIME:09:30',
	'occurrence_end_time'     => 'TIME:12:00',
	'occurrence_is_cancelled' => '0',
	'promotion_id'            => '201',
	'promotion_name'          => 'Promotion 2026',
	'promotion_slug'          => 'promotion-2026',
	'promotion_start_year'    => '2026',
	'promotion_status'        => 'active',
	'parcours_year'           => '1',
	'parcours_year_label'     => 'Année 1',
);

foreach ( $expected as $field => $value ) {
	occurrence_context_assert( $value === wp_seed_events_occurrence_context_value( $field, $context_a ), 'Field ' . $field . '.' );
}

occurrence_context_assert( '' === wp_seed_events_occurrence_context_value( 'promotion_name' ), 'No context is empty.' );
occurrence_context_assert( '' === wp_seed_events_occurrence_context_value( 'unknown', $context_a ), 'Unknown field is empty.' );
occurrence_context_assert( 20 === count( wp_seed_events_occurrence_dynamic_data_fields() ), 'Twenty canonical fields.' );

$block = new WP_Block( array(), array( 'wpSeedEvents/occurrence' => $context_b ) );
occurrence_context_assert(
	'Promotion 2027' === wp_seed_events_gutenberg_occurrence_block_binding_value( array( 'field' => 'promotion_name' ), $block, 'content' ),
	'Binding reads explicit block context.'
);
occurrence_context_assert(
	'' === wp_seed_events_gutenberg_occurrence_block_binding_value( array( 'field' => 'promotion_name' ), new WP_Block(), 'content' ),
	'Binding without occurrence context is empty.'
);
occurrence_context_assert(
	'' === wp_seed_events_gutenberg_occurrence_block_binding_value( array( 'field' => 'private_email' ), $block, 'content' ),
	'Private field is rejected.'
);

echo 'Occurrence builder context: ' . $assertions . '/' . $assertions . " assertions passed.\n";
