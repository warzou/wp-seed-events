<?php
/** Runtime-free assertions for public event rewrite rules. */
declare(strict_types=1);

if ( ! defined( 'WP_SEED_EVENTS_REWRITE_VERSION' ) ) {
	define( 'WP_SEED_EVENTS_REWRITE_VERSION', 'test-rewrite-version' );
}

$GLOBALS['rewrite_options'] = array(
	'wp_seed_events_permalink_prefix'               => '',
	'wp_seed_events_permalink_include_primary_type' => '1',
);
$GLOBALS['rewrite_rules'] = array();
$GLOBALS['rewrite_flushes'] = 0;
$GLOBALS['rewrite_flush_regenerates'] = false;
$GLOBALS['rewrite_page_paths'] = array();
$GLOBALS['rewrite_posts'] = array();
$GLOBALS['rewrite_primary_types'] = array();
$GLOBALS['rewrite_cases'] = 0;
$GLOBALS['rewrite_event_types'] = array(
	'non_classe'           => 'Non classe',
	'atelier'              => 'Atelier',
	'stage'                => 'Stage',
	'journee_decouverte'   => 'Journee decouverte',
	'reunion_information'  => 'Reunion information',
);
$GLOBALS['wp_post_types'] = array();
$GLOBALS['wp_taxonomies'] = array();
$GLOBALS['wp_rewrite'] = (object) array(
	'author_base'     => 'author',
	'comments_base'   => 'comments',
	'feed_base'       => 'feed',
	'pagination_base' => 'page',
	'search_base'     => 'search',
);

function get_option( $name, $default = false ) { return $GLOBALS['rewrite_options'][ $name ] ?? $default; }
function update_option( $name, $value, $autoload = null ) { $GLOBALS['rewrite_options'][ $name ] = $value; return true; }
function delete_option( $name ) { unset( $GLOBALS['rewrite_options'][ $name ] ); return true; }
function flush_rewrite_rules( $hard = true ) {
	$GLOBALS['rewrite_flushes']++;
	if ( $GLOBALS['rewrite_flush_regenerates'] ) {
		$GLOBALS['rewrite_rules'] = array();
		wp_seed_events_add_event_rewrite_rules();
	}
}
function wp_installing() { return false; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_title( $value ) { return trim( preg_replace( '/[^a-z0-9]+/', '-', strtolower( str_replace( '_', '-', (string) $value ) ) ), '-' ); }
function add_rewrite_rule( $regex, $query, $after = 'bottom' ) { $GLOBALS['rewrite_rules'][] = array( 'regex' => $regex, 'query' => $query, 'after' => $after ); }
function apply_filters( $hook, $value ) { return $value; }
function get_pages( $args = array() ) { return array_map( static function ( $path ) { return (object) array( 'post_name' => basename( $path ), 'page_uri' => $path ); }, $GLOBALS['rewrite_page_paths'] ); }
function get_page_uri( $page ) { return $page->page_uri; }
function wp_seed_events_event_type_options() { return $GLOBALS['rewrite_event_types']; }
function wp_seed_events_event_type_public_slug( $type_key ) { $type_key = sanitize_key( $type_key ); return isset( $GLOBALS['rewrite_event_types'][ $type_key ] ) ? wp_seed_events_event_type_public_slug_for_key( $type_key ) : ''; }
function wp_seed_events_native_event_type_slug( $type_key ) {
	$type_key = sanitize_key( $type_key );
	$stable = array(
		'journee_decouverte'  => 'journee-decouverte',
		'reunion_information' => 'reunion-information',
	);
	return '' === $type_key ? '' : ( $stable[ $type_key ] ?? sanitize_title( str_replace( '_', '-', $type_key ) ) );
}
class WP_Post {
	public $ID;
	public $post_type;
	public $post_name;
	public function __construct( $id, $post_name ) { $this->ID = $id; $this->post_type = 'wp_seed_event'; $this->post_name = $post_name; }
}
function get_post( $post ) { return $post instanceof WP_Post ? $post : ( $GLOBALS['rewrite_posts'][ (int) $post ] ?? null ); }
function wp_seed_events_primary_type_for_event( $post_id ) { return $GLOBALS['rewrite_primary_types'][ (int) $post_id ] ?? ''; }
function home_url( $path = '' ) { return 'https://example.test/' . ltrim( $path, '/' ); }
function user_trailingslashit( $path ) { return rtrim( $path, '/' ) . '/'; }

$source = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );
$start = strpos( $source, 'function wp_seed_events_default_permalink_prefix' );
$end = strpos( $source, 'function wp_seed_events_normalize_url_path', $start );
if ( false === $source || false === $start || false === $end ) {
	throw new RuntimeException( 'Unable to load rewrite functions.' );
}
eval( substr( $source, $start, $end - $start ) );

