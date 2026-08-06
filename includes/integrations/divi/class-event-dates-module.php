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
					'mode'                => array( 'sanitize_callback' => 'sanitize_key' ),
					'title'               => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'heading_level'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'scope'               => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_cancelled'      => array( 'sanitize_callback' => 'sanitize_key' ),
					'format'              => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_times'          => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_calendar_links' => array( 'sanitize_callback' => 'sanitize_key' ),
					'list_marker_type'     => array( 'sanitize_callback' => 'sanitize_key' ),
					'list_marker_position' => array( 'sanitize_callback' => 'sanitize_key' ),
					'list_indent'          => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'occurrence_gap'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'marker_color'         => array( 'sanitize_callback' => 'sanitize_text_field' ),
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
				'mode'                => $request->get_param( 'mode' ),
				'scope'               => $request->get_param( 'scope' ),
				'show_cancelled'      => $request->get_param( 'show_cancelled' ),
				'show_times'          => $request->get_param( 'show_times' ),
				'format'              => $request->get_param( 'format' ),
				'show_calendar_links' => $request->get_param( 'show_calendar_links' ),
				'list_marker_type'     => $request->get_param( 'list_marker_type' ),
				'list_marker_position' => $request->get_param( 'list_marker_position' ),
				'list_indent'          => $request->get_param( 'list_indent' ),
				'occurrence_gap'       => $request->get_param( 'occurrence_gap' ),
				'marker_color'         => $request->get_param( 'marker_color' ),
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
		$options  = self::normalize_options(
			array_merge( self::get_content_values( $attrs ), self::get_list_values( $attrs ) )
		);
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
	 * Read the persistent list design values saved by Divi.
	 */
	private static function get_list_values( $attrs ) {
		$advanced = isset( $attrs['listStyle']['advanced'] ) && is_array( $attrs['listStyle']['advanced'] )
			? $attrs['listStyle']['advanced']
			: array();
		$mapping  = array(
			'markerType'     => 'list_marker_type',
			'markerPosition' => 'list_marker_position',
			'leftIndent'     => 'list_indent',
			'occurrenceGap'  => 'occurrence_gap',
			'markerColor'    => 'marker_color',
		);
		$values   = array();

		foreach ( $mapping as $attribute => $option ) {
			$value = $advanced[ $attribute ]['desktop']['value'] ?? null;

			if ( is_array( $value ) && is_scalar( $value[ $attribute ] ?? null ) ) {
				$value = $value[ $attribute ];
			}

			if ( is_scalar( $value ) ) {
				$values[ $option ] = (string) $value;
			}
		}

		return $values;
	}

	/**
	 * Convert Divi field values to the shared renderer contract.
	 */
	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		$title  = array_key_exists( 'title', $values ) && null !== $values['title']
			? (string) $values['title']
			: 'Dates';
		$mode   = wp_seed_events_public_date_mode_option( $values['mode'] ?? 'all' );
		$scope  = wp_seed_events_public_date_scope_option( $values['scope'] ?? 'all' );
		$choice = is_scalar( $values['date_selection'] ?? null )
			? sanitize_key( (string) $values['date_selection'] )
			: '';

		if ( 'next' === $choice ) {
			$mode  = 'next';
			$scope = 'upcoming';
		} elseif ( in_array( $choice, array( 'first', 'last' ), true ) ) {
			$mode  = $choice;
			$scope = 'all';
		} elseif ( 'all_upcoming' === $choice ) {
			$mode  = 'all';
			$scope = 'upcoming';
		} elseif ( 'all_past' === $choice ) {
			$mode  = 'all';
			$scope = 'past';
		} elseif ( 'all' === $choice ) {
			$mode  = 'all';
			$scope = 'all';
		}

		return array(
			'title'               => $title,
			'heading_level'       => wp_seed_events_public_heading_level_option( $values['heading_level'] ?? 'h2' ),
			'mode'                => $mode,
			'scope'               => $scope,
			'show_cancelled'      => self::is_enabled( $values['show_cancelled'] ?? 'on' ),
			'show_times'          => self::is_enabled( $values['show_times'] ?? 'on' ),
			'format'              => wp_seed_events_public_date_format_option( $values['format'] ?? 'long' ),
			'show_calendar_links' => self::is_enabled( $values['show_calendar_links'] ?? 'on' ),
			'list_marker_type'     => $values['list_marker_type'] ?? 'disc',
			'list_marker_position' => $values['list_marker_position'] ?? 'outside',
			'list_indent'          => $values['list_indent'] ?? '2.5em',
			'occurrence_gap'       => $values['occurrence_gap'] ?? '0px',
			'marker_color'         => $values['marker_color'] ?? '',
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
