<?php

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Divi 5 adapter for the official public event sharing renderer.
 */
class WP_Seed_Events_Divi_Event_Share_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/event-share';

	public function load() {
		add_action( 'init', array( self::class, 'register_module' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/event-share-module/visual-builder/src',
			array( 'render_callback' => array( self::class, 'render_callback' ) )
		);
	}

	public static function register_rest_routes() {
		register_rest_route(
			'wp-seed-events/v1',
			'/divi-event-share-preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => 'wp_seed_events_divi_rest_preview_permissions',
			)
		);
	}

	public static function rest_preview( WP_REST_Request $request ) {
		return rest_ensure_response(
			array( 'html' => self::render_share( wp_seed_events_divi_rest_preview_event_id( $request ) ) )
		);
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$html     = self::render_share( $event_id );

		if ( '' === $html ) {
			return '';
		}

		$module_inner = HTMLUtility::render(
			array(
				'tag'               => 'div',
				'attributes'        => array( 'class' => 'et_pb_module_inner' ),
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $html,
			)
		);
		$module_children = $elements->style_components( array( 'attrName' => 'module' ) ) . $module_inner;

		return Module::render(
			array(
				'orderIndex'          => $block->parsed_block['orderIndex'] ?? 0,
				'storeInstance'       => $block->parsed_block['storeInstance'] ?? '',
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'] ?? '',
				'moduleClassName'     => 'wp_seed_events_divi_event_share',
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'classnamesFunction'  => array( self::class, 'module_classnames' ),
				'stylesComponent'     => array( self::class, 'module_styles' ),
				'scriptDataComponent' => array( self::class, 'module_script_data' ),
				'children'            => $module_children,
			)
		);
	}

	private static function render_share( $event_id ) {
		$event_id = absint( $event_id );

		if ( 0 === $event_id ) {
			return '';
		}

		$event = wp_seed_events_get_event_data( $event_id );

		return array() === $event ? '' : (string) wp_seed_events_render_event_share_menu( $event );
	}

	public static function module_classnames( $args ) {
		$args['classnamesInstance']->add(
			ElementClassnames::classnames( array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() ) )
		);
	}

	public static function module_styles( $args ) {
		$elements = $args['elements'];
		$styles   = array(
			$elements->style(
				array(
					'attrName'   => 'module',
					'styleProps' => array(
						'disabledOn' => array(
							'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
						),
					),
				)
			),
		);

		foreach ( array( 'shareStyle', 'summaryStyle', 'actionsStyle', 'buttonStyle', 'linkStyle' ) as $attr_name ) {
			$styles[] = $elements->style( array( 'attrName' => $attr_name ) );
		}

		Style::add(
			array(
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => $styles,
			)
		);
	}

	public static function module_script_data( $args ) {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}
}
