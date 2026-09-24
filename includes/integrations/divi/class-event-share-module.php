<?php

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\IconLibrary\IconFont\Utils as IconFontUtils;
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
				'args'                => array(
					'post_id'          => array( 'sanitize_callback' => 'absint' ),
					'loop_id'          => array( 'sanitize_callback' => 'absint' ),
					'display_mode'     => array( 'sanitize_callback' => 'sanitize_key' ),
					'action_order'     => array( 'sanitize_callback' => 'sanitize_key' ),
					'label'            => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'show_share'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'share_icon'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'share_icon_placement' => array( 'sanitize_callback' => 'sanitize_key' ),
					'share_icon_on_hover'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_copy'        => array( 'sanitize_callback' => 'sanitize_key' ),
					'copy_label'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'copy_icon'        => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'copy_icon_placement' => array( 'sanitize_callback' => 'sanitize_key' ),
					'copy_icon_on_hover'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_email'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'email_label'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'email_icon'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'email_icon_placement' => array( 'sanitize_callback' => 'sanitize_key' ),
					'email_icon_on_hover'  => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}

	public static function rest_preview( WP_REST_Request $request ) {
		return rest_ensure_response(
			array(
				'html' => self::render_share(
					wp_seed_events_divi_rest_preview_event_id( $request ),
					self::normalize_options( $request->get_params(), array(), true )
				),
			)
		);
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$html     = self::render_share(
			$event_id,
			self::normalize_options(
				self::get_content_values( $attrs ),
				array(
					'share_icon'           => self::get_button_icon( $attrs, 'shareActionStyle' ),
					'share_icon_placement' => self::get_button_icon_setting( $attrs, 'shareActionStyle', 'placement', 'right' ),
					'share_icon_on_hover'  => self::get_button_icon_setting( $attrs, 'shareActionStyle', 'onHover', 'on' ),
					'copy_icon'            => self::get_button_icon( $attrs, 'copyActionStyle' ),
					'copy_icon_placement'  => self::get_button_icon_setting( $attrs, 'copyActionStyle', 'placement', 'right' ),
					'copy_icon_on_hover'   => self::get_button_icon_setting( $attrs, 'copyActionStyle', 'onHover', 'on' ),
					'email_icon'           => self::get_button_icon( $attrs, 'emailActionStyle' ),
					'email_icon_placement' => self::get_button_icon_setting( $attrs, 'emailActionStyle', 'placement', 'right' ),
					'email_icon_on_hover'  => self::get_button_icon_setting( $attrs, 'emailActionStyle', 'onHover', 'on' ),
				),
				true
			)
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

	private static function render_share( $event_id, $options ) {
		$event_id = absint( $event_id );

		if ( 0 === $event_id ) {
			return '';
		}

		$event = wp_seed_events_get_event_data( $event_id );

		return array() === $event ? '' : (string) wp_seed_events_render_event_share_menu( $event, $options );
	}

	private static function get_content_values( $attrs ) {
		return isset( $attrs['content']['innerContent']['desktop']['value'] )
			&& is_array( $attrs['content']['innerContent']['desktop']['value'] )
			? $attrs['content']['innerContent']['desktop']['value']
			: array();
	}

	private static function get_button_icon( $attrs, $attr_name ) {
		$icon = $attrs[ $attr_name ]['decoration']['button']['desktop']['value']['icon'] ?? array();

		if ( ! is_array( $icon ) || 'on' !== (string) ( $icon['enable'] ?? '' ) ) {
			return '';
		}

		$settings = is_array( $icon['settings'] ?? null ) ? $icon['settings'] : array();
		$processed = empty( $settings ) ? null : IconFontUtils::process_font_icon( $settings );

		return is_string( $processed ) ? $processed : '';
	}

	private static function get_button_icon_setting( $attrs, $attr_name, $setting, $default ) {
		$value = $attrs[ $attr_name ]['decoration']['button']['desktop']['value']['icon'][ $setting ] ?? $default;

		return is_scalar( $value ) ? sanitize_key( (string) $value ) : $default;
	}

	private static function normalize_options( $values, $icons = array(), $use_divi_button = false ) {
		$values = is_array( $values ) ? $values : array();
		$icons  = is_array( $icons ) ? $icons : array();
		$share_icon = array_key_exists( 'share_icon', $icons ) ? $icons['share_icon'] : ( $values['share_icon'] ?? '' );
		$copy_icon  = array_key_exists( 'copy_icon', $icons ) ? $icons['copy_icon'] : ( $values['copy_icon'] ?? '' );
		$email_icon = array_key_exists( 'email_icon', $icons ) ? $icons['email_icon'] : ( $values['email_icon'] ?? '' );
		$share_icon_placement = array_key_exists( 'share_icon_placement', $icons ) ? $icons['share_icon_placement'] : ( $values['share_icon_placement'] ?? 'right' );
		$copy_icon_placement  = array_key_exists( 'copy_icon_placement', $icons ) ? $icons['copy_icon_placement'] : ( $values['copy_icon_placement'] ?? 'right' );
		$email_icon_placement = array_key_exists( 'email_icon_placement', $icons ) ? $icons['email_icon_placement'] : ( $values['email_icon_placement'] ?? 'right' );
		$share_icon_on_hover  = array_key_exists( 'share_icon_on_hover', $icons ) ? $icons['share_icon_on_hover'] : ( $values['share_icon_on_hover'] ?? 'on' );
		$copy_icon_on_hover   = array_key_exists( 'copy_icon_on_hover', $icons ) ? $icons['copy_icon_on_hover'] : ( $values['copy_icon_on_hover'] ?? 'on' );
		$email_icon_on_hover  = array_key_exists( 'email_icon_on_hover', $icons ) ? $icons['email_icon_on_hover'] : ( $values['email_icon_on_hover'] ?? 'on' );

		return array(
			'display_mode'     => wp_seed_events_share_display_mode( $values['display_mode'] ?? 'text_icon' ),
			'action_order'     => wp_seed_events_share_action_order_key( $values['action_order'] ?? 'share_copy_email' ),
			'label'            => wp_seed_events_share_text_option( $values['label'] ?? '', __( 'Partager', 'wp-seed-events' ) ),
			'show_share'       => wp_seed_events_public_boolean_option( $values['show_share'] ?? true, true ),
			'share_icon'       => sanitize_text_field( (string) $share_icon ),
			'share_icon_placement' => 'left' === $share_icon_placement ? 'left' : 'right',
			'share_icon_on_hover'  => 'off' === $share_icon_on_hover ? 'off' : 'on',
			'show_copy'        => wp_seed_events_public_boolean_option( $values['show_copy'] ?? true, true ),
			'copy_label'       => wp_seed_events_share_text_option( $values['copy_label'] ?? '', __( 'Copier le lien', 'wp-seed-events' ) ),
			'copy_icon'        => sanitize_text_field( (string) $copy_icon ),
			'copy_icon_placement' => 'left' === $copy_icon_placement ? 'left' : 'right',
			'copy_icon_on_hover'  => 'off' === $copy_icon_on_hover ? 'off' : 'on',
			'show_email'       => wp_seed_events_public_boolean_option( $values['show_email'] ?? true, true ),
			'email_label'      => wp_seed_events_share_text_option( $values['email_label'] ?? '', __( 'Par email', 'wp-seed-events' ) ),
			'email_icon'       => sanitize_text_field( (string) $email_icon ),
			'email_icon_placement' => 'left' === $email_icon_placement ? 'left' : 'right',
			'email_icon_on_hover'  => 'off' === $email_icon_on_hover ? 'off' : 'on',
			'use_divi_button'  => (bool) $use_divi_button,
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

		foreach ( array( 'actionsStyle', 'actionStyle', 'shareActionStyle', 'copyActionStyle', 'emailActionStyle' ) as $attr_name ) {
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
