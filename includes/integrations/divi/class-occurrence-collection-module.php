<?php
/**
 * Divi 5 occurrence collection module.
 *
 * @package WPSeedEvents
 */

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

defined( 'ABSPATH' ) || exit;

/**
 * Server-rendered adapter for the canonical public occurrence collection APIs.
 */
class WP_Seed_Events_Divi_Occurrence_Collection_Module implements DependencyInterface {
	const MODULE_NAME = 'wp-seed-events/divi-occurrence-collection';
	const REST_ROUTE  = '/divi-occurrence-collection-preview';

	public function load() {
		add_action( 'init', array( self::class, 'register_module' ) );
		add_action( 'rest_api_init', array( self::class, 'register_rest_routes' ) );
	}

	public static function register_module() {
		ModuleRegistration::register_module(
			__DIR__ . '/occurrence-collection-module/visual-builder/src',
			array( 'render_callback' => array( self::class, 'render_callback' ) )
		);
	}

	public static function register_rest_routes() {
		register_rest_route(
			'wp-seed-events/v1',
			self::REST_ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'rest_preview' ),
				'permission_callback' => array( self::class, 'rest_preview_permissions' ),
				'args'                => self::rest_args(),
			)
		);
	}

	public static function rest_preview_permissions() {
		return current_user_can( 'edit_posts' );
	}

	public static function rest_preview( WP_REST_Request $request ) {
		$values = array();

		foreach ( array_keys( self::defaults() ) as $key ) {
			$values[ $key ] = $request->get_param( $key );
		}

		$options  = self::normalize_options( $values );
		$instance = self::instance_id(
			$request->get_param( 'collection_instance_id' ),
			$request->get_param( 'module_id' ),
			$request->get_param( 'store_instance' ),
			$request->get_param( 'order_index' )
		);

		return rest_ensure_response(
			array( 'html' => self::render_collection( $options, $instance, false ) )
		);
	}

	public static function render_callback( $attrs, $content, $block, $elements ) {
		unset( $content );

		$values   = self::get_content_values( $attrs );
		$options  = self::normalize_options( $values );
		$parsed   = is_object( $block ) && isset( $block->parsed_block ) && is_array( $block->parsed_block )
			? $block->parsed_block
			: array();
		$instance = self::instance_id(
			$options['collection_instance_id'],
			$parsed['id'] ?? '',
			$parsed['storeInstance'] ?? '',
			$parsed['orderIndex'] ?? 0
		);
		$html     = self::render_collection( $options, $instance, true );
		$inner    = HTMLUtility::render(
			array(
				'tag'               => 'div',
				'attributes'        => array( 'class' => 'et_pb_module_inner' ),
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $html,
			)
		);
		$children = $elements->style_components( array( 'attrName' => 'module' ) ) . $inner;
		$type     = is_object( $block ) && isset( $block->block_type ) ? $block->block_type : null;

		return Module::render(
			array(
				'orderIndex'          => $parsed['orderIndex'] ?? 0,
				'storeInstance'       => $parsed['storeInstance'] ?? '',
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $parsed['id'] ?? '',
				'moduleClassName'     => 'wp_seed_events_divi_occurrence_collection',
				'name'                => is_object( $type ) && isset( $type->name ) ? $type->name : self::MODULE_NAME,
				'moduleCategory'      => is_object( $type ) && isset( $type->category ) ? $type->category : 'module',
				'classnamesFunction'  => array( self::class, 'module_classnames' ),
				'stylesComponent'     => array( self::class, 'module_styles' ),
				'scriptDataComponent' => array( self::class, 'module_script_data' ),
				'children'            => $children,
			)
		);
	}

	public static function defaults() {
		return array(
			'mode'                   => 'flat',
			'promotion'              => '',
			'parcours_year'          => 0,
			'event_id'               => 0,
			'type'                   => '',
			'status'                 => 'upcoming',
			'pinned'                 => 'all',
			'include_cancelled'      => 'off',
			'from'                   => '',
			'to'                     => '',
			'order'                  => 'chronological',
			'page'                   => 1,
			'per_page'               => 20,
			'grouped_limit'          => 200,
			'collection_instance_id' => '',
			'show_event_title'       => 'on',
			'show_event_type'        => 'off',
			'show_event_status'      => 'off',
			'show_event_pinned'      => 'off',
			'show_start_date'        => 'on',
			'show_end_date'          => 'off',
			'show_start_time'        => 'on',
			'show_end_time'          => 'on',
			'show_cancelled'         => 'on',
			'show_promotion_name'    => 'on',
			'show_promotion_year'    => 'off',
			'show_promotion_status'  => 'off',
			'show_parcours_year'     => 'off',
			'show_parcours_label'    => 'on',
			'show_labels'            => 'on',
			'date_format'            => 'long',
			'time_format'            => 'site',
			'field_separator'        => '',
			'date_separator'         => ' – ',
			'time_separator'         => ' – ',
			'empty_message'          => 'Aucune occurrence à afficher.',
			'cancelled_text'         => 'Annulée',
		);
	}

	public static function normalize_options( $values ) {
		$values = array_merge( self::defaults(), is_array( $values ) ? $values : array() );
		$mode   = 'grouped' === sanitize_key( (string) $values['mode'] ) ? 'grouped' : 'flat';
		$status = sanitize_key( (string) $values['status'] );
		$pinned = sanitize_key( (string) $values['pinned'] );
		$order  = sanitize_key( (string) $values['order'] );

		$status = in_array( $status, array( 'upcoming', 'past', 'all' ), true ) ? $status : 'upcoming';
		$pinned = in_array( $pinned, array( 'all', 'only' ), true ) ? $pinned : 'all';
		$order  = in_array( $order, array( 'upcoming', 'chronological', 'chronological_desc' ), true ) ? $order : 'chronological';

		$year     = absint( $values['parcours_year'] );
		$event_id = absint( $values['event_id'] );
		$options  = array(
			'mode'                   => $mode,
			'promotion'              => self::scalar_text( $values['promotion'] ),
			'parcours_year'          => in_array( $year, array( 1, 2, 3, 4 ), true ) ? $year : null,
			'event_id'               => 0 < $event_id ? $event_id : null,
			'type'                   => sanitize_title( self::scalar_text( $values['type'] ) ),
			'status'                 => $status,
			'pinned'                 => $pinned,
			'include_cancelled'      => self::is_enabled( $values['include_cancelled'], false ),
			'from'                   => sanitize_text_field( self::scalar_text( $values['from'] ) ),
			'to'                     => sanitize_text_field( self::scalar_text( $values['to'] ) ),
			'order'                  => $order,
			'page'                   => max( 1, absint( $values['page'] ) ),
			'per_page'               => min( 100, max( 1, absint( $values['per_page'] ) ) ),
			'grouped_limit'          => min( 500, max( 1, absint( $values['grouped_limit'] ) ) ),
			'collection_instance_id' => sanitize_key( self::scalar_text( $values['collection_instance_id'] ) ),
			'date_format'            => 'short' === sanitize_key( (string) $values['date_format'] ) ? 'short' : 'long',
			'time_format'            => '24h' === sanitize_key( (string) $values['time_format'] ) ? '24h' : 'site',
			'field_separator'        => sanitize_text_field( self::scalar_text( $values['field_separator'] ) ),
			'date_separator'         => sanitize_text_field( self::scalar_text( $values['date_separator'] ) ),
			'time_separator'         => sanitize_text_field( self::scalar_text( $values['time_separator'] ) ),
			'empty_message'          => sanitize_text_field( self::scalar_text( $values['empty_message'] ) ),
			'cancelled_text'         => sanitize_text_field( self::scalar_text( $values['cancelled_text'] ) ),
		);

		foreach ( array( 'show_event_title', 'show_event_type', 'show_event_status', 'show_event_pinned', 'show_start_date', 'show_end_date', 'show_start_time', 'show_end_time', 'show_cancelled', 'show_promotion_name', 'show_promotion_year', 'show_promotion_status', 'show_parcours_year', 'show_parcours_label', 'show_labels' ) as $key ) {
			$options[ $key ] = self::is_enabled( $values[ $key ], 'on' === self::defaults()[ $key ] );
		}

		return $options;
	}

	public static function instance_id( $requested, $module_id, $store_instance, $order_index ) {
		$requested = sanitize_key( self::scalar_text( $requested ) );

		if ( '' !== $requested ) {
			return $requested;
		}

		$identity = implode( '|', array( self::scalar_text( $module_id ), self::scalar_text( $store_instance ), (string) absint( $order_index ) ) );
		return 'divi-occurrence-' . substr( md5( $identity ), 0, 12 );
	}

	public static function pagination_query_key( $instance ) {
		return 'wpseed_divi_occurrence_page_' . sanitize_key( str_replace( '-', '_', (string) $instance ) );
	}

	public static function query_args( $options, $instance, $read_query_page = true ) {
		$args = array(
			'promotion'         => $options['promotion'],
			'parcours_year'     => $options['parcours_year'],
			'event_id'          => $options['event_id'],
			'type'              => $options['type'],
			'status'            => $options['status'],
			'pinned'            => $options['pinned'],
			'include_cancelled' => $options['include_cancelled'],
			'from'              => $options['from'],
			'to'                => $options['to'],
		);

		if ( 'grouped' === $options['mode'] ) {
			$args['order'] = 'canonical_path';
			$args['limit'] = $options['grouped_limit'];
			return $args;
		}

		$page = $options['page'];
		$key  = self::pagination_query_key( $instance );

		if ( $read_query_page && isset( $_GET[ $key ] ) && is_scalar( $_GET[ $key ] ) ) {
			$page = max( 1, absint( wp_unslash( $_GET[ $key ] ) ) );
		}

		$args['order']    = $options['order'];
		$args['page']     = $page;
		$args['per_page'] = $options['per_page'];
		return $args;
	}

	public static function render_collection( $options, $instance, $read_query_page = true ) {
		$options  = self::normalize_options( $options );
		$instance = sanitize_key( (string) $instance );
		$instance = '' !== $instance ? $instance : 'divi-occurrence-collection';
		$args      = self::query_args( $options, $instance, $read_query_page );
		$result    = 'grouped' === $options['mode']
			? wp_seed_events_query_grouped_occurrence_collection( $args )
			: wp_seed_events_query_occurrence_collection( $args );

		if ( is_wp_error( $result ) ) {
			return sprintf( '<div class="wp-seed-events-divi-occurrence-collection__error" role="alert">%s</div>', esc_html__( 'La collection d’occurrences ne peut pas être affichée.', 'wp-seed-events' ) );
		}

		$content = 'grouped' === $options['mode']
			? self::render_grouped( $result, $options, $instance )
			: self::render_flat( $result, $options, $instance );

		if ( '' === trim( $content ) ) {
			$message = '' !== $options['empty_message'] ? $options['empty_message'] : __( 'Aucune occurrence à afficher.', 'wp-seed-events' );
			$content = sprintf( '<p class="wp-seed-events-divi-occurrence-collection__empty" role="status">%s</p>', esc_html( $message ) );
		}

		return sprintf(
			'<div class="wp-seed-events-divi-occurrence-collection wp-seed-events-divi-occurrence-collection--%1$s" data-collection-id="%2$s" data-collection-mode="%1$s">%3$s</div>',
			esc_attr( $options['mode'] ),
			esc_attr( $instance ),
			$content
		);
	}

	private static function render_flat( $result, $options, $instance ) {
		$html = '';
		foreach ( $result['items'] ?? array() as $index => $item ) {
			$html .= self::render_item( $item, $options, $instance, $index );
		}
		return $html . self::render_pagination( $result, $instance );
	}

	private static function render_grouped( $result, $options, $instance ) {
		$html  = '';
		$index = 0;
		foreach ( $result['promotions'] ?? array() as $promotion_group ) {
			$years_html = '';
			foreach ( $promotion_group['years'] ?? array() as $year_group ) {
				$themes_html = '';
				foreach ( $year_group['themes'] ?? array() as $theme_group ) {
					$items_html = '';
					foreach ( $theme_group['occurrences'] ?? array() as $item ) {
						$items_html .= self::render_item( $item, $options, $instance, $index++ );
					}
					if ( '' === $items_html ) {
						continue;
					}
					$event        = is_array( $theme_group['event'] ?? null ) ? $theme_group['event'] : array();
					$themes_html .= sprintf( '<section class="wp-seed-events-divi-occurrence-collection__theme"><h4 class="wp-seed-events-divi-occurrence-collection__theme-title">%1$s</h4>%2$s</section>', esc_html( (string) ( $event['title'] ?? '' ) ), $items_html );
				}
				if ( '' === $themes_html ) {
					continue;
				}
				$years_html .= sprintf( '<section class="wp-seed-events-divi-occurrence-collection__year"><h3 class="wp-seed-events-divi-occurrence-collection__year-title">%1$s</h3>%2$s</section>', esc_html( (string) ( $year_group['parcours_year_label'] ?? '' ) ), $themes_html );
			}
			if ( '' === $years_html ) {
				continue;
			}
			$promotion = is_array( $promotion_group['promotion'] ?? null ) ? $promotion_group['promotion'] : array();
			$html     .= sprintf( '<section class="wp-seed-events-divi-occurrence-collection__promotion"><h2 class="wp-seed-events-divi-occurrence-collection__promotion-title">%1$s</h2>%2$s</section>', esc_html( (string) ( $promotion['name'] ?? '' ) ), $years_html );
		}
		return $html;
	}

	private static function render_item( $item, $options, $instance, $index ) {
		$context = wp_seed_events_occurrence_context_from_item( $item, $instance, $index );
		if ( array() === $context ) {
			return '';
		}

		return (string) wp_seed_events_with_occurrence_context(
			$context,
			static function () use ( $context, $options ) {
				$fields = array();
				self::append_field( $fields, $options['show_event_type'], 'event-type', __( 'Type', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'event_type' ), $options );
				self::append_field( $fields, $options['show_event_status'], 'event-status', __( 'Statut', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'event_status' ), $options );
				self::append_field( $fields, $options['show_event_pinned'], 'event-pinned', __( 'Épinglé', 'wp-seed-events' ), '1' === wp_seed_events_occurrence_context_value( 'event_is_pinned' ) ? __( 'Oui', 'wp-seed-events' ) : __( 'Non', 'wp-seed-events' ), $options );

				$start      = wp_seed_events_occurrence_context_split_datetime( wp_seed_events_occurrence_context_value( 'occurrence_start' ) );
				$end        = wp_seed_events_occurrence_context_split_datetime( wp_seed_events_occurrence_context_value( 'occurrence_end' ) );
				$date_parts = array();
				$time_parts = array();

				if ( $options['show_start_date'] && '' !== $start['date'] ) {
					$date_parts[] = wp_seed_events_public_format_occurrence_date( $start['date'], $options['date_format'] );
				}
				if ( $options['show_end_date'] && '' !== $end['date'] ) {
					$date_parts[] = wp_seed_events_public_format_occurrence_date( $end['date'], $options['date_format'] );
				}
				if ( $options['show_start_time'] && '' !== $start['time'] ) {
					$time_parts[] = self::format_time( $start['time'], $options['time_format'] );
				}
				if ( $options['show_end_time'] && '' !== $end['time'] ) {
					$time_parts[] = self::format_time( $end['time'], $options['time_format'] );
				}
				self::append_field( $fields, array() !== $date_parts, 'date', __( 'Date', 'wp-seed-events' ), implode( $options['date_separator'], $date_parts ), $options );
				self::append_field( $fields, array() !== $time_parts, 'time', __( 'Horaire', 'wp-seed-events' ), implode( $options['time_separator'], $time_parts ), $options );
				self::append_field( $fields, $options['show_cancelled'] && '1' === wp_seed_events_occurrence_context_value( 'occurrence_is_cancelled' ), 'cancelled', __( 'État', 'wp-seed-events' ), $options['cancelled_text'], $options );
				self::append_field( $fields, $options['show_promotion_name'], 'promotion-name', __( 'Promotion', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'promotion_name' ), $options );
				self::append_field( $fields, $options['show_promotion_year'], 'promotion-year', __( 'Année de départ', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'promotion_start_year' ), $options );
				self::append_field( $fields, $options['show_promotion_status'], 'promotion-status', __( 'Statut de la Promotion', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'promotion_status' ), $options );
				self::append_field( $fields, $options['show_parcours_year'], 'parcours-year', __( 'Année du parcours', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'parcours_year' ), $options );
				self::append_field( $fields, $options['show_parcours_label'], 'parcours-label', __( 'Parcours', 'wp-seed-events' ), wp_seed_events_occurrence_context_value( 'parcours_year_label' ), $options );

				$title = $options['show_event_title'] ? wp_seed_events_occurrence_context_value( 'event_title' ) : '';
				if ( '' === $title && array() === $fields ) {
					return '';
				}
				$html = '' !== $title ? sprintf( '<h3 class="wp-seed-events-divi-occurrence-collection__event-title">%s</h3>', esc_html( $title ) ) : '';
				if ( array() !== $fields ) {
					$html .= '<dl class="wp-seed-events-divi-occurrence-collection__fields">' . implode( $options['field_separator'], $fields ) . '</dl>';
				}
				$html = (string) apply_filters( 'wp_seed_events_divi_occurrence_collection_item_html', $html, $context, $options );
				return sprintf( '<article class="wp-seed-events-divi-occurrence-collection__item%1$s" data-occurrence-key="%2$s">%3$s</article>', '1' === wp_seed_events_occurrence_context_value( 'occurrence_is_cancelled' ) ? ' is-cancelled' : '', esc_attr( (string) $context['item_key'] ), $html );
			}
		);
	}

	private static function append_field( &$fields, $enabled, $slug, $label, $value, $options ) {
		if ( ! $enabled || '' === trim( (string) $value ) ) {
			return;
		}
		$label_html = $options['show_labels'] ? sprintf( '<dt class="wp-seed-events-divi-occurrence-collection__label">%s</dt>', esc_html( $label ) ) : '';
		$fields[]   = sprintf( '<div class="wp-seed-events-divi-occurrence-collection__field wp-seed-events-divi-occurrence-collection__field--%1$s">%2$s<dd class="wp-seed-events-divi-occurrence-collection__value">%3$s</dd></div>', esc_attr( sanitize_html_class( $slug ) ), $label_html, esc_html( (string) $value ) );
	}

	private static function render_pagination( $result, $instance ) {
		if ( empty( $result['has_previous'] ) && empty( $result['has_next'] ) ) {
			return '';
		}
		$page  = max( 1, absint( $result['page'] ?? 1 ) );
		$key   = self::pagination_query_key( $instance );
		$links = array();
		if ( ! empty( $result['has_previous'] ) ) {
			$links[] = sprintf( '<a class="wp-seed-events-divi-occurrence-collection__previous" href="%1$s">%2$s</a>', esc_url( add_query_arg( $key, $page - 1 ) ), esc_html__( 'Occurrences précédentes', 'wp-seed-events' ) );
		}
		if ( ! empty( $result['has_next'] ) ) {
			$links[] = sprintf( '<a class="wp-seed-events-divi-occurrence-collection__next" href="%1$s">%2$s</a>', esc_url( add_query_arg( $key, $page + 1 ) ), esc_html__( 'Occurrences suivantes', 'wp-seed-events' ) );
		}
		return sprintf( '<nav class="wp-seed-events-divi-occurrence-collection__pagination" aria-label="%1$s">%2$s</nav>', esc_attr__( 'Pagination des occurrences', 'wp-seed-events' ), implode( '', $links ) );
	}

	private static function get_content_values( $attrs ) {
		return isset( $attrs['content']['innerContent']['desktop']['value'] ) && is_array( $attrs['content']['innerContent']['desktop']['value'] )
			? $attrs['content']['innerContent']['desktop']['value']
			: array();
	}

	private static function rest_args() {
		$args = array(
			'module_id'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'store_instance' => array( 'sanitize_callback' => 'sanitize_text_field' ),
			'order_index'    => array( 'sanitize_callback' => 'absint' ),
		);
		foreach ( array_keys( self::defaults() ) as $key ) {
			$args[ $key ] = array( 'sanitize_callback' => 'sanitize_text_field' );
		}
		return $args;
	}

	private static function scalar_text( $value ) {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	private static function is_enabled( $value, $default ) {
		if ( ! is_scalar( $value ) ) {
			return (bool) $default;
		}
		$value = strtolower( trim( (string) $value ) );
		if ( in_array( $value, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}
		if ( in_array( $value, array( '0', 'false', 'no', 'off', '' ), true ) ) {
			return false;
		}
		return (bool) $default;
	}

	private static function format_time( $time, $format ) {
		if ( '24h' === $format ) {
			return preg_match( '/^[0-2]\d:[0-5]\d$/', (string) $time ) ? (string) $time : '';
		}
		return wp_seed_events_format_occurrence_time( $time );
	}

	public static function module_classnames( $args ) {
		$args['classnamesInstance']->add( ElementClassnames::classnames( array( 'attrs' => $args['attrs']['module']['decoration'] ?? array() ) ) );
	}

	public static function module_styles( $args ) {
		$elements = $args['elements'];
		$styles   = array(
			$elements->style(
				array(
					'attrName'   => 'module',
					'styleProps' => array( 'disabledOn' => array( 'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null ) ),
				)
			),
		);
		foreach ( array( 'collectionStyle', 'promotionStyle', 'yearStyle', 'themeStyle', 'itemStyle', 'titleStyle', 'labelStyle', 'valueStyle', 'emptyStyle', 'paginationStyle' ) as $attr_name ) {
			$styles[] = $elements->style( array( 'attrName' => $attr_name ) );
		}
		Style::add( array( 'id' => $args['id'], 'name' => $args['name'], 'orderIndex' => $args['orderIndex'], 'storeInstance' => $args['storeInstance'], 'styles' => $styles ) );
	}

	public static function module_script_data( $args ) {
		$args['elements']->script_data( array( 'attrName' => 'module' ) );
	}
}
