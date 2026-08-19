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
import {
  normalizeListStyles,
  styleSharedListHtml,
} from '../../../event-dates-module/visual-builder/src/divi-style-values';

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
  const values = attrs?.content?.innerContent?.desktop?.value ?? {};
  const listStyles = normalizeListStyles(attrs, 'eventListStyle');
  const listStylesKey = JSON.stringify(listStyles);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const context = resolveCurrentEventContext({ data, attrs, parentId: props.parentId, loopIndex: props.loopIndex, currentPage });
  const requestData = useMemo(() => ({ post_id: context.eventId, ...(context.eventId > 0 ? { loop_id: context.eventId } : {}), ...values }), [context.cacheKey, JSON.stringify(values)]);
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
    () => styleSharedListHtml(html, '.wp-seed-event-people__list', listStyles),
    [html, listStylesKey],
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
            role_organizer: 'off',
            role_speaker: 'off',
            role_registration_contact: 'off',
            role_information_contact: 'off',
            show_name: 'on',
            show_roles: 'on',
            show_email: 'on',
            show_phone: 'on',
            show_link: 'on',
            link_phone: 'on',
            link_email: 'on',
            link_url: 'on',
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
  registerModule(metadata, eventPeopleModule);
});
