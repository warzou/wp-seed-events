<?php
/**
 * Standalone contract for strict Divi loop/page Dynamic Data resolution.
 *
 * Run with: php tests/divi-dynamic-data-strict-context-harness.php
 */

declare(strict_types=1);

namespace ET\Builder\Packages\Module\Layout\Components\DynamicContent {
	interface DynamicContentOptionInterface {
	}

	abstract class DynamicContentOptionBase {
	}

	class DynamicContentElements {
		public static function get_wrapper_element( array $args ): string {
			return '<span data-source="' . $args['name'] . '">' . $args['value'] . '</span>';
		}
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	$GLOBALS['wp_seed_test_types'] = array(
		101 => 'wp_seed_event',
		102 => 'wp_seed_event',
		103 => 'wp_seed_event',
		900 => 'page',
	);
	$GLOBALS['wp_seed_test_queried_id'] = 0;
	$GLOBALS['wp_seed_test_values']     = array(
		101 => array(
			'title'                => 'Event Alpha',
			'url'                  => 'https://example.test/event-alpha/',
			'communication_visual' => array( 'url' => 'https://example.test/event-alpha.jpg' ),
		),
		102 => array(
			'title'                => 'Event Beta',
			'url'                  => 'https://example.test/event-beta/',
			'communication_visual' => array( 'url' => 'https://example.test/event-beta.jpg' ),
		),
		103 => array(
			'title'                => 'Event Gamma',
			'url'                  => 'https://example.test/event-gamma/',
			'communication_visual' => array( 'url' => 'https://example.test/event-gamma.jpg' ),
		),
	);

	function absint( $value ) {
		return abs( (int) $value );
	}

	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
	}

	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	function esc_html__( $value, $domain = '' ) {
		return (string) $value;
	}

	function get_post_type( $post_id ) {
		return $GLOBALS['wp_seed_test_types'][ absint( $post_id ) ] ?? false;
	}

