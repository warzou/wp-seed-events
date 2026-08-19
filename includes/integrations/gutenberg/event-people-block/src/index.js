import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import {
  Notice,
  CheckboxControl,
  PanelBody,
  Placeholder,
  SelectControl,
  Spinner,
  TextControl,
  ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

import metadata from './block.json';

const HEADING_LEVEL_OPTIONS = [
  { label: 'h2', value: 'h2' },
  { label: 'h3', value: 'h3' },
  { label: 'h4', value: 'h4' },
  { label: 'h5', value: 'h5' },
  { label: 'h6', value: 'h6' },
];

const ROLE_OPTIONS = [
  { label: __( 'Tous les rôles', 'wp-seed-events' ), value: 'all' },
  { label: __( 'Organisateur', 'wp-seed-events' ), value: 'organizer' },
  { label: __( 'Intervenant', 'wp-seed-events' ), value: 'speaker' },
  { label: __( 'Contact', 'wp-seed-events' ), value: 'contact' },
];
const ROLE_FILTER_OPTIONS = ROLE_OPTIONS.filter( ( option ) => option.value !== 'all' );

function canonicalRole( role ) {
  return [ 'registration_contact', 'information_contact' ].includes( role )
    ? 'contact'
    : role;
}


const LAYOUT_OPTIONS = [
  { label: __( 'Liste', 'wp-seed-events' ), value: 'list' },
  { label: __( 'Grille', 'wp-seed-events' ), value: 'grid' },
];

function validOption( options, value, fallback ) {
  return options.some( ( option ) => option.value === value ) ? value : fallback;
}

function booleanOption( value, fallback ) {
  return typeof value === 'boolean' ? value : fallback;
}
function normalizedRoles( attributes ) {
  const rawRoles = Array.isArray( attributes.roles ) ? attributes.roles : [];

  if ( rawRoles.includes( 'all' ) ) {
    return [];
  }

  const roles = rawRoles.map( canonicalRole ).filter(
    ( role, index, values ) =>
      ROLE_FILTER_OPTIONS.some( ( option ) => option.value === role ) &&
      values.indexOf( role ) === index,
  );

  if ( roles.length ) {
    return roles;
  }

  const legacyRole = validOption( ROLE_OPTIONS, canonicalRole( attributes.role ), 'all' );

  return legacyRole === 'all' ? [] : [ legacyRole ];
}


function previewAttributes( attributes ) {
  const roles = normalizedRoles( attributes );

  return {
    title:
      typeof attributes.title === 'string'
        ? attributes.title
        : 'Contacts et intervenants',
    heading_level: validOption(
      HEADING_LEVEL_OPTIONS,
      attributes.heading_level,
      'h2',
    ),
    roles,
    role: roles[ 0 ] || 'all',
    show_name: booleanOption( attributes.show_name, true ),
    show_roles: booleanOption( attributes.show_roles, true ),
    show_email: booleanOption( attributes.show_email, true ),
    show_phone: booleanOption( attributes.show_phone, true ),
    show_link: booleanOption( attributes.show_link, true ),
    link_phone: booleanOption( attributes.link_phone, true ),
    link_email: booleanOption( attributes.link_email, true ),
    link_url: booleanOption( attributes.link_url, true ),
    layout: validOption( LAYOUT_OPTIONS, attributes.layout, 'list' ),
  };
}

function LoadingPreview() {
  return (
    <Placeholder icon="groups" label={ __( 'WP Seed — Personnes de l’événement', 'wp-seed-events' ) }>
      <Spinner />
      <span>{ __( 'Chargement de l’aperçu…', 'wp-seed-events' ) }</span>
    </Placeholder>
  );
}

function EmptyPreview() {
  return (
    <Placeholder
      icon="groups"
      label={ __( 'WP Seed — Personnes de l’événement', 'wp-seed-events' ) }
      instructions={ __( 'Aucune personne à afficher dans ce contexte.', 'wp-seed-events' ) }
    />
  );
}

function ErrorPreview() {
  return (
    <Placeholder icon="groups" label={ __( 'WP Seed — Personnes de l’événement', 'wp-seed-events' ) }>
      <Notice status="error" isDismissible={ false }>
        { __( 'Impossible de charger l’aperçu des personnes.', 'wp-seed-events' ) }
      </Notice>
    </Placeholder>
  );
}

function Edit( { attributes, setAttributes, context = {} } ) {
  const normalized = previewAttributes( attributes );
  const blockProps = useBlockProps();
  const postId = Number.parseInt( context.postId, 10 );
  const urlQueryArgs = Number.isInteger( postId ) && postId > 0 ? { post_id: postId } : {};

  return (
    <>
      <InspectorControls>
        <PanelBody title={ __( 'Réglages des personnes', 'wp-seed-events' ) } initialOpen>
          <TextControl
            label={ __( 'Titre', 'wp-seed-events' ) }
            value={ normalized.title }
            onChange={ ( value ) => setAttributes( { title: value } ) }
          />
          <SelectControl
            label={ __( 'Niveau du titre', 'wp-seed-events' ) }
            value={ normalized.heading_level }
            options={ HEADING_LEVEL_OPTIONS }
            onChange={ ( value ) => setAttributes( { heading_level: value } ) }
          />
          <fieldset>
            <legend>{ __( 'Rôles affichés', 'wp-seed-events' ) }</legend>
            <CheckboxControl
              label={ __( 'Tous les rôles', 'wp-seed-events' ) }
              checked={ normalized.roles.length === 0 }
              onChange={ ( checked ) => {
                if ( checked ) {
                  setAttributes( { roles: [], role: 'all' } );
                }
              } }
            />
            { ROLE_FILTER_OPTIONS.map( ( option ) => (
              <CheckboxControl
                key={ option.value }
                label={ option.label }
                checked={ normalized.roles.includes( option.value ) }
                onChange={ ( checked ) => {
                  const roles = checked
                    ? [ ...normalized.roles, option.value ]
                    : normalized.roles.filter( ( role ) => role !== option.value );
                  const uniqueRoles = roles.filter(
                    ( role, index, values ) => values.indexOf( role ) === index,
                  );

                  setAttributes( {
                    roles: uniqueRoles,
                    role: uniqueRoles[ 0 ] || 'all',
                  } );
                } }
              />
            ) ) }
          </fieldset>
          <ToggleControl
            label={ __( 'Afficher le nom', 'wp-seed-events' ) }
            checked={ normalized.show_name }
            onChange={ ( value ) => setAttributes( { show_name: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les rôles', 'wp-seed-events' ) }
            checked={ normalized.show_roles }
            onChange={ ( value ) => setAttributes( { show_roles: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les emails autorisés', 'wp-seed-events' ) }
            help={ __( 'Les coordonnées sont privées par défaut. Seuls les emails autorisés sur l’événement peuvent apparaître.', 'wp-seed-events' ) }
            checked={ normalized.show_email }
            onChange={ ( value ) => setAttributes( { show_email: value } ) }
          />
          <ToggleControl
            label={ __( 'Rendre l’email cliquable', 'wp-seed-events' ) }
            checked={ normalized.link_email }
            disabled={ ! normalized.show_email }
            onChange={ ( value ) => setAttributes( { link_email: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les téléphones autorisés', 'wp-seed-events' ) }
            help={ __( 'Masquer ce champ dans le bloc ne modifie pas son autorisation.', 'wp-seed-events' ) }
            checked={ normalized.show_phone }
            onChange={ ( value ) => setAttributes( { show_phone: value } ) }
          />
          <ToggleControl
            label={ __( 'Rendre le téléphone cliquable', 'wp-seed-events' ) }
            checked={ normalized.link_phone }
            disabled={ ! normalized.show_phone }
            onChange={ ( value ) => setAttributes( { link_phone: value } ) }
          />
          <ToggleControl
            label={ __( 'Afficher les liens autorisés', 'wp-seed-events' ) }
            help={ __( 'Gutenberg ne peut jamais publier une coordonnée non autorisée.', 'wp-seed-events' ) }
            checked={ normalized.show_link }
            onChange={ ( value ) => setAttributes( { show_link: value } ) }
          />
          <ToggleControl
            label={ __( 'Rendre le lien cliquable', 'wp-seed-events' ) }
            checked={ normalized.link_url }
            disabled={ ! normalized.show_link }
            onChange={ ( value ) => setAttributes( { link_url: value } ) }
          />
          <SelectControl
            label={ __( 'Mise en page', 'wp-seed-events' ) }
            value={ normalized.layout }
            options={ LAYOUT_OPTIONS }
            onChange={ ( value ) => setAttributes( { layout: value } ) }
          />
        </PanelBody>
      </InspectorControls>
      <div { ...blockProps }>
        <ServerSideRender
          block={ metadata.name }
          attributes={ normalized }
          urlQueryArgs={ urlQueryArgs }
          LoadingResponsePlaceholder={ LoadingPreview }
          EmptyResponsePlaceholder={ EmptyPreview }
          ErrorResponsePlaceholder={ ErrorPreview }
        />
      </div>
    </>
  );
}

registerBlockType( metadata.name, {
  ...metadata,
  edit: Edit,
  save: () => null,
} );
