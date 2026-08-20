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
import { resolveCurrentEventContext, toPlainObject } from '../../../visual-builder-event-context';
import {
  normalizeListStyles,
  resolveDiviStyleValue,
  styleSharedListHtml,
} from '../../../event-dates-module/visual-builder/src/divi-style-values';

const personTypeFieldName = (key) => `role_${String(key ?? '').replace(/[^a-z0-9_-]/gi, '').toLowerCase()}`;
const canonicalPersonTypeFields = (types) => Object.fromEntries(
  (Array.isArray(types) ? types : []).filter(({ key, label }) => key && label).map(({ key, label }, index) => {
    const subName = personTypeFieldName(key);
    return [`personType_${subName.slice(5).replace(/[^a-z0-9]/gi, '_')}`, {
      groupSlug: 'contentPeopleFilter',
      priority: (index + 1) * 10,
      render: true,
      subName,
      label: String(label),
      description: index === 0 ? 'Activez un ou plusieurs types. Sans sélection, toutes les personnes sont affichées.' : undefined,
      features: { sticky: false, responsive: false, hover: false, dynamicContent: false },
      component: { name: 'divi/toggle', type: 'field', props: { options: { off: 'Non', on: 'Oui' } } },
    }];
  }),
);
const loadCanonicalPersonTypes = async () => {
  const root = window.wpApiSettings?.root ?? `${window.location.origin}/wp-json/`;
  const route = new URL('wp-seed-events/v1/person-types', root);
  route.searchParams.set('_wpseed_registry', String(Date.now()));
  const response = await window.fetch(route.toString(), { credentials: 'same-origin', cache: 'no-store' });
  if (!response.ok) throw new Error(`Person type registry returned HTTP ${response.status}`);
  const payload = await response.json();
  return Array.isArray(payload?.types) ? payload.types : [];
};
const metadataWithCanonicalPersonTypes = (baseMetadata, types) => {
  const next = JSON.parse(JSON.stringify(baseMetadata));
  const items = next.attributes.content.settings.innerContent.items;
  Object.assign(items, canonicalPersonTypeFields(types));
  return next;
};
const registerEventPeopleModule = (types) => registerModule(
  metadataWithCanonicalPersonTypes(metadata, types),
  eventPeopleModule,
);

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
const normalizeSeparatorStyles = (attrs, attributeName) => {
  const advanced = toPlainObject(attrs)?.[attributeName]?.advanced ?? {};
  const config = {
    color: { fallback: '', color: true },
    fontSize: { fallback: '1em' },
    spaceBefore: { fallback: '0.35em' },
    spaceAfter: { fallback: '0.35em' },
  };
  const styles = {};
  ['desktop', 'tablet', 'phone'].forEach((breakpoint) => {
    styles[breakpoint] = {};
    Object.entries(config).forEach(([field, settings]) => {
      let value = resolveDiviStyleValue(advanced[field], breakpoint, 'value', field, settings.fallback);
      if (settings.color) {
        value = /^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\([^;{}]+\)|var\(--[a-z0-9_-]+\))$/i.test(value) ? value : '';
      } else if (!/^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/.test(value)) {
        value = settings.fallback;
      }
      styles[breakpoint][field] = value;
    });
  });
  return styles;
};
const applySeparatorStyles = (elements, separatorStyles, variablePrefix) => {
  const propertyMap = { color: 'color', fontSize: 'size', spaceBefore: 'before', spaceAfter: 'after' };
  elements.forEach((separator) => {
    ['desktop', 'tablet', 'phone'].forEach((breakpoint) => {
      Object.entries(propertyMap).forEach(([field, property]) => {
        separator.style.setProperty(
          `--wp-seed-event-people-${variablePrefix}-${property}-${breakpoint}`,
          field === 'color' ? (separatorStyles[breakpoint][field] || 'currentColor') : separatorStyles[breakpoint][field],
        );
      });
    });
  });
};
const stylePeoplePreviewHtml = (html, listStyles, separatorStyles, nameSeparatorStyles) => {
  const listStyled = styleSharedListHtml(html, '.wp-seed-event-people__list', listStyles);
  if (typeof document !== 'object' || listStyled === '') return listStyled;
  const template = document.createElement('template');
  template.innerHTML = listStyled;
  applySeparatorStyles(
    template.content.querySelectorAll('.wp-seed-event-people__contact-separator:not(.wp-seed-event-people__contact-separator--name)'),
    separatorStyles,
    'separator',
  );
  applySeparatorStyles(
    template.content.querySelectorAll('.wp-seed-event-people__contact-separator--name'),
    nameSeparatorStyles,
    'name-separator',
  );
  return template.innerHTML;
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
    {elements.style({ attrName: 'itemStyle' })}
    {elements.style({ attrName: 'nameStyle' })}
    {elements.style({ attrName: 'rolesStyle' })}
    {elements.style({ attrName: 'roleStyle' })}
    {elements.style({ attrName: 'contactsStyle' })}
    {elements.style({ attrName: 'emailLinkStyle' })}
    {elements.style({ attrName: 'phoneLinkStyle' })}
    {elements.style({ attrName: 'publicLinkStyle' })}
	{elements.style({ attrName: 'contactSeparatorStyle' })}
	{elements.style({ attrName: 'nameContactSeparatorStyle' })}
  </StyleContainer>
);

