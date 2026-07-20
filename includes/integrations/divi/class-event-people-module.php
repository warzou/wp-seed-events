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
	}

	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/event-people-module/visual-builder/src',
			array( 'render_callback' => array( self::class, 'render_callback' ) )
		);
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( self::get_block_context( $attrs, $block ) );
		$options  = self::normalize_options( self::get_content_values( $attrs ) );
		$html     = self::render_people( $event_id, $options );

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
				'orderIndex'          => $block->parsed_block['orderIndex'],
				'storeInstance'       => $block->parsed_block['storeInstance'],
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'],
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
		$values   = is_array( $values ) ? $values : array();
		$defaults = array(
			'title'         => 'Contacts et intervenants',
			'heading_level' => 'h2',
			'role'          => 'all',
			'show_roles'    => 'on',
			'show_email'    => 'on',
			'show_phone'    => 'on',
			'show_link'     => 'on',
			'layout'        => 'list',
		);
		$options  = array();

		foreach ( $defaults as $key => $default ) {
			$options[ $key ] = array_key_exists( $key, $values ) && is_scalar( $values[ $key ] )
				? (string) $values[ $key ]
				: $default;
		}

		return $options;
	}

	private static function get_block_context( $attrs, $block ) {
		$loop_post_id = absint( $attrs['__loop_post_id'] ?? 0 );

		if ( 0 !== $loop_post_id ) {
			return array( 'loop_id' => $loop_post_id );
		}

		$block_context = is_object( $block ) && isset( $block->context ) && is_array( $block->context )
			? $block->context
			: array();
		$context_post_id = absint( $block_context['postId'] ?? 0 );
		$query_id        = absint( $block_context['queryId'] ?? 0 );

		if ( 0 !== $query_id ) {
			return array( 'loop_id' => $context_post_id );
		}

		global $wp_seed_events_public_event_id;

		if ( wp_seed_events_divi_is_event( $wp_seed_events_public_event_id ?? 0 ) ) {
			return array();
		}

		if ( array_key_exists( 'postId', $block_context ) || array_key_exists( 'postType', $block_context ) ) {
			return array(
				'post_id'    => $context_post_id,
				'post_type'  => sanitize_key( (string) ( $block_context['postType'] ?? '' ) ),
				'strict_post' => true,
			);
		}

		return array();
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
