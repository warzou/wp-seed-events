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

const PREVIEW_PATH = '/wp-seed-events/v1/gutenberg-event-visuals-preview';
const PREVIEW_DELAY = 250;

const HEADING_LEVEL_OPTIONS = [
  { label: 'h2', value: 'h2' },
  { label: 'h3', value: 'h3' },
  { label: 'h4', value: 'h4' },
  { label: 'h5', value: 'h5' },
  { label: 'h6', value: 'h6' },
];

const IMAGE_SIZE_OPTIONS = [
  { label: __( 'Miniature', 'wp-seed-events' ), value: 'thumbnail' },
  { label: __( 'Moyenne', 'wp-seed-events' ), value: 'medium' },
  { label: __( 'Moyenne large', 'wp-seed-events' ), value: 'medium_large' },
  { label: __( 'Grande', 'wp-seed-events' ), value: 'large' },
];

const LAYOUT_OPTIONS = [
  { label: __( 'Grille', 'wp-seed-events' ), value: 'grid' },
  { label: __( 'Liste', 'wp-seed-events' ), value: 'list' },
];

function validOption( options, value, fallback ) {
  return options.some( ( option ) => option.value === value ) ? value : fallback;
}

function booleanOption( value, fallback ) {
  return typeof value === 'boolean' ? value : fallback;
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
  const label = __( 'WP Seed — Visuels de communication', 'wp-seed-events' );

  if ( state.status === 'loading' ) {
    return (
      <Placeholder icon="format-gallery" label={ label }>
        <Spinner />
        <span>{ __( 'Chargement de l’aperçu…', 'wp-seed-events' ) }</span>
      </Placeholder>
    );
  }

  if ( state.status === 'error' ) {
    return (
      <Placeholder icon="format-gallery" label={ label }>
        <Notice status="error" isDismissible={ false }>
          { __( 'Impossible de charger l’aperçu des visuels.', 'wp-seed-events' ) }
        </Notice>
      </Placeholder>
    );
  }

  if ( state.status === 'empty' ) {
    return (
      <Placeholder
        icon="format-gallery"
        label={ label }
        instructions={
          state.message || __( 'Aucun visuel à afficher dans ce contexte.', 'wp-seed-events' )
        }
      />
    );
  }

  return <RawHTML>{ state.html }</RawHTML>;
}

function Edit( { attributes, setAttributes, context = {} } ) {
  const title =
    typeof attributes.title === 'string'
      ? attributes.title
      : 'Visuels de communication';
  const headingLevel = validOption(
    HEADING_LEVEL_OPTIONS,
    attributes.heading_level,
    'h2',
  );
  const showFlyer = booleanOption( attributes.show_flyer, true );
  const showVisuals = booleanOption( attributes.show_visuals, true );
  const showDocument = booleanOption( attributes.show_document, true );
  const showCaptions = booleanOption( attributes.show_captions, false );
  const imageSize = validOption(
    IMAGE_SIZE_OPTIONS,
    attributes.image_size,
    'large',
  );
  const linkOriginal = booleanOption( attributes.link_original, true );
  const layout = validOption( LAYOUT_OPTIONS, attributes.layout, 'grid' );
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
      controller =
        typeof AbortController === 'undefined' ? null : new AbortController();
      const request = {
        path: PREVIEW_PATH,
        method: 'POST',
        data: {
          attributes: {
            title,
            heading_level: headingLevel,
            show_flyer: showFlyer,
            show_visuals: showVisuals,
            show_document: showDocument,
            show_captions: showCaptions,
            image_size: imageSize,
            link_original: linkOriginal,
            layout,
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
    showFlyer,
    showVisuals,
    showDocument,
    showCaptions,
    imageSize,
    linkOriginal,
    layout,
    contextPostId,
    contextPostType,
    contextQueryId,
  ] );

  return (
    <>
      <InspectorControls>
        <PanelBody
          title={ __( 'Réglages des visuels', 'wp-seed-events' ) }
          initialOpen
        >
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
          <ToggleControl
            label={ __( 'Afficher le recto', 'wp-seed-events' ) }
            checked={ showFlyer }
            onChange={ ( value ) => setAttributes( { show_flyer: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les autres visuels', 'wp-seed-events' ) }
            checked={ showVisuals }
            onChange={ ( value ) => setAttributes( { show_visuals: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher le document', 'wp-seed-events' ) }
            checked={ showDocument }
            onChange={ ( value ) => setAttributes( { show_document: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les légendes', 'wp-seed-events' ) }
            checked={ showCaptions }
            onChange={ ( value ) => setAttributes( { show_captions: value } ) }
          />
          <SelectControl
            label={ __( 'Taille d’image', 'wp-seed-events' ) }
            value={ imageSize }
            options={ IMAGE_SIZE_OPTIONS }
            onChange={ ( value ) => setAttributes( { image_size: value } ) }
          />
          <ToggleControl
            label={ __( 'Lier vers le fichier original', 'wp-seed-events' ) }
            checked={ linkOriginal }
            onChange={ ( value ) => setAttributes( { link_original: value } ) }
          />
          <SelectControl
            label={ __( 'Disposition', 'wp-seed-events' ) }
            value={ layout }
            options={ LAYOUT_OPTIONS }
            onChange={ ( value ) => setAttributes( { layout: value } ) }
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