function rewrite_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function rewrite_case( $label, $callback ) {
	$GLOBALS['rewrite_cases']++;
	$GLOBALS['rewrite_rules'] = array();
	$GLOBALS['rewrite_flush_regenerates'] = false;
	$GLOBALS['rewrite_page_paths'] = array();
	$GLOBALS['rewrite_posts'] = array();
	$GLOBALS['rewrite_primary_types'] = array();
	$GLOBALS['rewrite_options'] = array(
		'wp_seed_events_permalink_prefix'               => '',
		'wp_seed_events_permalink_include_primary_type' => '1',
	);
	$GLOBALS['rewrite_event_types'] = array(
		'non_classe'           => 'Non classe',
		'atelier'              => 'Atelier',
		'stage'                => 'Stage',
		'journee_decouverte'   => 'Journee decouverte',
		'reunion_information'  => 'Reunion information',
	);
	$GLOBALS['wp_post_types'] = array(
		'et_body_layout'   => (object) array( 'rewrite' => array( 'slug' => 'et_body_layout' ) ),
		'et_header_layout' => (object) array( 'rewrite' => array( 'slug' => 'et_header_layout' ) ),
		'et_footer_layout' => (object) array( 'rewrite' => array( 'slug' => 'et_footer_layout' ) ),
	);
	$GLOBALS['wp_taxonomies'] = array(
		'category' => (object) array( 'rewrite' => array( 'slug' => 'category' ) ),
	);
	$callback();
	echo '[OK] ' . $GLOBALS['rewrite_cases'] . ' ' . $label . PHP_EOL;
}
function rewrite_query_for_path( $path ) {
	foreach ( $GLOBALS['rewrite_rules'] as $rule ) {
		if ( preg_match( '#^' . $rule['regex'] . '#', $path, $matches ) ) {
			$query = $rule['query'];
			foreach ( $matches as $index => $match ) {
				$query = str_replace( '$matches[' . $index . ']', $match, $query );
			}
			return $query;
		}
	}
	return null;
}
function rewrite_regexes() { return array_map( static function ( $rule ) { return $rule['regex']; }, $GLOBALS['rewrite_rules'] ); }
function rewrite_validate_optional_root_page( $slug ) {
	if ( ! in_array( $slug, $GLOBALS['rewrite_page_paths'], true ) ) {
		return 'SKIP';
	}

	return 'index.php?pagename=' . $slug === rewrite_query_for_path( $slug . '/' ) ? 'PASS' : 'FAIL';
}
function rewrite_assert_canonical_type_rules() {
	$regexes = rewrite_regexes();
	rewrite_assert( ! in_array( '^([^/]+)/([^/]+)/?$', $regexes, true ), 'Generic two-segment rule still exists.' );
	rewrite_assert( in_array( '^atelier/([^/]+)/?$', $regexes, true ), 'Atelier rule missing.' );
	rewrite_assert( in_array( '^stage/([^/]+)/?$', $regexes, true ), 'Stage rule missing.' );
	rewrite_assert( in_array( '^journee\-decouverte/([^/]+)/?$', $regexes, true ), 'Stable journee rule missing.' );
	rewrite_assert( in_array( '^reunion\-information/([^/]+)/?$', $regexes, true ), 'Stable reunion rule missing.' );
	rewrite_assert( 'index.php?post_type=wp_seed_event&name=danse-libre&wp_seed_event_primary_type=journee_decouverte' === rewrite_query_for_path( 'journee-decouverte/danse-libre/' ), 'Stable discovery-day URL was not resolved.' );
	rewrite_assert( 'index.php?post_type=wp_seed_event&name=portes-ouvertes&wp_seed_event_primary_type=reunion_information' === rewrite_query_for_path( 'reunion-information/portes-ouvertes/' ), 'Stable information-meeting URL was not resolved.' );
}

