<?php
/** Standalone assertions for the Divi event-result condition adapter. */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_SEED_EVENTS_VERSION', 'test' );

$GLOBALS['event_condition_filters'] = array();
$GLOBALS['event_condition_actions'] = array();
$GLOBALS['event_condition_count']   = 0;
$GLOBALS['event_condition_queries'] = array();
$GLOBALS['event_condition_results'] = array();
$GLOBALS['event_condition_types']   = array(
	'atelier'  => 'Atelier',
	'rencontre' => 'Rencontre',
);
$GLOBALS['event_condition_terms']   = array(
	'atelier'              => null,
	'rencontre'             => null,
	'journee-decouverte'    => null,
);

class WP_Term {
	public $term_id;
	public $name;
	public $slug;
	public function __construct( $term_id, $name, $slug ) {
		$this->term_id = $term_id;
		$this->name    = $name;
		$this->slug    = $slug;
	}
}
$GLOBALS['event_condition_terms']['atelier']           = new WP_Term( 11, 'Atelier', 'atelier' );
$GLOBALS['event_condition_terms']['rencontre']          = new WP_Term( 12, 'Rencontre', 'rencontre' );
$GLOBALS['event_condition_terms']['journee-decouverte'] = new WP_Term( 99, 'Journée découverte', 'journee-decouverte' );

function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['event_condition_filters'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['event_condition_actions'][ $hook ][] = compact( 'callback', 'priority', 'accepted_args' );
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
}

function absint( $value ) {
	return abs( (int) $value );
}

function is_wp_error( $value ) {
	return false;
}

function get_terms() {
	return array();
}

function get_term_by( $field, $value ) {
	return 'slug' === $field ? ( $GLOBALS['event_condition_terms'][ $value ] ?? false ) : false;
}

function wp_seed_events_event_type_taxonomy() {
	return 'wp_seed_event_type';
}

function wp_seed_events_event_type_options() {
	return $GLOBALS['event_condition_types'];
}

function wp_seed_events_native_event_type_slug( $key ) {
	return str_replace( '_', '-', sanitize_key( $key ) );
}

function plugins_url( $path ) {
	return $path;
}

function wp_seed_events_divi_flatten_term_values( $values ) {
	return array_values( array_unique( array_map( 'strval', is_array( $values ) ? $values : array( $values ) ) ) );
}

function wp_seed_events_divi_apply_collection_query( $args, $orderby, $controls ) {
	$GLOBALS['event_condition_queries'][] = compact( 'args', 'orderby', 'controls' );
	$args['post__in'] = array( 101, 102 );
	return $args;
}

function get_posts( $args ) {
	$GLOBALS['event_condition_last_get_posts'] = $args;
	return $GLOBALS['event_condition_results'];
}

require dirname( __DIR__ ) . '/includes/integrations/divi/event-results-condition.php';

function event_condition_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function event_condition_case( $label, $callback ) {
	$GLOBALS['event_condition_count']++;
	$callback();
	echo 'ok ' . $GLOBALS['event_condition_count'] . ' - ' . $label . PHP_EOL;
}

$settings = array(
	'eventStatus' => 'upcoming',
	'eventTypes'  => array( '13', '14' ),
	'eventPinned' => 'featured_only',
);

event_condition_case( 'official Divi condition hooks are registered', function () {
	event_condition_assert( 1 === count( $GLOBALS['event_condition_filters']['divi_module_options_conditions_is_custom_condition_true'] ?? array() ), 'Evaluator hook missing.' );
	event_condition_assert( 1 === count( $GLOBALS['event_condition_actions']['divi_visual_builder_assets_before_enqueue_scripts'] ?? array() ), 'Builder asset hook missing.' );
} );

event_condition_case( 'two matching results evaluate true', function () use ( $settings ) {
	$GLOBALS['event_condition_results'] = array( 101, 102 );
	event_condition_assert( wp_seed_events_divi_event_results_condition_has_results( $settings ), 'Two results evaluated false.' );
} );

event_condition_case( 'one matching result evaluates true', function () use ( $settings ) {
	$GLOBALS['event_condition_results'] = array( 101 );
	event_condition_assert( wp_seed_events_divi_event_results_condition_has_results( $settings ), 'One result evaluated false.' );
} );

