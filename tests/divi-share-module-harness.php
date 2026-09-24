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

namespace ET\Builder\Packages\IconLibrary\IconFont {
	class Utils {
		public static function process_font_icon( $settings ) {
			return html_entity_decode( (string) ( $settings['unicode'] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
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
	$GLOBALS['share_renderer_options'] = array();
	$GLOBALS['share_styles']        = array();

	function add_action( $hook, $callback ) {
		$GLOBALS['share_actions'][] = array( $hook, $callback );
	}

	function absint( $value ) {
		return abs( (int) $value );
	}

	function __( $value ) {
		return $value;
	}

	function sanitize_text_field( $value ) {
		return trim( strip_tags( (string) $value ) );
	}

	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
	}

	function wp_seed_events_share_display_mode( $value ) {
		return in_array( $value, array( 'text_icon', 'text', 'icon' ), true ) ? $value : 'text_icon';
	}

	function wp_seed_events_share_action_order_key( $value ) {
		$valid = array( 'share_copy_email', 'share_email_copy', 'copy_share_email', 'copy_email_share', 'email_share_copy', 'email_copy_share' );
		return in_array( $value, $valid, true ) ? $value : 'share_copy_email';
	}

	function wp_seed_events_share_text_option( $value, $default ) {
		$value = is_scalar( $value ) ? trim( strip_tags( (string) $value ) ) : '';
		return '' !== $value ? $value : $default;
	}

	function wp_seed_events_public_boolean_option( $value, $default ) {
		return is_bool( $value ) ? $value : ( ! in_array( $value, array( 'off', '0' ), true ) );
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

	function wp_seed_events_render_event_share_menu( $event, $options = array() ) {
		++$GLOBALS['share_renderer_calls'];
		$GLOBALS['share_renderer_options'] = $options;
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
	share_assert(
		array(
			'display_mode'     => 'text_icon',
			'action_order'     => 'share_copy_email',
			'label'            => 'Partager',
			'show_share'       => true,
			'share_icon'       => '',
			'share_icon_placement' => 'right',
			'share_icon_on_hover'  => 'on',
			'show_copy'        => true,
			'copy_label'       => 'Copier le lien',
			'copy_icon'        => '',
			'copy_icon_placement' => 'right',
			'copy_icon_on_hover'  => 'on',
			'show_email'       => true,
			'email_label'      => 'Par email',
			'email_icon'       => '',
			'email_icon_placement' => 'right',
			'email_icon_on_hover'  => 'on',
			'use_divi_button'  => true,
		) === $GLOBALS['share_renderer_options'],
		'Historical share defaults differ.'
	);

	WP_Seed_Events_Divi_Event_Share_Module::render_callback(
		array(
			'content' => array(
				'innerContent' => array(
					'desktop' => array(
						'value' => array(
							'display_mode' => 'icon',
							'action_order' => 'email_copy_share',
							'label'       => 'Diffuser',
							'show_share'  => 'off',
							'show_copy'   => 'on',
							'show_email'  => 'off',
							'copy_label'  => 'Copier',
							'email_label' => 'Email',
							'copy_icon'   => '&#xe099;',
						),
					),
				),
			),
			'shareActionStyle' => array(
				'decoration' => array(
					'button' => array(
						'desktop' => array(
							'value' => array(
								'icon' => array( 'enable' => 'on', 'settings' => array( 'unicode' => '&#xe001;' ), 'placement' => 'left', 'onHover' => 'off' ),
							),
						),
					),
				),
			),
			'copyActionStyle' => array(
				'decoration' => array(
					'button' => array(
						'desktop' => array(
							'value' => array(
								'icon' => array( 'enable' => 'off', 'settings' => array( 'unicode' => '&#xe002;' ) ),
							),
						),
					),
				),
			),
		),
		'',
		$block,
		$elements
	);
	share_assert( 'Diffuser' === $GLOBALS['share_renderer_options']['label'], 'Custom share label was not forwarded.' );
	share_assert( 'email_copy_share' === $GLOBALS['share_renderer_options']['action_order'], 'Custom action order was not forwarded.' );
	share_assert( 'icon' === $GLOBALS['share_renderer_options']['display_mode'], 'Display mode was not forwarded.' );
	share_assert( false === $GLOBALS['share_renderer_options']['show_share'], 'Share visibility was not forwarded.' );
	share_assert( true === $GLOBALS['share_renderer_options']['show_copy'], 'Copy visibility was not forwarded.' );
	share_assert( false === $GLOBALS['share_renderer_options']['show_email'], 'Email visibility was not forwarded.' );
	share_assert( "\u{e001}" === $GLOBALS['share_renderer_options']['share_icon'], 'Explicit Share icon was not processed through Divi.' );
	share_assert( 'left' === $GLOBALS['share_renderer_options']['share_icon_placement'], 'Share icon placement was not forwarded.' );
	share_assert( 'off' === $GLOBALS['share_renderer_options']['share_icon_on_hover'], 'Share icon hover mode was not forwarded.' );
	share_assert( '' === $GLOBALS['share_renderer_options']['copy_icon'], 'Disabled Copy icon did not override legacy content.' );

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

	echo "Divi event share module harness: 21/21 OK\n";
}
