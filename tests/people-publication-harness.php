<?php
/**
 * Standalone assertions for per-event person contact publication.
 *
 * Run with: php tests/people-publication-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY', '_wp_seed_event_short_description' );

$GLOBALS['p1_assertions']       = 0;
$GLOBALS['p1_posts']            = array();
$GLOBALS['p1_meta']             = array();
$GLOBALS['p1_people']           = array();
$GLOBALS['p1_meta_reads']       = array();
$GLOBALS['p1_writes']           = array();
$GLOBALS['p1_event_ids']        = array();
$GLOBALS['p1_invalidated_ids']  = array();
$GLOBALS['p1_current_user_can'] = true;

function p1_assert( $condition, $message ) {
	$GLOBALS['p1_assertions']++;

	if ( ! $condition ) {
		throw new RuntimeException( 'Assertion failed: ' . $message );
	}
}

function p1_same( $expected, $actual, $message ) {
	p1_assert( $expected === $actual, $message . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
}

function p1_reset_runtime() {
	$GLOBALS['p1_posts']            = array();
	$GLOBALS['p1_meta']             = array();
	$GLOBALS['p1_people']           = array();
	$GLOBALS['p1_meta_reads']       = array();
	$GLOBALS['p1_writes']           = array();
	$GLOBALS['p1_event_ids']        = array();
	$GLOBALS['p1_invalidated_ids']  = array();
	$GLOBALS['p1_current_user_can'] = true;
	$_POST                          = array();
}

function p1_extract_function( $source, $function_name, $next_function_name ) {
	$start = strpos( $source, 'function ' . $function_name . '(' );
	$end   = strpos( $source, 'function ' . $next_function_name . '(', $start );

	if ( false === $start || false === $end ) {
		throw new RuntimeException( 'Unable to extract ' . $function_name );
	}

	return substr( $source, $start, $end - $start );
}

function absint( $value ) {
	return abs( (int) $value );
}

function sanitize_text_field( $value ) {
	$value = strip_tags( (string) $value );
	$value = preg_replace( '/[\r\n\t ]+/', ' ', $value );

	return trim( (string) $value );
}

function sanitize_textarea_field( $value ) {
	return trim( strip_tags( (string) $value ) );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function sanitize_title( $value ) {
	$value = strtolower( remove_accents( sanitize_text_field( $value ) ) );
	$value = preg_replace( '/[^a-z0-9]+/', '-', $value );

	return trim( (string) $value, '-' );
}

function sanitize_email( $value ) {
	return filter_var( trim( (string) $value ), FILTER_SANITIZE_EMAIL );
}

function is_email( $value ) {
	return false !== filter_var( (string) $value, FILTER_VALIDATE_EMAIL );
}

function remove_accents( $value ) {
	return strtr(
		(string) $value,
		array(
			'é' => 'e',
			'è' => 'e',
			'ê' => 'e',
			'à' => 'a',
			'ô' => 'o',
			'ù' => 'u',
		)
	);
}

function esc_url_raw( $url, $protocols = null ) {
	$url   = trim( (string) $url );
	$parts = '' !== $url ? parse_url( $url ) : false;

	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return '';
	}

	$allowed = is_array( $protocols ) ? array_map( 'strtolower', $protocols ) : array( 'http', 'https' );

	return in_array( strtolower( (string) $parts['scheme'] ), $allowed, true ) ? $url : '';
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component );
}

function sanitize_file_name( $value ) {
	return basename( str_replace( '\\', '/', (string) $value ) );
}

function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : ( is_string( $value ) ? stripslashes( $value ) : $value );
}

function wp_verify_nonce( $nonce, $action ) {
	return 'valid' === $nonce && 'wp_seed_events_save_contacts' === $action;
}

function wp_is_post_revision( $post_id ) {
	unset( $post_id );

	return false;
}

function current_user_can( $capability, $post_id = 0 ) {
	unset( $capability, $post_id );

	return $GLOBALS['p1_current_user_can'];
}

function get_post( $post_id ) {
	return $GLOBALS['p1_posts'][ absint( $post_id ) ] ?? null;
}

function get_post_type( $post_id = 0 ) {
	$post = get_post( $post_id );

	return $post ? $post->post_type : false;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$post_id = absint( $post_id );
	$key     = (string) $key;
	$GLOBALS['p1_meta_reads'][] = array( $post_id, $key );
	$value = $GLOBALS['p1_meta'][ $post_id ][ $key ] ?? ( $single ? '' : array() );

	return $single ? $value : array( $value );
}

function update_post_meta( $post_id, $key, $value ) {
	$post_id = absint( $post_id );
	$GLOBALS['p1_meta'][ $post_id ][ (string) $key ] = $value;
	$GLOBALS['p1_writes'][] = array( 'update_post_meta', $post_id, (string) $key, $value );

	return true;
}

function delete_post_meta( $post_id, $key ) {
	$post_id = absint( $post_id );
	unset( $GLOBALS['p1_meta'][ $post_id ][ (string) $key ] );
	$GLOBALS['p1_writes'][] = array( 'delete_post_meta', $post_id, (string) $key );

	return true;
}

function wp_seed_events_dynamic_data_invalidate_event_cache( $event_id ) {
	$GLOBALS['p1_invalidated_ids'][] = absint( $event_id );
}

function get_posts( $args = array() ) {
	unset( $args );

	return $GLOBALS['p1_event_ids'];
}

function get_the_title( $post_id = 0 ) {
	$post = get_post( $post_id );

	return $post ? $post->post_title : '';
}

function get_permalink( $post_id = 0 ) {
	return 'https://example.test/events/event-' . absint( $post_id ) . '/';
}

function wp_seed_events_get_event_occurrences( $event_id, $args = array() ) {
	unset( $event_id, $args );

	return array();
}

function wp_seed_events_get_next_active_occurrence( $event_id ) {
	unset( $event_id );

	return array();
}

function wp_seed_events_get_last_active_occurrence( $event_id ) {
	unset( $event_id );

	return array();
}

function wp_seed_events_get_event_lifecycle( $event_id ) {
	unset( $event_id );

	return 'undated';
}

function wp_seed_events_get_event_media( $event_id ) {
	unset( $event_id );

	return array(
		'featured_image'        => null,
		'communication_visual'  => null,
		'communication_visuals' => array(),
		'other_visuals'         => array(),
		'event_document'        => null,
	);
}

function wp_seed_events_public_event_place_data( $event_id ) {
	unset( $event_id );

	return array();
}

function wp_seed_events_resolve_short_description( string $description, string $short_description = '', int $word_limit = 40 ): string {
	unset( $word_limit );
	return '' !== trim( $short_description ) ? $short_description : sanitize_text_field( $description );
}

function wp_seed_events_event_type_labels_for_event( $event_id ) {
	unset( $event_id );

	return array( 'Atelier' );
}

function wp_seed_events_contact_roles() {
	return array(
		'organizer' => 'Organisateur',
		'speaker'   => 'Intervenant',
		'contact'   => 'Contact',
	);
}

function wp_seed_events_contact_role_keys( $contact, $available_roles ) {
	$raw_roles = isset( $contact['roles'] ) && is_array( $contact['roles'] )
		? $contact['roles']
		: array( $contact['role'] ?? '' );
	$roles = array();

	foreach ( $raw_roles as $raw_role ) {
		$role = sanitize_key( $raw_role );

		if ( isset( $available_roles[ $role ] ) && ! in_array( $role, $roles, true ) ) {
			$roles[] = $role;
		}
	}

	return $roles;
}

function wp_seed_events_contact_role_labels( $contact, $roles ) {
	return array_map(
		function ( $role_key ) use ( $roles ) {
			return $roles[ $role_key ];
		},
		wp_seed_events_contact_role_keys( $contact, $roles )
	);
}

function wp_seed_events_stored_people() {
	return $GLOBALS['p1_people'];
}

function wp_seed_events_save_people( $people ) {
	$GLOBALS['p1_people']   = $people;
	$GLOBALS['p1_writes'][] = array( 'save_people', $people );
}

function wp_seed_events_normalize_reusable_label( $label ) {
	return strtolower( trim( remove_accents( (string) $label ) ) );
}

function wp_seed_events_person_key_from_name( $person_name, $existing_people = array() ) {
	$base = sanitize_key( sanitize_title( $person_name ) );

	if ( '' === $base ) {
		$base = 'personne';
	}

	$key   = $base;
	$index = 2;

	while ( isset( $existing_people[ $key ] ) ) {
		$key = $base . '-' . $index;
		$index++;
	}

	return $key;
}

require_once dirname( __DIR__ ) . '/includes/public/event-data.php';
require_once dirname( __DIR__ ) . '/includes/public/people.php';

function wp_seed_events_sanitize_person( $person, $person_key = '' ) {
	$name        = sanitize_text_field( $person['name'] ?? '' );
	$coordinates = wp_seed_events_normalize_person_coordinates( $person );

	if ( '' === $name ) {
		return array();
	}

	return array(
		'person_key'    => sanitize_key( '' !== $person_key ? $person_key : ( $person['person_key'] ?? '' ) ),
		'name'          => $name,
		'phone'         => $coordinates['phone'],
		'email'         => $coordinates['email'],
		'link'          => $coordinates['link'],
		'website_label' => wp_seed_events_normalize_person_website_label( $person['website_label'] ?? '' ),
	);
}

$main_source = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );

if ( false === $main_source ) {
	throw new RuntimeException( 'Unable to read wp-seed-events.php' );
}

eval( p1_extract_function( $main_source, 'wp_seed_events_update_person_in_events', 'wp_seed_events_people_admin_url' ) );
eval( p1_extract_function( $main_source, 'wp_seed_events_save_contacts', 'wp_seed_events_media_fields' ) );

foreach ( array( true, 1, '1' ) as $value ) {
	p1_assert( wp_seed_events_contact_publication_is_authorized( $value ), 'strict authorized value rejected' );
}

foreach ( array( false, 0, '0', null, '', 'true', 'yes', 2, array( '1' ) ) as $value ) {
	p1_assert( ! wp_seed_events_contact_publication_is_authorized( $value ), 'permissive publication value accepted' );
}

p1_same( 'person@example.test', wp_seed_events_normalize_person_email( ' person@example.test ' ), 'valid email normalization' );
p1_same( '', wp_seed_events_normalize_person_email( 'not-an-email' ), 'invalid email rejected' );
p1_same( '+32 (0) 495 12 34 56', wp_seed_events_normalize_person_phone( ' +32 (0) 495 12 34 56 ' ), 'valid display phone retained' );
p1_same( '', wp_seed_events_normalize_person_phone( '12345' ), 'short phone rejected' );
p1_same( '', wp_seed_events_normalize_person_phone( '+12 abc 345678' ), 'phone letters rejected' );
p1_same( '', wp_seed_events_normalize_person_phone( '1234567890123456' ), 'long phone rejected' );
p1_same( 'https://person.example.test/profile', wp_seed_events_normalize_person_link( ' https://person.example.test/profile ' ), 'HTTPS link normalized' );
p1_same( 'Découvrir le site', wp_seed_events_normalize_person_website_label( "  Découvrir\n le site  " ), 'website label normalized' );
p1_same( 'Découvrir le site', wp_seed_events_person_website_label( array( 'website_label' => 'Découvrir le site' ), 'https://person.example.test/' ), 'website label preferred' );
p1_same( 'https://person.example.test/', wp_seed_events_person_website_label( array(), 'https://person.example.test/' ), 'historical URL fallback missing' );
p1_same( 'none', wp_seed_events_normalize_contact_phone_action( 'none' ), 'none phone action rejected' );
p1_same( 'call', wp_seed_events_normalize_contact_phone_action( 'call' ), 'call phone action rejected' );
p1_same( 'sms', wp_seed_events_normalize_contact_phone_action( 'sms' ), 'sms phone action rejected' );
p1_same( 'call', wp_seed_events_contact_phone_action( array() ), 'historical phone action fallback changed' );
p1_same( 'none', wp_seed_events_contact_phone_action( array( 'phone_action' => 'none' ) ), 'explicit none phone action changed' );
p1_same( null, wp_seed_events_contact_phone_action_for_storage( array(), array(), false ), 'phone action stored without a phone' );
p1_same( 'none', wp_seed_events_contact_phone_action_for_storage( array( 'phone' => '+32 470 11 22 33' ), array(), false ), 'new association default is not none' );
p1_same( null, wp_seed_events_contact_phone_action_for_storage( array( 'phone' => '+32 470 11 22 33' ), array(), true ), 'historical absence was migrated' );
p1_same( 'sms', wp_seed_events_contact_phone_action_for_storage( array( 'phone' => '+32 470 11 22 33', 'phone_action' => 'sms' ), array(), false ), 'explicit SMS was not stored' );

foreach ( array( '/relative', 'javascript:alert(1)', 'data:text/plain,x', 'file:///tmp/x', 'mailto:a@example.test', 'tel:+32123456' ) as $unsafe_url ) {
	p1_same( '', wp_seed_events_normalize_person_link( $unsafe_url ), 'unsafe person link accepted: ' . $unsafe_url );
}

$base_contact = array(
	'name'  => 'Person A',
	'phone' => '+32 495 12 34 56',
	'email' => 'person@example.test',
	'link'  => 'https://person.example.test/',
);

for ( $mask = 0; $mask < 8; $mask++ ) {
	$contact = array_merge(
		$base_contact,
		array(
			'publish_email' => (bool) ( $mask & 1 ),
			'publish_phone' => (bool) ( $mask & 2 ),
			'publish_link'  => (bool) ( $mask & 4 ),
		)
	);
	$state = wp_seed_events_contact_publication_state( $contact );
	p1_same( (bool) ( $mask & 1 ), $state['publish_email'], 'email state mismatch for mask ' . $mask );
	p1_same( (bool) ( $mask & 2 ), $state['publish_phone'], 'phone state mismatch for mask ' . $mask );
	p1_same( (bool) ( $mask & 4 ), $state['publish_link'], 'link state mismatch for mask ' . $mask );
}

p1_same(
	array(
		'publish_phone' => false,
		'publish_email' => false,
		'publish_link'  => false,
	),
	wp_seed_events_contact_publication_state( $base_contact ),
	'historical association is not private by default'
);

p1_same(
	array(
		'publish_phone' => false,
		'publish_email' => false,
		'publish_link'  => false,
	),
	wp_seed_events_contact_publication_state(
		array(
			'name'          => 'Invalid',
			'phone'         => '123',
			'email'         => 'invalid',
			'link'          => 'javascript:alert(1)',
			'publish_phone' => true,
			'publish_email' => true,
			'publish_link'  => true,
		)
	),
	'invalid coordinates remain publishable'
);

$new_publication = wp_seed_events_normalize_contact_publication_for_storage(
	array_merge(
		$base_contact,
		array(
			'publish_phone' => '1',
			'publish_email' => 1,
			'publish_link'  => true,
		)
	)
);
p1_assert( true === $new_publication['publish_phone'] && true === $new_publication['publish_email'] && true === $new_publication['publish_link'], 'new explicit publication flags rejected' );

$new_default_publication = wp_seed_events_normalize_contact_publication_for_storage( $base_contact );
p1_assert( true === $new_default_publication['publish_phone'] && true === $new_default_publication['publish_email'] && true === $new_default_publication['publish_link'], 'new association is not public by default' );

$new_private_publication = wp_seed_events_normalize_contact_publication_for_storage(
	array_merge( $base_contact, array( 'publish_phone' => '0', 'publish_email' => '0', 'publish_link' => '0' ) )
);
p1_assert( false === $new_private_publication['publish_phone'] && false === $new_private_publication['publish_email'] && false === $new_private_publication['publish_link'], 'new association explicit privacy was ignored' );

$existing_contact = array_merge(
	$base_contact,
	array(
		'person_key'    => 'person-a',
		'publish_phone' => true,
		'publish_email' => true,
		'publish_link'  => true,
	)
);

$historical_private = array_merge( $base_contact, array( 'person_key' => 'private' ) );
$preserved_private  = wp_seed_events_normalize_contact_publication_for_storage( $historical_private, $historical_private, true );
p1_assert( false === $preserved_private['publish_phone'] && false === $preserved_private['publish_email'] && false === $preserved_private['publish_link'], 'historical private association became public' );

$submitted_without_flags = $existing_contact;
unset( $submitted_without_flags['publish_phone'], $submitted_without_flags['publish_email'], $submitted_without_flags['publish_link'] );
$preserved_public = wp_seed_events_normalize_contact_publication_for_storage( $submitted_without_flags, $existing_contact, true );
p1_assert( true === $preserved_public['publish_phone'] && true === $preserved_public['publish_email'] && true === $preserved_public['publish_link'], 'unrelated edit changed public flags' );

$without_email = array_merge( $existing_contact, array( 'email' => '' ) );
unset( $without_email['publish_email'] );
$with_new_email = array_merge( $without_email, array( 'email' => 'added@example.test' ) );
$added_coordinate = wp_seed_events_normalize_contact_publication_for_storage( $with_new_email, $without_email, true );
p1_same( true, $added_coordinate['publish_email'], 'new coordinate on existing association is not public by default' );
$changed_publication = wp_seed_events_normalize_contact_publication_for_storage(
	array_merge( $existing_contact, array( 'email' => 'new@example.test' ) ),
	$existing_contact,
	true
);
p1_same( false, $changed_publication['publish_email'], 'changed email did not revoke email publication' );
p1_same( true, $changed_publication['publish_phone'], 'changed email revoked phone publication' );
p1_same( true, $changed_publication['publish_link'], 'changed email revoked link publication' );

$removed_phone = wp_seed_events_normalize_contact_publication_for_storage(
	array_merge( $existing_contact, array( 'phone' => '', 'publish_phone' => true ) ),
	$existing_contact,
	true
);
p1_same( '', $removed_phone['phone'], 'removed phone retained a value' );
p1_same( false, $removed_phone['publish_phone'], 'removed phone remained public' );

$spacing_only = wp_seed_events_normalize_contact_publication_for_storage(
	array_merge( $existing_contact, array( 'phone' => '+32  495 12 34 56' ) ),
	$existing_contact,
	true
);
p1_same( true, $spacing_only['publish_phone'], 'equivalent normalized phone revoked publication' );

p1_assert( wp_seed_events_people_submission_has_complete_payload( array( 'wp_seed_event_people_changed' => '1', 'wp_seed_event_people_payload_present' => '1' ) ), 'complete payload rejected' );
foreach (
	array(
		array(),
		array( 'wp_seed_event_people_changed' => '1' ),
		array( 'wp_seed_event_people_payload_present' => '1' ),
		array( 'wp_seed_event_people_changed' => '0', 'wp_seed_event_people_payload_present' => '1' ),
		array( 'wp_seed_event_people_changed' => array( '1' ), 'wp_seed_event_people_payload_present' => '1' )
	) as $incomplete_payload
) {
	p1_assert( ! wp_seed_events_people_submission_has_complete_payload( $incomplete_payload ), 'incomplete payload accepted' );
}

p1_reset_runtime();
$GLOBALS['p1_meta'][300]['_wp_seed_event_contacts'] = array(
	array_merge( $base_contact, array( 'name' => 'Historical', 'roles' => array( 'organizer' ), 'person_key' => 'historical' ) ),
	array_merge(
		$base_contact,
		array(
			'name'          => 'Public',
			'roles'         => array( 'speaker', 'contact' ),
			'person_key'    => 'public',
			'phone'         => '+32 470 11 22 33',
			'email'         => 'public@example.test',
			'link'          => 'https://public.example.test/',
			'publish_phone' => 1,
			'publish_email' => '1',
			'publish_link'  => true,
		)
	),
	array( 'name' => 'No coordinates', 'roles' => array() ),
	array( 'name' => '   ', 'phone' => '+32 499 00 00 00', 'publish_phone' => true ),
);
$GLOBALS['p1_people']['public'] = array(
	'person_key'    => 'public',
	'name'          => 'Public',
	'phone'         => '+32 470 11 22 33',
	'email'         => 'public@example.test',
	'link'          => 'https://public.example.test/',
	'website_label' => 'Découvrir mon site',
);
$people = wp_seed_events_public_event_people_data( 300 );
p1_same( 3, count( $people ), 'invalid empty-name person was not filtered' );
p1_same( 'Historical', $people[0]['name'], 'people order changed' );
p1_same( array( 'organizer' ), $people[0]['role_keys'], 'role keys changed' );
p1_same( array( 'Organisateur' ), $people[0]['roles'], 'role labels changed' );
p1_same( '', $people[0]['public_phone'], 'historical phone leaked' );
p1_same( '', $people[0]['public_email'], 'historical email leaked' );
p1_same( '', $people[0]['public_url'], 'historical link leaked' );
p1_same( '', $people[0]['phone'], 'historical phone alias leaked' );
p1_same( '', $people[0]['email'], 'historical email alias leaked' );
p1_same( '', $people[0]['link'], 'historical link alias leaked' );
p1_same( '+32 470 11 22 33', $people[1]['public_phone'], 'authorized phone missing' );
p1_same( 'public@example.test', $people[1]['public_email'], 'authorized email missing' );
p1_same( 'https://public.example.test/', $people[1]['public_url'], 'authorized link missing' );
p1_same( 'https://public.example.test/', $people[1]['website_url'], 'canonical website URL missing' );
p1_same( 'Découvrir mon site', $people[1]['website_label'], 'canonical website label missing' );
p1_same( true, $people[1]['phone_public'], 'public phone state missing' );
p1_same( 'call', $people[1]['phone_action'], 'historical phone action fallback missing from Event Data' );
p1_same( $people[1]['public_phone'], $people[1]['phone'], 'phone compatibility alias differs' );
p1_same( $people[1]['public_email'], $people[1]['email'], 'email compatibility alias differs' );
p1_same( $people[1]['public_url'], $people[1]['link'], 'link compatibility alias differs' );
p1_assert( ! array_key_exists( 'person_key', $people[1] ), 'person_key exposed publicly' );
foreach ( array_keys( $people[1] ) as $public_key ) {
	p1_assert( 0 !== strpos( $public_key, 'publish_' ), 'publication flag exposed publicly' );
}

$shared_person = array_merge(
	$base_contact,
	array(
		'person_key'    => 'public',
		'name'          => 'Public',
		'phone'         => '+32 470 11 22 33',
		'publish_phone' => true,
	)
);
$GLOBALS['p1_meta'][301]['_wp_seed_event_contacts'] = array(
	array_merge( $shared_person, array( 'phone_action' => 'call', 'publish_email' => false, 'publish_phone' => true, 'publish_link' => false ) ),
);
$GLOBALS['p1_meta'][302]['_wp_seed_event_contacts'] = array(
	array_merge( $shared_person, array( 'phone_action' => 'sms', 'publish_email' => true, 'publish_phone' => false, 'publish_link' => true ) ),
);
$event_a_people = wp_seed_events_public_event_people_data( 301 );
$event_b_people = wp_seed_events_public_event_people_data( 302 );
p1_same( 'call', $event_a_people[0]['phone_action'], 'event A phone action differs' );
p1_same( 'sms', $event_b_people[0]['phone_action'], 'event B phone action differs' );
p1_same( '', $event_a_people[0]['public_email'], 'event A inherited event B email publication' );
p1_same( 'public@example.test', $event_b_people[0]['public_email'], 'event B lost its email publication' );
p1_same( '+32 470 11 22 33', $event_a_people[0]['public_phone'], 'event A lost its phone publication' );
p1_same( '', $event_b_people[0]['public_phone'], 'event B inherited event A phone publication' );
p1_same( '', $event_a_people[0]['public_url'], 'event A inherited event B website publication' );
p1_same( 'https://public.example.test/', $event_b_people[0]['public_url'], 'event B lost its website publication' );
p1_same( array(), $people[2]['roles'], 'person without role changed' );
p1_same( '', $people[2]['public_email'], 'person without coordinates has public email' );

$GLOBALS['p1_posts'][300] = (object) array(
	'ID'           => 300,
	'post_type'    => 'wp_seed_event',
	'post_status'  => 'publish',
	'post_title'   => 'Public event',
	'post_name'    => 'public-event',
	'post_content' => 'Description',
);
$GLOBALS['p1_posts'][301] = (object) array(
	'ID'           => 301,
	'post_type'    => 'wp_seed_event',
	'post_status'  => 'draft',
	'post_title'   => 'Draft event',
	'post_content' => 'Private draft',
);
$GLOBALS['p1_meta'][301]['_wp_seed_event_contacts'] = array(
	array_merge( $base_contact, array( 'publish_email' => true ) ),
);
$event_data = wp_seed_events_get_event_data( 300 );
p1_same( 3, count( $event_data['people'] ), 'published Event Data people mismatch' );
$event_json = json_encode( $event_data );
p1_assert( false === strpos( $event_json, 'person@example.test' ), 'private email appears in Event Data' );
p1_assert( false === strpos( $event_json, '+32 495 12 34 56' ), 'private phone appears in Event Data' );
p1_same( array(), wp_seed_events_get_event_data( 301 ), 'draft Event Data was exposed' );

p1_reset_runtime();
$GLOBALS['p1_posts'][200] = (object) array( 'post_type' => 'wp_seed_event', 'post_status' => 'draft', 'post_title' => 'Event' );
$raw_historical = array(
	array(
		'person_key' => 'historical',
		'name'       => 'Historical',
		'phone'      => '+32 495 12 34 56',
		'email'      => 'person@example.test',
		'link'       => 'https://person.example.test/',
		'roles'      => array( 'organizer' ),
	)
);
$GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] = $raw_historical;
$before = serialize( $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] );
$_POST = array(
	'wp_seed_events_contacts_nonce'        => 'valid',
	'wp_seed_event_people_changed'         => '0',
	'wp_seed_event_people_payload_present' => '1',
);
wp_seed_events_save_contacts( 200 );
p1_same( $before, serialize( $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] ), 'neutral save changed raw historical meta' );
p1_same( array(), $GLOBALS['p1_meta_reads'], 'neutral save read contacts' );
p1_same( array(), $GLOBALS['p1_writes'], 'neutral save wrote contacts' );

$guard_requests = array(
	array( 'wp_seed_events_contacts_nonce' => 'valid' ),
	array( 'wp_seed_events_contacts_nonce' => 'valid', 'wp_seed_event_people_changed' => '1' ),
	array( 'wp_seed_events_contacts_nonce' => 'valid', 'wp_seed_event_people_payload_present' => '1' ),
	array(
		'wp_seed_events_contacts_nonce'        => 'valid',
		'wp_seed_event_people_changed'         => '0',
		'wp_seed_event_people_payload_present' => '1',
		'wp_seed_events_contacts'              => array( array( 'name' => 'Partial' ) ),
	),
);
foreach ( $guard_requests as $guard_request ) {
	$GLOBALS['p1_meta_reads'] = array();
	$GLOBALS['p1_writes']     = array();
	$_POST                    = $guard_request;
	wp_seed_events_save_contacts( 200 );
	p1_same( array(), $GLOBALS['p1_meta_reads'], 'guarded save read contacts' );
	p1_same( array(), $GLOBALS['p1_writes'], 'guarded save wrote contacts' );
	p1_same( $before, serialize( $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] ), 'guarded save changed contacts' );
}

$GLOBALS['p1_meta_reads'] = array();
$GLOBALS['p1_writes']     = array();
$_POST = array(
	'wp_seed_events_contacts_nonce'        => 'invalid',
	'wp_seed_event_people_changed'         => '1',
	'wp_seed_event_people_payload_present' => '1',
);
wp_seed_events_save_contacts( 200 );
p1_same( array(), $GLOBALS['p1_meta_reads'], 'invalid nonce read contacts' );
p1_same( array(), $GLOBALS['p1_writes'], 'invalid nonce wrote contacts' );

$GLOBALS['p1_meta_reads'] = array();
$GLOBALS['p1_writes']     = array();
$_POST = array(
	'wp_seed_events_contacts_nonce'        => 'valid',
	'wp_seed_event_people_changed'         => '1',
	'wp_seed_event_people_payload_present' => '1',
);
wp_seed_events_save_contacts( 200 );
p1_assert( ! isset( $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] ), 'explicit empty payload did not remove last contact' );
p1_same( 'delete_post_meta', $GLOBALS['p1_writes'][0][0], 'explicit empty payload did not delete contacts meta' );

for ( $mask = 0; $mask < 8; $mask++ ) {
	p1_reset_runtime();
	$GLOBALS['p1_posts'][200] = (object) array( 'post_type' => 'wp_seed_event', 'post_status' => 'draft', 'post_title' => 'Event' );
	$GLOBALS['p1_people']['person-a'] = array_merge( $base_contact, array( 'person_key' => 'person-a', 'website_label' => 'Découvrir mon site' ) );
	$_POST = array(
		'wp_seed_events_contacts_nonce'        => 'valid',
		'wp_seed_event_people_changed'         => '1',
		'wp_seed_event_people_payload_present' => '1',
		'wp_seed_events_contacts'              => array(
			array_merge(
				$base_contact,
				array(
					'person_key'    => 'person-a',
					'roles'         => array( 'organizer', 'organizer', 'unknown' ),
					'publish_email' => (bool) ( $mask & 1 ),
					'publish_phone' => (bool) ( $mask & 2 ),
					'publish_link'  => (bool) ( $mask & 4 ),
				)
			),
		),
	);
	wp_seed_events_save_contacts( 200 );
	$saved = $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'][0];
	p1_same( (bool) ( $mask & 1 ), $saved['publish_email'], 'saved email flag mismatch for mask ' . $mask );
	p1_same( (bool) ( $mask & 2 ), $saved['publish_phone'], 'saved phone flag mismatch for mask ' . $mask );
	p1_same( (bool) ( $mask & 4 ), $saved['publish_link'], 'saved link flag mismatch for mask ' . $mask );
	p1_same( array( 'organizer' ), $saved['roles'], 'role normalization mismatch' );
	p1_assert( ! array_key_exists( 'publish_email', $GLOBALS['p1_people']['person-a'] ), 'email authorization stored globally' );
	p1_assert( ! array_key_exists( 'publish_phone', $GLOBALS['p1_people']['person-a'] ), 'phone authorization stored globally' );
	p1_assert( ! array_key_exists( 'publish_link', $GLOBALS['p1_people']['person-a'] ), 'link authorization stored globally' );
	p1_assert( ! array_key_exists( 'link', $saved ), 'website URL duplicated in event association' );
	p1_assert( ! array_key_exists( 'website_label', $saved ), 'website label duplicated in event association' );
	p1_same( 'Découvrir mon site', $GLOBALS['p1_people']['person-a']['website_label'], 'event association save dropped the person website label' );
}

p1_reset_runtime();
$GLOBALS['p1_posts'][200] = (object) array( 'post_type' => 'wp_seed_event', 'post_status' => 'draft', 'post_title' => 'Event' );
$GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] = array( $existing_contact );
$_POST = array(
	'wp_seed_events_contacts_nonce'        => 'valid',
	'wp_seed_event_people_changed'         => '1',
	'wp_seed_event_people_payload_present' => '1',
	'wp_seed_events_contacts'              => array(
		array_merge(
			$existing_contact,
			array(
				'email'         => 'changed@example.test',
				'publish_email' => '1',
				'publish_phone' => '1',
				'publish_link'  => '1',
			)
		),
	),
);
wp_seed_events_save_contacts( 200 );
$saved = $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'][0];
p1_same( false, $saved['publish_email'], 'server accepted publication for changed email' );
p1_same( true, $saved['publish_phone'], 'server revoked unchanged phone' );
p1_same( true, $saved['publish_link'], 'server revoked unchanged link' );

$GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] = array( $existing_contact );
$GLOBALS['p1_meta_reads'] = array();
$GLOBALS['p1_writes']     = array();
$_POST['wp_seed_events_contacts'][0] = array_merge(
	$existing_contact,
	array(
		'link'          => '',
		'publish_email' => '1',
		'publish_phone' => '1',
		'publish_link'  => '1',
	)
);
wp_seed_events_save_contacts( 200 );
$saved = $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'][0];
p1_assert( ! array_key_exists( 'link', $saved ), 'removed link retained in association' );
p1_same( false, $saved['publish_link'], 'removed link remained public' );
p1_same( true, $saved['publish_email'], 'removed link revoked email' );
p1_same( true, $saved['publish_phone'], 'removed link revoked phone' );

p1_reset_runtime();
$GLOBALS['p1_posts'][200] = (object) array( 'post_type' => 'wp_seed_event', 'post_status' => 'draft', 'post_title' => 'Event' );
$_POST = array(
	'wp_seed_events_contacts_nonce'        => 'valid',
	'wp_seed_event_people_changed'         => '1',
	'wp_seed_event_people_payload_present' => '1',
	'wp_seed_events_contacts'              => array(
		array_merge( $base_contact, array( 'person_key' => 'same', 'name' => 'Duplicate', 'publish_email' => '0' ) ),
		array_merge( $base_contact, array( 'person_key' => 'same', 'name' => 'Duplicate', 'publish_email' => '1' ) ),
	),
);
wp_seed_events_save_contacts( 200 );
p1_same( 2, count( $GLOBALS['p1_meta'][200]['_wp_seed_event_contacts'] ), 'duplicate association was silently removed' );

p1_reset_runtime();
$GLOBALS['p1_event_ids'] = array( 10, 11 );
$GLOBALS['p1_meta'][10]['_wp_seed_event_contacts'] = array(
	array_merge(
		$existing_contact,
		array(
			'publish_email' => true,
			'publish_phone' => true,
			'publish_link'  => false,
		)
	),
);
$GLOBALS['p1_meta'][11]['_wp_seed_event_contacts'] = array(
	array_merge(
		$existing_contact,
		array(
			'publish_email' => false,
			'publish_phone' => false,
			'publish_link'  => true,
		)
	),
);
wp_seed_events_update_person_in_events(
	'person-a',
	array_merge(
		$existing_contact,
		array(
			'name'          => 'Updated name',
			'email'         => 'library-change@example.test',
			'website_label' => 'Canonical person label',
		)
	)
);
$first_propagated  = $GLOBALS['p1_meta'][10]['_wp_seed_event_contacts'][0];
$second_propagated = $GLOBALS['p1_meta'][11]['_wp_seed_event_contacts'][0];
p1_same( 'Updated name', $first_propagated['name'], 'library name did not propagate' );
p1_same( 'library-change@example.test', $first_propagated['email'], 'library email did not propagate' );
p1_same( false, $first_propagated['publish_email'], 'changed library email remained public' );
p1_same( true, $first_propagated['publish_phone'], 'library email change revoked first phone' );
p1_same( false, $first_propagated['publish_link'], 'library email change altered first link flag' );
p1_same( false, $second_propagated['publish_email'], 'library email change authorized second email' );
p1_same( false, $second_propagated['publish_phone'], 'library email change altered second phone flag' );
p1_same( true, $second_propagated['publish_link'], 'library email change altered second link flag' );
p1_assert( ! array_key_exists( 'website_label', $first_propagated ), 'person website label was duplicated into the event association' );
p1_same( array( 10, 11 ), $GLOBALS['p1_invalidated_ids'], 'person change did not invalidate associated Event Data caches' );

$GLOBALS['p1_meta'][12]['_wp_seed_event_contacts'] = array(
	array_merge( $base_contact, array( 'name' => 'Deleted from library', 'person_key' => 'deleted' ) ),
);
$GLOBALS['p1_people'] = array();
$deleted_library_snapshot = wp_seed_events_public_event_people_data( 12 );
p1_same( 'Deleted from library', $deleted_library_snapshot[0]['name'], 'library deletion removed event snapshot' );
p1_same( '', $deleted_library_snapshot[0]['phone'], 'library deletion published historical phone' );

$save_source     = p1_extract_function( $main_source, 'wp_seed_events_save_contacts', 'wp_seed_events_media_fields' );
$marker_position = strpos( $save_source, 'wp_seed_events_people_submission_has_complete_payload' );
$meta_position   = strpos( $save_source, "get_post_meta( \$post_id, '_wp_seed_event_contacts'" );
p1_assert( false !== $marker_position && false !== $meta_position && $marker_position < $meta_position, 'save guard does not precede contacts read' );
p1_same( 1, substr_count( $main_source, 'name="wp_seed_event_people_changed"' ), 'changed marker is not rendered exactly once' );
p1_same( 1, substr_count( $main_source, 'name="wp_seed_event_people_payload_present"' ), 'payload marker is not rendered exactly once' );
p1_same( 3, substr_count( $main_source, 'markChanged(peopleRoot);' ), 'dirty marker must be limited to save, reorder and remove actions' );

foreach (
	array(
		'<legend>Identité</legend>',
		'<legend>Coordonnées</legend>',
		'<legend>Site</legend>',
		'Les choix de publication sont propres à cet événement.',
		'Afficher cet email sur la fiche publique de cet événement',
		'Afficher ce téléphone sur la fiche publique de cet événement',
		'Afficher ce site sur la fiche publique de cet événement',
		'aria-live="polite"',
		':focus-visible'
	) as $required_ui_contract
) {
	p1_assert( false !== strpos( $main_source, $required_ui_contract ), 'admin UI contract missing: ' . $required_ui_contract );
}

$sanitize_person_source = p1_extract_function( $main_source, 'wp_seed_events_sanitize_person', 'wp_seed_events_stored_people' );
p1_assert( false === strpos( $sanitize_person_source, 'publish_' ), 'global people sanitizer contains authorization flags' );
p1_assert( false !== strpos( $sanitize_person_source, "'website_label'" ), 'global people sanitizer does not store website_label' );
p1_assert( false !== strpos( $main_source, 'name="wp_seed_person_website_label"' ), 'people admin does not expose website_label' );
p1_assert( false === strpos( $save_source, "'website_label'    =>" ), 'event association stores website_label' );

$people_source            = file_get_contents( dirname( __DIR__ ) . '/includes/public/people.php' );
$public_projection_start  = strpos( $people_source, 'function wp_seed_events_public_event_people_data(' );
$public_projection_source = false === $public_projection_start ? '' : substr( $people_source, $public_projection_start );
p1_assert( false === strpos( $public_projection_source, "'person_key' =>" ), 'public projection exposes person_key' );
p1_assert( false === strpos( $public_projection_source, "'publish_email' =>" ), 'public projection exposes publish_email' );
p1_assert( false === strpos( $public_projection_source, "'publish_phone' =>" ), 'public projection exposes publish_phone' );
p1_assert( false === strpos( $public_projection_source, "'publish_link' =>" ), 'public projection exposes publish_link' );

$rendering_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$template_source  = file_get_contents( dirname( __DIR__ ) . '/templates/event-single.php' );
p1_assert( false === strpos( $rendering_source, '_wp_seed_event_contacts' ), 'shortcodes read raw contacts meta' );
p1_assert( false === strpos( $template_source, '_wp_seed_event_contacts' ), 'native template reads raw contacts meta' );
p1_assert( false !== strpos( $rendering_source, '$event[\'people\']' ), 'shortcodes no longer consume Event Data people' );
p1_assert( false !== strpos( $template_source, 'wp_seed_events_render_public_event_people_section(' ), 'native template does not delegate to the public people renderer' );
p1_assert( false === strpos( $template_source, '$event[\'people\']' ), 'native template reads Event Data people directly' );

$people_admin_source = p1_extract_function( $main_source, 'wp_seed_events_render_people_admin_page', 'wp_seed_events_handle_people_admin_form' );
p1_assert( false !== strpos( $people_admin_source, "current_user_can( 'edit_posts' )" ), 'people library capability changed without permission audit' );

echo 'People publication harness: ' . $GLOBALS['p1_assertions'] . " assertions passed.\n";
