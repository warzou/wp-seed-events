<?php

define( 'ABSPATH', __DIR__ );

$GLOBALS['contact_rest_meta']   = array();
$GLOBALS['contact_rest_fields'] = array();
$GLOBALS['contact_cache_flush'] = array();
$GLOBALS['contact_people']      = array();

class WP_Error {
	public $code;
	public function __construct( $code ) { $this->code = $code; }
}
class Contact_Request {
	private $context;
	public function __construct( $context ) { $this->context = $context; }
	public function get_param( $key ) { return 'context' === $key ? $this->context : null; }
}

function add_action() {}
function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_email( $value ) { return trim( (string) $value ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function esc_url_raw( $value ) { return trim( (string) $value ); }
function wp_parse_url( $value ) { return parse_url( $value ); }
function current_user_can() { return true; }
function __( $value ) { return $value; }
function get_post_meta( $id ) { return $GLOBALS['contact_rest_meta'][ $id ] ?? array(); }
function update_post_meta( $id, $key, $value ) { unset( $key ); $GLOBALS['contact_rest_meta'][ $id ] = $value; return true; }
function delete_post_meta( $id ) { unset( $GLOBALS['contact_rest_meta'][ $id ] ); return true; }
function register_rest_field( $type, $name, $args ) { $GLOBALS['contact_rest_fields'][ $type ][ $name ] = $args; }
function wp_seed_events_dynamic_data_invalidate_event_cache( $id ) { $GLOBALS['contact_cache_flush'][] = $id; }
function wp_seed_events_person_key_from_name( $name ) { return sanitize_key( $name ); }
function wp_seed_events_sanitize_public_http_url( $value ) {
	$parts = parse_url( trim( (string) $value ) );
	return is_array( $parts ) && in_array( $parts['scheme'] ?? '', array( 'http', 'https' ), true ) ? trim( (string) $value ) : '';
}
function wp_seed_events_canonical_contact_role( $role ) {
	$role = sanitize_key( $role );
	return in_array( $role, array( 'registration_contact', 'information_contact' ), true ) ? 'contact' : $role;
}
function wp_seed_events_contact_roles() { return array( 'organizer' => 'Organisateur', 'speaker' => 'Intervenant', 'contact' => 'Contact' ); }
function wp_seed_events_contact_role_keys( $contact, $roles ) {
	$raw = is_array( $contact['roles'] ?? null ) ? $contact['roles'] : array( $contact['role'] ?? '' );
	$out = array();
	foreach ( $raw as $role ) {
		$role = wp_seed_events_canonical_contact_role( $role );
		if ( isset( $roles[ $role ] ) && ! in_array( $role, $out, true ) ) { $out[] = $role; }
	}
	return $out;
}
function wp_seed_events_get_event_data( $id ) {
	return array( 'contact' => array( array( 'name' => 'Public ' . $id, 'role_keys' => array( 'contact' ) ) ) );
}
function wp_seed_events_stored_people() { return $GLOBALS['contact_people']; }
function wp_seed_events_save_people( $people ) { $GLOBALS['contact_people'] = $people; }
function wp_seed_events_sanitize_person( $person, $person_key = '' ) {
	return array(
		'person_key'    => sanitize_key( $person_key ?: ( $person['person_key'] ?? '' ) ),
		'name'          => sanitize_text_field( $person['name'] ?? '' ),
		'phone'         => sanitize_text_field( $person['phone'] ?? '' ),
		'email'         => sanitize_email( $person['email'] ?? '' ),
		'link'          => wp_seed_events_normalize_person_link( $person['link'] ?? '' ),
		'website_label' => wp_seed_events_normalize_person_website_label( $person['website_label'] ?? '' ),
	);
}

require dirname( __DIR__ ) . '/includes/public/people.php';

$passed = 0;
function cr_case( $name, $callback ) { global $passed; $callback(); $passed++; echo "ok {$passed} - {$name}\n"; }
function cr_same( $expected, $actual, $message ) { if ( $expected !== $actual ) { throw new RuntimeException( $message ); } }
function cr_row( $role, $name ) { return array( 'person_key' => sanitize_key( $name ), 'role' => $role, 'roles' => array( $role ), 'name' => $name, 'phone' => '', 'email' => '', 'link' => '', 'publish_phone' => false, 'publish_email' => false, 'publish_link' => false ); }

cr_case( 'REST registers one canonical writable field', function () {
	wp_seed_events_register_contact_rest_fields();
	$args = $GLOBALS['contact_rest_fields']['wp_seed_event']['contact'] ?? array();
	cr_same( 'wp_seed_events_contact_rest_get', $args['get_callback'] ?? '', 'get callback differs' );
	cr_same( 'wp_seed_events_contact_rest_update', $args['update_callback'] ?? '', 'update callback differs' );
} );

cr_case( 'public REST read exposes public contact only', function () {
	$value = wp_seed_events_contact_rest_get( array( 'id' => 9 ), 'contact', new Contact_Request( 'view' ) );
	cr_same( 'Public 9', $value[0]['name'], 'public projection differs' );
} );

cr_case( 'edit REST read canonicalizes legacy storage', function () {
	$GLOBALS['contact_rest_meta'][9] = array( cr_row( 'registration_contact', 'Claire' ), cr_row( 'organizer', 'Alice' ) );
	$value = wp_seed_events_contact_rest_get( array( 'id' => 9 ), 'contact', new Contact_Request( 'edit' ) );
	cr_same( 1, count( $value ), 'non-contact leaked into contact field' );
	cr_same( 'contact', $value[0]['role'], 'legacy role was not canonicalized' );
	cr_same( 'call', $value[0]['phone_action'], 'historical phone action fallback differs' );
	cr_same( false, $value[0]['phone_action_explicit'], 'historical fallback became explicit' );
} );

cr_case( 'REST write replaces contacts and preserves other people', function () {
	$result = wp_seed_events_contact_rest_update( array( cr_row( 'contact', 'David' ) ), (object) array( 'ID' => 9 ) );
	cr_same( true, $result, 'write failed' );
	cr_same( 'organizer', $GLOBALS['contact_rest_meta'][9][0]['role'], 'organizer was changed' );
	cr_same( 'contact', $GLOBALS['contact_rest_meta'][9][1]['role'], 'contact was not stored canonically' );
} );

cr_case( 'REST deletion removes contacts only', function () {
	wp_seed_events_contact_rest_update( array(), (object) array( 'ID' => 9 ) );
	cr_same( 1, count( $GLOBALS['contact_rest_meta'][9] ), 'contact was not removed' );
	cr_same( 'organizer', $GLOBALS['contact_rest_meta'][9][0]['role'], 'organizer was removed' );
} );

cr_case( 'REST writes phone action on the event association', function () {
	$row = cr_row( 'contact', 'David' );
	$row['phone'] = '+32 470 11 22 33';
	$row['publish_phone'] = true;
	$row['phone_action'] = 'sms';
	wp_seed_events_contact_rest_update( array( $row ), (object) array( 'ID' => 9 ) );
	$stored = $GLOBALS['contact_rest_meta'][9][1] ?? array();
	cr_same( 'sms', $stored['phone_action'] ?? '', 'REST phone action was not stored on the association' );
} );

cr_case( 'REST preserves an implicit historical action on round trip', function () {
	$legacy = cr_row( 'contact', 'Claire' );
	$GLOBALS['contact_rest_meta'][10] = array( $legacy );
	$editable = wp_seed_events_contact_rest_get( array( 'id' => 10 ), 'contact', new Contact_Request( 'edit' ) );
	wp_seed_events_contact_rest_update( $editable, (object) array( 'ID' => 10 ) );
	cr_same( false, array_key_exists( 'phone_action', $GLOBALS['contact_rest_meta'][10][0] ), 'REST round trip migrated historical storage' );
} );

cr_case( 'edit REST exposes the canonical website pair', function () {
	$row = cr_row( 'contact', 'Claire' );
	$row['link'] = 'https://example.test/claire';
	$GLOBALS['contact_rest_meta'][11] = array( $row );
	$GLOBALS['contact_people']['claire'] = array_merge( $row, array( 'website_label' => 'Découvrir le site' ) );
	$value = wp_seed_events_contact_rest_get( array( 'id' => 11 ), 'contact', new Contact_Request( 'edit' ) );
	cr_same( 'https://example.test/claire', $value[0]['website_url'], 'website URL missing from edit REST' );
	cr_same( 'Découvrir le site', $value[0]['website_label'], 'website label missing from edit REST' );
} );

cr_case( 'REST stores the label globally without duplicating the association', function () {
	$row = cr_row( 'contact', 'Claire' );
	$row['website_url'] = 'https://example.test/claire';
	$row['website_label'] = 'Voir Claire';
	$result = wp_seed_events_contact_rest_update( array( $row ), (object) array( 'ID' => 11 ) );
	cr_same( true, $result, 'website REST write failed' );
	cr_same( 'Voir Claire', $GLOBALS['contact_people']['claire']['website_label'], 'global website label was not updated' );
	cr_same( false, array_key_exists( 'website_label', $GLOBALS['contact_rest_meta'][11][0] ), 'website label leaked into event association' );
} );

cr_case( 'REST rejects a website label without URL', function () {
	$row = cr_row( 'contact', 'Claire' );
	$row['website_label'] = 'Voir Claire';
	$result = wp_seed_events_contact_rest_update( array( $row ), (object) array( 'ID' => 11 ) );
	cr_same( true, $result instanceof WP_Error, 'invalid website pair did not return WP_Error' );
	cr_same( 'rest_invalid_website', $result->code, 'invalid website pair returned the wrong error' );
} );

echo "PASS {$passed}/{$passed}\n";
