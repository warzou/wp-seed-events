<?php
/**
 * V1 optional multiline short-description contract.
 *
 * Run with: php tests/short-description-harness.php
 */

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['sd_actions']       = array();
$GLOBALS['sd_meta']          = array();
$GLOBALS['sd_posts']         = array();
$GLOBALS['sd_writes']        = array();
$GLOBALS['sd_rest_fields']   = array();
$GLOBALS['sd_can_edit']      = true;
$GLOBALS['sd_nonce_valid']   = true;
$GLOBALS['sd_is_revision']   = false;
$GLOBALS['sd_assertions']    = 0;
$GLOBALS['sd_cases']         = 0;

class WP_Post {
	public $ID;
	public $post_type = 'wp_seed_event';
	public $post_status = 'publish';
	public $post_content = '';
	public $post_excerpt = '';

	public function __construct( $id, $content = '', $excerpt = '' ) {
		$this->ID           = (int) $id;
		$this->post_content = $content;
		$this->post_excerpt = $excerpt;
	}
}

class WP_Error {
	public $code;
	public function __construct( $code ) {
		$this->code = $code;
	}
}

class SD_Request {
	private $context;
	public function __construct( $context ) {
		$this->context = $context;
	}
	public function get_param( $name ) {
		return 'context' === $name ? $this->context : null;
	}
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['sd_actions'][] = array( $hook, $callback, $priority, $accepted_args );
}
function absint( $value ) {
	return abs( (int) $value );
}
function strip_shortcodes( $value ) {
	return preg_replace( '/\[(?:\/)?[^\]]+\]/', '', (string) $value );
}
function wp_strip_all_tags( $value ) {
	return strip_tags( (string) $value );
}
function sanitize_textarea_field( $value ) {
	return trim( preg_replace( '/[ \t]+$/m', '', strip_tags( (string) $value ) ) );
}
function sanitize_text_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}
function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}
function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}
function get_extended( $content ) {
	$parts = preg_split( '/<!--more(?:.*?)?-->/is', (string) $content, 2 );
	return array( 'main' => $parts[0] ?? '', 'extended' => $parts[1] ?? '' );
}
function get_post( $id ) {
	return $GLOBALS['sd_posts'][ (int) $id ] ?? null;
}
function get_post_meta( $id, $key ) {
	return $GLOBALS['sd_meta'][ (int) $id ][ $key ] ?? '';
}
function update_post_meta( $id, $key, $value ) {
	$GLOBALS['sd_writes'][] = array( 'update', (int) $id, $key, $value );
	$GLOBALS['sd_meta'][ (int) $id ][ $key ] = $value;
	return true;
}
function delete_post_meta( $id, $key ) {
	$GLOBALS['sd_writes'][] = array( 'delete', (int) $id, $key );
	unset( $GLOBALS['sd_meta'][ (int) $id ][ $key ] );
	return true;
}
function wp_nonce_field( $action, $name ) {
	echo '<input type="hidden" name="' . $name . '" value="valid">';
}
function esc_textarea( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
}
function wp_verify_nonce() {
	return $GLOBALS['sd_nonce_valid'];
}
function wp_is_post_revision() {
	return $GLOBALS['sd_is_revision'];
}
function get_post_type( $id ) {
	$post = get_post( $id );
	return $post ? $post->post_type : '';
}
function current_user_can() {
	return $GLOBALS['sd_can_edit'];
}
function __( $value ) {
	return $value;
}
function register_rest_field( $post_type, $name, $args ) {
	$GLOBALS['sd_rest_fields'][ $name ] = array( $post_type, $args );
}

require dirname( __DIR__ ) . '/includes/public/descriptions.php';

