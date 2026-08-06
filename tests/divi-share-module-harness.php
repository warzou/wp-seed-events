<?php

namespace ET\Builder\Framework\DependencyManagement\Interfaces {
	interface DependencyInterface {
		public function load();
	}
}

namespace ET\Builder\Framework\Utility {
	class HTMLUtility {
		public static function render( $args ) {
			return '<' . $args['tag'] . ' class="et_pb_module_inner">' . $args['children'] . '</' . $args['tag'] . '>';
		}
	}
}

namespace ET\Builder\FrontEnd\Module {
	class Style {
		public static function add( $args ) {
			$GLOBALS['share_styles'][] = $args;
		}
	}
}

namespace ET\Builder\Packages\Module {
	class Module {
		public static function render( $args ) {
			$GLOBALS['share_render_args'] = $args;
			return $args['children'];
		}
	}
}

namespace ET\Builder\Packages\Module\Options\Element {
	class ElementClassnames {
		public static function classnames( $args ) {
			return 'decorated';
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
	define( 'ABSPATH', __DIR__ );

	$GLOBALS['share_actions']       = array();
	$GLOBALS['share_context']       = array();
	$GLOBALS['share_event_id']      = 0;
	$GLOBALS['share_events']        = array();
	$GLOBALS['share_data_calls']    = 0;
	$GLOBALS['share_renderer_calls'] = 0;
	$GLOBALS['share_styles']        = array();

	function add_action( $hook, $callback ) {
		$GLOBALS['share_actions'][] = array( $hook, $callback );
	}

	function absint( $value ) {
		return abs( (int) $value );
	}

	function wp_seed_events_divi_get_module_event_context( $attrs, $block ) {
		$GLOBALS['share_context'] = array( $attrs, $block );
		return array( 'post_id' => $GLOBALS['share_event_id'] );
	}

	function wp_seed_events_divi_resolve_event_id( $context ) {
		return (int) ( $context['post_id'] ?? 0 );
	}

	function wp_seed_events_get_event_data( $event_id ) {
		++$GLOBALS['share_data_calls'];
		return $GLOBALS['share_events'][ $event_id ] ?? array();
	}

	function wp_seed_events_render_event_share_menu( $event ) {
		++$GLOBALS['share_renderer_calls'];
		return empty( $event['url'] ) ? '' : '<div class="wp-seed-event-share">Partager</div>';
	}

	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-event-share-module.php';

	function share_assert( $condition, $message ) {
		if ( ! $condition ) {
			throw new \RuntimeException( $message );
		}
	}

	final class ShareElements {
		public function style_components() {
			return '<style-component />';
		}

		public function style( $args ) {
			return $args['attrName'];
		}

		public function script_data() {
			return null;
		}
	}

	final class ShareClassnames {
		public $values = array();

		public function add( $value ) {
			$this->values[] = $value;
		}
	}

	$module = new WP_Seed_Events_Divi_Event_Share_Module();
	$module->load();
	share_assert( array( 'init', array( WP_Seed_Events_Divi_Event_Share_Module::class, 'register_module' ) ) === $GLOBALS['share_actions'][0], 'Dependency hook differs.' );

	WP_Seed_Events_Divi_Event_Share_Module::register_module();
	share_assert( 1 === count( \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::$registrations ), 'Module registration differs.' );

	$block = (object) array(
		'parsed_block' => array( 'orderIndex' => 1, 'storeInstance' => 'test', 'id' => 'share-1' ),
		'block_type'   => (object) array( 'name' => 'wp-seed-events/event-share', 'category' => 'module' ),
	);
	$elements = new ShareElements();

	share_assert( '' === WP_Seed_Events_Divi_Event_Share_Module::render_callback( array(), '', $block, $elements ), 'Missing context did not hide module.' );
	share_assert( 0 === $GLOBALS['share_data_calls'], 'Missing context queried Event Data.' );

	$GLOBALS['share_event_id'] = 2414;
	share_assert( '' === WP_Seed_Events_Divi_Event_Share_Module::render_callback( array(), '', $block, $elements ), 'Missing event did not hide module.' );
	share_assert( 1 === $GLOBALS['share_data_calls'], 'Missing event query count differs.' );
	share_assert( 0 === $GLOBALS['share_renderer_calls'], 'Missing event called share renderer.' );

	$GLOBALS['share_events'][2414] = array( 'title' => 'Journee', 'url' => 'https://example.test/event/' );
	$output = WP_Seed_Events_Divi_Event_Share_Module::render_callback( array( 'module' => array() ), '', $block, $elements );
	share_assert( false !== strpos( $output, 'wp-seed-event-share' ), 'Valid share markup missing.' );
	share_assert( 2 === $GLOBALS['share_data_calls'], 'Valid event query count differs.' );
	share_assert( 1 === $GLOBALS['share_renderer_calls'], 'Official renderer call count differs.' );
	share_assert( 'wp_seed_events_divi_event_share' === $GLOBALS['share_render_args']['moduleClassName'], 'Module class differs.' );

	$classnames = new ShareClassnames();
	WP_Seed_Events_Divi_Event_Share_Module::module_classnames(
		array( 'classnamesInstance' => $classnames, 'attrs' => array( 'module' => array( 'decoration' => array() ) ) )
	);
	share_assert( array( 'decorated' ) === $classnames->values, 'Decoration classnames differ.' );

	WP_Seed_Events_Divi_Event_Share_Module::module_styles(
		array(
			'elements'      => $elements,
			'settings'      => array(),
			'id'            => 'share-1',
			'name'          => 'wp-seed-events/event-share',
			'orderIndex'    => 1,
			'storeInstance' => 'test',
		)
	);
	share_assert( 1 === count( $GLOBALS['share_styles'] ), 'Style registration differs.' );

	echo "Divi event share module harness: 12/12 OK\n";
}
