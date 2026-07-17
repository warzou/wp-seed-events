<?php
/**
 * Standalone assertions for the shared public event visuals renderer.
 *
 * Run with: php tests/visuals-renderer-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );

$GLOBALS['wp_seed_events_visuals_case_count']       = 0;
$GLOBALS['wp_seed_events_visuals_image_calls']      = array();
$GLOBALS['wp_seed_events_visuals_event_data_calls'] = 0;
$GLOBALS['wp_seed_events_visuals_meta_calls']       = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function wp_parse_args( $args, $defaults = array() ) {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
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

function wp_get_attachment_image( $attachment_id, $size = 'thumbnail', $icon = false, $attr = array() ) {
	$attachment_id = absint( $attachment_id );
	$attr          = is_array( $attr ) ? $attr : array();

	$GLOBALS['wp_seed_events_visuals_image_calls'][] = array(
		'id'      => $attachment_id,
		'size'    => (string) $size,
		'alt'     => isset( $attr['alt'] ) ? (string) $attr['alt'] : '',
		'class'   => isset( $attr['class'] ) ? (string) $attr['class'] : '',
		'loading' => isset( $attr['loading'] ) ? (string) $attr['loading'] : '',
	);

	if ( 99 === $attachment_id ) {
		return '';
	}

	$quote = chr( 34 );

	return '<img src=' . $quote . esc_attr( 'https://example.test/generated/' . $attachment_id . '-' . $size . '.jpg' ) . $quote
		. ' class=' . $quote . esc_attr( (string) ( $attr['class'] ?? '' ) ) . $quote
		. ' alt=' . $quote . esc_attr( (string) ( $attr['alt'] ?? '' ) ) . $quote
		. ' loading=' . $quote . esc_attr( (string) ( $attr['loading'] ?? '' ) ) . $quote
		. ' data-id=' . $quote . $attachment_id . $quote
		. ' data-size=' . $quote . esc_attr( (string) $size ) . $quote
		. ' width=' . $quote . '1200' . $quote
		. ' height=' . $quote . '800' . $quote
		. ' srcset=' . $quote . 'fixture-srcset' . $quote
		. ' sizes=' . $quote . 'fixture-sizes' . $quote
		. ' />';
}

function wp_seed_events_get_event_data( $event_id ) {
	$GLOBALS['wp_seed_events_visuals_event_data_calls']++;

	return array();
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$GLOBALS['wp_seed_events_visuals_meta_calls']++;

	return '';
}

require dirname( __DIR__ ) . '/includes/public/rendering.php';

function wp_seed_events_visuals_media( $id, $overrides = array() ) {
	$media = array(
		'id'        => absint( $id ),
		'url'       => 'https://example.test/uploads/media-' . absint( $id ) . '.jpg',
		'mime_type' => 'image/jpeg',
		'title'     => 'Media ' . absint( $id ),
		'alt'       => 'Alternative ' . absint( $id ),
		'width'     => 1200,
		'height'    => 800,
		'caption'   => 'Legende ' . absint( $id ),
		'filename'  => 'media-' . absint( $id ) . '.jpg',
	);

	return array_merge( $media, is_array( $overrides ) ? $overrides : array() );
}

function wp_seed_events_visuals_document( $overrides = array() ) {
	return array_merge(
		array(
			'id'        => 20,
			'url'       => 'https://example.test/uploads/programme.pdf',
			'mime_type' => 'application/pdf',
			'title'     => 'Programme',
			'alt'       => '',
			'width'     => null,
			'height'    => null,
			'caption'   => '',
			'filename'  => 'programme.pdf',
		),
		is_array( $overrides ) ? $overrides : array()
	);
}

function wp_seed_events_visuals_event( $visuals = array(), $document = null, $featured_image = null ) {
	$visuals = is_array( $visuals ) ? array_values( $visuals ) : array();

	return array(
		'id'                    => 42,
		'featured_image'        => $featured_image,
		'communication_visual'  => $visuals[0] ?? null,
		'communication_visuals' => $visuals,
		'other_visuals'         => array_slice( $visuals, 1 ),
		'event_document'        => $document,
	);
}

function wp_seed_events_visuals_reset_calls() {
	$GLOBALS['wp_seed_events_visuals_image_calls']      = array();
	$GLOBALS['wp_seed_events_visuals_event_data_calls'] = 0;
	$GLOBALS['wp_seed_events_visuals_meta_calls']       = 0;
}

function wp_seed_events_visuals_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function wp_seed_events_visuals_contains( $needle, $haystack, $message ) {
	wp_seed_events_visuals_assert( false !== strpos( (string) $haystack, (string) $needle ), $message );
}

function wp_seed_events_visuals_not_contains( $needle, $haystack, $message ) {
	wp_seed_events_visuals_assert( false === strpos( (string) $haystack, (string) $needle ), $message );
}

function wp_seed_events_visuals_case( $name, $callback ) {
	wp_seed_events_visuals_reset_calls();

	try {
		$callback();
		$GLOBALS['wp_seed_events_visuals_case_count']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

function wp_seed_events_visuals_count( $needle, $haystack ) {
	return substr_count( (string) $haystack, (string) $needle );
}

function wp_seed_events_visuals_image_marker( $attachment_id ) {
	return 'data-id=' . chr( 34 ) . absint( $attachment_id ) . chr( 34 );
}

$flyer   = wp_seed_events_visuals_media( 1 );
$visual2 = wp_seed_events_visuals_media( 2 );
$visual3 = wp_seed_events_visuals_media( 3 );
$pdf     = wp_seed_events_visuals_document();

wp_seed_events_visuals_case(
	'1 no media returns an empty string',
	function () {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event() );

		wp_seed_events_visuals_assert( '' === $html, 'An empty event produced markup.' );
		wp_seed_events_visuals_assert( array() === $GLOBALS['wp_seed_events_visuals_image_calls'], 'An empty event requested an image.' );
	}
);

wp_seed_events_visuals_case(
	'2 flyer only',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer ) ) );

		wp_seed_events_visuals_contains( 'wp-seed-event-visuals__item--flyer', $html, 'Flyer item is missing.' );
		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 1 ), $html, 'Flyer image is missing.' );
		wp_seed_events_visuals_contains( 'srcset=', $html, 'Native responsive srcset is missing.' );
		wp_seed_events_visuals_contains( 'sizes=', $html, 'Native responsive sizes are missing.' );
		wp_seed_events_visuals_contains( 'width=', $html, 'Native image width is missing.' );
		wp_seed_events_visuals_contains( 'height=', $html, 'Native image height is missing.' );
		wp_seed_events_visuals_assert( 'lazy' === $GLOBALS['wp_seed_events_visuals_image_calls'][0]['loading'], 'Lazy loading was not requested.' );
		wp_seed_events_visuals_assert( 1 === wp_seed_events_visuals_count( '<figure', $html ), 'Flyer rendered more than one figure.' );
		wp_seed_events_visuals_assert( 1 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'Flyer requested an unexpected number of images.' );
	}
);

wp_seed_events_visuals_case(
	'3 other visuals only',
	function () use ( $visual2, $visual3 ) {
		$event                          = wp_seed_events_visuals_event();
		$event['other_visuals']         = array( $visual2, $visual3 );
		$event['communication_visual']  = null;
		$event['communication_visuals'] = array();
		$html                           = wp_seed_events_render_public_event_visuals_section( $event );

		wp_seed_events_visuals_not_contains( 'wp-seed-event-visuals__item--flyer', $html, 'An other visual was promoted to flyer.' );
		wp_seed_events_visuals_assert( 2 === wp_seed_events_visuals_count( 'wp-seed-event-visuals__item--visual', $html ), 'Other visuals were not both rendered.' );
		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 2 ), $html, 'First other visual is missing.' );
		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 3 ), $html, 'Second other visual is missing.' );
	}
);

wp_seed_events_visuals_case(
	'4 flyer and other visuals',
	function () use ( $flyer, $visual2, $visual3 ) {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer, $visual2, $visual3 ) ) );

		wp_seed_events_visuals_assert( 3 === wp_seed_events_visuals_count( '<figure', $html ), 'The visual collection is incomplete.' );
		wp_seed_events_visuals_assert( 1 === wp_seed_events_visuals_count( 'wp-seed-event-visuals__item--flyer', $html ), 'Flyer cardinality is incorrect.' );
		wp_seed_events_visuals_assert( 2 === wp_seed_events_visuals_count( 'wp-seed-event-visuals__item--visual', $html ), 'Other visual cardinality is incorrect.' );
	}
);

wp_seed_events_visuals_case(
	'5 PDF only',
	function () use ( $pdf ) {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array(), $pdf ) );

		wp_seed_events_visuals_contains( 'wp-seed-event-visuals__document-link', $html, 'PDF link is missing.' );
		wp_seed_events_visuals_contains( 'Télécharger le document PDF', $html, 'PDF link text is not explicit.' );
		wp_seed_events_visuals_contains( 'programme.pdf', $html, 'Safe PDF filename is missing.' );
		wp_seed_events_visuals_not_contains( '<figure', $html, 'PDF produced an image figure.' );
		wp_seed_events_visuals_not_contains( 'target=', $html, 'PDF link opens a new target.' );
		wp_seed_events_visuals_assert( array() === $GLOBALS['wp_seed_events_visuals_image_calls'], 'PDF requested an attachment image.' );
	}
);

wp_seed_events_visuals_case(
	'6 complete combination',
	function () use ( $flyer, $visual2, $visual3, $pdf ) {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer, $visual2, $visual3 ), $pdf ) );

		wp_seed_events_visuals_assert( 3 === wp_seed_events_visuals_count( '<figure', $html ), 'Complete event image count is incorrect.' );
		wp_seed_events_visuals_assert( 1 === wp_seed_events_visuals_count( 'wp-seed-event-visuals__document-link', $html ), 'Complete event PDF count is incorrect.' );
		wp_seed_events_visuals_assert( 4 === wp_seed_events_visuals_count( '<li ', $html ), 'Complete event list cardinality is incorrect.' );
	}
);

wp_seed_events_visuals_case(
	'7 show flyer false',
	function () use ( $flyer, $visual2 ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer, $visual2 ) ),
			array( 'show_flyer' => 'false' )
		);

		wp_seed_events_visuals_not_contains( wp_seed_events_visuals_image_marker( 1 ), $html, 'Flyer was not hidden.' );
		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 2 ), $html, 'Other visual disappeared with flyer.' );
	}
);

wp_seed_events_visuals_case(
	'8 show visuals false',
	function () use ( $flyer, $visual2 ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer, $visual2 ) ),
			array( 'show_visuals' => 'no' )
		);

		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 1 ), $html, 'Flyer disappeared with other visuals.' );
		wp_seed_events_visuals_not_contains( wp_seed_events_visuals_image_marker( 2 ), $html, 'Other visual was not hidden.' );
	}
);

wp_seed_events_visuals_case(
	'9 show document false',
	function () use ( $flyer, $pdf ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ), $pdf ),
			array( 'show_document' => 0 )
		);

		wp_seed_events_visuals_contains( wp_seed_events_visuals_image_marker( 1 ), $html, 'Flyer disappeared with document.' );
		wp_seed_events_visuals_not_contains( 'wp-seed-event-visuals__document-link', $html, 'Document was not hidden.' );
	}
);

wp_seed_events_visuals_case(
	'10 captions enabled and escaped',
	function () {
		$captioned = wp_seed_events_visuals_media( 4, array( 'caption' => '<strong>Legende & test</strong>' ) );
		$html      = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $captioned ) ),
			array( 'show_captions' => true )
		);

		wp_seed_events_visuals_contains( '<figcaption', $html, 'Caption element is missing.' );
		wp_seed_events_visuals_contains( 'Legende &amp; test', $html, 'Caption was not escaped.' );
		wp_seed_events_visuals_not_contains( '<strong>', $html, 'Caption retained arbitrary HTML.' );
	}
);

wp_seed_events_visuals_case(
	'11 captions disabled',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'show_captions' => false )
		);

		wp_seed_events_visuals_not_contains( '<figcaption', $html, 'Caption rendered while disabled.' );
		wp_seed_events_visuals_not_contains( 'Legende 1', $html, 'Caption text leaked while disabled.' );
	}
);

wp_seed_events_visuals_case(
	'12 original image link enabled',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'link_original' => true )
		);

		wp_seed_events_visuals_contains( 'wp-seed-event-visuals__image-link', $html, 'Original image link is missing.' );
		wp_seed_events_visuals_contains( 'https://example.test/uploads/media-1.jpg', $html, 'Original image URL is missing.' );
		wp_seed_events_visuals_contains( 'aria-label=', $html, 'Original image link lacks an accessible name.' );
		wp_seed_events_visuals_not_contains( 'target=', $html, 'Original image link opens a new target.' );
	}
);

wp_seed_events_visuals_case(
	'13 original image link disabled',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'link_original' => false )
		);

		wp_seed_events_visuals_not_contains( 'wp-seed-event-visuals__image-link', $html, 'Original image link rendered while disabled.' );
		wp_seed_events_visuals_contains( '<img ', $html, 'Unlinked image is missing.' );
	}
);

wp_seed_events_visuals_case(
	'14 grid layout',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'layout' => 'grid' )
		);

		wp_seed_events_visuals_contains( 'is-layout-grid', $html, 'Grid class is missing.' );
	}
);

wp_seed_events_visuals_case(
	'15 list layout',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'layout' => 'list' )
		);

		wp_seed_events_visuals_contains( 'is-layout-list', $html, 'List class is missing.' );
		wp_seed_events_visuals_not_contains( 'is-layout-grid', $html, 'Grid class leaked into list layout.' );
	}
);

wp_seed_events_visuals_case(
	'16 heading levels h2 through h6',
	function () use ( $flyer ) {
		foreach ( array( 'h2', 'h3', 'h4', 'h5', 'h6' ) as $level ) {
			$html = wp_seed_events_render_public_event_visuals_section(
				wp_seed_events_visuals_event( array( $flyer ) ),
				array( 'heading_level' => $level )
			);

			wp_seed_events_visuals_contains( '<' . $level . ' class=', $html, 'Expected heading level is missing: ' . $level );
			wp_seed_events_visuals_contains( '</' . $level . '>', $html, 'Expected heading closing tag is missing: ' . $level );
		}
	}
);

wp_seed_events_visuals_case(
	'17 empty title uses an accessible section label',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'title' => '' )
		);

		wp_seed_events_visuals_not_contains( 'wp-seed-event-visuals__title', $html, 'Empty title produced a heading.' );
		wp_seed_events_visuals_contains( 'aria-label=', $html, 'Untitled section lacks an accessible label.' );
		wp_seed_events_visuals_contains( 'Visuels de communication', $html, 'Untitled section label is incorrect.' );
	}
);

wp_seed_events_visuals_case(
	'18 invalid heading falls back to h2',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'heading_level' => 'h1' )
		);

		wp_seed_events_visuals_contains( '<h2 class=', $html, 'Invalid heading did not fall back to h2.' );
		wp_seed_events_visuals_not_contains( '<h1 ', $html, 'Invalid h1 heading was accepted.' );
	}
);

wp_seed_events_visuals_case(
	'19 invalid layout falls back to grid',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'layout' => 'masonry' )
		);

		wp_seed_events_visuals_contains( 'is-layout-grid', $html, 'Invalid layout did not fall back to grid.' );
		wp_seed_events_visuals_not_contains( 'masonry', $html, 'Invalid layout leaked into markup.' );
	}
);

wp_seed_events_visuals_case(
	'20 invalid image size falls back to large',
	function () use ( $flyer ) {
		wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'image_size' => 'server-path' )
		);

		wp_seed_events_visuals_assert( 'large' === $GLOBALS['wp_seed_events_visuals_image_calls'][0]['size'], 'Invalid image size did not fall back to large.' );

		wp_seed_events_visuals_reset_calls();
		wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'image_size' => 'poster' )
		);

		wp_seed_events_visuals_assert( 'poster' === $GLOBALS['wp_seed_events_visuals_image_calls'][0]['size'], 'Registered custom image size was rejected.' );
	}
);

wp_seed_events_visuals_case(
	'21 duplicate visuals are removed without reordering',
	function () use ( $flyer, $visual2 ) {
		$event = wp_seed_events_visuals_event( array( $flyer, $visual2, $flyer, $visual2 ) );
		$html  = wp_seed_events_render_public_event_visuals_section( $event );

		wp_seed_events_visuals_assert( 1 === wp_seed_events_visuals_count( wp_seed_events_visuals_image_marker( 1 ), $html ), 'Flyer duplicate was rendered.' );
		wp_seed_events_visuals_assert( 1 === wp_seed_events_visuals_count( wp_seed_events_visuals_image_marker( 2 ), $html ), 'Other visual duplicate was rendered.' );
		wp_seed_events_visuals_assert( 2 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'Duplicate visuals triggered duplicate attachment calls.' );
	}
);

wp_seed_events_visuals_case(
	'22 invalid image MIME is ignored',
	function () {
		$invalid = wp_seed_events_visuals_media( 5, array( 'mime_type' => 'text/html' ) );
		$html    = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $invalid ) ) );

		wp_seed_events_visuals_assert( '' === $html, 'Unexpected image MIME produced markup.' );
		wp_seed_events_visuals_assert( array() === $GLOBALS['wp_seed_events_visuals_image_calls'], 'Unexpected image MIME reached the attachment renderer.' );
	}
);

wp_seed_events_visuals_case(
	'23 non PDF document is ignored',
	function () {
		$invalid = wp_seed_events_visuals_document( array( 'mime_type' => 'application/msword' ) );
		$html    = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array(), $invalid ) );

		wp_seed_events_visuals_assert( '' === $html, 'Non-PDF document produced markup.' );
	}
);

wp_seed_events_visuals_case(
	'24 absent or unsafe URL is ignored',
	function () {
		$missing_image = wp_seed_events_visuals_media( 6, array( 'url' => '' ) );
		$unsafe_pdf    = wp_seed_events_visuals_document( array( 'url' => 'javascript:alert(1)' ) );
		$image_html    = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $missing_image ) ) );
		$pdf_html      = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array(), $unsafe_pdf ) );

		wp_seed_events_visuals_assert( '' === $image_html, 'Image without URL produced markup.' );
		wp_seed_events_visuals_assert( '' === $pdf_html, 'Unsafe PDF URL produced an empty link or container.' );
	}
);

wp_seed_events_visuals_case(
	'25 empty alt remains empty',
	function () {
		$decorative = wp_seed_events_visuals_media( 7, array( 'alt' => '' ) );
		$html       = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $decorative ) ) );
		$empty_alt  = 'alt=' . chr( 34 ) . chr( 34 );

		wp_seed_events_visuals_contains( $empty_alt, $html, 'Empty alt was replaced.' );
		wp_seed_events_visuals_assert( '' === $GLOBALS['wp_seed_events_visuals_image_calls'][0]['alt'], 'Empty alt was not passed to WordPress.' );
		wp_seed_events_visuals_not_contains( 'alt=' . chr( 34 ) . 'Media 7' . chr( 34 ), $html, 'Title was used as alt fallback.' );
		wp_seed_events_visuals_not_contains( 'alt=' . chr( 34 ) . 'media-7.jpg' . chr( 34 ), $html, 'Filename was used as alt fallback.' );
	}
);

wp_seed_events_visuals_case(
	'26 empty caption stays absent',
	function () {
		$uncaptioned = wp_seed_events_visuals_media( 8, array( 'caption' => '', 'title' => 'Fallback forbidden' ) );
		$html        = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $uncaptioned ) ),
			array( 'show_captions' => true )
		);

		wp_seed_events_visuals_not_contains( '<figcaption', $html, 'Empty caption produced a figcaption.' );
		wp_seed_events_visuals_not_contains( 'Fallback forbidden', $html, 'Title was used as caption fallback.' );
	}
);

wp_seed_events_visuals_case(
	'27 PDF filename is sanitized',
	function () {
		$document = wp_seed_events_visuals_document( array( 'filename' => '../../private programme.pdf' ) );
		$html     = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array(), $document ) );

		wp_seed_events_visuals_contains( 'private-programme.pdf', $html, 'Safe PDF basename is missing.' );
		wp_seed_events_visuals_not_contains( '../', $html, 'PDF filename exposed path traversal.' );
		wp_seed_events_visuals_not_contains( 'private programme.pdf', $html, 'Unsafe filename spacing was retained.' );
	}
);

wp_seed_events_visuals_case(
	'28 featured image is never added',
	function () {
		$featured = wp_seed_events_visuals_media( 9 );
		$event    = wp_seed_events_visuals_event( array(), null, $featured );
		$html     = wp_seed_events_render_public_event_visuals_section( $event );

		wp_seed_events_visuals_assert( '' === $html, 'Featured image was added to communication visuals.' );
		wp_seed_events_visuals_assert( array() === $GLOBALS['wp_seed_events_visuals_image_calls'], 'Featured image reached the attachment renderer.' );
	}
);

wp_seed_events_visuals_case(
	'29 DOM order is flyer then visuals then document',
	function () use ( $flyer, $visual2, $visual3, $pdf ) {
		$html       = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer, $visual2, $visual3 ), $pdf ) );
		$flyer_pos = strpos( $html, wp_seed_events_visuals_image_marker( 1 ) );
		$two_pos   = strpos( $html, wp_seed_events_visuals_image_marker( 2 ) );
		$three_pos = strpos( $html, wp_seed_events_visuals_image_marker( 3 ) );
		$pdf_pos   = strpos( $html, 'wp-seed-event-visuals__document-link' );

		wp_seed_events_visuals_assert( false !== $flyer_pos && false !== $two_pos && false !== $three_pos && false !== $pdf_pos, 'Ordered media is incomplete.' );
		wp_seed_events_visuals_assert( $flyer_pos < $two_pos && $two_pos < $three_pos && $three_pos < $pdf_pos, 'Media DOM order is incorrect.' );
	}
);

$rendering_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/rendering.php' );
$visuals_start    = strpos( $rendering_source, 'function wp_seed_events_public_event_visual_media_is_valid' );
$visuals_end      = strpos( $rendering_source, 'function wp_seed_events_public_event_occurrence_has_valid_date' );
$visuals_source   = false !== $visuals_start && false !== $visuals_end
	? substr( $rendering_source, $visuals_start, $visuals_end - $visuals_start )
	: '';

wp_seed_events_visuals_case(
	'30 no direct meta Event Data SQL HTTP or write access',
	function () use ( $flyer, $visuals_source ) {
		wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer ) ) );

		foreach ( array( 'featured_image', 'get_post_meta(', 'get_post(', 'wp_seed_events_get_event_data(', 'wp_seed_events_get_event_media(', '$wpdb', 'SELECT ', 'wp_remote_', 'update_', 'delete_', 'add_post_meta(', 'echo ' ) as $forbidden ) {
			wp_seed_events_visuals_not_contains( $forbidden, $visuals_source, 'Forbidden data access in visuals renderer: ' . $forbidden );
		}

		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_event_data_calls'], 'Renderer reloaded Event Data.' );
		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_meta_calls'], 'Renderer read post meta.' );
	}
);

wp_seed_events_visuals_case(
	'31 no shortcode dependency',
	function () use ( $visuals_source ) {
		foreach ( array( 'do_shortcode(', 'shortcode_atts(', 'add_shortcode(' ) as $forbidden ) {
			wp_seed_events_visuals_not_contains( $forbidden, $visuals_source, 'Shortcode dependency found: ' . $forbidden );
		}
	}
);

wp_seed_events_visuals_case(
	'32 no builder-specific HTML or dependency',
	function () use ( $flyer, $visuals_source ) {
		$html = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer ) ) );

		foreach ( array( 'et_pb', 'divi', 'wp-block-', 'gutenberg', 'spectra', 'uagb', 'ifolders' ) as $forbidden ) {
			wp_seed_events_visuals_not_contains( $forbidden, strtolower( $html . $visuals_source ), 'Builder-specific dependency found: ' . $forbidden );
		}

		wp_seed_events_visuals_not_contains( ' id=', $html, 'Renderer emitted a fixed DOM ID.' );
	}
);

wp_seed_events_visuals_case(
	'33 deleted attachment leaves no empty figure',
	function () {
		$deleted = wp_seed_events_visuals_media( 99 );
		$html    = wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $deleted ) ) );

		wp_seed_events_visuals_assert( '' === $html, 'Deleted attachment produced an empty container.' );
		wp_seed_events_visuals_assert( 1 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'Deleted attachment was not checked through the native image API.' );
	}
);

wp_seed_events_visuals_case(
	'34 strict Event Data argument and all content disabled',
	function () use ( $flyer, $pdf ) {
		$invalid = wp_seed_events_render_public_event_visuals_section( 42 );
		$hidden  = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ), $pdf ),
			array(
				'show_flyer'    => false,
				'show_visuals'  => false,
				'show_document' => false,
			)
		);

		wp_seed_events_visuals_assert( '' === $invalid, 'Non-array Event Data was accepted.' );
		wp_seed_events_visuals_assert( '' === $hidden, 'Fully hidden media produced an empty section.' );
		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_event_data_calls'], 'Strict argument triggered an Event Data lookup.' );
	}
);

wp_seed_events_visuals_case(
	'35 title is plain text and escaped',
	function () use ( $flyer ) {
		$html = wp_seed_events_render_public_event_visuals_section(
			wp_seed_events_visuals_event( array( $flyer ) ),
			array( 'title' => '<script>alert(1)</script>Visuels & documents' )
		);

		wp_seed_events_visuals_not_contains( '<script>', $html, 'Title retained executable HTML.' );
		wp_seed_events_visuals_contains( 'alert(1)Visuels &amp; documents', $html, 'Title was not normalized and escaped.' );
	}
);

wp_seed_events_visuals_case(
	'36 attachment calls are bounded per rendered image',
	function () use ( $flyer, $visual2, $visual3, $pdf ) {
		wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer ) ) );
		wp_seed_events_visuals_assert( 1 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'One visual did not produce exactly one image call.' );

		wp_seed_events_visuals_reset_calls();
		wp_seed_events_render_public_event_visuals_section( wp_seed_events_visuals_event( array( $flyer, $visual2, $visual3 ), $pdf ) );
		wp_seed_events_visuals_assert( 3 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'Three visuals did not produce exactly three image calls.' );
		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_event_data_calls'], 'Performance case reloaded Event Data.' );
		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_meta_calls'], 'Performance case read post meta.' );
	}
);

wp_seed_events_visuals_case(
	'37 repeated and multi-event rendering is deterministic',
	function () use ( $flyer, $visual2, $visual3, $pdf ) {
		$event    = wp_seed_events_visuals_event( array( $flyer, $visual2, $visual3 ), $pdf );
		$expected = wp_seed_events_render_public_event_visuals_section( $event );

		wp_seed_events_visuals_reset_calls();

		for ( $index = 0; $index < 5; $index++ ) {
			$current = $event;
			$current['id'] = 100 + $index;
			$html = wp_seed_events_render_public_event_visuals_section( $current );

			wp_seed_events_visuals_assert( $expected === $html, 'Identical media produced builder- or context-dependent HTML.' );
		}

		wp_seed_events_visuals_assert( 15 === count( $GLOBALS['wp_seed_events_visuals_image_calls'] ), 'Multi-event rendering added unbounded image calls.' );
		wp_seed_events_visuals_assert( 0 === $GLOBALS['wp_seed_events_visuals_event_data_calls'], 'Multi-event rendering reloaded Event Data.' );
	}
);

echo 'Visuals renderer harness: ' . $GLOBALS['wp_seed_events_visuals_case_count'] . ' cases passed.' . PHP_EOL;
