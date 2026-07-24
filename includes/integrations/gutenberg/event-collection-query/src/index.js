import { InspectorControls } from '@wordpress/block-editor';
import { registerBlockVariation } from '@wordpress/blocks';
import { PanelBody, RangeControl, SelectControl } from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const NAMESPACE = 'wp-seed-events/event-collection';
const BUSINESS_ORDER = 'business_date';

const STATUS_OPTIONS = [
  { label: __( 'À venir', 'wp-seed-events' ), value: 'upcoming' },
  { label: __( 'Passés', 'wp-seed-events' ), value: 'past' },
  { label: __( 'Tous', 'wp-seed-events' ), value: 'all' },
];

const PINNED_OPTIONS = [
  { label: __( 'Tous', 'wp-seed-events' ), value: 'all' },
  { label: __( 'Épinglés uniquement', 'wp-seed-events' ), value: 'only' },
];

const ORDER_OPTIONS = [
  { label: __( 'Croissant', 'wp-seed-events' ), value: 'ASC' },
  { label: __( 'Décroissant', 'wp-seed-events' ), value: 'DESC' },
];

const ORDER_BY_OPTIONS = [
  { label: __( '1re date de l’événement', 'wp-seed-events' ), value: BUSINESS_ORDER },
];

const DEFAULT_QUERY = {
  perPage: 6,
  pages: 0,
  offset: 0,
  postType: 'wp_seed_event',
  order: 'asc',
  orderBy: 'date',
  author: '',
  search: '',
  exclude: [],
  sticky: '',
  inherit: false,
  wpSeedEventsCollection: true,
  wpSeedEventsType: '',
  wpSeedEventsStatus: 'upcoming',
  wpSeedEventsPinned: 'all',
  wpSeedEventsOrder: 'ASC',
  wpSeedEventsOrderBy: BUSINESS_ORDER,
};

registerBlockVariation( 'core/query', {
  name: NAMESPACE,
  title: __( 'WP Seed Events — Collection d’événements', 'wp-seed-events' ),
  description: __(
    'Composez librement une carte répétée à partir de la sélection métier WP Seed Events.',
    'wp-seed-events',
  ),
  icon: 'calendar-alt',
  category: 'widgets',
  attributes: {
    namespace: NAMESPACE,
    query: DEFAULT_QUERY,
  },
  allowedControls: [],
  scope: [ 'inserter' ],
  isActive: ( blockAttributes ) => blockAttributes.namespace === NAMESPACE,
} );

function selectValue( options, value, fallback ) {
  return options.some( ( option ) => option.value === value ) ? value : fallback;
}

function eventTypeOptions() {
  const settings = window.wpSeedEventsCollectionQuerySettings;

  if ( ! settings || ! Array.isArray( settings.eventTypes ) ) {
    return [ { label: __( 'Tous les types', 'wp-seed-events' ), value: '' } ];
  }

  return settings.eventTypes.filter(
    ( option ) => option && typeof option.label === 'string' && typeof option.value === 'string',
  );
}

const withEventCollectionControls = createHigherOrderComponent(
  ( BlockEdit ) => ( props ) => {
    if ( props.name !== 'core/query' || props.attributes.namespace !== NAMESPACE ) {
      return <BlockEdit { ...props } />;
    }

    const query = { ...DEFAULT_QUERY, ...( props.attributes.query || {} ) };
    const typeOptions = eventTypeOptions();
    const type = selectValue( typeOptions, query.wpSeedEventsType, '' );
    const status = selectValue( STATUS_OPTIONS, query.wpSeedEventsStatus, 'upcoming' );
    const pinned = selectValue( PINNED_OPTIONS, query.wpSeedEventsPinned, 'all' );
    const order = selectValue( ORDER_OPTIONS, query.wpSeedEventsOrder, 'ASC' );
    const perPage = Math.min( 100, Math.max( 1, Number.parseInt( query.perPage, 10 ) || 6 ) );

    const updateQuery = ( values ) => {
      props.setAttributes( {
        query: {
          ...query,
          ...values,
          postType: 'wp_seed_event',
          inherit: false,
          wpSeedEventsCollection: true,
          wpSeedEventsOrderBy: BUSINESS_ORDER,
        },
      } );
    };

    return (
      <Fragment>
        <BlockEdit { ...props } />
        <InspectorControls>
          <PanelBody title={ __( 'Collection WP Seed Events', 'wp-seed-events' ) } initialOpen>
            <SelectControl
              label={ __( 'Type d’événement', 'wp-seed-events' ) }
              value={ type }
              options={ typeOptions }
              onChange={ ( value ) => updateQuery( { wpSeedEventsType: value } ) }
            />
            <SelectControl
              label={ __( 'Statut', 'wp-seed-events' ) }
              value={ status }
              options={ STATUS_OPTIONS }
              onChange={ ( value ) => updateQuery( { wpSeedEventsStatus: value } ) }
            />
            <SelectControl
              label={ __( 'Événements épinglés', 'wp-seed-events' ) }
              value={ pinned }
              options={ PINNED_OPTIONS }
              onChange={ ( value ) => updateQuery( { wpSeedEventsPinned: value } ) }
            />
            <SelectControl
              label={ __( 'Trier par', 'wp-seed-events' ) }
              value={ BUSINESS_ORDER }
              options={ ORDER_BY_OPTIONS }
              disabled
            />
            <SelectControl
              label={ __( 'Ordre', 'wp-seed-events' ) }
              value={ order }
              options={ ORDER_OPTIONS }
              onChange={ ( value ) =>
                updateQuery( { wpSeedEventsOrder: value, order: value.toLowerCase() } )
              }
            />
            <RangeControl
              label={ __( 'Éléments par page', 'wp-seed-events' ) }
              value={ perPage }
              min={ 1 }
              max={ 100 }
              onChange={ ( value ) => updateQuery( { perPage: value || 1 } ) }
            />
            <p>
              <strong>{ __( 'Présentation de la carte', 'wp-seed-events' ) }</strong>
              <br />
              { __(
                'La carte compacte ou détaillée est un point de départ modifiable librement. Pour réutiliser la même structure, enregistrez la collection comme composition WordPress. Utilisez une composition synchronisée seulement si sa structure et ses réglages doivent rester identiques.',
                'wp-seed-events',
              ) }
            </p>
          </PanelBody>
        </InspectorControls>
      </Fragment>
    );
  },
  'withEventCollectionControls',
);

addFilter(
  'editor.BlockEdit',
  'wp-seed-events/event-collection-controls',
  withEventCollectionControls,
);
