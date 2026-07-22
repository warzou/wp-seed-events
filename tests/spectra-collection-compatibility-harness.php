<?php
/**
 * Static compatibility assertions for the future Spectra collection adapter.
 */

declare(strict_types=1);

$root        = dirname( __DIR__ );
$plugin      = file_get_contents( $root . '/wp-seed-events.php' );
$collections = file_get_contents( $root . '/includes/public/collections.php' );
$docs        = file_get_contents( $root . '/docs/SPECTRA-EVENT-COLLECTIONS.md' );
$cases       = 0;

function spectra_collection_case( $label, $condition ) {
	global $cases;
	$cases++;

	if ( ! $condition ) {
		throw new RuntimeException( $label );
	}

	echo 'ok ' . $cases . ' - ' . $label . PHP_EOL;
}

spectra_collection_case( 'no Spectra runtime file is required', false === strpos( $plugin, 'spectra' ) );
spectra_collection_case( 'no global Spectra query hook is registered', false === strpos( $plugin . $collections, 'spectra_loop_builder_main_query_args' ) );
spectra_collection_case( 'canonical builder bridge is available', false !== strpos( $collections, 'function wp_seed_events_apply_collection_to_query_args' ) );
spectra_collection_case( 'canonical query remains builder neutral', false === strpos( $collections, 'uagb' ) && false === strpos( $collections, 'spectra' ) );
spectra_collection_case( 'documentation records Free behavior', false !== strpos( $docs, 'Spectra Free' ) );
spectra_collection_case( 'documentation records Pro requirement', false !== strpos( $docs, 'Spectra Pro' ) );
spectra_collection_case( 'documentation names the official candidate hook', false !== strpos( $docs, 'spectra_loop_builder_main_query_args' ) );
spectra_collection_case( 'documentation requires a stable loop marker', false !== strpos( $docs, 'marqueur stable' ) );
spectra_collection_case( 'documentation requires editor and frontend validation', false !== strpos( $docs, 'apercu editeur' ) && false !== strpos( $docs, 'frontend' ) );
spectra_collection_case( 'documentation does not claim production compatibility', false !== strpos( $docs, 'installation et recette explicites requises' ) );

echo 'Spectra collection compatibility harness: ' . $cases . '/' . $cases . ' OK' . PHP_EOL;
