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
const { data } = window.divi;

import metadata from './module.json';
import {
  createEventDatesPreviewFilter,
  getDiviDesktopFieldValue,
  resolveCurrentEventContext,
  toPlainObject,
} from './loop-preview-context';

const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

const getContentValues = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};
const normalizeListOptions = (attrs) => {
  const advanced = toPlainObject(attrs?.listStyle)?.advanced ?? {};
  const markerTypes = ['none', 'disc', 'circle', 'square'];
  const markerPositions = ['outside', 'inside'];
  const markerType = getDiviDesktopFieldValue(advanced.markerType, 'markerType', 'disc');
  const markerPosition = getDiviDesktopFieldValue(advanced.markerPosition, 'markerPosition', 'outside');

  return {
    list_marker_type: markerTypes.includes(markerType) ? markerType : 'disc',
    list_marker_position: markerPositions.includes(markerPosition) ? markerPosition : 'outside',
    list_indent: getDiviDesktopFieldValue(advanced.leftIndent, 'leftIndent', '2.5em'),
    occurrence_gap: getDiviDesktopFieldValue(advanced.occurrenceGap, 'occurrenceGap', '0px'),
    marker_color: getDiviDesktopFieldValue(advanced.markerColor, 'markerColor', ''),
  };
};

const applyPreviewListStyle = (html, listOptions, documentRef = document) => {
  if (html === '' || !documentRef?.createElement) {
    return html;
  }

  const template = documentRef.createElement('template');
  template.innerHTML = html;

  const list = template.content.querySelector('.wp-seed-event-dates');

  if (!list) {
    return html;
  }

  const markerType = listOptions.list_marker_type;
  const markerPosition = listOptions.list_marker_position;
  const items = list.querySelectorAll(':scope > .wp-seed-event-date');

  list.style.setProperty('list-style-type', markerType, 'important');
  list.style.setProperty('list-style-position', markerPosition, 'important');

  items.forEach((item) => {
    item.style.setProperty('display', 'list-item', 'important');
    item.style.setProperty('list-style-type', markerType, 'important');
    item.style.setProperty('list-style-position', markerPosition, 'important');
  });

  if (markerType === 'none') {
    list.style.setProperty('list-style', 'none', 'important');
    items.forEach((item) => item.style.setProperty('list-style', 'none', 'important'));
  }

  return template.innerHTML;
};

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

const EventDatesPreview = (props) => {
  const {
    attrs,
    id,
    name,
    elements,
  } = props;
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const options = normalizeOptions(attrs);
  const listOptions = normalizeListOptions(attrs);
  const optionsKey = JSON.stringify({ ...options, ...listOptions });
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const parentId = typeof props.parentId === 'string' ? props.parentId : '';
  const loopIndex = Number.isInteger(props.loopIndex) ? props.loopIndex : -1;
  const eventContext = resolveCurrentEventContext({ data, attrs, parentId, loopIndex, currentPage });
  const loopPostId = eventContext.eventId;
  const postId = eventContext.eventId;
  const loopContextKey = eventContext.cacheKey;

  const requestData = useMemo(
    () => ({
      post_id: postId,
      ...(loopPostId > 0 ? { loop_id: loopPostId } : {}),
      ...options,
      ...listOptions,
    }),
    [postId, loopPostId, loopContextKey, optionsKey],
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
  const previewHtml = useMemo(
    () => applyPreviewListStyle(html, listOptions),
    [html, optionsKey],
  );

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
        {!isLoading && !hasError && previewHtml === '' && (
          <div>Aucune date à afficher dans ce contexte.</div>
        )}
        {!isLoading && !hasError && previewHtml !== '' && (
          <div dangerouslySetInnerHTML={{ __html: previewHtml }} />
        )}
      </div>
    </ModuleContainer>
  );
};

// Divi's loop wrapper injects the repeated event ID before React evaluates this child.
const EventDatesEditRenderer = (props) => <EventDatesPreview {...props} />;

const eventDatesModule = {
  renderers: {
    edit: EventDatesEditRenderer,
  },
  placeholderContent: {
    __loop_post_id: loopPostIdContext,
    listStyle: {
      advanced: {
        markerType: { desktop: { value: 'none' } },
        markerPosition: { desktop: { value: 'outside' } },
        leftIndent: { desktop: { value: '0px' } },
        occurrenceGap: { desktop: { value: '12px' } },
        markerColor: { desktop: { value: '' } },
      },
    },
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

if (!window.__wpSeedEventsEventDatesLoopPreviewRegistered) {
  addFilter(
    'divi.module.wrapper.render',
    'wpSeedEvents.eventDatesLoopPreview',
    createEventDatesPreviewFilter({ React, data }),
  );
  window.__wpSeedEventsEventDatesLoopPreviewRegistered = true;
}
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