function sd_assert( $condition, $message ) {
	$GLOBALS['sd_assertions']++;
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}
function sd_case( $number, $name, $callback ) {
	$GLOBALS['sd_cases']++;
	try {
		$callback();
	} catch ( Throwable $error ) {
		fwrite( STDERR, 'FAIL case ' . $number . ' (' . $name . '): ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}
function sd_words( $count ) {
	$words = array();
	for ( $i = 1; $i <= $count; $i++ ) {
		$words[] = 'mot' . $i;
	}
	return implode( ' ', $words );
}
function sd_event( $id, $content, $legacy = '' ) {
	$GLOBALS['sd_posts'][ $id ] = new WP_Post( $id, $content, $legacy );
	return $GLOBALS['sd_posts'][ $id ];
}
function sd_reset_save_state() {
	$_POST = array();
	$GLOBALS['sd_writes']      = array();
	$GLOBALS['sd_can_edit']    = true;
	$GLOBALS['sd_nonce_valid'] = true;
	$GLOBALS['sd_is_revision'] = false;
}
function sd_source( $path ) {
	return file_get_contents( dirname( __DIR__ ) . '/' . $path );
}

sd_case( 1, 'manual', function () {
	sd_assert( 'Manuelle' === wp_seed_events_resolve_short_description( 'Complète', 'Manuelle' ), 'manual value differs' );
} );
sd_case( 2, 'manual two lines', function () {
	sd_assert( "Ligne 1\nLigne 2" === wp_seed_events_resolve_short_description( 'Complète', "Ligne 1\nLigne 2" ), 'manual newlines differ' );
} );
sd_case( 3, 'manual blank line', function () {
	sd_assert( "Un\n\nDeux" === wp_seed_events_resolve_short_description( 'Complète', "Un\n\nDeux" ), 'manual blank line differs' );
} );
sd_case( 4, 'manual whitespace only', function () {
	sd_assert( 'Automatique' === wp_seed_events_resolve_short_description( 'Automatique', " \n\t " ), 'whitespace manual did not fall back' );
} );
sd_case( 5, 'manual zero', function () {
	sd_assert( '0' === wp_seed_events_resolve_short_description( 'Automatique', '0' ), 'zero manual rejected' );
} );
sd_case( 6, 'more', function () {
	sd_assert( 'Avant' === wp_seed_events_resolve_short_description( 'Avant<!--more-->Après' ), 'more differs' );
} );
sd_case( 7, 'more label', function () {
	sd_assert( 'Avant' === wp_seed_events_resolve_short_description( 'Avant<!--more Continuer-->Après' ), 'labeled more differs' );
} );
sd_case( 8, 'more paragraphs', function () {
	sd_assert( "Un\n\nDeux" === wp_seed_events_resolve_short_description( '<p>Un</p><p>Deux</p><!--more--><p>Trois</p>' ), 'paragraph more differs' );
} );
sd_case( 9, 'early more', function () {
	sd_assert( 'Un' === wp_seed_events_resolve_short_description( 'Un<!--more-->' . sd_words( 50 ) ), 'early more differs' );
} );
sd_case( 10, 'late more', function () {
	$before = sd_words( 55 );
	sd_assert( $before === wp_seed_events_resolve_short_description( $before . '<!--more-->Après' ), 'late more was truncated' );
} );
sd_case( 11, 'more at end', function () {
	sd_assert( 'Texte final' === wp_seed_events_resolve_short_description( 'Texte final<!--more-->' ), 'end more differs' );
} );
sd_case( 12, 'no more', function () {
	sd_assert( 'Texte simple' === wp_seed_events_resolve_short_description( 'Texte simple' ), 'no-more fallback differs' );
} );
sd_case( 13, 'under 40 words', function () {
	sd_assert( sd_words( 39 ) === wp_seed_events_resolve_short_description( sd_words( 39 ) ), 'under-limit text differs' );
} );
sd_case( 14, 'exactly 40 words', function () {
	sd_assert( sd_words( 40 ) === wp_seed_events_resolve_short_description( sd_words( 40 ) ), 'exact-limit text differs' );
} );
sd_case( 15, 'over 40 words', function () {
	sd_assert( sd_words( 40 ) . '…' === wp_seed_events_resolve_short_description( sd_words( 41 ) ), 'over-limit result differs' );
} );
sd_case( 16, 'empty description', function () {
	sd_assert( '' === wp_seed_events_resolve_short_description( '' ), 'empty description differs' );
} );
sd_case( 17, 'simple HTML', function () {
	sd_assert( 'Texte gras' === wp_seed_events_resolve_short_description( '<p>Texte <strong>gras</strong></p>' ), 'HTML cleanup differs' );
} );
sd_case( 18, 'HTML paragraphs', function () {
	sd_assert( "Un\n\nDeux" === wp_seed_events_resolve_short_description( '<p>Un</p><p>Deux</p>' ), 'HTML paragraph boundaries differ' );
} );
sd_case( 19, 'HTML br', function () {
	sd_assert( "Un\nDeux" === wp_seed_events_resolve_short_description( 'Un<br>Deux' ), 'br boundary differs' );
} );
sd_case( 20, 'script and style', function () {
	sd_assert( 'AvantAprès' === wp_seed_events_resolve_short_description( 'Avant<script>alert(1)</script><style>x{}</style>Après' ), 'script/style survived' );
} );
sd_case( 21, 'shortcode', function () {
	sd_assert( 'AvantTexteAprès' === wp_seed_events_resolve_short_description( 'Avant[x]Texte[/x]Après' ), 'shortcode survived' );
} );
sd_case( 22, 'UTF-8', function () {
	sd_assert( 'Événement cœur d’été' === wp_seed_events_resolve_short_description( 'Événement cœur d’été' ), 'UTF-8 differs' );
} );
sd_case( 23, 'CRLF', function () {
	sd_assert( "Un\nDeux" === wp_seed_events_resolve_short_description( "Un\r\nDeux" ), 'CRLF not normalized' );
} );
sd_case( 24, 'CR', function () {
	sd_assert( "Un\nDeux" === wp_seed_events_resolve_short_description( "Un\rDeux" ), 'CR not normalized' );
} );
sd_case( 25, 'LF', function () {
	sd_assert( "Un\nDeux" === wp_seed_events_resolve_short_description( "Un\nDeux" ), 'LF differs' );
} );
sd_case( 26, 'trailing spaces', function () {
	sd_assert( "Un\nDeux" === wp_seed_events_resolve_short_description( "Un   \nDeux\t " ), 'line trailing spaces differ' );
} );
sd_case( 27, 'excess blank lines', function () {
	sd_assert( "Un\n\nDeux" === wp_seed_events_resolve_short_description( "Un\n\n\n\nDeux" ), 'blank-line compaction differs' );
} );
sd_case( 28, 'truncation across newline', function () {
	$text = sd_words( 20 ) . "\n" . implode( ' ', array_slice( explode( ' ', sd_words( 41 ) ), 20 ) );
	sd_assert( false !== strpos( wp_seed_events_resolve_short_description( $text ), "\n" ), 'truncation flattened newline' );
} );
sd_case( 29, 'ellipsis only on truncation', function () {
	sd_assert( str_ends_with( wp_seed_events_resolve_short_description( sd_words( 41 ) ), '…' ), 'missing truncation ellipsis' );
} );
sd_case( 30, 'no ellipsis without truncation', function () {
	sd_assert( ! str_ends_with( wp_seed_events_resolve_short_description( sd_words( 40 ) ), '…' ), 'unexpected ellipsis' );
} );

sd_case( 31, 'legacy full copy ignored', function () {
	$post = sd_event( 931, sd_words( 41 ), sd_words( 41 ) );
	sd_assert( sd_words( 40 ) . '…' === wp_seed_events_public_event_excerpt( $post ), 'legacy copy influenced effective value' );
} );
sd_case( 32, 'legacy transformed summary ignored', function () {
	$post = sd_event( 932, 'Contenu canonique', 'Résumé legacy distinct' );
	sd_assert( 'Contenu canonique' === wp_seed_events_public_event_excerpt( $post ), 'legacy summary influenced excerpt' );
} );
sd_case( 33, 'legacy unchanged after manual save', function () {
	sd_reset_save_state();
	$post = sd_event( 933, 'Complet', "Legacy\r\nbyte");
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => "Court\nmanuel" );
	wp_seed_events_save_short_description( 933 );
	sd_assert( "Legacy\r\nbyte" === $post->post_excerpt, 'legacy changed after save' );
} );
sd_case( 34, 'legacy unchanged after manual delete', function () {
	sd_reset_save_state();
	$post = sd_event( 934, 'Complet', 'Legacy exact' );
	$GLOBALS['sd_meta'][934][WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY] = 'Avant';
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => '' );
	wp_seed_events_save_short_description( 934 );
	sd_assert( 'Legacy exact' === $post->post_excerpt, 'legacy changed after delete' );
} );
sd_case( 35, 'historical event without meta', function () {
	$post = sd_event( 935, 'Fallback actuel', 'Legacy');
	sd_assert( 'Fallback actuel' === wp_seed_events_public_event_excerpt( $post ), 'historical event fallback failed' );
} );
sd_case( 36, 'read never writes meta', function () {
	$GLOBALS['sd_writes'] = array();
	wp_seed_events_public_event_excerpt( sd_event( 936, 'Lecture', 'Legacy' ) );
	sd_assert( array() === $GLOBALS['sd_writes'], 'read wrote meta' );
} );
sd_case( 37, 'read never writes post_excerpt', function () {
	$post = sd_event( 937, 'Lecture', 'Legacy byte exact' );
	wp_seed_events_description_rest_values( 937 );
	sd_assert( 'Legacy byte exact' === $post->post_excerpt && array() === $GLOBALS['sd_writes'], 'read wrote legacy excerpt' );
} );

$event_data_source = sd_source( 'includes/public/event-data.php' );
$render_source     = sd_source( 'includes/public/rendering.php' );
$card_source       = sd_source( 'templates/event-card.php' );
$gutenberg_source  = sd_source( 'includes/integrations/gutenberg/block-bindings.php' );
$divi_source       = sd_source( 'includes/integrations/divi/class-dynamic-content-text.php' );
$patterns_source   = sd_source( 'includes/integrations/gutenberg/event-collection-patterns.php' );
$single_source     = sd_source( 'templates/event-single.php' );
$calendar_source   = sd_source( 'includes/public/calendar.php' );

sd_case( 38, 'Event Data full description', function () use ( $event_data_source ) {
	sd_assert( false !== strpos( $event_data_source, "'description'                 => \$description" ), 'Event Data description contract missing' );
} );
sd_case( 39, 'Event Data raw manual', function () use ( $event_data_source ) {
	sd_assert( false !== strpos( $event_data_source, "'short_description'           => \$short_description" ), 'raw manual field missing' );
} );
sd_case( 40, 'Event Data effective', function () use ( $event_data_source ) {
	sd_assert( false !== strpos( $event_data_source, "'short_description_effective' => \$short_description_effective" ), 'effective field missing' );
} );
sd_case( 41, 'excerpt strict alias', function () use ( $event_data_source ) {
	sd_assert( false !== strpos( $event_data_source, "'excerpt'                     => \$short_description_effective" ), 'excerpt is not the same variable' );
} );
sd_case( 42, 'Event Data preserves newlines', function () {
	$value = wp_seed_events_resolve_short_description( 'Complet', "Ligne 1\nLigne 2" );
	sd_assert( "Ligne 1\nLigne 2" === $value && false === strpos( $value, '<br' ), 'Event Data textual newline differs' );
} );
sd_case( 43, 'REST preserves newlines', function () {
	sd_event( 943, 'Complet' );
	$GLOBALS['sd_meta'][943][WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY] = "Ligne 1\nLigne 2";
	$value = wp_seed_events_description_rest_get( array( 'id' => 943 ), 'excerpt', new SD_Request( 'edit' ) );
	sd_assert( "Ligne 1\nLigne 2" === $value, 'REST newline differs' );
} );
sd_case( 44, 'card renders lines', function () use ( $card_source ) {
	sd_assert( false !== strpos( $card_source, "nl2br( esc_html( \$event['excerpt'] ) )" ), 'card multiline renderer missing' );
} );
sd_case( 45, 'shortcode renders lines', function () use ( $render_source ) {
	sd_assert( false !== strpos( $render_source, "nl2br( esc_html( \$event['excerpt'] ) )" ), 'shortcode multiline renderer missing' );
} );
sd_case( 46, 'Gutenberg renders lines', function () use ( $gutenberg_source ) {
	sd_assert( false !== strpos( $gutenberg_source, 'wp-seed-events-multiline-text' ), 'Gutenberg multiline class missing' );
} );
sd_case( 47, 'Divi frontend renders lines', function () use ( $divi_source ) {
	sd_assert( false !== strpos( $divi_source, 'wp-seed-events-multiline-text' ), 'Divi multiline wrapper missing' );
} );
sd_case( 48, 'Divi Visual Builder shares renderer', function () use ( $divi_source ) {
	sd_assert( false !== strpos( $divi_source, "if ( 'excerpt' === \$this->get_field() )" ), 'Divi excerpt branch missing' );
} );
sd_case( 49, 'collection consumes excerpt', function () use ( $patterns_source ) {
	sd_assert( false !== strpos( $patterns_source, '"field":"excerpt"' ) && false === strpos( $patterns_source, 'wp:post-excerpt' ), 'collection excerpt contract differs' );
} );
sd_case( 50, 'detail uses full content', function () use ( $single_source ) {
	sd_assert( false !== strpos( $single_source, "\$event['description']" ) && false === strpos( $single_source, "\$event['excerpt']" ), 'detail does not exclusively use full description' );
} );
sd_case( 51, 'ICS uses full content', function () use ( $calendar_source ) {
	sd_assert( false !== strpos( $calendar_source, "\$event['description']" ) && false === strpos( $calendar_source, "\$event['excerpt']" ), 'ICS description contract differs' );
} );

sd_case( 52, 'manual beats more', function () {
	sd_assert( 'Manuel' === wp_seed_events_resolve_short_description( 'Avant<!--more-->Après', 'Manuel' ), 'manual did not beat more' );
} );
sd_case( 53, 'manual beats 40', function () {
	$manual = sd_words( 50 );
	sd_assert( $manual === wp_seed_events_resolve_short_description( sd_words( 60 ), $manual ), 'manual was truncated' );
} );
sd_case( 54, 'more over 40 stays complete', function () {
	$before = sd_words( 50 );
	sd_assert( $before === wp_seed_events_resolve_short_description( $before . '<!--more-->Après' ), 'more branch was truncated' );
} );
sd_case( 55, 'automatic is 40', function () {
	sd_assert( sd_words( 40 ) . '…' === wp_seed_events_resolve_short_description( sd_words( 60 ) ), 'automatic fallback is not 40 words' );
} );
sd_case( 56, 'manual deletion reveals more', function () {
	sd_assert( 'Avant' === wp_seed_events_resolve_short_description( 'Avant<!--more-->Après', '' ), 'more not effective after manual deletion' );
} );
sd_case( 57, 'manual deletion reveals automatic', function () {
	sd_assert( sd_words( 40 ) . '…' === wp_seed_events_resolve_short_description( sd_words( 41 ), '' ), 'automatic not effective after manual deletion' );
} );
sd_case( 58, 'adding more changes effective', function () {
	$plain = wp_seed_events_resolve_short_description( sd_words( 41 ) );
	$more  = wp_seed_events_resolve_short_description( 'Éditorial<!--more-->' . sd_words( 41 ) );
	sd_assert( $plain !== $more && 'Éditorial' === $more, 'adding more had no effect' );
} );
sd_case( 59, 'removing more restores automatic', function () {
	sd_assert( sd_words( 40 ) . '…' === wp_seed_events_resolve_short_description( sd_words( 41 ) ), 'removing more did not restore automatic' );
} );

sd_case( 60, 'admin textarea present', function () {
	sd_event( 960, 'Complet' );
	ob_start();
	wp_seed_events_render_short_description_field( $GLOBALS['sd_posts'][960] );
	$html = ob_get_clean();
	sd_assert( false !== strpos( $html, 'name="wp_seed_event_short_description"' ), 'textarea missing' );
} );
sd_case( 61, 'admin help text', function () {
	ob_start();
	wp_seed_events_render_short_description_field( $GLOBALS['sd_posts'][960] );
	$html = ob_get_clean();
	sd_assert( false !== strpos( $html, 'Lire la suite' ) && false !== strpos( $html, '40 mots' ), 'help text differs' );
} );
sd_case( 62, 'admin simple save', function () {
	sd_reset_save_state();
	sd_event( 962, 'Complet', 'Legacy' );
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => 'Court' );
	wp_seed_events_save_short_description( 962 );
	sd_assert( 'Court' === get_post_meta( 962, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'simple save failed' );
} );
sd_case( 63, 'admin multiline save', function () {
	sd_reset_save_state();
	sd_event( 963, 'Complet', 'Legacy' );
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => "Un\r\nDeux" );
	wp_seed_events_save_short_description( 963 );
	sd_assert( "Un\nDeux" === get_post_meta( 963, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'multiline save differs' );
} );
sd_case( 64, 'admin clearing deletes meta', function () {
	sd_reset_save_state();
	sd_event( 964, 'Complet', 'Legacy' );
	$GLOBALS['sd_meta'][964][WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY] = 'Avant';
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => " \n " );
	wp_seed_events_save_short_description( 964 );
	sd_assert( '' === get_post_meta( 964, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'clear did not delete meta' );
} );
sd_case( 65, 'admin leaves post_content', function () {
	sd_reset_save_state();
	$post = sd_event( 965, "Complet\nexact", 'Legacy' );
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => 'Court' );
	wp_seed_events_save_short_description( 965 );
	sd_assert( "Complet\nexact" === $post->post_content, 'post_content changed' );
} );
sd_case( 66, 'admin leaves post_excerpt', function () {
	sd_reset_save_state();
	$post = sd_event( 966, 'Complet', "Legacy\r\nexact" );
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => 'Court' );
	wp_seed_events_save_short_description( 966 );
	sd_assert( "Legacy\r\nexact" === $post->post_excerpt, 'post_excerpt changed' );
} );
sd_case( 67, 'admin nonce and capability guards', function () {
	sd_reset_save_state();
	sd_event( 967, 'Complet', 'Legacy' );
	$GLOBALS['sd_nonce_valid'] = false;
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'bad', 'wp_seed_event_short_description' => 'Interdit' );
	wp_seed_events_save_short_description( 967 );
	$GLOBALS['sd_nonce_valid'] = true;
	$GLOBALS['sd_can_edit'] = false;
	wp_seed_events_save_short_description( 967 );
	sd_assert( '' === get_post_meta( 967, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'guard allowed save' );
} );
sd_case( 68, 'admin revision and autosave guards', function () {
	sd_reset_save_state();
	sd_event( 968, 'Complet', 'Legacy' );
	$GLOBALS['sd_is_revision'] = true;
	$_POST = array( 'wp_seed_events_short_description_nonce' => 'valid', 'wp_seed_event_short_description' => 'Interdit' );
	wp_seed_events_save_short_description( 968 );
	$source = sd_source( 'includes/public/descriptions.php' );
	sd_assert( '' === get_post_meta( 968, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ) && false !== strpos( $source, "defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE" ), 'revision/autosave guard differs' );
} );

