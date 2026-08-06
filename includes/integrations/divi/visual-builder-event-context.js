(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  if (root) root.wpSeedEventsDiviContext = api;
})(typeof window === 'object' ? window : null, function () {
  const toPlainObject = (value) => (value && typeof value.toJS === 'function' ? value.toJS() : value);
  const getDiviAttribute = (attrs, key) => (attrs && typeof attrs.get === 'function' ? attrs.get(key) : attrs?.[key]);
  const setDiviAttribute = (attrs, key, value) => (attrs && typeof attrs.set === 'function'
    ? attrs.set(key, value)
    : { ...(attrs ?? {}), [key]: value });
  const positiveId = (value) => {
    const id = Number(value ?? 0);
    return Number.isSafeInteger(id) && id > 0 ? id : 0;
  };
  const getDiviLoopPostId = (attrs) => positiveId(getDiviAttribute(attrs, '__loop_post_id'));
  const getDiviDesktopFieldValue = (attribute, field, fallback = '') => {
    const value = toPlainObject(attribute)?.desktop?.value;
    if (typeof value === 'string') return value;
    const nestedValue = toPlainObject(value)?.[field];
    return typeof nestedValue === 'string' ? nestedValue : fallback;
  };
  const getEventLoopItemContext = (data, loopOwnerId, loopIndex) => {
    if (typeof loopOwnerId !== 'string' || loopOwnerId === '' || !Number.isInteger(loopIndex) || loopIndex < 0) {
      return { eventId: 0, item: null, loopData: null, storeAvailable: false };
    }
    const store = data?.select?.('divi/edit-post');
    const loopData = toPlainObject(store?.getModuleLoopData?.(loopOwnerId));
    const item = Array.isArray(loopData?.queryItems) ? loopData.queryItems[loopIndex] : null;
    const postType = item?.post_type ?? item?.postType ?? null;
    const eventId = positiveId(item?.postId ?? item?.post_id ?? item?.id);
    return { eventId: postType === 'wp_seed_event' ? eventId : 0, item, loopData, storeAvailable: Boolean(store) };
  };
  const getEventLoopPostId = (data, loopOwnerId, loopIndex) => getEventLoopItemContext(data, loopOwnerId, loopIndex).eventId;
  const resolveCurrentEventContext = ({ data, attrs, parentId = '', loopIndex = -1, explicitEventId = 0, currentPage = null } = {}) => {
    const loop = getEventLoopItemContext(data, parentId, loopIndex);
    if (loop.eventId > 0) return { eventId: loop.eventId, source: 'loop-store', item: loop.item, cacheKey: `${parentId}:${loopIndex}:${loop.eventId}` };
    const attrEventId = getDiviLoopPostId(attrs);
    const explicitId = positiveId(explicitEventId) || attrEventId;
    if (explicitId > 0) return { eventId: explicitId, source: attrEventId > 0 ? 'loop-attribute' : 'explicit', item: null, cacheKey: `event:${explicitId}` };
    const page = toPlainObject(currentPage) ?? {};
    const pageType = page.postType ?? page.post_type ?? page.type ?? '';
    const pageId = positiveId(page.id ?? page.postId ?? page.post_id);
    if (pageType === 'wp_seed_event' && pageId > 0) return { eventId: pageId, source: 'event-page', item: null, cacheKey: `event-page:${pageId}` };
    return { eventId: 0, source: 'none', item: null, cacheKey: 'none' };
  };
  const replaceLoopPostId = (attrs, eventId) => (attrs ? setDiviAttribute(attrs, '__loop_post_id', String(eventId)) : attrs);
  const clonePreviewTree = (element, eventId, moduleId, React) => {
    if (!React.isValidElement(element)) return element;
    const props = element.props ?? {};
    const nextProps = {};
    let changed = false;
    if (!moduleId || props.id === moduleId || props.moduleId === moduleId) {
      ['attrs', 'moduleAttrs', 'runtimeModuleAttrs'].forEach((key) => {
        const nextAttrs = replaceLoopPostId(props[key], eventId);
        if (nextAttrs !== props[key]) { nextProps[key] = nextAttrs; changed = true; }
      });
    }
    if (Object.prototype.hasOwnProperty.call(props, 'children')) {
      let childrenChanged = false;
      const nextChildren = React.Children.map(props.children, (child) => {
        const nextChild = clonePreviewTree(child, eventId, moduleId, React);
        childrenChanged = childrenChanged || nextChild !== child;
        return nextChild;
      });
      if (childrenChanged) { nextProps.children = nextChildren; changed = true; }
    }
    return changed ? React.cloneElement(element, nextProps) : element;
  };
  const createEventModulePreviewFilter = ({ React, data, moduleNames }) => {
    const names = new Set(moduleNames ?? []);
    return (element, context) => {
      if (!names.has(context?.name)) return element;
      const eventId = getEventLoopPostId(data, context?.parentId, context?.loopIndex);
      return eventId > 0 ? clonePreviewTree(element, eventId, context?.id ?? null, React) : element;
    };
  };
  return { clonePreviewTree, createEventModulePreviewFilter, getDiviAttribute, getDiviDesktopFieldValue, getDiviLoopPostId, getEventLoopItemContext, getEventLoopPostId, positiveId, replaceLoopPostId, resolveCurrentEventContext, setDiviAttribute, toPlainObject };
});
