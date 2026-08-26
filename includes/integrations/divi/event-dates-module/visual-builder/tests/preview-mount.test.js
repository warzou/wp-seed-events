'use strict';

const assert = require('assert');
const path = require('path');
const React = require('react');
const { act } = require('react');
const { createRoot } = require('react-dom/client');
const { JSDOM } = require('jsdom');
const { Map, fromJS } = require('immutable');

const root = path.resolve(__dirname, '..');
const bundlePath = path.join(root, 'build', 'wp-seed-events-event-dates.js');
const dom = new JSDOM('<!doctype html><html><body></body></html>', { url: 'https://example.test/' });

global.IS_REACT_ACT_ENVIRONMENT = true;
global.window = dom.window;
global.document = dom.window.document;
Object.defineProperty(global, 'navigator', { configurable: true, value: dom.window.navigator });
global.AbortController = dom.window.AbortController;
global.URLSearchParams = dom.window.URLSearchParams;

let registeredModule;
const loopItems = [
  { postId: 2414, post_type: 'wp_seed_event' },
  { postId: 2417, post_type: 'wp_seed_event' },
];
const requestLog = [];
const unexpectedErrors = [];
const unhandledRejections = [];
const originalConsoleError = console.error;
console.error = (...args) => unexpectedErrors.push(args.map(String).join(' '));
const rejectionHandler = (reason) => unhandledRejections.push(reason);
process.on('unhandledRejection', rejectionHandler);

const useFetch = () => {
  const [result, setResult] = React.useState({ response: { html: '' }, isLoading: true });
  return {
    ...result,
    fetch: ({ restRoute }) => {
      const url = new URL(restRoute, 'https://example.test/');
      const eventId = Number(url.searchParams.get('post_id'));
      const allDates = eventId === 2414 ? ['10/10/2026'] : ['13/10/2026', '03/11/2026'];
      const mode = url.searchParams.get('mode');
      const dates = mode === 'last' ? allDates.slice(-1) : (mode === 'first' || mode === 'next' ? allDates.slice(0, 1) : allDates);
      const title = url.searchParams.get('title') || '';
      const showDate = url.searchParams.get('show_dates') !== 'off';
      const time = url.searchParams.get('show_times') === 'off' ? '' : '<span class="wp-seed-event-date__time">20:00</span>';
      const calendar = url.searchParams.get('show_calendar_links') === 'off' ? '' : '<a class="wp-seed-event-calendar-link">Calendrier</a>';
      const layouts = ['desktop', 'tablet', 'phone'].map((breakpoint) => (
        `is-time-layout-${breakpoint}-${url.searchParams.get(`time_layout${breakpoint === 'desktop' ? '' : `_${breakpoint}`}`) || 'below'}`
      )).join(' ');
      const inlineClass = url.searchParams.get('time_layout') === 'inline' ? ' is-time-inline' : '';
      const hasInlineLayout = ['time_layout', 'time_layout_tablet', 'time_layout_phone']
        .some((parameter) => url.searchParams.get(parameter) === 'inline');
      const separator = url.searchParams.get('show_separator') === 'on' && showDate && time && hasInlineLayout
        ? `<span class="wp-seed-event-date__separator" aria-hidden="true">${url.searchParams.get('separator_character') || '\u2014'}</span>`
        : '';
      const html = `<section class="wp-seed-event-section--dates ${layouts}">${title ? `<h2 class="wp-seed-event-dates__title">${title}</h2>` : ''}<ul class="wp-seed-event-dates">${dates.map((date) => `<li class="wp-seed-event-date${inlineClass}">${showDate ? `<time class="wp-seed-event-date__date">${date}</time>` : ''}${separator}${time}${calendar}</li>`).join('')}</ul></section>`;
      requestLog.push(Object.fromEntries(url.searchParams.entries()));
      setResult({ response: { html }, isLoading: false });
      return Promise.resolve();
    },
  };
};