wp_seed_events_register_description_rest_fields();
sd_assert( isset( $GLOBALS['sd_rest_fields']['short_description'][1]['update_callback'] ), 'manual REST field is not writable' );
sd_assert( ! isset( $GLOBALS['sd_rest_fields']['short_description_effective'][1]['update_callback'] ), 'effective REST field is writable' );
sd_assert( ! isset( $GLOBALS['sd_rest_fields']['excerpt'][1]['update_callback'] ), 'excerpt REST field is writable' );

sd_reset_save_state();
$post = sd_event( 990, 'Complet REST', 'Legacy REST exact' );
sd_assert( true === wp_seed_events_description_rest_update( "REST ligne 1\r\nREST ligne 2", array( 'id' => 990 ) ), 'REST manual update failed' );
sd_assert( "REST ligne 1\nREST ligne 2" === get_post_meta( 990, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'REST update flattened newlines' );
sd_assert( 'Legacy REST exact' === $post->post_excerpt, 'REST update changed post_excerpt' );
sd_assert( true === wp_seed_events_description_rest_update( '', array( 'id' => 990 ) ), 'REST clear failed' );
sd_assert( '' === get_post_meta( 990, WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY ), 'REST clear did not delete meta' );
$GLOBALS['sd_can_edit'] = false;
sd_assert( wp_seed_events_description_rest_update( 'Forbidden', array( 'id' => 990 ) ) instanceof WP_Error, 'REST capability guard failed' );
$GLOBALS['sd_can_edit'] = true;

sd_assert( 68 === $GLOBALS['sd_cases'], 'case count differs' );
sd_assert( false === strpos( sd_source( 'wp-seed-events.php' ), 'wp_trim_words( $content, 28' ), 'legacy 28-word fallback remains' );
sd_assert( false === strpos( sd_source( 'includes/public/event-data.php' ), 'post_excerpt' ), 'Event Data reads legacy post_excerpt' );

echo 'Short description V1 harness: 68/68 cases; ' . $GLOBALS['sd_assertions'] . ' assertions OK' . PHP_EOL;