rewrite_case( 'canonical rules do not require homonymous root pages', function () {
	wp_seed_events_add_event_rewrite_rules();
	rewrite_assert_canonical_type_rules();
	rewrite_assert( 'SKIP' === rewrite_validate_optional_root_page( 'journee-decouverte' ), 'Missing discovery-day root page was not skipped.' );
	rewrite_assert( 'SKIP' === rewrite_validate_optional_root_page( 'reunion-information' ), 'Missing information-meeting root page was not skipped.' );
} );

rewrite_case( 'homonymous root pages remain valid without suppressing event rules', function () {
	$GLOBALS['rewrite_page_paths'] = array( 'journee-decouverte', 'reunion-information' );
	add_rewrite_rule( '^journee-decouverte/?$', 'index.php?pagename=journee-decouverte', 'top' );
	add_rewrite_rule( '^reunion-information/?$', 'index.php?pagename=reunion-information', 'top' );
	wp_seed_events_add_event_rewrite_rules();
	rewrite_assert_canonical_type_rules();
	rewrite_assert( 'PASS' === rewrite_validate_optional_root_page( 'journee-decouverte' ), 'Existing discovery-day root page was not preserved.' );
	rewrite_assert( 'PASS' === rewrite_validate_optional_root_page( 'reunion-information' ), 'Existing information-meeting root page was not preserved.' );
} );
rewrite_case( 'empty prefix does not intercept Divi or native roots', function () {
	$GLOBALS['rewrite_page_paths'] = array( 'parent-page/child-page' );
	add_rewrite_rule( '^et_body_layout/([^/]+)/?$', 'index.php?post_type=et_body_layout&name=$matches[1]', 'top' );
	add_rewrite_rule( '^et_header_layout/([^/]+)/?$', 'index.php?post_type=et_header_layout&name=$matches[1]', 'top' );
	add_rewrite_rule( '^et_footer_layout/([^/]+)/?$', 'index.php?post_type=et_footer_layout&name=$matches[1]', 'top' );
	add_rewrite_rule( '^wp-sitemap\.xml$', 'index.php?sitemap=index', 'top' );
	wp_seed_events_add_event_rewrite_rules();
	rewrite_assert( 'index.php?post_type=et_body_layout&name=theme-builder-layout-3' === rewrite_query_for_path( 'et_body_layout/theme-builder-layout-3/' ), 'Divi body layout was intercepted.' );
	rewrite_assert( 'index.php?post_type=et_header_layout&name=theme-builder-layout' === rewrite_query_for_path( 'et_header_layout/theme-builder-layout/' ), 'Divi header layout was intercepted.' );
	rewrite_assert( 'index.php?post_type=et_footer_layout&name=theme-builder-layout' === rewrite_query_for_path( 'et_footer_layout/theme-builder-layout/' ), 'Divi footer layout was intercepted.' );
	rewrite_assert( null === rewrite_query_for_path( 'wp-json/wp/v2/posts/' ), 'REST route was intercepted.' );
	rewrite_assert( null === rewrite_query_for_path( 'feed/rss2/' ), 'Feed route was intercepted.' );
	rewrite_assert( 'index.php?sitemap=index' === rewrite_query_for_path( 'wp-sitemap.xml' ), 'Sitemap route was intercepted.' );
	rewrite_assert( null === rewrite_query_for_path( 'parent-page/child-page/' ), 'Hierarchical page was intercepted.' );
} );

