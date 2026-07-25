<?php
/**
 * Standalone assertions for the Divi 5 event people module.
 *
 * Run with: php tests/divi-people-module-harness.php
 */

declare(strict_types=1);

namespace ET\Builder\Framework\DependencyManagement\Interfaces {
	interface DependencyInterface {
	}
}

namespace ET\Builder\Framework\Utility {
	class HTMLUtility {
		public static function render( $args ) {
			return (string) ( $args['children'] ?? '' );
		}
	}
}

namespace ET\Builder\FrontEnd\Module {
	class Style {
		public static function add( $args ) {
		}
	}
}

namespace ET\Builder\Packages\Module {
	class Module {
		public static function render( $args ) {
			return (string) ( $args['children'] ?? '' );
		}
	}
}

namespace ET\Builder\Packages\Module\Options\Element {
	class ElementClassnames {
		public static function classnames( $args ) {
			return array();
		}
	}
}

namespace ET\Builder\Packages\ModuleLibrary {
	class ModuleRegistration {
		public static $registrations = array();

		public static function register_module( $path, $args ) {
			self::$registrations[] = array( $path, $args );
		}
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['p4_cases']          = 0;
	$GLOBALS['p4_data_calls']     = 0;
	$GLOBALS['p4_renderer_calls'] = 0;
	$GLOBALS['p4_events']         = array();
	$GLOBALS['p4_post_types']     = array( 10 => 'wp_seed_event', 11 => 'wp_seed_event', 12 => 'wp_seed_event', 13 => 'wp_seed_event', 14 => 'wp_seed_event', 20 => 'page' );
	$GLOBALS['p4_queried_id']     = 0;
	$GLOBALS['p4_current_id']     = 0;

	class WP_REST_Server {
		const READABLE = 'GET';
	}

	class WP_REST_Request {
		private $params;

		public function __construct( $params = array() ) {
			$this->params = $params;
		}

		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
		}
	}

	function add_action( $hook, $callback, $priority = 10 ) {
	}

	function register_rest_route( $namespace, $route, $args ) {
		$GLOBALS['p4_rest_route'] = array( $namespace, $route, $args );
	}

	function rest_ensure_response( $value ) {
		return $value;
	}

	function current_user_can( $capability, ...$args ) {
		return true;
	}

