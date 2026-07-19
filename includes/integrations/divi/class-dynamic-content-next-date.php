<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Compatibility class for the historical next date source.
 */
class WP_Seed_Events_Divi_Dynamic_Content_Next_Date extends WP_Seed_Events_Divi_Dynamic_Content_Text {
	/**
	 * Historical registry field.
	 *
	 * @var string
	 */
	protected $field = 'next_date';

	/**
	 * Historical Divi source identifier.
	 *
	 * @var string
	 */
	protected $source_name = 'wp_seed_events_next_date';
}
