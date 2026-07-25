<?php
/** Standalone assertions for the public event people shortcode adapter. */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['p3a_cases'] = 0;
$GLOBALS['p3a_data_calls'] = 0;
$GLOBALS['p3a_meta_calls'] = 0;
$GLOBALS['p3a_post_id'] = 0;
$GLOBALS['p3a_post_types'] = array();
$GLOBALS['p3a_data'] = array();

function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$output = $pairs;
	foreach ( is_array( $atts ) ? $atts : array() as $name => $value ) {
		if ( array_key_exists( $name, $pairs ) ) { $output[ $name ] = $value; }
	}
	return $output;
}
function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = strip_tags( (string) $value );
	return $remove_breaks ? preg_replace( '/[[:space:]]+/', ' ', $value ) : $value;
}
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) {
	$value = trim( (string) $value );
	return preg_match( '#^https?://#i', $value ) ? esc_attr( $value ) : '';
}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function get_the_ID() { return (int) $GLOBALS['p3a_post_id']; }
function get_post_type( $post_id = 0 ) { return $GLOBALS['p3a_post_types'][ absint( $post_id ) ] ?? false; }
function get_post_meta( $post_id, $key = '', $single = false ) { $GLOBALS['p3a_meta_calls']++; return ''; }
function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['p3a_data_calls']++;
	return $GLOBALS['p3a_data'][ absint( $event_id ) ] ?? array();
}
function wp_seed_events_normalize_person_email( $value ) {
	$value = trim( (string) $value );
	return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
}
function wp_seed_events_normalize_person_phone( $value ) {
	$value = trim( (string) $value );
	$length = strlen( (string) preg_replace( '/\D+/', '', $value ) );
	return $length >= 6 && $length <= 15 ? $value : '';
}
function wp_seed_events_normalize_person_link( $value ) {
	$value = trim( (string) $value );
	return preg_match( '#^https?://#i', $value ) ? $value : '';
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';

function p3a_person( $name, $role_keys = array(), $roles = array(), $values = array() ) {
	return array_merge( array(
		'name' => $name, 'role_keys' => $role_keys, 'roles' => $roles,
		'public_email' => '', 'public_phone' => '', 'public_url' => '',
		'email' => '', 'phone' => '', 'link' => '',
	), $values );
}
function p3a_event( $id, $people ) { return array( 'id' => absint( $id ), 'people' => $people ); }
function p3a_reset() {
	$GLOBALS['p3a_data_calls'] = 0; $GLOBALS['p3a_meta_calls'] = 0;
	$GLOBALS['p3a_post_id'] = 0; $GLOBALS['p3a_post_types'] = array();
	unset( $GLOBALS['wp_seed_events_public_event_id'] );
}
function p3a_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function p3a_contains( $needle, $haystack, $message ) { p3a_assert( false !== strpos( (string) $haystack, (string) $needle ), $message ); }
function p3a_not_contains( $needle, $haystack, $message ) { p3a_assert( false === strpos( (string) $haystack, (string) $needle ), $message ); }
function p3a_case( $name, $callback ) {
	p3a_reset();
	try { $callback(); $GLOBALS['p3a_cases']++; echo '[OK] ' . $name . PHP_EOL; }
	catch ( Throwable $error ) { fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
}
function p3a_count_people( $html ) { return substr_count( (string) $html, 'wp-seed-event-people__item' ); }

$organizer = p3a_person( 'Alice Martin', array( 'organizer' ), array( 'Organisatrice' ), array(
	'public_email' => 'alice@example.test', 'public_phone' => '+33 1 23 45 67 89',
	'public_url' => 'https://example.test/alice', 'email' => 'private-alice@example.test',
	'phone' => '+33 9 99 99 99 99', 'link' => 'https://private.example.test/alice',
) );
$speaker = p3a_person( 'Benoit Durand', array( 'speaker' ), array( 'Intervenant' ) );
$register = p3a_person( 'Claire Petit', array( 'registration_contact' ), array( 'Contact inscription' ) );
$inform = p3a_person( 'David Robert', array( 'information_contact' ), array( 'Contact information' ) );
$GLOBALS['p3a_data'] = array(
	914 => p3a_event( 914, array( $organizer, $speaker, $register, $inform ) ),
	1011 => p3a_event( 1011, array( $speaker ) ), 1022 => p3a_event( 1022, array() ),
);

p3a_case( '1 no people', function () { p3a_assert( '' === wp_seed_events_event_people_shortcode( array( 'id' => 1022 ) ), 'Markup returned.' ); } );
p3a_case( '2 one person', function () { p3a_assert( 1 === p3a_count_people( wp_seed_events_event_people_shortcode( array( 'id' => 1011 ) ) ), 'Wrong count.' ); } );
p3a_case( '3 several people', function () { p3a_assert( 4 === p3a_count_people( wp_seed_events_event_people_shortcode( array( 'id' => 914 ) ) ), 'Wrong count.' ); } );
p3a_case( '4 order preserved', function () { $html = wp_seed_events_event_people_shortcode( array( 'id' => 914 ) ); p3a_assert( strpos( $html, 'Alice Martin' ) < strpos( $html, 'Benoit Durand' ), 'Order changed.' ); } );
p3a_case( '5 event context', function () { $GLOBALS['p3a_post_id'] = 1011; $GLOBALS['p3a_post_types'][1011] = 'wp_seed_event'; p3a_contains( 'Benoit', wp_seed_events_event_people_shortcode( array() ), 'Context failed.' ); } );
p3a_case( '6 ordinary page', function () { $GLOBALS['p3a_post_id'] = 998; $GLOBALS['p3a_post_types'][998] = 'page'; p3a_assert( '' === wp_seed_events_event_people_shortcode( array() ), 'Page rendered.' ); p3a_assert( 0 === $GLOBALS['p3a_data_calls'], 'Data loaded.' ); } );
p3a_case( '7 valid explicit ID', function () { $GLOBALS['wp_seed_events_public_event_id'] = 1011; p3a_contains( 'Alice', wp_seed_events_event_people_shortcode( array( 'id' => '914' ) ), 'Explicit ID lost.' ); } );
p3a_case( '8 invalid explicit ID', function () { $GLOBALS['wp_seed_events_public_event_id'] = 914; foreach ( array( '', '0', '-1', 'event', '9.14', array( 914 ) ) as $id ) { p3a_assert( '' === wp_seed_events_event_people_shortcode( array( 'id' => $id ) ), 'Fallback occurred.' ); } p3a_assert( 0 === $GLOBALS['p3a_data_calls'], 'Data loaded.' ); } );
p3a_case( '9 explicit page ID', function () { $GLOBALS['wp_seed_events_public_event_id'] = 914; p3a_assert( '' === wp_seed_events_event_people_shortcode( array( 'id' => 998 ) ), 'Page fell back.' ); p3a_assert( 1 === $GLOBALS['p3a_data_calls'], 'Wrong calls.' ); } );
p3a_case( '10 draft', function () { p3a_assert( '' === wp_seed_events_event_people_shortcode( array( 'id' => 999 ) ), 'Draft rendered.' ); } );
p3a_case( '11 public model context', function () { $GLOBALS['wp_seed_events_public_event_id'] = 914; $GLOBALS['p3a_post_id'] = 998; $GLOBALS['p3a_post_types'][998] = 'page'; p3a_contains( 'Alice', wp_seed_events_event_people_shortcode( array() ), 'Public context failed.' ); } );

foreach ( array( 'all' => 4, 'organizer' => 1, 'speaker' => 1, 'registration_contact' => 1, 'information_contact' => 1, 'invalid-role' => 4 ) as $role => $count ) {
	p3a_case( 'role ' . $role, function () use ( $role, $count ) { p3a_assert( $count === p3a_count_people( wp_seed_events_event_people_shortcode( array( 'id' => 914, 'role' => $role ) ) ), 'Role failed.' ); } );
}

p3a_case( '18 details yes', function () { $html = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'details' => 'yes' ) ); p3a_contains( '__roles', $html, 'Roles hidden.' ); p3a_contains( 'mailto:', $html, 'Contacts hidden.' ); } );
p3a_case( '19 details no', function () { $html = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'details' => 'no' ) ); p3a_not_contains( '__roles', $html, 'Roles shown.' ); p3a_not_contains( '__contacts', $html, 'Contacts shown.' ); } );
p3a_case( '20 invalid details', function () { $html = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'details' => 'maybe' ) ); p3a_contains( '__roles', $html, 'Safe yes missing.' ); p3a_contains( 'mailto:', $html, 'Safe yes missing.' ); } );
foreach ( array( 'show_roles' => '__roles', 'show_email' => 'mailto:', 'show_phone' => 'tel:', 'show_link' => 'example.test/alice' ) as $option => $marker ) {
	p3a_case( $option . ' priority', function () use ( $option, $marker ) { p3a_contains( $marker, wp_seed_events_event_people_shortcode( array( 'id' => 914, 'details' => 'no', $option => 'yes' ) ), 'Override failed.' ); } );
}

