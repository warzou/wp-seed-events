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
