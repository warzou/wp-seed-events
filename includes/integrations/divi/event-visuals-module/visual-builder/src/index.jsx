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
import { resolveCurrentEventContext, toPlainObject } from '../../../visual-builder-event-context';

const { data } = window.divi;

const loopPostIdContext = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';

const publishLayoutDebug = ({ id, parentId, loopIndex, eventId, attrs, root }) => {
  if (!root || typeof window === 'undefined') return;

  const list = root.querySelector('.wp-seed-event-visuals__list');
  const items = list ? Array.from(list.children) : [];
  const computed = list ? window.getComputedStyle(list) : null;
  const rect = (element) => {
    if (!element) return null;
    const value = element.getBoundingClientRect();
    return { top: value.top, left: value.left, right: value.right, bottom: value.bottom, width: value.width, height: value.height };
  };
  const cssRules = [];

  Array.from(document.styleSheets).forEach((sheet) => {
    let rules;
    try { rules = Array.from(sheet.cssRules ?? []); } catch (error) { return; }
    rules.forEach((rule) => {
      const text = rule.cssText ?? '';
      if (text.includes('wp-seed-event-visuals__list') || (id && text.includes(id))) cssRules.push(text);
    });
  });

  const payload = {
    timestamp: new Date().toISOString(),
    id,
    parentId,
    loopIndex,
    eventId,
    eventListStyle: toPlainObject(attrs?.eventListStyle) ?? {},
    computed: computed ? {
      display: computed.display,
      flexDirection: computed.flexDirection,
      columnGap: computed.columnGap,
      rowGap: computed.rowGap,
      justifyContent: computed.justifyContent,
      alignItems: computed.alignItems,
      flexWrap: computed.flexWrap,
    } : null,
    geometry: { list: rect(list), items: items.map(rect) },
    styleSheets: Array.from(document.styleSheets).map((sheet) => sheet.href || 'inline'),
    matchingRules: cssRules,
  };

  try {
    const topWindow = window.top;
    topWindow.__wpSeedEventsVisualsLayoutDebug = topWindow.__wpSeedEventsVisualsLayoutDebug || {};
    topWindow.__wpSeedEventsVisualsLayoutDebug[id || `${parentId}:${loopIndex}`] = payload;
  } catch (error) {
    console.log('[WP Seed Visuals Layout Debug]', JSON.stringify(payload));
  }
};

const getContentValues = (attrs) => attrs?.content?.innerContent?.desktop?.value ?? {};

const normalizeOptions = (attrs) => {
  const values = getContentValues(attrs);
  const valueOrDefault = (value, fallback) => (
    typeof value === 'string' ? value : fallback
  );

  return {
    show_flyer: valueOrDefault(values.show_flyer, 'on'),
    show_visuals: valueOrDefault(values.show_visuals, 'on'),
    show_captions: valueOrDefault(values.show_captions, 'off'),
    image_size: valueOrDefault(values.image_size, 'large'),
    click_action: valueOrDefault(values.click_action, ''),
    link_original: valueOrDefault(values.link_original, 'on'),
    lightbox: valueOrDefault(values.lightbox, 'off'),
    layout: valueOrDefault(values.layout, 'grid'),
    horizontal_gap: valueOrDefault(values.horizontal_gap, '24px'),
    vertical_gap: valueOrDefault(values.vertical_gap, '24px'),
    align_items: valueOrDefault(values.align_items, 'stretch'),
    justify_content: valueOrDefault(values.justify_content, 'flex-start'),
    wrap: valueOrDefault(values.wrap, 'on'),
    columns: valueOrDefault(values.columns, '3'),
    columns_tablet: valueOrDefault(values.columns_tablet, '2'),
    columns_phone: valueOrDefault(values.columns_phone, '1'),
    layout_contract: valueOrDefault(values.layout_contract, ''),
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
    {elements.style({ attrName: 'listStyle' })}
    {elements.style({ attrName: 'eventListStyle' })}
    {elements.style({ attrName: 'gridStyle' })}
    {elements.style({ attrName: 'listLayoutStyle' })}
    {elements.style({ attrName: 'itemStyle' })}
    {elements.style({ attrName: 'figureStyle' })}
    {elements.style({ attrName: 'imageStyle' })}
    {elements.style({ attrName: 'captionStyle' })}
    {elements.style({ attrName: 'imageLinkStyle' })}
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
  const moduleRef = useRef();
  const htmlRef = useRef();
  const [hasError, setHasError] = useState(false);
  const options = normalizeOptions(attrs);
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

  useEffect(() => {
    if (html === '' || options.click_action !== 'lightbox' || !htmlRef.current) {
      return;
    }

    const initializeLightbox = window.et_pb_image_lightbox_init;
    const jquery = window.jQuery;

    if (typeof initializeLightbox === 'function' && typeof jquery === 'function') {
      initializeLightbox(jquery(htmlRef.current).find('.et_pb_lightbox_image'));
    }
  }, [html, options.click_action]);

  const nativeLayout = options.layout_contract === 'native-v1';
  const layoutStateKey = JSON.stringify(toPlainObject(attrs?.eventListStyle) ?? {});

  useEffect(() => {
    const frame = window.requestAnimationFrame(() => publishLayoutDebug({
      id,
      parentId,
      loopIndex,
      eventId: postId,
      attrs,
      root: moduleRef.current,
    }));

    return () => window.cancelAnimationFrame(frame);
  }, [html, id, parentId, loopIndex, postId, layoutStateKey]);

  return (
    <ModuleContainer
      attrs={attrs}
      elements={elements}
      id={id}
      moduleClassName={`wp_seed_events_divi_event_visuals${nativeLayout ? ' is-native-layout' : ''}`}
      name={name}
      scriptDataComponent={ModuleScriptData}
      stylesComponent={ModuleStyles}
      classnamesFunction={moduleClassnames}
    >
      {elements.styleComponents({ attrName: 'module' })}
      <ElementComponents attrs={attrs?.module?.decoration ?? {}} id={id} />
      <div ref={moduleRef} className='et_pb_module_inner'>
        {isLoading && <div role='status'>Chargement des visuels…</div>}
        {!isLoading && hasError && <div role='alert'>L’aperçu des visuels est indisponible.</div>}
        {!isLoading && !hasError && html === '' && (
          <div>Aucun visuel à afficher dans ce contexte.</div>
        )}
        {!isLoading && !hasError && html !== '' && (
          <div ref={htmlRef} className='wp-seed-events-visuals-preview-html' dangerouslySetInnerHTML={{ __html: html }} />
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
            title: '',
            show_title: 'off',
            heading_level: 'h2',
            show_flyer: 'on',
            show_visuals: 'on',
            show_captions: 'off',
            image_size: 'large',
            click_action: 'none',
            link_original: 'on',
            lightbox: 'off',
            layout_contract: 'native-v1',
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
    title: 'WPSEvents',
    icon: '',
    category: 'module',
  });
  registerModule(metadata, eventVisualsModule);
});
