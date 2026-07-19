<?php
/**
 * Standalone assertions for Divi text Dynamic Content sources.
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

	$GLOBALS['d1_divi_loaded_sources'] = array();
	$GLOBALS['d1_divi_wrapper_args']   = array();
	$GLOBALS['d1_divi_events']         = array();
	$GLOBALS['d1_divi_types']          = array();
	$GLOBALS['d1_divi_statuses']       = array();
	$GLOBALS['d1_divi_current_id']     = 0;
	$GLOBALS['d1_divi_queried_id']     = 0;
	$GLOBALS['d1_divi_data_calls']     = 0;
	$GLOBALS['d1_divi_case_count']     = 0;

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

	function add_action() {
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
	d1_divi_post( 976, 'page' );
	d1_divi_post( 998, 'page' );
	d1_divi_post( 1205, 'page' );
	d1_divi_post( 1057, 'wp_seed_event', 'draft' );

	wp_seed_events_divi_load_next_date();
	$sources = d1_divi_sources_by_name();

	d1_divi_case( 'all registry text and URL sources load once', function () use ( $sources ) {
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
			'title' => 'Titre', 'types' => 'Types', 'next_date' => 'Prochaine date',
			'next_time' => 'Prochaine heure', 'display_date' => 'Date affichée',
			'display_time' => 'Heure affichée', 'place' => 'Lieu',
			'place_address' => 'Adresse du lieu', 'description' => 'Description',
			'excerpt' => 'Extrait', 'practical_info' => 'Informations pratiques',
			'event_document_filename' => 'Nom du document',
			'url' => 'URL de l’événement', 'place_url' => 'URL du lieu',
			'event_document_url' => 'URL du document',
		);
		$url_fields = array( 'url', 'place_url', 'event_document_url' );

		foreach ( $expected_labels as $field => $label ) {
			$name    = 'wp_seed_events_' . $field;
			$options = $sources[ $name ]->register_option_callback( array(), 914, 'content' );
			d1_divi_assert( isset( $options[ $name ] ), 'missing option: ' . $name );
			d1_divi_assert( 'WP Seed Events — ' . esc_html( $label ) === $options[ $name ]['label'], 'wrong label: ' . $name . ' got ' . $options[ $name ]['label'] . ' expected ' . 'WP Seed Events — ' . esc_html( $label ) );
			d1_divi_assert( 'WP Seed Events' === $options[ $name ]['group'], 'wrong group: ' . $name );
			$expected_type = in_array( $field, $url_fields, true ) ? 'url' : 'text';
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

		d1_divi_assert( '<span data-source="wp_seed_events_title">Second event</span>' === $event_value, 'event loop value differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_url">https://example.test/events/event-1011/</span>' === $event_url, 'event loop URL differs' );
		d1_divi_assert( '<span data-source="wp_seed_events_place_url">http://places.example.test/place-1011/</span>' === $place_url, 'place loop URL differs' );
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
			dirname( __DIR__ ) . '/includes/integrations/divi/class-dynamic-content-next-date.php',
		);
		$source = '';

		foreach ( $paths as $path ) {
			$source .= file_get_contents( $path );
		}

		d1_divi_assert( false === strpos( $source, 'get_post_meta' ), 'provider reads a meta directly' );
		d1_divi_assert( false === strpos( $source, 'WP_Query' ), 'provider runs a query' );
	} );

	echo 'PASS: ' . (string) $GLOBALS['d1_divi_case_count'] . ' Divi D2 cases.' . PHP_EOL;
}
