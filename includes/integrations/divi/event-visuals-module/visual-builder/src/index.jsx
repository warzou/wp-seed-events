import React, { useEffect, useMemo, useRef, useState } from 'react';

const { addAction, addFilter } = window.vendor.wp.hooks;
const {
  ElementComponents,
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = window.divi.module;
const {
  getCurrentPageSetting,
  registerFolder,
  registerModule,
} = window.divi.moduleLibrary;
const { useFetch } = window.divi.rest;

import metadata from './module.json';
import { resolveCurrentEventContext } from '../../../visual-builder-event-context';
import {
  normalizeListStyles,
  styleSharedListHtml,
} from '../../../event-dates-module/visual-builder/src/divi-style-values';

const { data } = window.divi;

const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

const getContentValues = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};

const normalizeOptions = (attrs) => {
  const values = getContentValues(attrs);
  const valueOrDefault = (value, fallback) => (
    typeof value === 'string' ? value : fallback
  );

  return {
    title: valueOrDefault(values.title, 'Visuels de communication'),
    show_title: valueOrDefault(values.show_title, 'on'),
    heading_level: valueOrDefault(values.heading_level, 'h2'),
    show_flyer: valueOrDefault(values.show_flyer, 'on'),
    show_visuals: valueOrDefault(values.show_visuals, 'on'),
    show_document: valueOrDefault(values.show_document, 'on'),
    show_captions: valueOrDefault(values.show_captions, 'off'),
    image_size: valueOrDefault(values.image_size, 'large'),
    link_original: valueOrDefault(values.link_original, 'on'),
    layout: valueOrDefault(values.layout, 'grid'),
  };
};

const ModuleStyles = ({ elements, mode, state, noStyleTag, settings }) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: {
        disabledOn: {
          disabledModuleVisibility: settings?.disabledModuleVisibility,
        },
      },
    })}
    {elements.style({ attrName: 'sectionStyle' })}
    {elements.style({ attrName: 'titleStyle' })}
    {elements.style({ attrName: 'listStyle' })}
    {elements.style({ attrName: 'gridStyle' })}
    {elements.style({ attrName: 'listLayoutStyle' })}
    {elements.style({ attrName: 'itemStyle' })}
    {elements.style({ attrName: 'figureStyle' })}
    {elements.style({ attrName: 'imageStyle' })}
    {elements.style({ attrName: 'captionStyle' })}
    {elements.style({ attrName: 'documentStyle' })}
    {elements.style({ attrName: 'imageLinkStyle' })}
    {elements.style({ attrName: 'documentLinkStyle' })}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>
    {elements.scriptData({ attrName: 'module' })}
  </React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(
    elementClassnames({
      attrs: attrs?.module?.decoration ?? {},
    }),
  );
};

const EventVisualsPreview = (props) => {
  const { attrs, id, name, elements } = props;
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const options = normalizeOptions(attrs);
  const listStyles = normalizeListStyles(attrs, 'eventListStyle');
  const listStylesKey = JSON.stringify(listStyles);
  const optionsKey = JSON.stringify(options);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const parentId = typeof props.parentId === 'string' ? props.parentId : '';
  const loopIndex = Number.isInteger(props.loopIndex) ? props.loopIndex : -1;
  const eventContext = resolveCurrentEventContext({ data, attrs, parentId, loopIndex, currentPage });
  const postId = eventContext.eventId;
  const loopPostId = eventContext.eventId;

  const requestData = useMemo(
    () => ({
      post_id: postId,
      ...(loopPostId > 0 ? { loop_id: loopPostId } : {}),
      ...options,
    }),
    [postId, loopPostId, eventContext.cacheKey, optionsKey],
  );
  const restRoute = useMemo(
    () => '/wp-seed-events/v1/divi-event-visuals-preview?' + new URLSearchParams(requestData).toString(),
    [requestData],
  );

  useEffect(() => {
    if (abortRef.current) {
      abortRef.current.abort();
    }

    const controller = new AbortController();
    abortRef.current = controller;
    setHasError(false);

    fetch({
      restRoute,
      method: 'GET',
      signal: controller.signal,
    }).catch((error) => {
      if (error?.name !== 'AbortError') {
        setHasError(true);
      }
    });

    return () => controller.abort();
  }, [restRoute]);

  const html = typeof response?.html === 'string' ? response.html : '';
  const styledHtml = useMemo(
    () => styleSharedListHtml(html, '.wp-seed-event-visuals__list', listStyles),
    [html, listStylesKey],
  );

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      moduleClassName='wp_seed_events_divi_event_visuals'
      name={name}
      scriptDataComponent={ModuleScriptData}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div className='et_pb_module_inner'>
        {isLoading && <div role='status'>Chargement des visuels…</div>}
        {!isLoading && hasError && <div role='alert'>L’aperçu des visuels est indisponible.</div>}
        {!isLoading && !hasError && html === '' && (
          <div>Aucun visuel à afficher dans ce contexte.</div>
        )}
        {!isLoading && !hasError && styledHtml !== '' && (
          <div dangerouslySetInnerHTML={{ __html: styledHtml }} />
        )}
      </div>
    </ModuleContainer>
  );
};

const eventVisualsModule = {
  renderers: {
    edit: EventVisualsPreview,
  },
  placeholderContent: {
    __loop_post_id: loopPostIdContext,
    content: {
      innerContent: {
        desktop: {
          value: {
            title: 'Visuels de communication',
            show_title: 'on',
            heading_level: 'h2',
            show_flyer: 'on',
            show_visuals: 'on',
            show_document: 'on',
            show_captions: 'off',
            image_size: 'large',
            link_original: 'on',
            layout: 'grid',
          },
        },
      },
    },
  },
};

addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.eventVisualsFolder', (modules) => {
  const module = modules?.[metadata.name];

  if (module?.metadata) {
    module.metadata.folder = 'wp-seed-events';
  }

  return modules;
});

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.eventVisuals', () => {
  registerFolder({
    name: 'wp-seed-events',
    path: '',
    title: 'WP Seed Events',
    icon: '',
    category: 'module',
  });
  registerModule(metadata, eventVisualsModule);
});