p3a_case( '25 default title', function () { p3a_contains( '>Contacts et intervenants</h2>', wp_seed_events_event_people_shortcode( array( 'id' => 914 ) ), 'Title changed.' ); } );
p3a_case( '26 custom title', function () { p3a_contains( '>Equipe</h2>', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'title' => 'Equipe' ) ), 'Title missing.' ); } );
p3a_case( '27 empty title', function () { p3a_not_contains( '__title', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'title' => '' ) ), 'Heading rendered.' ); } );
p3a_case( '28 h2 through h6', function () { foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) { p3a_contains( '<' . $level . ' class=', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'heading_level' => $level ) ), 'Heading failed.' ); } } );
p3a_case( '29 invalid heading', function () { p3a_contains( '<h2 class=', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'heading_level' => 'h1' ) ), 'Fallback failed.' ); } );
p3a_case( '30 list layout', function () { p3a_contains( 'is-layout-list', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'layout' => 'list' ) ), 'List missing.' ); } );
p3a_case( '31 grid layout', function () { p3a_contains( 'is-layout-grid', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'layout' => 'grid' ) ), 'Grid missing.' ); } );
p3a_case( '32 invalid layout', function () { p3a_contains( 'is-layout-list', wp_seed_events_event_people_shortcode( array( 'id' => 914, 'layout' => 'carousel' ) ), 'Fallback failed.' ); } );
p3a_case( '33 one Event Data call', function () { wp_seed_events_event_people_shortcode( array( 'id' => 914 ) ); p3a_assert( 1 === $GLOBALS['p3a_data_calls'], 'Call count differs.' ); } );
p3a_case( '34 shortcode equals renderer', function () {
	$options = array( 'title' => 'Equipe', 'heading_level' => 'h4', 'role' => 'organizer', 'details' => false, 'show_roles' => true, 'show_email' => false, 'show_phone' => true, 'show_link' => false, 'layout' => 'grid' );
	$atts = array( 'id' => 914, 'title' => 'Equipe', 'heading_level' => 'h4', 'role' => 'organizer', 'details' => 'no', 'show_roles' => 'yes', 'show_email' => 'no', 'show_phone' => 'yes', 'show_link' => 'no', 'layout' => 'grid' );
	p3a_assert( wp_seed_events_render_public_event_people_section( $GLOBALS['p3a_data'][914], $options ) === wp_seed_events_event_people_shortcode( $atts ), 'Outputs differ.' );
} );
p3a_case( '35 private coordinates', function () { $html = wp_seed_events_event_people_shortcode( array( 'id' => 914 ) ); p3a_contains( 'alice@example.test', $html, 'Public missing.' ); p3a_not_contains( 'private-alice', $html, 'Email leaked.' ); p3a_not_contains( '999999999', str_replace( ' ', '', $html ), 'Phone leaked.' ); p3a_not_contains( 'private.example.test', $html, 'URL leaked.' ); } );