event_condition_case( 'zero matching results evaluate false', function () use ( $settings ) {
	$GLOBALS['event_condition_results'] = array();
	event_condition_assert( ! wp_seed_events_divi_event_results_condition_has_results( $settings ), 'Zero results evaluated true.' );
} );

event_condition_case( 'condition delegates all criteria to the shared adapter', function () {
	$call = end( $GLOBALS['event_condition_queries'] );
	event_condition_assert( 'wp_seed_event' === $call['args']['post_type'][0], 'Event post type missing.' );
	event_condition_assert( 'publish' === $call['args']['post_status'], 'Public visibility boundary missing.' );
	event_condition_assert( 'upcoming' === $call['args']['meta_query'][0]['value'], 'Temporal status diverged.' );
	event_condition_assert( array( '13', '14' ) === $call['controls']['types'], 'Event types diverged.' );
	event_condition_assert( 'featured_only' === $call['controls']['pinned'], 'Pinned filter diverged.' );
	event_condition_assert( 1 === $GLOBALS['event_condition_last_get_posts']['posts_per_page'], 'Existence query is not bounded.' );
} );

event_condition_case( 'other custom conditions remain untouched', function () {
	event_condition_assert(
		null === wp_seed_events_divi_evaluate_event_results_condition( null, 'otherCondition', array(), 'x' ),
		'Unrelated condition changed.'
	);
} );

event_condition_case( 'new choices use only the canonical active type registry', function () {
	$options = wp_seed_events_divi_event_results_condition_type_options();
	event_condition_assert(
		array( '11', '12' ) === array_column( $options, 'value' ),
		'A historical deleted taxonomy term remains selectable.'
	);
	event_condition_assert( array( 'Atelier', 'Rencontre' ) === array_column( $options, 'label' ), 'Canonical labels differ.' );
} );

event_condition_case( 'create appears immediately from the canonical registry', function () {
	$GLOBALS['event_condition_types']['conference'] = 'Conférence';
	$GLOBALS['event_condition_terms']['conference'] = new WP_Term( 13, 'Conférence', 'conference' );
	$options = wp_seed_events_divi_event_results_condition_type_options();
	event_condition_assert( '13' === end( $options )['value'], 'Created canonical type is missing.' );
	event_condition_assert( 'Conférence' === end( $options )['label'], 'Created canonical label differs.' );
} );

event_condition_case( 'rename updates the offered label without remapping the saved term ID', function () {
	$GLOBALS['event_condition_types']['rencontre'] = 'Cercle de rencontre';
	$options = wp_seed_events_divi_event_results_condition_type_options();
	event_condition_assert( '12' === $options[1]['value'], 'Rename changed the stable term ID.' );
	event_condition_assert( 'Cercle de rencontre' === $options[1]['label'], 'Rename was not reflected.' );
} );

event_condition_case( 'delete disappears while a historical saved term remains untouched', function () {
	unset( $GLOBALS['event_condition_types']['conference'] );
	$options = wp_seed_events_divi_event_results_condition_type_options();
	event_condition_assert( ! in_array( '13', array_column( $options, 'value' ), true ), 'Deleted type remains selectable.' );
	event_condition_assert( 13 === $GLOBALS['event_condition_terms']['conference']->term_id, 'Historical term identity was rewritten.' );
} );

event_condition_case( 'Visual Builder registers settings without frontend behavior', function () {
	$source = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/event-results-condition/visual-builder.js' );
	foreach ( array( 'conditionsStore', 'initialCustomItemEdit', 'customSettingsComponent' ) as $contract ) {
		event_condition_assert( false !== strpos( $source, $contract ), 'Builder hook missing: ' . $contract );
	}
	foreach ( array( 'DOMContentLoaded', 'MutationObserver', 'querySelector', 'display:none' ) as $forbidden ) {
		event_condition_assert( false === strpos( $source, $forbidden ), 'Frontend workaround found: ' . $forbidden );
	}
} );

echo 'Divi event results condition harness: ' . $GLOBALS['event_condition_count'] . '/' . $GLOBALS['event_condition_count'] . ' OK' . PHP_EOL;
