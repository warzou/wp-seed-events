<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether a post ID belongs to a WP Seed event.
 */
function wp_seed_events_divi_is_event( $post_id ) {
	$post_id = absint( $post_id );

	return 0 !== $post_id && 'wp_seed_event' === get_post_type( $post_id );
}

/**
 * Read the event context attached by Divi to a module or one of its ancestors.
 *
 * Loop Builder stores __loop_post_id on the repeated block. Child modules must
 * therefore use Divi's own ancestor-aware resolver instead of relying only on
 * their local attributes or on the holder page context.
 *
 * @param array  $attrs Module attributes.
 * @param object $block Divi block instance.
 * @return array
 */
function wp_seed_events_divi_get_module_event_context( $attrs, $block ) {
	$attrs        = is_array( $attrs ) ? $attrs : array();
	$parsed_block = is_object( $block ) && isset( $block->parsed_block ) && is_array( $block->parsed_block )
		? $block->parsed_block
		: array();
	$parsed_attrs = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] )
		? $parsed_block['attrs']
		: array();
	// Divi resolves loop variables on the parsed repeated block. Keep that
	// resolved value authoritative when callback attributes still contain the
	// original dynamic variable expression.
	$module_attrs = array_replace( $attrs, $parsed_attrs );
	$loop_post_id = absint( $parsed_attrs['__loop_post_id'] ?? 0 );

	if ( 0 === $loop_post_id ) {
		$loop_post_id = absint( $attrs['__loop_post_id'] ?? 0 );
	}

	$dynamic_content_utils = '\\ET\\Builder\\Packages\\Module\\Layout\\Components\\DynamicContent\\DynamicContentUtils';
	$module_id             = isset( $parsed_block['id'] ) && is_scalar( $parsed_block['id'] )
		? (string) $parsed_block['id']
		: '';
	$store_instance        = isset( $parsed_block['storeInstance'] ) && is_numeric( $parsed_block['storeInstance'] )
		? (int) $parsed_block['storeInstance']
		: null;

	if (
		0 === $loop_post_id
		&& '' !== $module_id
		&& class_exists( $dynamic_content_utils )
		&& is_callable( array( $dynamic_content_utils, 'get_loop_post_id' ) )
	) {
		$loop_post_id = absint( $dynamic_content_utils::get_loop_post_id( $module_attrs, $module_id, $store_instance ) );
	}

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

	$queried_event_id = absint( get_queried_object_id() );

	if ( wp_seed_events_divi_is_event( $queried_event_id ) ) {
		return array(
			'post_id'     => $queried_event_id,
			'post_type'   => 'wp_seed_event',
			'strict_post' => true,
		);
	}

	if ( array_key_exists( 'postId', $block_context ) || array_key_exists( 'postType', $block_context ) ) {
		return array(
			'post_id'     => $context_post_id,
			'post_type'   => sanitize_key( (string) ( $block_context['postType'] ?? '' ) ),
			'strict_post' => true,
		);
	}

	return array();
}

/**
 * Resolve the current event from a Divi rendering context.
 *
 * A real but incompatible loop item must not fall back to its holder page.
 */
function wp_seed_events_divi_rest_preview_event_id( $request ) {
	return wp_seed_events_divi_resolve_event_id(
		array(
			'loop_id'     => absint( $request->get_param( 'loop_id' ) ),
			'post_id'     => absint( $request->get_param( 'post_id' ) ),
			'strict_post' => true,
		)
	);
}

function wp_seed_events_divi_rest_preview_permissions( $request ) {
	if ( ! current_user_can( 'edit_posts' ) ) {
		return false;
	}
	$event_id = absint( $request->get_param( 'loop_id' ) ?: $request->get_param( 'post_id' ) );

	return 0 === $event_id || ( wp_seed_events_divi_is_event( $event_id ) && current_user_can( 'edit_post', $event_id ) );
}

