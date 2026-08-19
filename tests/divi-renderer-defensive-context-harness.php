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

namespace ET\Builder\Packages\Module\Layout\Components\DynamicContent {
	class DynamicContentUtils {
		public static $loop_post_id = 0;

		public static function get_loop_post_id( array $attrs, string $module_id, ?int $store_instance = null ): int {
			return self::$loop_post_id;
		}
	}
}

namespace {
	use ET\Builder\Packages\Module\Module;

	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['defensive_cases']      = 0;
	$GLOBALS['defensive_post_types'] = array( 10 => 'wp_seed_event', 11 => 'wp_seed_event', 12 => 'wp_seed_event', 13 => 'wp_seed_event', 20 => 'page' );
	$GLOBALS['defensive_queried_id'] = 0;
	$GLOBALS['defensive_current_id'] = 0;

	class WP_REST_Server {
		const READABLE = 'GET';
	}

	class WP_REST_Request {
		private $params;

		public function __construct( $params = array() ) {
			$this->params = is_array( $params ) ? $params : array();
		}

		public function get_param( $key ) {
			return $this->params[ $key ] ?? null;
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

	function wp_parse_args( $args, $defaults ) {
		return array_merge( $defaults, is_array( $args ) ? $args : array() );
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

	function wp_seed_events_public_heading_level_option( $value ) {
		return in_array( $value, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $value : 'h2';
	}

	function wp_seed_events_public_date_mode_option( $value ) {
		return in_array( $value, array( 'next', 'first', 'last', 'all' ), true ) ? $value : 'all';
	}

	function wp_seed_events_public_date_scope_option( $value ) {
		return in_array( $value, array( 'all', 'upcoming', 'past' ), true ) ? $value : 'all';
	}

	function wp_seed_events_public_date_format_option( $value ) {
		return in_array( $value, array( 'long', 'short' ), true ) ? $value : 'long';
	}

	function wp_seed_events_public_boolean_option( $value, $default = false ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( ! is_scalar( $value ) ) {
			return $default;
		}
		return in_array( strtolower( trim( (string) $value ) ), array( '1', 'on', 'true', 'yes' ), true );
	}

	function wp_seed_events_public_date_list_dimension_option( $value, $default ) {
		$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return preg_match( '/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/', $value ) ? $value : $default;
	}

	function wp_seed_events_public_date_list_marker_color_option( $value ) {
		$value = is_scalar( $value ) ? strtolower( trim( (string) $value ) ) : '';
		return preg_match( '/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/', $value ) ? $value : '';
	}

	function wp_seed_events_public_people_role_option( $value ) {
		return in_array( $value, array( 'organizer', 'speaker', 'registration_contact', 'information_contact' ), true ) ? $value : 'all';
	}

	function wp_seed_events_public_people_roles_option( $value ) {
		$roles = is_array( $value ) ? $value : explode( ',', (string) $value );
		$roles = array_values( array_intersect( $roles, array( 'organizer', 'speaker', 'registration_contact', 'information_contact' ) ) );
		return array_values( array_unique( $roles ) );
	}

	function wp_seed_events_public_event_people_layout_option( $value ) {
		return 'grid' === $value ? 'grid' : 'list';
	}

	function wp_seed_events_get_event_data( $event_id ) {
		$events = array(
			10 => array(
				'id'                   => 10,
				'communication_visual' => array( 'id' => 101 ),
				'other_visuals'        => array(),
			),
			11 => array(
				'id'                   => 11,
				'communication_visual' => array( 'id' => 201 ),
				'other_visuals'        => array( array( 'id' => 202 ), array( 'id' => 203 ) ),
			),
			12 => array(
				'id'                   => 12,
				'communication_visual' => array(),
				'other_visuals'        => array(),
			),
		);

		return $events[ (int) $event_id ] ?? array();
	}

	function wp_seed_events_render_public_event_dates_section( $event, $options ) {
		return '<section data-kind="dates" data-event="' . (int) $event['id'] . '" data-marker="' . (string) ( $options['list_marker_type'] ?? '' ) . '" data-position="' . (string) ( $options['list_marker_position'] ?? '' ) . '" data-indent="' . (string) ( $options['list_indent'] ?? '' ) . '" data-gap="' . (string) ( $options['occurrence_gap'] ?? '' ) . '" data-color="' . (string) ( $options['marker_color'] ?? '' ) . '"></section>';
	}

	function wp_seed_events_render_public_event_visuals_section( $event, $options ) {
		$media_ids = array();

		if ( 'off' !== ( $options['show_flyer'] ?? 'on' ) && ! empty( $event['communication_visual']['id'] ) ) {
			$media_ids[] = (int) $event['communication_visual']['id'];
		}

		if ( 'off' !== ( $options['show_visuals'] ?? 'on' ) ) {
			foreach ( $event['other_visuals'] ?? array() as $visual ) {
				if ( ! empty( $visual['id'] ) ) {
					$media_ids[] = (int) $visual['id'];
				}
			}
		}

		if ( array() === $media_ids ) {
			return '';
		}

		return '<section data-kind="visuals" data-event="' . (int) $event['id'] . '" data-media="' . implode( ',', $media_ids ) . '"></section>';
	}

	function wp_seed_events_render_public_event_people_section( $event, $options ) {
		return '<section data-kind="people" data-event="' . (int) $event['id'] . '"></section>';
	}

	require dirname( __DIR__ ) . '/includes/integrations/divi/context.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/module-contracts.php';
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

	defensive_case( 'event detail visuals accepts Divi responsive list attributes', function () use ( $full_metadata, $event_context ) {
		$attrs = array(
			'eventListStyle' => array(
				'advanced' => array(
					'markerType' => array(
						'desktop' => array( 'value' => 'none' ),
						'tablet'  => array( 'value' => array( 'markerType' => 'circle' ) ),
					),
					'leftIndent' => array( 'desktop' => array( 'value' => '0px' ) ),
				),
			),
		);
		$result = defensive_render( WP_Seed_Events_Divi_Event_Visuals_Module::class, $full_metadata, $event_context, $attrs );
		defensive_assert( false !== strpos( $result['html'], 'data-kind="visuals"' ), 'Event detail Visuals module did not render.' );
	} );

	foreach ( $modules as $label => $class_name ) {
		defensive_case( $label . ' resolves an ancestor Loop Builder event', function () use ( $class_name ) {
			\ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentUtils::$loop_post_id = 10;
			$result = defensive_render(
				$class_name,
				array( 'id' => 'module-in-loop', 'storeInstance' => 3 ),
				array( 'postId' => 20, 'postType' => 'page' )
			);
			\ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentUtils::$loop_post_id = 0;
			defensive_assert( false !== strpos( $result['html'], 'data-event="10"' ), 'Ancestor loop event did not render.' );
		} );
	}

	$dates_normalize = new ReflectionMethod( WP_Seed_Events_Divi_Event_Dates_Module::class, 'normalize_options' );

	$date_selections = array(
		'next'         => array( 'next', 'upcoming' ),
		'first'        => array( 'first', 'all' ),
		'last'         => array( 'last', 'all' ),
		'all_upcoming' => array( 'all', 'upcoming' ),
		'all_past'     => array( 'all', 'past' ),
		'all'          => array( 'all', 'all' ),
	);
	foreach ( $date_selections as $selection => $expected ) {
		defensive_case( 'dates maps explicit selection ' . $selection, function () use ( $dates_normalize, $selection, $expected ) {
			$options = $dates_normalize->invoke( null, array( 'date_selection' => $selection, 'mode' => 'last', 'scope' => 'past' ) );
			defensive_assert( $expected[0] === $options['mode'], 'Mapped mode differs.' );
			defensive_assert( $expected[1] === $options['scope'], 'Mapped scope differs.' );
		} );
	}
	defensive_case( 'dates keeps legacy mode and scope without explicit selection', function () use ( $dates_normalize ) {
		$options = $dates_normalize->invoke( null, array( 'mode' => 'first', 'scope' => 'past' ) );
		defensive_assert( 'first' === $options['mode'], 'Legacy mode changed.' );
		defensive_assert( 'past' === $options['scope'], 'Legacy scope changed.' );
	} );

	defensive_case( 'strict incompatible context never falls back', function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 10;
		$resolved = wp_seed_events_divi_resolve_event_id( array( 'post_id' => 20, 'post_type' => 'page', 'strict_post' => true ) );
		unset( $GLOBALS['wp_seed_events_public_event_id'] );
		defensive_assert( 0 === $resolved, 'Strict incompatible context fell back.' );
	} );

	defensive_case( 'visuals prefers the parsed repeated-block event over a raw callback token', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-loop-a', 'attrs' => array( '__loop_post_id' => 11 ) ),
			array( 'postId' => 20, 'postType' => 'page' ),
			array( '__loop_post_id' => '$variable(loop_post_id)$' )
		);
		defensive_assert( false !== strpos( $result['html'], 'data-event="11"' ), 'Parsed loop item was not authoritative.' );
		defensive_assert( false !== strpos( $result['html'], 'data-media="201,202,203"' ), 'Complete visual collection was not rendered.' );
	} );

