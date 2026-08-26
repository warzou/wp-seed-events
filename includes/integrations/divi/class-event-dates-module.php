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
					'show_title'          => array( 'sanitize_callback' => 'sanitize_key' ),
					'heading_level'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'scope'               => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_cancelled'      => array( 'sanitize_callback' => 'sanitize_key' ),
					'format'              => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_dates'          => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_times'          => array( 'sanitize_callback' => 'sanitize_key' ),
					'time_layout'         => array( 'sanitize_callback' => 'sanitize_key' ),
					'time_layout_tablet'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'time_layout_phone'   => array( 'sanitize_callback' => 'sanitize_key' ),
					'responsive_time_layout_requested' => array( 'sanitize_callback' => 'rest_sanitize_boolean' ),
					'show_separator'      => array( 'sanitize_callback' => 'sanitize_key' ),
					'separator_character' => array( 'sanitize_callback' => 'sanitize_text_field' ),
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
				'show_title'          => $request->get_param( 'show_title' ),
				'heading_level'       => $request->get_param( 'heading_level' ),
				'mode'                => $request->get_param( 'mode' ),
				'scope'               => $request->get_param( 'scope' ),
				'show_cancelled'      => $request->get_param( 'show_cancelled' ),
				'show_dates'          => $request->get_param( 'show_dates' ),
				'show_times'          => $request->get_param( 'show_times' ),
				'time_layout'         => $request->get_param( 'time_layout' ),
				'time_layouts'        => array(
					'desktop' => $request->get_param( 'time_layout' ),
					'tablet'  => $request->get_param( 'time_layout_tablet' ),
					'phone'   => $request->get_param( 'time_layout_phone' ),
				),
				'responsive_time_layout_requested' => $request->get_param( 'responsive_time_layout_requested' ),
				'show_separator'      => $request->get_param( 'show_separator' ),
				'separator_character' => $request->get_param( 'separator_character' ),
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
		$content_values = self::get_content_values( $attrs );
		$options  = self::normalize_options(
			array_merge(
				$content_values,
				self::get_list_values( $attrs ),
				array(
					'time_layouts' => self::get_responsive_time_layouts( $attrs ),
					'responsive_time_layout_requested' => self::has_responsive_time_layout_override( $attrs ),
					'separator_styles' => self::get_responsive_separator_values( $attrs ),
				)
			)
		);
		$html     = self::render_dates( $event_id, $options );
		$html     = self::apply_responsive_list_styles( $html, self::get_responsive_list_values( $attrs ) );

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
	 * Resolve the responsive time layout using Divi's breakpoint inheritance.
	 */
	private static function get_responsive_time_layouts( $attrs ) {
		$inheritance = array(
			'desktop' => array( 'desktop' ),
			'tablet'  => array( 'tablet', 'desktop' ),
			'phone'   => array( 'phone', 'tablet', 'desktop' ),
		);
		$layouts = array();

		foreach ( $inheritance as $breakpoint => $candidates ) {
			$value = '';
			foreach ( $candidates as $candidate ) {
				$candidate_value = $attrs['content']['innerContent'][ $candidate ]['value']['time_layout'] ?? '';
				if ( is_scalar( $candidate_value ) && '' !== trim( (string) $candidate_value ) ) {
					$value = (string) $candidate_value;
					break;
				}
			}
			$layouts[ $breakpoint ] = wp_seed_events_public_date_component_layout_option( $value );
		}

		return $layouts;
	}

	/**
	 * Tell the renderer whether Divi stored a tablet or phone override.
	 */
	private static function has_responsive_time_layout_override( $attrs ) {
		foreach ( array( 'tablet', 'phone' ) as $breakpoint ) {
			$value = $attrs['content']['innerContent'][ $breakpoint ]['value']['time_layout'] ?? '';
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize the lightweight separator styles with Divi breakpoint inheritance.
	 */
	private static function get_responsive_separator_values( $attrs ) {
		$advanced = is_array( $attrs['separatorStyle']['advanced'] ?? null )
			? $attrs['separatorStyle']['advanced']
			: array();
		$config = array(
			'color'       => array( 'default' => '', 'color' => true ),
			'fontSize'    => array( 'default' => '1em' ),
			'spaceBefore' => array( 'default' => '0.35em' ),
			'spaceAfter'  => array( 'default' => '0.35em' ),
		);
		$styles = array();

		foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
			$styles[ $breakpoint ] = array();
			foreach ( $config as $field => $field_config ) {
				$fallback = 'desktop' === $breakpoint
					? $field_config['default']
					: $styles[ 'tablet' === $breakpoint ? 'desktop' : 'tablet' ][ $field ];
				$value = self::resolve_divi_style_value( $advanced[ $field ] ?? array(), $breakpoint, 'value', $field, $fallback );
				$styles[ $breakpoint ][ $field ] = ! empty( $field_config['color'] )
					? wp_seed_events_public_date_list_marker_color_option( $value )
					: wp_seed_events_public_date_list_dimension_option( $value, $fallback );
			}
		}

		return $styles;
	}

	/**
	 * Read one scalar from the responsive/state shapes emitted by Divi 5.
	 */
	private static function resolve_divi_style_value( $attribute, $breakpoint, $state, $field, $fallback = '' ) {
		$inheritance = array(
			'desktop' => array( 'desktop' ),
			'tablet'  => array( 'tablet', 'desktop' ),
			'phone'   => array( 'phone', 'tablet', 'desktop' ),
		);
		$breakpoints = $inheritance[ $breakpoint ] ?? array( $breakpoint, 'desktop' );

		foreach ( $breakpoints as $candidate_breakpoint ) {
			$breakpoint_value = is_array( $attribute ) && isset( $attribute[ $candidate_breakpoint ] )
				? $attribute[ $candidate_breakpoint ]
				: null;
			$states = 'value' === $state ? array( 'value' ) : array( $state, 'value' );

			foreach ( $states as $candidate_state ) {
				$value = is_array( $breakpoint_value ) && array_key_exists( $candidate_state, $breakpoint_value )
					? self::scalar_divi_style_value( $breakpoint_value[ $candidate_state ], $field )
					: null;

				if ( null !== $value ) {
					return $value;
				}
			}

			$value = self::scalar_divi_style_value( $breakpoint_value, $field );
			if ( null !== $value ) {
				return $value;
			}
		}

		if ( is_array( $attribute ) && array_key_exists( $state, $attribute ) ) {
			$value = self::scalar_divi_style_value( $attribute[ $state ], $field );
			if ( null !== $value ) {
				return $value;
			}
		}

		$value = self::scalar_divi_style_value( $attribute, $field );
		return null !== $value ? $value : $fallback;
	}

	/**
	 * Unwrap scalar and nested custom-field values without property-specific branches.
	 */
	private static function scalar_divi_style_value( $value, $field ) {
		if ( is_scalar( $value ) ) {
			return (string) $value;
		}

		if ( ! is_array( $value ) ) {
			return null;
		}

		if ( '' !== $field && is_scalar( $value[ $field ] ?? null ) ) {
			return (string) $value[ $field ];
		}

		return array_key_exists( 'value', $value )
			? self::scalar_divi_style_value( $value['value'], $field )
			: null;
	}

	/**
	 * Normalize all custom list controls for each Divi breakpoint.
	 */
	private static function get_responsive_list_values( $attrs ) {
		return wp_seed_events_divi_list_style_values( $attrs, 'listStyle' );
	}

	/**
	 * Read desktop list values for the shared public renderer contract.
	 */
	private static function get_list_values( $attrs ) {
		$desktop = self::get_responsive_list_values( $attrs )['desktop'];

		return array(
			'list_marker_type'     => $desktop['markerType'],
			'list_marker_position' => $desktop['markerPosition'],
			'list_indent'          => $desktop['leftIndent'],
			'occurrence_gap'       => $desktop['occurrenceGap'],
			'marker_color'         => $desktop['markerColor'],
		);
	}

	/**
	 * Add per-module responsive variables without changing the shared renderer API.
	 */
	private static function apply_responsive_list_styles( $html, $styles ) {
		if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );
		if ( ! $processor->next_tag( array( 'class_name' => 'wp-seed-event-dates' ) ) ) {
			return $html;
		}

		$property_map = array(
			'markerType'     => 'marker-type',
			'markerPosition' => 'marker-position',
			'leftIndent'     => 'list-indent',
			'occurrenceGap'  => 'occurrence-gap',
			'markerColor'    => 'marker-color',
		);
		$declarations = array();

		foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
			foreach ( $property_map as $field => $property ) {
				$value = $styles[ $breakpoint ][ $field ];
				if ( 'markerColor' === $field && '' === $value ) {
					$value = 'currentColor';
				}
				$declarations[] = '--wp-seed-event-dates-' . $property . '-' . $breakpoint . ':' . $value;
			}
		}

		$existing_style = trim( (string) $processor->get_attribute( 'style' ), " \t\n\r\0\x0B;" );
		$style = ( '' !== $existing_style ? $existing_style . ';' : '' ) . implode( ';', $declarations );
		$processor->set_attribute( 'style', $style );

		return $processor->get_updated_html();
	}
	/**
	 * Convert Divi field values to the shared renderer contract.
	 */
	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		$title  = wp_seed_events_divi_optional_title( $values, 'Dates' );
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
			'show_dates'          => self::is_enabled( $values['show_dates'] ?? 'on' ),
			'show_times'          => self::is_enabled( $values['show_times'] ?? 'on' ),
			'time_layout'         => wp_seed_events_public_date_component_layout_option( $values['time_layout'] ?? 'below' ),
			'time_layouts'        => is_array( $values['time_layouts'] ?? null ) ? $values['time_layouts'] : array(),
			'responsive_time_layout_requested' => ! empty( $values['responsive_time_layout_requested'] ),
			'show_separator'      => self::is_enabled( $values['show_separator'] ?? 'off' ),
			'separator_character' => wp_seed_events_public_date_separator_character_option( $values['separator_character'] ?? "\u{2014}" ),
			'separator_styles'    => is_array( $values['separator_styles'] ?? null ) ? $values['separator_styles'] : array(),
			'format'              => wp_seed_events_public_date_format_option( $values['format'] ?? 'long' ),
			'show_calendar_links' => self::is_enabled( $values['show_calendar_links'] ?? 'on' ),
			'list_marker_type'     => $values['list_marker_type'] ?? 'none',
			'list_marker_position' => $values['list_marker_position'] ?? 'outside',
			'list_indent'          => $values['list_indent'] ?? '0px',
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

		foreach ( array( 'titleStyle', 'dateStyle', 'timeStyle', 'separatorStyle', 'statusStyle', 'calendarLinkStyle', 'occurrenceStyle' ) as $attr_name ) {
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