	function get_queried_object_id() {
		return absint( $GLOBALS['wp_seed_test_queried_id'] );
	}

	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}

	function wp_seed_events_sanitize_public_http_url( $url ) {
		$url = trim( (string) $url );

		return 1 === preg_match( '#^https?://#i', $url ) ? $url : '';
	}

	function wp_seed_events_dynamic_data_fields() {
		return array(
			'title'                => array( 'type' => 'text', 'label' => 'Title' ),
			'url'                  => array( 'type' => 'url', 'label' => 'URL' ),
			'communication_visual' => array( 'type' => 'image', 'label' => 'Image' ),
		);
	}

	function wp_seed_events_dynamic_data_field_format( $field ) {
		return 'plain_text';
	}

	function wp_seed_events_dynamic_data_get_value( $field, $event_id ) {
		return $GLOBALS['wp_seed_test_values'][ absint( $event_id ) ][ (string) $field ] ?? '';
	}

	class WP_Seed_Test_Response {
		private $data;

		public function __construct( array $data ) {
			$this->data = $data;
		}

		public function get_data(): array {
			return $this->data;
		}

		public function set_data( $data ): void {
			$this->data = $data;
		}
	}

	class WP_Seed_Test_Request {
		public function get_route(): string {
			return '/divi/v1/loop/query-results';
		}
	}

	function wp_seed_test_assert_same( $expected, $actual, string $message ): void {
		static $count = 0;
		++$count;

		if ( $expected !== $actual ) {
			fwrite( STDERR, "FAIL {$count}: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
			exit( 1 );
		}

		echo "PASS {$count}: {$message}\n";
	}

	require_once dirname( __DIR__ ) . '/includes/integrations/divi/context.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-text.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-url.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-image.php';

	$text = new WP_Seed_Events_Divi_Dynamic_Content_Text();
	$url  = new WP_Seed_Events_Divi_Dynamic_Content_URL();
	$image = new WP_Seed_Events_Divi_Dynamic_Content_Image();
	$text->configure( 'title' );
	$url->configure( 'url' );
	$image->configure( 'communication_visual' );

	$rendered = static function ( $provider, string $name, array $context ): string {
		return $provider->render_callback( '', array_merge( array( 'name' => $name ), $context ) );
	};

	// Event-page sources retain the historical page/current-event resolver.
	$GLOBALS['wp_seed_test_queried_id'] = 101;
	wp_seed_test_assert_same( '<span data-source="wp_seed_events_title">Event Alpha</span>', $rendered( $text, 'wp_seed_events_title', array() ), 'page text resolves the queried event' );
	wp_seed_test_assert_same( '<span data-source="wp_seed_events_url">https://example.test/event-alpha/</span>', $rendered( $url, 'wp_seed_events_url', array() ), 'page URL resolves the queried event' );
	wp_seed_test_assert_same( '<span data-source="wp_seed_events_communication_visual">https://example.test/event-alpha.jpg</span>', $rendered( $image, 'wp_seed_events_communication_visual', array() ), 'page image resolves the queried event' );

	// Loop aliases resolve only the explicit repeated item, including after A/B/C/A rerenders.
	foreach ( array( 101, 102, 103, 101 ) as $event_id ) {
		$values = $GLOBALS['wp_seed_test_values'][ $event_id ];
		wp_seed_test_assert_same( '<span data-source="loop_wp_seed_events_title">' . $values['title'] . '</span>', $rendered( $text, 'loop_wp_seed_events_title', array( 'post_id' => 900, 'loop_id' => $event_id ) ), 'loop text stays on item ' . $event_id );
		wp_seed_test_assert_same( '<span data-source="loop_wp_seed_events_url">' . $values['url'] . '</span>', $rendered( $url, 'loop_wp_seed_events_url', array( 'post_id' => 900, 'loop_id' => $event_id ) ), 'loop URL stays on item ' . $event_id );
		wp_seed_test_assert_same( '<span data-source="loop_wp_seed_events_communication_visual">' . $values['communication_visual']['url'] . '</span>', $rendered( $image, 'loop_wp_seed_events_communication_visual', array( 'post_id' => 900, 'loop_id' => $event_id ) ), 'loop image stays on item ' . $event_id );
	}

	$GLOBALS['wp_seed_events_public_event_id'] = 103;
	wp_seed_test_assert_same( '', $rendered( $text, 'loop_wp_seed_events_title', array( 'post_id' => 101 ) ), 'loop text never falls back to page or global event' );
	wp_seed_test_assert_same( '', $rendered( $url, 'loop_wp_seed_events_url', array( 'loop_id' => 900 ) ), 'loop URL rejects a non-event item' );
	wp_seed_test_assert_same( '', $rendered( $image, 'loop_wp_seed_events_communication_visual', array() ), 'loop image never falls back to page or global event' );

	// Visual Builder loop payloads carry the same per-item values used by frontend providers.
	$response = new WP_Seed_Test_Response(
		array(
			'items' => array(
				array( 'id' => 101, 'post_type' => 'wp_seed_event' ),
				array( 'id' => 102, 'post_type' => 'wp_seed_event' ),
				array( 'id' => 103, 'post_type' => 'wp_seed_event' ),
				array( 'id' => 900, 'post_type' => 'page' ),
			),
		)
	);
	wp_seed_events_divi_add_event_loop_dynamic_data( $response, null, new WP_Seed_Test_Request() );
	$items = $response->get_data()['items'];
	foreach ( array( 101, 102, 103 ) as $index => $event_id ) {
		wp_seed_test_assert_same( $GLOBALS['wp_seed_test_values'][ $event_id ]['title'], $items[ $index ]['wp_seed_events_title'] ?? '', 'Visual Builder text stays on item ' . $event_id );
		wp_seed_test_assert_same( $GLOBALS['wp_seed_test_values'][ $event_id ]['url'], $items[ $index ]['wp_seed_events_url'] ?? '', 'Visual Builder URL stays on item ' . $event_id );
		wp_seed_test_assert_same( $GLOBALS['wp_seed_test_values'][ $event_id ]['communication_visual']['url'], $items[ $index ]['wp_seed_events_communication_visual'] ?? '', 'Visual Builder image stays on item ' . $event_id );
	}
	wp_seed_test_assert_same( array( 'id' => 900, 'post_type' => 'page' ), $items[3], 'Visual Builder leaves non-event items untouched' );

	echo "DYNAMIC DATA STRICT CONTEXT HARNESS PASS\n";
}
