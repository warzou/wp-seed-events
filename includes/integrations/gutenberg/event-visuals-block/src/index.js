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

const IMAGE_SIZE_OPTIONS = [
  { label: __( 'Miniature', 'wp-seed-events' ), value: 'thumbnail' },
  { label: __( 'Moyenne', 'wp-seed-events' ), value: 'medium' },
  { label: __( 'Moyenne large', 'wp-seed-events' ), value: 'medium_large' },
  { label: __( 'Grande', 'wp-seed-events' ), value: 'large' },
];

const LAYOUT_OPTIONS = [
  { label: __( 'Verticale', 'wp-seed-events' ), value: 'vertical' },
  { label: __( 'Horizontale', 'wp-seed-events' ), value: 'horizontal' },
  { label: __( 'Grille', 'wp-seed-events' ), value: 'grid' },
];
const CLICK_OPTIONS = [
  { label: __( 'Aucune', 'wp-seed-events' ), value: 'none' },
  { label: __( 'Visionneuse', 'wp-seed-events' ), value: 'lightbox' },
  { label: __( 'Image originale', 'wp-seed-events' ), value: 'original' },
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
  const label = __( 'WPSEvents — Visuels', 'wp-seed-events' );

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
  const showFlyer = booleanOption( attributes.show_flyer, true );
  const showVisuals = booleanOption( attributes.show_visuals, true );
  const showCaptions = booleanOption( attributes.show_captions, false );
  const imageSize = validOption(
    IMAGE_SIZE_OPTIONS,
    attributes.image_size,
    'large',
  );
  const clickAction = validOption( CLICK_OPTIONS, attributes.click_action, 'none' );
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
            show_flyer: showFlyer,
            show_visuals: showVisuals,
            show_captions: showCaptions,
            image_size: imageSize,
            click_action: clickAction,
            layout,
            horizontal_gap: attributes.horizontal_gap,
            vertical_gap: attributes.vertical_gap,
            align_items: attributes.align_items,
            justify_content: attributes.justify_content,
            wrap: attributes.wrap,
            columns: attributes.columns,
            columns_tablet: attributes.columns_tablet,
            columns_phone: attributes.columns_phone,
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
    showFlyer,
    showVisuals,
    showCaptions,
    imageSize,
    clickAction,
    layout,
    attributes.horizontal_gap, attributes.vertical_gap, attributes.align_items,
    attributes.justify_content, attributes.wrap, attributes.columns,
    attributes.columns_tablet, attributes.columns_phone,
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
          <SelectControl label={ __( 'Action au clic', 'wp-seed-events' ) } value={ clickAction } options={ CLICK_OPTIONS } onChange={ ( value ) => setAttributes( { click_action: value } ) } />
          <SelectControl
            label={ __( 'Disposition', 'wp-seed-events' ) }
            value={ layout }
            options={ LAYOUT_OPTIONS }
            onChange={ ( value ) => setAttributes( { layout: value } ) }
          />
          <TextControl label={ __( 'Espacement horizontal', 'wp-seed-events' ) } value={ attributes.horizontal_gap } onChange={ ( value ) => setAttributes( { horizontal_gap: value } ) } />
          <TextControl label={ __( 'Espacement vertical', 'wp-seed-events' ) } value={ attributes.vertical_gap } onChange={ ( value ) => setAttributes( { vertical_gap: value } ) } />
          <TextControl label={ __( 'Colonnes bureau', 'wp-seed-events' ) } value={ String( attributes.columns ) } onChange={ ( value ) => setAttributes( { columns: Number.parseInt( value, 10 ) || 1 } ) } />
          <TextControl label={ __( 'Colonnes tablette', 'wp-seed-events' ) } value={ String( attributes.columns_tablet ) } onChange={ ( value ) => setAttributes( { columns_tablet: Number.parseInt( value, 10 ) || 1 } ) } />
          <TextControl label={ __( 'Colonnes téléphone', 'wp-seed-events' ) } value={ String( attributes.columns_phone ) } onChange={ ( value ) => setAttributes( { columns_phone: Number.parseInt( value, 10 ) || 1 } ) } />
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
