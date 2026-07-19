<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Generic Divi 5 URL Dynamic Content source backed by the data registry.
 */
class WP_Seed_Events_Divi_Dynamic_Content_URL extends WP_Seed_Events_Divi_Dynamic_Content_Text {
	/**
	 * Registry and Divi value type.
	 *
	 * @var string
	 */
	protected $field_type = 'url';

	/**
	 * Keep the URL raw for Divi's URL-aware Dynamic Content wrapper.
	 *
	 * @param mixed $value Registry value.
	 * @return string
	 */
	protected function prepare_resolved_value( $value ): string {
		return wp_seed_events_sanitize_public_http_url( $value );
	}
}
