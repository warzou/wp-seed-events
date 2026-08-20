<?php
/**
 * Standalone assertions for the shared public event people renderer.
 *
 * Run with: php tests/people-renderer-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['wp_seed_events_people_renderer_cases']      = 0;
$GLOBALS['wp_seed_events_people_renderer_data_calls'] = 0;
$GLOBALS['wp_seed_events_people_renderer_meta_calls'] = 0;

function absint( $value ) {
	return abs( (int) $value );
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

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function wp_seed_events_normalize_person_email( $value ) {
	$value = trim( (string) $value );

	return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
}

function wp_seed_events_normalize_person_phone( $value ) {
	$value = trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );

	if ( '' === $value || ! preg_match( '/^\+?[0-9\s().\/-]+$/u', $value ) ) {
		return '';
	}

	$length = strlen( (string) preg_replace( '/\D+/', '', $value ) );

	return $length >= 6 && $length <= 15 ? $value : '';
}

function wp_seed_events_normalize_person_link( $value ) {
	$value  = trim( (string) $value );
	$parts  = parse_url( $value );
	$scheme = is_array( $parts ) ? strtolower( (string) ( $parts['scheme'] ?? '' ) ) : '';
	$host   = is_array( $parts ) ? (string) ( $parts['host'] ?? '' ) : '';

	return in_array( $scheme, array( 'http', 'https' ), true ) && '' !== $host ? $value : '';
}

function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['wp_seed_events_people_renderer_data_calls']++;

	return array();
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['wp_seed_events_people_renderer_meta_calls']++;

	return '';
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';

function p2_person( $name, $role_keys = array(), $roles = array(), $coordinates = array() ) {
	return array_merge(
		array(
			'name'         => (string) $name,
			'role_keys'    => $role_keys,
			'roles'        => $roles,
			'public_email' => '',
			'public_phone' => '',
			'public_url'   => '',
			'email'        => '',
			'phone'        => '',
			'link'         => '',
		),
		is_array( $coordinates ) ? $coordinates : array()
	);
}

function p2_event( $people ) {
	return array(
		'id'     => 42,
		'people' => is_array( $people ) ? $people : array(),
	);
}

function p2_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function p2_contains( $needle, $haystack, $message ) {
	p2_assert( false !== strpos( (string) $haystack, (string) $needle ), $message );
}

function p2_not_contains( $needle, $haystack, $message ) {
	p2_assert( false === strpos( (string) $haystack, (string) $needle ), $message );
}

function p2_count( $needle, $haystack ) {
	return substr_count( (string) $haystack, (string) $needle );
}

function p2_case( $name, $callback ) {
	try {
		$callback();
		$GLOBALS['wp_seed_events_people_renderer_cases']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

$organizer = p2_person( 'Alice Martin', array( 'organizer' ), array( 'Organisatrice' ) );
$speaker   = p2_person( 'Benoît Durand', array( 'speaker' ), array( 'Intervenant' ) );
$register  = p2_person( 'Claire Petit', array( 'registration_contact' ), array( 'Contact inscription' ) );
$inform    = p2_person( 'David Robert', array( 'information_contact' ), array( 'Contact information' ) );
$complete  = p2_person(
	'Émilie Test',
	array( 'organizer', 'speaker' ),
	array( 'Organisatrice', 'Intervenante' ),
	array(
		'public_email' => 'public@example.test',
		'public_phone' => '+32 470 11 22 33',
		'public_url'   => 'https://example.test/profil',
		'email'        => 'private@example.test',
		'phone'        => '+32 499 99 99 99',
		'link'         => 'https://private.example.test/',
	)
);

p2_case( '1 invalid argument returns empty', function () {
	p2_assert( '' === wp_seed_events_render_public_event_people_section( null ), 'Invalid argument rendered markup.' );
} );

p2_case( '2 absent people returns empty', function () {
	p2_assert( '' === wp_seed_events_render_public_event_people_section( array( 'id' => 1 ) ), 'Missing people rendered markup.' );
} );

p2_case( '3 empty people returns empty', function () {
	p2_assert( '' === wp_seed_events_render_public_event_people_section( p2_event( array() ) ), 'Empty people rendered markup.' );
} );

p2_case( '4 nameless person is filtered', function () {
	$person = p2_person( '   ', array(), array(), array( 'public_email' => 'public@example.test' ) );
	p2_assert( '' === wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) ), 'Nameless person rendered markup.' );
} );

p2_case( '5 name-only person is rendered safely', function () {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( p2_person( '<b>Alice</b>' ) ) ) );
	p2_contains( '>Alice</span>', $html, 'Sanitized name is missing.' );
	p2_not_contains( '<strong class="wp-seed-event-people__name"', $html, 'Default Divi Normal weight is overridden semantically.' );
	p2_not_contains( '<b>', $html, 'Name HTML was preserved.' );
	p2_not_contains( 'wp-seed-event-people__contacts', $html, 'Empty contacts container rendered.' );
} );

p2_case( '6 multiple people render once each', function () use ( $organizer, $speaker, $register ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer, $speaker, $register ) ) );
	p2_assert( 3 === p2_count( 'wp-seed-event-people__item', $html ), 'People cardinality is incorrect.' );
} );

p2_case( '7 business order is preserved', function () use ( $organizer, $speaker, $register ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $speaker, $register, $organizer ) ) );
	p2_assert( strpos( $html, 'Benoît' ) < strpos( $html, 'Claire' ) && strpos( $html, 'Claire' ) < strpos( $html, 'Alice' ), 'People order changed.' );
} );

p2_case( '8 public roles are visible and escaped', function () {
	$person = p2_person( 'Alice', array( 'organizer' ), array( '<em>Organisatrice</em>' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_contains( 'wp-seed-event-people__roles', $html, 'Roles container is missing.' );
	p2_contains( '>Organisatrice</li>', $html, 'Public role is missing.' );
	p2_not_contains( '<em>', $html, 'Role HTML was preserved.' );
} );

p2_case( '9 roles can be hidden and legacy details remains compatible', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'show_roles' => false ) );
	p2_not_contains( 'wp-seed-event-people__roles', $html, 'Roles were not hidden.' );
	$legacy = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'details' => false ) );
	p2_contains( 'Contacts et intervenants', $legacy, 'Legacy title changed.' );
	p2_not_contains( 'wp-seed-event-people__contacts', $legacy, 'Legacy details=false exposed contacts.' );
} );

p2_case( '10 multiple roles keep their order', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_assert( strpos( $html, 'Organisatrice' ) < strpos( $html, 'Intervenante' ), 'Role order changed.' );
	p2_assert( 2 === p2_count( 'wp-seed-event-people__role', $html ) - 1, 'Multiple roles cardinality is incorrect.' );
} );

p2_case( '11 duplicate role labels are removed', function () {
	$person = p2_person( 'Alice', array( 'organizer' ), array( 'Organisatrice', 'Organisatrice', '' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_assert( 1 === p2_count( '>Organisatrice</li>', $html ), 'Duplicate role rendered.' );
} );

$role_people = array( $organizer, $speaker, $register, $inform );

p2_case( '12 organizer filter uses canonical role keys', function () use ( $role_people ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( $role_people ), array( 'role' => 'organizer' ) );
	p2_contains( 'Alice Martin', $html, 'Organizer missing.' );
	p2_assert( 1 === p2_count( 'wp-seed-event-people__item', $html ), 'Organizer filter leaked another person.' );
} );

p2_case( '13 speaker filter uses canonical role keys', function () use ( $role_people ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( $role_people ), array( 'role' => 'speaker' ) );
	p2_contains( 'Benoît Durand', $html, 'Speaker missing.' );
	p2_assert( 1 === p2_count( 'wp-seed-event-people__item', $html ), 'Speaker filter leaked another person.' );
} );

p2_case( '14 registration contact filter uses canonical role keys', function () use ( $role_people ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( $role_people ), array( 'role' => 'registration_contact' ) );
	p2_contains( 'Claire Petit', $html, 'Registration contact missing.' );
	p2_assert( 2 === p2_count( 'wp-seed-event-people__item', $html ), 'Registration alias did not expose all canonical contacts.' );
} );

p2_case( '15 information contact filter uses canonical role keys', function () use ( $role_people ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( $role_people ), array( 'role' => 'information_contact' ) );
	p2_contains( 'David Robert', $html, 'Information contact missing.' );
	p2_assert( 2 === p2_count( 'wp-seed-event-people__item', $html ), 'Information alias did not expose all canonical contacts.' );
} );

p2_case( '16 invalid role follows the historical all-people fallback', function () use ( $role_people ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( $role_people ), array( 'role' => 'translated-label' ) );
	p2_assert( 4 === p2_count( 'wp-seed-event-people__item', $html ), 'Invalid role fallback is not all people.' );
} );

p2_case( '17 public email is visible', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( 'mailto:public@example.test', $html, 'Public email is missing.' );
} );

p2_case( '18 private email alias stays absent when canonical value is empty', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_email' => '', 'email' => 'private@example.test' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'private@example.test', $html, 'Private email alias leaked.' );
} );

p2_case( '19 public phone is visible', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( 'href="tel:+32470112233"', $html, 'Public phone link is missing or malformed.' );
} );

p2_case( '20 private phone alias stays absent when canonical value is empty', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_phone' => '', 'phone' => '+32 499 99 99 99' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( '499', $html, 'Private phone alias leaked.' );
} );

p2_case( '21 public link is visible', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( 'href="https://example.test/profil"', $html, 'Public URL is missing.' );
	p2_not_contains( 'target=', $html, 'Public URL opens a new tab.' );
} );

p2_case( '22 private URL alias stays absent when canonical value is empty', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_url' => '', 'link' => 'https://private.example.test/' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'private.example.test', $html, 'Private URL alias leaked.' );
} );

p2_case( '23 three public coordinates remain independent', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_assert( 1 === p2_count( 'wp-seed-event-people__email"', $html ), 'Email item cardinality is incorrect.' );
	p2_assert( 1 === p2_count( 'wp-seed-event-people__phone"', $html ), 'Phone item cardinality is incorrect.' );
	p2_assert( 1 === p2_count( 'wp-seed-event-people__link"', $html ), 'URL item cardinality is incorrect.' );
} );

p2_case( '24 email can be hidden independently', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'show_email' => false ) );
	p2_not_contains( 'mailto:', $html, 'Email was not hidden.' );
	p2_contains( 'tel:', $html, 'Hiding email hid phone.' );
	p2_contains( 'example.test/profil', $html, 'Hiding email hid URL.' );
} );

p2_case( '25 phone can be hidden independently', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'show_phone' => false ) );
	p2_not_contains( 'tel:', $html, 'Phone was not hidden.' );
	p2_contains( 'mailto:', $html, 'Hiding phone hid email.' );
	p2_contains( 'example.test/profil', $html, 'Hiding phone hid URL.' );
} );

p2_case( '26 URL can be hidden independently', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'show_link' => false ) );
	p2_not_contains( 'example.test/profil', $html, 'URL was not hidden.' );
	p2_contains( 'mailto:', $html, 'Hiding URL hid email.' );
	p2_contains( 'tel:', $html, 'Hiding URL hid phone.' );
} );

p2_case( '27 default title is Personnes', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ) );
	p2_contains( '<h2 class="wp-seed-event-people__title">Personnes</h2>', $html, 'Default title is incorrect.' );
} );

p2_case( '28 custom title is sanitized', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'title' => '<em>Équipe</em>' ) );
	p2_contains( '>Équipe</h2>', $html, 'Custom title is missing.' );
	p2_not_contains( '<em>', $html, 'Custom title HTML was preserved.' );
} );

p2_case( '29 empty title omits heading', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'title' => '' ) );
	p2_not_contains( 'wp-seed-event-people__title', $html, 'Empty title rendered a heading.' );
} );

p2_case( '30 heading levels h2 through h6 are supported', function () use ( $organizer ) {
	foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) {
		$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'heading_level' => $level ) );
		p2_contains( '<' . $level . ' class="wp-seed-event-people__title">', $html, 'Heading level ' . $level . ' is missing.' );
	}
} );

p2_case( '31 invalid heading falls back to h2', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'heading_level' => 'h1' ) );
	p2_contains( '<h2 class="wp-seed-event-people__title">', $html, 'Invalid heading did not fall back to h2.' );
	p2_not_contains( '<h1', $html, 'Invalid h1 was rendered.' );
} );

p2_case( '32 list layout is the default', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ) );
	p2_contains( 'is-layout-list', $html, 'Default list layout class is missing.' );
} );

p2_case( '33 grid layout is available', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'layout' => 'grid' ) );
	p2_contains( 'is-layout-grid', $html, 'Grid layout class is missing.' );
} );

p2_case( '34 invalid layout falls back to list', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'layout' => 'carousel' ) );
	p2_contains( 'is-layout-list', $html, 'Invalid layout did not fall back to list.' );
	p2_not_contains( 'is-layout-carousel', $html, 'Invalid layout class was rendered.' );
} );

p2_case( '35 filtered historical aliases remain compatible', function () {
	$person = array(
		'name'      => 'Alias Public',
		'role_keys' => array(),
		'roles'     => array(),
		'email'     => 'alias@example.test',
		'phone'     => '+33 1 23 45 67 89',
		'link'      => 'https://example.test/alias',
	);
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_contains( 'alias@example.test', $html, 'Filtered email alias is missing.' );
	p2_contains( 'tel:+33123456789', $html, 'Filtered phone alias is missing.' );
	p2_contains( 'example.test/alias', $html, 'Filtered URL alias is missing.' );
} );

p2_case( '36 canonical public values have priority over aliases', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( 'public@example.test', $html, 'Canonical email is missing.' );
	p2_contains( '470', $html, 'Canonical phone is missing.' );
	p2_contains( 'example.test/profil', $html, 'Canonical URL is missing.' );
	p2_not_contains( 'private@example.test', $html, 'Email alias overrode canonical value.' );
	p2_not_contains( '499', $html, 'Phone alias overrode canonical value.' );
	p2_not_contains( 'private.example.test', $html, 'URL alias overrode canonical value.' );
} );

p2_case( '37 arbitrary private fields are never rendered', function () {
	$person = array_merge( p2_person( 'Alice' ), array( 'private_email' => 'LEAK_PRIVATE_EMAIL', 'raw_phone' => 'LEAK_PRIVATE_PHONE' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'LEAK_PRIVATE', $html, 'Arbitrary private field leaked.' );
} );

p2_case( '38 person key is never rendered', function () {
	$person = array_merge( p2_person( 'Alice' ), array( 'person_key' => 'SECRET_PERSON_KEY' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'SECRET_PERSON_KEY', $html, 'Person key leaked.' );
} );

p2_case( '39 publication flags are never rendered', function () {
	$person = array_merge( p2_person( 'Alice' ), array( 'publish_email' => 'LEAK_EMAIL_FLAG', 'publish_phone' => 'LEAK_PHONE_FLAG', 'publish_link' => 'LEAK_LINK_FLAG' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'LEAK_', $html, 'Publication flag leaked.' );
} );

p2_case( '40 empty role and contacts containers are omitted', function () {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( p2_person( 'Alice' ) ) ), array( 'title' => '' ) );
	p2_not_contains( 'wp-seed-event-people__roles', $html, 'Empty roles container rendered.' );
	p2_not_contains( 'wp-seed-event-people__contacts', $html, 'Empty contacts container rendered.' );
	p2_not_contains( 'wp-seed-event-people__title', $html, 'Empty title container rendered.' );
} );

p2_case( '41 output has no builder-specific markup', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	foreach ( array( 'et_pb_', 'divi', 'wp-block-', 'spectra', 'uag-' ) as $token ) {
		p2_not_contains( $token, strtolower( $html ), 'Builder token rendered: ' . $token );
	}
} );

$rendering_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$people_start     = strpos( $rendering_source, 'function wp_seed_events_public_event_people_layout_option' );
$people_source    = false === $people_start ? '' : substr( $rendering_source, $people_start );

p2_case( '42 renderer has no direct meta access', function () use ( $people_source ) {
	p2_not_contains( 'get_post_meta', $people_source, 'Renderer reads post meta.' );
	p2_assert( 1 === p2_count( "foreach ( \$event['people']", $people_source ), 'Renderer does not use one people traversal.' );
} );

p2_case( '43 renderer has no SQL access', function () use ( $people_source ) {
	foreach ( array( '$wpdb', 'WP_Query', 'SELECT ' ) as $token ) {
		p2_not_contains( $token, $people_source, 'Renderer contains SQL access: ' . $token );
	}
} );

p2_case( '44 renderer has no shortcode dependency', function () use ( $people_source ) {
	foreach ( array( 'do_shortcode', 'shortcode_atts', '[wp_seed_' ) as $token ) {
		p2_not_contains( $token, $people_source, 'Renderer contains shortcode dependency: ' . $token );
	}
} );

p2_case( '45 invalid email is rejected', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_email' => 'not-an-email' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'mailto:', $html, 'Invalid email rendered.' );
} );

p2_case( '46 invalid phone is rejected', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_phone' => 'CALL-ME' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'tel:', $html, 'Invalid phone rendered.' );
} );

p2_case( '47 dangerous URL is rejected', function () {
	$person = p2_person( 'Alice', array(), array(), array( 'public_url' => 'javascript:alert(1)' ) );
	$html   = wp_seed_events_render_public_event_people_section( p2_event( array( $person ) ) );
	p2_not_contains( 'javascript:', $html, 'Dangerous URL rendered.' );
	p2_not_contains( 'wp-seed-event-people__link-anchor', $html, 'Dangerous URL produced a link.' );
} );

p2_case( '48 titleless section has an accessible name', function () use ( $organizer ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer ) ), array( 'title' => '' ) );
	p2_contains( '<section ', $html, 'Section element is missing.' );
	p2_contains( 'aria-label="Personnes de l&#039;événement"', $html, 'Titleless section has no accessible name.' );
	p2_contains( '<ul ', $html, 'People list is missing.' );
} );

p2_case( '49 public links have contextual names', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( 'aria-label="Envoyer un email à Émilie Test"', $html, 'Email accessible name is missing.' );
	p2_contains( 'aria-label="Appeler Émilie Test"', $html, 'Phone accessible name is missing.' );
	p2_contains( '>Consulter le lien associé à Émilie Test</a>', $html, 'URL contextual label is missing.' );
	p2_not_contains( 'tabindex="-1"', $html, 'A public link was removed from keyboard order.' );
} );

p2_case( '50 output is deterministic and side-effect free', function () use ( $complete ) {
	$event  = p2_event( array( $complete ) );
	$first  = wp_seed_events_render_public_event_people_section( $event, array( 'layout' => 'grid' ) );
	$second = wp_seed_events_render_public_event_people_section( $event, array( 'layout' => 'grid' ) );
	p2_assert( $first === $second, 'Successive renders differ.' );
	p2_assert( 0 === $GLOBALS['wp_seed_events_people_renderer_data_calls'], 'Renderer reloaded Event Data.' );
	p2_assert( 0 === $GLOBALS['wp_seed_events_people_renderer_meta_calls'], 'Renderer read post meta.' );
} );

p2_case( '51 multiple roles use OR semantics', function () use ( $organizer, $speaker, $register ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer, $speaker, $register ) ), array( 'roles' => array( 'organizer', 'speaker' ) ) );
	p2_assert( 2 === p2_count( 'wp-seed-event-people__item', $html ), 'Multiple roles did not use OR semantics.' );
	p2_not_contains( 'Claire Petit', $html, 'Unselected role leaked.' );
} );

p2_case( '52 all role is exclusive', function () use ( $organizer, $speaker, $register ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $organizer, $speaker, $register ) ), array( 'roles' => array( 'all', 'speaker' ) ) );
	p2_assert( 3 === p2_count( 'wp-seed-event-people__item', $html ), 'All did not override specific roles.' );
} );

p2_case( '53 hidden name remains accessible with public contacts', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'show_name' => false, 'show_roles' => false ) );
	p2_contains( 'wp-seed-event-people__name screen-reader-text', $html, 'Hidden name lacks a screen-reader identity.' );
	p2_contains( 'Émilie Test', $html, 'Accessible name is missing.' );
} );

p2_case( '54 link toggles keep public values without anchors', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'link_email' => false, 'link_phone' => false, 'link_url' => false ) );
	p2_contains( 'public@example.test', $html, 'Public email was hidden instead of unlinked.' );
	p2_contains( '+32 470 11 22 33', $html, 'Public phone was hidden instead of unlinked.' );
	p2_contains( 'https://example.test/profil', $html, 'Public URL was hidden instead of unlinked.' );
	p2_not_contains( 'mailto:', $html, 'Email remained linked.' );
	p2_not_contains( 'tel:', $html, 'Phone remained linked.' );
	p2_not_contains( '__link-anchor', $html, 'URL remained linked.' );
	p2_not_contains( 'private@example.test', $html, 'Private email leaked.' );
} );

p2_case( '55 composable name-only output excludes all details', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array(
		'contract' => 'composable-v1', 'show_name' => true, 'show_roles' => false,
		'show_email' => false, 'show_phone' => false, 'show_link' => false,
	) );
	p2_contains( 'Émilie Test', $html, 'Name-only composition lost the name.' );
	p2_not_contains( '__roles', $html, 'Name-only composition rendered roles.' );
	p2_not_contains( '__contacts', $html, 'Name-only composition rendered contacts.' );
} );

p2_case( '56 phone action supports none call and SMS', function () use ( $complete ) {
	$base = array( 'contract' => 'composable-v1', 'show_name' => false, 'show_roles' => false, 'show_email' => false, 'show_link' => false );
	$none = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array_merge( $base, array( 'phone_action' => 'none' ) ) );
	$call = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array_merge( $base, array( 'phone_action' => 'call' ) ) );
	$sms  = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array_merge( $base, array( 'phone_action' => 'sms' ) ) );
	p2_not_contains( 'href=', $none, 'None phone action created a link.' );
	p2_contains( 'href="tel:+32470112233"', $call, 'Call action is invalid.' );
	p2_contains( 'href="sms:+32470112233"', $sms, 'SMS action is invalid.' );
	p2_contains( '+32 470 11 22 33', $sms, 'Readable phone was changed.' );
} );

p2_case( '57 email and site clickability are independent', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array(
		'contract' => 'composable-v1', 'email_clickable' => false, 'site_clickable' => true, 'site_label' => 'Site internet',
	) );
	p2_not_contains( 'mailto:', $html, 'Email remained clickable.' );
	p2_contains( '>public@example.test</span>', $html, 'Email text disappeared.' );
	p2_contains( 'href="https://example.test/profil">Site internet</a>', $html, 'Site label or URL is invalid.' );
} );

p2_case( '58 empty site label falls back to URL in composable contract', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array( 'contract' => 'composable-v1', 'site_label' => '' ) );
	p2_contains( '>https://example.test/profil</a>', $html, 'Site URL fallback is missing.' );
} );

p2_case( '59 inline contacts render neutral separators only between values', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), array(
		'contract' => 'composable-v1', 'contact_layouts' => array( 'desktop' => 'inline', 'tablet' => 'stacked', 'phone' => 'inline' ),
		'show_contact_separator' => true, 'contact_separator' => '|',
	) );
	p2_contains( 'is-contact-layout-desktop-inline', $html, 'Desktop inline class is missing.' );
	p2_contains( 'is-contact-layout-tablet-stacked', $html, 'Tablet stacked class is missing.' );
	p2_contains( 'is-contact-layout-phone-inline', $html, 'Phone inline class is missing.' );
	p2_assert( 2 === p2_count( 'wp-seed-event-people__contact-separator', $html ), 'Separator cardinality differs from contacts minus one.' );
	p2_assert( 3 === p2_count( 'wp-seed-event-people__contact wp-seed-event-people__', $html ), 'Contact cardinality changed.' );
} );

p2_case( '60 legacy output remains byte-compatible in its public labels', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ) );
	p2_contains( '>Consulter le lien associé à Émilie Test</a>', $html, 'Legacy site label changed.' );
	p2_not_contains( 'is-people-composable', $html, 'Legacy output received composable classes.' );
	p2_not_contains( '__contact-separator', $html, 'Legacy output received separators.' );
} );

p2_case( '61 composable v2 consumes canonical person website data', function () use ( $complete ) {
	$person = array_merge(
		$complete,
		array(
			'website_url'   => 'https://example.test/profil',
			'website_label' => 'Découvrir mon site',
		)
	);
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $person ) ),
		array( 'contract' => 'composable-v2', 'site_clickable' => true )
	);
	p2_contains( 'href="https://example.test/profil">Découvrir mon site</a>', $html, 'Canonical website label was not rendered.' );
} );

p2_case( '62 composable v2 site text remains canonical when unlinked', function () use ( $complete ) {
	$person = array_merge(
		$complete,
		array(
			'website_url'   => 'https://example.test/profil',
			'website_label' => 'Découvrir mon site',
		)
	);
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $person ) ),
		array( 'contract' => 'composable-v2', 'site_clickable' => false )
	);
	p2_contains( '>Découvrir mon site</span>', $html, 'Canonical unlinked website label was not rendered.' );
	p2_not_contains( '__link-anchor', $html, 'Unlinked canonical website remained clickable.' );
} );

p2_case( '63 composable v1 preserves its saved module label', function () use ( $complete ) {
	$person = array_merge( $complete, array( 'website_label' => 'Nouveau libellé Personne' ) );
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $person ) ),
		array( 'contract' => 'composable-v1', 'site_label' => 'Ancien libellé du module' )
	);
	p2_contains( '>Ancien libellé du module</a>', $html, 'Saved v1 module label was replaced.' );
	p2_not_contains( 'Nouveau libellé Personne', $html, 'V1 instance consumed v2 person data.' );
} );

p2_case( '64 composable v2 consumes canonical association phone actions', function () use ( $complete ) {
	$base = array( 'contract' => 'composable-v2', 'show_name' => false, 'show_roles' => false, 'show_email' => false, 'show_link' => false );
	$none = wp_seed_events_render_public_event_people_section( p2_event( array( array_merge( $complete, array( 'phone_action' => 'none' ) ) ) ), $base );
	$call = wp_seed_events_render_public_event_people_section( p2_event( array( array_merge( $complete, array( 'phone_action' => 'call' ) ) ) ), $base );
	$sms  = wp_seed_events_render_public_event_people_section( p2_event( array( array_merge( $complete, array( 'phone_action' => 'sms' ) ) ) ), $base );
	p2_not_contains( 'href=', $none, 'Canonical none action created a link.' );
	p2_contains( 'href="tel:+32470112233"', $call, 'Canonical call action is invalid.' );
	p2_contains( 'href="sms:+32470112233"', $sms, 'Canonical SMS action is invalid.' );
} );

p2_case( '65 same person can use distinct phone actions per event', function () use ( $complete ) {
	$base = array( 'contract' => 'composable-v2', 'show_email' => false, 'show_link' => false, 'phone_action' => 'none' );
	$call = wp_seed_events_render_public_event_people_section( p2_event( array( array_merge( $complete, array( 'phone_action' => 'call' ) ) ) ), $base );
	$sms  = wp_seed_events_render_public_event_people_section( p2_event( array( array_merge( $complete, array( 'phone_action' => 'sms' ) ) ) ), $base );
	p2_contains( 'href="tel:', $call, 'Event A did not keep call.' );
	p2_contains( 'href="sms:', $sms, 'Event B did not keep SMS.' );
	p2_not_contains( 'href="sms:', $call, 'Event B action leaked into event A.' );
} );

p2_case( '66 with-name layout is normalized and emitted per breakpoint', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'        => 'composable-v2',
			'contact_layouts' => array( 'desktop' => 'with_name', 'tablet' => 'inline', 'phone' => 'stacked' ),
		)
	);
	p2_contains( 'is-contact-layout-desktop-with_name', $html, 'Desktop with-name class is missing.' );
	p2_contains( 'is-contact-layout-tablet-inline', $html, 'Tablet inline class is missing.' );
	p2_contains( 'is-contact-layout-phone-stacked', $html, 'Phone stacked class is missing.' );
} );

p2_case( '67 with-name separator includes the name boundary', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'               => 'composable-v2',
			'contact_layouts'        => array( 'desktop' => 'with_name' ),
			'show_contact_separator' => true,
		)
	);
	p2_contains( 'wp-seed-event-people__contact-separator--name', $html, 'Name/contact separator boundary is missing.' );
	p2_contains( '>&mdash;</span>', str_replace( '—', '&mdash;', $html ), 'New separator default is not an em dash.' );
} );

p2_case( '68 explicit historical separator remains unchanged', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'               => 'composable-v2',
			'contact_layouts'        => array( 'desktop' => 'with_name' ),
			'show_contact_separator' => true,
			'contact_separator'      => '|',
		)
	);
	p2_contains( '>|</span>', $html, 'Explicit separator was replaced.' );
} );

p2_case( '69 with-name separators cover only visible values', function () use ( $complete ) {
	$base = array(
		'contract'               => 'composable-v2',
		'contact_layouts'        => array( 'desktop' => 'with_name' ),
		'show_contact_separator' => true,
		'contact_separator'      => '|',
		'show_roles'             => false,
	);
	$all = wp_seed_events_render_public_event_people_section( p2_event( array( $complete ) ), $base );
	p2_assert( 3 === p2_count( '<span class="wp-seed-event-people__contact-separator', $all ), 'Name plus three contacts must have exactly three separators.' );
	p2_not_contains( '||', wp_strip_all_tags( $all ), 'Adjacent separators were rendered.' );

	$name_and_phone = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array_merge( $base, array( 'show_email' => false, 'show_link' => false ) )
	);
	p2_assert( 1 === p2_count( '<span class="wp-seed-event-people__contact-separator', $name_and_phone ), 'Name plus phone must have exactly one separator.' );

	$name_only = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array_merge( $base, array( 'show_email' => false, 'show_phone' => false, 'show_link' => false ) )
	);
	p2_not_contains( '__contact-separator', $name_only, 'Name-only output rendered a separator.' );
} );

p2_case( '70 hidden name never creates a leading with-name separator', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'               => 'composable-v2',
			'contact_layouts'        => array( 'desktop' => 'with_name' ),
			'show_name'              => false,
			'show_roles'             => false,
			'show_contact_separator' => true,
		)
	);
	p2_not_contains( '__contact-separator--name', $html, 'Hidden name created a leading separator.' );
	p2_assert( 2 === p2_count( '<span class="wp-seed-event-people__contact-separator', $html ), 'Visible contacts did not keep contacts-minus-one separators.' );
} );

p2_case( '71 composable v3 keeps both separator contracts independent', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'                    => 'composable-v3',
			'contact_layouts'             => array( 'desktop' => 'with-name' ),
			'show_contact_separator'      => true,
			'contact_separator'           => '|',
			'show_name_contact_separator' => true,
			'name_contact_separator'      => ':',
			'contact_separator_styles'    => array( 'desktop' => array( 'color' => '#112233' ) ),
			'name_contact_separator_styles' => array( 'desktop' => array( 'color' => '#445566' ) ),
		)
	);
	p2_contains( 'is-contact-layout-desktop-with_name', $html, 'Hyphenated UI value was not normalized.' );
	p2_contains( 'wp-seed-event-people__identity-contact-flow', $html, 'Composable output lacks the real identity/contact flow wrapper.' );
	p2_contains( '>:</span>', $html, 'Name separator character is missing.' );
	p2_contains( '>|</span>', $html, 'Contact separator character is missing.' );
	p2_contains( '--wp-seed-event-people-name-separator-color-desktop:#445566', $html, 'Name separator style leaked or disappeared.' );
	p2_contains( '--wp-seed-event-people-separator-color-desktop:#112233', $html, 'Contact separator style leaked or disappeared.' );
	p2_assert(
		strpos( $html, 'wp-seed-event-people__name' ) < strpos( $html, '__contact-separator--name' )
		&& strpos( $html, '__contact-separator--name' ) < strpos( $html, 'wp-seed-event-people__contacts' ),
		'Name, boundary separator and contacts do not share the expected DOM order.'
	);
} );

p2_case( '72 composable v3 never renders orphan name separators', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'                    => 'composable-v3',
			'contact_layouts'             => array( 'desktop' => 'with_name' ),
			'show_name_contact_separator' => true,
			'show_email'                  => false,
			'show_phone'                  => false,
			'show_link'                   => false,
		)
	);
	p2_not_contains( '__contact-separator', $html, 'Name-only output rendered an orphan separator.' );
} );

p2_case( '73 responsive layouts preserve with-name inline and stacked modes', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'        => 'composable-v3',
			'contact_layouts' => array( 'desktop' => 'with-name', 'tablet' => 'inline', 'phone' => 'stacked' ),
		)
	);
	p2_contains( 'is-contact-layout-desktop-with_name', $html, 'Desktop with-name class missing.' );
	p2_contains( 'is-contact-layout-tablet-inline', $html, 'Tablet inline class missing.' );
	p2_contains( 'is-contact-layout-phone-stacked', $html, 'Phone stacked class missing.' );
} );

p2_case( '74 untouched historical instances keep legacy markup', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array( 'contract' => 'legacy', 'contact_layouts' => array( 'desktop' => 'inline' ) )
	);
	p2_not_contains( 'wp-seed-event-people__identity-contact-flow', $html, 'Untouched legacy inline instance was migrated implicitly.' );
	p2_not_contains( 'is-people-composable', $html, 'Untouched legacy inline instance received composable classes.' );
} );

p2_case( '75 explicit with-name overrides a missing historical contract', function () use ( $complete ) {
	$html = wp_seed_events_render_public_event_people_section(
		p2_event( array( $complete ) ),
		array(
			'contract'                    => 'legacy',
			'contact_layouts'             => array( 'desktop' => 'with_name', 'tablet' => 'inline', 'phone' => 'stacked' ),
			'show_contact_separator'      => true,
			'show_name_contact_separator' => true,
			'name_contact_separator'      => ':',
		)
	);
	p2_contains( 'is-contact-layout-desktop-with_name', $html, 'Explicit historical with-name value did not become authoritative.' );
	p2_contains( 'wp-seed-event-people__identity-contact-flow', $html, 'Explicit historical with-name value did not create the shared flow.' );
	p2_contains( 'wp-seed-event-people__contact-separator--name', $html, 'Explicit historical with-name value lost its name separator.' );
} );

$benchmark_events = array(
	'zero'            => p2_event( array() ),
	'one'             => p2_event( array( $organizer ) ),
	'three'           => p2_event( array( $organizer, $speaker, $complete ) ),
	'multiple_roles'  => p2_event( array( $complete ) ),
	'no_coordinates'  => p2_event( array( $organizer, $speaker, $register ) ),
);
$benchmark = array();

foreach ( $benchmark_events as $name => $event ) {
	$start = hrtime( true );

	for ( $iteration = 0; $iteration < 1000; $iteration++ ) {
		wp_seed_events_render_public_event_people_section( $event );
	}

	$benchmark[ $name . '_1000_ms' ] = round( ( hrtime( true ) - $start ) / 1000000, 3 );
}

$loop_events = array_fill( 0, 7, $benchmark_events['three'] );
$start       = hrtime( true );

for ( $iteration = 0; $iteration < 250; $iteration++ ) {
	foreach ( $loop_events as $event ) {
		wp_seed_events_render_public_event_people_section( $event, array( 'role' => 'speaker' ) );
	}
}

$benchmark['seven_event_loop_1750_ms'] = round( ( hrtime( true ) - $start ) / 1000000, 3 );

echo '[OK] ' . $GLOBALS['wp_seed_events_people_renderer_cases'] . ' people renderer cases passed.' . PHP_EOL;
echo '[PERF] ' . json_encode( $benchmark, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
