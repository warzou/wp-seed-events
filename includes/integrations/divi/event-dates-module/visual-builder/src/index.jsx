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

const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

const getContentValues = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};

const normalizeOptions = (attrs) => {
  const values = getContentValues(attrs);
  const headingLevels = ['h2', 'h3', 'h4', 'h5', 'h6'];
  const modes = ['next', 'first', 'last', 'all'];
  const scopes = ['all', 'upcoming', 'past'];
  const selections = ['next', 'first', 'last', 'all_upcoming', 'all_past', 'all'];
  const formats = ['long', 'short'];
  const legacyMode = modes.includes(values.mode) ? values.mode : 'all';
  const legacyScope = scopes.includes(values.scope) ? values.scope : 'all';
  const selection = selections.includes(values.date_selection) ? values.date_selection : '';
  let mode = legacyMode;
  let scope = legacyScope;

  if (selection === 'next') {
    mode = 'next';
    scope = 'upcoming';
  } else if (selection === 'first' || selection === 'last') {
    mode = selection;
    scope = 'all';
  } else if (selection === 'all_upcoming') {
    mode = 'all';
    scope = 'upcoming';
  } else if (selection === 'all_past') {
    mode = 'all';
    scope = 'past';
  } else if (selection === 'all') {
    mode = 'all';
    scope = 'all';
  }

  return {
    title: typeof values.title === 'string' ? values.title : 'Dates',
    heading_level: headingLevels.includes(values.heading_level) ? values.heading_level : 'h2',
    mode,
    scope,
    show_cancelled: values.show_cancelled === 'off' ? 'off' : 'on',
    show_times: values.show_times === 'off' ? 'off' : 'on',
    format: formats.includes(values.format) ? values.format : 'long',
    show_calendar_links: values.show_calendar_links === 'off' ? 'off' : 'on',
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
    {elements.style({ attrName: 'titleStyle' })}
    {elements.style({ attrName: 'dateStyle' })}
    {elements.style({ attrName: 'timeStyle' })}
    {elements.style({ attrName: 'statusStyle' })}
    {elements.style({ attrName: 'calendarLinkStyle' })}
    {elements.style({ attrName: 'occurrenceStyle' })}
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

const EventDatesPreview = ({ attrs, id, name, elements }) => {
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const options = normalizeOptions(attrs);
  const optionsKey = JSON.stringify(options);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const postId = Number(currentPage?.id ?? 0);
  const loopPostId = Number(attrs?.__loop_post_id ?? 0);

  const requestData = useMemo(
    () => ({
      post_id: postId,
      ...(loopPostId > 0 ? { loop_id: loopPostId } : {}),
      ...options,
    }),
    [postId, loopPostId, optionsKey],
  );
  const restRoute = useMemo(
    () => `/wp-seed-events/v1/divi-event-dates-preview?${new URLSearchParams(requestData).toString()}`,
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

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      moduleClassName="wp_seed_events_divi_event_dates"
      name={name}
      scriptDataComponent={ModuleScriptData}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div className="et_pb_module_inner">
        {isLoading && <div role="status">Chargement des dates…</div>}
        {!isLoading && hasError && <div role="alert">L’aperçu des dates est indisponible.</div>}
        {!isLoading && !hasError && html === '' && (
          <div>Aucune date à afficher dans ce contexte.</div>
        )}
        {!isLoading && !hasError && html !== '' && (
          <div dangerouslySetInnerHTML={{ __html: html }} />
        )}
      </div>
    </ModuleContainer>
  );
};

const eventDatesModule = {
  renderers: {
    edit: EventDatesPreview,
  },
  placeholderContent: {
    __loop_post_id: loopPostIdContext,
    content: {
      innerContent: {
        desktop: {
          value: {
            title: 'Dates',
            heading_level: 'h2',
            mode: 'all',
            scope: 'all',
            date_selection: 'all',
            show_cancelled: 'on',
            show_times: 'on',
            format: 'long',
            show_calendar_links: 'on',
          },
        },
      },
    },
  },
};

addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.eventDatesFolder', (modules) => {
  const module = modules?.[metadata.name];

  if (module?.metadata) {
    module.metadata.folder = 'wp-seed-events';
  }

  return modules;
});

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.eventDates', () => {
  registerFolder({
    name: 'wp-seed-events',
    path: '',
    title: 'WP Seed Events',
    icon: '',
    category: 'module',
  });
  registerModule(metadata, eventDatesModule);
});