rewrite_case( 'conflicting type roots are skipped and reported', function () {
	$GLOBALS['rewrite_page_paths'] = array( 'atelier/child-page' );
	$GLOBALS['rewrite_event_types']['wp_json'] = 'WP JSON';
	add_rewrite_rule( '^atelier/child-page/?$', 'index.php?pagename=atelier/child-page', 'top' );
	wp_seed_events_add_event_rewrite_rules();
	$regexes = rewrite_regexes();
	rewrite_assert( ! in_array( '^atelier/([^/]+)/?$', $regexes, true ), 'Page root conflict was still registered.' );
	rewrite_assert( ! in_array( '^wp\-json/([^/]+)/?$', $regexes, true ), 'Reserved REST root conflict was still registered.' );
	rewrite_assert( in_array( '^journee\-decouverte/([^/]+)/?$', $regexes, true ), 'Unrelated discovery-day rule was suppressed.' );
	rewrite_assert( in_array( '^reunion\-information/([^/]+)/?$', $regexes, true ), 'Unrelated information-meeting rule was suppressed.' );
	rewrite_assert( 'index.php?pagename=atelier/child-page' === rewrite_query_for_path( 'atelier/child-page/' ), 'Competing child page was not preserved.' );
	$conflicts = wp_seed_events_event_type_rewrite_slug_conflicts();
	rewrite_assert( 'atelier' === ( $conflicts['atelier'] ?? '' ), 'Page root conflict was not reported.' );
	rewrite_assert( 'wp_json' === ( $conflicts['wp-json'] ?? '' ), 'Reserved root conflict was not reported.' );
	rewrite_assert( wp_seed_events_event_type_key_has_root_conflict( 'author' ), 'Future author type was not rejected.' );
} );

rewrite_case( 'wp cli flush context regenerates all canonical type rules', function () {
	$GLOBALS['rewrite_page_paths'] = array( 'journee-decouverte', 'reunion-information' );
	$GLOBALS['rewrite_options']['wp_seed_events_rewrite_version'] = 'old';
	$GLOBALS['rewrite_flush_regenerates'] = true;
	wp_seed_events_maybe_flush_rewrite_rules();
	$regexes = rewrite_regexes();
	rewrite_assert( ! in_array( '^([^/]+)/([^/]+)/?$', $regexes, true ), 'WP-CLI context restored the global rule.' );
	rewrite_assert( in_array( '^journee\-decouverte/([^/]+)/?$', $regexes, true ), 'WP-CLI context lost the discovery-day rule.' );
	rewrite_assert( in_array( '^reunion\-information/([^/]+)/?$', $regexes, true ), 'WP-CLI context lost the information-meeting rule.' );
	rewrite_assert( null === rewrite_query_for_path( 'et_body_layout/theme-builder-layout-3/' ), 'WP-CLI context intercepted Divi.' );
} );

rewrite_case( '29 canonical event permalinks remain unchanged', function () {
	$type_keys = array( 'atelier', 'stage', 'journee_decouverte', 'reunion_information' );
	$expected  = array();
	$actual    = array();

	for ( $index = 1; $index <= 29; $index++ ) {
		$type_key = $type_keys[ ( $index - 1 ) % count( $type_keys ) ];
		$slug     = sprintf( 'event-%02d', $index );
		$post     = new WP_Post( $index, $slug );
		$GLOBALS['rewrite_posts'][ $index ] = $post;
		$GLOBALS['rewrite_primary_types'][ $index ] = $type_key;
		$expected[] = 'https://example.test/' . wp_seed_events_native_event_type_slug( $type_key ) . '/' . $slug . '/';
		$actual[] = wp_seed_events_event_post_type_link( '', $post, false, false );
	}

	rewrite_assert( $expected === $actual, 'One or more of the 29 event permalinks changed.' );
} );

rewrite_case( 'non-empty prefix remains unchanged', function () {
	$GLOBALS['rewrite_options']['wp_seed_events_permalink_prefix'] = 'evenements';
	wp_seed_events_add_event_rewrite_rules();
	rewrite_assert( array( '^evenements/([^/]+)/([^/]+)/?$', '^evenements/([^/]+)/?$' ) === rewrite_regexes(), 'Prefixed rewrite contract changed.' );
} );

rewrite_case( 'rewrite version flushes once', function () {
	$GLOBALS['rewrite_options']['wp_seed_events_rewrite_version'] = 'old';
	$GLOBALS['rewrite_flushes'] = 0;
	wp_seed_events_maybe_flush_rewrite_rules();
	wp_seed_events_maybe_flush_rewrite_rules();
	rewrite_assert( 1 === $GLOBALS['rewrite_flushes'], 'Rewrite version did not flush exactly once.' );
	rewrite_assert( WP_SEED_EVENTS_REWRITE_VERSION === $GLOBALS['rewrite_options']['wp_seed_events_rewrite_version'], 'Rewrite version option was not updated.' );
} );

echo 'Event rewrite rules harness: ' . $GLOBALS['rewrite_cases'] . '/' . $GLOBALS['rewrite_cases'] . ' OK' . PHP_EOL;