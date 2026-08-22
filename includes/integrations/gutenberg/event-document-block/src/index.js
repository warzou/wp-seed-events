import apiFetch from '@wordpress/api-fetch';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { PanelBody, Placeholder, SelectControl, Spinner, TextControl, ToggleControl } from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import metadata from './block.json';
const PATH = '/wp-seed-events/v1/gutenberg-event-document-preview';
function Edit({ attributes, setAttributes, context = {} }) {
  const [preview, setPreview] = useState({ loading: true, html: '' }); const sequence = useRef(0);
  useEffect(() => {
    const id = ++sequence.current; const controller = new AbortController(); setPreview({ loading: true, html: '' });
    apiFetch({ path: PATH, method: 'POST', signal: controller.signal, data: { attributes, context: { postId: context.postId, postType: context.postType, queryId: context.queryId } } })
      .then((response) => { if (id === sequence.current) setPreview({ loading: false, html: typeof response?.html === 'string' ? response.html : '' }); })
      .catch((error) => { if (error?.name !== 'AbortError' && id === sequence.current) setPreview({ loading: false, html: '' }); });
    return () => controller.abort();
  }, [attributes.show_document, attributes.link_text, attributes.name_display, attributes.name_position, context.postId, context.postType, context.queryId]);
  return <><InspectorControls><PanelBody title='Document' initialOpen>
    <ToggleControl label='Afficher le document' checked={attributes.show_document} onChange={(value) => setAttributes({ show_document: value })} />
    <TextControl label='Texte du lien' value={attributes.link_text} onChange={(value) => setAttributes({ link_text: value })} />
    <SelectControl label='Affichage du nom' value={attributes.name_display} options={[{label:'Texte uniquement',value:'text'},{label:'Nom du document uniquement',value:'name'},{label:'Texte + nom du document',value:'text_name'}]} onChange={(value) => setAttributes({ name_display: value })} />
    <SelectControl label='Position du nom' value={attributes.name_position} options={[{label:'À la suite',value:'inline'},{label:'Ligne suivante',value:'next_line'}]} onChange={(value) => setAttributes({ name_position: value })} />
  </PanelBody></InspectorControls><div {...useBlockProps()}>{preview.loading ? <Placeholder icon='media-document' label='WPSEvents — Document'><Spinner /></Placeholder> : preview.html ? <RawHTML>{preview.html}</RawHTML> : <Placeholder icon='media-document' label='WPSEvents — Document' instructions='Aucun document à afficher dans ce contexte.' />}</div></>;
}
registerBlockType(metadata.name, { ...metadata, edit: Edit, save: () => null });
