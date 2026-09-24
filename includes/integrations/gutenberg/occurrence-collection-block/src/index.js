import apiFetch from '@wordpress/api-fetch';
import {
  BlockContextProvider,
  InspectorControls,
  InnerBlocks,
  store as blockEditorStore,
  useBlockProps,
  useInnerBlocksProps,
} from '@wordpress/block-editor';
import { registerBlockBindingsSource, registerBlockType } from '@wordpress/blocks';
import {
  Notice,
  PanelBody,
  RangeControl,
  SelectControl,
  Spinner,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

const OCCURRENCE_SOURCE = 'wp-seed-events/occurrence-field';

const DEFAULT_TEMPLATE = [
  [
    'core/group',
    { className: 'wp-seed-events-occurrence-card' },
    [
      [
        'core/heading',
        {
          level: 3,
          metadata: {
            bindings: {
              content: {
                source: OCCURRENCE_SOURCE,
                args: { field: 'event_title' },
              },
            },
          },
        },
      ],
      [
        'core/paragraph',
        {
          metadata: {
            bindings: {
              content: {
                source: OCCURRENCE_SOURCE,
                args: { field: 'promotion_name' },
              },
            },
          },
        },
      ],
      [
        'core/paragraph',
        {
          metadata: {
            bindings: {
              content: {
                source: OCCURRENCE_SOURCE,
                args: { field: 'parcours_year_label' },
              },
            },
          },
        },
      ],
      [
        'core/paragraph',
        {
          metadata: {
            bindings: {
              content: {
                source: OCCURRENCE_SOURCE,
                args: { field: 'occurrence_start_date' },
              },
            },
          },
        },
      ],
    ],
  ],
];

function splitDateTime( value ) {
  const match = String( value || '' ).match( /^(\d{4}-\d{2}-\d{2})(?:[ T](\d{2}:\d{2}))?/ );

  return match ? { date: match[ 1 ], time: match[ 2 ] || '' } : { date: '', time: '' };
}

function formatDate( value ) {
  if ( ! value ) {
    return '';
  }

  const date = new Date( `${ value }T12:00:00` );

  return Number.isNaN( date.getTime() )
    ? value
    : new Intl.DateTimeFormat( undefined, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    } ).format( date );
}

function occurrenceValue( field, context ) {
  const item = context && context.item ? context.item : {};
  const promotion = item.promotion && typeof item.promotion === 'object' ? item.promotion : {};
  const start = splitDateTime( item.start );
  const end = splitDateTime( item.end );
  const values = {
    event_title: item.event_title || '',
    event_slug: item.event_slug || '',
    event_type: item.event_type || '',
    event_status: item.event_status || '',
    event_is_pinned: item.is_pinned ? '1' : '0',
    occurrence_uid: item.occurrence_uid || '',
    occurrence_start: item.start || '',
    occurrence_end: item.end || '',
    occurrence_start_date: formatDate( start.date ),
    occurrence_end_date: formatDate( end.date ),
    occurrence_start_time: start.time,
    occurrence_end_time: end.time,
    occurrence_is_cancelled: item.is_cancelled ? '1' : '0',
    promotion_id: item.promotion_id ? String( item.promotion_id ) : '',
    promotion_name: promotion.name || '',
    promotion_slug: promotion.slug || '',
    promotion_start_year: promotion.start_year ? String( promotion.start_year ) : '',
    promotion_status: promotion.status || '',
    parcours_year: item.parcours_year ? String( item.parcours_year ) : '',
    parcours_year_label: item.parcours_year_label || '',
  };

  return typeof values[ field ] === 'string' ? values[ field ] : '';
}

registerBlockBindingsSource( {
  name: OCCURRENCE_SOURCE,
  getValues( { bindings, context } ) {
    const occurrenceContext = context[ 'wpSeedEvents/occurrence' ];
    const values = {};

    for ( const [ attributeName, binding ] of Object.entries( bindings ) ) {
      const field = binding && binding.args ? binding.args.field : '';
      values[ attributeName ] = occurrenceValue( field, occurrenceContext );
    }

    return values;
  },
} );