function wp_seed_events_divi_resolve_event_id( $context = array() ) {
	$context = is_array( $context ) ? $context : array();
	$loop_id = absint( $context['loop_id'] ?? 0 );

	if ( 0 !== $loop_id ) {
		return wp_seed_events_divi_is_event( $loop_id ) ? $loop_id : 0;
	}

	$post_id = absint( $context['post_id'] ?? 0 );
	$post_type = isset( $context['post_type'] ) && is_scalar( $context['post_type'] )
		? sanitize_key( (string) $context['post_type'] )
		: '';
	$strict_post = ! empty( $context['strict_post'] );

	if ( $strict_post && '' !== $post_type && 'wp_seed_event' !== $post_type ) {
		return 0;
	}

	if ( wp_seed_events_divi_is_event( $post_id ) ) {
		return $post_id;
	}

	if ( $strict_post && 0 !== $post_id ) {
		return 0;
	}

	global $wp_seed_events_public_event_id;

	$public_event_id = absint( $wp_seed_events_public_event_id ?? 0 );

	if ( wp_seed_events_divi_is_event( $public_event_id ) ) {
		return $public_event_id;
	}

	$queried_post_id = absint( get_queried_object_id() );

	if ( wp_seed_events_divi_is_event( $queried_post_id ) ) {
		return $queried_post_id;
	}

	$current_post_id = absint( $context['current_post_id'] ?? 0 );

	return wp_seed_events_divi_is_event( $current_post_id ) ? $current_post_id : 0;
}

/**
 * Add public WP Seed event fields to Divi's Visual Builder loop items.
 *
 * Divi resolves `loop_*` variables in the browser from the corresponding
 * property on each `/divi/v1/loop/query-results` item. Frontend rendering
 * continues to use the Dynamic Content provider and the same event resolver.
 *
 * @param mixed $response REST response.
 * @param mixed $server   REST server.
 * @param mixed $request  REST request.
 * @return mixed
 */
function wp_seed_events_divi_add_event_loop_dynamic_data( $response, $server, $request ) {
	if (
		! is_object( $request )
		|| ! is_callable( array( $request, 'get_route' ) )
		|| '/divi/v1/loop/query-results' !== $request->get_route()
		|| ! is_object( $response )
		|| ! is_callable( array( $response, 'get_data' ) )
		|| ! is_callable( array( $response, 'set_data' ) )
	) {
		return $response;
	}

	$data = $response->get_data();

	if ( ! is_array( $data ) ) {
		return $response;
	}

	if ( isset( $data['items'] ) && is_array( $data['items'] ) ) {
		$items =& $data['items'];
	} elseif ( isset( $data['data']['items'] ) && is_array( $data['data']['items'] ) ) {
		$items =& $data['data']['items'];
	} else {
		return $response;
	}

	foreach ( $items as &$item ) {
		if ( ! is_array( $item ) || 'wp_seed_event' !== ( $item['post_type'] ?? '' ) ) {
			continue;
		}

		$event_id = wp_seed_events_divi_resolve_event_id(
			array( 'loop_id' => absint( $item['id'] ?? 0 ) )
		);
		foreach ( wp_seed_events_dynamic_data_fields() as $field => $definition ) {
			$value = 0 !== $event_id ? wp_seed_events_dynamic_data_get_value( $field, $event_id ) : '';

			if ( 'image' === ( $definition['type'] ?? '' ) ) {
				$value = is_array( $value ) ? wp_seed_events_sanitize_public_http_url( $value['url'] ?? '' ) : '';
			} elseif ( ! is_scalar( $value ) ) {
				$value = '';
			}

			$key = 'wp_seed_events_' . $field;
			if ( '' !== (string) $value ) {
				$item[ $key ] = (string) $value;
			} else {
				unset( $item[ $key ] );
			}
		}
	}
	unset( $item );

	$response->set_data( $data );

	return $response;
}
if ( function_exists( 'add_filter' ) ) {
	add_filter( 'rest_post_dispatch', 'wp_seed_events_divi_add_event_loop_dynamic_data', 10, 3 );
}
