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
 * Divi 5 module that renders the event communication visuals.
 */
class WP_Seed_Events_Divi_Event_Visuals_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/event-visuals';

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
			__DIR__ . '/event-visuals-module/visual-builder/src',
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
			'/divi-event-visuals-preview',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => array( self::class, 'rest_preview_permissions' ),
				'args'                => array(
					'post_id'        => array( 'sanitize_callback' => 'absint' ),
					'loop_id'        => array( 'sanitize_callback' => 'absint' ),
					'show_flyer'     => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_visuals'   => array( 'sanitize_callback' => 'sanitize_key' ),
					'show_captions'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'image_size'     => array( 'sanitize_callback' => 'sanitize_key' ),
					'click_action'   => array( 'sanitize_callback' => 'sanitize_key' ),
					'link_original'  => array( 'sanitize_callback' => 'sanitize_key' ),
					'lightbox'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'layout'         => array( 'sanitize_callback' => 'sanitize_key' ),
					'horizontal_gap' => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'vertical_gap'   => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'align_items'    => array( 'sanitize_callback' => 'sanitize_key' ),
					'justify_content'=> array( 'sanitize_callback' => 'sanitize_key' ),
					'wrap'           => array( 'sanitize_callback' => 'sanitize_key' ),
					'columns'        => array( 'sanitize_callback' => 'absint' ),
					'columns_tablet' => array( 'sanitize_callback' => 'absint' ),
					'columns_phone'  => array( 'sanitize_callback' => 'absint' ),
					'layout_contract'=> array( 'sanitize_callback' => 'sanitize_key' ),
				),
			)
		);
	}

	/**
	 * Restrict previews to authenticated users who can edit the requested context.
	 */
	public static function rest_preview_permissions( WP_REST_Request $request ) {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		$context_id = absint( $request->get_param( 'loop_id' ) );

		if ( 0 === $context_id ) {
			$context_id = absint( $request->get_param( 'post_id' ) );
		}

		return 0 === $context_id || current_user_can( 'edit_post', $context_id );
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
				'show_flyer'     => $request->get_param( 'show_flyer' ),
				'show_visuals'   => $request->get_param( 'show_visuals' ),
				'show_captions'  => $request->get_param( 'show_captions' ),
				'image_size'     => $request->get_param( 'image_size' ),
				'click_action'   => $request->get_param( 'click_action' ),
				'link_original'  => $request->get_param( 'link_original' ),
				'lightbox'       => $request->get_param( 'lightbox' ),
				'layout'         => $request->get_param( 'layout' ),
				'horizontal_gap' => $request->get_param( 'horizontal_gap' ),
				'vertical_gap'   => $request->get_param( 'vertical_gap' ),
				'align_items'    => $request->get_param( 'align_items' ),
				'justify_content'=> $request->get_param( 'justify_content' ),
				'wrap'           => $request->get_param( 'wrap' ),
				'columns'        => $request->get_param( 'columns' ),
				'columns_tablet' => $request->get_param( 'columns_tablet' ),
				'columns_phone'  => $request->get_param( 'columns_phone' ),
				'layout_contract'=> $request->get_param( 'layout_contract' ),
			)
		);

		return rest_ensure_response(
			array(
				'html' => self::render_visuals( $event_id, $options ),
			)
		);
	}

	/**
	 * Render the module on the frontend.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$event_id = wp_seed_events_divi_resolve_event_id( wp_seed_events_divi_get_module_event_context( $attrs, $block ) );
		$options  = self::normalize_options( self::get_content_values( $attrs ) );
		$html     = self::render_visuals( $event_id, $options );
		$html     = wp_seed_events_divi_apply_list_styles(
			$html,
			wp_seed_events_divi_list_style_values( $attrs, 'eventListStyle' ),
			'wp-seed-event-visuals__list'
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
				'moduleClassName'     => 'wp_seed_events_divi_event_visuals' . ( 'native' === $options['layout'] ? ' is-native-layout' : '' ),
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
	 * Render the shared visuals section from one Event Data API result.
	 */
	private static function render_visuals( $event_id, $options ) {
		$event_id = absint( $event_id );

		if ( 0 === $event_id ) {
			return '';
		}

		$event = wp_seed_events_get_event_data( $event_id );

		if ( array() === $event ) {
			return '';
		}

		$click_action = wp_seed_events_public_visuals_click_action_option(
			$options['click_action'] ?? '',
			array( 'lightbox' => $options['lightbox'] ?? false, 'link_original' => $options['link_original'] ?? false )
		);
		$lightbox = 'lightbox' === $click_action;

		if ( $lightbox ) {
			self::enqueue_divi_lightbox_assets();
			$options['click_action'] = 'original';
		}

		$html = (string) wp_seed_events_render_public_event_visuals_section( $event, $options );

		return $lightbox ? self::apply_divi_lightbox( $html ) : $html;
	}

	/**
	 * Load Divi's own Magnific Popup runtime when this module is its only consumer.
	 */
	private static function enqueue_divi_lightbox_assets() {
		if ( ! function_exists( 'wp_enqueue_script' ) || ! function_exists( 'wp_enqueue_style' ) ) {
			return;
		}

		if ( class_exists( '\\ET\\Builder\\FrontEnd\\Assets\\DynamicAssetsUtils' ) ) {
			\ET\Builder\FrontEnd\Assets\DynamicAssetsUtils::enqueue_magnific_popup_script();
		} elseif ( wp_script_is( 'magnific-popup', 'registered' ) ) {
			wp_enqueue_script( 'magnific-popup' );
		}

		if ( defined( 'ET_BUILDER_URI' ) ) {
			wp_enqueue_style(
				'wp-seed-events-divi-magnific-popup',
				ET_BUILDER_URI . '/feature/dynamic-assets/assets/css/magnific_popup.css',
				array(),
				defined( 'ET_CORE_VERSION' ) ? ET_CORE_VERSION : null
			);
			add_action( 'wp_footer', array( self::class, 'print_divi_lightbox_style' ), 1 );
		}

		if ( wp_script_is( 'wp-seed-events-divi-visuals-lightbox', 'registered' ) ) {
			wp_enqueue_script( 'wp-seed-events-divi-visuals-lightbox' );
		}
	}

	/**
	 * Print a native Divi lightbox stylesheet that was discovered after wp_head.
	 */
	public static function print_divi_lightbox_style() {
		wp_print_styles( 'wp-seed-events-divi-magnific-popup' );
	}

	/**
	 * Apply Divi's native image-lightbox trigger to renderer-owned image links.
	 */
	private static function apply_divi_lightbox( $html ) {
		if ( '' === $html || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $html;
		}

		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( array( 'class_name' => 'wp-seed-event-visuals__image-link' ) ) ) {
			$processor->add_class( 'et_pb_lightbox_image' );
			$processor->set_attribute( 'data-wp-seed-divi-lightbox', '1' );
		}

		return $processor->get_updated_html();
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
	 * Preserve Divi field values while applying the renderer defaults.
	 */
	private static function normalize_options( $values ) {
		$values   = is_array( $values ) ? $values : array();
		$defaults = array(
			'show_flyer'     => 'on',
			'show_visuals'   => 'on',
			'show_captions'  => 'off',
			'image_size'     => 'large',
			'click_action'   => '',
			'link_original'  => 'on',
			'lightbox'       => 'off',
			'layout'         => 'grid',
			'horizontal_gap' => '',
			'vertical_gap'   => '',
			'align_items'    => '',
			'justify_content'=> '',
			'wrap'           => 'on',
			'columns'        => '3',
			'columns_tablet' => '2',
			'columns_phone'  => '1',
			'layout_contract'=> '',
		);
		$options  = array();

		foreach ( $defaults as $key => $default ) {
			$options[ $key ] = array_key_exists( $key, $values ) && is_scalar( $values[ $key ] )
				? (string) $values[ $key ]
				: $default;
		}

		if ( 'native-v1' === $options['layout_contract'] ) {
			$options['layout'] = 'native';
		}
		return $options;
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

		foreach (
			array(
				'sectionStyle',
				'listStyle',
				'eventListStyle',
				'gridStyle',
				'listLayoutStyle',
				'itemStyle',
				'figureStyle',
				'imageStyle',
				'captionStyle',
				'imageLinkStyle',
			) as $attr_name
		) {
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
