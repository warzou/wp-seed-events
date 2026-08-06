const getDiviAttribute = (attrs, key) => {
  if (attrs && typeof attrs.get === 'function') {
    return attrs.get(key);
  }

  return attrs?.[key];
};

const setDiviAttribute = (attrs, key, value) => {
  if (attrs && typeof attrs.set === 'function') {
    return attrs.set(key, value);
  }

  return {
    ...(attrs ?? {}),
    [key]: value,
  };
};

const toPlainObject = (value) => (
  value && typeof value.toJS === 'function' ? value.toJS() : value
);

const getDiviDesktopFieldValue = (attribute, field, fallback = '') => {
  const value = toPlainObject(attribute)?.desktop?.value;

  if (typeof value === 'string') {
    return value;
  }

  const nestedValue = toPlainObject(value)?.[field];

  return typeof nestedValue === 'string' ? nestedValue : fallback;
};

const getDiviLoopPostId = (attrs) => {
  const value = Number(getDiviAttribute(attrs, '__loop_post_id') ?? 0);

  return Number.isSafeInteger(value) && value > 0 ? value : 0;
};

const getEventLoopItemContext = (data, loopOwnerId, loopIndex) => {
  if (
    typeof loopOwnerId !== 'string'
    || loopOwnerId === ''
    || !Number.isInteger(loopIndex)
    || loopIndex < 0
  ) {
    return {
      eventId: 0,
      item: null,
      loopData: null,
      storeAvailable: false,
    };
  }

  const store = data?.select?.('divi/edit-post');
  const loopData = toPlainObject(store?.getModuleLoopData?.(loopOwnerId));
  const item = Array.isArray(loopData?.queryItems)
    ? loopData.queryItems[loopIndex]
    : null;
  const postType = item?.post_type ?? item?.postType ?? null;
  const eventId = Number(item?.postId ?? item?.post_id ?? item?.id ?? 0);
  const isEvent = postType === 'wp_seed_event'
    && Number.isSafeInteger(eventId)
    && eventId > 0;

  return {
    eventId: isEvent ? eventId : 0,
    item,
    loopData,
    storeAvailable: Boolean(store),
  };
};

const getEventLoopPostId = (data, loopOwnerId, loopIndex) => (
  getEventLoopItemContext(data, loopOwnerId, loopIndex).eventId
);

const replaceLoopPostId = (attrs, eventId) => {
  if (!attrs) {
    return attrs;
  }

  return setDiviAttribute(attrs, '__loop_post_id', String(eventId));
};

const clonePreviewTree = (element, eventId, moduleId, React) => {
  if (!React.isValidElement(element)) {
    return element;
  }

  const props = element.props ?? {};
  const nextProps = {};
  let changed = false;

  if (props.id === moduleId || props.moduleId === moduleId) {
    ['attrs', 'moduleAttrs', 'runtimeModuleAttrs'].forEach((key) => {
      const nextAttrs = replaceLoopPostId(props[key], eventId);

      if (nextAttrs !== props[key]) {
        nextProps[key] = nextAttrs;
        changed = true;
      }
    });
  }

  if (Object.prototype.hasOwnProperty.call(props, 'children')) {
    let childrenChanged = false;
    const nextChildren = React.Children.map(props.children, (child) => {
      const nextChild = clonePreviewTree(child, eventId, moduleId, React);

      childrenChanged = childrenChanged || nextChild !== child;
      return nextChild;
    });

    if (childrenChanged) {
      nextProps.children = nextChildren;
      changed = true;
    }
  }

  return changed ? React.cloneElement(element, nextProps) : element;
};

const createEventDatesPreviewFilter = ({ React, data }) => (element, context) => {
  const loopOwnerId = context?.parentId ?? null;
  const hasLoopCoordinates = typeof loopOwnerId === 'string'
    && loopOwnerId !== ''
    && Number.isInteger(context?.loopIndex)
    && context.loopIndex >= 0;

  if (
    context?.name !== 'wp-seed-events/event-dates'
    || !hasLoopCoordinates
  ) {
    return element;
  }

  const eventId = getEventLoopItemContext(data, loopOwnerId, context.loopIndex).eventId;

  return eventId > 0
    ? clonePreviewTree(element, eventId, context.id, React)
    : element;
};

module.exports = {
  clonePreviewTree,
  createEventDatesPreviewFilter,
  getDiviAttribute,
  getDiviDesktopFieldValue,
  getDiviLoopPostId,
  getEventLoopItemContext,
  getEventLoopPostId,
  replaceLoopPostId,
  setDiviAttribute,
  toPlainObject,
};
