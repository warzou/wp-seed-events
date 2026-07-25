<?php
/**
 * Runtime-free assertions for Gutenberg Block Bindings editor previews.
 *
 * Run with: php tests/gutenberg-block-bindings-preview-harness.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['preview_actions']       = array();
$GLOBALS['preview_source']        = array();
$GLOBALS['preview_rest_field']    = array();
$GLOBALS['preview_can_edit']      = true;
$GLOBALS['preview_values']        = array();
$GLOBALS['preview_value_calls']   = 0;
$GLOBALS['preview_assertions']    = 0;

function add_action( $hook, $callback ) {
	$GLOBALS['preview_actions'][] = array( $hook, $callback );
}

function register_block_bindings_source( $name, $properties ) {
	$GLOBALS['preview_source'] = array( $name, $properties );
}

function register_rest_field( $post_type, $field_name, $properties ) {
	$GLOBALS['preview_rest_field'] = array( $post_type, $field_name, $properties );
}

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function current_user_can( $capability, $event_id ) {
	return 'edit_post' === $capability && 914 === (int) $event_id && $GLOBALS['preview_can_edit'];
}

function wp_seed_events_dynamic_data_get_value( $field, $event_id ) {
	$GLOBALS['preview_value_calls']++;
	return $GLOBALS['preview_values'][ $event_id ][ $field ] ?? '';
}

function preview_assert( $condition, $message ) {
	$GLOBALS['preview_assertions']++;
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

class Preview_Request {
	private $context;

	public function __construct( $context ) {
		$this->context = $context;
	}

	public function get_param( $name ) {
		return 'context' === $name ? $this->context : null;
	}
}

require dirname( __DIR__ ) . '/includes/integrations/gutenberg/block-bindings.php';

preview_assert(
	array(
		array( 'init', 'wp_seed_events_register_gutenberg_block_bindings_source' ),
		array( 'rest_api_init', 'wp_seed_events_register_gutenberg_block_bindings_rest_field' ),
	) === $GLOBALS['preview_actions'],
	'Expected init and REST registration hooks.'
);

wp_seed_events_register_gutenberg_block_bindings_source();
preview_assert( 'wp-seed-events/event-field' === $GLOBALS['preview_source'][0], 'Binding source name differs.' );
preview_assert(
	array( 'postId', 'postType', 'queryId' ) === $GLOBALS['preview_source'][1]['uses_context'],
	'Binding source contexts differ.'
);

wp_seed_events_register_gutenberg_block_bindings_rest_field();
preview_assert( 'wp_seed_event' === $GLOBALS['preview_rest_field'][0], 'REST field post type differs.' );
preview_assert( 'wp_seed_events_public_fields' === $GLOBALS['preview_rest_field'][1], 'REST field name differs.' );
preview_assert(
	array( 'edit' ) === $GLOBALS['preview_rest_field'][2]['schema']['context'],
	'Preview field must be restricted to edit context.'
);
preview_assert(
	wp_seed_events_gutenberg_block_binding_preview_fields() === array_keys( $GLOBALS['preview_rest_field'][2]['schema']['properties'] ),
	'Preview schema and field allowlist differ.'
);
preview_assert(
	array( 'types', 'status', 'display_date', 'place', 'excerpt', 'url' ) === wp_seed_events_gutenberg_block_binding_preview_fields(),
	'Preview field allowlist differs.'
);

$GLOBALS['preview_values'][914] = array(
	'types'        => 'Atelier',
	'status'       => 'À venir',
	'display_date' => 'Vendredi 31 juillet 2026',
	'place'        => 'Centre Shania',
	'excerpt'      => 'Un extrait public.',
	'url'          => 'https://example.test/atelier/exemple/',
);
$values = wp_seed_events_gutenberg_block_bindings_rest_values(
	array( 'id' => 914 ),
	'wp_seed_events_public_fields',
	new Preview_Request( 'edit' )
);
preview_assert( $GLOBALS['preview_values'][914] === $values, 'Authorized preview values differ.' );
preview_assert( 6 === $GLOBALS['preview_value_calls'], 'Each allowlisted value must be resolved once.' );

$GLOBALS['preview_value_calls'] = 0;
preview_assert(
	null === wp_seed_events_gutenberg_block_bindings_rest_values( array( 'id' => 914 ), '', new Preview_Request( 'view' ) ),
	'Public view context must not expose the editor payload.'
);
preview_assert( 0 === $GLOBALS['preview_value_calls'], 'View context resolved values unexpectedly.' );

$GLOBALS['preview_can_edit'] = false;
preview_assert(
	null === wp_seed_events_gutenberg_block_bindings_rest_values( array( 'id' => 914 ), '', new Preview_Request( 'edit' ) ),
	'Unauthorized editor must not receive preview values.'
);
preview_assert( 0 === $GLOBALS['preview_value_calls'], 'Unauthorized context resolved values unexpectedly.' );

$GLOBALS['preview_can_edit'] = true;
preview_assert(
	null === wp_seed_events_gutenberg_block_bindings_rest_values( array( 'id' => 0 ), '', new Preview_Request( 'edit' ) ),
	'Invalid event must not receive preview values.'
);
preview_assert(
	null === wp_seed_events_gutenberg_block_bindings_rest_values( 'invalid', '', new Preview_Request( 'edit' ) ),
	'Invalid prepared object must fail closed.'
);

echo 'Gutenberg Block Bindings preview harness: ' . $GLOBALS['preview_assertions'] . '/' . $GLOBALS['preview_assertions'] . ' OK' . PHP_EOL;