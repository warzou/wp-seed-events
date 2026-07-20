<?php
/** Standalone assertions for the native event template people delegation. */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['p3b_cases'] = 0;
$GLOBALS['p3b_event_data_calls'] = 0;
$GLOBALS['p3b_meta_calls'] = 0;

function absint( $value ) { return abs( (int) $value ); }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = strip_tags( (string) $value );
	return $remove_breaks ? preg_replace( '/[[:space:]]+/', ' ', $value ) : $value;
}
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { $value = trim( (string) $value ); return preg_match( '#^https?://#i', $value ) ? esc_attr( $value ) : ''; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function wp_kses_post( $value ) { return (string) $value; }
function wp_get_attachment_image( $id, $size = 'thumbnail', $icon = false, $attr = array() ) { return ''; }
function wp_get_attachment_url( $id ) { return false; }
function apply_filters( $hook, $value ) { return (string) $value; }
function get_post_meta( $post_id, $key = '', $single = false ) { $GLOBALS['p3b_meta_calls']++; return ''; }
function wp_seed_events_get_event_data( $event_id ) { $GLOBALS['p3b_event_data_calls']++; return array(); }
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

function p3b_person( $name, $role_keys = array(), $roles = array(), $values = array() ) {
	return array_merge( array(
		'name' => $name, 'role_keys' => $role_keys, 'roles' => $roles,
		'public_email' => '', 'public_phone' => '', 'public_url' => '',
		'email' => '', 'phone' => '', 'link' => '',
	), $values );
}
function p3b_event( $people_marker = null ) {
	$event = array(
		'id' => 914, 'title' => 'Test Event', 'types' => array(), 'place' => array(),
		'description' => '', 'primary_image_id' => 0, 'flyer_pdf_id' => 0,
	);
	if ( null !== $people_marker ) { $event['people'] = $people_marker; }
	return $event;
}
function p3b_render( $event ) {
	ob_start();
	include dirname( __DIR__ ) . '/templates/event-single.php';
	return trim( (string) ob_get_clean() );
}
function p3b_assert( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
function p3b_contains( $needle, $haystack, $message ) { p3b_assert( false !== strpos( (string) $haystack, (string) $needle ), $message ); }
function p3b_not_contains( $needle, $haystack, $message ) { p3b_assert( false === strpos( (string) $haystack, (string) $needle ), $message ); }
function p3b_case( $name, $callback ) {
	$GLOBALS['p3b_event_data_calls'] = 0; $GLOBALS['p3b_meta_calls'] = 0;
	try { $callback(); $GLOBALS['p3b_cases']++; echo '[OK] ' . $name . PHP_EOL; }
	catch ( Throwable $error ) { fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL ); exit( 1 ); }
}
function p3b_people_count( $html ) { return substr_count( (string) $html, 'wp-seed-event-people__item' ); }

$organizer = p3b_person( 'Alice Martin', array( 'organizer' ), array( 'Organisatrice' ) );
$speaker = p3b_person( 'Benoit Durand', array( 'speaker' ), array( 'Intervenant' ) );
$complete = p3b_person( 'Claire Petit', array( 'information_contact' ), array( 'Contact information' ), array(
	'public_email' => 'public@example.test', 'public_phone' => '+33 1 23 45 67 89',
	'public_url' => 'https://example.test/contact', 'email' => 'private@example.test',
	'phone' => '+33 9 99 99 99 99', 'link' => 'https://private.example.test/contact',
) );

p3b_case( '1 invalid Event Data people value', function () { p3b_not_contains( 'wp-seed-event-people-section', p3b_render( p3b_event( 'invalid' ) ), 'Invalid people rendered.' ); } );
p3b_case( '2 people absent', function () { p3b_not_contains( 'wp-seed-event-people-section', p3b_render( p3b_event() ), 'Absent people rendered.' ); } );
p3b_case( '3 people empty', function () { p3b_not_contains( 'wp-seed-event-people-section', p3b_render( p3b_event( array() ) ), 'Empty people rendered.' ); } );
p3b_case( '4 one person', function () use ( $organizer ) { p3b_assert( 1 === p3b_people_count( p3b_render( p3b_event( array( $organizer ) ) ) ), 'Wrong count.' ); } );
p3b_case( '5 several people', function () use ( $organizer, $speaker ) { p3b_assert( 2 === p3b_people_count( p3b_render( p3b_event( array( $organizer, $speaker ) ) ) ), 'Wrong count.' ); } );
p3b_case( '6 order preserved', function () use ( $organizer, $speaker ) { $html = p3b_render( p3b_event( array( $speaker, $organizer ) ) ); p3b_assert( strpos( $html, 'Benoit' ) < strpos( $html, 'Alice' ), 'Order changed.' ); } );
p3b_case( '7 roles visible', function () use ( $organizer ) { p3b_contains( 'Organisatrice', p3b_render( p3b_event( array( $organizer ) ) ), 'Role missing.' ); } );
p3b_case( '8 public coordinates visible', function () use ( $complete ) { $html = p3b_render( p3b_event( array( $complete ) ) ); foreach ( array( 'mailto:public@example.test', 'tel:+33123456789', 'https://example.test/contact' ) as $value ) { p3b_contains( $value, $html, 'Public coordinate missing.' ); } } );
p3b_case( '9 private coordinates absent', function () use ( $complete ) { $html = p3b_render( p3b_event( array( $complete ) ) ); foreach ( array( 'private@example.test', '999999999', 'private.example.test' ) as $value ) { p3b_not_contains( $value, str_replace( ' ', '', $html ), 'Private coordinate leaked.' ); } } );
p3b_case( '10 permissions independent', function () { $person = p3b_person( 'Phone Only', array(), array(), array( 'public_phone' => '+33 6 11 22 33 44', 'email' => 'private@example.test', 'link' => 'https://private.example.test' ) ); $html = p3b_render( p3b_event( array( $person ) ) ); p3b_contains( 'tel:+33611223344', $html, 'Phone missing.' ); p3b_not_contains( 'mailto:', $html, 'Email leaked.' ); p3b_not_contains( 'private.example.test', $html, 'Link leaked.' ); } );
p3b_case( '11 nameless person omitted', function () { p3b_assert( 0 === p3b_people_count( p3b_render( p3b_event( array( p3b_person( '' ) ) ) ) ), 'Nameless person rendered.' ); } );
p3b_case( '12 historical title', function () use ( $organizer ) { p3b_contains( '>Contacts et intervenants</h2>', p3b_render( p3b_event( array( $organizer ) ) ), 'Title changed.' ); } );
p3b_case( '13 heading h2', function () use ( $organizer ) { p3b_contains( '<h2 class="wp-seed-event-people__title">', p3b_render( p3b_event( array( $organizer ) ) ), 'Heading changed.' ); } );
p3b_case( '14 list layout', function () use ( $organizer ) { p3b_contains( 'is-layout-list', p3b_render( p3b_event( array( $organizer ) ) ), 'Layout changed.' ); } );

$template_source = file_get_contents( dirname( __DIR__ ) . '/templates/event-single.php' );
$people_start = strpos( $template_source, '$people_html = wp_seed_events_render_public_event_people_section' );
$people_end = strpos( $template_source, "if ( ! empty( \$event['description'] ) )" );
$people_source = false !== $people_start && false !== $people_end ? substr( $template_source, $people_start, $people_end - $people_start ) : '';

p3b_case( '15 one renderer call', function () use ( $template_source ) { p3b_assert( 1 === substr_count( $template_source, 'wp_seed_events_render_public_event_people_section(' ), 'Renderer call count differs.' ); } );
p3b_case( '16 no Event Data reload', function () use ( $organizer, $people_source ) { p3b_render( p3b_event( array( $organizer ) ) ); p3b_assert( 0 === $GLOBALS['p3b_event_data_calls'], 'Event Data reloaded.' ); foreach ( array( 'wp_seed_events_public_event_data', 'wp_seed_events_get_event_data' ) as $token ) { p3b_not_contains( $token, $people_source, 'Event Data lookup found.' ); } } );
p3b_case( '17 no direct meta', function () use ( $organizer, $people_source ) { p3b_render( p3b_event( array( $organizer ) ) ); p3b_assert( 0 === $GLOBALS['p3b_meta_calls'], 'Meta read.' ); p3b_not_contains( 'get_post_meta', $people_source, 'Meta call found.' ); } );
p3b_case( '18 no duplicated people HTML', function () use ( $people_source ) { foreach ( array( 'foreach', "['name']", "['roles']", "['phone']", "['email']", "['link']", '<section', '<ul', '<li', '<strong' ) as $token ) { p3b_not_contains( $token, $people_source, 'Duplicated people logic found: ' . $token ); } } );
p3b_case( '19 no person key', function () use ( $people_source ) { p3b_not_contains( 'person_key', $people_source, 'person_key found.' ); } );
p3b_case( '20 no publication flags', function () use ( $people_source ) { p3b_not_contains( 'publish_', $people_source, 'Publication flag found.' ); } );
p3b_case( '21 no empty wrapper', function () { $html = p3b_render( p3b_event( array() ) ); p3b_not_contains( 'wp-seed-event-people-section', $html, 'Empty section rendered.' ); p3b_not_contains( 'Contacts et intervenants', $html, 'Empty title rendered.' ); } );
p3b_case( '22 template HTML equals renderer HTML', function () use ( $organizer, $speaker ) { $event = p3b_event( array( $organizer, $speaker ) ); $expected = wp_seed_events_render_public_event_people_section( $event, array( 'title' => 'Contacts et intervenants' ) ); $html = p3b_render( $event ); p3b_assert( 1 === substr_count( $html, $expected ), 'Renderer HTML altered or duplicated.' ); } );
p3b_case( '23 deterministic', function () use ( $complete ) { $event = p3b_event( array( $complete ) ); p3b_assert( p3b_render( $event ) === p3b_render( $event ), 'Output differs.' ); } );
p3b_case( '24 real-shape fixture without public coordinates', function () use ( $organizer, $speaker ) { $html = p3b_render( p3b_event( array( $organizer, $speaker ) ) ); p3b_contains( 'Alice Martin', $html, 'Name missing.' ); p3b_contains( 'Organisatrice', $html, 'Role missing.' ); p3b_not_contains( 'wp-seed-event-people__contacts', $html, 'Empty contacts container rendered.' ); } );
p3b_case( '25 isolated public phone', function () { $html = p3b_render( p3b_event( array( p3b_person( 'Phone', array(), array(), array( 'public_phone' => '+33 6 12 34 56 78' ) ) ) ) ); p3b_contains( 'tel:+33612345678', $html, 'Phone missing.' ); p3b_not_contains( 'mailto:', $html, 'Email appeared.' ); } );
p3b_case( '26 isolated public email', function () { $html = p3b_render( p3b_event( array( p3b_person( 'Email', array(), array(), array( 'public_email' => 'email@example.test' ) ) ) ) ); p3b_contains( 'mailto:email@example.test', $html, 'Email missing.' ); p3b_not_contains( 'tel:', $html, 'Phone appeared.' ); } );
p3b_case( '27 isolated public link', function () { $html = p3b_render( p3b_event( array( p3b_person( 'Link', array(), array(), array( 'public_url' => 'https://example.test/person' ) ) ) ) ); p3b_contains( 'https://example.test/person', $html, 'Link missing.' ); p3b_not_contains( 'mailto:', $html, 'Email appeared.' ); } );

echo '[OK] ' . $GLOBALS['p3b_cases'] . ' people template cases passed.' . PHP_EOL;
