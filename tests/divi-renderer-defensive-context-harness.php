<?php
/**
 * Standalone assertions for defensive Divi renderer metadata handling.
 *
 * Run with: php tests/divi-renderer-defensive-context-harness.php
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
		public static $calls = array();

		public static function render( $args ) {
			self::$calls[] = $args;
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
		public static function register_module( $path, $args ) {
		}
	}
}

namespace {
	use ET\Builder\Packages\Module\Module;

	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['defensive_cases']      = 0;
	$GLOBALS['defensive_post_types'] = array( 10 => 'wp_seed_event', 20 => 'page' );
	$GLOBALS['defensive_queried_id'] = 0;
	$GLOBALS['defensive_current_id'] = 0;

	class WP_REST_Server {
		const READABLE = 'GET';
	}

	class WP_REST_Request {
		public function get_param( $key ) {
			return null;
		}
	}

	class Defensive_Elements {
		public function style_components( $args ) {
			return '';
		}
	}

	function add_action( $hook, $callback, $priority = 10 ) {
	}

	function register_rest_route( $namespace, $route, $args ) {
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
		return $GLOBALS['defensive_post_types'][ (int) $post_id ] ?? false;
	}

	function get_queried_object_id() {
		return $GLOBALS['defensive_queried_id'];
	}

	function get_the_ID() {
		return $GLOBALS['defensive_current_id'];
	}

	function wp_seed_events_get_event_data( $event_id ) {
		return 10 === (int) $event_id ? array( 'id' => 10 ) : array();
	}

	function wp_seed_events_render_public_event_dates_section( $event, $options ) {
		return '<section data-kind="dates" data-event="' . (int) $event['id'] . '"></section>';
	}

	function wp_seed_events_render_public_event_visuals_section( $event, $options ) {
		return '<section data-kind="visuals" data-event="' . (int) $event['id'] . '"></section>';
	}

	function wp_seed_events_render_public_event_people_section( $event, $options ) {
		return '<section data-kind="people" data-event="' . (int) $event['id'] . '"></section>';
	}

	require dirname( __DIR__ ) . '/includes/integrations/divi/context.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/class-event-dates-module.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/class-event-visuals-module.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/class-event-people-module.php';

	function defensive_assert( $condition, $message ) {
		if ( ! $condition ) {
			throw new RuntimeException( $message );
		}
	}

	function defensive_case( $name, $callback ) {
		try {
			$callback();
			$GLOBALS['defensive_cases']++;
			echo '[OK] ' . $name . PHP_EOL;
		} catch ( Throwable $error ) {
			fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
			exit( 1 );
		}
	}

	function defensive_block( $metadata, $context = array() ) {
		$block               = new stdClass();
		$block->parsed_block = $metadata;
		$block->context      = $context;
		$block->block_type   = (object) array( 'name' => 'wp-seed-events/test', 'category' => 'module' );
		return $block;
	}

	function defensive_render( $class_name, $metadata, $context = array(), $attrs = array() ) {
		Module::$calls = array();
		$html          = $class_name::render_callback( $attrs, '', defensive_block( $metadata, $context ), new Defensive_Elements() );
		return array( 'html' => $html, 'args' => Module::$calls[0] ?? array() );
	}

	$modules = array(
		'dates'   => WP_Seed_Events_Divi_Event_Dates_Module::class,
		'visuals' => WP_Seed_Events_Divi_Event_Visuals_Module::class,
		'people'  => WP_Seed_Events_Divi_Event_People_Module::class,
	);
	$full_metadata = array( 'orderIndex' => 7, 'storeInstance' => 'store-1', 'id' => 'module-1' );
	$event_context = array( 'postId' => 10, 'postType' => 'wp_seed_event' );

	set_error_handler(
		function ( $severity, $message, $file, $line ) {
			throw new ErrorException( $message, 0, $severity, $file, $line );
		}
	);

	foreach ( $modules as $label => $class_name ) {
		defensive_case( $label . ' preserves complete metadata', function () use ( $class_name, $full_metadata, $event_context ) {
			$result = defensive_render( $class_name, $full_metadata, $event_context );
			defensive_assert( 7 === $result['args']['orderIndex'], 'orderIndex changed.' );
			defensive_assert( 'store-1' === $result['args']['storeInstance'], 'storeInstance changed.' );
			defensive_assert( 'module-1' === $result['args']['id'], 'id changed.' );
		} );

		foreach ( array( 'orderIndex', 'storeInstance', 'id' ) as $missing_key ) {
			defensive_case( $label . ' defaults missing ' . $missing_key, function () use ( $class_name, $full_metadata, $event_context, $missing_key ) {
				$metadata = $full_metadata;
				unset( $metadata[ $missing_key ] );
				$result   = defensive_render( $class_name, $metadata, $event_context );
				$expected = 'orderIndex' === $missing_key ? 0 : '';
				defensive_assert( $expected === $result['args'][ $missing_key ], $missing_key . ' default differs.' );
			} );
		}

		defensive_case( $label . ' defaults all missing metadata', function () use ( $class_name, $event_context ) {
			$result = defensive_render( $class_name, array(), $event_context );
			defensive_assert( 0 === $result['args']['orderIndex'], 'Missing orderIndex was not defaulted.' );
			defensive_assert( '' === $result['args']['storeInstance'], 'Missing storeInstance was not defaulted.' );
			defensive_assert( '' === $result['args']['id'], 'Missing id was not defaulted.' );
		} );

		defensive_case( $label . ' defaults null metadata', function () use ( $class_name, $event_context ) {
			$result = defensive_render( $class_name, array( 'orderIndex' => null, 'storeInstance' => null, 'id' => null ), $event_context );
			defensive_assert( 0 === $result['args']['orderIndex'], 'Null orderIndex was not defaulted.' );
			defensive_assert( '' === $result['args']['storeInstance'], 'Null storeInstance was not defaulted.' );
			defensive_assert( '' === $result['args']['id'], 'Null id was not defaulted.' );
		} );

		defensive_case( $label . ' preserves empty metadata', function () use ( $class_name, $event_context ) {
			$result = defensive_render( $class_name, array( 'orderIndex' => '', 'storeInstance' => '', 'id' => '' ), $event_context );
			defensive_assert( '' === $result['args']['orderIndex'], 'Empty orderIndex changed.' );
			defensive_assert( '' === $result['args']['storeInstance'], 'Empty storeInstance changed.' );
			defensive_assert( '' === $result['args']['id'], 'Empty id changed.' );
		} );

		defensive_case( $label . ' renders a valid event', function () use ( $class_name, $event_context ) {
			$result = defensive_render( $class_name, array(), $event_context );
			defensive_assert( false !== strpos( $result['html'], 'data-event="10"' ), 'Valid event did not render.' );
		} );

		defensive_case( $label . ' returns empty for invalid event', function () use ( $class_name ) {
			$result = defensive_render( $class_name, array(), array( 'postId' => 999, 'postType' => 'wp_seed_event' ) );
			defensive_assert( '' === $result['html'], 'Invalid event rendered.' );
			defensive_assert( array() === $result['args'], 'Module renderer ran for invalid event.' );
		} );

		defensive_case( $label . ' returns empty for incompatible context', function () use ( $class_name ) {
			$result = defensive_render( $class_name, array(), array( 'postId' => 20, 'postType' => 'page' ) );
			defensive_assert( '' === $result['html'], 'Incompatible context rendered.' );
		} );

		defensive_case( $label . ' uses an implicit valid context', function () use ( $class_name ) {
			$GLOBALS['wp_seed_events_public_event_id'] = 10;
			$result = defensive_render( $class_name, array(), array() );
			unset( $GLOBALS['wp_seed_events_public_event_id'] );
			defensive_assert( false !== strpos( $result['html'], 'data-event="10"' ), 'Implicit event did not render.' );
		} );

		defensive_case( $label . ' produces valid JSON without private data or server paths', function () use ( $class_name, $event_context ) {
			$result = defensive_render( $class_name, array(), $event_context );
			$json   = json_encode( array( 'rendered' => $result['html'] ), JSON_THROW_ON_ERROR );
			defensive_assert( is_array( json_decode( $json, true, 512, JSON_THROW_ON_ERROR ) ), 'JSON response is invalid.' );
			defensive_assert( false === strpos( $json, dirname( __DIR__ ) ), 'Server path leaked.' );
			defensive_assert( false === strpos( $json, 'publish_' ) && false === strpos( $json, 'person_key' ), 'Private data leaked.' );
		} );

		defensive_case( $label . ' is deterministic across successive calls', function () use ( $class_name, $event_context ) {
			$first  = defensive_render( $class_name, array(), $event_context );
			$second = defensive_render( $class_name, array(), $event_context );
			defensive_assert( $first['html'] === $second['html'], 'Successive HTML differs.' );
			foreach ( array( 'orderIndex', 'storeInstance', 'id' ) as $key ) {
				defensive_assert( $first['args'][ $key ] === $second['args'][ $key ], 'Successive metadata differs.' );
			}
		} );
	}

	defensive_case( 'strict incompatible context never falls back', function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 10;
		$resolved = wp_seed_events_divi_resolve_event_id( array( 'post_id' => 20, 'post_type' => 'page', 'strict_post' => true ) );
		unset( $GLOBALS['wp_seed_events_public_event_id'] );
		defensive_assert( 0 === $resolved, 'Strict incompatible context fell back.' );
	} );

	restore_error_handler();

	echo 'Divi defensive renderer metadata: ' . $GLOBALS['defensive_cases'] . '/' . $GLOBALS['defensive_cases'] . ' OK' . PHP_EOL;
}
