<?php

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

defined( 'ABSPATH' ) || exit;

class WP_Seed_Events_Divi_Event_Document_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/event-document';

	public function load() {
		add_action( 'init', array( self::class, 'register_module' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/event-document-module/visual-builder/src',
			array( 'render_callback' => array( self::class, 'render_callback' ) )
		);
	}

	public static function register_rest_routes() {
		register_rest_route(
			'wp-seed-events/v1',
			'/divi-event-document-preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => array( self::class, 'rest_preview_permissions' ),
				'args'                => array(
					'post_id'       => array( 'sanitize_callback' => 'absint' ),
					'loop_id'       => array( 'sanitize_callback' => 'absint' ),
					'show_document' => array( 'sanitize_callback' => 'sanitize_key' ),
					'link_text'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'name_display'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'name_position' => array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}

	public static function rest_preview_permissions( WP_REST_Request $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$context_id = absint( $request->get_param( 'loop_id' ) ?: $request->get_param( 'post_id' ) );
		return 0 === $context_id || current_user_can( 'edit_post', $context_id );
	}

	private static function normalize_options( $values ) {
		$values = is_array( $values ) ? $values : array();
		return array(
			'show_document' => is_scalar( $values['show_document'] ?? null ) ? (string) $values['show_document'] : 'on',
			'link_text'     => is_scalar( $values['link_text'] ?? null ) ? (string) $values['link_text'] : 'Télécharger le document',
			'name_display'  => is_scalar( $values['name_display'] ?? null ) ? (string) $values['name_display'] : 'text_name',
			'name_position' => is_scalar( $values['name_position'] ?? null ) ? (string) $values['name_position'] : 'inline',
		);
	}

	private static function get_link_text_value( $attrs, $legacy_values ) {
		$current = $attrs['linkText']['innerContent']['desktop']['value'] ?? null;

		if ( is_string( $current ) ) {
			return $current;
		}

		return is_scalar( $legacy_values['link_text'] ?? null )
			? (string) $legacy_values['link_text']
			: 'Télécharger le document';
	}

	private static function render_document( $event_id, $options ) {
		$event = wp_seed_events_get_event_data( absint( $event_id ) );
		return array() === $event ? '' : (string) wp_seed_events_render_public_event_document_section( $event, $options );
	}

	public static function rest_preview( WP_REST_Request $request ) {
		$event_id = wp_seed_events_divi_resolve_event_id(
			array( 'loop_id' => absint( $request->get_param( 'loop_id' ) ), 'post_id' => absint( $request->get_param( 'post_id' ) ) )
		);
		$options = self::normalize_options(
			array(
				'show_document' => $request->get_param( 'show_document' ),
				'link_text'     => $request->get_param( 'link_text' ),
				'name_display'  => $request->get_param( 'name_display' ),
				'name_position' => $request->get_param( 'name_position' ),
			)
		);
		return rest_ensure_response( array( 'html' => self::render_document( $event_id, $options ) ) );
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		$values = $attrs['content']['innerContent']['desktop']['value'] ?? array();
		$values['link_text'] = self::get_link_text_value( $attrs, $values );
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$html = self::render_document( $event_id, self::normalize_options( $values ) );

		if ( '' === $html ) {
			return '';
		}

		$inner = HTMLUtility::render( array( 'tag' => 'div', 'attributes' => array( 'class' => 'et_pb_module_inner' ), 'childrenSanitizer' => 'et_core_esc_previously', 'children' => $html ) );
		return Module::render(
			array(
				'orderIndex' => $block->parsed_block['orderIndex'] ?? 0, 'storeInstance' => $block->parsed_block['storeInstance'] ?? '',
				'attrs' => $attrs, 'elements' => $elements, 'id' => $block->parsed_block['id'] ?? '',
				'moduleClassName' => 'wp_seed_events_divi_event_document', 'name' => $block->block_type->name,
				'moduleCategory' => $block->block_type->category, 'classnamesFunction' => array( self::class, 'module_classnames' ),
				'stylesComponent' => array( self::class, 'module_styles' ), 'scriptDataComponent' => array( self::class, 'module_script_data' ),
				'children' => $elements->style_components( array( 'attrName' => 'module' ) ) . $inner,
			)
		);
	}

	public static function module_classnames( $args ) {
		$args['classnamesInstance']->add( ElementClassnames::classnames( array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() ) ) );
	}

	public static function module_styles( $args ) {
		$styles = array();
		foreach ( array( 'module', 'linkStyle', 'nameStyle' ) as $attr_name ) {
			$styles[] = $args['elements']->style( array( 'attrName' => $attr_name ) );
		}
		Style::add( array( 'id' => $args['id'], 'name' => $args['name'], 'orderIndex' => $args['orderIndex'], 'storeInstance' => $args['storeInstance'], 'styles' => $styles ) );
	}

	public static function module_script_data( $args ) {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}
}
