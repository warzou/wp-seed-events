<?php
/**
 * Runtime-free harness for Gutenberg event collection patterns.
 *
 * Run with: php tests/gutenberg-collection-patterns-harness.php
 */

$actions    = array();
$categories = array();
$patterns   = array();
$cases      = 0;

function add_action( $hook, $callback ) {
	global $actions;
	$actions[] = array( $hook, $callback );
}

function register_block_pattern_category( $slug, $properties ) {
	global $categories;
	$categories[ $slug ] = $properties;
	return true;
}

function register_block_pattern( $slug, $properties ) {
	global $patterns;
	$patterns[ $slug ] = $properties;
	return true;
}

function __( $text ) {
	return $text;
}

function patterns_assert( $condition, $message ) {
	global $cases;
	++$cases;

	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/integrations/gutenberg/event-collection-patterns.php';

patterns_assert( array( array( 'init', 'wp_seed_events_register_event_collection_patterns' ) ) === $actions, 'Registration hook differs.' );

wp_seed_events_register_event_collection_patterns();

patterns_assert( 1 === count( $categories ), 'Pattern category must be registered once.' );
patterns_assert( isset( $categories['wp-seed-events/collections'] ), 'Pattern category slug differs.' );
patterns_assert( 2 === count( $patterns ), 'Exactly two patterns must be registered.' );

$expected = array(
	'wp-seed-events/event-collection-compact',
	'wp-seed-events/event-collection-detailed',
);
patterns_assert( $expected === array_keys( $patterns ), 'Pattern slugs differ.' );

foreach ( $patterns as $slug => $pattern ) {
	patterns_assert( array( 'core/query' ) === $pattern['blockTypes'], $slug . ' is not associated with Core Query.' );
	patterns_assert( true === $pattern['inserter'], $slug . ' is hidden from the inserter.' );
	patterns_assert( 'plugin' === $pattern['source'], $slug . ' source differs.' );
	patterns_assert( false !== strpos( $pattern['content'], '<!-- wp:query ' ), $slug . ' has no Query block.' );
	patterns_assert( false !== strpos( $pattern['content'], '"namespace":"wp-seed-events/event-collection"' ), $slug . ' namespace differs.' );
	patterns_assert( false !== strpos( $pattern['content'], '"postType":"wp_seed_event"' ), $slug . ' post type differs.' );
	patterns_assert( false !== strpos( $pattern['content'], '"wpSeedEventsCollection":true' ), $slug . ' marker is absent.' );
	patterns_assert( false !== strpos( $pattern['content'], '<!-- wp:post-template ' ), $slug . ' has no Post Template.' );
	patterns_assert( false !== strpos( $pattern['content'], '<!-- wp:query-pagination ' ), $slug . ' has no pagination.' );
	patterns_assert( false === stripos( $pattern['content'], 'shortcode' ), $slug . ' contains a shortcode.' );
	patterns_assert( false === stripos( $pattern['content'], 'spectra' ), $slug . ' depends on Spectra.' );
	patterns_assert( false === stripos( $pattern['content'], 'uagb' ), $slug . ' depends on UAGB.' );
	patterns_assert( false === strpos( $pattern['content'], '914' ), $slug . ' contains a fixed event ID.' );
}

$compact  = $patterns['wp-seed-events/event-collection-compact']['content'];
$detailed = $patterns['wp-seed-events/event-collection-detailed']['content'];

foreach ( array( 'event-visuals-block', 'post-title', 'event-dates-block', '"field":"place"', 'wp:button', '"field":"url"' ) as $token ) {
	patterns_assert( false !== strpos( $compact, $token ), 'Compact pattern misses ' . $token . '.' );
}
patterns_assert( false === strpos( $compact, 'post-excerpt' ), 'Compact pattern must remain compact.' );
patterns_assert( false === strpos( $compact, 'event-people-block' ), 'Compact pattern must not include People.' );
patterns_assert( false !== strpos( $compact, '"show_times":false' ), 'Compact pattern must hide times.' );
patterns_assert( false === strpos( $compact, 'wp:read-more' ), 'Compact pattern must use canonical Core button markup.' );

foreach ( array( 'event-visuals-block', 'post-title', 'post-excerpt', 'event-dates-block', '"show_times":true', '"field":"place"', 'event-people-block', 'wp:button', '"field":"url"' ) as $token ) {
	patterns_assert( false !== strpos( $detailed, $token ), 'Detailed pattern misses ' . $token . '.' );
}
patterns_assert( false === strpos( $detailed, 'wp:read-more' ), 'Detailed pattern must use canonical Core button markup.' );
patterns_assert( 4 === substr_count( $compact . $detailed, '<p></p>' ), 'Bound paragraphs must use canonical Core markup.' );
patterns_assert( 2 === substr_count( $compact . $detailed, '<p>Aucun événement à afficher.</p>' ), 'No-results paragraphs must use canonical Core markup.' );
patterns_assert( 0 === preg_match_all( '/<!-- wp:paragraph .*\\/-->/', $compact . $detailed ), 'Core paragraphs must never be self-closing.' );

echo 'Gutenberg collection patterns harness: ' . $cases . '/' . $cases . ' OK' . PHP_EOL;
