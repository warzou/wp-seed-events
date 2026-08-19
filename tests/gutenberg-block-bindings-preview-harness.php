<?php
/**
 * Runtime-free assertions for Gutenberg Block Bindings editor previews.
 *
 * Run with: php tests/gutenberg-block-bindings-preview-harness.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['preview_actions']       = array();
$GLOBALS['preview_filters']       = array();
$GLOBALS['preview_sources']       = array();
$GLOBALS['preview_rest_field']    = array();
$GLOBALS['preview_can_edit']      = true;
$GLOBALS['preview_values']        = array();
$GLOBALS['preview_value_calls']   = 0;
$GLOBALS['preview_assertions']    = 0;

function add_action( $hook, $callback ) {
	$GLOBALS['preview_actions'][] = array( $hook, $callback );
}

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['preview_filters'][] = array( $hook, $callback, $priority, $accepted_args );
}

function register_block_bindings_source( $name, $properties ) {
	$GLOBALS['preview_sources'][] = array( $name, $properties );
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

function wp_seed_events_occurrence_dynamic_data_fields() {
	return array( 'occurrence_uid' => 'Occurrence UID' );
}

function wp_seed_events_occurrence_context_value() {
	return '';
}

function preview_assert( $condition, $message ) {
	$GLOBALS['preview_assertions']++;
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

class WP_HTML_Tag_Processor {
	private $html;

	public function __construct( $html ) {
		$this->html = $html;
	}

	public function next_tag() {
		return false !== strpos( $this->html, '<' );
	}

	public function add_class( $class ) {
		$this->html = preg_replace( '/<([a-z0-9]+)([^>]*)>/i', '<$1$2 class="' . $class . '">', $this->html, 1 );
	}

	public function get_updated_html() {
		return $this->html;
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

preview_assert(
	array( array( 'render_block', 'wp_seed_events_gutenberg_multiline_excerpt_block', 10, 2 ) ) === $GLOBALS['preview_filters'],
	'Expected bounded render_block filter.'
);

wp_seed_events_register_gutenberg_block_bindings_source();
preview_assert( 2 === count( $GLOBALS['preview_sources'] ), 'Exactly two distinct binding sources are registered.' );
preview_assert( 'wp-seed-events/event-field' === $GLOBALS['preview_sources'][0][0], 'Historical binding source name differs.' );
preview_assert(
	array( 'postId', 'postType', 'queryId' ) === $GLOBALS['preview_sources'][0][1]['uses_context'],
	'Historical binding source contexts differ.'
);
preview_assert( 'wp-seed-events/occurrence-field' === $GLOBALS['preview_sources'][1][0], 'Occurrence binding source name differs.' );
preview_assert(
	array( 'wpSeedEvents/occurrence' ) === $GLOBALS['preview_sources'][1][1]['uses_context'],
	'Occurrence binding source context differs.'
);
preview_assert(
	'' === wp_seed_events_gutenberg_occurrence_block_binding_value( array( 'field' => 'occurrence_uid' ), new stdClass(), 'content' ),
	'Occurrence binding must stay empty without WP_Block being loaded.'
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
	array( 'types', 'status', 'display_date', 'place', 'contact', 'excerpt', 'url' ) === wp_seed_events_gutenberg_block_binding_preview_fields(),
	'Preview field allowlist differs.'
);

$GLOBALS['preview_values'][914] = array(
	'types'        => 'Atelier',
	'status'       => 'À venir',
	'display_date' => 'Vendredi 31 juillet 2026',
	'place'        => 'Centre Shania',
	'contact'      => 'Claire Test',
	'excerpt'      => "Une ligne\nUne autre ligne",
	'url'          => 'https://example.test/atelier/exemple/',
);
$values = wp_seed_events_gutenberg_block_bindings_rest_values(
	array( 'id' => 914 ),
	'wp_seed_events_public_fields',
	new Preview_Request( 'edit' )
);
preview_assert( $GLOBALS['preview_values'][914] === $values, 'Authorized preview values differ.' );
preview_assert( 7 === $GLOBALS['preview_value_calls'], 'Each allowlisted value must be resolved once.' );
preview_assert( "Une ligne\nUne autre ligne" === $values['excerpt'], 'REST preview flattened multiline excerpt.' );

$excerpt_block = array(
	'attrs' => array(
		'metadata' => array(
			'bindings' => array(
				'content' => array(
					'source' => 'wp-seed-events/event-field',
					'args'   => array( 'field' => 'excerpt' ),
				),
			),
		),
	),
);
$rendered = wp_seed_events_gutenberg_multiline_excerpt_block( '<p>Une ligne' . "\n" . 'Une autre ligne</p>', $excerpt_block );
preview_assert( false !== strpos( $rendered, 'wp-seed-events-multiline-text' ), 'Excerpt block lacks multiline class.' );
preview_assert( false !== strpos( $rendered, "Une ligne\nUne autre ligne" ), 'Excerpt block flattened textual newline.' );

$title_block = $excerpt_block;
$title_block['attrs']['metadata']['bindings']['content']['args']['field'] = 'title';
preview_assert(
	'<p>Titre</p>' === wp_seed_events_gutenberg_multiline_excerpt_block( '<p>Titre</p>', $title_block ),
	'Non-excerpt binding was modified.'
);

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
