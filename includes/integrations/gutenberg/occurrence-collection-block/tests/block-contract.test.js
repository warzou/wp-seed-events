const assert = require( 'assert' );
const fs = require( 'fs' );
const path = require( 'path' );

const root = path.resolve( __dirname, '..' );
const metadata = JSON.parse( fs.readFileSync( path.join( root, 'src', 'block.json' ), 'utf8' ) );
const source = fs.readFileSync( path.join( root, 'src', 'index.js' ), 'utf8' );
const bootstrap = fs.readFileSync(
  path.resolve( root, '..', 'occurrence-collection-block.php' ),
  'utf8',
);
const contextSource = fs.readFileSync(
  path.resolve( root, '..', '..', '..', 'public', 'occurrence-context.php' ),
  'utf8',
);

assert.strictEqual( metadata.name, 'wp-seed-events/occurrence-collection' );
assert.strictEqual( metadata.apiVersion, 3 );
assert.strictEqual( metadata.version, '0.2.0-beta.6' );
assert.strictEqual( metadata.attributes.mode.default, 'flat' );
assert.deepStrictEqual( metadata.attributes.mode.enum, [ 'flat', 'grouped' ] );
assert.strictEqual( metadata.attributes.perPage.default, 20 );
assert.strictEqual( metadata.attributes.groupedLimit.default, 200 );
assert.strictEqual( metadata.editorScript, 'file:./index.js' );
assert.ok( source.includes( 'wp-seed-events/occurrence-field' ) );
assert.ok( source.includes( 'BlockContextProvider' ) );
assert.ok( source.includes( 'useSelect' ) );
assert.ok( source.includes( 'getClientIdsWithDescendants' ) );
assert.ok( source.includes( 'collectionInstanceId' ) );
assert.ok( source.includes( 'useInnerBlocksProps' ) );
assert.ok( source.includes( 'InnerBlocks.Content' ) );
assert.ok( source.includes( '/wp-seed-events/v1/occurrences/grouped' ) );
assert.ok( source.includes( '/wp-seed-events/v1/occurrences' ) );
assert.ok( source.includes( 'controller.abort()' ) );
assert.ok( source.includes( '250' ) );
assert.ok( source.includes( 'previewItems.length' ) );
assert.ok( source.includes( 'Promotion → année → thème → occurrence' ) );
assert.ok( source.includes( 'Inclure les occurrences annulées' ) );
assert.ok( bootstrap.includes( 'wp_seed_events_query_occurrence_collection' ) );
assert.ok( bootstrap.includes( 'wp_seed_events_query_grouped_occurrence_collection' ) );
assert.ok( bootstrap.includes( 'wp_seed_events_with_occurrence_context' ) );
assert.ok( bootstrap.includes( "new WP_Block( $parsed_block, $available_context )" ) );
assert.ok( bootstrap.includes( "'wpSeedEvents/occurrence' => $context" ) );
assert.ok( contextSource.includes( 'finally' ) );
assert.ok( bootstrap.includes( 'get_post_meta' ) === false );
assert.ok( bootstrap.includes( '$wpdb' ) === false );
assert.ok( bootstrap.includes( 'register_block_type_from_metadata' ) );
assert.ok( bootstrap.includes( "'render_callback' => 'wp_seed_events_render_gutenberg_occurrence_collection_block'" ) );
assert.ok( bootstrap.includes( 'wpseed_occurrence_page_' ) );
assert.ok( bootstrap.includes( 'aria-label' ) );
assert.ok( bootstrap.includes( 'role="status"' ) );
assert.ok( bootstrap.includes( 'role="alert"' ) );

console.log( 'Gutenberg occurrence collection block contract: 38/38 OK' );
