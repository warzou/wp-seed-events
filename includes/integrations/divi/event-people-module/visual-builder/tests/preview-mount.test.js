'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const React = require('react');
const { act } = require('react');
const { createRoot } = require('react-dom/client');
const { JSDOM } = require('jsdom');
const { fromJS } = require('immutable');

const bundlePath = path.resolve(__dirname, '..', 'build', 'wp-seed-events-event-people.js');
const stylesheet = fs.readFileSync(path.resolve(__dirname, '..', '..', '..', '..', '..', 'public', 'event-lists.css'), 'utf8');
const dom = new JSDOM(`<!doctype html><html><head><style>${stylesheet}</style></head><body></body></html>`, { url: 'https://example.test/' });
global.IS_REACT_ACT_ENVIRONMENT = true;
global.window = dom.window;
global.document = dom.window.document;
global.AbortController = dom.window.AbortController;
global.URLSearchParams = dom.window.URLSearchParams;
window.fetch = async () => ({
  ok: true,
  json: async () => ({ types: [
    { key: 'organizer', label: 'Organisateur' },
    { key: 'speaker', label: 'Intervenant' },
    { key: 'contact', label: 'Contact' },
  ] }),
});
Object.defineProperty(global, 'navigator', { configurable: true, value: dom.window.navigator });

let registeredModule;
let registeredMetadata;
const requests = [];
const loopItems = [
  { postId: 2414, post_type: 'wp_seed_event' },
  { postId: 2417, post_type: 'wp_seed_event' },
];

