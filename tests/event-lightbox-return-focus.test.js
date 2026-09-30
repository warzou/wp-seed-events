const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

// Model the browser focus boundary and the native popup lifecycle, including
// its teardown resetting activeElement to BODY before AfterClose.
const document = { activeElement: null, readyState: 'complete' };
const body = { name: 'BODY' }, modal = { name: 'native dialog' };
document.activeElement = body;
document.documentElement = { contains: element => !!element?.connected };
const element = (href, overrides = {}) => ({
  ownerDocument: document, connected: true, href, hidden: false, disabled: false,
  getAttribute(name) { return name === 'href' ? this.href : null; },
  matches(selector) { return selector === ':disabled' ? this.disabled : true; },
  closest() { return this.hidden ? this : null; },
  getClientRects() { return this.hidden ? [] : [{}]; },
  focus(options) { this.lastOptions = options; document.activeElement = this; },
  ...overrides,
});
const first = element('/first.jpg'), second = element('/second.jpg');
let children = [first, second];
const component = { connected: true, querySelectorAll: () => children };
const events = new Map();
const links = {
  length: 2,
  off(names) { names.split(' ').forEach(name => events.delete(name)); return this; },
  on(name, callback) { events.set(name, callback); return this; },
};
const $ = value => value === component ? { find: () => links } : { find: () => ({ each: callback => callback.call(component) }) };
$.fn = { magnificPopup() {} }; $.magnificPopup = { instance: {} };
let load, initCalls = 0;
const window = { jQuery: $, getComputedStyle: () => ({ visibility: 'visible' }),
  et_pb_image_lightbox_init: actual => { assert.equal(actual, links); initCalls++; },
  addEventListener: (name, callback) => { if (name === 'load') load = callback; },
};
const context = vm.createContext({ window, document });
for (const file of ['event-lightbox-focus.js', 'event-visuals-divi.js']) {
  vm.runInContext(fs.readFileSync(path.join(__dirname, '../includes/public', file), 'utf8'), context);
}
const emit = event => { for (const [name, callback] of events) if (name.split('.')[0] === event) callback(); };
const open = trigger => {
  document.activeElement = trigger;
  $.magnificPopup.instance.currItem = { el: [trigger] };
  emit('mfpOpen');
  document.activeElement = modal;
};
const close = method => {
  assert.ok(['Escape', 'close button', 'backdrop'].includes(method));
  emit('mfpClose');
  assert.equal(document.activeElement, modal, 'do not focus before the native trap is removed');
  document.activeElement = body;
  $.magnificPopup.instance.currItem = null;
  emit('mfpAfterClose');
};

for (const method of ['Escape', 'close button', 'backdrop']) {
  open(second); close(method);
  assert.equal(document.activeElement, second, `${method}: return to the exact second trigger, not the first link`);
  assert.equal(second.lastOptions.preventScroll, true);
  open(first); close(method); assert.equal(document.activeElement, first);
}
open(second); load(); close('Escape');
assert.equal(document.activeElement, second, 'load/reinitialization must not forget an already open trigger');
assert.equal(events.size, 2, 'initialization does not duplicate listeners');
assert.equal(initCalls, 2);

open(second); second.connected = false;
const replacement = element('/second.jpg'); children = [first, replacement];
close('close button'); assert.equal(document.activeElement, replacement, 'prefer the equivalent replacement link in the same component');
open(replacement); replacement.connected = false; children = [first];
close('backdrop'); assert.equal(document.activeElement, first, 'removed opener falls back within its own component');
open(first); first.disabled = true; children = [first, element('/next.jpg')];
close('Escape'); assert.equal(document.activeElement, children[1]);
open(children[1]); children[1].connected = false; component.connected = false;
assert.doesNotThrow(() => close('Escape')); assert.equal(document.activeElement, body);
assert.equal(window.wpSeedEventsLightboxFocus.capture(null, null)(), false);

// A usable opener in another native adapter does not need a theme-specific API.
const other = element('/other.jpg'); const restore = window.wpSeedEventsLightboxFocus.capture(other, null);
document.activeElement = body; assert.equal(restore(), true); assert.equal(document.activeElement, other);
document.activeElement = body;
const hidden = element('/hidden.jpg', { hidden: true });
assert.equal(window.wpSeedEventsLightboxFocus.capture(hidden, null)(), false);
assert.equal(document.activeElement, body);
const throwing = element('/throw.jpg', { focus() { throw new Error('focus unavailable'); } });
assert.doesNotThrow(() => window.wpSeedEventsLightboxFocus.capture(throwing, null)());
const legacy = element('/legacy.jpg', { focus(options) { if (options) throw new TypeError('options unsupported'); document.activeElement = this; } });
assert.equal(window.wpSeedEventsLightboxFocus.capture(legacy, null)(), true);
console.log('Lightbox return focus PASS: exact trigger, Escape/X/backdrop, post-teardown timing, reinitialization, replacement/fallback, hidden/disabled/missing nodes, no scroll, safe focus failure.');
