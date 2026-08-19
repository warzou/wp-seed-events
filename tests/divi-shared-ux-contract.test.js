'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const helper = require(path.join(root, 'includes/integrations/divi/event-dates-module/visual-builder/src/divi-style-values.js'));
const css = fs.readFileSync(path.join(root, 'includes/public/event-lists.css'), 'utf8');
const rendering = fs.readFileSync(path.join(root, 'includes/public/rendering.php'), 'utf8');
const plugin = fs.readFileSync(path.join(root, 'wp-seed-events.php'), 'utf8');
const people = JSON.parse(fs.readFileSync(path.join(root, 'includes/integrations/divi/event-people-module/visual-builder/src/module.json'), 'utf8'));
const visuals = JSON.parse(fs.readFileSync(path.join(root, 'includes/integrations/divi/event-visuals-module/visual-builder/src/module.json'), 'utf8'));
const dates = JSON.parse(fs.readFileSync(path.join(root, 'includes/integrations/divi/event-dates-module/visual-builder/src/module.json'), 'utf8'));

const fakeList = () => {
  const classes = new Set();
  const values = {};
  return {
    classList: { add: (...names) => names.forEach((name) => classes.add(name)) },
    style: { setProperty: (name, value) => { values[name] = value; } },
    classes,
    values,
  };
};

for (const metadata of [dates, people, visuals]) {
  assert.strictEqual(metadata.attributes.content.default.innerContent.desktop.value.show_title, 'on');
  assert.strictEqual(metadata.attributes.content.settings.innerContent.items.showTitle.subName, 'show_title');
}

for (const metadata of [dates, people, visuals]) {
  const attrName = metadata === dates ? 'listStyle' : 'eventListStyle';
  const style = metadata.attributes[attrName];
  assert.deepStrictEqual(Object.keys(style.settings.advanced), ['markerType', 'markerPosition', 'leftIndent', 'occurrenceGap', 'markerColor']);
  assert.strictEqual(style.default.advanced.markerType.desktop.value, 'none');
  assert.strictEqual(style.default.advanced.leftIndent.desktop.value, '0px');
  for (const field of Object.values(style.settings.advanced)) assert.strictEqual(field.item.features.responsive, true);
}

const attrs = {
  eventListStyle: {
    advanced: {
      markerType: { desktop: { value: 'disc' }, tablet: { value: { markerType: 'circle' } }, phone: { value: 'square' } },
      markerPosition: { desktop: { value: 'outside' } },
      leftIndent: { desktop: { value: '2.5em' }, tablet: { value: '1em' } },
      occurrenceGap: { desktop: { value: '4px' } },
      markerColor: { desktop: { value: '#123456' } },
    },
  },
};
const plain = helper.normalizeListStyles(attrs, 'eventListStyle');
const immutable = helper.normalizeListStyles({ toJS: () => attrs }, 'eventListStyle');
assert.deepStrictEqual(immutable, plain);
assert.deepStrictEqual([plain.desktop.markerType, plain.tablet.markerType, plain.phone.markerType], ['disc', 'circle', 'square']);

for (const source of [{}, { toJS: () => ({}) }]) {
  const untouched = helper.normalizeListStyles(source, 'eventListStyle');
  assert.deepStrictEqual(
    [untouched.desktop.markerType, untouched.tablet.markerType, untouched.phone.markerType],
    ['none', 'none', 'none'],
  );
  assert.deepStrictEqual(
    [untouched.desktop.leftIndent, untouched.tablet.leftIndent, untouched.phone.leftIndent],
    ['0px', '0px', '0px'],
  );
}

for (const markerType of ['none', 'disc', 'circle', 'square']) {
  const explicit = helper.normalizeListStyles({
    eventListStyle: { advanced: { markerType: { desktop: { value: markerType } } } },
  }, 'eventListStyle');
  assert.strictEqual(explicit.desktop.markerType, markerType);
  assert.strictEqual(explicit.tablet.markerType, markerType);
  assert.strictEqual(explicit.phone.markerType, markerType);
}

const reset = helper.normalizeListStyles({
  eventListStyle: { advanced: { markerType: { desktop: { value: '' } } } },
}, 'eventListStyle');
assert.strictEqual(reset.desktop.markerType, 'none');

const list = fakeList();
helper.applySharedListStyles(list, plain);
assert.ok(list.classes.has('wp-seed-event-list'));
assert.ok(list.classes.has('has-custom-list-style'));
assert.strictEqual(list.values['--wp-seed-event-list-marker-type-phone'], 'square');
assert.strictEqual(list.values['--wp-seed-event-list-marker-color-tablet'], '#123456');

for (const token of ['list-style-type', '::marker', '::before', 'padding-inline-start', '@media (max-width: 980px)', '@media (max-width: 767px)']) {
  assert.ok(css.includes(token), `Missing shared CSS contract: ${token}`);
}
assert.ok(rendering.includes("'<span class='"));
assert.ok(!rendering.includes("'<strong class=' . $quote . esc_attr( implode( ' ', $name_classes )"));
assert.ok(!plugin.includes('data-wp-seed-event-type-add'));
assert.ok(!plugin.includes('name="wp_seed_new_event_type"'));
assert.ok(plugin.includes('data-wp-seed-person-autocomplete'));
assert.ok(!plugin.includes('data-wp-seed-person-suggestion-search'));
assert.ok(!plugin.includes('Rechercher une personne'));
assert.ok(plugin.includes('normalizeSuggestionText'));
assert.ok(plugin.includes('refreshSuggestionAvailability'));
assert.ok(plugin.includes("field(personPanel,'person_key').val('')"));
assert.ok(plugin.includes("personPanel.data('wpSeedSelectedPersonName'"));

for (const relative of [
  'includes/integrations/divi/event-people-module/visual-builder/build/wp-seed-events-event-people.js',
  'includes/integrations/divi/event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js',
]) {
  const bundle = fs.readFileSync(path.join(root, relative), 'utf8');
  assert.ok(bundle.includes('wp-seed-event-list'));
  assert.ok(bundle.includes('eventListStyle'));
}

console.log('Divi shared UX true-runtime contract: PASS');
