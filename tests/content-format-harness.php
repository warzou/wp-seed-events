<?php

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['content_filter_calls'] = 0;
$GLOBALS['content_event'] = array();

function add_action() {}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function strip_shortcodes( $value ) { return preg_replace( '/\[[^\]]+\]/', '', (string) $value ); }
function wp_kses_post( $value ) { return preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', (string) $value ); }
function apply_filters( $hook, $value ) {
	if ( 'the_content' === $hook ) { $GLOBALS['content_filter_calls']++; }
	return (string) $value;
}
function get_the_ID() { return 701; }
function get_post_type( $id ) { return 701 === (int) $id ? 'wp_seed_event' : ''; }
function wp_seed_events_get_event_data( $id ) { return 701 === (int) $id ? $GLOBALS['content_event'] : array(); }
function wp_seed_events_sanitize_public_http_url( $value ) { return (string) $value; }
function wp_seed_events_public_event_next_date_line() { return ''; }
function wp_seed_events_public_event_next_time_line() { return ''; }
function wp_seed_events_public_event_display_date_line() { return ''; }
function wp_seed_events_public_event_display_time_line() { return ''; }
function wp_seed_events_public_event_status_label() { return ''; }
function wp_seed_events_normalize_person_phone( $value ) { return (string) $value; }
function wp_seed_events_normalize_person_email( $value ) { return (string) $value; }
function wp_seed_events_normalize_person_link( $value ) { return (string) $value; }
function sanitize_file_name( $value ) { return basename( (string) $value ); }
function wp_basename( $value ) { return basename( (string) $value ); }
function wp_seed_events_gutenberg_event_people_resolve_event_id( $context ) { return absint( $context['postId'] ?? 0 ); }
function wp_seed_events_gutenberg_event_people_block_context( $block ) { return is_object( $block ) ? (array) ( $block->context ?? array() ) : array(); }
function get_block_wrapper_attributes( $attributes = array() ) { return 'class="' . htmlspecialchars( (string) ( $attributes['class'] ?? '' ), ENT_QUOTES ) . '"'; }

require dirname( __DIR__ ) . '/includes/public/descriptions.php';
require dirname( __DIR__ ) . '/includes/public/data-registry.php';
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-content-block.php';

$rich = '<h2>Programme</h2><p>Premier <strong>gras</strong>.</p><p>Deuxième <em>italique</em>.</p><ul><li>Élément 1</li><li>Élément 2</li></ul><a href="https://example.test">Lien</a><script>alert(1)</script>';
$rich = str_replace( '</h2>', '</h2><h3>Details</h3>', $rich );
$GLOBALS['content_event'] = array( 'description' => $rich, 'excerpt' => "Ligne 1\nLigne 2\n\nParagraphe 2", 'practical_info' => "Info 1\nInfo 2" );

$rendered = wp_seed_events_dynamic_data_get_value( 'description', 701 );
foreach ( array( '<h2>', '<h3>', '<p>', '<strong>', '<em>', '<ul>', '<li>', '<a href=' ) as $markup ) {
	if ( false === strpos( $rendered, $markup ) ) { throw new RuntimeException( 'Rich markup missing: ' . $markup ); }
}
if ( false !== strpos( $rendered, '<script' ) || 1 !== $GLOBALS['content_filter_calls'] ) {
	throw new RuntimeException( 'Rich sanitization/filter count differs.' );
}
if ( "Ligne 1\nLigne 2\n\nParagraphe 2" !== wp_seed_events_dynamic_data_get_value( 'excerpt', 701 ) ) {
	throw new RuntimeException( 'Multiline text changed.' );
}

$gutenberg = wp_seed_events_render_gutenberg_event_content_block(
	array(),
	'',
	(object) array( 'context' => array( 'postId' => 701, 'postType' => 'wp_seed_event' ) )
);
foreach ( array( 'wp-seed-events-rich-content', '<h2>', '<h3>', '<strong>', '<em>', '<ul>', '<li>', '<a href=' ) as $markup ) {
	if ( false === strpos( $gutenberg, $markup ) ) { throw new RuntimeException( 'Gutenberg rich markup missing: ' . $markup ); }
}
if ( false !== strpos( $gutenberg, '<script' ) ) {
	throw new RuntimeException( 'Gutenberg rich output is not sanitized.' );
}

$expected_formats = array( 'title' => 'plain_text', 'description' => 'rich_html', 'excerpt' => 'multiline_text', 'practical_info' => 'multiline_text', 'url' => 'url', 'communication_visual' => 'image' );
foreach ( $expected_formats as $field => $format ) {
	if ( $format !== wp_seed_events_dynamic_data_field_format( $field ) ) { throw new RuntimeException( 'Format differs: ' . $field ); }
}

$divi = (string) file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-text.php' );
if ( false === strpos( $divi, "'rich_html' === \$format" ) || false === strpos( $divi, "'multiline_text' === \$format" ) ) {
	throw new RuntimeException( 'Divi format dispatch missing.' );
}

echo "Content format harness: 24/24 OK\n";
