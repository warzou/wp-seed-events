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
 * Divi 5 adapter for the shared public event people renderer.
 */
class WP_Seed_Events_Divi_Event_People_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/event-people';

	public function load() {
		add_action( 'init', array( self::class, 'register_module' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/event-people-module/visual-builder/src',
			array( 'render_callback' => array( self::class, 'render_callback' ) )
		);
	}

	public static function register_rest_routes() {
		register_rest_route(
			'wp-seed-events/v1',
			'/divi-event-people-preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => 'wp_seed_events_divi_rest_preview_permissions',
			)
		);
	}

	public static function rest_preview( WP_REST_Request $request ) {
		$event_id = wp_seed_events_divi_rest_preview_event_id( $request );
		$options  = self::normalize_options( $request->get_params() );
		return rest_ensure_response( array( 'html' => self::render_people( $event_id, $options ) ) );
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$values   = self::get_content_values( $attrs );
		$values['contact_layout_tablet'] = self::get_responsive_content_value( $attrs, 'contact_layout', 'tablet', $values['contact_layout'] ?? 'stacked' );
		$values['contact_layout_phone']  = self::get_responsive_content_value( $attrs, 'contact_layout', 'phone', $values['contact_layout_tablet'] );
		$values['contact_separator_styles']      = self::get_responsive_separator_values( $attrs, 'contactSeparatorStyle' );
		if ( isset( $attrs['nameContactSeparatorStyle'] ) ) {
			$values['name_contact_separator_styles'] = self::get_responsive_separator_values( $attrs, 'nameContactSeparatorStyle' );
		}
		$options  = self::normalize_options( $values );
		$html     = self::render_people( $event_id, $options );
		$html     = wp_seed_events_divi_apply_list_styles(
			$html,
			wp_seed_events_divi_list_style_values( $attrs, 'eventListStyle' ),
			'wp-seed-event-people__list'
		);

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
				'moduleClassName'     => 'wp_seed_events_divi_event_people',
				'name'                => $block->block_type->name,
				'moduleCategory'      => $block->block_type->category,
				'classnamesFunction'  => array( self::class, 'module_classnames' ),
				'stylesComponent'     => array( self::class, 'module_styles' ),
				'scriptDataComponent' => array( self::class, 'module_script_data' ),
				'children'            => $module_children,
			)
		);
	}

	private static function render_people( $event_id, $options ) {
		$event_id = absint( $event_id );

		if ( 0 === $event_id ) {
			return '';
		}

		$event = wp_seed_events_get_event_data( $event_id );

		if ( array() === $event ) {
			return '';
		}

		return (string) wp_seed_events_render_public_event_people_section( $event, $options );
	}

	private static function get_content_values( $attrs ) {
		return isset( $attrs['content']['innerContent']['desktop']['value'] )
			&& is_array( $attrs['content']['innerContent']['desktop']['value'] )
			? $attrs['content']['innerContent']['desktop']['value']
			: array();
	}

	private static function get_responsive_content_value( $attrs, $field, $breakpoint, $fallback ) {
		$inheritance = 'tablet' === $breakpoint ? array( 'tablet', 'desktop' ) : array( 'phone', 'tablet', 'desktop' );
		foreach ( $inheritance as $candidate ) {
			$value = $attrs['content']['innerContent'][ $candidate ]['value'][ $field ] ?? null;
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return (string) $value;
			}
		}

		return $fallback;
	}

	private static function get_responsive_separator_values( $attrs, $attribute_name ) {
		$advanced = is_array( $attrs[ $attribute_name ]['advanced'] ?? null ) ? $attrs[ $attribute_name ]['advanced'] : array();
		$config = array(
			'color'       => '',
			'fontSize'    => '1em',
			'spaceBefore' => '0.35em',
			'spaceAfter'  => '0.35em',
		);
		$styles = array();
		foreach ( array( 'desktop', 'tablet', 'phone' ) as $breakpoint ) {
			$styles[ $breakpoint ] = array();
			foreach ( $config as $field => $fallback ) {
				$styles[ $breakpoint ][ $field ] = self::resolve_divi_style_value( $advanced[ $field ] ?? array(), $breakpoint, $field, $fallback );
			}
		}

		return $styles;
	}

	private static function resolve_divi_style_value( $attribute, $breakpoint, $field, $fallback ) {
		$inheritance = array(
			'desktop' => array( 'desktop' ),
			'tablet'  => array( 'tablet', 'desktop' ),
			'phone'   => array( 'phone', 'tablet', 'desktop' ),
		);
		foreach ( $inheritance[ $breakpoint ] as $candidate ) {
			$value = $attribute[ $candidate ]['value'] ?? null;
			if ( is_array( $value ) && is_scalar( $value[ $field ] ?? null ) ) {
				return (string) $value[ $field ];
			}
			if ( is_scalar( $value ) ) {
				return (string) $value;
			}
		}

		return $fallback;
	}

	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		$legacy_role_toggles = array(
			'role_organizer'            => 'organizer',
			'role_speaker'              => 'speaker',
			'role_contact'              => 'contact',

			// Saved Divi modules can still carry either historical toggle.
			'role_registration_contact' => 'contact',
			'role_information_contact'  => 'contact',
		);
		$has_role_toggles = false;
		$roles            = array();

		foreach ( $legacy_role_toggles as $field => $role ) {
			if ( array_key_exists( $field, $values ) ) {
				$has_role_toggles = true;
			}

			if ( wp_seed_events_public_boolean_option( $values[ $field ] ?? false, false ) ) {
				if ( ! in_array( $role, $roles, true ) ) {
					$roles[] = $role;
				}
			}
		}

		foreach ( $values as $field => $enabled ) {
			if ( ! is_string( $field ) || 0 !== strpos( $field, 'role_' ) || isset( $legacy_role_toggles[ $field ] ) ) {
				continue;
			}
			$role = sanitize_key( substr( $field, 5 ) );
			if ( '' === $role ) {
				continue;
			}
			$has_role_toggles = true;
			if ( wp_seed_events_public_boolean_option( $enabled, false ) && ! in_array( $role, $roles, true ) ) {
				$roles[] = $role;
			}
		}

		$legacy_role = wp_seed_events_public_people_role_option( $values['role'] ?? 'all' );
		if ( ! $has_role_toggles && 'all' !== $legacy_role ) {
			$roles = array( $legacy_role );
		}

		$people_contract = is_scalar( $values['people_contract'] ?? null ) ? (string) $values['people_contract'] : '';
		$people_contract = in_array( $people_contract, array( 'composable-v1', 'composable-v2', 'composable-v3' ), true ) ? $people_contract : 'legacy';
		$is_v3           = 'composable-v3' === $people_contract;

		return array(
			'title'         => wp_seed_events_divi_optional_title( $values, 'Contacts et intervenants' ),
			'heading_level' => wp_seed_events_public_heading_level_option( $values['heading_level'] ?? 'h2' ),
			'roles'         => $roles,
			'role'          => $legacy_role,
			'show_name'     => wp_seed_events_public_boolean_option( $values['show_name'] ?? true, true ),
			'show_roles'    => wp_seed_events_public_boolean_option(
				$values['show_roles'] ?? ( 'legacy' === $people_contract ),
				'legacy' === $people_contract
			),
			'show_email'    => wp_seed_events_public_boolean_option( $values['show_email'] ?? true, true ),
			'show_phone'    => wp_seed_events_public_boolean_option( $values['show_phone'] ?? true, true ),
			'show_link'     => wp_seed_events_public_boolean_option( $values['show_link'] ?? true, true ),
			'link_phone'    => wp_seed_events_public_boolean_option( $values['link_phone'] ?? true, true ),
			'link_email'    => wp_seed_events_public_boolean_option( $values['link_email'] ?? true, true ),
			'link_url'      => wp_seed_events_public_boolean_option( $values['link_url'] ?? true, true ),
			'layout'        => wp_seed_events_public_event_people_layout_option( $values['layout'] ?? 'list' ),
			'contract'      => $people_contract,
			'contact_layouts' => array(
				'desktop' => wp_seed_events_public_people_contact_layout_option( $values['contact_layout'] ?? 'stacked' ),
				'tablet'  => wp_seed_events_public_people_contact_layout_option( $values['contact_layout_tablet'] ?? ( $values['contact_layout'] ?? 'stacked' ) ),
				'phone'   => wp_seed_events_public_people_contact_layout_option( $values['contact_layout_phone'] ?? ( $values['contact_layout_tablet'] ?? ( $values['contact_layout'] ?? 'stacked' ) ) ),
			),
			'show_contact_separator' => wp_seed_events_public_boolean_option( $values['show_contact_separator'] ?? false, false ),
			'contact_separator' => wp_seed_events_public_date_separator_character_option( $values['contact_separator'] ?? "\u{2014}" ),
			'contact_separator_styles' => is_array( $values['contact_separator_styles'] ?? null ) ? $values['contact_separator_styles'] : array(),
			'show_name_contact_separator' => wp_seed_events_public_boolean_option(
				$is_v3 ? ( $values['show_name_contact_separator'] ?? false ) : ( $values['show_name_contact_separator'] ?? ( $values['show_contact_separator'] ?? false ) ),
				false
			),
			'name_contact_separator' => wp_seed_events_public_date_separator_character_option(
				$is_v3 ? ( $values['name_contact_separator'] ?? "\u{2014}" ) : ( $values['name_contact_separator'] ?? ( $values['contact_separator'] ?? "\u{2014}" ) )
			),
			'name_contact_separator_styles' => is_array( $values['name_contact_separator_styles'] ?? null ) && array() !== $values['name_contact_separator_styles']
				? $values['name_contact_separator_styles']
				: ( $is_v3 ? array() : ( is_array( $values['contact_separator_styles'] ?? null ) ? $values['contact_separator_styles'] : array() ) ),
			'phone_action'  => in_array( $people_contract, array( 'composable-v2', 'composable-v3' ), true )
				? ''
				: wp_seed_events_public_people_phone_action_option( $values['phone_action'] ?? '', wp_seed_events_public_boolean_option( $values['link_phone'] ?? true, true ) ? 'call' : 'none' ),
			'email_clickable' => array_key_exists( 'email_clickable', $values ) ? wp_seed_events_public_boolean_option( $values['email_clickable'], true ) : null,
			'phone_clickable' => array_key_exists( 'phone_clickable', $values ) ? wp_seed_events_public_boolean_option( $values['phone_clickable'], true ) : null,
			'site_clickable' => array_key_exists( 'site_clickable', $values ) ? wp_seed_events_public_boolean_option( $values['site_clickable'], true ) : null,
			'site_label'    => 'composable-v1' === $people_contract ? wp_seed_events_public_people_site_label_option( $values['site_label'] ?? '' ) : '',
		);
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

		foreach ( array( 'sectionStyle', 'titleStyle', 'listStyle', 'itemStyle', 'nameStyle', 'rolesStyle', 'roleStyle', 'contactsStyle', 'emailLinkStyle', 'phoneLinkStyle', 'publicLinkStyle', 'contactSeparatorStyle', 'nameContactSeparatorStyle' ) as $attr_name ) {
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
