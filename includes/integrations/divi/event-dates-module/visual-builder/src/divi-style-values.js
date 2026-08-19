(function (root, factory) {
  const api = factory();
  if (typeof module === 'object' && module.exports) module.exports = api;
  if (root) root.wpSeedEventsDiviStyleValues = api;
})(typeof window === 'object' ? window : null, function () {
  const BREAKPOINTS = ['desktop', 'tablet', 'phone'];
  const BREAKPOINT_INHERITANCE = {
    desktop: ['desktop'],
    tablet: ['tablet', 'desktop'],
    phone: ['phone', 'tablet', 'desktop'],
  };
  const LIST_FIELDS = {
    markerType: { defaultValue: 'none', allowed: ['none', 'disc', 'circle', 'square'] },
    markerPosition: { defaultValue: 'outside', allowed: ['outside', 'inside'] },
    leftIndent: { defaultValue: '0px', dimension: true },
    occurrenceGap: { defaultValue: '0px', dimension: true },
    markerColor: { defaultValue: '', color: true },
  };

  const toPlainObject = (value) => (value && typeof value.toJS === 'function' ? value.toJS() : value);
  const isScalar = (value) => ['string', 'number', 'boolean'].includes(typeof value);
  const scalarValue = (value, field) => {
    const plain = toPlainObject(value);
    if (isScalar(plain)) return String(plain);
    if (!plain || typeof plain !== 'object') return undefined;
    if (field && isScalar(plain[field])) return String(plain[field]);
    if (Object.prototype.hasOwnProperty.call(plain, 'value')) return scalarValue(plain.value, field);
    return undefined;
  };

  const resolveDiviStyleValue = (attribute, breakpoint = 'desktop', state = 'value', field = '', fallback = '') => {
    const plain = toPlainObject(attribute);
    const inheritance = BREAKPOINT_INHERITANCE[breakpoint] ?? [breakpoint, 'desktop'];

    for (const candidateBreakpoint of inheritance) {
      const breakpointValue = toPlainObject(plain?.[candidateBreakpoint]);
      const stateCandidates = state === 'value' ? ['value'] : [state, 'value'];
      for (const candidateState of stateCandidates) {
        const resolved = scalarValue(breakpointValue?.[candidateState], field);
        if (resolved !== undefined) return resolved;
      }
      const legacyBreakpointValue = scalarValue(breakpointValue, field);
      if (legacyBreakpointValue !== undefined) return legacyBreakpointValue;
    }

    const legacyStateValue = scalarValue(plain?.[state], field);
    if (legacyStateValue !== undefined) return legacyStateValue;
    const legacyValue = scalarValue(plain, field);
    return legacyValue !== undefined ? legacyValue : fallback;
  };

  const normalizeDimension = (value, fallback) => {
    const normalized = String(value ?? '').trim().toLowerCase();
    return /^(?:0|(?:\d+(?:\.\d+)?|\.\d+)(?:px|em|rem|%|ch))$/.test(normalized)
      ? normalized
      : fallback;
  };
  const normalizeColor = (value) => {
    const normalized = String(value ?? '').trim();
    return /^(?:#[0-9a-f]{3,8}|(?:rgb|hsl)a?\([^;{}]+\)|var\(--[a-z0-9_-]+\))$/i.test(normalized)
      ? normalized
      : '';
  };

  const normalizeListStyles = (attrs, attrName = 'listStyle', defaults = {}) => {
    const plainAttrs = toPlainObject(attrs);
    const advanced = toPlainObject(plainAttrs?.[attrName])?.advanced ?? {};
    const styles = {};

    BREAKPOINTS.forEach((breakpoint) => {
      const values = {};
      Object.entries(LIST_FIELDS).forEach(([field, config]) => {
        let value = resolveDiviStyleValue(
          advanced[field],
          breakpoint,
          'value',
          field,
          defaults[field] ?? config.defaultValue,
        );
        const defaultValue = defaults[field] ?? config.defaultValue;
        if (config.allowed && !config.allowed.includes(value)) value = defaultValue;
        if (config.dimension) value = normalizeDimension(value, defaultValue);
        if (config.color) value = normalizeColor(value);
        values[field] = value;
      });
      if (values.markerType === 'none' && values.leftIndent === '2.5em') values.leftIndent = '0px';
      styles[breakpoint] = values;
    });

    return styles;
  };

  const applySharedListStyles = (list, styles) => {
    if (!list?.style) return;
    list.classList.add('wp-seed-event-list', 'has-custom-list-style');
    const propertyMap = {
      markerType: 'marker-type',
      markerPosition: 'marker-position',
      leftIndent: 'list-indent',
      occurrenceGap: 'item-gap',
      markerColor: 'marker-color',
    };
    BREAKPOINTS.forEach((breakpoint) => {
      Object.entries(propertyMap).forEach(([field, property]) => {
        const value = field === 'markerColor' ? (styles[breakpoint][field] || 'currentColor') : styles[breakpoint][field];
        list.style.setProperty(`--wp-seed-event-list-${property}-${breakpoint}`, value);
      });
    });
  };

  const styleSharedListHtml = (html, listSelector, styles) => {
    if (typeof document !== 'object' || !html) return html;
    const container = document.createElement('div');
    container.innerHTML = html;
    const list = container.querySelector(listSelector);
    applySharedListStyles(list, styles);
    return container.innerHTML;
  };

  const listRequestOptions = (styles) => ({
    list_marker_type: styles.desktop.markerType,
    list_marker_position: styles.desktop.markerPosition,
    list_indent: styles.desktop.leftIndent,
    occurrence_gap: styles.desktop.occurrenceGap,
    marker_color: styles.desktop.markerColor,
  });

  const applyListStyleVariables = (list, styles) => {
    if (!list?.style) return;
    const propertyMap = {
      markerType: 'marker-type',
      markerPosition: 'marker-position',
      leftIndent: 'list-indent',
      occurrenceGap: 'occurrence-gap',
      markerColor: 'marker-color',
    };
    BREAKPOINTS.forEach((breakpoint) => {
      Object.entries(propertyMap).forEach(([field, property]) => {
        const value = field === 'markerColor' ? (styles[breakpoint][field] || 'currentColor') : styles[breakpoint][field];
        list.style.setProperty(`--wp-seed-event-dates-${property}-${breakpoint}`, value);
      });
    });
  };

  const previewListStyleScope = (styles) => {
    const serialized = JSON.stringify(styles);
    let hash = 2166136261;
    for (let index = 0; index < serialized.length; index += 1) {
      hash ^= serialized.charCodeAt(index);
      hash = Math.imul(hash, 16777619);
    }
    return `wp-seed-event-dates-preview-${(hash >>> 0).toString(36)}`;
  };

  const previewListStyleCss = (scope) => `
.${scope} {
  --wp-seed-event-dates-current-marker-type: var(--wp-seed-event-dates-marker-type-desktop, none);
  --wp-seed-event-dates-current-marker-position: var(--wp-seed-event-dates-marker-position-desktop, outside);
  --wp-seed-event-dates-current-list-indent: var(--wp-seed-event-dates-list-indent-desktop, 0px);
  --wp-seed-event-dates-current-occurrence-gap: var(--wp-seed-event-dates-occurrence-gap-desktop, 0);
  --wp-seed-event-dates-current-marker-color: var(--wp-seed-event-dates-marker-color-desktop, currentColor);
  list-style-position: var(--wp-seed-event-dates-current-marker-position) !important;
  list-style-type: var(--wp-seed-event-dates-current-marker-type) !important;
  padding-inline-start: var(--wp-seed-event-dates-current-list-indent) !important;
  padding-block-end: 0 !important;
  margin-inline-start: 0;
}
.${scope} > .wp-seed-event-date {
  display: list-item !important;
  list-style-position: var(--wp-seed-event-dates-current-marker-position) !important;
  list-style-type: var(--wp-seed-event-dates-current-marker-type) !important;
}
.${scope} > .wp-seed-event-date::before {
  content: none !important;
  display: none !important;
}
.${scope} > .wp-seed-event-date + .wp-seed-event-date {
  margin-block-start: var(--wp-seed-event-dates-current-occurrence-gap);
}
.${scope} > .wp-seed-event-date::marker {
  color: var(--wp-seed-event-dates-current-marker-color);
}
@media (max-width: 980px) {
  .${scope} {
    --wp-seed-event-dates-current-marker-type: var(--wp-seed-event-dates-marker-type-tablet, var(--wp-seed-event-dates-marker-type-desktop, none));
    --wp-seed-event-dates-current-marker-position: var(--wp-seed-event-dates-marker-position-tablet, var(--wp-seed-event-dates-marker-position-desktop, outside));
    --wp-seed-event-dates-current-list-indent: var(--wp-seed-event-dates-list-indent-tablet, var(--wp-seed-event-dates-list-indent-desktop, 0px));
    --wp-seed-event-dates-current-occurrence-gap: var(--wp-seed-event-dates-occurrence-gap-tablet, var(--wp-seed-event-dates-occurrence-gap-desktop, 0));
    --wp-seed-event-dates-current-marker-color: var(--wp-seed-event-dates-marker-color-tablet, var(--wp-seed-event-dates-marker-color-desktop, currentColor));
  }
}
@media (max-width: 767px) {
  .${scope} {
    --wp-seed-event-dates-current-marker-type: var(--wp-seed-event-dates-marker-type-phone, var(--wp-seed-event-dates-marker-type-tablet, none));
    --wp-seed-event-dates-current-marker-position: var(--wp-seed-event-dates-marker-position-phone, var(--wp-seed-event-dates-marker-position-tablet, outside));
    --wp-seed-event-dates-current-list-indent: var(--wp-seed-event-dates-list-indent-phone, var(--wp-seed-event-dates-list-indent-tablet, 0px));
    --wp-seed-event-dates-current-occurrence-gap: var(--wp-seed-event-dates-occurrence-gap-phone, var(--wp-seed-event-dates-occurrence-gap-tablet, 0));
    --wp-seed-event-dates-current-marker-color: var(--wp-seed-event-dates-marker-color-phone, var(--wp-seed-event-dates-marker-color-tablet, currentColor));
  }
}`;

  return {
    BREAKPOINTS,
    LIST_FIELDS,
    applySharedListStyles,
    applyListStyleVariables,
    listRequestOptions,
    normalizeListStyles,
    previewListStyleCss,
    previewListStyleScope,
    resolveDiviStyleValue,
    styleSharedListHtml,
  };
});
