<?php

define( 'ABSPATH', __DIR__ );

$GLOBALS['contact_meta']    = array();
$GLOBALS['contact_options'] = array();

class WP_Error {
	public $code;
	public $data;
	public function __construct( $code, $message = '', $data = null ) {
		unset( $message );
		$this->code = $code;
		$this->data = $data;
	}
}
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function absint( $value ) { return abs( (int) $value ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function get_post_meta( $id ) { return $GLOBALS['contact_meta'][ $id ] ?? array(); }
function update_post_meta( $id, $key, $value ) { unset( $key ); $GLOBALS['contact_meta'][ $id ] = $value; return true; }
function update_option( $key, $value ) { $GLOBALS['contact_options'][ $key ] = $value; return true; }
function get_option( $key, $default = false ) { return $GLOBALS['contact_options'][ $key ] ?? $default; }
function delete_option( $key ) { unset( $GLOBALS['contact_options'][ $key ] ); return true; }
function get_posts() { return array_keys( $GLOBALS['contact_meta'] ); }
function wp_seed_events_contact_roles() { return array( 'organizer' => 'Organisateur', 'speaker' => 'Intervenant', 'contact' => 'Contact' ); }
function wp_seed_events_canonical_contact_role( $role ) {
	$role = sanitize_key( $role );
	return in_array( $role, array( 'registration_contact', 'information_contact' ), true ) ? 'contact' : $role;
}

require dirname( __DIR__ ) . '/includes/admin/contact-migration.php';

$passed = 0;
function contact_case( $name, $callback ) { global $passed; $callback(); $passed++; echo "ok {$passed} - {$name}\n"; }
function contact_same( $expected, $actual, $message ) { if ( $expected !== $actual ) { throw new RuntimeException( $message ); } }
function row( $role, $name = 'Claire' ) { return array( 'person_key' => strtolower( $name ), 'role' => $role, 'roles' => array( $role ), 'name' => $name, 'email' => '', 'phone' => '', 'link' => '' ); }

contact_case( 'A both empty', fn() => contact_same( 'A', wp_seed_events_contact_migration_classify( array() ), 'A failed' ) );
contact_case( 'B registration only', fn() => contact_same( 'B', wp_seed_events_contact_migration_classify( array( row( 'registration_contact' ) ) ), 'B failed' ) );
contact_case( 'C information only', fn() => contact_same( 'C', wp_seed_events_contact_migration_classify( array( row( 'information_contact' ) ) ), 'C failed' ) );
contact_case( 'D identical', fn() => contact_same( 'D', wp_seed_events_contact_migration_classify( array( row( 'registration_contact' ), row( 'information_contact' ) ) ), 'D failed' ) );
contact_case( 'E different', fn() => contact_same( 'E', wp_seed_events_contact_migration_classify( array( row( 'registration_contact', 'Claire' ), row( 'information_contact', 'David' ) ) ), 'E failed' ) );

contact_case( 'migration refuses E without writes', function () {
	$GLOBALS['contact_meta'] = array( 1 => array( row( 'registration_contact', 'Claire' ), row( 'information_contact', 'David' ) ) );
	$before = $GLOBALS['contact_meta'];
	$result = wp_seed_events_migrate_contacts_to_canonical( array( 1 ) );
	contact_same( 'contact_migration_ambiguous', $result->code, 'E was not rejected' );
	contact_same( $before, $GLOBALS['contact_meta'], 'E changed storage' );
} );

contact_case( 'migration is lossless, idempotent and rollbackable', function () {
	$GLOBALS['contact_meta'] = array( 2 => array( row( 'registration_contact' ), row( 'organizer', 'Alice' ) ) );
	$before = $GLOBALS['contact_meta'][2];
	$first  = wp_seed_events_migrate_contacts_to_canonical( array( 2 ) );
	contact_same( 1, $first['changed'], 'first migration did not change row' );
	contact_same( 'contact', $GLOBALS['contact_meta'][2][0]['role'], 'canonical role missing' );
	contact_same( row( 'organizer', 'Alice' ), $GLOBALS['contact_meta'][2][1], 'unrelated association changed' );
	$second = wp_seed_events_migrate_contacts_to_canonical( array( 2 ) );
	contact_same( 0, $second['changed'], 'migration is not idempotent' );
	wp_seed_events_rollback_contact_migration( array( 2 => $before ), $first['options_before'] );
	contact_same( $before, $GLOBALS['contact_meta'][2], 'rollback failed' );
	contact_same( array(), $GLOBALS['contact_options'], 'migration options were not rolled back' );
} );

echo "PASS {$passed}/{$passed}\n";
