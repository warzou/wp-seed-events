<?php
/**
 * Standalone assertions for Divi Dynamic Content sources.
 *
 * Run with: php tests/divi-dynamic-content-harness.php
 */

declare(strict_types=1);

namespace ET\Builder\Packages\Module\Layout\Components\DynamicContent {
	interface DynamicContentOptionInterface {
	}

	abstract class DynamicContentOptionBase {
		public function load(): void {
			$GLOBALS['d1_divi_loaded_sources'][] = $this;
		}
	}

	class DynamicContentElements {
		public static function get_wrapper_element( array $args ): string {
			$GLOBALS['d1_divi_wrapper_args'][] = $args;

			return '<span data-source="' . $args['name'] . '">' . $args['value'] . '</span>';
		}
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	class D1_Divi_REST_Request {
		private $route;

		public function __construct( $route ) {
			$this->route = (string) $route;
		}

		public function get_route() {
			return $this->route;
		}
	}

	class D1_Divi_REST_Response {
		private $data;

		public function __construct( $data ) {
			$this->data = $data;
		}

		public function get_data() {
			return $this->data;
		}

		public function set_data( $data ) {
			$this->data = $data;
		}
	}

	$GLOBALS['d1_divi_loaded_sources'] = array();
	$GLOBALS['d1_divi_wrapper_args']   = array();
	$GLOBALS['d1_divi_events']         = array();
	$GLOBALS['d1_divi_types']          = array();
	$GLOBALS['d1_divi_statuses']       = array();
	$GLOBALS['d1_divi_current_id']     = 0;
	$GLOBALS['d1_divi_queried_id']     = 0;
	$GLOBALS['d1_divi_data_calls']     = 0;
	$GLOBALS['d1_divi_case_count']     = 0;
	$GLOBALS['d1_divi_actions']        = array();

	function absint( $value ) {
		return abs( (int) $value );
	}

	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
	}

	function sanitize_file_name( $value ) {
		return basename( str_replace( '\\', '/', (string) $value ) );
	}

	function wp_basename( $value ) {
		return basename( str_replace( '\\', '/', (string) $value ) );
	}
	function esc_html( $value ) {
		return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}

	function esc_html__( $value, $domain = '' ) {
		return (string) $value;
	}

	function wp_strip_all_tags( $value ) {
		$value = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $value );