	defensive_case( 'visuals renders the flyer without other visuals', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-flyer', 'attrs' => array( '__loop_post_id' => 11 ) ),
			array(),
			array( 'content' => array( 'innerContent' => array( 'desktop' => array( 'value' => array( 'show_flyer' => 'on', 'show_visuals' => 'off' ) ) ) ) )
		);
		defensive_assert( false !== strpos( $result['html'], 'data-media="201"' ), 'Flyer was not rendered.' );
		defensive_assert( false === strpos( $result['html'], '202' ), 'Other visuals leaked into flyer-only output.' );
	} );

	defensive_case( 'visuals renders all other visuals without the flyer', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-others', 'attrs' => array( '__loop_post_id' => 11 ) ),
			array(),
			array( 'content' => array( 'innerContent' => array( 'desktop' => array( 'value' => array( 'show_flyer' => 'off', 'show_visuals' => 'on' ) ) ) ) )
		);
		defensive_assert( false !== strpos( $result['html'], 'data-media="202,203"' ), 'Other visuals were not rendered in stable order.' );
		defensive_assert( false === strpos( $result['html'], '201' ), 'Flyer leaked into other-visual output.' );
	} );

	defensive_case( 'visuals returns empty for an event without visuals', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-empty', 'attrs' => array( '__loop_post_id' => 12 ) )
		);
		defensive_assert( '' === $result['html'], 'Empty event rendered a module wrapper.' );
	} );

	defensive_case( 'visuals keeps two module instances and two loop items isolated', function () {
		$first = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-instance-a', 'attrs' => array( '__loop_post_id' => 10 ) )
		);
		$second = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-instance-b', 'attrs' => array( '__loop_post_id' => 11 ) )
		);
		$again = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-instance-a', 'attrs' => array( '__loop_post_id' => 10 ) )
		);
		defensive_assert( false !== strpos( $first['html'], 'data-event="10"' ), 'First item resolved incorrectly.' );
		defensive_assert( false !== strpos( $second['html'], 'data-event="11"' ), 'Second item resolved incorrectly.' );
		defensive_assert( $first['html'] === $again['html'], 'Loop context leaked between successive items.' );
		defensive_assert( 'visuals-instance-a' === $first['args']['id'], 'First module ID changed.' );
		defensive_assert( 'visuals-instance-b' === $second['args']['id'], 'Second module ID changed.' );
	} );

	defensive_case( 'visuals Visual Builder preview uses each explicit loop item', function () {
		$first = WP_Seed_Events_Divi_Event_Visuals_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 10 ) ) );
		$second = WP_Seed_Events_Divi_Event_Visuals_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 11, 'show_flyer' => 'off', 'show_visuals' => 'on' ) ) );
		defensive_assert( false !== strpos( $first['html'], 'data-event="10"' ), 'First Visual Builder item resolved incorrectly.' );
		defensive_assert( false !== strpos( $second['html'], 'data-event="11"' ), 'Second Visual Builder item resolved incorrectly.' );
		defensive_assert( false !== strpos( $second['html'], 'data-media="202,203"' ), 'Visual Builder options were not preserved.' );
	} );

	defensive_case( 'visuals preserves a valid non-loop event context', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-single' ),
			array( 'postId' => 10, 'postType' => 'wp_seed_event' )
		);
		defensive_assert( false !== strpos( $result['html'], 'data-event="10"' ), 'Non-loop event context regressed.' );
	} );

	defensive_case( 'visuals excludes a private event returned empty by Event Data', function () {
		$result = defensive_render(
			WP_Seed_Events_Divi_Event_Visuals_Module::class,
			array( 'id' => 'visuals-private', 'attrs' => array( '__loop_post_id' => 13 ) )
		);
		defensive_assert( '' === $result['html'], 'Private event rendered through the public Event Data contract.' );
	} );

	defensive_case( 'dates frontend unwraps Divi 5 nested list design values', function () {
		foreach ( array( 'none', 'disc', 'circle', 'square' ) as $marker ) {
			$attrs = array(
				'__loop_post_id' => 10,
				'listStyle'      => array(
					'advanced' => array(
						'markerType'     => array( 'desktop' => array( 'value' => array( 'markerType' => $marker ) ) ),
						'markerPosition' => array( 'desktop' => array( 'value' => array( 'markerPosition' => 'inside' ) ) ),
						'leftIndent'     => array( 'desktop' => array( 'value' => array( 'leftIndent' => '3rem' ) ) ),
						'occurrenceGap'  => array( 'desktop' => array( 'value' => array( 'occurrenceGap' => '12px' ) ) ),
						'markerColor'    => array( 'desktop' => array( 'value' => array( 'markerColor' => '#123456' ) ) ),
					),
				),
			);
			$result = defensive_render(
				WP_Seed_Events_Divi_Event_Dates_Module::class,
				array( 'id' => 'dates-marker-' . $marker ),
				array(),
				$attrs
			);

			defensive_assert( false !== strpos( $result['html'], 'data-marker="' . $marker . '"' ), 'Nested marker value was not forwarded.' );
			defensive_assert( false !== strpos( $result['html'], 'data-position="inside"' ), 'Nested marker position was not forwarded.' );
			defensive_assert( false !== strpos( $result['html'], 'data-indent="3rem"' ), 'Nested list indent was not forwarded.' );
			defensive_assert( false !== strpos( $result['html'], 'data-gap="12px"' ), 'Nested occurrence gap was not forwarded.' );
			defensive_assert( false !== strpos( $result['html'], 'data-color="#123456"' ), 'Nested marker color was not forwarded.' );
		}
	} );
	defensive_case( 'dates list design values resolve responsive inheritance and nested states', function () {
		$attrs = array(
			'listStyle' => array(
				'advanced' => array(
					'markerType' => array(
						'desktop' => array( 'value' => array( 'markerType' => 'none' ) ),
						'tablet'  => array( 'value' => 'circle' ),
						'phone'   => array( 'value' => array( 'markerType' => 'square' ) ),
					),
					'markerPosition' => array(
						'desktop' => array( 'value' => 'outside' ),
						'tablet'  => array( 'value' => array( 'markerPosition' => 'inside' ) ),
					),
					'leftIndent' => array(
						'desktop' => array( 'value' => '2.5em' ),
						'tablet'  => array( 'value' => array( 'leftIndent' => '3rem' ) ),
					),
					'occurrenceGap' => array(
						'desktop' => array( 'value' => '0px' ),
						'phone'   => array( 'value' => array( 'occurrenceGap' => '4px' ) ),
					),
					'markerColor' => array(
						'desktop' => array( 'value' => '#123456' ),
						'tablet'  => array( 'value' => array( 'markerColor' => '#654321' ) ),
					),
				),
			),
		);
		$method = new ReflectionMethod( WP_Seed_Events_Divi_Event_Dates_Module::class, 'get_responsive_list_values' );
		$styles = $method->invoke( null, $attrs );

		defensive_assert( 'none' === $styles['desktop']['markerType'], 'Desktop nested marker failed.' );
		defensive_assert( '0px' === $styles['desktop']['leftIndent'], 'None marker did not reset default indent.' );
		defensive_assert( 'circle' === $styles['tablet']['markerType'], 'Tablet marker failed.' );
		defensive_assert( 'inside' === $styles['tablet']['markerPosition'], 'Tablet nested position failed.' );
		defensive_assert( '3rem' === $styles['tablet']['leftIndent'], 'Tablet nested indent failed.' );
		defensive_assert( 'square' === $styles['phone']['markerType'], 'Phone nested marker failed.' );
		defensive_assert( 'inside' === $styles['phone']['markerPosition'], 'Phone position inheritance failed.' );
		defensive_assert( '4px' === $styles['phone']['occurrenceGap'], 'Phone nested gap failed.' );
		defensive_assert( '#654321' === $styles['phone']['markerColor'], 'Phone color inheritance failed.' );
	} );

	defensive_case( 'dates Visual Builder preview uses each explicit loop item without leakage', function () {
		$first  = WP_Seed_Events_Divi_Event_Dates_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 10 ) ) );
		$second = WP_Seed_Events_Divi_Event_Dates_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 11 ) ) );
		$again  = WP_Seed_Events_Divi_Event_Dates_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 10 ) ) );

		defensive_assert( false !== strpos( $first['html'], 'data-event="10"' ), 'First Dates preview item resolved incorrectly.' );
		defensive_assert( false !== strpos( $second['html'], 'data-event="11"' ), 'Second Dates preview item resolved incorrectly.' );
		defensive_assert( $first['html'] === $again['html'], 'Dates preview context leaked between loop items.' );
	} );

	defensive_case( 'dates Visual Builder preview forwards list design values', function () {
		$result = WP_Seed_Events_Divi_Event_Dates_Module::rest_preview(
			new WP_REST_Request(
				array(
					'post_id'             => 20,
					'loop_id'             => 11,
					'list_marker_type'     => 'square',
					'list_marker_position' => 'inside',
					'list_indent'          => '3rem',
					'occurrence_gap'       => '12px',
					'marker_color'         => '#123456',
				)
			)
		);

		defensive_assert( false !== strpos( $result['html'], 'data-event="11"' ), 'Styled Dates preview lost its loop context.' );
		defensive_assert( false !== strpos( $result['html'], 'data-marker="square"' ), 'Marker type was not forwarded.' );
		defensive_assert( false !== strpos( $result['html'], 'data-position="inside"' ), 'Marker position was not forwarded.' );
		defensive_assert( false !== strpos( $result['html'], 'data-indent="3rem"' ), 'List indent was not forwarded.' );
		defensive_assert( false !== strpos( $result['html'], 'data-gap="12px"' ), 'Occurrence gap was not forwarded.' );
		defensive_assert( false !== strpos( $result['html'], 'data-color="#123456"' ), 'Marker color was not forwarded.' );
	} );

	defensive_case( 'dates Visual Builder preview stays empty without a public occurrence context', function () {
		$result = WP_Seed_Events_Divi_Event_Dates_Module::rest_preview( new WP_REST_Request( array( 'post_id' => 20, 'loop_id' => 13 ) ) );
		defensive_assert( '' === $result['html'], 'Dates preview rendered an event without public occurrence data.' );
	} );
	restore_error_handler();

	echo 'Divi defensive renderer metadata: ' . $GLOBALS['defensive_cases'] . '/' . $GLOBALS['defensive_cases'] . ' OK' . PHP_EOL;
}
