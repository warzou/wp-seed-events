import React from 'react';

const { addAction, addFilter } = window.vendor.wp.hooks;
const {
  ElementComponents,
  ModuleContainer,
  StyleContainer,
  elementClassnames,
} = window.divi.module;
const { registerFolder, registerModule } = window.divi.moduleLibrary;

import metadata from './module.json';

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

const EventPeoplePreview = ({ attrs, id, name, elements }) => (
  <ModuleContainer
    attrs={attrs}
    elements={elements}
    id={id}
    moduleClassName='wp_seed_events_divi_event_people'
    name={name}
    scriptDataComponent={ModuleScriptData}
    stylesComponent={ModuleStyles}
    classnamesFunction={moduleClassnames}
  >
    {elements.styleComponents({ attrName: 'module' })}
    <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
    <div className='et_pb_module_inner'>
      <div>Aperçu disponible sur le frontend dans un contexte événement.</div>
    </div>
  </ModuleContainer>
);

const eventPeopleModule = {
  renderers: { edit: EventPeoplePreview },
  placeholderContent: {
    __loop_post_id: loopPostIdContext,
    content: {
      innerContent: {
        desktop: {
          value: {
            title: 'Contacts et intervenants',
            heading_level: 'h2',
            role: 'all',
            show_roles: 'on',
            show_email: 'on',
            show_phone: 'on',
            show_link: 'on',
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
