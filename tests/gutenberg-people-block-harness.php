<?php
/**
 * Standalone assertions for the Gutenberg event people block.
 *
 * Run with: php tests/gutenberg-people-block-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['p5_cases']       = 0;
$GLOBALS['p5_data_calls']  = 0;
$GLOBALS['p5_current_id']  = 0;
$GLOBALS['p5_registered']  = array();
$GLOBALS['p5_post_types']  = array( 10 => 'wp_seed_event', 11 => 'wp_seed_event', 12 => 'wp_seed_event', 13 => 'wp_seed_event', 20 => 'page' );
$GLOBALS['p5_post_status'] = array( 10 => 'publish', 11 => 'publish', 12 => 'publish', 13 => 'publish', 20 => 'publish' );

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = strip_tags( (string) $value );

	return $remove_breaks ? preg_replace( '/[[:space:]]+/', ' ', $value ) : $value;
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_attr( $value ) {
	return esc_html( $value );
}

function esc_url( $value ) {
	$value  = trim( (string) $value );
	$parts  = parse_url( $value );
	$scheme = is_array( $parts ) ? strtolower( (string) ( $parts['scheme'] ?? '' ) ) : '';
	$host   = is_array( $parts ) ? (string) ( $parts['host'] ?? '' ) : '';

	return in_array( $scheme, array( 'http', 'https' ), true ) && '' !== $host ? esc_attr( $value ) : '';
}

function wp_seed_events_normalize_person_email( $value ) {
	$value = trim( (string) $value );

	return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
}

function wp_seed_events_normalize_person_phone( $value ) {
	$value = trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );
	$digits = preg_replace( '/\D+/', '', $value );

	return preg_match( '/^\+?[0-9\s().\/-]+$/u', $value ) && strlen( $digits ) >= 6 && strlen( $digits ) <= 15 ? $value : '';
}

function wp_seed_events_normalize_person_link( $value ) {
	$value  = trim( (string) $value );
	$parts  = parse_url( $value );
	$scheme = is_array( $parts ) ? strtolower( (string) ( $parts['scheme'] ?? '' ) ) : '';
	$host   = is_array( $parts ) ? (string) ( $parts['host'] ?? '' ) : '';

	return in_array( $scheme, array( 'http', 'https' ), true ) && '' !== $host ? $value : '';
}

function get_post_type( $post_id ) {
	return $GLOBALS['p5_post_types'][ (int) $post_id ] ?? false;
}

function get_post_status( $post_id ) {
	return $GLOBALS['p5_post_status'][ (int) $post_id ] ?? false;
}

function get_the_ID() {
	return (int) $GLOBALS['p5_current_id'];
}

function get_block_wrapper_attributes( $attributes = array() ) {
	return 'class="wp-block-wp-seed-events-event-people-block ' . esc_attr( $attributes['class'] ?? '' ) . '"';
}

function add_action( $hook, $callback, $priority = 10 ) {
	return true;
}

function register_block_type_from_metadata( $path, $args = array() ) {
	$GLOBALS['p5_registered'] = array( 'path' => $path, 'args' => $args );

	return true;
}

function p5_person( $name, $role_key, $role, $coordinates = array() ) {
	return array_merge(
		array(
			'name'         => $name,
			'role_keys'    => array( $role_key ),
			'roles'        => array( $role ),
			'public_email' => '',
			'public_phone' => '',
			'public_url'   => '',
			'email'        => '',
			'phone'        => '',
			'link'         => '',
		),
		$coordinates
	);
}

$GLOBALS['p5_events'] = array(
	10 => array(
		'id'     => 10,
		'people' => array(
			p5_person( 'Alice', 'organizer', 'Organisatrice', array( 'public_email' => 'alice@example.test' ) ),
			p5_person( 'Benoit', 'speaker', 'Intervenant', array( 'public_phone' => '+32 470 11 22 33' ) ),
			p5_person( 'Claire', 'registration_contact', 'Contact inscription', array( 'public_url' => 'https://example.test/claire' ) ),
		),
	),
	11 => array(
		'id'     => 11,
		'people' => array( p5_person( 'David', 'information_contact', 'Contact information' ) ),
	),
	12 => array( 'id' => 12, 'people' => array() ),
	13 => array(
		'id' => 13,
		'people' => array( p5_person( 'SMS', 'contact', 'Contact', array( 'public_phone' => '+32 470 11 22 33', 'phone_action' => 'sms' ) ) ),
	),
);

function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['p5_data_calls']++;

	return $GLOBALS['p5_events'][ (int) $event_id ] ?? array();
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-people-block.php';

function p5_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function p5_contains( $needle, $haystack, $message ) {
	p5_assert( false !== strpos( (string) $haystack, (string) $needle ), $message );
}

function p5_not_contains( $needle, $haystack, $message ) {
	p5_assert( false === strpos( (string) $haystack, (string) $needle ), $message );
}

function p5_case( $name, $callback ) {
	try {
		$callback();
		$GLOBALS['p5_cases']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

function p5_block( $context ) {
	return (object) array( 'context' => $context );
}

function p5_render( $context = array(), $attributes = array() ) {
	return wp_seed_events_render_gutenberg_event_people_block( $attributes, '', p5_block( $context ) );
}

$metadata_path = dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-people-block/build/block.json';
$metadata      = json_decode( file_get_contents( $metadata_path ), true );
$source        = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-people-block.php' );

p5_case( '1 block registered', function () {
	wp_seed_events_register_event_people_block();
	p5_assert( 'wp_seed_events_render_gutenberg_event_people_block' === ( $GLOBALS['p5_registered']['args']['render_callback'] ?? '' ), 'Render callback not registered.' );
} );
p5_case( '2 canonical identifier', fn() => p5_assert( 'wp-seed-events/event-people-block' === $GLOBALS['metadata']['name'], 'Wrong block ID.' ) );
p5_case( '3 French title', fn() => p5_assert( 'WP Seed — Personnes de l’événement' === $GLOBALS['metadata']['title'], 'Wrong title.' ) );
p5_case( '4 category', fn() => p5_assert( 'widgets' === $GLOBALS['metadata']['category'], 'Wrong category.' ) );
p5_case( '5 server render callback', fn() => p5_contains( 'wp_seed_events_render_gutenberg_event_people_block', $GLOBALS['source'], 'Server callback missing.' ) );
p5_case( '6 current event context', fn() => p5_contains( 'Alice', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'Current event did not render.' ) );
p5_case( '7 explicit valid context', fn() => p5_contains( 'David', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ) ), 'Explicit event did not render.' ) );
p5_case( '8 invalid explicit ID', fn() => p5_assert( '' === p5_render( array( 'postId' => 999, 'postType' => 'wp_seed_event' ) ), 'Invalid ID fell back.' ) );
p5_case( '9 incompatible post type', fn() => p5_assert( '' === p5_render( array( 'postId' => 20, 'postType' => 'page' ) ), 'Page context fell back.' ) );
p5_case( '10 ordinary page is empty', fn() => p5_assert( '' === p5_render( array( 'postId' => 20 ) ), 'Ordinary page rendered.' ) );
p5_case( '11 Query Loop context is local', function () {
	p5_contains( 'Alice', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event', 'queryId' => 7 ) ), 'First loop event missing.' );
	p5_contains( 'David', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event', 'queryId' => 7 ) ), 'Second loop event missing.' );
} );
p5_case( '12 no people is empty', fn() => p5_assert( '' === p5_render( array( 'postId' => 12, 'postType' => 'wp_seed_event' ) ), 'Empty event produced wrapper.' ) );
p5_case( '13 one person', fn() => p5_contains( 'David', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ) ), 'One person missing.' ) );
p5_case( '14 multiple people', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) );
	p5_assert( 3 === substr_count( $html, 'wp-seed-event-people__item' ), 'Wrong people count.' );
} );
p5_case( '15 order preserved', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) );
	p5_assert( strpos( $html, 'Alice' ) < strpos( $html, 'Benoit' ) && strpos( $html, 'Benoit' ) < strpos( $html, 'Claire' ), 'Order changed.' );
} );
p5_case( '16 role all', fn() => p5_contains( 'Claire', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'role' => 'all' ) ), 'All role failed.' ) );
p5_case( '17 organizer filter', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'role' => 'organizer' ) );
	p5_contains( 'Alice', $html, 'Organizer missing.' ); p5_not_contains( 'Benoit', $html, 'Speaker leaked.' );
} );
p5_case( '18 speaker filter', fn() => p5_contains( 'Benoit', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'role' => 'speaker' ) ), 'Speaker missing.' ) );
p5_case( '19 registration filter', fn() => p5_contains( 'Claire', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'role' => 'registration_contact' ) ), 'Registration contact missing.' ) );
p5_case( '20 information filter', fn() => p5_contains( 'David', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'role' => 'information_contact' ) ), 'Information contact missing.' ) );
p5_case( '21 invalid role falls back to all', fn() => p5_contains( 'Claire', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'role' => 'private_role' ) ), 'Invalid role did not fall back.' ) );
p5_case( '22 default title', fn() => p5_contains( 'Contacts et intervenants', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ) ), 'Default title missing.' ) );
p5_case( '23 custom title', fn() => p5_contains( 'Equipe', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'title' => 'Equipe' ) ), 'Custom title missing.' ) );
p5_case( '24 empty title', fn() => p5_not_contains( '<h2', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'title' => '' ) ), 'Empty title rendered.' ) );
p5_case( '25 headings h2 to h6', function () {
	foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $heading ) {
		p5_contains( '<' . $heading, p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'heading_level' => $heading ) ), 'Heading missing.' );
	}
} );
p5_case( '26 invalid heading', fn() => p5_contains( '<h2', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'heading_level' => 'h1' ) ), 'Invalid heading did not normalize.' ) );
p5_case( '27 roles shown', fn() => p5_contains( 'Organisatrice', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'Role missing.' ) );
p5_case( '28 roles hidden', fn() => p5_not_contains( 'wp-seed-event-people__roles', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'show_roles' => false ) ), 'Roles not hidden.' ) );
p5_case( '29 public email', fn() => p5_contains( 'alice@example.test', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'Public email missing.' ) );
p5_case( '30 email hidden', fn() => p5_not_contains( 'alice@example.test', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'show_email' => false ) ), 'Email not hidden.' ) );
p5_case( '31 public phone', fn() => p5_contains( '+32 470 11 22 33', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'Public phone missing.' ) );
p5_case( '32 phone hidden', fn() => p5_not_contains( '+32 470 11 22 33', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'show_phone' => false ) ), 'Phone not hidden.' ) );
p5_case( '33 public link', fn() => p5_contains( 'https://example.test/claire', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'Public URL missing.' ) );
p5_case( '34 link hidden', fn() => p5_not_contains( 'https://example.test/claire', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'show_link' => false ) ), 'URL not hidden.' ) );
p5_case( '35 show options are independent', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'show_email' => false, 'show_phone' => true, 'show_link' => false ) );
	p5_not_contains( 'alice@example.test', $html, 'Email leaked.' ); p5_contains( '+32 470', $html, 'Phone hidden unexpectedly.' ); p5_not_contains( 'example.test/claire', $html, 'Link leaked.' );
} );
p5_case( '36 list layout', fn() => p5_contains( 'is-layout-list', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'layout' => 'list' ) ), 'List class missing.' ) );
p5_case( '37 grid layout', fn() => p5_contains( 'is-layout-grid', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'layout' => 'grid' ) ), 'Grid class missing.' ) );
p5_case( '38 invalid layout', fn() => p5_contains( 'is-layout-list', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ), array( 'layout' => 'tiles' ) ), 'Invalid layout did not normalize.' ) );
p5_case( '39 one Event Data call', function () {
	$GLOBALS['p5_data_calls'] = 0; p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ); p5_assert( 1 === $GLOBALS['p5_data_calls'], 'Event Data called more than once.' );
} );
p5_case( '40 one renderer call in adapter', fn() => p5_assert( 1 === preg_match_all( '/wp_seed_events_render_public_event_people_section\s*\(/', $GLOBALS['source'] ), 'Renderer call count differs.' ) );
p5_case( '41 no duplicated business HTML', fn() => p5_not_contains( 'wp-seed-event-people__item', $GLOBALS['source'], 'Business HTML duplicated.' ) );
p5_case( '42 no direct meta read', fn() => p5_not_contains( 'get_post_meta', $GLOBALS['source'], 'Direct meta read found.' ) );
p5_case( '43 no publish flags', fn() => p5_not_contains( 'publish_', $GLOBALS['source'], 'Publish flag exposed.' ) );
p5_case( '44 no person key', fn() => p5_not_contains( 'person_key', $GLOBALS['source'], 'Person key exposed.' ) );
p5_case( '45 no raw contacts access', fn() => p5_not_contains( '_wp_seed_event_contacts', $GLOBALS['source'], 'Raw contacts accessed.' ) );
p5_case( '46 Gutenberg equals renderer', function () {
	$attributes = array( 'title' => 'Equipe', 'layout' => 'grid' );
	$inner      = wp_seed_events_gutenberg_event_people_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), $attributes );
	$direct     = wp_seed_events_render_public_event_people_section( $GLOBALS['p5_events'][10], wp_seed_events_gutenberg_event_people_options( $attributes ) );
	p5_assert( $inner === $direct, 'Renderer parity failed.' );
} );
p5_case( '47 deterministic output', function () {
	$context = array( 'postId' => 10, 'postType' => 'wp_seed_event' ); p5_assert( p5_render( $context ) === p5_render( $context ), 'Output changed.' );
} );
p5_case( '48 multiple blocks remain independent', function () {
	p5_contains( 'Alice', p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ) ), 'First block failed.' ); p5_contains( 'David', p5_render( array( 'postId' => 11, 'postType' => 'wp_seed_event' ) ), 'Second block failed.' );
} );
p5_case( '49 no empty wrapper', fn() => p5_assert( '' === p5_render( array( 'postId' => 12, 'postType' => 'wp_seed_event' ) ), 'Empty wrapper found.' ) );
p5_case( '50 no context is safe', function () {
	$GLOBALS['p5_current_id'] = 0; $GLOBALS['wp_seed_events_public_event_id'] = 0; p5_assert( '' === p5_render(), 'No-context render was not empty.' );
} );

p5_case( '51 multiple roles use OR semantics', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'roles' => array( 'organizer', 'speaker' ) ) );
	p5_contains( 'Alice', $html, 'Organizer missing from OR filter.' );
	p5_contains( 'Benoit', $html, 'Speaker missing from OR filter.' );
	p5_not_contains( 'Claire', $html, 'Unselected role leaked.' );
} );
p5_case( '52 name and link controls preserve privacy', function () {
	$html = p5_render( array( 'postId' => 10, 'postType' => 'wp_seed_event' ), array( 'roles' => array( 'organizer' ), 'show_name' => false, 'show_roles' => false, 'link_email' => false, 'link_phone' => false, 'link_url' => false ) );
	p5_contains( '__name screen-reader-text', $html, 'Hidden name is not accessible.' );
	p5_not_contains( 'mailto:', $html, 'Email link remained active.' );
	p5_not_contains( 'tel:', $html, 'Phone link remained active.' );
	p5_not_contains( 'secret', $html, 'Private coordinate leaked.' );
} );

p5_case( '53 Gutenberg consumes canonical association phone action', function () {
	$html = p5_render( array( 'postId' => 13, 'postType' => 'wp_seed_event' ) );
	p5_contains( 'href="sms:+32470112233"', $html, 'Canonical SMS action is missing.' );
	p5_not_contains( 'href="tel:', $html, 'Gutenberg rebuilt the phone action.' );
} );

p5_assert( 53 === $GLOBALS['p5_cases'], 'Expected 53 cases.' );
echo 'Gutenberg people block: 53/53 OK' . PHP_EOL;
