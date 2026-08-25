<?php
/**
 * Standalone assertions for the canonical calendar Dynamic Data URL.
 *
 * Run with: php tests/calendar-dynamic-url-harness.php
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

	class WP_Block {
		public $context;

		public function __construct( array $context ) {
			$this->context = $context;
		}
	}

	$GLOBALS['calendar_url_events']     = array();
	$GLOBALS['calendar_url_occurrences'] = array();
	$GLOBALS['calendar_url_post_types'] = array(
		101 => 'wp_seed_event',
		102 => 'wp_seed_event',
		103 => 'wp_seed_event',
		104 => 'wp_seed_event',
		105 => 'wp_seed_event',
		900 => 'page',
	);
	$GLOBALS['calendar_url_queried_id'] = 0;

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

	function esc_url( $value ) {
		return wp_seed_events_sanitize_public_http_url( $value );
	}

	function wp_strip_all_tags( $value ) {
		return strip_tags( (string) $value );
	}

	function strip_shortcodes( $value ) {
		return preg_replace( '/\[[^\]]+\]/', '', (string) $value );
	}

	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}

	function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		return true;
	}

	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
	}

	function add_query_arg( array $args, $url ) {
		return (string) $url . '?' . http_build_query( $args, '', '&', PHP_QUERY_RFC3986 );
	}

	function wp_seed_events_sanitize_public_http_url( $url ) {
		$url = trim( (string) $url );

		return 1 === preg_match( '#^https?://[^/]+#i', $url ) ? $url : '';
	}

	function get_post_type( $post_id ) {
		return $GLOBALS['calendar_url_post_types'][ absint( $post_id ) ] ?? false;
	}

	function get_queried_object_id() {
		return absint( $GLOBALS['calendar_url_queried_id'] );
	}

	function get_the_ID() {
		return absint( $GLOBALS['calendar_url_queried_id'] );
	}

	function wp_seed_events_get_event_occurrences( $event_id, $args = array() ) {
		unset( $args );

		return $GLOBALS['calendar_url_occurrences'][ absint( $event_id ) ] ?? array();
	}

	function wp_seed_events_get_event_data( $event_id ) {
		return $GLOBALS['calendar_url_events'][ absint( $event_id ) ] ?? array();
	}

	function wp_seed_events_public_event_status_label( $lifecycle ) {
		unset( $lifecycle );

		return '';
	}

	function wp_seed_events_programming_status_label( $status ) {
		unset( $status );

		return '';
	}

	function wp_seed_events_public_event_next_date_line( $event ) {
		unset( $event );

		return '';
	}

	function wp_seed_events_public_event_next_time_line( $event ) {
		unset( $event );

		return '';
	}

	function wp_seed_events_public_event_display_date_line( $event ) {
		unset( $event );

		return '';
	}

	function wp_seed_events_public_event_display_time_line( $event ) {
		unset( $event );

		return '';
	}

	function wp_seed_events_gutenberg_event_dates_resolve_event_id( $context ) {
		$context = is_array( $context ) ? $context : array();
		$post_id = absint( $context['postId'] ?? 0 );

		return 'wp_seed_event' === ( $context['postType'] ?? '' ) && 'wp_seed_event' === get_post_type( $post_id ) ? $post_id : 0;
	}

	function calendar_url_occurrence( string $id, array $overrides = array() ): array {
		return array_merge(
			array(
				'id'         => $id,
				'is_active'  => true,
				'is_future'  => true,
				'is_all_day' => false,
				'start_date' => '2026-10-10',
				'end_date'   => '2026-10-10',
				'start_time' => '10:00',
				'end_time'   => '17:00',
			),
			$overrides
		);
	}

	function calendar_url_assert_same( $expected, $actual, string $message ): void {
		static $count = 0;
		++$count;

		if ( $expected !== $actual ) {
			fwrite( STDERR, "FAIL {$count}: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
			exit( 1 );
		}

		echo "PASS {$count}: {$message}\n";
	}

	function calendar_url_assert_contains( string $needle, string $actual, string $message ): void {
		calendar_url_assert_same( true, false !== strpos( $actual, $needle ), $message );
	}

	class Calendar_URL_Response {
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

	class Calendar_URL_Request {
		public function get_route(): string {
			return '/divi/v1/loop/query-results';
		}
	}

	require_once dirname( __DIR__ ) . '/includes/public/calendar.php';
	require_once dirname( __DIR__ ) . '/includes/public/data-registry.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/context.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-text.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-url.php';
	require_once dirname( __DIR__ ) . '/includes/integrations/gutenberg/block-bindings.php';

	$fixtures = array(
		101 => array( calendar_url_occurrence( 'single' ) ),
		102 => array( calendar_url_occurrence( 'multi-a' ), calendar_url_occurrence( 'multi-b', array( 'start_date' => '2026-11-03', 'end_date' => '2026-11-03' ) ) ),
		103 => array( calendar_url_occurrence( 'all-day', array( 'is_all_day' => true, 'start_time' => '', 'end_time' => '' ) ) ),
		104 => array( calendar_url_occurrence( 'multi-day', array( 'end_date' => '2026-10-11' ) ) ),
		105 => array(),
	);

	foreach ( $fixtures as $event_id => $occurrences ) {
		$GLOBALS['calendar_url_occurrences'][ $event_id ] = $occurrences;
		$url = wp_seed_events_event_calendar_url( array( 'id' => $event_id ), $occurrences );
		$GLOBALS['calendar_url_events'][ $event_id ] = array( 'calendar_all_occurrences_url' => $url );
	}

	foreach ( array( 101, 102, 103, 104 ) as $event_id ) {
		$url = $GLOBALS['calendar_url_events'][ $event_id ]['calendar_all_occurrences_url'];
		calendar_url_assert_contains( 'action=wp_seed_events_download_event_ics', $url, 'canonical action for event ' . $event_id );
		calendar_url_assert_contains( 'event_id=' . $event_id, $url, 'canonical event ID for event ' . $event_id );
	}
	calendar_url_assert_same( '', $GLOBALS['calendar_url_events'][105]['calendar_all_occurrences_url'], 'event without an active future occurrence stays empty' );
	calendar_url_assert_same( '', wp_seed_events_event_calendar_url( array(), $fixtures[101] ), 'invalid event context stays empty' );
	calendar_url_assert_same( '', wp_seed_events_event_calendar_url( array( 'id' => 101 ), array( calendar_url_occurrence( 'past', array( 'is_future' => false ) ) ) ), 'past occurrence stays empty' );
	calendar_url_assert_same( '', wp_seed_events_event_calendar_url( array( 'id' => 101 ), array( calendar_url_occurrence( 'cancelled', array( 'is_active' => false ) ) ) ), 'inactive occurrence stays empty' );

	$fields = wp_seed_events_dynamic_data_fields();
	calendar_url_assert_same( 'url', $fields['calendar_all_occurrences_url']['type'] ?? '', 'registry exposes a URL field' );
	calendar_url_assert_same( $GLOBALS['calendar_url_events'][101]['calendar_all_occurrences_url'], wp_seed_events_dynamic_data_get_value( 'calendar_all_occurrences_url', 101 ), 'registry returns the canonical URL' );
	calendar_url_assert_same( '', wp_seed_events_dynamic_data_get_value( 'calendar_all_occurrences_url', 105 ), 'registry preserves empty URL' );

	$provider = new WP_Seed_Events_Divi_Dynamic_Content_URL();
	calendar_url_assert_same( true, $provider->configure( 'calendar_all_occurrences_url' ), 'Divi URL provider accepts the field' );
	$GLOBALS['calendar_url_queried_id'] = 101;
	calendar_url_assert_contains( 'event_id=101', $provider->render_callback( '', array( 'name' => 'wp_seed_events_calendar_all_occurrences_url' ) ), 'event page provider resolves current event' );
	calendar_url_assert_contains( 'event_id=102', $provider->render_callback( '', array( 'name' => 'loop_wp_seed_events_calendar_all_occurrences_url', 'post_id' => 900, 'loop_id' => 102 ) ), 'loop provider resolves explicit item' );
	$GLOBALS['wp_seed_events_public_event_id'] = 101;
	calendar_url_assert_same( '', $provider->render_callback( '', array( 'name' => 'loop_wp_seed_events_calendar_all_occurrences_url', 'post_id' => 900 ) ), 'loop provider never falls back without loop_id' );

	$response = new Calendar_URL_Response(
		array(
			'items' => array(
				array( 'id' => 101, 'post_type' => 'wp_seed_event' ),
				array( 'id' => 102, 'post_type' => 'wp_seed_event' ),
			)
		)
	);
	wp_seed_events_divi_add_event_loop_dynamic_data( $response, null, new Calendar_URL_Request() );
	$items = $response->get_data()['items'];
	calendar_url_assert_contains( 'event_id=101', $items[0]['wp_seed_events_calendar_all_occurrences_url'] ?? '', 'Visual Builder item A receives its URL' );
	calendar_url_assert_contains( 'event_id=102', $items[1]['wp_seed_events_calendar_all_occurrences_url'] ?? '', 'Visual Builder item B receives its URL' );

	calendar_url_assert_same( true, in_array( 'calendar_all_occurrences_url', wp_seed_events_gutenberg_block_binding_preview_fields(), true ), 'Gutenberg preview exposes the URL' );
	$block = new WP_Block( array( 'postId' => 104, 'postType' => 'wp_seed_event', 'queryId' => 0 ) );
	calendar_url_assert_contains( 'event_id=104', wp_seed_events_gutenberg_block_binding_value( array( 'field' => 'calendar_all_occurrences_url' ), $block, 'url' ), 'Gutenberg binding resolves the current event' );

	echo "CALENDAR DYNAMIC URL HARNESS PASS\n";
}
