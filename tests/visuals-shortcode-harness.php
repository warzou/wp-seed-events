<?php
/**
 * Standalone assertions for the public event visuals shortcode adapter.
 *
 * Run with: php tests/visuals-shortcode-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['wp_seed_events_visuals_shortcode_case_count'] = 0;
$GLOBALS['wp_seed_events_visuals_shortcode_data']       = array();
$GLOBALS['wp_seed_events_visuals_shortcode_data_calls'] = 0;
$GLOBALS['wp_seed_events_visuals_shortcode_image_calls'] = array();
$GLOBALS['wp_seed_events_visuals_shortcode_meta_calls'] = 0;
$GLOBALS['wp_seed_events_visuals_shortcode_post_id']    = 0;
$GLOBALS['wp_seed_events_visuals_shortcode_post_types'] = array();

function absint( $value ) {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

function shortcode_atts( $pairs, $atts, $shortcode = '' ) {
	$output = $pairs;

	foreach ( is_array( $atts ) ? $atts : array() as $name => $value ) {
		if ( array_key_exists( $name, $pairs ) ) {
			$output[ $name ] = $value;
		}
	}

	return $output;
}

function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = strip_tags( (string) $value );

	return $remove_breaks ? preg_replace( '/[[:space:]]+/', ' ', $value ) : $value;
}

function esc_html( $value ) {
	return htmlspecialchars( (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
}

function esc_attr( $value ) {
	return esc_html( $value );
}

function esc_url( $value ) {
	$value = trim( (string) $value );

	return preg_match( '#^https?://#i', $value ) ? esc_attr( $value ) : '';
}

function sanitize_key( $value ) {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) );
}

function sanitize_file_name( $value ) {
	$value = str_replace( chr( 92 ), '/', (string) $value );
	$value = basename( $value );
	$value = preg_replace( '/[^A-Za-z0-9._-]+/', '-', $value );

	return trim( (string) $value, '.-' );
}

function get_intermediate_image_sizes() {
	return array( 'thumbnail', 'medium', 'medium_large', 'large', 'poster' );
}

function get_the_ID() {
	return (int) $GLOBALS['wp_seed_events_visuals_shortcode_post_id'];
}

function get_post_type( $post_id = 0 ) {
	return $GLOBALS['wp_seed_events_visuals_shortcode_post_types'][ absint( $post_id ) ] ?? false;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['wp_seed_events_visuals_shortcode_meta_calls']++;

	return '';
}

function wp_get_attachment_url( $attachment_id ) {
	$attachment_id = absint( $attachment_id );

	return $attachment_id > 0 ? 'https://example.test/uploads/attachment-' . $attachment_id . '.pdf' : false;
}

function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
	$attachment_id = absint( $attachment_id );
	$attr          = is_array( $attr ) ? $attr : array();

	$GLOBALS['wp_seed_events_visuals_shortcode_image_calls'][] = array(
		'id'      => $attachment_id,
		'size'    => (string) $size,
		'alt'     => isset( $attr['alt'] ) ? (string) $attr['alt'] : '',
		'class'   => isset( $attr['class'] ) ? (string) $attr['class'] : '',
		'loading' => isset( $attr['loading'] ) ? (string) $attr['loading'] : '',
	);

	$quote = chr( 34 );

	return '<img src=' . $quote . esc_attr( 'https://example.test/generated/' . $attachment_id . '-' . $size . '.jpg' ) . $quote
		. ' class=' . $quote . esc_attr( (string) ( $attr['class'] ?? '' ) ) . $quote
		. ' alt=' . $quote . esc_attr( (string) ( $attr['alt'] ?? '' ) ) . $quote
		. ' loading=' . $quote . esc_attr( (string) ( $attr['loading'] ?? '' ) ) . $quote
		. ' data-id=' . $quote . $attachment_id . $quote
		. ' data-size=' . $quote . esc_attr( (string) $size ) . $quote
		. ' />';
}

function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['wp_seed_events_visuals_shortcode_data_calls']++;

	return $GLOBALS['wp_seed_events_visuals_shortcode_data'][ absint( $event_id ) ] ?? array();
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';

function wp_seed_events_visuals_shortcode_media( $id, $overrides = array() ) {
	return array_merge(
		array(
			'id'        => absint( $id ),
			'url'       => 'https://example.test/uploads/media-' . absint( $id ) . '.jpg',
			'mime_type' => 'image/jpeg',
			'title'     => 'Media ' . absint( $id ),
			'alt'       => 'Alternative ' . absint( $id ),
			'width'     => 1200,
			'height'    => 800,
			'caption'   => 'Legende ' . absint( $id ),
			'filename'  => 'media-' . absint( $id ) . '.jpg',
		),
		is_array( $overrides ) ? $overrides : array()
	);
}

function wp_seed_events_visuals_shortcode_document() {
	return array(
		'id'        => 20,
		'url'       => 'https://example.test/uploads/programme.pdf',
		'mime_type' => 'application/pdf',
		'title'     => 'Programme',
		'alt'       => '',
		'width'     => null,
		'height'    => null,
		'caption'   => '',
		'filename'  => 'programme.pdf',
	);
}

function wp_seed_events_visuals_shortcode_event( $id, $visuals = array(), $document = null, $featured = null ) {
	$visuals = is_array( $visuals ) ? array_values( $visuals ) : array();

	return array(
		'id'                    => absint( $id ),
		'title'                 => 'Event ' . absint( $id ),
		'url'                   => 'https://example.test/events/' . absint( $id ) . '/',
		'featured_image'        => $featured,
		'communication_visual'  => $visuals[0] ?? null,
		'communication_visuals' => $visuals,
		'other_visuals'         => array_slice( $visuals, 1 ),
		'event_document'        => $document,
		'primary_image_id'      => absint( $visuals[0]['id'] ?? 0 ),
		'flyer_pdf_id'          => absint( $document['id'] ?? 0 ),
	);
}

function wp_seed_events_visuals_shortcode_reset() {
	$GLOBALS['wp_seed_events_visuals_shortcode_data_calls']  = 0;
	$GLOBALS['wp_seed_events_visuals_shortcode_image_calls'] = array();
	$GLOBALS['wp_seed_events_visuals_shortcode_meta_calls']  = 0;
	$GLOBALS['wp_seed_events_visuals_shortcode_post_id']     = 0;
	$GLOBALS['wp_seed_events_visuals_shortcode_post_types']  = array();

	unset( $GLOBALS['wp_seed_events_public_event_id'] );
}

function wp_seed_events_visuals_shortcode_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function wp_seed_events_visuals_shortcode_contains( $needle, $haystack, $message ) {
	wp_seed_events_visuals_shortcode_assert( false !== strpos( (string) $haystack, (string) $needle ), $message );
}

function wp_seed_events_visuals_shortcode_not_contains( $needle, $haystack, $message ) {
	wp_seed_events_visuals_shortcode_assert( false === strpos( (string) $haystack, (string) $needle ), $message );
}

function wp_seed_events_visuals_shortcode_case( $name, $callback ) {
	wp_seed_events_visuals_shortcode_reset();

	try {
		$callback();
		$GLOBALS['wp_seed_events_visuals_shortcode_case_count']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

function wp_seed_events_visuals_shortcode_marker( $attachment_id ) {
	return 'data-id=' . chr( 34 ) . absint( $attachment_id ) . chr( 34 );
}

$flyer   = wp_seed_events_visuals_shortcode_media( 1 );
$visual2 = wp_seed_events_visuals_shortcode_media( 2 );
$visual3 = wp_seed_events_visuals_shortcode_media( 3 );
$pdf     = wp_seed_events_visuals_shortcode_document();

$GLOBALS['wp_seed_events_visuals_shortcode_data'] = array(
	914  => wp_seed_events_visuals_shortcode_event( 914, array( $flyer, $visual2 ), $pdf ),
	1011 => wp_seed_events_visuals_shortcode_event( 1011, array( $flyer ) ),
	1022 => wp_seed_events_visuals_shortcode_event( 1022, array(), $pdf ),
	1031 => wp_seed_events_visuals_shortcode_event( 1031, array( $flyer, $visual2, $visual3 ) ),
	1048 => wp_seed_events_visuals_shortcode_event( 1048, array( $flyer, $visual2 ), $pdf ),
	1057 => wp_seed_events_visuals_shortcode_event( 1057 ),
	1060 => wp_seed_events_visuals_shortcode_event( 1060, array(), null, wp_seed_events_visuals_shortcode_media( 9 ) ),
);

wp_seed_events_visuals_shortcode_case(
	'1 no ID in an event context',
	function () {
		$GLOBALS['wp_seed_events_visuals_shortcode_post_id']         = 914;
		$GLOBALS['wp_seed_events_visuals_shortcode_post_types'][914] = 'wp_seed_event';
		$html = wp_seed_events_event_visuals_shortcode( array() );

		wp_seed_events_visuals_shortcode_contains( wp_seed_events_visuals_shortcode_marker( 1 ), $html, 'Current event context was not resolved.' );
		wp_seed_events_visuals_shortcode_assert( 1 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Current context did not load Event Data exactly once.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'2 no ID on an ordinary page',
	function () {
		$GLOBALS['wp_seed_events_visuals_shortcode_post_id']         = 998;
		$GLOBALS['wp_seed_events_visuals_shortcode_post_types'][998] = 'page';
		$html = wp_seed_events_event_visuals_shortcode( array() );

		wp_seed_events_visuals_shortcode_assert( '' === $html, 'Ordinary page received an arbitrary event.' );
		wp_seed_events_visuals_shortcode_assert( 0 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Ordinary page triggered Event Data.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'3 valid explicit ID has priority',
	function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 1031;
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => '914' ) );

		wp_seed_events_visuals_shortcode_contains( 'programme.pdf', $html, 'Explicit event was not rendered.' );
		wp_seed_events_visuals_shortcode_assert( 2 === substr_count( $html, '<figure' ), 'Explicit event was contaminated by public context.' );
		wp_seed_events_visuals_shortcode_assert( 1 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Explicit ID did not load Event Data exactly once.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'4 invalid explicit ID never falls back',
	function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 914;

		foreach ( array( '', '0', '-914', 'event', '91.4', array( 914 ) ) as $invalid_id ) {
			$html = wp_seed_events_event_visuals_shortcode( array( 'id' => $invalid_id ) );
			wp_seed_events_visuals_shortcode_assert( '' === $html, 'Invalid explicit ID fell back to context.' );
		}

		wp_seed_events_visuals_shortcode_assert( 0 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Invalid explicit ID triggered Event Data.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'5 explicit page ID is rejected',
	function () {
		$GLOBALS['wp_seed_events_public_event_id']                    = 914;
		$GLOBALS['wp_seed_events_visuals_shortcode_post_types'][998] = 'page';
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => '998' ) );

		wp_seed_events_visuals_shortcode_assert( '' === $html, 'Page ID fell back to an event.' );
		wp_seed_events_visuals_shortcode_assert( 1 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Page ID should be rejected by one Event Data lookup.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'6 flyer only',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011 ) );

		wp_seed_events_visuals_shortcode_contains( 'wp-seed-event-visuals__item--flyer', $html, 'Flyer item is missing.' );
		wp_seed_events_visuals_shortcode_assert( 1 === substr_count( $html, '<figure' ), 'Flyer-only event rendered extra figures.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'7 several visuals',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1031 ) );

		wp_seed_events_visuals_shortcode_assert( 3 === substr_count( $html, '<figure' ), 'Several visuals were not all rendered.' );
		wp_seed_events_visuals_shortcode_assert( 2 === substr_count( $html, 'wp-seed-event-visuals__item--visual' ), 'Other visual count is incorrect.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'8 PDF only',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1022 ) );

		wp_seed_events_visuals_shortcode_contains( 'wp-seed-event-visuals__document-link', $html, 'PDF link is missing.' );
		wp_seed_events_visuals_shortcode_not_contains( '<figure', $html, 'PDF-only event produced an image.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'9 complete combination',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 914 ) );

		wp_seed_events_visuals_shortcode_assert( 2 === substr_count( $html, '<figure' ), 'Complete event image count is incorrect.' );
		wp_seed_events_visuals_shortcode_assert( 1 === substr_count( $html, 'wp-seed-event-visuals__document-link' ), 'Complete event PDF count is incorrect.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'10 event without media',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1057 ) );

		wp_seed_events_visuals_shortcode_assert( '' === $html, 'Event without media produced a wrapper.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'11 show flyer false',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 914, 'show_flyer' => 'false' ) );

		wp_seed_events_visuals_shortcode_not_contains( wp_seed_events_visuals_shortcode_marker( 1 ), $html, 'Flyer was not hidden.' );
		wp_seed_events_visuals_shortcode_contains( wp_seed_events_visuals_shortcode_marker( 2 ), $html, 'Other visual disappeared with flyer.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'12 show visuals false',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 914, 'show_visuals' => '0' ) );

		wp_seed_events_visuals_shortcode_contains( wp_seed_events_visuals_shortcode_marker( 1 ), $html, 'Flyer disappeared with other visuals.' );
		wp_seed_events_visuals_shortcode_not_contains( wp_seed_events_visuals_shortcode_marker( 2 ), $html, 'Other visual was not hidden.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'13 show document false',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 914, 'show_document' => 'off' ) );

		wp_seed_events_visuals_shortcode_not_contains( 'wp-seed-event-visuals__document-link', $html, 'Document was not hidden.' );
		wp_seed_events_visuals_shortcode_contains( '<figure', $html, 'Images disappeared with document.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'14 captions true and false',
	function () {
		$with_captions = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'show_captions' => 'yes' ) );
		$without       = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'show_captions' => 'no' ) );

		wp_seed_events_visuals_shortcode_contains( '<figcaption', $with_captions, 'Caption was not enabled.' );
		wp_seed_events_visuals_shortcode_not_contains( '<figcaption', $without, 'Caption was not disabled.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'15 original links true and false',
	function () {
		$linked   = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'link_original' => 'true' ) );
		$unlinked = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'link_original' => 'false' ) );

		wp_seed_events_visuals_shortcode_contains( 'wp-seed-event-visuals__image-link', $linked, 'Original link was not enabled.' );
		wp_seed_events_visuals_shortcode_not_contains( 'wp-seed-event-visuals__image-link', $unlinked, 'Original link was not disabled.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'16 grid and list layouts',
	function () {
		$grid = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'layout' => 'grid' ) );
		$list = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'layout' => 'list' ) );

		wp_seed_events_visuals_shortcode_contains( 'is-layout-grid', $grid, 'Grid layout class is missing.' );
		wp_seed_events_visuals_shortcode_contains( 'is-layout-list', $list, 'List layout class is missing.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'17 heading levels h2 to h6',
	function () {
		foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) {
			$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'heading_level' => $level ) );

			wp_seed_events_visuals_shortcode_contains( '<' . $level . ' class=', $html, 'Heading level ' . $level . ' is missing.' );
		}
	}
);

wp_seed_events_visuals_shortcode_case(
	'18 empty title',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'title' => '' ) );

		wp_seed_events_visuals_shortcode_not_contains( 'wp-seed-event-visuals__title', $html, 'Empty title produced a heading.' );
		wp_seed_events_visuals_shortcode_contains( 'aria-label=', $html, 'Untitled section has no accessible label.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'19 valid and invalid image sizes',
	function () {
		wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'image_size' => 'poster' ) );
		wp_seed_events_visuals_shortcode_assert( 'poster' === $GLOBALS['wp_seed_events_visuals_shortcode_image_calls'][0]['size'], 'Registered image size was not preserved.' );

		$GLOBALS['wp_seed_events_visuals_shortcode_image_calls'] = array();
		wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'image_size' => 'server-path' ) );
		wp_seed_events_visuals_shortcode_assert( 'large' === $GLOBALS['wp_seed_events_visuals_shortcode_image_calls'][0]['size'], 'Invalid image size did not fall back to large.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'20 accepted boolean forms',
	function () {
		foreach ( array( '1', 'true', 'yes', 'on' ) as $truthy ) {
			$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'show_flyer' => $truthy ) );
			wp_seed_events_visuals_shortcode_contains( '<figure', $html, 'Truthy value ' . $truthy . ' was rejected.' );
		}

		foreach ( array( '0', 'false', 'no', 'off' ) as $falsy ) {
			$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1011, 'show_flyer' => $falsy ) );
			wp_seed_events_visuals_shortcode_assert( '' === $html, 'Falsy value ' . $falsy . ' was rejected.' );
		}
	}
);

wp_seed_events_visuals_shortcode_case(
	'21 invalid boolean values use renderer defaults',
	function () {
		$html = wp_seed_events_event_visuals_shortcode(
			array(
				'id'            => 1011,
				'show_flyer'    => 'maybe',
				'show_captions' => 'maybe',
			)
		);

		wp_seed_events_visuals_shortcode_contains( '<figure', $html, 'Invalid flyer boolean did not use true default.' );
		wp_seed_events_visuals_shortcode_not_contains( '<figcaption', $html, 'Invalid caption boolean did not use false default.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'22 output is exactly the shared renderer output',
	function () {
		$options = array(
			'title'          => 'Galerie',
			'heading_level'  => 'h4',
			'show_flyer'     => false,
			'show_visuals'   => true,
			'show_document'  => true,
			'show_captions'  => true,
			'image_size'     => 'medium',
			'link_original'  => false,
			'layout'         => 'list',
		);
		$shortcode = wp_seed_events_event_visuals_shortcode( array_merge( array( 'id' => 914 ), $options ) );
		$direct    = wp_seed_events_render_public_event_visuals_section( $GLOBALS['wp_seed_events_visuals_shortcode_data'][914], $options );

		wp_seed_events_visuals_shortcode_assert( $direct === $shortcode, 'Shortcode altered the shared renderer output.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'23 Event Data is loaded exactly once',
	function () {
		wp_seed_events_event_visuals_shortcode( array( 'id' => 914 ) );

		wp_seed_events_visuals_shortcode_assert( 1 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Event Data call count is not one.' );
	}
);

$rendering_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$adapter_start    = strpos( $rendering_source, 'function wp_seed_events_event_visuals_shortcode_event_id' );
$adapter_end      = strpos( $rendering_source, 'function wp_seed_events_event_people_shortcode' );
$adapter_source   = false !== $adapter_start && false !== $adapter_end
	? substr( $rendering_source, $adapter_start, $adapter_end - $adapter_start )
	: '';
$main_source      = file_get_contents( dirname( __DIR__ ) . '/wp-seed-events.php' );

wp_seed_events_visuals_shortcode_case(
	'24 shortcode delegates exactly once to the shared renderer',
	function () use ( $adapter_source ) {
		wp_seed_events_visuals_shortcode_assert(
			1 === substr_count( $adapter_source, 'wp_seed_events_render_public_event_visuals_section(' ),
			'Shortcode adapter does not contain exactly one renderer call.'
		);
	}
);

wp_seed_events_visuals_shortcode_case(
	'25 shortcode performs no direct meta access',
	function () use ( $adapter_source ) {
		wp_seed_events_event_visuals_shortcode( array( 'id' => 914 ) );

		wp_seed_events_visuals_shortcode_not_contains( 'get_post_meta', $adapter_source, 'Shortcode adapter reads post meta directly.' );
		wp_seed_events_visuals_shortcode_assert( 0 === $GLOBALS['wp_seed_events_visuals_shortcode_meta_calls'], 'Shortcode triggered a direct meta read.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'26 shortcode contains no duplicated business or HTML rendering logic',
	function () use ( $adapter_source ) {
		foreach ( array( '$wpdb', 'SELECT ', 'wp_remote_', 'global $wp_seed_events_public_event_id', '<section', '<figure', '<img', '<a ' ) as $forbidden ) {
			wp_seed_events_visuals_shortcode_not_contains( $forbidden, $adapter_source, 'Forbidden adapter logic found: ' . $forbidden );
		}
	}
);

wp_seed_events_visuals_shortcode_case(
	'27 featured image is never promoted to communication visuals',
	function () {
		$html = wp_seed_events_event_visuals_shortcode( array( 'id' => 1060 ) );

		wp_seed_events_visuals_shortcode_assert( '' === $html, 'Featured image was rendered as a communication visual.' );
		wp_seed_events_visuals_shortcode_assert( array() === $GLOBALS['wp_seed_events_visuals_shortcode_image_calls'], 'Featured image reached the image renderer.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'28 empty output has no wrapper',
	function () {
		$without_media = wp_seed_events_event_visuals_shortcode( array( 'id' => 1057 ) );
		$all_hidden    = wp_seed_events_event_visuals_shortcode(
			array(
				'id'            => 914,
				'show_flyer'    => false,
				'show_visuals'  => false,
				'show_document' => false,
			)
		);

		wp_seed_events_visuals_shortcode_assert( '' === $without_media, 'Media-free event produced a wrapper.' );
		wp_seed_events_visuals_shortcode_assert( '' === $all_hidden, 'Fully hidden media produced a wrapper.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'29 historical image and flyer fields remain compatible',
	function () {
		$title = wp_seed_events_event_field_shortcode( array( 'id' => 914, 'field' => 'title' ) );
		$image = wp_seed_events_event_field_shortcode( array( 'id' => 914, 'field' => 'image' ) );
		$flyer = wp_seed_events_event_field_shortcode( array( 'id' => 914, 'field' => 'flyer' ) );

		wp_seed_events_visuals_shortcode_assert( 'Event 914' === $title, 'Historical title field changed.' );
		wp_seed_events_visuals_shortcode_contains( 'wp-seed-event-image', $image, 'Historical image class changed.' );
		wp_seed_events_visuals_shortcode_contains( wp_seed_events_visuals_shortcode_marker( 1 ), $image, 'Historical image ID changed.' );
		wp_seed_events_visuals_shortcode_contains( 'attachment-20.pdf', $flyer, 'Historical flyer URL changed.' );
		wp_seed_events_visuals_shortcode_not_contains( 'wp-seed-event-visuals', $image . $flyer, 'Historical fields were silently delegated.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'30 shortcode is registered once and never leaks a raw token',
	function () use ( $main_source ) {
		$registration = 'add_shortcode( ' . chr( 39 ) . 'wp_seed_event_visuals' . chr( 39 ) . ', ' . chr( 39 ) . 'wp_seed_events_event_visuals_shortcode' . chr( 39 ) . ' );';
		$html         = wp_seed_events_event_visuals_shortcode( array( 'id' => 914 ) );

		wp_seed_events_visuals_shortcode_assert( 1 === substr_count( $main_source, $registration ), 'Shortcode registration count is not one.' );
		wp_seed_events_visuals_shortcode_not_contains( '[wp_seed_event_visuals', $html, 'Raw shortcode token leaked into output.' );
	}
);

wp_seed_events_visuals_shortcode_case(
	'31 resolved public event context works outside an event post',
	function () {
		$GLOBALS['wp_seed_events_public_event_id'] = 1031;
		$GLOBALS['wp_seed_events_visuals_shortcode_post_id'] = 998;
		$GLOBALS['wp_seed_events_visuals_shortcode_post_types'][998] = 'page';

		$html = wp_seed_events_event_visuals_shortcode( array() );

		wp_seed_events_visuals_shortcode_assert( 3 === substr_count( $html, '<figure' ), 'Resolved public event context was not used.' );
		wp_seed_events_visuals_shortcode_assert( 1 === $GLOBALS['wp_seed_events_visuals_shortcode_data_calls'], 'Resolved context loaded Event Data more than once.' );
	}
);

echo 'Visuals shortcode harness: ' . $GLOBALS['wp_seed_events_visuals_shortcode_case_count'] . ' cases passed.' . PHP_EOL;
