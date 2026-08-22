import React, { useEffect, useMemo, useRef, useState } from 'react';
const { addAction, addFilter } = window.vendor.wp.hooks;
const { ElementComponents, ModuleContainer, StyleContainer, elementClassnames } = window.divi.module;
const { getCurrentPageSetting, registerFolder, registerModule } = window.divi.moduleLibrary;
const { useFetch } = window.divi.rest;
import metadata from './module.json';
import { resolveCurrentEventContext, toPlainObject } from '../../../visual-builder-event-context';
const { data } = window.divi;
const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';
const values = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};
const linkTextValue = (attrs) => {
  const plain = toPlainObject(attrs) ?? {};
  const current = plain?.linkText?.innerContent?.desktop?.value;

  return typeof current === 'string' ? current : undefined;
};
const options = (attrs) => {
  const value = values(attrs);
  const currentLinkText = linkTextValue(attrs);
  return {
    show_document: typeof value.show_document === 'string' ? value.show_document : 'on',
    link_text: typeof currentLinkText === 'string'
      ? currentLinkText
      : (typeof value.link_text === 'string' ? value.link_text : 'Télécharger le document'),
    name_display: typeof value.name_display === 'string' ? value.name_display : 'text_name',
    name_position: typeof value.name_position === 'string' ? value.name_position : 'inline',
  };
};
const ModuleStyles = ({ elements, mode, state, noStyleTag, settings }) => <StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
  {elements.style({ attrName: 'module', styleProps: { disabledOn: { disabledModuleVisibility: settings?.disabledModuleVisibility } } })}
  {elements.style({ attrName: 'linkStyle' })}{elements.style({ attrName: 'nameStyle' })}
</StyleContainer>;
const ModuleScriptData = ({ elements }) => <>{elements.scriptData({ attrName: 'module' })}</>;
const moduleClassnames = ({ classnamesInstance, attrs }) => classnamesInstance.add(elementClassnames({ attrs: attrs?.module?.decoration ?? {} }));
const Preview = (props) => {
  const { attrs, id, name, elements } = props;
  const { fetch, response, isLoading } = useFetch({ html: '' });
  const abortRef = useRef(); const [hasError, setHasError] = useState(false);
  const currentPage = typeof getCurrentPageSetting === 'function' ? getCurrentPageSetting() : {};
  const parentId = typeof props.parentId === 'string' ? props.parentId : '';
  const loopIndex = Number.isInteger(props.loopIndex) ? props.loopIndex : -1;
  const context = resolveCurrentEventContext({ data, attrs, parentId, loopIndex, currentPage });
  const normalized = options(attrs); const optionsKey = JSON.stringify(normalized);
  const request = useMemo(() => ({ post_id: context.eventId, ...(context.eventId > 0 ? { loop_id: context.eventId } : {}), ...normalized }), [context.cacheKey, optionsKey]);
  const route = useMemo(() => '/wp-seed-events/v1/divi-event-document-preview?' + new URLSearchParams(request).toString(), [request]);
  useEffect(() => {
    if (abortRef.current) abortRef.current.abort();
    const controller = new AbortController(); abortRef.current = controller; setHasError(false);
    fetch({ restRoute: route, method: 'GET', signal: controller.signal }).catch((error) => { if (error?.name !== 'AbortError') setHasError(true); });
    return () => controller.abort();
  }, [route]);
  const html = typeof response?.html === 'string' ? response.html : '';
  return <ModuleContainer attrs={attrs} elements={elements} id={id} moduleClassName='wp_seed_events_divi_event_document' name={name} scriptDataComponent={ModuleScriptData} stylesComponent={ModuleStyles} classnamesFunction={moduleClassnames}>
    {elements.styleComponents({ attrName: 'module' })}<ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
    <div className='et_pb_module_inner'>{isLoading && <div role='status'>Chargement du document…</div>}{!isLoading && hasError && <div role='alert'>L’aperçu du document est indisponible.</div>}{!isLoading && !hasError && html === '' && <div>Aucun document à afficher dans ce contexte.</div>}{!isLoading && !hasError && html !== '' && <div dangerouslySetInnerHTML={{ __html: html }} />}</div>
  </ModuleContainer>;
};
const definition = { renderers: { edit: Preview }, placeholderContent: { __loop_post_id: loopPostIdContext, linkText: { innerContent: { desktop: { value: 'Télécharger le document' } } }, content: { innerContent: { desktop: { value: { show_document: 'on', link_text: 'Télécharger le document', name_display: 'text_name', name_position: 'inline' } } } } } };
addFilter('divi.moduleLibrary.moduleMapping', 'wpSeedEvents.eventDocumentFolder', (modules) => { if (modules?.[metadata.name]?.metadata) modules[metadata.name].metadata.folder = 'wp-seed-events'; return modules; });
addAction('divi.moduleLibrary.registerModuleLibraryStore.after', 'wpSeedEvents.eventDocument', () => { registerFolder({ name: 'wp-seed-events', path: '', title: 'WPSEvents', icon: '', category: 'module' }); registerModule(metadata, definition); });
