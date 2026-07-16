import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
  Notice,
  PanelBody,
  Placeholder,
  SelectControl,
  Spinner,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';

const PREVIEW_PATH = '/wp-seed-events/v1/gutenberg-event-dates-preview';
const PREVIEW_DELAY = 250;

const HEADING_LEVEL_OPTIONS = [
  { label: 'h2', value: 'h2' },
  { label: 'h3', value: 'h3' },
  { label: 'h4', value: 'h4' },
  { label: 'h5', value: 'h5' },
  { label: 'h6', value: 'h6' },
];

const SCOPE_OPTIONS = [
  { label: __( 'Toutes les dates', 'wp-seed-events' ), value: 'all' },
  { label: __( 'À venir', 'wp-seed-events' ), value: 'upcoming' },
  { label: __( 'Passées', 'wp-seed-events' ), value: 'past' },
];

function validOption( options, value, fallback ) {
  return options.some( ( option ) => option.value === value ) ? value : fallback;
}

function booleanOption( value ) {
  return typeof value === 'boolean' ? value : true;
}

function previewContext( context ) {
  const value = context && typeof context === 'object' ? context : {};
  const result = {};
  const postId = Number.parseInt( value.postId, 10 );

  if ( Number.isInteger( postId ) && postId > 0 ) {
    result.postId = postId;
  }

  if ( typeof value.postType === 'string' && value.postType ) {
    result.postType = value.postType;
  }

  if ( value.queryId !== undefined && value.queryId !== null ) {
    const queryId = Number.parseInt( value.queryId, 10 );
    result.queryId = Number.isInteger( queryId ) && queryId >= 0 ? queryId : 0;
  }

  return result;
}

function Preview( { state } ) {
  const label = __( 'WP Seed — Dates de l’événement', 'wp-seed-events' );

  if ( state.status === 'loading' ) {
    return (
      <Placeholder icon="calendar-alt" label={ label }>
        <Spinner />
        <span>{ __( 'Chargement de l’aperçu…', 'wp-seed-events' ) }</span>
      </Placeholder>
    );
  }

  if ( state.status === 'error' ) {
    return (
      <Placeholder icon="calendar-alt" label={ label }>
        <Notice status="error" isDismissible={ false }>
          { __( 'Impossible de charger l’aperçu des dates.', 'wp-seed-events' ) }
        </Notice>
      </Placeholder>
    );
  }

  if ( state.status === 'empty' ) {
    return (
      <Placeholder
        icon="calendar-alt"
        label={ label }
        instructions={
          state.message || __( 'Aucune date à afficher dans ce contexte.', 'wp-seed-events' )
        }
      />
    );
  }

  return <RawHTML>{ state.html }</RawHTML>;
}

function Edit( { attributes, setAttributes, context = {} } ) {
  const title = typeof attributes.title === 'string' ? attributes.title : 'Dates';
  const headingLevel = validOption(
    HEADING_LEVEL_OPTIONS,
    attributes.heading_level,
    'h2',
  );
  const scope = validOption( SCOPE_OPTIONS, attributes.scope, 'all' );
  const showCancelled = booleanOption( attributes.show_cancelled );
  const showTimes = booleanOption( attributes.show_times );
  const showCalendarLinks = booleanOption( attributes.show_calendar_links );
  const [ preview, setPreview ] = useState( {
    status: 'loading',
    html: '',
    message: '',
  } );
  const requestSequence = useRef( 0 );
  const blockProps = useBlockProps( {
    'aria-busy': preview.status === 'loading',
    'aria-live': 'polite',
  } );
  const contextPostId = context.postId;
  const contextPostType = context.postType;
  const contextQueryId = context.queryId;

  useEffect( () => {
    const requestId = requestSequence.current + 1;
    requestSequence.current = requestId;
    let active = true;
    let controller = null;

    setPreview( { status: 'loading', html: '', message: '' } );

    const timer = window.setTimeout( () => {
      controller = typeof AbortController === 'undefined' ? null : new AbortController();
      const request = {
        path: PREVIEW_PATH,
        method: 'POST',
        data: {
          attributes: {
            title,
            heading_level: headingLevel,
            scope,
            show_cancelled: showCancelled,
            show_times: showTimes,
            show_calendar_links: showCalendarLinks,
          },
          context: previewContext( {
            postId: contextPostId,
            postType: contextPostType,
            queryId: contextQueryId,
          } ),
        },
      };

      if ( controller ) {
        request.signal = controller.signal;
      }

      apiFetch( request )
        .then( ( response ) => {
          if ( ! active || requestId !== requestSequence.current ) {
            return;
          }

          const html = typeof response?.html === 'string' ? response.html : '';
          const empty = response?.empty === true || html.trim() === '';

          setPreview( {
            status: empty ? 'empty' : 'ready',
            html: empty ? '' : html,
            message: typeof response?.message === 'string' ? response.message : '',
          } );
        } )
        .catch( ( error ) => {
          if (
            ! active ||
            requestId !== requestSequence.current ||
            error?.name === 'AbortError'
          ) {
            return;
          }

          setPreview( { status: 'error', html: '', message: '' } );
        } );
    }, PREVIEW_DELAY );

    return () => {
      active = false;
      window.clearTimeout( timer );

      if ( controller ) {
        controller.abort();
      }
    };
  }, [
    title,
    headingLevel,
    scope,
    showCancelled,
    showTimes,
    showCalendarLinks,
    contextPostId,
    contextPostType,
    contextQueryId,
  ] );

  return (
    <>
      <InspectorControls>
        <PanelBody title={ __( 'Réglages des dates', 'wp-seed-events' ) } initialOpen>
          <TextControl
            label={ __( 'Titre', 'wp-seed-events' ) }
            value={ title }
            onChange={ ( value ) => setAttributes( { title: value } ) }
          />
          <SelectControl
            label={ __( 'Niveau du titre', 'wp-seed-events' ) }
            value={ headingLevel }
            options={ HEADING_LEVEL_OPTIONS }
            onChange={ ( value ) => setAttributes( { heading_level: value } ) }
          />
          <SelectControl
            label={ __( 'Portée', 'wp-seed-events' ) }
            value={ scope }
            options={ SCOPE_OPTIONS }
            onChange={ ( value ) => setAttributes( { scope: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les occurrences annulées', 'wp-seed-events' ) }
            checked={ showCancelled }
            onChange={ ( value ) => setAttributes( { show_cancelled: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les horaires', 'wp-seed-events' ) }
            checked={ showTimes }
            onChange={ ( value ) => setAttributes( { show_times: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les liens calendrier', 'wp-seed-events' ) }
            checked={ showCalendarLinks }
            onChange={ ( value ) => setAttributes( { show_calendar_links: value } ) }
          />
        </PanelBody>
      </InspectorControls>
      <div { ...blockProps }>
        <Preview state={ preview } />
      </div>
    </>
  );
}

registerBlockType( metadata.name, {
  ...metadata,
  edit: Edit,
  save: () => null,
} );