	function absint( $value ) {
		return abs( (int) $value );
	}

	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
	}

	function sanitize_text_field( $value ) {
		return trim( strip_tags( (string) $value ) );
	}

	function get_post_type( $post_id ) {
		return $GLOBALS['p4_post_types'][ (int) $post_id ] ?? false;
	}

	function get_queried_object_id() {
		return $GLOBALS['p4_queried_id'];
	}

	function get_the_ID() {
		return $GLOBALS['p4_current_id'];
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
		$value = trim( (string) $value );
		$parts = parse_url( $value );
		return is_array( $parts ) && in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true ) && ! empty( $parts['host'] ) ? esc_attr( $value ) : '';
	}

	function wp_seed_events_normalize_person_email( $value ) {
		$value = trim( (string) $value );
		return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ) ? $value : '';
	}

	function wp_seed_events_normalize_person_phone( $value ) {
		$value = trim( (string) preg_replace( '/\s+/u', ' ', (string) $value ) );
		$digits = preg_replace( '/\D+/', '', $value );
		return preg_match( '/^\+?[0-9\s().\/-]+$/u', $value ) && strlen( (string) $digits ) >= 6 && strlen( (string) $digits ) <= 15 ? $value : '';
	}

	function wp_seed_events_normalize_person_link( $value ) {
		$value = trim( (string) $value );
		$parts = parse_url( $value );
		return is_array( $parts ) && in_array( strtolower( (string) ( $parts['scheme'] ?? '' ) ), array( 'http', 'https' ), true ) && ! empty( $parts['host'] ) ? $value : '';
	}

	function wp_seed_events_get_event_data( $event_id ) {
		$GLOBALS['p4_data_calls']++;
		return $GLOBALS['p4_events'][ (int) $event_id ] ?? array();
	}

	require dirname( __DIR__ ) . '/includes/public/rendering.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/context.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/class-event-people-module.php';

	function p4_person( $name, $role_key, $role, $coordinates = array() ) {
		return array_merge(
			array(
				'name' => $name, 'role_keys' => array( $role_key ), 'roles' => array( $role ),
				'public_email' => '', 'public_phone' => '', 'public_url' => '',
				'email' => '', 'phone' => '', 'link' => '',
			),
			$coordinates
		);
	}

	$organizer = p4_person( 'Alice', 'organizer', 'Organisatrice' );
	$speaker   = p4_person( 'Bruno', 'speaker', 'Intervenant' );
	$register  = p4_person( 'Claire', 'registration_contact', 'Contact inscription' );
	$inform    = p4_person( 'David', 'information_contact', 'Contact information' );
	$public    = p4_person(
		'Emilie',
		'organizer',
		'Organisatrice',
		array(
			'public_email' => 'public@example.test', 'public_phone' => '+33 1 23 45 67 89',
			'public_url' => 'https://example.test/contact', 'email' => 'private@example.test',
			'phone' => '+33 9 99 99 99 99', 'link' => 'https://private.example.test/',
		)
	);
	$private = p4_person( 'Privee', 'speaker', 'Intervenante', array( 'email' => 'secret@example.test', 'phone' => '+33 8 88 88 88 88', 'link' => 'https://secret.example.test/' ) );

	$GLOBALS['p4_events'] = array(
		10 => array( 'id' => 10, 'people' => array( $organizer, $speaker, $register, $inform, $public, $private ) ),
		11 => array(),
		12 => array( 'id' => 12, 'people' => array() ),
		13 => array( 'id' => 13, 'people' => array( $organizer ) ),
		14 => array( 'id' => 14, 'people' => array( $speaker, $register, $organizer ) ),
	);

	function p4_assert( $condition, $message ) {
		if ( ! $condition ) {
			throw new RuntimeException( $message );
		}
	}

	function p4_case( $name, $callback ) {
		try {
			$callback();
			$GLOBALS['p4_cases']++;
			echo '[OK] ' . $name . PHP_EOL;
		} catch ( Throwable $error ) {
			fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
			exit( 1 );
		}
	}

	function p4_invoke( $method, ...$args ) {
		$reflection = new ReflectionMethod( WP_Seed_Events_Divi_Event_People_Module::class, $method );
		$reflection->setAccessible( true );
		return $reflection->invoke( null, ...$args );
	}

	function p4_render( $event_id, $options = array() ) {
		return p4_invoke( 'render_people', $event_id, $options );
	}

	function p4_contains( $needle, $value ) {
		return false !== strpos( (string) $value, (string) $needle );
	}

	$metadata = json_decode( file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/event-people-module/visual-builder/src/module.json' ), true );
	$source   = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/class-event-people-module.php' );
	$js       = file_get_contents( dirname( __DIR__ ) . '/includes/integrations/divi/event-people-module/visual-builder/src/index.jsx' );

	p4_case( '1 module registers', function () {
		WP_Seed_Events_Divi_Event_People_Module::register_module();
		p4_assert( 1 === count( \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::$registrations ), 'Registration count differs.' );
	} );
	p4_case( '2 canonical identifier', fn() => p4_assert( 'wp-seed-events/event-people' === WP_Seed_Events_Divi_Event_People_Module::MODULE_NAME, 'Wrong ID.' ) );
	p4_case( '3 French label', fn() => p4_assert( 'WP Seed — Personnes de l’événement' === $metadata['title'], 'Wrong label.' ) );
	p4_case( '4 valid event context', fn() => p4_assert( 10 === wp_seed_events_divi_resolve_event_id( array( 'post_id' => 10, 'post_type' => 'wp_seed_event' ) ), 'Valid context failed.' ) );
	p4_case( '5 implicit public context', function () { $GLOBALS['wp_seed_events_public_event_id'] = 10; p4_assert( 10 === wp_seed_events_divi_resolve_event_id(), 'Public context failed.' ); unset( $GLOBALS['wp_seed_events_public_event_id'] ); } );
	p4_case( '6 explicit valid post ID', fn() => p4_assert( 13 === wp_seed_events_divi_resolve_event_id( array( 'post_id' => 13 ) ), 'Explicit event failed.' ) );
	p4_case( '7 explicit invalid post ID', function () { $GLOBALS['wp_seed_events_public_event_id'] = 10; p4_assert( 0 === wp_seed_events_divi_resolve_event_id( array( 'post_id' => 999, 'strict_post' => true ) ), 'Invalid ID fell back.' ); unset( $GLOBALS['wp_seed_events_public_event_id'] ); } );
	p4_case( '8 explicit incompatible post type', fn() => p4_assert( 0 === wp_seed_events_divi_resolve_event_id( array( 'post_id' => 10, 'post_type' => 'page', 'strict_post' => true ) ), 'Incompatible type passed.' ) );
	p4_case( '9 ordinary page context', function () { $GLOBALS['wp_seed_events_public_event_id'] = 10; p4_assert( 0 === wp_seed_events_divi_resolve_event_id( array( 'post_id' => 20, 'post_type' => 'page', 'strict_post' => true ) ), 'Page fell back.' ); unset( $GLOBALS['wp_seed_events_public_event_id'] ); } );
	p4_case( '10 draft event returns empty Event Data', fn() => p4_assert( '' === p4_render( 11 ), 'Draft rendered.' ) );
	p4_case( '11 no people returns empty', fn() => p4_assert( '' === p4_render( 12 ), 'Empty people rendered.' ) );
	p4_case( '12 one person', fn() => p4_assert( 1 === substr_count( p4_render( 13 ), 'wp-seed-event-people__item' ), 'One person cardinality failed.' ) );
	p4_case( '13 multiple people', fn() => p4_assert( 6 === substr_count( p4_render( 10 ), 'wp-seed-event-people__item' ), 'Multiple people cardinality failed.' ) );
	p4_case( '14 order preserved', function () { $html = p4_render( 14 ); p4_assert( strpos( $html, 'Bruno' ) < strpos( $html, 'Claire' ) && strpos( $html, 'Claire' ) < strpos( $html, 'Alice' ), 'Order changed.' ); } );
	p4_case( '15 role all', fn() => p4_assert( 6 === substr_count( p4_render( 10, array( 'role' => 'all' ) ), 'wp-seed-event-people__item' ), 'All filter failed.' ) );
	p4_case( '16 organizer filter', fn() => p4_assert( 2 === substr_count( p4_render( 10, array( 'role' => 'organizer' ) ), 'wp-seed-event-people__item' ), 'Organizer failed.' ) );
	p4_case( '17 speaker filter', fn() => p4_assert( 2 === substr_count( p4_render( 10, array( 'role' => 'speaker' ) ), 'wp-seed-event-people__item' ), 'Speaker failed.' ) );
	p4_case( '18 registration filter', fn() => p4_assert( 1 === substr_count( p4_render( 10, array( 'role' => 'registration_contact' ) ), 'wp-seed-event-people__item' ), 'Registration failed.' ) );
	p4_case( '19 information filter', fn() => p4_assert( 1 === substr_count( p4_render( 10, array( 'role' => 'information_contact' ) ), 'wp-seed-event-people__item' ), 'Information failed.' ) );
	p4_case( '20 invalid role falls back to all', fn() => p4_assert( 6 === substr_count( p4_render( 10, array( 'role' => 'bad' ) ), 'wp-seed-event-people__item' ), 'Invalid role failed.' ) );
	p4_case( '21 default title', fn() => p4_assert( p4_contains( 'Contacts et intervenants', p4_render( 13, p4_invoke( 'normalize_options', array() ) ) ), 'Default title missing.' ) );
	p4_case( '22 custom title', fn() => p4_assert( p4_contains( '>Equipe</h2>', p4_render( 13, array( 'title' => 'Equipe' ) ) ), 'Custom title missing.' ) );
	p4_case( '23 empty title', fn() => p4_assert( ! p4_contains( 'wp-seed-event-people__title', p4_render( 13, array( 'title' => '' ) ) ), 'Empty title rendered.' ) );
	p4_case( '24 h2 through h6', function () { foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) { p4_assert( p4_contains( '<' . $level, p4_render( 13, array( 'heading_level' => $level ) ) ), 'Heading missing.' ); } } );
	p4_case( '25 invalid heading', fn() => p4_assert( p4_contains( '<h2', p4_render( 13, array( 'heading_level' => 'h1' ) ) ), 'Heading fallback failed.' ) );
	p4_case( '26 roles shown', fn() => p4_assert( p4_contains( 'wp-seed-event-people__roles', p4_render( 13 ) ), 'Roles missing.' ) );
	p4_case( '27 roles hidden', fn() => p4_assert( ! p4_contains( 'wp-seed-event-people__roles', p4_render( 13, array( 'show_roles' => false ) ) ), 'Roles visible.' ) );
	p4_case( '28 public email shown', fn() => p4_assert( p4_contains( 'mailto:public@example.test', p4_render( 10 ) ), 'Public email missing.' ) );
	p4_case( '29 private email absent', fn() => p4_assert( ! p4_contains( 'secret@example.test', p4_render( 10 ) ), 'Private email leaked.' ) );
	p4_case( '30 public phone shown', fn() => p4_assert( p4_contains( 'tel:+33123456789', p4_render( 10 ) ), 'Public phone missing.' ) );
	p4_case( '31 private phone absent', fn() => p4_assert( ! p4_contains( '888888888', p4_render( 10 ) ), 'Private phone leaked.' ) );
	p4_case( '32 public link shown', fn() => p4_assert( p4_contains( 'example.test/contact', p4_render( 10 ) ), 'Public link missing.' ) );
	p4_case( '33 private link absent', fn() => p4_assert( ! p4_contains( 'secret.example.test', p4_render( 10 ) ), 'Private link leaked.' ) );
	p4_case( '34 show options independent', function () { $html = p4_render( 10, array( 'show_email' => false, 'show_phone' => true, 'show_link' => true ) ); p4_assert( ! p4_contains( 'mailto:', $html ) && p4_contains( 'tel:', $html ) && p4_contains( 'example.test/contact', $html ), 'Independent toggles failed.' ); } );
	p4_case( '35 list layout', fn() => p4_assert( p4_contains( 'is-layout-list', p4_render( 13, array( 'layout' => 'list' ) ) ), 'List missing.' ) );
	p4_case( '36 grid layout', fn() => p4_assert( p4_contains( 'is-layout-grid', p4_render( 13, array( 'layout' => 'grid' ) ) ), 'Grid missing.' ) );
	p4_case( '37 invalid layout', fn() => p4_assert( p4_contains( 'is-layout-list', p4_render( 13, array( 'layout' => 'carousel' ) ) ), 'Layout fallback failed.' ) );
	p4_case( '38 one Event Data call', function () { $GLOBALS['p4_data_calls'] = 0; p4_render( 10 ); p4_assert( 1 === $GLOBALS['p4_data_calls'], 'Event Data count differs.' ); } );
	p4_case( '39 one renderer call site', fn() => p4_assert( 1 === substr_count( $source, 'wp_seed_events_render_public_event_people_section' ), 'Renderer call site differs.' ) );
	p4_case( '40 no duplicated business HTML in JavaScript', fn() => p4_assert( ! p4_contains( '<section', $js ) && ! p4_contains( '<ul', $js ) && ! p4_contains( '<li', $js ), 'Business HTML duplicated.' ) );
	p4_case( '41 no direct meta access', fn() => p4_assert( ! p4_contains( 'get_post_meta', $source ), 'Meta access found.' ) );
	p4_case( '42 no publication flags', fn() => p4_assert( ! p4_contains( 'publish_', $source . $js ), 'Publication flag exposed.' ) );
	p4_case( '43 no person key', fn() => p4_assert( ! p4_contains( 'person_key', $source . $js ), 'Person key exposed.' ) );
	p4_case( '44 no raw contacts access', fn() => p4_assert( ! p4_contains( '_wp_seed_event_contacts', $source . $js ) && ! p4_contains( 'wp_seed_events_people', $source . $js ), 'Raw contacts accessed.' ) );
	p4_case( '45 module equals renderer', function () { $event = $GLOBALS['p4_events'][13]; $options = array( 'title' => 'Equipe' ); p4_assert( p4_render( 13, $options ) === wp_seed_events_render_public_event_people_section( $event, $options ), 'Renderer parity failed.' ); } );
	p4_case( '46 deterministic output', fn() => p4_assert( p4_render( 10 ) === p4_render( 10 ), 'Output differs.' ) );
	p4_case( '47 Loop Builder context', fn() => p4_assert( 14 === wp_seed_events_divi_resolve_event_id( array( 'loop_id' => 14, 'post_id' => 20 ) ), 'Loop context failed.' ) );
	p4_case( '48 successive modules stay isolated', function () { $one = p4_render( 13 ); $three = p4_render( 14 ); p4_assert( 1 === substr_count( $one, 'wp-seed-event-people__item' ) && 3 === substr_count( $three, 'wp-seed-event-people__item' ), 'Module state leaked.' ); } );
	p4_case( '49 no empty wrapper', fn() => p4_assert( '' === p4_render( 12 ), 'Empty wrapper returned.' ) );
	p4_case( '50 no context is safe', function () { $GLOBALS['p4_queried_id'] = 0; $GLOBALS['p4_current_id'] = 0; p4_assert( 0 === wp_seed_events_divi_resolve_event_id(), 'No context resolved.' ); } );

	p4_case( '51 role toggles use OR semantics', function () {
		$options = p4_invoke( 'normalize_options', array( 'role_organizer' => 'on', 'role_speaker' => 'on' ) );
		p4_assert( 4 === substr_count( p4_render( 10, $options ), 'wp-seed-event-people__item' ), 'Divi role toggles did not use OR.' );
	} );
	p4_case( '52 visibility and link toggles reach the renderer', function () {
		$options = p4_invoke( 'normalize_options', array( 'show_name' => 'off', 'show_roles' => 'off', 'link_email' => 'off', 'link_phone' => 'off', 'link_url' => 'off' ) );
		$html = p4_render( 10, $options );
		p4_assert( p4_contains( '__name screen-reader-text', $html ), 'Hidden name is not accessible.' );
		p4_assert( ! p4_contains( 'mailto:', $html ) && ! p4_contains( 'tel:', $html ) && ! p4_contains( '__link-anchor', $html ), 'Divi link toggles were ignored.' );
		p4_assert( ! p4_contains( 'secret@example.test', $html ), 'Private coordinate leaked.' );
	} );

	$benchmarks = array();
	foreach ( array( 'zero' => 12, 'one' => 13, 'three' => 14 ) as $name => $id ) {
		$start = hrtime( true );
		for ( $i = 0; $i < 500; $i++ ) { p4_render( $id ); }
		$benchmarks[ $name . '_500_ms' ] = round( ( hrtime( true ) - $start ) / 1000000, 3 );
	}
	$start = hrtime( true );
	for ( $i = 0; $i < 100; $i++ ) { foreach ( array( 10, 12, 13, 14, 10, 13, 12 ) as $id ) { p4_render( $id ); } }
	$benchmarks['seven_event_loop_700_ms'] = round( ( hrtime( true ) - $start ) / 1000000, 3 );

	p4_assert( 52 === $GLOBALS['p4_cases'], 'Harness case count differs.' );
	echo '[OK] Divi people module: 52/52 cases passed.' . PHP_EOL;
	echo '[PERF] ' . json_encode( $benchmarks, JSON_UNESCAPED_SLASHES ) . PHP_EOL;
}
