<?php
/**
 * Standalone assertions for event media normalization and Event Data aliases.
 *
 * Run with: php tests/media-normalizer-harness.php
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'WP_SEED_EVENTS_SHORT_DESCRIPTION_META_KEY', '_wp_seed_event_short_description' );

$GLOBALS['wp_seed_events_media_posts']       = array();
$GLOBALS['wp_seed_events_media_mime_types']  = array();
$GLOBALS['wp_seed_events_media_urls']        = array();
$GLOBALS['wp_seed_events_media_metadata']    = array();
$GLOBALS['wp_seed_events_media_captions']    = array();
$GLOBALS['wp_seed_events_media_meta']        = array();
$GLOBALS['wp_seed_events_media_thumbnails']  = array();
$GLOBALS['wp_seed_events_media_places']      = array();
$GLOBALS['wp_seed_events_media_permalinks']  = array();
$GLOBALS['wp_seed_events_media_write_calls'] = array();
$GLOBALS['wp_seed_events_media_case_count']  = 0;

function absint( $value ) {
	return abs( (int) $value );
}

function get_post( $post_id ) {
	return $GLOBALS['wp_seed_events_media_posts'][ absint( $post_id ) ] ?? null;
}

function get_post_mime_type( $attachment_id ) {
	return $GLOBALS['wp_seed_events_media_mime_types'][ absint( $attachment_id ) ] ?? '';
}

function wp_get_attachment_url( $attachment_id ) {
	return $GLOBALS['wp_seed_events_media_urls'][ absint( $attachment_id ) ] ?? false;
}

function wp_get_attachment_metadata( $attachment_id ) {
	return $GLOBALS['wp_seed_events_media_metadata'][ absint( $attachment_id ) ] ?? false;
}

function wp_get_attachment_caption( $attachment_id ) {
	return $GLOBALS['wp_seed_events_media_captions'][ absint( $attachment_id ) ] ?? false;
}

function get_post_meta( $post_id, $key = '', $single = false ) {
	$value = $GLOBALS['wp_seed_events_media_meta'][ absint( $post_id ) ][ (string) $key ] ?? ( $single ? '' : array() );

	return $single ? $value : array( $value );
}

function get_post_thumbnail_id( $post_id ) {
	return $GLOBALS['wp_seed_events_media_thumbnails'][ absint( $post_id ) ] ?? 0;
}

function wp_parse_url( $url, $component = -1 ) {
	return parse_url( (string) $url, $component );
}

function esc_url_raw( $url, $protocols = null ) {
	$url   = trim( (string) $url );
	$parts = '' !== $url ? parse_url( $url ) : false;

	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) ) {
		return '';
	}

	$allowed = is_array( $protocols ) ? array_map( 'strtolower', $protocols ) : array( 'http', 'https' );

	return in_array( strtolower( (string) $parts['scheme'] ), $allowed, true ) ? $url : '';
}

function wp_basename( $path, $suffix = '' ) {
	return basename( str_replace( '\\', '/', (string) $path ), (string) $suffix );
}

function sanitize_file_name( $filename ) {
	$filename = preg_replace( '/[^A-Za-z0-9._-]+/', '-', (string) $filename );

	return trim( (string) $filename, '-.' );
}

function wp_strip_all_tags( $value, $remove_breaks = false ) {
	$value = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $value );
	$value = strip_tags( (string) $value );

	return $remove_breaks ? preg_replace( '/[\r\n\t ]+/', ' ', $value ) : $value;
}

function sanitize_text_field( $value ) {
	return trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $value, true ) ) );
}

function sanitize_textarea_field( $value ) {
	$value = wp_strip_all_tags( (string) $value );
	$value = str_replace( array( "\r\n", "\r" ), "\n", $value );

	return trim( $value );
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['wp_seed_events_media_write_calls'][] = array( 'update_post_meta', $post_id, $key, $value );
}

function add_post_meta( $post_id, $key, $value ) {
	$GLOBALS['wp_seed_events_media_write_calls'][] = array( 'add_post_meta', $post_id, $key, $value );
}

function delete_post_meta( $post_id, $key ) {
	$GLOBALS['wp_seed_events_media_write_calls'][] = array( 'delete_post_meta', $post_id, $key );
}

function set_post_thumbnail( $post_id, $attachment_id ) {
	$GLOBALS['wp_seed_events_media_write_calls'][] = array( 'set_post_thumbnail', $post_id, $attachment_id );
}

function delete_post_thumbnail( $post_id ) {
	$GLOBALS['wp_seed_events_media_write_calls'][] = array( 'delete_post_thumbnail', $post_id );
}

function wp_seed_events_get_event_occurrences( $event_id, $args = array() ) {
	return array();
}

function wp_seed_events_get_next_active_occurrence( $event_id ) {
	return array();
}

function wp_seed_events_get_last_active_occurrence( $event_id ) {
	return array();
}

function wp_seed_events_get_event_lifecycle( $event_id ) {
	return 'undated';
}

function wp_seed_events_event_type_labels_for_event( $event_id ) {
	return array( 'Atelier' );
}

function wp_seed_events_public_event_place_data( $event_id ) {
	return $GLOBALS['wp_seed_events_media_places'][ absint( $event_id ) ] ?? array();
}

function wp_seed_events_public_event_people_data( $event_id ) {
	return array();
}

function wp_seed_events_resolve_short_description( string $description, string $short_description = '', int $word_limit = 40 ): string {
	unset( $word_limit );
	return '' !== trim( $short_description ) ? $short_description : $description;
}

function get_the_title( $post_id ) {
	$post = get_post( $post_id );

	return $post ? (string) $post->post_title : '';
}

function get_permalink( $post_id ) {
	$post_id = absint( $post_id );

	return $GLOBALS['wp_seed_events_media_permalinks'][ $post_id ]
		?? 'https://example.test/events/event-' . $post_id . '/';
}

require dirname( __DIR__ ) . '/includes/public/media.php';
require dirname( __DIR__ ) . '/includes/public/event-data.php';

function wp_seed_events_media_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function wp_seed_events_media_assert_same( $expected, $actual, $message ) {
	wp_seed_events_media_assert(
		$expected === $actual,
		$message . ' Expected ' . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . '.'
	);
}

function wp_seed_events_media_case( $name, $callback ) {
	try {
		$callback();
		$GLOBALS['wp_seed_events_media_case_count']++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

function wp_seed_events_media_attachment( $attachment_id, $title, $mime_type, $url, $caption = '', $alt = '', $metadata = array() ) {
	$GLOBALS['wp_seed_events_media_posts'][ $attachment_id ] = (object) array(
		'ID'           => $attachment_id,
		'post_type'    => 'attachment',
		'post_status'  => 'inherit',
		'post_title'   => $title,
		'post_content' => '',
	);
	$GLOBALS['wp_seed_events_media_mime_types'][ $attachment_id ] = $mime_type;
	$GLOBALS['wp_seed_events_media_urls'][ $attachment_id ]       = $url;
	$GLOBALS['wp_seed_events_media_captions'][ $attachment_id ]   = $caption;
	$GLOBALS['wp_seed_events_media_metadata'][ $attachment_id ]   = $metadata;
	$GLOBALS['wp_seed_events_media_meta'][ $attachment_id ]       = array(
		'_wp_attachment_image_alt' => $alt,
	);
}

$GLOBALS['wp_seed_events_media_posts'][501] = (object) array(
	'ID'           => 501,
	'post_type'    => 'wp_seed_event',
	'post_status'  => 'publish',
	'post_title'   => 'Media contract event',
	'post_content' => 'Event description',
);

wp_seed_events_media_attachment(
	101,
	'Recto title',
	'image/jpeg',
	'https://example.test/wp-content/uploads/2026/07/recto.jpg',
	'Flyer <em>recto</em><script>alert(1)</script>',
	'Recto alt',
	array(
		'width'  => 1200,
		'height' => 800,
	)
);
wp_seed_events_media_attachment(
	102,
	'Verso title',
	'image/png',
	'https://example.test/wp-content/uploads/2026/07/verso%20final.png?version=2',
	'',
	'Verso alt',
	array(
		'width'  => 900,
		'height' => 1200,
	)
);
wp_seed_events_media_attachment(
	103,
	'Programme title',
	'application/pdf',
	'https://cdn.example.test/documents/programme-detaille.pdf',
	'Programme detaille',
	'',
	array()
);
wp_seed_events_media_attachment( 104, 'Missing URL', 'image/jpeg', false );
wp_seed_events_media_attachment(
	105,
	'Missing local file',
	'image/webp',
	'https://cdn.example.test/remote/missing-local-file.webp',
	'',
	'',
	false
);
wp_seed_events_media_attachment( 106, 'No public basename', 'image/jpeg', 'https://cdn.example.test/' );

$GLOBALS['wp_seed_events_media_thumbnails'][501] = 101;
$GLOBALS['wp_seed_events_media_places'][501]     = array(
	'id'      => 701,
	'name'    => 'Centre Test',
	'address' => '<strong>3 rue du Test</strong>',
	'link'    => 'https://example.test/place/',
	'details' => "Parking dans la cour.\nAccueil 15 minutes avant.",
);
$GLOBALS['wp_seed_events_media_meta'][501]       = array(
	'_wp_seed_event_illustration_ids' => array( 102, 101, 999, 102, 105, 104 ),
	'_wp_seed_event_flyer_pdf_id'     => 103,
);

wp_seed_events_media_case(
	'image caption and existing fields',
	function () {
		$media = wp_seed_events_get_media_object( 101, 'image/' );
		wp_seed_events_media_assert_same( 101, $media['id'], 'Image ID changed.' );
		wp_seed_events_media_assert_same( 'https://example.test/wp-content/uploads/2026/07/recto.jpg', $media['url'], 'Image URL changed.' );
		wp_seed_events_media_assert_same( 'image/jpeg', $media['mime_type'], 'Image MIME changed.' );
		wp_seed_events_media_assert_same( 'Recto title', $media['title'], 'Image title changed.' );
		wp_seed_events_media_assert_same( 'Recto alt', $media['alt'], 'Image alt changed.' );
		wp_seed_events_media_assert_same( 'Flyer recto', $media['caption'], 'Caption was not normalized to plain text.' );
		wp_seed_events_media_assert_same( 'recto.jpg', $media['filename'], 'Public filename is incorrect.' );
		wp_seed_events_media_assert_same( 1200, $media['width'], 'Image width changed.' );
		wp_seed_events_media_assert_same( 800, $media['height'], 'Image height changed.' );
		wp_seed_events_media_assert( false === strpos( $media['caption'], '<' ), 'Caption contains HTML.' );
	}
);

wp_seed_events_media_case(
	'image without caption and encoded public filename',
	function () {
		$media = wp_seed_events_get_media_object( 102, 'image/' );
		wp_seed_events_media_assert_same( '', $media['caption'], 'Missing caption must be an empty string.' );
		wp_seed_events_media_assert_same( 'verso-final.png', $media['filename'], 'Encoded filename was not safely normalized.' );
		wp_seed_events_media_assert_same( 900, $media['width'], 'Image width changed.' );
		wp_seed_events_media_assert_same( 1200, $media['height'], 'Image height changed.' );
	}
);

wp_seed_events_media_case(
	'PDF media object',
	function () {
		$media = wp_seed_events_get_media_object( 103, 'application/pdf' );
		wp_seed_events_media_assert_same( 'application/pdf', $media['mime_type'], 'PDF MIME changed.' );
		wp_seed_events_media_assert_same( 'Programme detaille', $media['caption'], 'PDF caption is missing.' );
		wp_seed_events_media_assert_same( 'programme-detaille.pdf', $media['filename'], 'PDF filename is incorrect.' );
		wp_seed_events_media_assert_same( null, $media['width'], 'PDF width must remain null.' );
		wp_seed_events_media_assert_same( null, $media['height'], 'PDF height must remain null.' );
	}
);

wp_seed_events_media_case(
	'invalid attachment and unavailable public URL',
	function () {
		wp_seed_events_media_assert_same( null, wp_seed_events_get_media_object( 999, 'image/' ), 'Invalid attachment must be filtered.' );
		wp_seed_events_media_assert_same( null, wp_seed_events_get_media_object( 104, 'image/' ), 'Attachment without public URL must be filtered.' );
		wp_seed_events_media_assert_same( null, wp_seed_events_get_media_object( 103, 'image/' ), 'Unexpected MIME must be filtered.' );
	}
);

wp_seed_events_media_case(
	'missing local file does not expose a server path',
	function () {
		$media = wp_seed_events_get_media_object( 105, 'image/' );
		wp_seed_events_media_assert_same( 'missing-local-file.webp', $media['filename'], 'Filename must come from the public URL.' );
		wp_seed_events_media_assert_same( null, $media['width'], 'Missing metadata width must remain null.' );
		wp_seed_events_media_assert_same( null, $media['height'], 'Missing metadata height must remain null.' );
		wp_seed_events_media_assert( false === strpos( $media['filename'], '/' ), 'Filename contains a path separator.' );
		wp_seed_events_media_assert( false === strpos( $media['filename'], '\\' ), 'Filename contains a Windows path separator.' );
	}
);

wp_seed_events_media_case(
	'filename is empty when no safe public basename exists',
	function () {
		$media = wp_seed_events_get_media_object( 106, 'image/' );
		wp_seed_events_media_assert_same( '', $media['filename'], 'Unavailable public basename must produce an empty filename.' );
	}
);

wp_seed_events_media_case(
	'normalization order and deduplication stay unchanged',
	function () {
		$media = wp_seed_events_get_event_media( 501 );
		wp_seed_events_media_assert_same( array( 101, 102, 105 ), array_column( $media['communication_visuals'], 'id' ), 'Visual order or deduplication changed.' );
		wp_seed_events_media_assert_same( 101, $media['featured_image']['id'], 'Featured image changed.' );
		wp_seed_events_media_assert_same( 101, $media['communication_visual']['id'], 'Communication visual changed.' );
		wp_seed_events_media_assert_same( array( 102, 105 ), array_column( $media['other_visuals'], 'id' ), 'Other visuals changed.' );
		wp_seed_events_media_assert_same( 103, $media['event_document']['id'], 'Event document changed.' );
	}
);

wp_seed_events_media_case(
	'Event Data exposes enriched media and preserves aliases',
	function () {
		$data = wp_seed_events_get_event_data( 501 );
		wp_seed_events_media_assert_same( 'Flyer recto', $data['featured_image']['caption'], 'Featured image caption did not reach Event Data.' );
		wp_seed_events_media_assert_same( 'recto.jpg', $data['communication_visual']['filename'], 'Communication filename did not reach Event Data.' );
		wp_seed_events_media_assert_same( 'programme-detaille.pdf', $data['event_document']['filename'], 'Document filename did not reach Event Data.' );
		wp_seed_events_media_assert_same( 'Event description', $data['excerpt'], 'Existing excerpt changed.' );
		wp_seed_events_media_assert_same( '3 rue du Test', $data['place_address'], 'Place address projection is incorrect.' );
		wp_seed_events_media_assert_same( "Parking dans la cour.\nAccueil 15 minutes avant.", $data['practical_info'], 'Practical information projection is incorrect.' );
		wp_seed_events_media_assert_same( 'programme-detaille.pdf', $data['event_document_filename'], 'Document filename projection is incorrect.' );
		wp_seed_events_media_assert( false === strpos( $data['place_address'], '<' ), 'Place address contains HTML.' );
		wp_seed_events_media_assert( false === strpos( $data['practical_info'], '<' ), 'Practical information contains HTML.' );
		wp_seed_events_media_assert( false === strpos( $data['event_document_filename'], '/' ), 'Document filename contains a path.' );
		wp_seed_events_media_assert_same( 101, $data['primary_image_id'], 'Primary image alias changed.' );
		wp_seed_events_media_assert_same( 101, $data['featured_image_id'], 'Featured image alias changed.' );
		wp_seed_events_media_assert_same( array( 101, 102, 105 ), $data['illustration_ids'], 'Illustration aliases changed.' );
		wp_seed_events_media_assert_same( 103, $data['flyer_pdf_id'], 'PDF alias changed.' );
		wp_seed_events_media_assert_same( 'https://example.test/events/event-501/', $data['url'], 'Canonical event URL differs.' );
		wp_seed_events_media_assert_same( 'https://example.test/place/', $data['place_url'], 'Place URL differs.' );
		wp_seed_events_media_assert_same( 'https://cdn.example.test/documents/programme-detaille.pdf', $data['event_document_url'], 'Document URL differs.' );
		foreach ( array( 'event_url', 'canonical_url', 'document_url', 'flyer_url' ) as $alias ) {
			wp_seed_events_media_assert( ! array_key_exists( $alias, $data ), 'Unexpected URL alias exposed: ' . $alias );
		}
	}
);

wp_seed_events_media_case(
	'Event Data text projections use empty fallbacks',
	function () {
		$GLOBALS['wp_seed_events_media_posts'][502] = (object) array(
			'ID'           => 502,
			'post_type'    => 'wp_seed_event',
			'post_status'  => 'publish',
			'post_title'   => 'Empty event',
			'post_content' => '',
		);
		$GLOBALS['wp_seed_events_media_meta'][502]   = array();
		$GLOBALS['wp_seed_events_media_places'][502] = array();

		$data = wp_seed_events_get_event_data( 502 );

		wp_seed_events_media_assert_same( '', $data['excerpt'], 'Missing excerpt must be empty.' );
		wp_seed_events_media_assert_same( '', $data['place_address'], 'Missing place address must be empty.' );
		wp_seed_events_media_assert_same( '', $data['practical_info'], 'Missing practical information must be empty.' );
		wp_seed_events_media_assert_same( '', $data['event_document_filename'], 'Missing document filename must be empty.' );
		wp_seed_events_media_assert_same( 'https://example.test/events/event-502/', $data['url'], 'Event URL fallback differs.' );
		wp_seed_events_media_assert_same( '', $data['place_url'], 'Missing place URL must be empty.' );
		wp_seed_events_media_assert_same( '', $data['event_document_url'], 'Missing document URL must be empty.' );
	}
);

wp_seed_events_media_case(
	'Event Data rejects unsafe, relative and non-PDF public URLs',
	function () {
		$GLOBALS['wp_seed_events_media_posts'][503] = (object) array(
			'ID'           => 503,
			'post_type'    => 'wp_seed_event',
			'post_status'  => 'publish',
			'post_title'   => 'Unsafe URL event',
			'post_content' => '',
		);
		$GLOBALS['wp_seed_events_media_permalinks'][503] = '/relative-event/';
		$GLOBALS['wp_seed_events_media_places'][503]     = array(
			'link' => 'javascript:alert(1)',
		);
		$GLOBALS['wp_seed_events_media_meta'][503]       = array(
			'_wp_seed_event_flyer_pdf_id' => 107,
		);
		wp_seed_events_media_attachment(
			107,
			'Unsafe document URL',
			'application/pdf',
			'file:///var/www/private/programme.pdf'
		);

		$data = wp_seed_events_get_event_data( 503 );
		wp_seed_events_media_assert_same( '', $data['url'], 'Relative event URL must be empty.' );
		wp_seed_events_media_assert_same( '', $data['place_url'], 'Unsafe place URL must be empty.' );
		wp_seed_events_media_assert_same( '', $data['event_document_url'], 'Local document URL must be empty.' );

		$GLOBALS['wp_seed_events_media_posts'][504] = (object) array(
			'ID'           => 504,
			'post_type'    => 'wp_seed_event',
			'post_status'  => 'publish',
			'post_title'   => 'Non PDF document',
			'post_content' => '',
		);
		$GLOBALS['wp_seed_events_media_meta'][504] = array(
			'_wp_seed_event_flyer_pdf_id' => 101,
		);
		wp_seed_events_media_assert_same( '', wp_seed_events_get_event_data( 504 )['event_document_url'], 'Non-PDF document URL must be empty.' );
	}
);

wp_seed_events_media_case(
	'normalizer remains read-only and Event Data does not read media metas',
	function () {
		wp_seed_events_get_event_data( 501 );
		wp_seed_events_media_assert_same( array(), $GLOBALS['wp_seed_events_media_write_calls'], 'Media reads triggered a write primitive.' );

		$media_source      = file_get_contents( dirname( __DIR__ ) . '/includes/public/media.php' );
		$event_data_source = file_get_contents( dirname( __DIR__ ) . '/includes/public/event-data.php' );

		foreach ( array( 'update_post_meta(', 'add_post_meta(', 'delete_post_meta(', 'set_post_thumbnail(', 'delete_post_thumbnail(', 'wp_update_post(', 'wp_insert_post(' ) as $primitive ) {
			wp_seed_events_media_assert( false === strpos( $media_source, $primitive ), 'Write primitive found in media normalizer: ' . $primitive );
		}

		foreach ( array( '_thumbnail_id', '_wp_seed_event_illustration_ids', '_wp_seed_event_flyer_pdf_id' ) as $meta_key ) {
			wp_seed_events_media_assert( false === strpos( $event_data_source, $meta_key ), 'Event Data reads a media meta directly: ' . $meta_key );
		}
	}
);

echo sprintf( '%d media test groups passed.%s', $GLOBALS['wp_seed_events_media_case_count'], PHP_EOL );
