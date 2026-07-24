const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );

const root = path.resolve( __dirname, '../../../../..' );
const source = fs.readFileSync( path.join( __dirname, '../src/index.js' ), 'utf8' );
const bootstrap = fs.readFileSync(
  path.join( root, 'includes/integrations/gutenberg/event-collection-query.php' ),
  'utf8',
);
const collections = fs.readFileSync(
  path.join( root, 'includes/public/collections.php' ),
  'utf8',
);
const patterns = fs.readFileSync(
  path.join( root, 'includes/integrations/gutenberg/event-collection-patterns.php' ),
  'utf8',
);

let cases = 0;

function check( label, callback ) {
  cases += 1;
  callback();
  process.stdout.write( `ok ${ cases } - ${ label }\n` );
}

check( 'variation targets Core Query once', () => {
  assert.equal( ( source.match( /registerBlockVariation\( 'core\/query'/g ) || [] ).length, 1 );
} );

check( 'namespace is stable and unique', () => {
  assert.ok( source.includes( "const NAMESPACE = 'wp-seed-events/event-collection'" ) );
  assert.ok( bootstrap.includes( "return 'wp-seed-events/event-collection';" ) );
} );

check( 'variation is locked to the event post type', () => {
  assert.ok( source.includes( "postType: 'wp_seed_event'" ) );
  assert.ok( bootstrap.includes( "'wp_seed_event' !== sanitize_key" ) );
} );

check( 'canonical business order has a user-facing label', () => {
  assert.ok( source.includes( '1re date de l’événement' ) );
  assert.ok( ! source.includes( 'Date métier' ) );
} );

check( 'all required controls are present', () => {
  [
    'Type d’événement',
    'Statut',
    'Événements épinglés',
    'Ordre',
    'Éléments par page',
  ].forEach( ( label ) => assert.ok( source.includes( label ), `${ label } missing` ) );
} );

check( 'status values are complete', () => {
  [ 'upcoming', 'past', 'all' ].forEach( ( value ) =>
    assert.ok( source.includes( `value: '${ value }'` ) ),
  );
} );

check( 'pinned values are complete', () => {
  assert.ok( source.includes( "wpSeedEventsPinned: 'all'" ) );
  assert.ok( source.includes( "value: 'only'" ) );
} );

check( 'order values are complete', () => {
  assert.ok( source.includes( "value: 'ASC'" ) );
  assert.ok( source.includes( "value: 'DESC'" ) );
} );

check( 'pagination uses the native perPage attribute', () => {
  assert.ok( source.includes( 'perPage: 6' ) );
  assert.ok( source.includes( 'updateQuery( { perPage: value || 1 } )' ) );
  assert.ok( patterns.includes( 'wp:query-pagination' ) );
} );

check( 'card composition remains editable blocks', () => {
  [
    'wp:post-template',
    'wp:post-title',
    'wp:post-excerpt',
    'wp:wp-seed-events/event-dates-block',
    'wp:wp-seed-events/event-visuals-block',
    'wp:wp-seed-events/event-people-block',
  ].forEach( ( block ) => assert.ok( patterns.includes( block ), `${ block } missing` ) );
} );

check( 'dynamic fields use the existing binding source', () => {
  assert.ok( patterns.includes( '"source":"wp-seed-events/event-field"' ) );
  [ 'type', 'status', 'place' ].forEach( ( field ) =>
    assert.ok( patterns.includes( `"field":"${ field }"` ) ),
  );
} );

check( 'bound Core blocks use canonical non-self-closing serialization', () => {
  assert.ok( ! /<!-- wp:paragraph .*\/-->/.test( patterns ) );
  assert.ok( ! patterns.includes( 'wp:read-more' ) );
  assert.equal( ( patterns.match( /<p><\/p>/g ) || [] ).length, 4 );
  assert.equal( ( patterns.match( /<p>Aucun événement à afficher\.<\/p>/g ) || [] ).length, 2 );
  assert.equal( ( patterns.match( /wp:button \{"metadata":\{"bindings":\{"url"/g ) || [] ).length, 2 );
  assert.equal( ( patterns.match( /wp-block-button__link wp-element-button/g ) || [] ).length, 2 );
  assert.equal( ( patterns.match( /"field":"url"/g ) || [] ).length, 2 );
} );

check( 'variation delegates the initial presentation to Query Loop patterns', () => {
  assert.ok( ! source.includes( 'innerBlocks:' ) );
  assert.ok( source.includes( 'Présentation de la carte' ) );
  assert.ok( source.includes( 'point de départ modifiable librement' ) );
} );

check( 'two official Query Loop patterns are registered', () => {
  assert.equal( ( patterns.match( /register_block_pattern_category\(/g ) || [] ).length, 1 );
  assert.equal( ( patterns.match( /register_block_pattern\(/g ) || [] ).length, 2 );
  assert.ok( patterns.includes( "'wp-seed-events/event-collection-compact'" ) );
  assert.ok( patterns.includes( "'wp-seed-events/event-collection-detailed'" ) );
  assert.equal( ( patterns.match( /'blockTypes'\s*=>\s*array\( 'core\/query' \)/g ) || [] ).length, 2 );
} );

check( 'patterns keep the canonical collection query contract', () => {
  [
    '"namespace":"wp-seed-events/event-collection"',
    '"postType":"wp_seed_event"',
    '"wpSeedEventsCollection":true',
    '"wpSeedEventsStatus":"upcoming"',
    '"wpSeedEventsPinned":"all"',
    '"wpSeedEventsOrder":"ASC"',
    '"wpSeedEventsOrderBy":"business_date"',
  ].forEach( ( token ) => assert.ok( patterns.includes( token ), `${ token } missing` ) );
} );

check( 'patterns have no external builder dependency', () => {
  [ 'spectra', 'uagb', 'divi', 'et_pb_', 'content-kit', 'shortcode' ].forEach( ( token ) =>
    assert.ok( ! patterns.toLowerCase().includes( token ), `${ token } found` ),
  );
} );

check( 'ordinary Query Loops are bypassed by namespace', () => {
  assert.ok( bootstrap.includes( 'wp_seed_events_gutenberg_collection_namespace() !==' ) );
  assert.ok( bootstrap.includes( 'return $query;' ) );
} );

check( 'frontend uses the official Query Loop hook', () => {
  assert.ok( bootstrap.includes( "add_filter( 'query_loop_block_query_vars'" ) );
} );

check( 'editor REST parameters are scoped to events', () => {
  assert.ok( bootstrap.includes( "add_filter( 'rest_wp_seed_event_collection_params'" ) );
  assert.ok( bootstrap.includes( "add_filter( 'rest_wp_seed_event_query'" ) );
  assert.ok( ! bootstrap.includes( "add_filter( 'rest_post_query'" ) );
} );

check( 'empty event type uses a scalar-only REST sanitizer', () => {
  assert.ok( bootstrap.includes( 'wp_seed_events_gutenberg_collection_rest_sanitize_slug' ) );
  assert.ok( bootstrap.includes( "return sanitize_title( (string) $value );" ) );
  assert.ok( ! bootstrap.includes( "'sanitize_callback' => 'sanitize_title'" ) );
} );

check( 'frontend and editor share one adapter', () => {
  assert.equal(
    ( bootstrap.match( /wp_seed_events_gutenberg_apply_collection_query\(/g ) || [] ).length,
    3,
  );
} );

check( 'adapter delegates selection to the canonical bridge', () => {
  assert.ok( bootstrap.includes( 'wp_seed_events_apply_collection_to_query_args' ) );
  assert.ok( collections.includes( 'wp_seed_events_query_event_collection( $collection_args )' ) );
} );

check( 'builder keeps pagination ownership', () => {
  assert.ok( ! bootstrap.includes( "unset( $query_args['offset'] );" ) );
  [ "['posts_per_page']", "['paged']" ].forEach( ( token ) =>
    assert.ok( ! bootstrap.includes( token ), `${ token } must remain builder-owned` ),
  );
} );

check( 'invalid marked state fails closed', () => {
  assert.ok( bootstrap.includes( "query_args['post__in']" ) );
  assert.ok( bootstrap.includes( 'array( 0 )' ) );
} );

check( 'no private event meta reaches Gutenberg', () => {
  [ 'get_post_meta(', "'_wp_seed_event_", '"_wp_seed_event_', '$wpdb', 'WP_Query(' ].forEach( ( token ) =>
    assert.ok( ! bootstrap.includes( token ), `${ token } found` ),
  );
} );

check( 'no shortcode or fixed event ID is used', () => {
  [ 'do_shortcode', '[wp_seed_event', '914', '1566' ].forEach( ( token ) => {
    assert.ok( ! source.includes( token ), `${ token } in source` );
    assert.ok( ! bootstrap.includes( token ), `${ token } in bootstrap` );
    assert.ok( ! patterns.includes( token ), `${ token } in patterns` );
  } );
} );

check( 'editor settings only expose public type labels and slugs', () => {
  assert.ok( bootstrap.includes( 'wp_seed_events_event_type_public_slug' ) );
  assert.ok( bootstrap.includes( 'wp_strip_all_tags' ) );
  assert.ok( bootstrap.includes( 'wp_json_encode' ) );
} );

process.stdout.write( `Gutenberg collection query contract: ${ cases }/${ cases } OK\n` );
