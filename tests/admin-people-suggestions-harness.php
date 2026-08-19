<?php
/** Standalone contract for exhaustive, usage-sorted admin people suggestions. */

declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['suggestion_people'] = array(
	'zoe'   => array( 'person_key' => 'zoe', 'name' => 'Zoé', 'phone' => '', 'email' => '', 'link' => '' ),
	'emile' => array( 'person_key' => 'emile', 'name' => 'Émile', 'phone' => '', 'email' => '', 'link' => '' ),
	'alice' => array( 'person_key' => 'alice', 'name' => 'Alice', 'phone' => '', 'email' => '', 'link' => '' ),
	'bruno' => array( 'person_key' => 'bruno', 'name' => 'Bruno', 'phone' => '', 'email' => '', 'link' => '' ),
);
$GLOBALS['suggestion_events'] = array(
	10 => array(
		array( 'person_key' => 'zoe', 'roles' => array( 'speaker', 'organizer' ) ),
		array( 'person_key' => 'zoe', 'roles' => array( 'registration_contact' ) ),
		array( 'person_key' => 'alice' ),
	),
	11 => array( array( 'person_key' => 'zoe' ), array( 'person_key' => 'emile' ) ),
	12 => array( array( 'person_key' => 'alice' ) ),
);

function get_posts( $args ) {
	if ( -1 !== $args['posts_per_page'] || 'ids' !== $args['fields'] || 'any' !== $args['post_status'] ) {
		throw new RuntimeException( 'The usage query is not exhaustive.' );
	}
	return array_keys( $GLOBALS['suggestion_events'] );
}
function get_post_meta( $id ) { return $GLOBALS['suggestion_events'][ $id ] ?? array(); }
function wp_seed_events_contact_person_key( $contact ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) ( $contact['person_key'] ?? '' ) ) ); }
function wp_seed_events_people() { return $GLOBALS['suggestion_people']; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function remove_accents( $value ) { return (string) iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', (string) $value ); }

require dirname( __DIR__ ) . '/includes/admin/people-suggestions.php';

function suggestion_assert( $condition, $message ) {
	if ( ! $condition ) throw new RuntimeException( $message );
}

$usage = wp_seed_events_contact_usage_counts();
suggestion_assert( 2 === $usage['zoe'], 'Multiple roles or duplicate rows in one event were counted more than once.' );
suggestion_assert( 2 === $usage['alice'], 'Distinct event usage count differs.' );
suggestion_assert( 1 === $usage['emile'], 'Single association count differs.' );

$suggestions = wp_seed_events_get_contact_suggestions( 10 );
suggestion_assert( 4 === count( $suggestions ), 'The default suggestion list is truncated.' );
suggestion_assert( array( 'alice', 'zoe', 'emile', 'bruno' ) === array_column( $suggestions, 'person_key' ), 'Usage DESC and alphabetical tie-break differ.' );
suggestion_assert( 'bruno' === end( $suggestions )['person_key'], 'An unused person became inaccessible.' );
suggestion_assert( 2 === count( wp_seed_events_get_contact_suggestions( 10, 2 ) ), 'Explicit bounded callers no longer work.' );
suggestion_assert( 4 === count( array_unique( array_column( $suggestions, 'person_key' ) ) ), 'Duplicate suggestions were returned.' );

echo "Admin people suggestions: 8/8 OK\n";
