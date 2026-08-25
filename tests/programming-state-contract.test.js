const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );

const root = path.resolve( __dirname, '..' );
const read = ( file ) => fs.readFileSync( path.join( root, file ), 'utf8' );
const programming = read( 'includes/public/programming.php' );
const occurrences = read( 'includes/public/occurrences.php' );
const projection = read( 'includes/admin/occurrence-projection.php' );
const rendering = read( 'includes/public/rendering.php' );
const eventData = read( 'includes/public/event-data.php' );
const registry = read( 'includes/public/data-registry.php' );
const collections = read( 'includes/public/collections.php' );
const plugin = read( 'wp-seed-events.php' );

let cases = 0;
const check = ( label, callback ) => {
  callback();
  cases += 1;
  process.stdout.write( `ok ${ cases } - ${ label }\n` );
};

check( 'canonical state has two stable values and three dedicated metas', () => {
  [ 'scheduled', 'to_schedule' ].forEach( ( value ) => assert.ok( programming.includes( `'${ value }'` ) ) );
  [
    '_wp_seed_event_programming_status',
    '_wp_seed_event_programming_text',
    '_wp_seed_event_programming_visible_until',
  ].forEach( ( key ) => assert.ok( programming.includes( key ) ) );
} );

check( 'legacy runtime defaults to scheduled without content migration', () => {
  assert.ok( programming.includes( "wp_seed_events_normalize_programming_status( $value, 'scheduled' )" ) );
} );

check( 'admin keeps programming controls in the existing dates metabox', () => {
  const start = plugin.indexOf( 'function wp_seed_events_render_occurrences_meta_box' );
  const end = plugin.indexOf( 'function wp_seed_events_save_occurrences', start );
  const metabox = plugin.slice( start, end );
  [ 'État de programmation', 'Texte de programmation', 'Visible jusqu’au' ].forEach( ( label ) =>
    assert.ok( metabox.includes( label ), `${ label } missing` ),
  );
  assert.ok( metabox.includes( 'data-wp-seed-scheduled-fields' ) );
  assert.ok( metabox.includes( 'data-wp-seed-programming-fields' ) );
} );

check( 'transition validation never deletes occurrences implicitly', () => {
  assert.ok( programming.includes( "return 'has_occurrences';" ) );
  assert.ok( programming.includes( 'Cet événement possède encore des dates programmées.' ) );
  assert.ok( ! programming.includes( "delete_post_meta( $event_id, '_wp_seed_event_occurrences'" ) );
} );

check( 'to-schedule is blocked from occurrences and projections', () => {
  assert.ok( occurrences.includes( 'wp_seed_events_event_is_to_schedule( $event_id )' ) );
  assert.ok( projection.includes( 'wp_seed_events_event_is_to_schedule( $event_id )' ) );
  assert.ok( projection.includes( 'return array();' ) );
} );

check( 'shared Dates renderer emits text without a synthetic title or date', () => {
  assert.ok( rendering.includes( "'to_schedule' === ( $event['programming_status'] ?? '' )" ) );
  assert.ok( rendering.includes( 'wp-seed-event-programming-text' ) );
  const branch = rendering.slice(
    rendering.indexOf( "if ( 'to_schedule'" ),
    rendering.indexOf( "if ( empty( $event['occurrences']", rendering.indexOf( "if ( 'to_schedule'" ) ),
  );
  assert.ok( ! branch.includes( '>À programmer<' ) );
  assert.ok( ! branch.includes( '<time' ) );
} );

check( 'Event Data and REST expose the canonical contract', () => {
  [ 'programming_status', 'programming_text', 'programming_visible_until' ].forEach( ( key ) => {
    assert.ok( eventData.includes( `'${ key }'` ) );
    assert.ok( programming.includes( `'${ key.replace( 'programming_', '' ) }'` ) );
  } );
  assert.ok( programming.includes( "'wp_seed_events_programming'" ) );
} );

check( 'Dynamic Data exposes status and text but not the technical cutoff', () => {
  assert.ok( registry.includes( "'programming_status'" ) );
  assert.ok( registry.includes( "'programming_text'" ) );
  const fields = registry.slice( registry.indexOf( 'function wp_seed_events_dynamic_data_fields' ), registry.indexOf( 'function wp_seed_events_dynamic_data_field_format' ) );
  assert.ok( ! fields.includes( "'programming_visible_until'" ) );
} );

check( 'collections support upcoming, to-schedule, past and expiration', () => {
  assert.ok( collections.includes( "array( 'upcoming', 'to_schedule', 'past', 'all' )" ) );
  assert.ok( collections.includes( "programming_cutoff_meta.meta_value >=" ) );
  assert.ok( collections.includes( 'COUNT(DISTINCT raw_occurrence_meta.meta_id) = 0' ) );
  assert.ok( collections.includes( 'event_posts.post_title ASC' ) );
  assert.ok( ! collections.includes( 'programming_cutoff_meta.meta_value ASC' ) );
} );

check( 'temporal collections rely on exact occurrence boundaries', () => {
  assert.ok( collections.includes( 'occurrence_projection.end_sort >=' ) );
  assert.ok( collections.includes( 'occurrence_projection.end_sort <' ) );
  assert.ok( occurrences.includes( "return 'undated';" ) );
  assert.ok( rendering.includes( "empty( $event['occurrences'] )" ) );
} );

process.stdout.write( `Programming state contract: ${ cases }/${ cases } PASS\n` );