function normalizeInteger( value, fallback = 0 ) {
  const parsed = Number.parseInt( value, 10 );

  return Number.isInteger( parsed ) && parsed >= 0 ? parsed : fallback;
}

function queryPath( attributes ) {
  const mode = attributes.mode === 'grouped' ? 'grouped' : 'flat';
  const params = new URLSearchParams();
  const values = {
    promotion: attributes.promotion,
    parcours_year: attributes.parcoursYear || '',
    event_id: attributes.eventId || '',
    type: attributes.eventType,
    status: attributes.status,
    pinned: attributes.pinned,
    include_cancelled: attributes.includeCancelled ? '1' : '0',
    from: attributes.from,
    to: attributes.to,
  };

  for ( const [ key, value ] of Object.entries( values ) ) {
    if ( value !== '' && value !== null && value !== undefined ) {
      params.set( key, String( value ) );
    }
  }

  if ( mode === 'grouped' ) {
    params.set( 'order', 'canonical_path' );
    params.set( 'limit', String( attributes.groupedLimit || 200 ) );
  } else {
    params.set( 'order', attributes.order || 'chronological' );
    params.set( 'page', String( attributes.page || 1 ) );
    params.set( 'per_page', String( attributes.perPage || 20 ) );
  }

  const endpoint = mode === 'grouped'
    ? '/wp-seed-events/v1/occurrences/grouped'
    : '/wp-seed-events/v1/occurrences';

  return `${ endpoint }?${ params.toString() }`;
}

function flattenGroupedItems( data ) {
  const items = [];

  for ( const promotion of data.promotions || [] ) {
    for ( const year of promotion.years || [] ) {
      for ( const theme of year.themes || [] ) {
        items.push( ...( theme.occurrences || [] ) );
      }
    }
  }

  return items;
}

function occurrenceContext( item, clientId, index ) {
  if ( ! item ) {
    return {};
  }

  return {
    event_id: Number( item.event_id ) || 0,
    occurrence_uid: item.occurrence_uid || '',
    collection_instance_id: clientId,
    promotion_id: Number( item.promotion_id ) || 0,
    parcours_year: Number( item.parcours_year ) || 0,
    current_item_index: index,
    item,
  };
}

