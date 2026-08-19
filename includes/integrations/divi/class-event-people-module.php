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
		$options  = self::normalize_options( self::get_content_values( $attrs ) );
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

	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		$role_toggles = array(
			'role_organizer'            => 'organizer',
			'role_speaker'              => 'speaker',
			'role_registration_contact' => 'registration_contact',
			'role_information_contact'  => 'information_contact',
		);
		$has_role_toggles = false;
		$roles            = array();

		foreach ( $role_toggles as $field => $role ) {
			if ( array_key_exists( $field, $values ) ) {
				$has_role_toggles = true;
			}

			if ( wp_seed_events_public_boolean_option( $values[ $field ] ?? false, false ) ) {
				$roles[] = $role;
			}
		}

		$legacy_role = wp_seed_events_public_people_role_option( $values['role'] ?? 'all' );
		if ( ! $has_role_toggles && 'all' !== $legacy_role ) {
			$roles = array( $legacy_role );
		}

		return array(
			'title'         => wp_seed_events_divi_optional_title( $values, 'Contacts et intervenants' ),
			'heading_level' => wp_seed_events_public_heading_level_option( $values['heading_level'] ?? 'h2' ),
			'roles'         => $roles,
			'role'          => $legacy_role,
			'show_name'     => wp_seed_events_public_boolean_option( $values['show_name'] ?? true, true ),
			'show_roles'    => wp_seed_events_public_boolean_option( $values['show_roles'] ?? true, true ),
			'show_email'    => wp_seed_events_public_boolean_option( $values['show_email'] ?? true, true ),
			'show_phone'    => wp_seed_events_public_boolean_option( $values['show_phone'] ?? true, true ),
			'show_link'     => wp_seed_events_public_boolean_option( $values['show_link'] ?? true, true ),
			'link_phone'    => wp_seed_events_public_boolean_option( $values['link_phone'] ?? true, true ),
			'link_email'    => wp_seed_events_public_boolean_option( $values['link_email'] ?? true, true ),
			'link_url'      => wp_seed_events_public_boolean_option( $values['link_url'] ?? true, true ),
			'layout'        => wp_seed_events_public_event_people_layout_option( $values['layout'] ?? 'list' ),
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

		foreach ( array( 'sectionStyle', 'titleStyle', 'listStyle', 'itemStyle', 'nameStyle', 'rolesStyle', 'roleStyle', 'contactsStyle', 'emailLinkStyle', 'phoneLinkStyle', 'publicLinkStyle' ) as $attr_name ) {
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
