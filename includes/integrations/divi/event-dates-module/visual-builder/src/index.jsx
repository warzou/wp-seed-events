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

import {
  applyListStyleVariables,
  normalizeListStyles,
  previewListStyleCss,
  previewListStyleScope,
  resolveDiviStyleValue,
} from './divi-style-values';
const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

const getContentValues = (attrs, breakpoint = 'desktop') => (
  toPlainObject(attrs)?.content?.innerContent?.[breakpoint]?.value ?? {}
);
const getResponsiveContentValue = (attrs, field, breakpoint, fallback) => {
  const inheritance = breakpoint === 'desktop'
    ? ['desktop']
    : (breakpoint === 'tablet' ? ['tablet', 'desktop'] : ['phone', 'tablet', 'desktop']);

  for (const candidate of inheritance) {
    const value = getContentValues(attrs, candidate)?.[field];
    if (typeof value === 'string' && value !== '') return value;
  }

  return fallback;
};
const normalizeSeparatorStyles = (attrs) => {
  const advanced = toPlainObject(attrs)?.separatorStyle?.advanced ?? {};
  const config = {
    color: { defaultValue: '', color: true },
    fontSize: { defaultValue: '1em' },
    spaceBefore: { defaultValue: '0.35em' },
    spaceAfter: { defaultValue: '0.35em' },
  };
  const styles = {};

  ['desktop', 'tablet', 'phone'].forEach((breakpoint) => {
    styles[breakpoint] = {};
    Object.entries(config).forEach(([field, fieldConfig]) => {
      let value = resolveDiviStyleValue(
        advanced[field],
        breakpoint,
        'value',
        field,
        fieldConfig.defaultValue,
      );
      value = String(value ?? '').trim();
      if (fieldConfig.color) {
        value = /^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\([^;{}]+\)|var\(--[a-z0-9_-]+\))$/i.test(value)
          ? value
          : '';
      } else if (!/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/.test(value)) {
        value = fieldConfig.defaultValue;
      }
      styles[breakpoint][field] = value;
    });
  });

  return styles;
};
const applySeparatorStyleVariables = (separator, styles) => {
  if (!separator?.style) return;
  const propertyMap = {
    color: 'color',
    fontSize: 'size',
    spaceBefore: 'before',
    spaceAfter: 'after',
  };

  ['desktop', 'tablet', 'phone'].forEach((breakpoint) => {
    Object.entries(propertyMap).forEach(([field, property]) => {
      const value = field === 'color' ? (styles[breakpoint][field] || 'currentColor') : styles[breakpoint][field];
      separator.style.setProperty(`--wp-seed-event-dates-separator-${property}-${breakpoint}`, value);
    });
  });
};
const applyPreviewStyles = (html, listStyles, separatorStyles, documentRef = document) => {
  if (html === '' || !documentRef?.createElement) {
    return html;
  }

  const template = documentRef.createElement('template');
  template.innerHTML = html;

  const list = template.content.querySelector('.wp-seed-event-dates');

  if (!list) {
    return html;
  }

  const items = list.querySelectorAll(':scope > .wp-seed-event-date');
  const scope = previewListStyleScope(listStyles);
  const previewStyle = documentRef.createElement('style');

  Array.from(list.classList).forEach((className) => {
    if (className.startsWith('is-marker-')) list.classList.remove(className);
  });
  list.classList.add(
    'has-custom-list-style',
    `is-marker-${listStyles.desktop.markerType}`,
    `is-marker-position-${listStyles.desktop.markerPosition}`,
    scope,
  );
  previewStyle.setAttribute('data-wp-seed-event-dates-preview-style', scope);
  previewStyle.textContent = previewListStyleCss(scope);
  template.content.prepend(previewStyle);

  applyListStyleVariables(list, listStyles);
  list.style.setProperty('list-style-type', 'var(--wp-seed-event-dates-current-marker-type)', 'important');
  list.style.setProperty('list-style-position', 'var(--wp-seed-event-dates-current-marker-position)', 'important');
  list.style.setProperty('padding-block-end', '0', 'important');

  items.forEach((item) => {
    item.style.setProperty('display', 'list-item', 'important');
    item.style.setProperty('list-style-type', 'var(--wp-seed-event-dates-current-marker-type)', 'important');
    item.style.setProperty('list-style-position', 'var(--wp-seed-event-dates-current-marker-position)', 'important');
  });

  template.content.querySelectorAll('.wp-seed-event-date__separator').forEach((separator) => {
    applySeparatorStyleVariables(separator, separatorStyles);
  });

  return template.innerHTML;
};

