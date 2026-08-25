const assert = require( 'node:assert/strict' );
const fs = require( 'node:fs' );
const path = require( 'node:path' );
const vm = require( 'node:vm' );

const root = path.resolve( __dirname, '..' );
const builderSource = fs.readFileSync(
  path.join( root, 'includes/integrations/divi/event-results-condition/visual-builder.js' ),
  'utf8',
);
const phpSource = fs.readFileSync(
  path.join( root, 'includes/integrations/divi/event-results-condition.php' ),
  'utf8',
);

const filters = {};
const hooks = {
  addFilter: ( name, namespace, callback ) => {
    filters[ name ] = callback;
  },
};
const React = {
  createElement: ( type, props, ...children ) => {
    if ( typeof type === 'function' ) {
      return type( { ...( props || {} ), children } );
    }
    return { type, props: props || {}, children };
  },
};
const window = {
  vendor: { wp: { hooks }, React },
  WpSeedEventsDiviEventResultsConditionData: {
    eventTypes: [ { value: '7', label: 'Stage' } ],
    currentEventTypes: [
      { value: 'stage', label: 'Stage' },
      { value: 'atelier', label: 'Atelier' },
    ],
  },
};

vm.runInNewContext( builderSource, { window, Number, Array, Object } );

let cases = 0;
const check = ( label, callback ) => {
  callback();
  cases += 1;
  process.stdout.write( `ok ${ cases } - ${ label }\n` );
};
const walk = ( value, callback ) => {
  if ( Array.isArray( value ) ) {
    value.forEach( ( child ) => walk( child, callback ) );
    return;
  }
  if ( ! value || typeof value !== 'object' ) {
    return;
  }
  callback( value );
  walk( value.children, callback );
};
const findAll = ( tree, predicate ) => {
  const matches = [];
  walk( tree, ( node ) => {
    if ( predicate( node ) ) {
      matches.push( node );
    }
  } );
  return matches;
};

check( 'existing public condition remains registered under the stable ID', () => {
  const conditions = filters[ 'divi.fieldLibrary.conditionalDisplay.conditionsStore' ]( [] );
  assert.equal( conditions.length, 2 );
  assert.equal( conditions[ 0 ].name, 'wpSeedEventsHasResults' );
  assert.equal( conditions[ 1 ].name, 'wpSeedEventsCurrentEventHasType' );
} );

check( 'current-event type condition persists canonical keys with OR selection', () => {
  let item = filters[ 'divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit' ](
    null,
    'wpSeedEventsCurrentEventHasType',
    'condition-type',
    'OR',
  );
  const setItem = ( updater ) => {
    item = updater( item );
  };
  let tree = filters[ 'divi.fieldLibrary.conditionalDisplay.customSettingsComponent' ]( null, item, setItem );
  let checkboxes = findAll( tree, ( node ) => node.type === 'input' && node.props.type === 'checkbox' );
  assert.equal( checkboxes.length, 2 );
  checkboxes[ 0 ].props.onChange( { target: { checked: true } } );
  tree = filters[ 'divi.fieldLibrary.conditionalDisplay.customSettingsComponent' ]( null, item, setItem );
  checkboxes = findAll( tree, ( node ) => node.type === 'input' && node.props.type === 'checkbox' );
  checkboxes[ 1 ].props.onChange( { target: { checked: true } } );
  assert.deepEqual( Array.from( item.conditionSettings.eventTypes ), [ 'stage', 'atelier' ] );
} );

check( 'new conditions default to the historical at-least-one contract', () => {
  const item = filters[ 'divi.fieldLibrary.conditionalDisplay.initialCustomItemEdit' ](
    null,
    'wpSeedEventsHasResults',
    'condition-1',
    'OR',
  );
  assert.equal( item.conditionSettings.resultCountOperator, 'at_least' );
  assert.equal( item.conditionSettings.resultCount, 1 );
} );

check( 'Visual Builder mounts exactly, at-least and greater-than controls', () => {
  let item = {
    conditionName: 'wpSeedEventsHasResults',
    conditionSettings: { eventStatus: 'upcoming', eventTypes: [], eventPinned: 'all' },
  };
  const setItem = ( updater ) => {
    item = updater( item );
  };
  const tree = filters[ 'divi.fieldLibrary.conditionalDisplay.customSettingsComponent' ]( null, item, setItem );
  const selects = findAll( tree, ( node ) => node.type === 'select' );
  const operator = selects.find( ( select ) => {
    const values = findAll( select, ( node ) => node.type === 'option' ).map( ( option ) => option.props.value );
    return values.includes( 'equals' ) && values.includes( 'at_least' ) && values.includes( 'greater_than' );
  } );
  assert.ok( operator, 'Cardinality operator control is not mounted.' );
  const count = findAll( tree, ( node ) => node.type === 'input' && node.props.type === 'number' )[ 0 ];
  assert.ok( count, 'Cardinality value control is not mounted.' );
  assert.equal( operator.props.value, 'at_least' );
  assert.equal( count.props.value, 1 );

  operator.props.onChange( { target: { value: 'equals' } } );
  count.props.onChange( { target: { value: '2' } } );
  assert.equal( item.conditionSettings.resultCountOperator, 'equals' );
  assert.equal( item.conditionSettings.resultCount, 2 );
} );

check( 'PHP evaluator keeps the old default and exposes generic comparisons', () => {
  [
    "'resultCountOperator'] ?? 'at_least'",
    "'resultCount'] ?? 1",
    "array( 'equals', 'at_least', 'greater_than' )",
    'wp_seed_events_divi_event_results_condition_matches_count',
    'wp_seed_events_divi_apply_collection_query',
    'wp_seed_events_divi_current_event_type_condition_matches',
    'wp_seed_events_event_type_keys_for_event',
  ].forEach( ( token ) => assert.ok( phpSource.includes( token ), `Missing PHP contract: ${ token }` ) );
} );

check( 'builder adds no consumer-side frontend counting workaround', () => {
  [ 'MutationObserver', 'querySelector', 'DOMContentLoaded', 'wp.apiFetch' ].forEach( ( token ) => {
    assert.equal( builderSource.includes( token ), false, `Forbidden frontend workaround found: ${ token }` );
  } );
} );

process.stdout.write( `Divi event result cardinality contract: ${ cases }/${ cases } OK\n` );
