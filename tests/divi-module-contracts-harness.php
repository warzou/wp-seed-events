<?php
/** Shared Divi title and responsive list contracts. */

declare(strict_types=1);
define( 'ABSPATH', __DIR__ . '/' );

function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function wp_seed_events_public_boolean_option( $value, $default ) {
	if ( is_bool( $value ) ) return $value;
	if ( ! is_scalar( $value ) ) return $default;
	$value = strtolower( trim( (string) $value ) );
	return in_array( $value, array( '1', 'on', 'yes', 'true' ), true ) ? true : ( in_array( $value, array( '0', 'off', 'no', 'false' ), true ) ? false : $default );
}
function wp_seed_events_public_date_list_dimension_option( $value, $fallback ) {
	$value = strtolower( trim( (string) $value ) );
	return preg_match( '/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/', $value ) ? $value : $fallback;
}
function wp_seed_events_public_date_list_marker_color_option( $value ) {
	$value = trim( (string) $value );
	return preg_match( '/^#[0-9a-f]{3,8}$/i', $value ) ? $value : '';
}

require dirname( __DIR__ ) . '/includes/integrations/divi/module-contracts.php';

function contract_assert( $condition, $message ) {
	if ( ! $condition ) throw new RuntimeException( $message );
}

contract_assert( 'Historique' === wp_seed_events_divi_optional_title( array( 'title' => 'Historique' ), 'Défaut' ), 'Historical title was hidden.' );
contract_assert( '' === wp_seed_events_divi_optional_title( array( 'title' => '' ), 'Défaut' ), 'Historical empty title gained content.' );
contract_assert( '' === wp_seed_events_divi_optional_title( array( 'title' => 'Titre', 'show_title' => 'off' ), 'Défaut' ), 'Explicit title toggle off failed.' );
contract_assert( 'Titre' === wp_seed_events_divi_optional_title( array( 'title' => 'Titre', 'show_title' => 'on' ), 'Défaut' ), 'Explicit title toggle on failed.' );

$attrs = array(
	'eventListStyle' => array(
		'advanced' => array(
			'markerType' => array( 'desktop' => array( 'value' => 'square' ), 'tablet' => array( 'value' => array( 'markerType' => 'circle' ) ) ),
			'leftIndent' => array( 'desktop' => array( 'value' => '3em' ), 'phone' => array( 'value' => '1rem' ) ),
			'occurrenceGap' => array( 'desktop' => array( 'value' => '7px' ) ),
			'markerColor' => array( 'desktop' => array( 'value' => '#123456' ) ),
		),
	),
);
$styles = wp_seed_events_divi_list_style_values( $attrs, 'eventListStyle' );
contract_assert( 'square' === $styles['desktop']['markerType'], 'Desktop marker differs.' );
contract_assert( 'circle' === $styles['tablet']['markerType'], 'Tablet marker differs.' );
contract_assert( 'circle' === $styles['phone']['markerType'], 'Phone marker inheritance differs.' );
contract_assert( '1rem' === $styles['phone']['leftIndent'], 'Phone indentation differs.' );
contract_assert( '7px' === $styles['phone']['occurrenceGap'], 'Gap inheritance differs.' );
contract_assert( '#123456' === $styles['tablet']['markerColor'], 'Marker color inheritance differs.' );

$untouched = wp_seed_events_divi_list_style_values( array(), 'eventListStyle' );
foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
	contract_assert( 'none' === $untouched[ $breakpoint ]['markerType'], 'Untouched marker default differs on ' . $breakpoint . '.' );
	contract_assert( '0px' === $untouched[ $breakpoint ]['leftIndent'], 'Untouched indentation differs on ' . $breakpoint . '.' );
}

foreach ( array( 'none', 'disc', 'circle', 'square' ) as $marker_type ) {
	$explicit = wp_seed_events_divi_list_style_values(
		array(
			'eventListStyle' => array(
				'advanced' => array(
					'markerType' => array( 'desktop' => array( 'value' => $marker_type ) ),
				),
			),
		),
		'eventListStyle'
	);
	contract_assert( $marker_type === $explicit['desktop']['markerType'], 'Explicit marker was not preserved: ' . $marker_type . '.' );
}

$reset = wp_seed_events_divi_list_style_values(
	array( 'eventListStyle' => array( 'advanced' => array( 'markerType' => array( 'desktop' => array( 'value' => '' ) ) ) ) ),
	'eventListStyle'
);
contract_assert( 'none' === $reset['desktop']['markerType'], 'Reset marker did not return to none.' );

echo "Shared Divi module contracts: PASS\n";