const normalizeOptions = (attrs) => {
  const values = getContentValues(attrs);
  const headingLevels = ['h2', 'h3', 'h4', 'h5', 'h6'];
  const modes = ['next', 'first', 'last', 'all'];
  const scopes = ['all', 'upcoming', 'past'];
  const selections = ['next', 'first', 'last', 'all_upcoming', 'all_past', 'all'];
  const formats = ['long', 'short'];
  const layouts = ['inline', 'below'];
  const legacyMode = modes.includes(values.mode) ? values.mode : 'all';
  const legacyScope = scopes.includes(values.scope) ? values.scope : 'all';
  const selection = selections.includes(values.date_selection) ? values.date_selection : '';
  let mode = legacyMode;
  let scope = legacyScope;
  const timeLayouts = {};
  const hasResponsiveTimeLayout = ['tablet', 'phone'].some((breakpoint) => {
    const breakpointValues = getContentValues(attrs, breakpoint);
    return Object.prototype.hasOwnProperty.call(breakpointValues, 'time_layout')
      && typeof breakpointValues.time_layout === 'string'
      && breakpointValues.time_layout !== '';
  });

  ['desktop', 'tablet', 'phone'].forEach((breakpoint) => {
    const value = getResponsiveContentValue(attrs, 'time_layout', breakpoint, 'below');
    timeLayouts[breakpoint] = layouts.includes(value) ? value : 'below';
  });

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

  const hasLegacyTitle = Object.prototype.hasOwnProperty.call(values, 'title')
    && Object.prototype.hasOwnProperty.call(values, 'show_title');
  const legacyTitleOptions = hasLegacyTitle
    ? {
      title: typeof values.title === 'string' ? values.title : '',
      show_title: values.show_title === 'off' ? 'off' : 'on',
      heading_level: headingLevels.includes(values.heading_level) ? values.heading_level : 'h2',
    }
    : {};

  return {
    ...legacyTitleOptions,
    mode,
    scope,
    show_cancelled: values.show_cancelled === 'off' ? 'off' : 'on',
    show_dates: values.show_dates === 'off' ? 'off' : 'on',
    show_times: values.show_times === 'off' ? 'off' : 'on',
    time_layout: timeLayouts.desktop,
    time_layout_tablet: timeLayouts.tablet,
    time_layout_phone: timeLayouts.phone,
    responsive_time_layout_requested: hasResponsiveTimeLayout,
    show_separator: values.show_separator === 'on' ? 'on' : 'off',
    separator_character: typeof values.separator_character === 'string' && values.separator_character.trim() !== ''
      ? values.separator_character.trim().slice(0, 8)
      : '\u2014',
    format: formats.includes(values.format) ? values.format : 'long',
    show_calendar_links: Object.prototype.hasOwnProperty.call(values, 'show_calendar_links')
      && values.show_calendar_links === 'on'
      ? 'on'
      : 'off',
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
    {elements.style({ attrName: 'dateStyle' })}
    {elements.style({ attrName: 'timeStyle' })}
    {elements.style({ attrName: 'separatorStyle' })}
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
  const listStyles = normalizeListStyles(attrs);
  const separatorStyles = normalizeSeparatorStyles(attrs);
  const listStylesKey = JSON.stringify(listStyles);
  const separatorStylesKey = JSON.stringify(separatorStyles);
  const optionsKey = JSON.stringify(options);
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
    () => applyPreviewStyles(html, listStyles, separatorStyles),
    [html, listStylesKey, separatorStylesKey],
  );

  const previewContent = !isLoading && !hasError && previewHtml !== '';
  const legacyHeadingLevel = options.heading_level || 'h2';
  const showLegacyTitle = options.show_title === 'on'
    && typeof options.title === 'string'
    && options.title.trim() !== '';

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
      {previewContent ? (
        <div className="et_pb_module_inner" dangerouslySetInnerHTML={{ __html: previewHtml }} />
      ) : (
        <div className="et_pb_module_inner">
        {isLoading && <div role="status">Chargement des dates…</div>}
        {!isLoading && hasError && <div role="alert">L’aperçu des dates est indisponible.</div>}
        {!isLoading && !hasError && previewHtml === '' && (
          <>
            {showLegacyTitle && React.createElement(
              legacyHeadingLevel,
              { className: 'wp-seed-event-dates__title' },
              options.title,
            )}
            <div>Aucune date à afficher dans ce contexte.</div>
          </>
        )}
        </div>
      )}
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
    separatorStyle: {
      advanced: {
        color: { desktop: { value: '' } },
        fontSize: { desktop: { value: '1em' } },
        spaceBefore: { desktop: { value: '0.35em' } },
        spaceAfter: { desktop: { value: '0.35em' } },
      },
    },
    content: {
      innerContent: {
        desktop: {
          value: {
            mode: 'all',
            scope: 'all',
            date_selection: 'all',
            show_cancelled: 'on',
            show_dates: 'on',
            show_times: 'on',
            time_layout: 'below',
            show_separator: 'off',
            separator_character: '\u2014',
            format: 'long',
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
