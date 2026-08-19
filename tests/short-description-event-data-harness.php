<?php
/**
 * Runtime integration for short descriptions in canonical Event Data.
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['sdi_posts'] = array();
$GLOBALS['sdi_meta']  = array();
$GLOBALS['sdi_writes'] = 0;
$GLOBALS['sdi_assertions'] = 0;

class WP_Post {
	public $ID;
	public $post_type = 'wp_seed_event';
	public $post_status = 'publish';
	public $post_content = '';
	public $post_excerpt = '';
	public $post_name = '';

	public function __construct( $id, $content, $legacy ) {
		$this->ID = $id;
		$this->post_content = $content;
		$this->post_excerpt = $legacy;
		$this->post_name = 'event-' . $id;
	}
}

function add_action() {}
function absint( $value ) { return abs( (int) $value ); }
function strip_shortcodes( $value ) { return preg_replace( '/\[(?:\/)?[^\]]+\]/', '', (string) $value ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function sanitize_textarea_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_file_name( $value ) { return basename( (string) $value ); }
function get_extended( $content ) {
	$parts = preg_split( '/<!--more(?:.*?)?-->/is', (string) $content, 2 );
	return array( 'main' => $parts[0] ?? '', 'extended' => $parts[1] ?? '' );
}
function get_post( $id ) { return $GLOBALS['sdi_posts'][ absint( $id ) ] ?? null; }
function get_post_meta( $id, $key ) { return $GLOBALS['sdi_meta'][ absint( $id ) ][ $key ] ?? ''; }
function update_post_meta() { $GLOBALS['sdi_writes']++; }
function delete_post_meta() { $GLOBALS['sdi_writes']++; }
function wp_seed_events_get_event_occurrences() { return array(); }
function wp_seed_events_normalize_parcours_year( $value ) { return absint( $value ); }
function wp_seed_events_get_next_active_occurrence() { return array(); }
function wp_seed_events_get_last_active_occurrence() { return array(); }
function wp_seed_events_get_event_lifecycle() { return 'undated'; }
function wp_seed_events_get_event_media() {
	return array(
		'featured_image' => null,
		'communication_visual' => null,
		'communication_visuals' => array(),
		'other_visuals' => array(),
		'event_document' => null,
	);
}
function wp_seed_events_public_event_place_data() { return array(); }
function get_permalink( $id ) { return 'https://example.test/event-' . absint( $id ) . '/'; }
function esc_url_raw( $url ) { return (string) $url; }
function wp_parse_url( $url ) { return parse_url( (string) $url ); }
function wp_seed_events_event_type_labels_for_event() { return array(); }
function wp_seed_events_public_event_people_data() { return array(); }
function get_the_title( $id ) { return 'Event ' . absint( $id ); }

require dirname( __DIR__ ) . '/includes/public/descriptions.php';
require dirname( __DIR__ ) . '/includes/public/event-data.php';

function sdi_assert( $condition, $message ) {
	$GLOBALS['sdi_assertions']++;
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

$post = new WP_Post( 1201, "Description complète\nDeuxième ligne", "Legacy\r\nbyte exact" );
$GLOBALS['sdi_posts'][1201] = $post;
$GLOBALS['sdi_meta'][1201][WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY] = "Courte ligne 1\nCourte ligne 2";

$event = wp_seed_events_get_event_data( 1201 );
sdi_assert( "Description complète\nDeuxième ligne" === $event['description'], 'full description differs' );
sdi_assert( "Courte ligne 1\nCourte ligne 2" === $event['short_description'], 'manual short description differs' );
sdi_assert( "Courte ligne 1\nCourte ligne 2" === $event['short_description_effective'], 'effective description differs' );
sdi_assert( $event['short_description_effective'] === $event['excerpt'], 'excerpt is not a strict alias' );
sdi_assert( false === strpos( $event['excerpt'], '<br' ), 'Event Data contains presentation HTML' );
sdi_assert( "Legacy\r\nbyte exact" === $post->post_excerpt, 'legacy post_excerpt changed' );
sdi_assert( 0 === $GLOBALS['sdi_writes'], 'Event Data read wrote storage' );

unset( $GLOBALS['sdi_meta'][1201][WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY] );
$post->post_content = "Avant\néditorial<!--more-->Après";
$event = wp_seed_events_get_event_data( 1201 );
sdi_assert( "Avant\néditorial" === $event['excerpt'], 'more fallback differs in Event Data' );
sdi_assert( '' === $event['short_description'], 'missing manual value was invented' );
sdi_assert( 0 === $GLOBALS['sdi_writes'], 'fallback read wrote storage' );

echo 'Short description Event Data integration: ' . $GLOBALS['sdi_assertions'] . '/' . $GLOBALS['sdi_assertions'] . ' OK' . PHP_EOL;
