<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['content_filter_calls'] = 0;
$GLOBALS['content_event']        = array();
$GLOBALS['registered_block']     = array();

function add_action() {}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function strip_shortcodes( $value ) { return preg_replace( '/\[[^\]]+\]/', '', (string) $value ); }
function wp_kses_post( $value ) { return preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $value ); }
function apply_filters( $hook, $value ) {
	if ( 'the_content' === $hook ) {
		$GLOBALS['content_filter_calls']++;
		$value = str_replace( '[example]', '<p class="shortcode">Shortcode</p>', (string) $value );
	}
	return (string) $value;
}
function get_the_ID() { return 701; }
function get_post_type( $id ) { return in_array( (int) $id, array( 701, 702 ), true ) ? 'wp_seed_event' : 'page'; }
function get_post_status( $id ) { return 701 === (int) $id ? 'publish' : 'draft'; }
class WP_Post {
	public $post_content = '';
}
function get_post( $id ) {
	if ( 702 !== (int) $id ) { return null; }
	$post               = new WP_Post();
	$post->post_content = (string) ( $GLOBALS['content_event']['description'] ?? '' );
	return $post;
}
function wp_seed_events_get_event_data( $id ) { return 701 === (int) $id ? $GLOBALS['content_event'] : array(); }
function has_block( $name, $content ) { return false !== strpos( (string) $content, '<!-- wp:' . $name ); }
function get_block_wrapper_attributes( $attributes = array() ) { return 'class="' . htmlspecialchars( (string) ( $attributes['class'] ?? '' ), ENT_QUOTES ) . '"'; }
function register_block_type_from_metadata( $path, $args ) { $GLOBALS['registered_block'] = array( $path, $args ); return true; }

require dirname( __DIR__ ) . '/includes/public/descriptions.php';
require dirname( __DIR__ ) . '/includes/public/data-registry.php';
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-content-block.php';

$rich = '<h2>Programme</h2><h3>Détails</h3><p>Premier <strong>gras</strong>.</p><ul><li>Élément</li></ul><p><a href="https://example.test">Lien</a></p><figure><img src="https://example.test/image.jpg" alt="Visuel"></figure>[example]<script>alert(1)</script>';
$GLOBALS['content_event'] = array( 'description' => $rich );

$rendered = wp_seed_events_dynamic_data_get_value( 'description', 701 );
foreach ( array( '<h2>', '<h3>', '<p>', '<strong>', '<ul>', '<li>', '<a href=', '<img ', 'Shortcode' ) as $markup ) {
	if ( false === strpos( $rendered, $markup ) ) { throw new RuntimeException( 'Rich markup missing: ' . $markup ); }
}
if ( false !== strpos( $rendered, '<script' ) || 1 !== $GLOBALS['content_filter_calls'] ) {
	throw new RuntimeException( 'Rich sanitization or filter count differs.' );
}

$GLOBALS['content_filter_calls'] = 0;
$block = wp_seed_events_render_gutenberg_event_content_block( array(), '', (object) array( 'context' => array( 'postId' => 701, 'postType' => 'wp_seed_event' ) ) );
if ( false === strpos( $block, 'wp-seed-events-rich-content' ) || 1 !== $GLOBALS['content_filter_calls'] ) {
	throw new RuntimeException( 'Frontend block rendering differs.' );
}
if ( '' !== wp_seed_events_gutenberg_event_content_render( array( 'postId' => 999, 'postType' => 'page' ) ) ) {
	throw new RuntimeException( 'Invalid context did not render empty.' );
}
if ( '' !== wp_seed_events_gutenberg_event_content_render( array( 'postId' => 702, 'postType' => 'wp_seed_event' ) ) ) {
	throw new RuntimeException( 'Draft content leaked to the public renderer.' );
}
if ( false === strpos( wp_seed_events_gutenberg_event_content_render( array( 'postId' => 702, 'postType' => 'wp_seed_event' ), true ), '<h2>' ) ) {
	throw new RuntimeException( 'Authorized draft preview is empty.' );
}

$GLOBALS['content_event']['description'] = '';
if ( '' !== wp_seed_events_gutenberg_event_content_render( array( 'postId' => 701, 'postType' => 'wp_seed_event' ) ) ) {
	throw new RuntimeException( 'Empty content did not render empty.' );
}

$GLOBALS['content_event']['description'] = '<!-- wp:wp-seed-events/event-content-block /-->';
$GLOBALS['content_filter_calls'] = 0;
if ( '' !== wp_seed_events_gutenberg_event_content_render( array( 'postId' => 701, 'postType' => 'wp_seed_event' ) ) || 0 !== $GLOBALS['content_filter_calls'] ) {
	throw new RuntimeException( 'Self-reference was filtered or rendered recursively.' );
}

wp_seed_events_register_event_content_block();
if ( ! is_callable( $GLOBALS['registered_block'][1]['render_callback'] ?? null ) ) {
	throw new RuntimeException( 'Dynamic block was not registered.' );
}

foreach ( array( 'description' => 'rich_html', 'excerpt' => 'multiline_text', 'url' => 'url' ) as $field => $format ) {
	if ( $format !== wp_seed_events_dynamic_data_field_format( $field ) ) { throw new RuntimeException( 'Format differs: ' . $field ); }
}

echo "Rich content harness: 20/20 OK\n";