window.vendor = {
  React,
  ReactDOM: require('react-dom'),
  wp: { hooks: { addAction: (hook, namespace, callback) => callback(), addFilter: () => {} } },
};
global.vendor = window.vendor;
const plainAttrs = (value) => (value && typeof value.toJS === 'function' ? value.toJS() : value);
const dateLineHeight = (attrs) => (
  plainAttrs(attrs)?.dateStyle?.decoration?.font?.font?.desktop?.value?.lineHeight ?? 'normal'
);

window.divi = {
  data: { select: () => ({ getModuleLoopData: () => ({ queryItems: loopItems }) }) },
  module: {
    ElementComponents: () => null,
    ModuleContainer: ({ attrs, children }) => React.createElement(
      'div',
      { 'data-module-container': true },
      React.createElement('style', null, '.wp-seed-event-date__date { line-height: ' + dateLineHeight(attrs) + '; }'),
      children,
    ),
    StyleContainer: ({ children }) => React.createElement(React.Fragment, null, children),
    elementClassnames: () => '',
  },
  moduleLibrary: {
    getCurrentPageSetting: () => ({}),
    registerFolder: () => {},
    registerModule: (metadata, moduleDefinition) => { registeredModule = moduleDefinition; },
  },
  rest: { useFetch },
};

class ErrorBoundary extends React.Component {
  constructor(props) {
    super(props);
    this.state = { error: null };
  }

  static getDerivedStateFromError(error) {
    return { error };
  }

  render() {
    return this.state.error
      ? React.createElement('div', { 'data-error': this.state.error.message })
      : this.props.children;
  }
}

const elements = {
  scriptData: () => null,
  style: () => null,
  styleComponents: () => null,
};
const defaultAttrs = {
  content: { innerContent: { desktop: { value: {} } } },
  module: { decoration: {} },
};
const customImmutableAttrs = Map({
  content: Map({ innerContent: Map({ desktop: Map({ value: Map() }) }) }),
  listStyle: Map({ advanced: Map({
    markerType: Map({
      desktop: Map({ value: 'square' }),
      tablet: Map({ value: 'circle' }),
      phone: Map({ value: 'none' }),
    }),
    markerPosition: Map({ desktop: Map({ value: 'inside' }) }),
    leftIndent: Map({ desktop: Map({ value: '3em' }), phone: Map({ value: '0px' }) }),
    occurrenceGap: Map({ desktop: Map({ value: '8px' }), tablet: Map({ value: '4px' }) }),
    markerColor: Map({ desktop: Map({ value: '#123456' }) }),
  }) }),
  module: Map({ decoration: Map() }),
});

const mountPreview = async (attrs, loopIndex) => {
  const container = document.createElement('div');
  document.body.appendChild(container);
  const reactRoot = createRoot(container);
  await act(async () => {
    reactRoot.render(React.createElement(ErrorBoundary, null, registeredModule.renderers.edit({
      attrs,
      elements,
      id: 'dates-module',
      name: 'wp-seed-events/event-dates',
      parentId: 'loop-9w1rqis3ro',
      loopIndex,
    })));
    await Promise.resolve();
  });
  const error = container.querySelector('[data-error]')?.getAttribute('data-error') ?? '';
  const text = container.textContent;
  const list = container.querySelector('.wp-seed-event-dates');
  const styles = list ? {
    desktopMarker: list.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'),
    tabletMarker: list.style.getPropertyValue('--wp-seed-event-dates-marker-type-tablet'),
    phoneMarker: list.style.getPropertyValue('--wp-seed-event-dates-marker-type-phone'),
    desktopPosition: list.style.getPropertyValue('--wp-seed-event-dates-marker-position-desktop'),
    desktopIndent: list.style.getPropertyValue('--wp-seed-event-dates-list-indent-desktop'),
    phoneIndent: list.style.getPropertyValue('--wp-seed-event-dates-list-indent-phone'),
    desktopGap: list.style.getPropertyValue('--wp-seed-event-dates-occurrence-gap-desktop'),
    tabletGap: list.style.getPropertyValue('--wp-seed-event-dates-occurrence-gap-tablet'),
    desktopColor: list.style.getPropertyValue('--wp-seed-event-dates-marker-color-desktop'),
  } : {};
  await act(async () => reactRoot.unmount());
  container.remove();
  return { error, styles, text };
};

