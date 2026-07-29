import React, { useEffect, useMemo, useRef, useState } from 'react';

const { addAction, addFilter } = window.vendor.wp.hooks;
const {
  ElementComponents,
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = window.divi.module;
const { registerFolder, registerModule } = window.divi.moduleLibrary;
const { useFetch } = window.divi.rest;

import metadata from './module.json';

const defaults = metadata.attributes.content.default.innerContent.desktop.value;
const getValues = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};
const scalar = (value, fallback = '') => (
  typeof value === 'string' || typeof value === 'number' ? String(value) : fallback
);
const toggle = (value, fallback) => {
  if (value === 'on' || value === true || value === 1) return 'on';
  if (value === 'off' || value === false || value === 0) return 'off';
  return fallback;
};

export const normalizeOptions = (attrs) => {
  const values = { ...defaults, ...getValues(attrs) };
  const modes = ['flat', 'grouped'];
  const statuses = ['upcoming', 'past', 'all'];
  const pinned = ['all', 'only'];
  const orders = ['upcoming', 'chronological', 'chronological_desc'];
  const toggles = [
    'include_cancelled', 'show_event_title', 'show_event_type', 'show_event_status',
    'show_event_pinned', 'show_start_date', 'show_end_date', 'show_start_time',
    'show_end_time', 'show_cancelled', 'show_promotion_name', 'show_promotion_year',
    'show_promotion_status', 'show_parcours_year', 'show_parcours_label', 'show_labels',
  ];
  const normalized = {
    ...values,
    mode: modes.includes(values.mode) ? values.mode : 'flat',
    status: statuses.includes(values.status) ? values.status : 'upcoming',
    pinned: pinned.includes(values.pinned) ? values.pinned : 'all',
    order: orders.includes(values.order) ? values.order : 'chronological',
    promotion: scalar(values.promotion),
    parcours_year: Math.max(0, Number.parseInt(values.parcours_year, 10) || 0),
    event_id: Math.max(0, Number.parseInt(values.event_id, 10) || 0),
    type: scalar(values.type),
    page: Math.max(1, Number.parseInt(values.page, 10) || 1),
    per_page: Math.min(100, Math.max(1, Number.parseInt(values.per_page, 10) || 20)),
    grouped_limit: Math.min(500, Math.max(1, Number.parseInt(values.grouped_limit, 10) || 200)),
    date_format: values.date_format === 'short' ? 'short' : 'long',
    time_format: values.time_format === '24h' ? '24h' : 'site',
  };

  toggles.forEach((key) => {
    normalized[key] = toggle(values[key], defaults[key]);
  });

  return normalized;
};

const ModuleStyles = ({ elements, mode, state, noStyleTag, settings }) => (
  <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
    {elements.style({
      attrName: 'module',
      styleProps: { disabledOn: { disabledModuleVisibility: settings?.disabledModuleVisibility } },
    })}
    {['collectionStyle', 'promotionStyle', 'yearStyle', 'themeStyle', 'itemStyle',
      'titleStyle', 'labelStyle', 'valueStyle', 'emptyStyle', 'paginationStyle']
      .map((attrName) => elements.style({ attrName }))}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>{elements.scriptData({ attrName: 'module' })}</React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
};

const OccurrenceCollectionPreview = ({ attrs, id, name, elements, storeInstance, orderIndex }) => {
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const options = normalizeOptions(attrs);
  const optionsKey = JSON.stringify(options);
  const instanceId = useMemo(
    () => `divi-occurrence-${[id, storeInstance, orderIndex].join('-').replace(/[^a-z0-9_-]/gi, '').toLowerCase()}`,
    [id, storeInstance, orderIndex],
  );
  const requestData = useMemo(
    () => ({
      ...options,
      collection_instance_id: options.collection_instance_id || instanceId,
      module_id: id ?? '',
      store_instance: storeInstance ?? '',
      order_index: orderIndex ?? 0,
    }),
    [optionsKey, instanceId, id, storeInstance, orderIndex],
  );
  const restRoute = useMemo(
    () => `/wp-seed-events/v1/divi-occurrence-collection-preview?${new URLSearchParams(requestData).toString()}`,
    [requestData],
  );

  useEffect(() => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;
    setHasError(false);
    fetch({ restRoute, method: 'GET', signal: controller.signal }).catch((error) => {
      if (error?.name !== 'AbortError') setHasError(true);
    });
    return () => controller.abort();
  }, [restRoute]);

  const html = typeof response?.html === 'string' ? response.html : '';

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      moduleClassName="wp_seed_events_divi_occurrence_collection"
      name={name}
      scriptDataComponent={ModuleScriptData}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div className="et_pb_module_inner">
        {isLoading && <div role="status">Chargement de la collection…</div>}
        {!isLoading && hasError && <div role="alert">L’aperçu de la collection est indisponible.</div>}
        {!isLoading && !hasError && html === '' && <div>Aucune occurrence à afficher.</div>}
        {!isLoading && !hasError && html !== '' && <div dangerouslySetInnerHTML={{ __html: html }} />}
      </div>
    </ModuleContainer>
  );
};

const occurrenceCollectionModule = {
  renderers: { edit: OccurrenceCollectionPreview },
  placeholderContent: {
    content: { innerContent: { desktop: { value: defaults } } },
  },
};

addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.occurrenceCollectionFolder', (modules) => {
  const module = modules?.[metadata.name];
  if (module?.metadata) module.metadata.folder = 'wp-seed-events';
  return modules;
});

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.occurrenceCollection', () => {
  registerFolder({ name: 'wp-seed-events', path: '', title: 'WP Seed Events', icon: '', category: 'module' });
  registerModule(metadata, occurrenceCollectionModule);
});
