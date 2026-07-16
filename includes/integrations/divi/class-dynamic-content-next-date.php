<?php

use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentElements;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionBase;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Divi 5 Dynamic Content source for the next event date.
 */
class WP_Seed_Events_Divi_Dynamic_Content_Next_Date extends DynamicContentOptionBase implements DynamicContentOptionInterface {
	/**
	 * Return the Dynamic Content source identifier.
	 */
	public function get_name(): string {
		return 'wp_seed_events_next_date';
	}

	/**
	 * Return the label displayed by Divi.
	 */
	public function get_label(): string {
		return sprintf(
			esc_html__( 'WP Seed Events %s Prochaine date', 'wp-seed-events' ),
			"\u{2014}"
		);
	}

	/**
	 * Register the Dynamic Content option.
	 */
	public function register_option_callback( array $options, int $post_id, string $context ): array {
		$name = $this->get_name();

		if ( isset( $options[ $name ] ) ) {
			return $options;
		}

		$options[ $name ] = array(
			'id'     => $name,
			'label'  => $this->get_label(),
			'type'   => 'text',
			'custom' => false,
			'group'  => esc_html__( 'WP Seed Events', 'wp-seed-events' ),
			'fields' => array(),
		);

		return $options;
	}

	/**
	 * Resolve and wrap the next date for the current event context.
	 */
	public function render_callback( $value, array $data_args = array() ): string {
		$name = isset( $data_args['name'] ) ? (string) $data_args['name'] : '';

		if ( $this->get_name() !== $name ) {
			return $value;
		}

		$event_id = wp_seed_events_divi_resolve_event_id( $data_args );

		if ( 0 === $event_id ) {
			return '';
		}

		$resolved_value = wp_seed_events_dynamic_data_get_value( 'next_date', $event_id );

		if ( '' === $resolved_value ) {
			return '';
		}

		$settings = isset( $data_args['settings'] ) && is_array( $data_args['settings'] )
			? $data_args['settings']
			: array();

		return DynamicContentElements::get_wrapper_element(
			array(
				'post_id'  => absint( $data_args['post_id'] ?? 0 ),
				'name'     => $name,
				'value'    => esc_html( $resolved_value ),
				'settings' => $settings,
			)
		);
	}
}