p3a_case( 'roles list uses OR and all remains exclusive', function () {
	$html = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'roles' => 'organizer,speaker' ) );
	p3a_assert( 2 === p3a_count_people( $html ), 'Multiple shortcode roles did not use OR.' );
	$all = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'roles' => 'all,speaker' ) );
	p3a_assert( 4 === p3a_count_people( $all ), 'All shortcode role is not exclusive.' );
} );

p3a_case( 'show name and link controls are forwarded', function () {
	$html = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'roles' => 'organizer', 'show_name' => 'no', 'show_roles' => 'no', 'link_phone' => 'no', 'link_email' => 'no', 'link_url' => 'no' ) );
	p3a_contains( '__name screen-reader-text', $html, 'Hidden name is not accessible.' );
	p3a_not_contains( 'mailto:', $html, 'Email link toggle was ignored.' );
	p3a_not_contains( 'tel:', $html, 'Phone link toggle was ignored.' );
	p3a_not_contains( '__link-anchor', $html, 'URL link toggle was ignored.' );
	p3a_contains( 'alice@example.test', $html, 'Public coordinate disappeared.' );
	p3a_not_contains( 'private-alice', $html, 'Private coordinate leaked.' );
} );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$start = strpos( $source, 'function wp_seed_events_event_people_shortcode_event_id' );
$end = strpos( $source, 'function wp_seed_events_event_place_shortcode' );
$adapter = false !== $start && false !== $end ? substr( $source, $start, $end - $start ) : '';
$renderer_start = strpos( $source, 'function wp_seed_events_render_public_event_people_section' );
$renderer = false === $renderer_start ? '' : substr( $source, $renderer_start );