function Edit( { attributes, setAttributes, clientId } ) {
  const blockProps = useBlockProps( {
    className: `wp-seed-events-occurrence-collection-editor wp-seed-events-occurrence-collection-editor--${ attributes.mode }`,
  } );
  const innerBlocksProps = useInnerBlocksProps(
    { className: 'wp-seed-events-occurrence-collection-editor__template' },
    { template: DEFAULT_TEMPLATE, templateLock: false },
  );
  const [ state, setState ] = useState( { status: 'loading', data: null, error: '' } );
  const path = useMemo( () => queryPath( attributes ), [ attributes ] );
  const duplicateInstanceId = useSelect( ( select ) => {
    if ( ! attributes.collectionInstanceId ) {
      return false;
    }

    const editor = select( blockEditorStore );
    const clientIds = typeof editor.getClientIdsWithDescendants === 'function'
      ? editor.getClientIdsWithDescendants()
      : [];
    const owner = clientIds.find( ( candidateId ) => (
      editor.getBlockName( candidateId ) === metadata.name
      && editor.getBlockAttributes( candidateId ).collectionInstanceId === attributes.collectionInstanceId
    ) );

    return Boolean( owner && owner !== clientId );
  }, [ attributes.collectionInstanceId, clientId ] );

  useEffect( () => {
    if ( ! attributes.collectionInstanceId || duplicateInstanceId ) {
      setAttributes( { collectionInstanceId: `occurrence-collection-${ clientId }` } );
    }
  }, [ attributes.collectionInstanceId, clientId, duplicateInstanceId, setAttributes ] );

  useEffect( () => {
    const controller = new AbortController();
    setState( { status: 'loading', data: null, error: '' } );

    const timer = window.setTimeout( () => {
      apiFetch( { path, signal: controller.signal } )
        .then( ( data ) => {
          if ( ! controller.signal.aborted ) {
            setState( { status: 'ready', data, error: '' } );
          }
        } )
        .catch( ( error ) => {
          if ( ! controller.signal.aborted ) {
            setState( {
              status: 'error',
              data: null,
              error: error && error.message ? error.message : __( 'Aperçu indisponible.', 'wp-seed-events' ),
            } );
          }
        } );
    }, 250 );

    return () => {
      window.clearTimeout( timer );
      controller.abort();
    };
  }, [ path ] );

  const items = state.data
    ? ( attributes.mode === 'grouped' ? flattenGroupedItems( state.data ) : state.data.items || [] )
    : [];
  const previewItems = items.slice( 0, 6 );
  const previewContext = occurrenceContext(
    previewItems[ 0 ],
    attributes.collectionInstanceId || `occurrence-collection-${ clientId }`,
    0,
  );
  const updateInteger = ( key, value ) => setAttributes( { [ key ]: normalizeInteger( value ) } );

  return (
    <div { ...blockProps }>
      <InspectorControls>
        <PanelBody title={ __( 'Collection d’occurrences', 'wp-seed-events' ) } initialOpen>
          <SelectControl
            label={ __( 'Présentation', 'wp-seed-events' ) }
            value={ attributes.mode }
            options={ [
              { label: __( 'Collection plate', 'wp-seed-events' ), value: 'flat' },
              { label: __( 'Promotion → année → thème → occurrence', 'wp-seed-events' ), value: 'grouped' },
            ] }
            onChange={ ( mode ) => setAttributes( { mode } ) }
          />
          <TextControl
            label={ __( 'Promotion (slug ou ID)', 'wp-seed-events' ) }
            value={ attributes.promotion }
            onChange={ ( promotion ) => setAttributes( { promotion } ) }
          />
          <SelectControl
            label={ __( 'Année du parcours', 'wp-seed-events' ) }
            value={ String( attributes.parcoursYear ) }
            options={ [
              { label: __( 'Toutes les années', 'wp-seed-events' ), value: '0' },
              { label: __( 'Année 1', 'wp-seed-events' ), value: '1' },
              { label: __( 'Année 2', 'wp-seed-events' ), value: '2' },
              { label: __( 'Année 3', 'wp-seed-events' ), value: '3' },
              { label: __( 'Année 4', 'wp-seed-events' ), value: '4' },
            ] }
            onChange={ ( value ) => updateInteger( 'parcoursYear', value ) }
          />
          <TextControl
            label={ __( 'ID de l’événement', 'wp-seed-events' ) }
            type="number"
            min={ 0 }
            value={ attributes.eventId || '' }
            onChange={ ( value ) => updateInteger( 'eventId', value ) }
          />
          <TextControl
            label={ __( 'Type d’événement', 'wp-seed-events' ) }
            value={ attributes.eventType }
            onChange={ ( eventType ) => setAttributes( { eventType } ) }
          />
          <SelectControl
            label={ __( 'Statut', 'wp-seed-events' ) }
            value={ attributes.status }
            options={ [
              { label: __( 'À venir', 'wp-seed-events' ), value: 'upcoming' },
              { label: __( 'Passées', 'wp-seed-events' ), value: 'past' },
              { label: __( 'Toutes', 'wp-seed-events' ), value: 'all' },
            ] }
            onChange={ ( status ) => setAttributes( { status } ) }
          />
          <SelectControl
            label={ __( 'Événements épinglés', 'wp-seed-events' ) }
            value={ attributes.pinned }
            options={ [
              { label: __( 'Tous', 'wp-seed-events' ), value: 'all' },
              { label: __( 'Épinglés uniquement', 'wp-seed-events' ), value: 'only' },
            ] }
            onChange={ ( pinned ) => setAttributes( { pinned } ) }
          />
          <ToggleControl
            label={ __( 'Inclure les occurrences annulées', 'wp-seed-events' ) }
            checked={ attributes.includeCancelled }
            onChange={ ( includeCancelled ) => setAttributes( { includeCancelled } ) }
          />
          <TextControl
            label={ __( 'À partir du (AAAA-MM-JJ)', 'wp-seed-events' ) }
            value={ attributes.from }
            onChange={ ( from ) => setAttributes( { from } ) }
          />
          <TextControl
            label={ __( 'Jusqu’au (AAAA-MM-JJ)', 'wp-seed-events' ) }
            value={ attributes.to }
            onChange={ ( to ) => setAttributes( { to } ) }
          />
          { attributes.mode === 'flat' ? (
            <>
              <SelectControl
                label={ __( 'Ordre', 'wp-seed-events' ) }
                value={ attributes.order }
                options={ [
                  { label: __( 'Prochaines en premier', 'wp-seed-events' ), value: 'upcoming' },
                  { label: __( 'Chronologique', 'wp-seed-events' ), value: 'chronological' },
                  { label: __( 'Chronologique inverse', 'wp-seed-events' ), value: 'chronological_desc' },
                ] }
                onChange={ ( order ) => setAttributes( { order } ) }
              />
              <RangeControl
                label={ __( 'Occurrences par page', 'wp-seed-events' ) }
                value={ attributes.perPage }
                min={ 1 }
                max={ 100 }
                onChange={ ( perPage ) => setAttributes( { perPage: perPage || 1 } ) }
              />
            </>
          ) : (
            <RangeControl
              label={ __( 'Limite globale', 'wp-seed-events' ) }
              value={ attributes.groupedLimit }
              min={ 1 }
              max={ 500 }
              onChange={ ( groupedLimit ) => setAttributes( { groupedLimit: groupedLimit || 1 } ) }
            />
          ) }
          <TextControl
            label={ __( 'Message si la collection est vide', 'wp-seed-events' ) }
            value={ attributes.emptyMessage }
            onChange={ ( emptyMessage ) => setAttributes( { emptyMessage } ) }
          />
        </PanelBody>
      </InspectorControls>

      <div className="wp-seed-events-occurrence-collection-editor__preview">
        <strong>{ __( 'Aperçu des données', 'wp-seed-events' ) }</strong>
        { state.status === 'loading' && <Spinner /> }
        { state.status === 'error' && <Notice status="error" isDismissible={ false }>{ state.error }</Notice> }
        { state.status === 'ready' && previewItems.length === 0 && (
          <p>{ attributes.emptyMessage }</p>
        ) }
        { previewItems.map( ( item, index ) => (
          <div className="wp-seed-events-occurrence-collection-editor__preview-item" key={ `${ item.event_id }:${ item.occurrence_uid }:${ index }` }>
            <strong>{ item.event_title }</strong>
            <span>{ item.promotion && item.promotion.name ? item.promotion.name : __( 'Sans Promotion', 'wp-seed-events' ) }</span>
            <span>{ item.parcours_year_label || '' }</span>
            <span>{ item.start || '' }</span>
          </div>
        ) ) }
        { items.length > previewItems.length && (
          <p>{ __( 'L’aperçu est limité à six occurrences. Le frontend rend la collection complète.', 'wp-seed-events' ) }</p>
        ) }
      </div>

      <div className="wp-seed-events-occurrence-collection-editor__template-label">
        <strong>{ __( 'Modèle d’une occurrence', 'wp-seed-events' ) }</strong>
        <p>{ __( 'Ce modèle unique est répété pour chaque occurrence sur le frontend.', 'wp-seed-events' ) }</p>
      </div>
      <BlockContextProvider value={ { 'wpSeedEvents/occurrence': previewContext } }>
        <div { ...innerBlocksProps } />
      </BlockContextProvider>
    </div>
  );
}

registerBlockType( metadata.name, {
  edit: Edit,
  save: () => <InnerBlocks.Content />,
} );
