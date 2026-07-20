<?php
/** Static contract checks for public and admin PDF labels. */
declare(strict_types=1);

$root       = dirname( __DIR__ );
$main       = file_get_contents( $root . '/wp-seed-events.php' );
$rendering  = file_get_contents( $root . '/includes/public/rendering.php' );
$template   = file_get_contents( $root . '/templates/event-single.php' );
$event_data = file_get_contents( $root . '/includes/public/event-data.php' );
$media      = file_get_contents( $root . '/includes/public/media.php' );
$runtime    = $main . $rendering . $template;
$cases      = 0;

function pdf_labels_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function pdf_labels_case( $name, $callback ) {
	global $cases;
	try {
		$callback();
		$cases++;
		echo '[OK] ' . $name . PHP_EOL;
	} catch ( Throwable $error ) {
		fwrite( STDERR, '[KO] ' . $name . ': ' . $error->getMessage() . PHP_EOL );
		exit( 1 );
	}
}

foreach ( array( $main, $rendering, $template, $event_data, $media ) as $source ) {
	pdf_labels_assert( false !== $source, 'Unable to read a source file.' );
}

pdf_labels_case( '1 no obsolete public PDF label', function () use ( $runtime ) {
	pdf_labels_assert( false === strpos( $runtime, 'Télécharger le flyer' ), 'Obsolete public label remains.' );
} );
pdf_labels_case( '2 canonical frontend label', function () use ( $rendering, $template ) {
	pdf_labels_assert( 2 === substr_count( $rendering, 'Télécharger le document PDF' ), 'Renderer labels differ.' );
	pdf_labels_assert( 1 === substr_count( $template, 'Télécharger le document PDF' ), 'Template label differs.' );
} );
pdf_labels_case( '3 canonical admin label and help', function () use ( $main ) {
	pdf_labels_assert( false !== strpos( $main, '<h2 class="hndle">Document complémentaire</h2>' ), 'Admin heading differs.' );
	pdf_labels_assert( false !== strpos( $main, "'label' => 'Document complémentaire'," ), 'Admin field label differs.' );
	pdf_labels_assert( false !== strpos( $main, 'Ajoutez un document PDF complémentaire lié à l’événement.' ), 'Admin help differs.' );
} );
pdf_labels_case( '4 flyer recto image wording retained', function () use ( $main ) {
	pdf_labels_assert( substr_count( $main, 'Flyer recto' ) >= 3, 'Flyer recto image wording was removed.' );
	pdf_labels_assert( false !== strpos( $main, 'data-wp-seed-flyer-recto' ), 'Flyer recto image container changed.' );
} );
pdf_labels_case( '5 historical technical keys retained', function () use ( $main, $event_data, $media ) {
	foreach ( array( $main, $event_data, $media ) as $source ) {
		pdf_labels_assert( false !== strpos( $source, '_wp_seed_event_flyer_pdf_id' ) || false !== strpos( $source, 'flyer_pdf_id' ), 'Historical key missing.' );
	}
} );
pdf_labels_case( '6 media metas unchanged', function () use ( $main, $media ) {
	pdf_labels_assert( false !== strpos( $main, "'_wp_seed_event_flyer_pdf_id' => array(" ), 'Historical PDF meta missing.' );
	pdf_labels_assert( false !== strpos( $media, "get_post_meta( \$event_id, '_wp_seed_event_flyer_pdf_id', true )" ), 'Normalizer meta changed.' );
	pdf_labels_assert( false === strpos( $main . $media, '_wp_seed_event_document_id' ), 'New document meta introduced.' );
} );
pdf_labels_case( '7 function contracts retained', function () use ( $main, $rendering ) {
	foreach ( array( 'wp_seed_events_media_fields', 'wp_seed_events_render_media_document_panel', 'wp_seed_events_save_media' ) as $name ) {
		pdf_labels_assert( false !== strpos( $main, 'function ' . $name . '(' ), 'Function missing: ' . $name );
	}
	pdf_labels_assert( false !== strpos( $rendering, 'function wp_seed_events_public_event_field_value(' ), 'Public field function missing.' );
} );
pdf_labels_case( '8 shortcode compatibility retained', function () use ( $main, $rendering ) {
	pdf_labels_assert( false !== strpos( $main, "add_shortcode( 'wp_seed_event_field'" ), 'Field shortcode registration missing.' );
	pdf_labels_assert( false !== strpos( $main, "add_shortcode( 'wp_seed_event_visuals'" ), 'Visuals shortcode registration missing.' );
	pdf_labels_assert( false !== strpos( $rendering, "case 'flyer':" ), 'Historical flyer field missing.' );
} );
pdf_labels_case( '9 Event Data aliases retained', function () use ( $event_data ) {
	foreach ( array( "'event_document'", "'event_document_filename'", "'event_document_url'", "'flyer_pdf_id'" ) as $key ) {
		pdf_labels_assert( false !== strpos( $event_data, $key ), 'Event Data key missing: ' . $key );
	}
} );
pdf_labels_case( '10 no new storage contract', function () use ( $main, $event_data, $media ) {
	pdf_labels_assert( 1 === substr_count( $main, "'_wp_seed_event_flyer_pdf_id' => array(" ), 'Historical storage field count changed.' );
	foreach ( array( '_wp_seed_event_document_id', '_wp_seed_event_pdf_id', 'event_document_meta' ) as $token ) {
		pdf_labels_assert( false === strpos( $main . $event_data . $media, $token ), 'New storage token found: ' . $token );
	}
} );
pdf_labels_case( '11 media nonce retained', function () use ( $main ) {
	pdf_labels_assert( false !== strpos( $main, "wp_nonce_field( 'wp_seed_events_save_media', 'wp_seed_events_media_nonce' )" ), 'Media nonce changed.' );
	pdf_labels_assert( false !== strpos( $main, "wp_verify_nonce( \$nonce, 'wp_seed_events_save_media' )" ), 'Media nonce verification changed.' );
} );
pdf_labels_case( '12 persistence flow retained', function () use ( $main ) {
	pdf_labels_assert( false !== strpos( $main, "add_action( 'save_post_wp_seed_event', 'wp_seed_events_save_media' )" ), 'Media save hook changed.' );
	pdf_labels_assert( false !== strpos( $main, "if ( '1' === \$document_changed" ), 'Explicit document change guard missing.' );
} );
pdf_labels_case( '13 document URLs unchanged', function () use ( $rendering, $template ) {
	pdf_labels_assert( false !== strpos( $rendering, "wp_get_attachment_url( (int) \$event['flyer_pdf_id'] )" ), 'Field document URL lookup changed.' );
	pdf_labels_assert( false !== strpos( $template, "wp_get_attachment_url( (int) \$event['flyer_pdf_id'] )" ), 'Template document URL lookup changed.' );
} );
pdf_labels_case( '14 business HTML retained', function () use ( $rendering, $template ) {
	pdf_labels_assert( false !== strpos( $template, 'class="wp-seed-event-single__flyer"' ), 'Template wrapper changed.' );
	pdf_labels_assert( false !== strpos( $rendering, 'wp-seed-event-visuals__document-link' ), 'Renderer link class changed.' );
} );
pdf_labels_case( '15 accessible link semantics retained', function () use ( $main, $rendering, $template ) {
	pdf_labels_assert( false !== strpos( $main, 'Afficher ou masquer le document complémentaire' ), 'Screen-reader text differs.' );
	pdf_labels_assert( false !== strpos( $rendering, "wp_seed_events_public_url_link( \$url, 'Télécharger le document PDF' )" ), 'Public link helper changed.' );
	pdf_labels_assert( false !== strpos( $template, "wp_seed_events_public_url_link( \$flyer_url, 'Télécharger le document PDF' )" ), 'Template link helper changed.' );
} );
pdf_labels_case( '16 absent PDF remains guarded', function () use ( $rendering, $template ) {
	pdf_labels_assert( false !== strpos( $rendering, "if ( empty( \$event['flyer_pdf_id'] ) )" ), 'Field missing-PDF guard changed.' );
	pdf_labels_assert( false !== strpos( $template, "if ( ! empty( \$event['flyer_pdf_id'] ) )" ), 'Template missing-PDF guard changed.' );
} );
pdf_labels_case( '17 present PDF uses canonical label everywhere', function () use ( $runtime ) {
	pdf_labels_assert( 3 === substr_count( $runtime, 'Télécharger le document PDF' ), 'Canonical public label count differs.' );
} );
pdf_labels_case( '18 remove and re-add controls retained', function () use ( $main ) {
	foreach ( array( 'Choisir un document PDF', 'Remplacer le document', 'data-wp-seed-media-remove', 'data-wp-seed-document-changed' ) as $token ) {
		pdf_labels_assert( false !== strpos( $main, $token ), 'Document control missing: ' . $token );
	}
} );
pdf_labels_case( '19 no builder-specific PDF label duplication', function () use ( $runtime ) {
	foreach ( array( 'event-visuals-module', 'event-visuals-block' ) as $token ) {
		pdf_labels_assert( false === strpos( $runtime, $token . 'Télécharger le document PDF' ), 'Builder-specific label duplication found.' );
	}
} );
pdf_labels_case( '20 UTF-8 without BOM or mojibake', function () use ( $root ) {
	foreach ( array( 'wp-seed-events.php', 'includes/public/rendering.php', 'templates/event-single.php', 'tests/pdf-labels-guidance-harness.php' ) as $relative ) {
		$bytes = file_get_contents( $root . '/' . $relative );
		pdf_labels_assert( "ï»¿" !== substr( $bytes, 0, 3 ), 'BOM found in ' . $relative );
		pdf_labels_assert( 1 === preg_match( '//u', $bytes ), 'Invalid UTF-8 in ' . $relative );
		foreach ( array( 'c383', 'c382', 'c3a2e282ac' ) as $signature ) {
			pdf_labels_assert( false === strpos( $bytes, hex2bin( $signature ) ), 'Mojibake found in ' . $relative );
		}
	}
} );

echo '[OK] ' . $cases . '/20 PDF labels cases passed.' . PHP_EOL;