p3a_case( '36 one renderer call and no HTML', function () use ( $adapter ) { p3a_assert( 1 === substr_count( $adapter, 'wp_seed_events_render_public_event_people_section(' ), 'Renderer call count differs.' ); foreach ( array( '<section', '<ul', '<li', '<strong', 'ob_start', 'echo ' ) as $token ) { p3a_not_contains( $token, $adapter, 'Output logic found.' ); } } );
p3a_case( '37 no private access', function () use ( $adapter ) { foreach ( array( 'get_post_meta', 'wp_seed_events_people', '_wp_seed_event_contacts', 'publish_', 'person_key', 'do_shortcode' ) as $token ) { p3a_not_contains( $token, $adapter, 'Forbidden dependency.' ); } p3a_assert( 0 === $GLOBALS['p3a_meta_calls'], 'Meta read.' ); } );
p3a_case( '38 renderer owns HTML', function () use ( $adapter, $renderer ) { p3a_not_contains( 'wp-seed-event-people__item', $adapter, 'HTML duplicated.' ); p3a_contains( 'wp-seed-event-people__item', $renderer, 'Renderer HTML missing.' ); } );
p3a_case( '39 control fields private', function () { $GLOBALS['p3a_data'][1100] = p3a_event( 1100, array( array_merge( p3a_person( 'Safe Person' ), array( 'person_key' => 'SECRET_KEY', 'publish_email' => 'SECRET_FLAG' ) ) ) ); p3a_not_contains( 'SECRET_', wp_seed_events_event_people_shortcode( array( 'id' => 1100 ) ), 'Control data leaked.' ); } );
p3a_case( '40 deterministic', function () { $first = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'layout' => 'grid' ) ); $second = wp_seed_events_event_people_shortcode( array( 'id' => 914, 'layout' => 'grid' ) ); p3a_assert( $first === $second, 'Outputs differ.' ); } );

echo '[OK] ' . $GLOBALS['p3a_cases'] . ' people shortcode cases passed.' . PHP_EOL;