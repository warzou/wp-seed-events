<?php
/**
 * Gutenberg starter patterns for event collections.
 *
 * @package WPSeedEvents
 */

defined( 'ABSPATH' ) || exit;

function wp_seed_events_event_collection_query_attributes() {
	return '{"perPage":6,"pages":0,"offset":0,"postType":"wp_seed_event","order":"asc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false,"wpSeedEventsCollection":true,"wpSeedEventsType":"","wpSeedEventsStatus":"upcoming","wpSeedEventsPinned":"all","wpSeedEventsOrder":"ASC","wpSeedEventsOrderBy":"business_date"}';
}

function wp_seed_events_event_collection_compact_pattern_content() {
	$query = wp_seed_events_event_collection_query_attributes();

	return <<<HTML
<!-- wp:query {"namespace":"wp-seed-events/event-collection","query":{$query}} -->
<div class="wp-block-query">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
		<!-- wp:wp-seed-events/event-visuals-block {"title":"","show_visuals":false,"show_document":false,"layout":"list"} /-->
		<!-- wp:post-title {"isLink":true,"level":3} /-->
		<!-- wp:wp-seed-events/event-dates-block {"title":"","mode":"next","scope":"upcoming","show_cancelled":false,"show_times":false,"format":"long","show_calendar_links":false} /-->
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"place"}}}}} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"metadata":{"bindings":{"url":{"source":"wp-seed-events/event-field","args":{"field":"url"}}}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Voir l’événement</a></div>
		<!-- /wp:button --></div>
		<!-- /wp:buttons -->
	<!-- /wp:post-template -->
	<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"space-between"}} -->
		<!-- wp:query-pagination-previous /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->
	<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p>Aucun événement à afficher.</p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
HTML;
}

function wp_seed_events_event_collection_detailed_pattern_content() {
	$query = wp_seed_events_event_collection_query_attributes();

	return <<<HTML
<!-- wp:query {"namespace":"wp-seed-events/event-collection","query":{$query}} -->
<div class="wp-block-query">
	<!-- wp:post-template {"layout":{"type":"grid","columnCount":2}} -->
		<!-- wp:wp-seed-events/event-visuals-block {"title":"","show_visuals":false,"show_document":false} /-->
		<!-- wp:post-title {"isLink":true,"level":3} /-->
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"types"}}}}} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"status"}}}}} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"excerpt"}}}}} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- wp:wp-seed-events/event-dates-block {"title":"","mode":"all","scope":"upcoming","show_cancelled":false,"show_times":true,"format":"long","show_calendar_links":false} /-->
		<!-- wp:paragraph {"metadata":{"bindings":{"content":{"source":"wp-seed-events/event-field","args":{"field":"place"}}}}} -->
		<p></p>
		<!-- /wp:paragraph -->
		<!-- wp:wp-seed-events/event-people-block {"title":"","roles":["organizer","speaker"],"show_name":true,"show_roles":true,"show_email":true,"show_phone":true,"show_link":true,"link_phone":true,"link_email":true,"link_url":true} /-->
		<!-- wp:buttons -->
		<div class="wp-block-buttons"><!-- wp:button {"metadata":{"bindings":{"url":{"source":"wp-seed-events/event-field","args":{"field":"url"}}}}} -->
		<div class="wp-block-button"><a class="wp-block-button__link wp-element-button">Voir l’événement</a></div>
		<!-- /wp:button --></div>
		<!-- /wp:buttons -->
	<!-- /wp:post-template -->
	<!-- wp:query-pagination {"paginationArrow":"arrow","layout":{"type":"flex","justifyContent":"space-between"}} -->
		<!-- wp:query-pagination-previous /-->
		<!-- wp:query-pagination-numbers /-->
		<!-- wp:query-pagination-next /-->
	<!-- /wp:query-pagination -->
	<!-- wp:query-no-results -->
		<!-- wp:paragraph -->
		<p>Aucun événement à afficher.</p>
		<!-- /wp:paragraph -->
	<!-- /wp:query-no-results -->
</div>
<!-- /wp:query -->
HTML;
}

function wp_seed_events_register_event_collection_patterns() {
	if ( ! function_exists( 'register_block_pattern_category' ) || ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern_category(
		'wp-seed-events/collections',
		array(
			'label'       => __( 'WP Seed Events — Collections', 'wp-seed-events' ),
			'description' => __( 'Présentations de départ pour les collections d’événements.', 'wp-seed-events' ),
		)
	);

	register_block_pattern(
		'wp-seed-events/event-collection-compact',
		array(
			'title'         => __( 'WP Seed Events — Carte compacte', 'wp-seed-events' ),
			'description'   => __( 'Une carte sobre avec visuel, titre, dates, lieu et lien vers la fiche.', 'wp-seed-events' ),
			'categories'    => array( 'wp-seed-events/collections' ),
			'keywords'      => array( 'événement', 'collection', 'carte' ),
			'viewportWidth' => 1200,
			'blockTypes'    => array( 'core/query' ),
			'inserter'      => true,
			'source'        => 'plugin',
			'content'       => wp_seed_events_event_collection_compact_pattern_content(),
		)
	);

	register_block_pattern(
		'wp-seed-events/event-collection-detailed',
		array(
			'title'         => __( 'WP Seed Events — Carte détaillée', 'wp-seed-events' ),
			'description'   => __( 'Une carte complète avec visuel, titre, type, extrait, dates, lieu, personnes et lien.', 'wp-seed-events' ),
			'categories'    => array( 'wp-seed-events/collections' ),
			'keywords'      => array( 'événement', 'collection', 'carte' ),
			'viewportWidth' => 1200,
			'blockTypes'    => array( 'core/query' ),
			'inserter'      => true,
			'source'        => 'plugin',
			'content'       => wp_seed_events_event_collection_detailed_pattern_content(),
		)
	);
}
add_action( 'init', 'wp_seed_events_register_event_collection_patterns' );
