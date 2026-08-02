<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic Divi 5 image Dynamic Content source backed by the data registry.
 */
class WP_Seed_Events_Divi_Dynamic_Content_Image extends WP_Seed_Events_Divi_Dynamic_Content_Text {
	/**
	 * Registry and Divi value type.
	 *
	 * @var string
	 */
	protected $field_type = 'image';

	/**
	 * Return the Divi loop-aware alias for this image source.
	 */
	public function get_loop_name(): string {
		return 'loop_' . $this->get_name();
	}

	/**
	 * Register both the historical source and Divi's loop-aware variant.
	 *
	 * Divi resolves repeated Dynamic Content in the Visual Builder only when
	 * the source name follows its public `loop_` contract. The historical name
	 * remains registered so existing modules keep rendering unchanged.
	 */
	public function register_option_callback( array $options, int $post_id, string $context ): array {
		$options   = parent::register_option_callback( $options, $post_id, $context );
		$loop_name = $this->get_loop_name();

		if ( '' !== $loop_name && ! isset( $options[ $loop_name ] ) ) {
			$options[ $loop_name ] = array(
				'id'     => $loop_name,
				'label'  => $this->get_label(),
				'type'   => $this->get_type(),
				'custom' => false,
				'group'  => esc_html__( 'WP Seed Events — Boucle', 'wp-seed-events' ),
				'fields' => array(),
			);
		}

		return $options;
	}

	/**
	 * Resolve either source name through the canonical Divi event resolver.
	 */
	public function render_callback( $value, array $data_args = array() ): string {
		$name = isset( $data_args['name'] ) ? (string) $data_args['name'] : '';

		if ( ! in_array( $name, array( $this->get_name(), $this->get_loop_name() ), true ) ) {
			return $value;
		}

		$event_id = wp_seed_events_divi_resolve_event_id( $data_args );

		if ( 0 === $event_id ) {
			return $this->get_loop_name() === $name && is_string( $value ) && 0 === strpos( $value, '$variable(' )
				? $value
				: '';
		}

		$resolved_value = $this->prepare_resolved_value(
			wp_seed_events_dynamic_data_get_value( $this->get_field(), $event_id )
		);

		if ( '' === $resolved_value ) {
			return '';
		}

		$settings = isset( $data_args['settings'] ) && is_array( $data_args['settings'] )
			? $data_args['settings']
			: array();

		return \ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentElements::get_wrapper_element(
			array(
				'post_id'  => absint( $data_args['post_id'] ?? 0 ),
				'name'     => $name,
				'value'    => $resolved_value,
				'settings' => $settings,
			)
		);
	}

	/**
	 * Divi image fields consume a public image URL through their native wrapper.
	 *
	 * @param mixed $value Registry image object.
	 * @return string
	 */
	protected function prepare_resolved_value( $value ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}

		return wp_seed_events_sanitize_public_http_url( $value['url'] ?? '' );
	}
}
