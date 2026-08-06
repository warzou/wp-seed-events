const assert = require('assert');
const path = require('path');
const preview = require(path.resolve(__dirname, '../includes/integrations/divi/dynamic-event-data-preview.js'));
const contextApi = require(path.resolve(__dirname, '../includes/integrations/divi/visual-builder-event-context.js'));
const token = (name) => `$variable({"type":"content","value":{"name":"${name}","settings":{}}})$`;
const moduleUtils = { parseDynamicData: (value) => ({ value: { name: JSON.parse(value.slice(10, -2)).value.name } }) };
const registryFields = [
  'title', 'types', 'status', 'next_date', 'next_time', 'display_date', 'display_time',
  'place', 'place_address', 'description', 'excerpt', 'practical_info',
  'event_document_filename', 'url', 'place_url', 'event_document_url', 'communication_visual',
];
assert.strictEqual(registryFields.length, 17);
registryFields.forEach((field) => {
  assert.strictEqual(preview.parseSourceName(token(`wp_seed_events_${field}`), moduleUtils), `wp_seed_events_${field}`);
  assert.strictEqual(preview.parseSourceName(token(`loop_wp_seed_events_${field}`), moduleUtils), `wp_seed_events_${field}`);
});
assert.strictEqual(preview.replaceDynamicValues({ text: token('wp_seed_events_title') }, { wp_seed_events_title: 'Event A' }, moduleUtils).text, 'Event A');
assert.strictEqual(preview.replaceDynamicValues({ url: token('wp_seed_events_url') }, { wp_seed_events_url: 'https://example.test/a/' }, moduleUtils).url, 'https://example.test/a/');
assert.strictEqual(preview.replaceDynamicValues({ image: token('wp_seed_events_communication_visual') }, {}, moduleUtils).image, '');
class FakeMap {
  constructor(values) { this.values = values; }
  get(key) { return this.values[key]; }
  set(key, value) { return new FakeMap({ ...this.values, [key]: value }); }
  keySeq() { return { forEach: (callback) => Object.keys(this.values).forEach(callback) }; }
  toJS() { return this.values; }
}
const immutable = new FakeMap({ text: token('wp_seed_events_title') });
assert.strictEqual(preview.replaceDynamicValues(immutable, { wp_seed_events_title: 'Immutable Event' }, moduleUtils).get('text'), 'Immutable Event');
const data = { select: () => ({ getModuleLoopData: (id) => ({ queryItems: id === 'loop-a' ? [
  { post_type: 'wp_seed_event', postId: 2414, wp_seed_events_title: 'A' },
  { post_type: 'wp_seed_event', postId: 2417, wp_seed_events_title: 'B' },
] : [{ post_type: 'wp_seed_event', postId: 3000, wp_seed_events_title: 'C' }] }) }) };
assert.strictEqual(contextApi.resolveCurrentEventContext({ data, parentId: 'loop-a', loopIndex: 0 }).eventId, 2414);
assert.strictEqual(contextApi.resolveCurrentEventContext({ data, parentId: 'loop-a', loopIndex: 1 }).eventId, 2417);
assert.strictEqual(contextApi.resolveCurrentEventContext({ data, parentId: 'loop-b', loopIndex: 0 }).eventId, 3000);
assert.strictEqual(contextApi.resolveCurrentEventContext({ data, parentId: 'loop-a', loopIndex: 2 }).eventId, 0);
assert.strictEqual(contextApi.resolveCurrentEventContext({ currentPage: { id: 1901, postType: 'page' } }).eventId, 0);
assert.strictEqual(contextApi.resolveCurrentEventContext({ currentPage: { id: 2414, postType: 'wp_seed_event' } }).eventId, 2414);
console.log('Divi generic dynamic data preview contract: 17/17 tokens PASS');
