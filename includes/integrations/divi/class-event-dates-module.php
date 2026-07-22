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
 * Divi 5 module that renders the event occurrence collection.
 */
class WP_Seed_Events_Divi_Event_Dates_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/event-dates';

	/**
	 * Register the module and its read-only preview route.
	 */
	public function load() {
		add_action( 'init', array( self::class, 'register_module' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	/**
	 * Register the module metadata and frontend renderer.
	 */
	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/event-dates-module/visual-builder/src',
			array(
				'render_callback' => array( self::class, 'render_callback' ),
			)
		);
	}

	/**
	 * Register the Visual Builder server-rendered preview endpoint.
	 */
	public static function register_rest_routes() {
		register_rest_route(
			'wp-seed-events/v1',
			'/divi-event-dates-preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => array( self::class, 'rest_preview_permissions' ),
				'args'                => array(
					'post_id'             => array( 'sanitize_callback' => 'absint' ),
					'loop_id'             => array( 'sanitize_callback' => 'absint' ),
					'title'               => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'heading_level'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'scope'               => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_cancelled'      => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_times'          => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_calendar_links' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}

	/**
	 * Restrict previews to authenticated editors.
	 */
	public static function rest_preview_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Return the shared renderer HTML for the Visual Builder.
	 */
	public static function rest_preview( WP_REST_Request $request ) {
		$event_id = wp_seed_events_divi_resolve_event_id(
			array(
				'loop_id' => absint( $request->get_param( 'loop_id' ) ),
				'post_id' => absint( $request->get_param( 'post_id' ) ),
			)
		);

		$options = self::normalize_options(
			array(
				'title'               => $request->get_param( 'title' ),
				'heading_level'       => $request->get_param( 'heading_level' ),
				'scope'               => $request->get_param( 'scope' ),
				'show_cancelled'      => $request->get_param( 'show_cancelled' ),
				'show_times'          => $request->get_param( 'show_times' ),
				'show_calendar_links' => $request->get_param( 'show_calendar_links' ),
			)
		);

		return rest_ensure_response(
			array(
				'html' => self::render_dates( $event_id, $options ),
			)
		);
	}

	/**
	 * Render the module on the frontend.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$options  = self::normalize_options( self::get_content_values( $attrs ) );
		$html     = self::render_dates( $event_id, $options );

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

		$module_children = $elements->style_components(
			array(
				'attrName' => 'module',
			)
		) . $module_inner;

		return Module::render(
			array(
				'orderIndex'          => $block->parsed_block['orderIndex'] ?? 0,
				'storeInstance'       => $block->parsed_block['storeInstance'] ?? '',
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'] ?? '',
				'moduleClassName'     => 'wp_seed_events_divi_event_dates',
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'classnamesFunction'  => array( self::class, 'module_classnames' ),
				'stylesComponent'     => array( self::class, 'module_styles' ),
				'scriptDataComponent' => array( self::class, 'module_script_data' ),
				'children'            => $module_children,
			)
		);
	}

	/**
	 * Render the shared dates section from one Event Data API result.
	 */
	private static function render_dates( $event_id, $options ) {
		$event_id = absint( $event_id );

		if ( 0 === $event_id ) {
			return '';
		}

		$event = wp_seed_events_get_event_data( $event_id );

		if ( array() === $event ) {
			return '';
		}

		return (string) wp_seed_events_render_public_event_dates_section( $event, $options );
	}

	/**
	 * Read the persistent content values saved by Divi.
	 */
	private static function get_content_values( $attrs ) {
		return isset( $attrs['content']['innerContent']['desktop']['value'] )
			&& is_array( $attrs['content']['innerContent']['desktop']['value'] )
			? $attrs['content']['innerContent']['desktop']['value']
			: array();
	}

	/**
	 * Convert Divi field values to the shared renderer contract.
	 */
	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		$title  = array_key_exists( 'title', $values ) && null !== $values['title']
			? (string) $values['title']
			: 'Dates';

		$heading_level = sanitize_key( (string) ( $values['heading_level'] ?? 'h2' ) );
		$scope         = sanitize_key( (string) ( $values['scope'] ?? 'all' ) );

		if ( ! in_array( $heading_level, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) {
			$heading_level = 'h2';
		}

		if ( ! in_array( $scope, array( 'all', 'upcoming', 'past' ), true ) ) {
			$scope = 'all';
		}

		return array(
			'title'               => $title,
			'heading_level'       => $heading_level,
			'scope'               => $scope,
			'show_cancelled'      => self::is_enabled( $values['show_cancelled'] ?? 'on' ),
			'show_times'          => self::is_enabled( $values['show_times'] ?? 'on' ),
			'show_calendar_links' => self::is_enabled( $values['show_calendar_links'] ?? 'on' ),
		);
	}

	/**
	 * Normalize a Divi toggle value.
	 */
	private static function is_enabled( $value ) {
		if ( false === $value || 0 === $value ) {
			return false;
		}

		if ( ! is_scalar( $value ) ) {
			return true;
		}

		return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'off' ), true );
	}


	/**
	 * Register standard Divi element class names.
	 */
	public static function module_classnames( $args ) {
		$args['classnamesInstance']->add(
			ElementClassnames::classnames(
				array(
					'attrs' => $args['attrs']['module']['decoration'] ?? array(),
				)
			)
		);
	}

	/**
	 * Render all design attributes against the shared renderer selectors.
	 */
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

		foreach ( array( 'titleStyle', 'dateStyle', 'timeStyle', 'statusStyle', 'calendarLinkStyle', 'occurrenceStyle' ) as $attr_name ) {
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

	/**
	 * Register standard Divi module script data.
	 */
	public static function module_script_data( $args ) {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}
}
