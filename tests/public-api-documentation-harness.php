<?php
/** Static assertions for the frozen public API documentation. */

declare(strict_types=1);

$root  = dirname( __DIR__ );
$cases = 0;

function public_docs_assert( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

function public_docs_case( $label, $callback ) {
	global $cases;
	$cases++;
	$callback();
	echo 'ok ' . $cases . ' - ' . $label . PHP_EOL;
}

function public_docs_read( $path ) {
	$contents = file_get_contents( $path );
	public_docs_assert( false !== $contents, 'Documentation file cannot be read: ' . $path );
	return $contents;
}

$event_data    = public_docs_read( $root . '/docs/EVENT-DATA-API.md' );
$promotions    = public_docs_read( $root . '/docs/PROMOTION-DOMAIN-API.md' );
$occurrences   = public_docs_read( $root . '/docs/EVENT-OCCURRENCES-API.md' );
$occurrence_collections = public_docs_read( $root . '/docs/OCCURRENCE-COLLECTIONS.md' );
$domain_model  = public_docs_read( $root . '/DOMAIN-MODEL.md' );
$projection    = public_docs_read( $root . '/docs/OCCURRENCE-PROJECTION-LIFECYCLE-V3.md' );
$compatibility = public_docs_read( $root . '/docs/PUBLIC-API-COMPATIBILITY.md' );
$updates       = public_docs_read( $root . '/docs/GITHUB-UPDATES.md' );
$readme        = public_docs_read( $root . '/README.md' );

public_docs_case( 'Event Data signature and empty result are definitive', function () use ( $event_data ) {
	public_docs_assert( false !== strpos( $event_data, 'wp_seed_events_get_event_data( $event_id )' ), 'Canonical Event Data signature is absent.' );
	public_docs_assert( false !== strpos( $event_data, 'array()' ), 'Invalid Event Data result is absent.' );
	public_docs_assert( false === stripos( $event_data, 'premier lot technique' ), 'Prospective implementation language remains.' );
} );

public_docs_case( 'Event Data complete top-level keys are documented', function () use ( $event_data ) {
	foreach ( array( 'slug', 'active_occurrences', 'display_occurrence', 'promotions', 'parcours_years', 'place_address', 'event_document_filename', 'communication_visuals', 'featured_image_id' ) as $key ) {
		public_docs_assert( false !== strpos( $event_data, '`' . $key . '`' ), 'Missing Event Data key: ' . $key );
	}
} );

public_docs_case( 'Occurrences arguments and normalized projections are documented', function () use ( $occurrences ) {
	foreach ( array( 'include_cancelled', 'only_active', 'status', 'derived_id', 'start_sort', 'is_date_future', 'is_cancelled', 'datetime_label' ) as $key ) {
		public_docs_assert( false !== strpos( $occurrences, '`' . $key . '`' ), 'Missing occurrence contract: ' . $key );
	}
} );
public_docs_case( 'Lifecycle v3 projection remains internal and rebuildable', function () use ( $projection, $occurrences ) {
	foreach ( array( '{$wpdb->prefix}wp_seed_event_occurrences', '_wp_seed_event_occurrences', '(event_id, occurrence_uid)', 'expiring atomic lock', 'idempotent retry', 'not a public API' ) as $contract ) {
		public_docs_assert( false !== strpos( $projection, $contract ), 'Missing lifecycle v3 contract: ' . $contract );
	}
	public_docs_assert( false !== strpos( $occurrences, 'projection lifecycle v3' ), 'Occurrences API does not state the lifecycle v3 boundary.' );
	public_docs_assert( false !== strpos( $projection, 'Public occurrence collections consume' ), 'Lifecycle consumer boundary is absent.' );
} );


public_docs_case( 'Promotion domain, PHP and REST contracts are documented', function () use ( $promotions ) {
	foreach ( array( 'wp_seed_promotion', 'wp_seed_events_get_promotion(', 'wp_seed_events_get_promotions(', 'promotion_id', 'parcours_year', '/wp-json/wp-seed-events/v1/promotions', '/events/<event_id>/occurrences', 'lifecycle v3' ) as $contract ) {
		public_docs_assert( false !== strpos( $promotions, $contract ), 'Missing Promotion contract: ' . $contract );
	}
	public_docs_assert( false !== strpos( $promotions, 'Aucun backfill ni' ), 'No-migration guarantee is absent.' );
	public_docs_assert( false !== strpos( $promotions, 'site consommateur de formation reste bloque' ), 'Consumer blocking state is absent.' );
} );


public_docs_case( 'Occurrence collections contract, REST and grouping are documented', function () use ( $occurrence_collections, $domain_model, $compatibility ) {
	foreach ( array( 'wp_seed_events_query_occurrence_collection(', 'wp_seed_events_query_grouped_occurrence_collection(', '/wp-json/wp-seed-events/v1/occurrences', 'canonical_path', 'per_page', 'include_cancelled', 'Promotion', 'parcours_year' ) as $contract ) {
		public_docs_assert( false !== strpos( $occurrence_collections, $contract ), 'Missing occurrence collection contract: ' . $contract );
	}
	public_docs_assert( false !== strpos( $domain_model, 'Promotion' ) && false !== strpos( $domain_model, 'theme/evenement' ), 'Domain path is absent.' );
	public_docs_assert( false !== strpos( $compatibility, 'wp_seed_events_query_grouped_occurrence_collection()' ), 'Compatibility list omits grouped collections.' );
} );
public_docs_case( 'Historical aliases and filtered contacts are explicit', function () use ( $compatibility ) {
	foreach ( array( 'wp_seed_events_public_event_data()', 'wp_seed_events_get_event_collection()', '`role`', '`details`', '`show_time`', '`wp_seed_events_next_date`', '`illustration_ids`', '`email`, `phone`, `link`' ) as $alias ) {
		public_docs_assert( false !== strpos( $compatibility, $alias ), 'Missing compatibility alias: ' . $alias );
	}
} );

public_docs_case( 'Public and internal boundaries are explicit', function () use ( $compatibility ) {
	public_docs_assert( false !== strpos( $compatibility, '## Frontiere publique' ), 'Public boundary is absent.' );
	foreach ( array( 'routes d\'apercu', 'options lifecycle', 'requetes SQL', 'transients' ) as $internal ) {
		public_docs_assert( false !== strpos( $compatibility, $internal ), 'Missing internal boundary: ' . $internal );
	}
} );

public_docs_case( 'Updater contract requires exact package and checksum assets', function () use ( $updates ) {
	public_docs_assert( false !== strpos( $updates, 'wp-seed-events-<version>.zip.sha256' ), 'Checksum asset contract is absent.' );
	public_docs_assert( false !== strpos( $updates, 'aucune mise a jour automatique' ), 'Manual update boundary is absent.' );
	public_docs_assert( false !== strpos( $updates, 'ZipArchive' ), 'ZIP validation requirement is absent.' );
} );

public_docs_case( 'README developer links are clickable and complete', function () use ( $readme ) {
	foreach ( array( 'docs/EVENT-DATA-API.md', 'docs/EVENT-OCCURRENCES-API.md', 'docs/OCCURRENCE-COLLECTIONS.md', 'DOMAIN-MODEL.md', 'docs/OCCURRENCE-PROJECTION-LIFECYCLE-V3.md', 'docs/PUBLIC-COLLECTIONS.md', 'docs/PUBLIC-EVENT-DYNAMIC-DATA.md', 'docs/PUBLIC-API-COMPATIBILITY.md' ) as $target ) {
		public_docs_assert( false !== strpos( $readme, '](' . $target . ')' ), 'README link is absent: ' . $target );
	}
} );

public_docs_case( 'Documentation files are UTF-8 without BOM', function () use ( $root ) {
	foreach ( array( 'README.md', 'docs/EVENT-DATA-API.md', 'docs/EVENT-OCCURRENCES-API.md', 'docs/OCCURRENCE-COLLECTIONS.md', 'DOMAIN-MODEL.md', 'docs/OCCURRENCE-PROJECTION-LIFECYCLE-V3.md', 'docs/PROMOTION-DOMAIN-API.md', 'docs/PUBLIC-API-COMPATIBILITY.md', 'docs/GITHUB-UPDATES.md' ) as $relative ) {
		$contents = public_docs_read( $root . '/' . $relative );
		public_docs_assert( 0 !== strncmp( $contents, "\xEF\xBB\xBF", 3 ), 'BOM found: ' . $relative );
		public_docs_assert( 1 === preg_match( '//u', $contents ), 'Invalid UTF-8: ' . $relative );
	}
} );

echo 'Public API documentation harness: ' . $cases . '/' . $cases . ' OK' . PHP_EOL;