const dynamicAttrs = {
  content: {
    innerContent: {
      desktop: {
        value: {
          title: 'Agenda test',
          show_title: 'on',
          heading_level: 'h4',
          date_selection: 'last',
          show_cancelled: 'off',
          show_times: 'off',
          format: 'short',
          show_calendar_links: 'off',
        },
      },
    },
  },
  listStyle: {
    advanced: {
      markerType: { desktop: { value: 'none' }, tablet: { value: 'square' }, phone: { value: 'circle' } },
      markerPosition: { desktop: { value: 'inside' } },
      leftIndent: { desktop: { value: '0px' }, tablet: { value: '24px' } },
      occurrenceGap: { desktop: { value: '9px' }, phone: { value: '3px' } },
      markerColor: { desktop: { value: 'var(--EventMarker)' } },
    },
  },
  module: { decoration: {} },
  dateStyle: { decoration: { font: { font: { desktop: { value: { lineHeight: '5em' } } } } } },
};

const exerciseDynamicUpdate = async () => {
  const container = document.createElement('div');
  document.body.appendChild(container);
  const reactRoot = createRoot(container);
  const render = async (attrs) => {
    await act(async () => {
      reactRoot.render(React.createElement(ErrorBoundary, null, registeredModule.renderers.edit({
        attrs,
        elements,
        id: 'dates-module',
        name: 'wp-seed-events/event-dates',
        parentId: 'loop-9w1rqis3ro',
        loopIndex: 1,
      })));
      await Promise.resolve();
    });
  };

  await render(defaultAttrs);
  const untouchedRequest = requestLog.at(-1);
  ['title', 'show_title', 'heading_level'].forEach((field) => {
    assert.ok(!Object.prototype.hasOwnProperty.call(untouchedRequest, field), `Untouched preview sent ${field}`);
  });
  assert.strictEqual(container.querySelector('.wp-seed-event-dates__title'), null);
  const firstRequestCount = requestLog.length;
  await render(dynamicAttrs);
  const dynamicRequest = requestLog.at(-1);
  const dynamicList = container.querySelector('.wp-seed-event-dates');
  assert.ok(
    dynamicList.classList.contains('has-custom-list-style'),
    'The React preview depends on a server-side list class that may be stale after live style changes.',
  );
  assert.ok(requestLog.length > firstRequestCount, 'Content changes did not invalidate the preview request.');
  assert.deepStrictEqual({
    post_id: dynamicRequest.post_id,
    loop_id: dynamicRequest.loop_id,
    title: dynamicRequest.title,
    show_title: dynamicRequest.show_title,
    heading_level: dynamicRequest.heading_level,
    mode: dynamicRequest.mode,
    scope: dynamicRequest.scope,
    show_cancelled: dynamicRequest.show_cancelled,
    show_times: dynamicRequest.show_times,
    format: dynamicRequest.format,
    show_calendar_links: dynamicRequest.show_calendar_links,
  }, {
    post_id: '2417', loop_id: '2417', title: 'Agenda test', show_title: 'on', heading_level: 'h4',
    mode: 'last', scope: 'all', show_cancelled: 'off', show_times: 'off',
    format: 'short', show_calendar_links: 'off',
  });
  assert.ok(container.textContent.includes('Agenda test'));

  assert.ok(!container.textContent.includes('13/10/2026'));
  assert.ok(container.textContent.includes('03/11/2026'));
  assert.ok(!container.querySelector('.wp-seed-event-date__time'));
  assert.ok(!container.querySelector('.wp-seed-event-calendar-link'));
  assert.strictEqual(dynamicList.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'), 'none');
  assert.strictEqual(dynamicList.style.getPropertyValue('--wp-seed-event-dates-marker-type-tablet'), 'square');
  assert.strictEqual(dynamicList.style.getPropertyValue('--wp-seed-event-dates-marker-type-phone'), 'circle');
  assert.strictEqual(dynamicList.style.getPropertyValue('--wp-seed-event-dates-marker-color-desktop'), 'var(--EventMarker)');

  const resavedLegacyAttrs = JSON.parse(JSON.stringify({
    ...dynamicAttrs,
    content: {
      innerContent: {
        desktop: {
          value: {
            ...dynamicAttrs.content.innerContent.desktop.value,
            show_times: 'on',
          },
        },
      },
    },
  }));
  await render(resavedLegacyAttrs);
  const resavedLegacyRequest = requestLog.at(-1);
  assert.deepStrictEqual({
    title: resavedLegacyRequest.title,
    show_title: resavedLegacyRequest.show_title,
    heading_level: resavedLegacyRequest.heading_level,
  }, { title: 'Agenda test', show_title: 'on', heading_level: 'h4' });
  assert.ok(container.textContent.includes('Agenda test'));

  await render(dynamicAttrs);

  const requestCountBeforeResponsiveOnly = requestLog.length;
  const responsiveOnlyAttrs = {
    ...dynamicAttrs,
    listStyle: {
      advanced: {
        ...dynamicAttrs.listStyle.advanced,
        markerType: { desktop: { value: 'none' }, tablet: { value: 'disc' }, phone: { value: 'square' } },
      },
    },
  };
  await render(responsiveOnlyAttrs);
  const responsiveList = container.querySelector('.wp-seed-event-dates');
  assert.strictEqual(requestLog.length, requestCountBeforeResponsiveOnly, 'Responsive-only style change caused an unnecessary REST request.');
  assert.strictEqual(responsiveList.style.getPropertyValue('--wp-seed-event-dates-marker-type-tablet'), 'disc');
  assert.strictEqual(responsiveList.style.getPropertyValue('--wp-seed-event-dates-marker-type-phone'), 'square');

  await render(defaultAttrs);
  const resetList = container.querySelector('.wp-seed-event-dates');
	assert.strictEqual(resetList.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'), 'none');
	assert.strictEqual(resetList.style.getPropertyValue('--wp-seed-event-dates-marker-type-tablet'), 'none');
	assert.strictEqual(resetList.style.getPropertyValue('--wp-seed-event-dates-marker-type-phone'), 'none');
  assert.strictEqual(container.querySelector('[data-error]'), null);
  await act(async () => reactRoot.unmount());
  container.remove();
};

const exerciseComposableLayouts = async (immutable) => {
  const attrs = {
    content: {
      innerContent: {
        desktop: { value: { show_dates: 'off', show_times: 'on', time_layout: 'inline' } },
        tablet: { value: { time_layout: 'below' } },
        phone: { value: {} },
      },
    },
    module: { decoration: {} },
  };
  const before = requestLog.length;
  const result = await mountPreview(immutable ? fromJS(attrs) : attrs, 0);
  const request = requestLog.at(-1);
  assert.strictEqual(result.error, '');
  assert.ok(requestLog.length > before);
  assert.strictEqual(request.show_dates, 'off');
  assert.strictEqual(request.show_times, 'on');
  assert.strictEqual(request.time_layout, 'inline');
  assert.strictEqual(request.time_layout_tablet, 'below');
  assert.strictEqual(request.time_layout_phone, 'below');
  assert.strictEqual(request.responsive_time_layout_requested, 'true');
  assert.ok(!result.text.includes('10/10/2026'));
  assert.ok(result.text.includes('20:00'));
};

const exerciseSeparator = async (immutable) => {
  const container = document.createElement('div');
  document.body.appendChild(container);
  const reactRoot = createRoot(container);
  const render = async (attrs) => {
    await act(async () => {
      reactRoot.render(React.createElement(ErrorBoundary, null, registeredModule.renderers.edit({
        attrs: immutable ? fromJS(attrs) : attrs,
        elements,
        id: 'dates-separator-module',
        name: 'wp-seed-events/event-dates',
        parentId: immutable ? 'loop-separator-immutable' : 'loop-separator-simple',
        loopIndex: 1,
      })));
      await Promise.resolve();
    });
  };
  const attrs = {
    content: {
      innerContent: {
        desktop: { value: { show_dates: 'on', show_times: 'on', time_layout: 'inline', show_separator: 'on', separator_character: '·' } },
        tablet: { value: { time_layout: 'below' } },
        phone: { value: { time_layout: 'inline' } },
      },
    },
    separatorStyle: {
      advanced: {
        color: { desktop: { value: '#123456' } },
        fontSize: { desktop: { value: '1.25em' }, tablet: { value: '18px' } },
        spaceBefore: { desktop: { value: '4px' } },
        spaceAfter: { desktop: { value: '6px' }, phone: { value: '10px' } },
      },
    },
    module: { decoration: {} },
  };

  await render(attrs);
  const separator = container.querySelector('.wp-seed-event-date__separator');
  assert.ok(separator, 'Opt-in separator did not mount.');
  assert.strictEqual(separator.textContent, '·');
  assert.strictEqual(separator.style.getPropertyValue('--wp-seed-event-dates-separator-color-desktop'), '#123456');
  assert.strictEqual(separator.style.getPropertyValue('--wp-seed-event-dates-separator-size-tablet'), '18px');
  assert.strictEqual(separator.style.getPropertyValue('--wp-seed-event-dates-separator-after-phone'), '10px');
  const request = requestLog.at(-1);
  assert.deepStrictEqual({
    show_separator: request.show_separator,
    separator_character: request.separator_character,
    time_layout: request.time_layout,
    time_layout_tablet: request.time_layout_tablet,
    time_layout_phone: request.time_layout_phone,
  }, {
    show_separator: 'on', separator_character: '·', time_layout: 'inline', time_layout_tablet: 'below', time_layout_phone: 'inline',
  });

  const requestCount = requestLog.length;
  await render({
    ...attrs,
    separatorStyle: {
      ...attrs.separatorStyle,
      advanced: { ...attrs.separatorStyle.advanced, color: { desktop: { value: '#654321' } } },
    },
  });
  assert.strictEqual(requestLog.length, requestCount, 'Separator-only live style change triggered a REST request.');
  assert.strictEqual(
    container.querySelector('.wp-seed-event-date__separator').style.getPropertyValue('--wp-seed-event-dates-separator-color-desktop'),
    '#654321',
  );

  await render({
    ...attrs,
    content: { innerContent: { desktop: { value: { show_dates: 'on', show_times: 'on', time_layout: 'below', show_separator: 'on' } } } },
  });
  assert.strictEqual(container.querySelector('.wp-seed-event-date__separator'), null, 'Stacked-only layout rendered a separator.');
  await render(defaultAttrs);
  assert.strictEqual(container.querySelector('.wp-seed-event-date__separator'), null, 'Untouched historical attrs rendered a separator.');

  await act(async () => reactRoot.unmount());
  container.remove();
};
const liveStyleAttrs = (markerType, lineHeight, tabletMarker = markerType, phoneMarker = tabletMarker) => ({
  ...dynamicAttrs,
  dateStyle: { decoration: { font: { font: { desktop: { value: { lineHeight } } } } } },
  listStyle: {
    advanced: {
      ...dynamicAttrs.listStyle.advanced,
      markerType: {
        desktop: { value: { markerType } },
        tablet: { value: { markerType: tabletMarker } },
        phone: { value: { markerType: phoneMarker } },
      },
    },
  },
});

const exerciseLiveStyleSequence = async (immutable) => {
  const container = document.createElement('div');
  document.body.appendChild(container);
  const reactRoot = createRoot(container);
  const render = async (attrs) => {
    await act(async () => {
      reactRoot.render(React.createElement(ErrorBoundary, null, registeredModule.renderers.edit({
        attrs: immutable ? fromJS(attrs) : attrs,
        elements,
        id: 'dates-module',
        name: 'wp-seed-events/event-dates',
        parentId: immutable ? 'loop-immutable' : 'loop-simple',
        loopIndex: 1,
      })));
      await Promise.resolve();
    });
    const list = container.querySelector('.wp-seed-event-dates');
    const date = container.querySelector('.wp-seed-event-date__date');
    const style = container.querySelector('[data-wp-seed-event-dates-preview-style]');
    return {
      list,
      lineHeight: window.getComputedStyle(date).lineHeight,
      styleText: style?.textContent ?? '',
    };
  };

  const first = await render(liveStyleAttrs('square', '5em'));
  const requestCount = requestLog.length;
  assert.ok(first.list.classList.contains('has-custom-list-style'));
  assert.ok(first.list.classList.contains('is-marker-square'));
  assert.strictEqual(first.list.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'), 'square');
  assert.strictEqual(first.lineHeight, '5em');

  const lineHeightOnly = await render(liveStyleAttrs('square', '1em'));
  assert.strictEqual(requestLog.length, requestCount, 'A typography-only change triggered a REST request.');
  assert.ok(lineHeightOnly.list.classList.contains('is-marker-square'));
  assert.strictEqual(lineHeightOnly.lineHeight, '1em');

  const none = await render(liveStyleAttrs('none', '1em'));
  assert.strictEqual(requestLog.length, requestCount, 'A marker-only change triggered a REST request.');
  assert.ok(none.list.classList.contains('is-marker-none'));
  assert.strictEqual(none.list.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'), 'none');
  assert.ok(none.styleText.includes('list-style-type: var(--wp-seed-event-dates-current-marker-type) !important'));

  const noneTall = await render(liveStyleAttrs('none', '5em'));
  assert.ok(noneTall.list.classList.contains('is-marker-none'));
  assert.strictEqual(noneTall.lineHeight, '5em');

  const circleResponsive = await render(liveStyleAttrs('circle', '2em', 'square', 'none'));
  assert.ok(circleResponsive.list.classList.contains('is-marker-circle'));
  assert.strictEqual(circleResponsive.list.style.getPropertyValue('--wp-seed-event-dates-marker-type-desktop'), 'circle');
  assert.strictEqual(circleResponsive.list.style.getPropertyValue('--wp-seed-event-dates-marker-type-tablet'), 'square');
  assert.strictEqual(circleResponsive.list.style.getPropertyValue('--wp-seed-event-dates-marker-type-phone'), 'none');
  assert.strictEqual(circleResponsive.lineHeight, '2em');
  assert.strictEqual(requestLog.length, requestCount, 'Responsive style changes triggered a REST request.');

  await act(async () => reactRoot.unmount());
  container.remove();
};

delete require.cache[require.resolve(bundlePath)];require(bundlePath);
assert.ok(registeredModule, 'The Dates module was not registered.');

(async () => {
  const event2414 = await mountPreview(defaultAttrs, 0);
  const event2417 = await mountPreview(customImmutableAttrs, 1);
  assert.strictEqual(event2414.error, '', `2414 preview crashed: ${event2414.error}`);
  assert.strictEqual(event2417.error, '', `2417 preview crashed: ${event2417.error}`);
  assert.ok(event2414.text.includes('10/10/2026'));
  assert.ok(event2417.text.includes('13/10/2026'));
  assert.ok(event2417.text.includes('03/11/2026'));
  assert.deepStrictEqual(event2414.styles, {
    desktopMarker: 'none', tabletMarker: 'none', phoneMarker: 'none',
    desktopPosition: 'outside', desktopIndent: '0px', phoneIndent: '0px',
    desktopGap: '0px', tabletGap: '0px', desktopColor: 'currentColor',
  });
  assert.deepStrictEqual(event2417.styles, {
    desktopMarker: 'square', tabletMarker: 'circle', phoneMarker: 'none',
    desktopPosition: 'inside', desktopIndent: '3em', phoneIndent: '0px',
    desktopGap: '8px', tabletGap: '4px', desktopColor: '#123456',
  });
  await exerciseDynamicUpdate();
  await exerciseComposableLayouts(false);
  await exerciseComposableLayouts(true);
  await exerciseSeparator(false);
  await exerciseSeparator(true);
  await exerciseLiveStyleSequence(false);
  await exerciseLiveStyleSequence(true);
  assert.deepStrictEqual(unexpectedErrors, [], 'Unexpected console.error during preview mounts.');
  assert.deepStrictEqual(unhandledRejections, [], 'Unhandled rejection during preview mounts.');
  process.removeListener('unhandledRejection', rejectionHandler);
  console.error = originalConsoleError;
  console.log('Divi event Dates preview mount: default and responsive Immutable loop props passed.');
})().catch((error) => {
  process.removeListener('unhandledRejection', rejectionHandler);
  console.error = originalConsoleError;
  originalConsoleError(error);
  process.exit(1);
});