const useFetch = () => {
  const [state, setState] = React.useState({ response: { html: '' }, isLoading: true });
  return {
    ...state,
    fetch: ({ restRoute }) => {
      const url = new URL(restRoute, 'https://example.test/');
      const params = Object.fromEntries(url.searchParams.entries());
      requests.push(params);
      const contacts = [];
      if (params.show_phone !== 'off') {
        const phone = params.phone_clickable === 'off'
          ? `<span class="wp-seed-event-people__phone-text">${params.post_id}-phone</span>`
          : `<a class="wp-seed-event-people__phone-link" href="tel:0102030405">${params.post_id}-phone</a>`;
        contacts.push(`<li class="wp-seed-event-people__contact wp-seed-event-people__phone">${phone}</li>`);
      }
      if (params.show_email !== 'off') contacts.push(`<li class="wp-seed-event-people__contact wp-seed-event-people__email"><a class="wp-seed-event-people__email-link" href="mailto:${params.post_id}@example.test">${params.post_id}@example.test</a></li>`);
      if (params.show_link !== 'off') contacts.push('<li class="wp-seed-event-people__contact wp-seed-event-people__link"><a class="wp-seed-event-people__link-anchor" href="https://example.test/">Site internet</a></li>');
      const separators = params.show_contact_separator === 'on'
        ? contacts.map((contact, index) => index === 0 ? contact : contact.replace('>', `><span class="wp-seed-event-people__contact-separator">${params.contact_separator || '·'}</span>`))
        : contacts;
      const classes = ['desktop', 'tablet', 'phone'].map((breakpoint) => {
        const suffix = breakpoint === 'desktop' ? '' : `_${breakpoint}`;
        const layout = (params[`contact_layout${suffix}`] || 'stacked').replace('-', '_');
        return `is-contact-layout-${breakpoint}-${layout}`;
      }).join(' ');
      const name = params.show_name === 'off' ? '' : `<span class="wp-seed-event-people__name">Person ${params.post_id}</span>`;
      const hasWithName = [params.contact_layout, params.contact_layout_tablet, params.contact_layout_phone].some((layout) => ['with_name', 'with-name'].includes(layout));
      const nameSeparator = params.show_name_contact_separator === 'on' && hasWithName
        ? `<span class="wp-seed-event-people__contact-separator wp-seed-event-people__contact-separator--name">${params.name_contact_separator || '—'}</span>`
        : '';
      const roles = params.show_roles === 'off' ? '' : '<ul class="wp-seed-event-people__roles"><li>Contact</li></ul>';
      const contactsHtml = separators.length ? `<ul class="wp-seed-event-people__contacts">${separators.join('')}</ul>` : '';
      const html = `<section class="wp-seed-event-people-section is-people-composable ${classes}"><ul class="wp-seed-event-people__list"><li class="wp-seed-event-people__item"><div class="wp-seed-event-people__identity-contact-flow">${name}${nameSeparator}${contactsHtml}</div>${roles}</li></ul></section>`;
      setState({ response: { html }, isLoading: false });
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
window.divi = {
  data: { select: () => ({ getModuleLoopData: () => ({ queryItems: loopItems }) }) },
  module: {
    ElementComponents: () => null,
    ModuleContainer: ({ children }) => React.createElement('div', { 'data-module-container': true }, children),
    StyleContainer: ({ children }) => React.createElement(React.Fragment, null, children),
    elementClassnames: () => '',
  },
  moduleLibrary: {
    getCurrentPageSetting: () => ({}),
    registerFolder: () => {},
    registerModule: (metadata, definition) => { registeredMetadata = metadata; registeredModule = definition; },
  },
  rest: { useFetch },
};

const elements = {
  scriptData: () => null,
  style: () => null,
  styleComponents: () => null,
};
const attrs = (value, immutable = false) => {
  const result = {
    content: {
      innerContent: {
        desktop: { value: { people_contract: 'composable-v3', contact_layout: 'inline', ...value } },
        tablet: { value: { contact_layout: 'stacked' } },
        phone: { value: { contact_layout: 'inline' } },
      },
    },
    contactSeparatorStyle: { advanced: {
      color: { desktop: { value: '#123456' } },
      fontSize: { desktop: { value: '18px' } },
      spaceBefore: { desktop: { value: '4px' } },
      spaceAfter: { desktop: { value: '6px' } },
    } },
    nameContactSeparatorStyle: { advanced: {
      color: { desktop: { value: '#654321' } },
      fontSize: { desktop: { value: '20px' } },
      spaceBefore: { desktop: { value: '2px' } },
      spaceAfter: { desktop: { value: '3px' } },
    } },
    eventListStyle: { advanced: {} },
    module: { decoration: {} },
  };
  return immutable ? fromJS(result) : result;
};
const render = async (container, props) => {
  const root = createRoot(container);
  await act(async () => {
    root.render(React.createElement(registeredModule.renderers.edit, { ...props, elements, name: 'wp-seed-events/event-people' }));
    await Promise.resolve();
  });
  return root;
};

require(bundlePath);

(async () => {
  for (let attempt = 0; attempt < 10 && !registeredModule; attempt += 1) {
    await new Promise((resolve) => setImmediate(resolve));
  }
  assert.ok(registeredModule, 'People module did not register.');
  assert.deepStrictEqual(
    Object.values(registeredMetadata.attributes.content.settings.innerContent.items)
      .filter((item) => /^role_/.test(item.subName ?? ''))
      .map((item) => item.label),
    ['Organisateur', 'Intervenant', 'Contact'],
    'Canonical person types were not injected into the executed bundle.',
  );
  assert.deepStrictEqual(
    Object.keys(registeredMetadata.attributes.content.settings.innerContent.items.contactLayout.component.props.options),
    ['stacked', 'inline', 'with_name'],
    'The executed production bundle did not register all three layout options.',
  );
  const first = document.createElement('div');
  const second = document.createElement('div');
  document.body.append(first, second);
  const firstRoot = await render(first, { attrs: attrs({ show_email: 'off', show_link: 'off' }), id: 'people', parentId: 'loop-a', loopIndex: 0 });
  const secondRoot = await render(second, { attrs: attrs({
    show_phone: 'off',
    show_contact_separator: 'on',
    contact_separator: '|',
    show_name_contact_separator: 'on',
    name_contact_separator: ':',
    contact_layout: 'with_name',
  }, true), id: 'people', parentId: 'loop-a', loopIndex: 1 });

  assert.ok(first.textContent.includes('2414-phone'), 'Plain props lost loop item 2414.');
  assert.ok(!first.textContent.includes('@example.test'), 'Phone-only composition leaked email.');
  assert.ok(second.textContent.includes('2417@example.test'), 'Immutable props lost loop item 2417.');
  assert.ok(!second.textContent.includes('2414'), 'Loop context leaked between repeated modules.');
  assert.strictEqual(requests.at(-1).contact_layout_tablet, 'stacked');
  assert.strictEqual(requests.at(-1).contact_layout_phone, 'inline');
  assert.strictEqual(requests.at(-1).contact_layout, 'with_name');
  const nameSeparator = second.querySelector('.wp-seed-event-people__contact-separator--name');
  assert.ok(nameSeparator, 'With-name layout omitted the name boundary separator.');
  assert.strictEqual(nameSeparator.textContent, ':');
  assert.strictEqual(nameSeparator.style.getPropertyValue('--wp-seed-event-people-name-separator-color-desktop'), '#654321');
  const personItem = second.querySelector('.wp-seed-event-people__item');
  const identityContactFlow = second.querySelector('.wp-seed-event-people__identity-contact-flow');
  assert.ok(identityContactFlow, 'With-name runtime DOM omitted the shared identity/contact flow.');
  assert.strictEqual(window.getComputedStyle(personItem).display, 'list-item', 'The outer list item must preserve native/custom markers.');
  assert.strictEqual(window.getComputedStyle(identityContactFlow).display, 'inline-flex', 'With-name did not create one real horizontal flow.');
  const separator = second.querySelector('.wp-seed-event-people__contact-separator:not(.wp-seed-event-people__contact-separator--name)');
  assert.ok(separator, 'Inline separator did not render.');
  assert.strictEqual(separator.textContent, '|');
  assert.strictEqual(separator.style.getPropertyValue('--wp-seed-event-people-separator-color-desktop'), '#123456');

  const runtimeStyle = document.createElement('style');
  const coordinateSelector = registeredMetadata.attributes.contactsStyle.selector.replaceAll('{{selector}}', '[data-module-container]');
  runtimeStyle.textContent = `
    [data-module-container] a { color: rgb(200, 0, 0); font-weight: 400; }
    ${coordinateSelector} { color: rgb(18, 52, 86); font-weight: 700; font-size: 21px; line-height: 31px; letter-spacing: 2px; text-align: right; }
  `;
  document.head.append(runtimeStyle);
  [
    second.querySelector('.wp-seed-event-people__email-link'),
    second.querySelector('.wp-seed-event-people__link-anchor'),
  ].forEach((target) => {
    const computed = window.getComputedStyle(target);
    assert.strictEqual(computed.color, 'rgb(18, 52, 86)', 'Production bundle coordinate color lost to a link rule.');
    assert.strictEqual(computed.fontWeight, '700');
    assert.strictEqual(computed.fontSize, '21px');
    assert.strictEqual(computed.lineHeight, '31px');
    assert.strictEqual(computed.letterSpacing, '2px');
    assert.strictEqual(computed.textAlign, 'right');
  });

  await act(async () => {
    firstRoot.render(React.createElement(registeredModule.renderers.edit, {
      attrs: attrs({ show_phone: 'off', show_email: 'on', show_link: 'off' }),
      elements, id: 'people', name: 'wp-seed-events/event-people', parentId: 'loop-a', loopIndex: 0,
    }));
    await Promise.resolve();
  });
  assert.ok(first.textContent.includes('2414@example.test'), 'Live rerender did not switch composition.');
  assert.ok(!first.textContent.includes('2414-phone'), 'Live rerender retained stale phone HTML.');

  await act(async () => {
    firstRoot.render(React.createElement(registeredModule.renderers.edit, {
      attrs: attrs({ show_phone: 'on', show_email: 'off', show_link: 'off', phone_clickable: 'off' }),
      elements, id: 'people', name: 'wp-seed-events/event-people', parentId: 'loop-a', loopIndex: 0,
    }));
    await Promise.resolve();
  });
  assert.ok(first.querySelector('.wp-seed-event-people__phone-text'), 'Live phone presentation OFF did not render plain text.');
  assert.ok(!first.querySelector('.wp-seed-event-people__phone-link'), 'Live phone presentation OFF retained a link.');

  await act(async () => { firstRoot.unmount(); secondRoot.unmount(); });
  console.log('Divi event people preview mount: plain, Immutable, loop isolation, live rerender and computed link styles PASS');
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