		return strip_tags( (string) $value );
	}

	function strip_shortcodes( $value ) {
		return preg_replace( '/\[[^\]]+\]/', '', (string) $value );
	}

	function wp_seed_events_sanitize_public_http_url( $url ) {
		$url   = trim( (string) $url );
		$parts = '' !== $url ? parse_url( $url ) : false;

		if (
			! is_array( $parts )
			|| empty( $parts['scheme'] )
			|| empty( $parts['host'] )
			|| ! in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true )
		) {
			return '';
		}

		return $url;
	}

	function add_action( $hook, $callback, $priority = 10 ) {
		$GLOBALS['d1_divi_actions'][] = array( $hook, $callback, $priority );
	}

	function add_filter() {
	}

	function get_post_type( $post_id = 0 ) {
		return $GLOBALS['d1_divi_types'][ absint( $post_id ) ] ?? false;
	}

	function get_post_status( $post_id = 0 ) {
		return $GLOBALS['d1_divi_statuses'][ absint( $post_id ) ] ?? false;
	}

	function get_the_ID() {
		return (int) $GLOBALS['d1_divi_current_id'];
	}

	function get_queried_object_id() {
		return (int) $GLOBALS['d1_divi_queried_id'];
	}

	function wp_seed_events_get_event_data( $event_id ) {
		$event_id = absint( $event_id );
		$GLOBALS['d1_divi_data_calls']++;

		if ( 'wp_seed_event' !== get_post_type( $event_id ) || 'publish' !== get_post_status( $event_id ) ) {
			return array();
		}

		return $GLOBALS['d1_divi_events'][ $event_id ] ?? array();
	}

	function wp_seed_events_public_event_next_date_line( $event ) {
		return (string) ( $event['next_date_value'] ?? '' );
	}

	function wp_seed_events_public_event_next_time_line( $event ) {
		return (string) ( $event['next_time_value'] ?? '' );
	}

	function wp_seed_events_public_event_display_date_line( $event ) {
		return (string) ( $event['display_date_value'] ?? '' );
	}

	function wp_seed_events_public_event_display_time_line( $event ) {
		return (string) ( $event['display_time_value'] ?? '' );
	}

	function wp_seed_events_public_event_status_label( $value ) {
		$labels = array( 'upcoming' => 'À venir', 'past' => 'Passé', 'undated' => 'Sans date', 'cancelled_only' => 'Annulé' );

		return $labels[ (string) $value ] ?? '';
	}

	require dirname( __DIR__ ) . '/includes/public/data-registry.php';
	require dirname( __DIR__ ) . '/includes/integrations/divi/bootstrap.php';

	function d1_divi_event( $event_id, $title ) {
		$event_id = absint( $event_id );
		$GLOBALS['d1_divi_types'][ $event_id ]    = 'wp_seed_event';
		$GLOBALS['d1_divi_statuses'][ $event_id ] = 'publish';
		$GLOBALS['d1_divi_events'][ $event_id ]   = array(
			'id'                      => $event_id,
			'title'                   => $title,
			'types'                   => array( 'Atelier', 'Stage' ),
			'lifecycle'               => 'upcoming',
			'next_date_value'         => 'Next ' . (string) $event_id,
			'next_time_value'         => '10:00',
			'display_date_value'      => 'Display ' . (string) $event_id,
			'display_time_value'      => '10:00 - 12:00',
			'place'                   => array( 'name' => 'Place ' . (string) $event_id ),
			'place_address'           => 'Address ' . (string) $event_id,
			'description'             => '<p>Description ' . (string) $event_id . '</p>',
			'excerpt'                 => 'Excerpt ' . (string) $event_id,
			'practical_info'          => "Line one\nLine two",
			'event_document_filename' => 'programme-' . (string) $event_id . '.pdf',
			'url'                     => 'https://example.test/events/event-' . (string) $event_id . '/',
			'place_url'               => 'http://places.example.test/place-' . (string) $event_id . '/',
			'event_document_url'      => 'https://cdn.example.test/programme-' . (string) $event_id . '.pdf',
			'communication_visual'    => array(
				'id'        => 9000 + $event_id,
				'url'       => 'https://cdn.example.test/visual-' . (string) $event_id . '.jpg',
				'mime_type' => 'image/jpeg',
				'title'     => 'Visual ' . (string) $event_id,
				'alt'       => 'Alt ' . (string) $event_id,
				'caption'   => 'Caption ' . (string) $event_id,
				'width'     => 1200,
				'height'    => 800,
				'filename'  => 'visual-' . (string) $event_id . '.jpg',
			),
		);
	}

	function d1_divi_post( $post_id, $type = 'page', $status = 'publish' ) {
		$GLOBALS['d1_divi_types'][ absint( $post_id ) ]    = (string) $type;
		$GLOBALS['d1_divi_statuses'][ absint( $post_id ) ] = (string) $status;
	}

	function d1_divi_assert( $condition, $message ) {
		if ( ! $condition ) {
			fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
			exit( 1 );
		}
	}

	function d1_divi_case( $label, $callback ) {
		$callback();
		$GLOBALS['d1_divi_case_count']++;
		echo 'OK ' . (string) $GLOBALS['d1_divi_case_count'] . ' - ' . $label . PHP_EOL;
	}

	function d1_divi_sources_by_name() {
		$sources = array();

		foreach ( $GLOBALS['d1_divi_loaded_sources'] as $source ) {
			$sources[ $source->get_name() ] = $source;
		}

		return $sources;
	}

	d1_divi_event( 914, 'La Renaissance en JEu' );
	d1_divi_event( 1011, 'Second event' );
	d1_divi_event( 1012, 'Cache event' );
	d1_divi_event( 1014, 'Event without visual' );
	$GLOBALS['d1_divi_events'][ 1014 ]['communication_visual'] = null;
	d1_divi_post( 976, 'page' );
	d1_divi_post( 998, 'page' );
	d1_divi_post( 1205, 'page' );
	d1_divi_post( 1057, 'wp_seed_event', 'draft' );

	wp_seed_events_divi_load_next_date();
	$sources = d1_divi_sources_by_name();

	d1_divi_case( 'frontend retries Dynamic Content registration after Divi loads', function () {
		$matches = array_values(
			array_filter(
				$GLOBALS['d1_divi_actions'],
				static function ( $action ) {
					return array( 'wp', 'wp_seed_events_divi_load_next_date', 5 ) === $action;
				}
			)
		);
		d1_divi_assert( 1 === count( $matches ), 'frontend Dynamic Content fallback hook differs' );
	} );

	d1_divi_case( 'Theme Builder module context prefers the queried event', function () {
		$GLOBALS['d1_divi_queried_id'] = 914;
		$block = (object) array(
			'parsed_block' => array(),
			'context'      => array( 'postId' => 2773, 'postType' => 'et_body_layout' ),
		);
		$context = wp_seed_events_divi_get_module_event_context( array(), $block );
		d1_divi_assert( 914 === wp_seed_events_divi_resolve_event_id( $context ), 'queried event lost to layout context' );
		$GLOBALS['d1_divi_queried_id'] = 0;
	} );

	d1_divi_case( 'all registry Dynamic Content sources load once', function () use ( $sources ) {
		$expected = array_map(
			static function ( $field ) {
				return 'wp_seed_events_' . $field;
			},
			array_keys( wp_seed_events_dynamic_data_fields() )
		);
		$actual = array_keys( $sources );

		d1_divi_assert( $expected === $actual, 'source IDs differ' );
		d1_divi_assert( count( $actual ) === count( array_unique( $actual ) ), 'duplicate source ID' );

		$before = count( $GLOBALS['d1_divi_loaded_sources'] );
		wp_seed_events_divi_load_next_date();
		d1_divi_assert( $before === count( $GLOBALS['d1_divi_loaded_sources'] ), 'loader registered duplicates' );
	} );

	d1_divi_case( 'labels groups and types are correct', function () use ( $sources ) {
		$expected_labels = array(
			'title' => 'Titre', 'types' => 'Types', 'status' => 'Statut', 'next_date' => 'Prochaine date',
			'next_time' => 'Prochaine heure', 'display_date' => 'Date affichée',
			'display_time' => 'Heure affichée', 'place' => 'Lieu',
			'place_address' => 'Adresse du lieu', 'description' => 'Description',
			'excerpt' => 'Extrait', 'practical_info' => 'Informations pratiques',
			'event_document_filename' => 'Nom du document',
			'url' => 'URL de l’événement', 'place_url' => 'URL du lieu',
			'event_document_url' => 'URL du document',
			'communication_visual' => 'Visuel de communication',
		);
		$url_fields   = array( 'url', 'place_url', 'event_document_url' );
		$image_fields = array( 'communication_visual' );

		foreach ( $expected_labels as $field => $label ) {
			$name    = 'wp_seed_events_' . $field;
			$options = $sources[ $name ]->register_option_callback( array(), 914, 'content' );
			d1_divi_assert( isset( $options[ $name ] ), 'missing option: ' . $name );
			d1_divi_assert( 'WP Seed Events — ' . esc_html( $label ) === $options[ $name ]['label'], 'wrong label: ' . $name . ' got ' . $options[ $name ]['label'] . ' expected ' . 'WP Seed Events — ' . esc_html( $label ) );
			d1_divi_assert( 'WP Seed Events' === $options[ $name ]['group'], 'wrong group: ' . $name );
			$expected_type = in_array( $field, $url_fields, true )
				? 'url'
				: ( in_array( $field, $image_fields, true ) ? 'image' : 'text' );
			d1_divi_assert( $expected_type === $options[ $name ]['type'], 'wrong type: ' . $name );
			d1_divi_assert( $options === $sources[ $name ]->register_option_callback( $options, 914, 'content' ), 'option duplicated: ' . $name );
		}
	} );

	d1_divi_case( 'three URL sources use one generic public provider family', function () use ( $sources ) {
		foreach ( array( 'url', 'place_url', 'event_document_url' ) as $field ) {
			$name = 'wp_seed_events_' . $field;
			d1_divi_assert( $sources[ $name ] instanceof WP_Seed_Events_Divi_Dynamic_Content_URL, 'URL provider class differs: ' . $name );
			d1_divi_assert(
				'<span data-source="' . $name . '">' . $GLOBALS['d1_divi_events'][914][ $field ] . '</span>' === $sources[ $name ]->render_callback(
					'',
					array( 'name' => $name, 'post_id' => 914 )
				),
				'URL provider output differs: ' . $name
			);
		}
	} );
	d1_divi_case( 'communication visual uses one generic public image provider', function () use ( $sources ) {
		$name   = 'wp_seed_events_communication_visual';
		$source = $sources[ $name ];

		d1_divi_assert( $source instanceof WP_Seed_Events_Divi_Dynamic_Content_Image, 'image provider class differs' );
		d1_divi_assert(
			'<span data-source="' . $name . '">https://cdn.example.test/visual-914.jpg</span>' === $source->render_callback(
				'',
				array( 'name' => $name, 'post_id' => 914 )
			),
			'image provider output differs'
		);
		$args = end( $GLOBALS['d1_divi_wrapper_args'] );
		d1_divi_assert( 'https://cdn.example.test/visual-914.jpg' === $args['value'], 'Divi image wrapper did not receive a URL' );
	} );

	d1_divi_case( 'communication visual exposes a loop-aware Divi image source', function () use ( $sources ) {
		$name      = 'wp_seed_events_communication_visual';
		$loop_name = 'loop_' . $name;
		$source    = $sources[ $name ];
		$options   = $source->register_option_callback( array(), 1205, 'edit' );

		d1_divi_assert( isset( $options[ $name ], $options[ $loop_name ] ), 'historical or loop image option missing' );
		d1_divi_assert( 'image' === $options[ $loop_name ]['type'], 'loop image option type differs' );
		d1_divi_assert( 'WP Seed Events — Boucle' === $options[ $loop_name ]['group'], 'loop image option group differs' );
		d1_divi_assert( $options[ $name ]['label'] === $options[ $loop_name ]['label'], 'loop image label differs' );
	} );

	d1_divi_case( 'loop communication visual resolves distinct items without leaking', function () use ( $sources ) {
		$name        = 'loop_wp_seed_events_communication_visual';
		$source      = $sources['wp_seed_events_communication_visual'];
		$first       = $source->render_callback( '', array( 'name' => $name, 'post_id' => 1205, 'loop_id' => 914 ) );
		$second      = $source->render_callback( '', array( 'name' => $name, 'post_id' => 1205, 'loop_id' => 1011 ) );
		$first_again = $source->render_callback( '', array( 'name' => $name, 'post_id' => 1205, 'loop_id' => 914 ) );

		d1_divi_assert( '<span data-source="' . $name . '">https://cdn.example.test/visual-914.jpg</span>' === $first, 'first loop image differs' );
		d1_divi_assert( '<span data-source="' . $name . '">https://cdn.example.test/visual-1011.jpg</span>' === $second, 'second loop image differs' );
		d1_divi_assert( $first === $first_again, 'loop image leaked between repeated items' );
	} );

	d1_divi_case( 'loop communication visual preserves the Divi token until an item exists', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$name  = 'loop_wp_seed_events_communication_visual';
		$token = '$variable({"type":"content","value":{"name":"' . $name . '","settings":{}}})$';

		$wp_seed_events_public_event_id = 0;
		$GLOBALS['d1_divi_current_id']  = 1205;
		d1_divi_assert( $token === $sources['wp_seed_events_communication_visual']->render_callback( $token, array( 'name' => $name, 'post_id' => 1205 ) ), 'unresolved loop token was discarded' );
	} );

	d1_divi_case( 'Divi loop REST items expose isolated communication visual previews', function () {
		$response = new D1_Divi_REST_Response(
			array(
				'items' => array(
					array( 'id' => 914, 'post_type' => 'wp_seed_event' ),
					array( 'id' => 1011, 'post_type' => 'wp_seed_event' ),
					array( 'id' => 1014, 'post_type' => 'wp_seed_event' ),
					array( 'id' => 1057, 'post_type' => 'wp_seed_event' ),
					array( 'id' => 1205, 'post_type' => 'page' ),
				),
			)
		);

		wp_seed_events_divi_add_event_loop_dynamic_data(
			$response,
			null,
			new D1_Divi_REST_Request( '/divi/v1/loop/query-results' )
		);
		$items = $response->get_data()['items'];

		d1_divi_assert( 'https://cdn.example.test/visual-914.jpg' === $items[0]['wp_seed_events_communication_visual'], 'first preview image differs' );
		d1_divi_assert( 'https://cdn.example.test/visual-1011.jpg' === $items[1]['wp_seed_events_communication_visual'], 'second preview image differs' );
		d1_divi_assert( ! array_key_exists( 'wp_seed_events_communication_visual', $items[2] ), 'empty event inherited a preview image' );
		d1_divi_assert( ! array_key_exists( 'wp_seed_events_communication_visual', $items[3] ), 'private event exposed a preview image' );
		d1_divi_assert( ! array_key_exists( 'wp_seed_events_communication_visual', $items[4] ), 'non-event loop item was modified' );

		$again = new D1_Divi_REST_Response(
			array( 'data' => array( 'items' => array( array( 'id' => 914, 'post_type' => 'wp_seed_event' ) ) ) )
		);
		wp_seed_events_divi_add_event_loop_dynamic_data(
			$again,
			null,
			new D1_Divi_REST_Request( '/divi/v1/loop/query-results' )
		);
		d1_divi_assert( 'https://cdn.example.test/visual-914.jpg' === $again->get_data()['data']['items'][0]['wp_seed_events_communication_visual'], 'second response leaked prior loop item' );
	} );

	d1_divi_case( 'unrelated REST responses remain untouched', function () {
		$data     = array( 'items' => array( array( 'id' => 914, 'post_type' => 'wp_seed_event' ) ) );
		$response = new D1_Divi_REST_Response( $data );

		wp_seed_events_divi_add_event_loop_dynamic_data(
			$response,
			null,
			new D1_Divi_REST_Request( '/wp/v2/wp_seed_event' )
		);
		d1_divi_assert( $data === $response->get_data(), 'unrelated REST response changed' );
	} );

	d1_divi_case( 'communication visual resolves page model and distinct events', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$name = 'wp_seed_events_communication_visual';
		$wp_seed_events_public_event_id = 914;
		$GLOBALS['d1_divi_current_id']  = 976;

		d1_divi_assert(
			'<span data-source="' . $name . '">https://cdn.example.test/visual-914.jpg</span>' === $sources[ $name ]->render_callback(
				'',
				array( 'name' => $name, 'post_id' => 976 )
			),
			'page model image differs'
		);
		d1_divi_assert(
			'<span data-source="' . $name . '">https://cdn.example.test/visual-1011.jpg</span>' === $sources[ $name ]->render_callback(
				'',
				array( 'name' => $name, 'post_id' => 1011 )
			),
			'second event image differs'
		);
	} );

	d1_divi_case( 'communication visual rejects absent PDF unsafe and featured fallbacks', function () use ( $sources ) {
		$name = 'wp_seed_events_communication_visual';
		$cases = array(
			1113 => null,
			1114 => array( 'id' => 9114, 'url' => 'https://cdn.example.test/programme.pdf', 'mime_type' => 'application/pdf' ),
			1115 => array( 'id' => 9115, 'url' => 'javascript:alert(1)', 'mime_type' => 'image/jpeg' ),
		);

		foreach ( $cases as $event_id => $media ) {
			d1_divi_event( $event_id, 'Invalid image' );
			$GLOBALS['d1_divi_events'][ $event_id ]['communication_visual'] = $media;
			$GLOBALS['d1_divi_events'][ $event_id ]['featured_image'] = array(
				'id' => 9999,
				'url' => 'https://cdn.example.test/featured.jpg',
				'mime_type' => 'image/jpeg',
			);
			d1_divi_assert( '' === $sources[ $name ]->render_callback( '', array( 'name' => $name, 'post_id' => $event_id ) ), 'invalid image rendered: ' . (string) $event_id );
		}
	} );

	d1_divi_case( 'communication visual stays empty on ordinary and draft contexts', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$name = 'wp_seed_events_communication_visual';
		$wp_seed_events_public_event_id = 0;
		$GLOBALS['d1_divi_current_id']  = 998;
		d1_divi_assert( '' === $sources[ $name ]->render_callback( '', array( 'name' => $name, 'post_id' => 998 ) ), 'ordinary page image leak' );
		d1_divi_assert( '' === $sources[ $name ]->render_callback( '', array( 'name' => $name, 'post_id' => 1057 ) ), 'draft image leak' );
	} );
	d1_divi_case( 'historical next date source remains compatible', function () use ( $sources ) {
		$source = $sources['wp_seed_events_next_date'];
		d1_divi_assert( $source instanceof WP_Seed_Events_Divi_Dynamic_Content_Next_Date, 'next_date compatibility class changed' );
		d1_divi_assert(
			'<span data-source="wp_seed_events_next_date">Next 914</span>' === $source->render_callback(
				'',
				array( 'name' => 'wp_seed_events_next_date', 'post_id' => 914 )
			),
			'next_date output changed'
		);
	} );

	d1_divi_case( 'page model resolves the public event', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$wp_seed_events_public_event_id = 914;
		$GLOBALS['d1_divi_current_id']  = 976;

		d1_divi_assert(
			'<span data-source="wp_seed_events_place_address">Address 914</span>' === $sources['wp_seed_events_place_address']->render_callback(
				'',
				array( 'name' => 'wp_seed_events_place_address', 'post_id' => 976 )
			),
			'page model did not resolve public event'
		);
	} );

	d1_divi_case( 'delayed page-model URL resolution uses the queried event', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$wp_seed_events_public_event_id    = 0;
		$GLOBALS['d1_divi_current_id']     = 976;
		$GLOBALS['d1_divi_queried_id']     = 914;

		d1_divi_assert(
			'<span data-source="wp_seed_events_url">https://example.test/events/event-914/</span>' === $sources['wp_seed_events_url']->render_callback(
				'',
				array( 'name' => 'wp_seed_events_url', 'post_id' => 976 )
			),
			'delayed model URL did not resolve the queried event'
		);
		$GLOBALS['d1_divi_queried_id'] = 0;
	} );
	d1_divi_case( 'Loop Builder keeps event contexts isolated', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$wp_seed_events_public_event_id = 914;

		$event_value = $sources['wp_seed_events_title']->render_callback(
			'',
			array( 'name' => 'wp_seed_events_title', 'post_id' => 1205, 'loop_id' => 1011 )
		);
		$page_value = $sources['wp_seed_events_title']->render_callback(
			'',
			array( 'name' => 'wp_seed_events_title', 'post_id' => 1205, 'loop_id' => 998 )
		);
		$event_url = $sources['wp_seed_events_url']->render_callback(
			'',
			array( 'name' => 'wp_seed_events_url', 'post_id' => 1205, 'loop_id' => 1011 )
		);
		$place_url = $sources['wp_seed_events_place_url']->render_callback(
			'',
			array( 'name' => 'wp_seed_events_place_url', 'post_id' => 1205, 'loop_id' => 1011 )
		);
		$image = $sources['wp_seed_events_communication_visual']->render_callback(
			'',
			array( 'name' => 'wp_seed_events_communication_visual', 'post_id' => 1205, 'loop_id' => 1011 )
		);

		d1_divi_assert( '<span data-source="wp_seed_events_title">Second event</span>' === $event_value, 'event loop value differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_status">À venir</span>' === $sources['wp_seed_events_status']->render_callback( '', array( 'name' => 'wp_seed_events_status', 'post_id' => 1205, 'loop_id' => 1011 ) ), 'event loop status differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_url">https://example.test/events/event-1011/</span>' === $event_url, 'event loop URL differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_place_url">http://places.example.test/place-1011/</span>' === $place_url, 'place loop URL differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_communication_visual">https://cdn.example.test/visual-1011.jpg</span>' === $image, 'event loop image differs' );
		d1_divi_assert( '' === $page_value, 'incompatible loop leaked the public event' );
	} );

	d1_divi_case( 'ordinary page and draft event stay empty', function () use ( $sources ) {
		global $wp_seed_events_public_event_id;
		$wp_seed_events_public_event_id = 0;
		$GLOBALS['d1_divi_current_id']  = 998;

		d1_divi_assert(
			'' === $sources['wp_seed_events_excerpt']->render_callback(
				'',
				array( 'name' => 'wp_seed_events_excerpt', 'post_id' => 998 )
			),
			'ordinary page is not empty'
		);
		d1_divi_assert(
			'' === $sources['wp_seed_events_title']->render_callback(
				'',
				array( 'name' => 'wp_seed_events_title', 'post_id' => 1057 )
			),
			'draft event is public'
		);
	} );

	d1_divi_case( 'URL sources return empty for absent and unsafe values', function () use ( $sources ) {
		d1_divi_event( 1013, 'Empty URLs' );
		$GLOBALS['d1_divi_events'][1013]['place_url']          = '';
		$GLOBALS['d1_divi_events'][1013]['event_document_url'] = 'javascript:alert(1)';

		d1_divi_assert( '' === $sources['wp_seed_events_place_url']->render_callback( '', array( 'name' => 'wp_seed_events_place_url', 'post_id' => 1013 ) ), 'empty URL rendered' );
		d1_divi_assert( '' === $sources['wp_seed_events_event_document_url']->render_callback( '', array( 'name' => 'wp_seed_events_event_document_url', 'post_id' => 1013 ) ), 'unsafe URL rendered' );
	} );
	d1_divi_case( 'several sources share one cached Event Data result', function () use ( $sources ) {
		$before = $GLOBALS['d1_divi_data_calls'];

		foreach ( $sources as $name => $source ) {
			$source->render_callback( '', array( 'name' => $name, 'post_id' => 1012 ) );
		}

		d1_divi_assert( 1 === $GLOBALS['d1_divi_data_calls'] - $before, 'sources bypassed the request cache' );
	} );

	d1_divi_case( 'provider files contain no direct meta access', function () {
		$paths = array(
			dirname( __DIR__ ) . '/includes/integrations/divi/bootstrap.php',
			dirname( __DIR__ ) . '/includes/integrations/divi/context.php',
			dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-text.php',
			dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-url.php',
			dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-image.php',
			dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-next-date.php',
		);
		$source = '';

		foreach ( $paths as $path ) {
			$source .= file_get_contents( $path );
		}

		d1_divi_assert( false === strpos( $source, 'get_post_meta' ), 'provider reads a meta directly' );
		d1_divi_assert( false === strpos( $source, 'WP_Query' ), 'provider runs a query' );
	} );

	echo 'PASS: ' . (string) $GLOBALS['d1_divi_case_count'] . ' Divi D3 cases.' . PHP_EOL;
}
