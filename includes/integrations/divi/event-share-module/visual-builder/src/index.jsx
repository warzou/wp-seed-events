import React, { useEffect, useMemo, useRef, useState } from 'react';

const { addAction, addFilter } = window.vendor.wp.hooks;
const {
  ElementComponents,
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = window.divi.module;
const { getCurrentPageSetting, registerFolder, registerModule } = window.divi.moduleLibrary;
const { useFetch } = window.divi.rest;
const { data } = window.divi;

import metadata from './module.json';
import { resolveCurrentEventContext } from '../../../visual-builder-event-context';

const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

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
    {elements.style({ attrName: 'shareStyle' })}
    {elements.style({ attrName: 'summaryStyle' })}
    {elements.style({ attrName: 'actionsStyle' })}
    {elements.style({ attrName: 'buttonStyle' })}
    {elements.style({ attrName: 'linkStyle' })}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>{elements.scriptData({ attrName: 'module' })}</React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
};

const EventSharePreview = (props) => {
  const { attrs, id, name, elements } = props;
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const context = resolveCurrentEventContext({ data, attrs, parentId: props.parentId, loopIndex: props.loopIndex, currentPage });
  const restRoute = useMemo(() => `/wp-seed-events/v1/divi-event-share-preview?post_id=${context.eventId}&loop_id=${context.eventId}`, [context.cacheKey]);
  useEffect(() => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;
    setHasError(false);
    fetch({ restRoute, method: 'GET', signal: controller.signal }).catch((error) => { if (error?.name !== 'AbortError') setHasError(true); });
    return () => controller.abort();
  }, [restRoute]);
  const html = typeof response?.html === 'string' ? response.html : '';
  return (
    <ModuleContainer attrs={attrs} elements={elements} id={id} moduleClassName='wp_seed_events_divi_event_share' name={name} scriptDataComponent={ModuleScriptData} stylesComponent={ModuleStyles} classnamesFunction={moduleClassnames}>
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div className='et_pb_module_inner'>
        {isLoading && <div role='status'>Chargement du partage…</div>}
        {!isLoading && hasError && <div role='alert'>L’aperçu du partage est indisponible.</div>}
        {!isLoading && !hasError && html !== '' && <div dangerouslySetInnerHTML={{ __html: html }} />}
      </div>
    </ModuleContainer>
  );
};

const eventShareModule = {
  renderers: { edit: EventSharePreview },
  placeholderContent: { __loop_post_id: loopPostIdContext },
};

addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.eventShareFolder', (modules) => {
  const module = modules?.[metadata.name];

  if (module?.metadata) {
    module.metadata.folder = 'wp-seed-events';
  }

  return modules;
});

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.eventShare', () => {
  registerFolder({
    name: 'wp-seed-events',
    path: '',
    title: 'WP Seed Events',
    icon: '',
    category: 'module',
  });
  registerModule(metadata, eventShareModule);
});
