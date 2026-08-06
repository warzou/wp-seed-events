(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  if (root) api.register(root);
})(typeof window === 'object' ? window : null, function () {
  const PREFIX = 'wp_seed_events_';
  const parseSourceName = (value, moduleUtils) => {
    if (typeof value !== 'string' || !value.includes(PREFIX)) return '';
    const parsed = moduleUtils?.parseDynamicData?.(value);
    const name = parsed?.value?.name;
    if (typeof name !== 'string') return '';
    const normalized = name.startsWith('loop_') ? name.slice(5) : name;
    return normalized.startsWith(PREFIX) ? normalized : '';
  };
  const replaceDynamicValues = (value, item, moduleUtils) => {
    if (typeof value === 'string') {
      const source = parseSourceName(value, moduleUtils);
      return source ? String(item?.[source] ?? '') : value;
    }
    if (!value || typeof value !== 'object') return value;
    if (typeof value.get === 'function' && typeof value.set === 'function' && typeof value.keySeq === 'function') {
      let next = value;
      value.keySeq().forEach((key) => {
        const current = value.get(key);
        const replaced = replaceDynamicValues(current, item, moduleUtils);
        if (replaced !== current) next = next.set(key, replaced);
      });
      return next;
    }
    let changed = false;
    const next = Array.isArray(value) ? [...value] : { ...value };
    Object.keys(next).forEach((key) => {
      const replaced = replaceDynamicValues(next[key], item, moduleUtils);
      if (replaced !== next[key]) { next[key] = replaced; changed = true; }
    });
    return changed ? next : value;
  };
  const clonePreviewTree = (element, item, React, moduleUtils) => {
    if (!React.isValidElement(element)) return element;
    const props = element.props ?? {};
    const nextProps = {};
    let changed = false;
    ['attrs', 'moduleAttrs', 'runtimeModuleAttrs'].forEach((key) => {
      const next = replaceDynamicValues(props[key], item, moduleUtils);
      if (next !== props[key]) { nextProps[key] = next; changed = true; }
    });
    if (Object.prototype.hasOwnProperty.call(props, 'children')) {
      let childrenChanged = false;
      const children = React.Children.map(props.children, (child) => {
        const next = clonePreviewTree(child, item, React, moduleUtils);
        childrenChanged = childrenChanged || next !== child;
        return next;
      });
      if (childrenChanged) { nextProps.children = children; changed = true; }
    }
    return changed ? React.cloneElement(element, nextProps) : element;
  };
  const createPreviewFilter = ({ React, data, moduleUtils, contextApi }) => (element, context) => {
    const loop = contextApi.getEventLoopItemContext(data, context?.parentId, context?.loopIndex);
    if (loop.eventId === 0 || !loop.item) return element;
    return clonePreviewTree(element, loop.item, React, moduleUtils);
  };
  const register = (windowObject) => {
    if (windowObject.__wpSeedEventsDynamicDataPreviewRegistered) return false;
    const hooks = windowObject.vendor?.wp?.hooks;
    const React = windowObject.vendor?.React;
    const data = windowObject.divi?.data;
    const moduleUtils = windowObject.divi?.moduleUtils;
    const contextApi = windowObject.wpSeedEventsDiviContext;
    if (!hooks?.addFilter || !React?.cloneElement || !data?.select || !moduleUtils?.parseDynamicData || !contextApi) return false;
    hooks.addFilter('divi.module.wrapper.render', 'wpSeedEvents.dynamicEventDataPreview', createPreviewFilter({ React, data, moduleUtils, contextApi }));
    windowObject.__wpSeedEventsDynamicDataPreviewRegistered = true;
    return true;
  };
  return { PREFIX, clonePreviewTree, createPreviewFilter, parseSourceName, register, replaceDynamicValues };
});
