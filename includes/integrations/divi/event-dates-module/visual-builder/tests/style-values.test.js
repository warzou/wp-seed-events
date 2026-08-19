'use strict';

const assert = require('assert');
const path = require('path');

const {
  BREAKPOINTS,
  LIST_FIELDS,
  applyListStyleVariables,
  listRequestOptions,
  normalizeListStyles,
  previewListStyleCss,
  previewListStyleScope,
  resolveDiviStyleValue,
} = require(path.resolve(__dirname, '../src/divi-style-values.js'));

let assertions = 0;
const check = (condition, message) => {
  assertions += 1;
  assert.ok(condition, message);
};

check(BREAKPOINTS.join(',') === 'desktop,tablet,phone', 'breakpoint inventory differs');
check(Object.keys(LIST_FIELDS).join(',') === 'markerType,markerPosition,leftIndent,occurrenceGap,markerColor', 'list field inventory differs');

check(resolveDiviStyleValue('square', 'desktop', 'value', 'markerType', 'disc') === 'square', 'scalar history failed');
check(
  resolveDiviStyleValue({ desktop: { value: 'circle' } }, 'desktop', 'value', 'markerType', 'disc') === 'circle',
  'legacy responsive scalar failed',
);
check(
  resolveDiviStyleValue({ desktop: { value: { markerType: 'none' } } }, 'desktop', 'value', 'markerType', 'disc') === 'none',
  'nested Divi 5 value failed',
);
check(
  resolveDiviStyleValue({ desktop: { value: 'disc', hover: 'square' } }, 'desktop', 'hover', 'markerType', 'none') === 'square',
  'hover state failed',
);
check(
  resolveDiviStyleValue({ desktop: { value: 'disc' }, tablet: { value: 'circle' } }, 'phone', 'value', 'markerType', 'none') === 'circle',
  'responsive inheritance failed',
);
check(resolveDiviStyleValue({}, 'phone', 'value', 'markerType', 'none') === 'none', 'default fallback failed');

const attrs = {
  listStyle: {
    advanced: {
      markerType: {
        desktop: { value: { markerType: 'none' } },
        tablet: { value: 'circle' },
        phone: { value: { markerType: 'square' } },
      },
      markerPosition: {
        desktop: { value: 'outside' },
        tablet: { value: { markerPosition: 'inside' } },
      },
      leftIndent: {
        desktop: { value: '2.5em' },
        tablet: { value: { leftIndent: '3rem' } },
        phone: { value: '18px' },
      },
      occurrenceGap: {
        desktop: { value: { occurrenceGap: '0px' } },
        tablet: { value: '8px' },
        phone: { value: '4px' },
      },
      markerColor: {
        desktop: { value: '#123456' },
        tablet: { value: { markerColor: 'rgb(1, 2, 3)' } },
        phone: { value: 'var(--event-marker)' },
      },
    },
  },
};
const styles = normalizeListStyles(attrs);
check(styles.desktop.markerType === 'none', 'desktop marker failed');
check(styles.desktop.leftIndent === '0px', 'none marker default indent was not reset');
check(styles.tablet.markerType === 'circle', 'tablet marker failed');
check(styles.tablet.markerPosition === 'inside', 'tablet position failed');
check(styles.tablet.leftIndent === '3rem', 'tablet indent failed');
check(styles.tablet.occurrenceGap === '8px', 'tablet gap failed');
check(styles.tablet.markerColor === 'rgb(1, 2, 3)', 'tablet color failed');
check(styles.phone.markerType === 'square', 'phone marker failed');
check(styles.phone.markerPosition === 'inside', 'phone position inheritance failed');
check(styles.phone.leftIndent === '18px', 'phone indent failed');
check(styles.phone.markerColor === 'var(--event-marker)', 'phone CSS variable color failed');

const previewScope = previewListStyleScope(styles);
const previewCss = previewListStyleCss(previewScope);
check(previewScope === previewListStyleScope(styles), 'preview scope is not deterministic');
check(previewScope !== previewListStyleScope({ ...styles, desktop: { ...styles.desktop, markerType: 'disc' } }), 'preview scope ignores style changes');
check(previewCss.includes(`.${previewScope} > .wp-seed-event-date::before`), 'preview pseudo-marker scope is missing');
check(previewCss.includes('@media (max-width: 980px)'), 'preview tablet style is missing');
check(previewCss.includes('@media (max-width: 767px)'), 'preview phone style is missing');
check(previewCss.includes('padding-block-end: 0 !important'), 'preview trailing padding guard is missing');

const request = listRequestOptions(styles);check(request.list_marker_type === 'none', 'request marker must use desktop');
check(request.list_indent === '0px', 'request indent must use normalized desktop');
check(request.occurrence_gap === '0px', 'request gap differs');

const properties = {};
applyListStyleVariables({
  style: {
    setProperty: (property, value) => {
      properties[property] = value;
    },
  },
}, styles);
BREAKPOINTS.forEach((breakpoint) => {
  Object.values({
    markerType: 'marker-type',
    markerPosition: 'marker-position',
    leftIndent: 'list-indent',
    occurrenceGap: 'occurrence-gap',
    markerColor: 'marker-color',
  }).forEach((property) => {
    check(
      Object.prototype.hasOwnProperty.call(properties, `--wp-seed-event-dates-${property}-${breakpoint}`),
      `missing CSS variable: ${property}/${breakpoint}`,
    );
  });
});

const invalid = normalizeListStyles({
  listStyle: {
    advanced: {
      markerType: { desktop: { value: 'url(x)' } },
      markerPosition: { desktop: { value: 'fixed' } },
      leftIndent: { desktop: { value: '1px;display:none' } },
      occurrenceGap: { desktop: { value: 'calc(100%)' } },
      markerColor: { desktop: { value: 'red;background:black' } },
    },
  },
});
check(invalid.desktop.markerType === 'none', 'unsafe marker did not reset');
check(invalid.desktop.markerPosition === 'outside', 'unsafe position did not reset');
check(invalid.desktop.leftIndent === '0px', 'unsafe indent did not reset');
check(invalid.desktop.occurrenceGap === '0px', 'unsafe gap did not reset');
check(invalid.desktop.markerColor === '', 'unsafe color did not reset');

const reset = normalizeListStyles({});
BREAKPOINTS.forEach((breakpoint) => {
  check(reset[breakpoint].markerType === 'none', `reset marker failed on ${breakpoint}`);
  check(reset[breakpoint].markerPosition === 'outside', `reset position failed on ${breakpoint}`);
  check(reset[breakpoint].leftIndent === '0px', `reset indent failed on ${breakpoint}`);
  check(reset[breakpoint].occurrenceGap === '0px', `reset gap failed on ${breakpoint}`);
  check(reset[breakpoint].markerColor === '', `reset color failed on ${breakpoint}`);
});

console.log(`Divi event dates style values: ${assertions}/${assertions} assertions passed.`);
