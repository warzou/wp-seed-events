import apiFetch from '@wordpress/api-fetch';
import { useBlockProps } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { Notice, Placeholder, Spinner } from '@wordpress/components';
import { RawHTML, useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

const cleanContext = (context = {}) => {
  const result = {};
  const postId = Number.parseInt(context.postId, 10);
  if (Number.isInteger(postId) && postId > 0) result.postId = postId;
  if (typeof context.postType === 'string' && context.postType) result.postType = context.postType;
  if (context.queryId !== undefined && context.queryId !== null) result.queryId = Number.parseInt(context.queryId, 10) || 0;
  return result;
};

registerBlockType(metadata.name, {
  ...metadata,
  edit({ context = {} }) {
    const [state, setState] = useState({ status: 'loading', html: '' });
    const requestId = useRef(0);
    const blockProps = useBlockProps({ 'aria-busy': state.status === 'loading' });
    const postId = context.postId;
    const postType = context.postType;
    const queryId = context.queryId;

    useEffect(() => {
      const currentRequest = requestId.current + 1;
      requestId.current = currentRequest;
      const controller = typeof AbortController === 'undefined' ? null : new AbortController();
      setState({ status: 'loading', html: '' });
      apiFetch({
        path: '/wp-seed-events/v1/gutenberg-event-content-preview',
        method: 'POST',
        data: { context: cleanContext({ postId, postType, queryId }) },
        ...(controller ? { signal: controller.signal } : {}),
      }).then((response) => {
        if (currentRequest !== requestId.current) return;
        const html = typeof response?.html === 'string' ? response.html : '';
        setState({ status: html.trim() === '' ? 'empty' : 'ready', html });
      }).catch((error) => {
        if (error?.name !== 'AbortError' && currentRequest === requestId.current) setState({ status: 'error', html: '' });
      });
      return () => controller?.abort();
    }, [postId, postType, queryId]);

    if (state.status === 'loading') return <div {...blockProps}><Placeholder icon="text-page"><Spinner /></Placeholder></div>;
    if (state.status === 'error') return <div {...blockProps}><Notice status="error" isDismissible={false}>{__('Impossible de charger le contenu.', 'wp-seed-events')}</Notice></div>;
    if (state.status === 'empty') return <div {...blockProps}><Placeholder icon="text-page" instructions={__('Aucun contenu à afficher dans ce contexte.', 'wp-seed-events')} /></div>;
    return <div {...blockProps}><RawHTML>{state.html}</RawHTML></div>;
  },
  save: () => null,
});
