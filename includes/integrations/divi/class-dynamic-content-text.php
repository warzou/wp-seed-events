<?php

use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentElements;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionBase;
use ET\Builder\Packages\Module\Layout\Components\DynamicContent\DynamicContentOptionInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic Divi 5 Dynamic Content source backed by the text data registry.
 */
class WP_Seed_Events_Divi_Dynamic_Content_Text extends DynamicContentOptionBase implements DynamicContentOptionInterface {
	/**
	 * Registry and Divi value type.
	 *
	 * @var string
	 */
	protected $field_type = 'text';

	/**
	 * Registry field key.
	 *
	 * @var string
	 */
	protected $field = '';

	/**
	 * Divi source identifier.
	 *
	 * @var string
	 */
	protected $source_name = '';

	/**
	 * Configure a registry-backed text source.
	 */
	public function configure( $field, $source_name = '' ): bool {
		$field  = sanitize_key( (string) $field );
		$fields = wp_seed_events_dynamic_data_fields();

		if ( ! isset( $fields[ $field ] ) || $this->get_type() !== ( $fields[ $field ]['type'] ?? '' ) ) {
			return false;
		}

		$this->field       = $field;
		$this->source_name = '' !== $source_name
			? sanitize_key( (string) $source_name )
			: 'wp_seed_events_' . $field;

		return '' !== $this->source_name;
	}

	/**
	 * Return the registry field key.
	 */
	public function get_field(): string {
		return $this->field;
	}

	/**
	 * Return the Dynamic Content source identifier.
	 */
	public function get_name(): string {
		return $this->source_name;
	}

	/**
	 * Return the registry and Divi value type.
	 */
	public function get_type(): string {
		return $this->field_type;
	}

	public function get_loop_name(): string {
		return 'loop_' . $this->get_name();
	}

	/**
	 * Return the label displayed by Divi.
	 */
	public function get_label(): string {
		$fields = wp_seed_events_dynamic_data_fields();
		$label  = isset( $fields[ $this->field ]['label'] ) ? (string) $fields[ $this->field ]['label'] : '';

		return sprintf(
			esc_html__( 'WP Seed Events %1$s %2$s', 'wp-seed-events' ),
			"\u{2014}",
			esc_html( $label )
		);
	}

	/**
	 * Register the Dynamic Content option.
	 */
	public function register_option_callback( array $options, int $post_id, string $context ): array {
		$name = $this->get_name();

		if ( '' === $name || isset( $options[ $name ] ) ) {
			return $options;
		}

		$options[ $name ] = array(
			'id'     => $name,
			'label'  => $this->get_label(),
			'type'   => $this->get_type(),
			'custom' => false,
			'group'  => esc_html__( 'WP Seed Events', 'wp-seed-events' ),
			'fields' => array(),
		);

		$loop_name = $this->get_loop_name();
		if ( ! isset( $options[ $loop_name ] ) ) {
			$options[ $loop_name ] = array_merge(
				$options[ $name ],
				array(
					'id'    => $loop_name,
					'group' => esc_html__( 'WP Seed Events — Boucle', 'wp-seed-events' ),
				)
			);
		}

		return $options;
	}

	/**
	 * Resolve and wrap the registry value for the current event context.
	 */
	public function render_callback( $value, array $data_args = array() ): string {
		$name = isset( $data_args['name'] ) ? (string) $data_args['name'] : '';

		if ( ! in_array( $name, array( $this->get_name(), $this->get_loop_name() ), true ) ) {
			return $value;
		}

		$event_id = wp_seed_events_divi_resolve_event_id( $data_args );

		if ( 0 === $event_id ) {
			return '';
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

		return DynamicContentElements::get_wrapper_element(
			array(
				'post_id'  => absint( $data_args['post_id'] ?? 0 ),
				'name'     => $name,
				'value'    => $resolved_value,
				'settings' => $settings,
			)
		);
	}

	/**
	 * Prepare the resolved value for Divi's wrapper.
	 *
	 * @param mixed $value Registry value.
	 * @return string
	 */
	protected function prepare_resolved_value( $value ): string {
		$value = esc_html( (string) $value );

		if ( 'excerpt' === $this->get_field() ) {
			return '<span class="wp-seed-events-multiline-text">' . $value . '</span>';
		}

		return $value;
	}
}