const ModuleScriptData = ({ elements }) => (
  <React.Fragment>{elements.scriptData({ attrName: 'module' })}</React.Fragment>
);

const moduleClassnames = ({ classnamesInstance, attrs }) => {
  classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
};

const EventPeoplePreview = (props) => {
  const { attrs, id, name, elements } = props;
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef();
  const [hasError, setHasError] = useState(false);
  const values = getContentValues(attrs);
  const listStyles = normalizeListStyles(attrs, 'eventListStyle');
  const listStylesKey = JSON.stringify(listStyles);
	const separatorStyles = normalizeSeparatorStyles(attrs, 'contactSeparatorStyle');
	const separatorStylesKey = JSON.stringify(separatorStyles);
	const nameSeparatorStyles = normalizeSeparatorStyles(attrs, 'nameContactSeparatorStyle');
	const nameSeparatorStylesKey = JSON.stringify(nameSeparatorStyles);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const context = resolveCurrentEventContext({ data, attrs, parentId: props.parentId, loopIndex: props.loopIndex, currentPage });
  const requestData = useMemo(() => ({
	post_id: context.eventId,
	...(context.eventId > 0 ? { loop_id: context.eventId } : {}),
	...values,
	contact_layout_tablet: getResponsiveContentValue(attrs, 'contact_layout', 'tablet', values.contact_layout ?? 'stacked'),
	contact_layout_phone: getResponsiveContentValue(attrs, 'contact_layout', 'phone', values.contact_layout ?? 'stacked'),
  }), [context.cacheKey, JSON.stringify(toPlainObject(attrs)?.content?.innerContent ?? {})]);
  const restRoute = useMemo(() => `/wp-seed-events/v1/divi-event-people-preview?${new URLSearchParams(requestData).toString()}`, [requestData]);
  useEffect(() => {
    abortRef.current?.abort();
    const controller = new AbortController();
    abortRef.current = controller;
    setHasError(false);
    fetch({ restRoute, method: 'GET', signal: controller.signal }).catch((error) => { if (error?.name !== 'AbortError') setHasError(true); });
    return () => controller.abort();
  }, [restRoute]);
  const html = typeof response?.html === 'string' ? response.html : '';
  const styledHtml = useMemo(
    () => stylePeoplePreviewHtml(html, listStyles, separatorStyles, nameSeparatorStyles),
    [html, listStylesKey, separatorStylesKey, nameSeparatorStylesKey],
  );
  return (
    <ModuleContainer attrs={attrs} elements={elements} id={id} moduleClassName='wp_seed_events_divi_event_people' name={name} scriptDataComponent={ModuleScriptData} stylesComponent={ModuleStyles} classnamesFunction={moduleClassnames}>
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div className='et_pb_module_inner'>
        {isLoading && <div role='status'>Chargement des personnes…</div>}
        {!isLoading && hasError && <div role='alert'>L’aperçu des personnes est indisponible.</div>}
        {!isLoading && !hasError && styledHtml !== '' && <div dangerouslySetInnerHTML={{ __html: styledHtml }} />}
      </div>
    </ModuleContainer>
  );
};

const eventPeopleModule = {
  renderers: { edit: EventPeoplePreview },
  placeholderContent: {
    __loop_post_id: loopPostIdContext,
    content: {
      innerContent: {
        desktop: {
          value: {
            title: 'Contacts et intervenants',
            show_title: 'on',
            heading_level: 'h2',
            role: 'all',
			people_contract: 'composable-v3',
            show_name: 'on',
            show_roles: 'off',
            show_email: 'on',
            show_phone: 'on',
            show_link: 'on',
			email_clickable: 'on',
			phone_clickable: 'on',
			site_clickable: 'on',
			contact_layout: 'stacked',
			show_contact_separator: 'off',
			contact_separator: '\u2014',
			show_name_contact_separator: 'off',
			name_contact_separator: '\u2014',
            layout: 'list',
          },
        },
      },
    },
  },
};

addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.eventPeopleFolder', (modules) => {
  const module = modules?.[metadata.name];

  if (module?.metadata) {
    module.metadata.folder = 'wp-seed-events';
  }

  return modules;
});

addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.eventPeople', () => {
  registerFolder({
    name: 'wp-seed-events',
    path: '',
    title: 'WP Seed Events',
    icon: '',
    category: 'module',
  });
  loadCanonicalPersonTypes()
    .then(registerEventPeopleModule)
    .catch(() => registerEventPeopleModule([]));
});
