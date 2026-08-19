'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const pluginRoot = path.resolve(__dirname, '..', '..', '..', '..', '..', '..');
const sourceRoot = path.join(pluginRoot, 'includes', 'integrations', 'divi', 'event-dates-module', 'visual-builder');
const metadata = JSON.parse(fs.readFileSync(path.join(sourceRoot, 'src', 'module.json'), 'utf8'));
const reactSource = fs.readFileSync(path.join(sourceRoot, 'src', 'index.jsx'), 'utf8');
const phpSource = fs.readFileSync(path.join(pluginRoot, 'includes', 'integrations', 'divi', 'class-event-dates-module.php'), 'utf8');
const renderer = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'rendering.php'), 'utf8');
const calendarRenderer = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'calendar.php'), 'utf8');
const publicRenderSource = `${renderer}\n${calendarRenderer}`;
const css = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'event-dates.css'), 'utf8');
const computedTest = fs.readFileSync(path.join(sourceRoot, 'tests', 'computed-style.test.js'), 'utf8');

const contentFields = {
  title: ['title', 'divi/text', false, false],
  headingLevel: ['heading_level', 'divi/select', false, false],
  dateSelection: ['date_selection', 'divi/select', false, false],
  showCancelled: ['show_cancelled', 'divi/toggle', false, false],
  showTimes: ['show_times', 'divi/toggle', false, false],
  format: ['format', 'divi/select', false, false],
  showCalendarLinks: ['show_calendar_links', 'divi/toggle', false, false],
  showTitle: ['show_title', 'divi/toggle', false, false],
};
const contentItems = metadata.attributes.content.settings.innerContent.items;
assert.deepStrictEqual(Object.keys(contentItems), Object.keys(contentFields));
for (const [name, expected] of Object.entries(contentFields)) {
  const field = contentItems[name];
  assert.deepStrictEqual([
    field.subName,
    field.component.name,
    field.features.responsive,
    field.features.hover,
  ], expected, `Content field contract differs: ${name}`);
}
assert.deepStrictEqual(Object.keys(contentItems.headingLevel.component.props.options), ['h2', 'h3', 'h4', 'h5', 'h6']);
assert.deepStrictEqual(Object.keys(contentItems.dateSelection.component.props.options), ['next', 'first', 'last', 'all_upcoming', 'all_past', 'all']);
assert.deepStrictEqual(Object.keys(contentItems.format.component.props.options), ['long', 'short']);
assert.deepStrictEqual(Object.keys(contentItems.showCancelled.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.showTimes.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.showCalendarLinks.component.props.options), ['off', 'on']);

const listFields = {
  markerType: ['Type de puce', 'divi/select', 'none'],
  markerPosition: ['Position de la puce', 'divi/select', 'outside'],
  leftIndent: ['Retrait gauche de la liste', 'divi/range', '0px'],
  occurrenceGap: ['Espacement entre occurrences', 'divi/range', '0px'],
  markerColor: ['Couleur de la puce', 'divi/color-picker', ''],
};
const listSettings = metadata.attributes.listStyle.settings.advanced;
for (const [name, expected] of Object.entries(listFields)) {
  const field = listSettings[name].item;
  assert.strictEqual(field.label, expected[0]);
  assert.strictEqual(field.component.name, expected[1]);
  assert.strictEqual(field.features.responsive, true);
  assert.strictEqual(field.features.hover, false);
  assert.strictEqual(metadata.attributes.listStyle.default.advanced[name].desktop.value, expected[2]);
}
assert.deepStrictEqual(Object.keys(listSettings.markerType.item.component.props.options), ['none', 'disc', 'circle', 'square']);
assert.deepStrictEqual(Object.keys(listSettings.markerPosition.item.component.props.options), ['outside', 'inside']);

const styleAttributes = {
  titleStyle: ['{{selector}} .wp-seed-event-dates__title', ['font', 'spacing']],
  dateStyle: ['{{selector}} .wp-seed-event-date__date', ['font']],
  timeStyle: ['{{selector}} .wp-seed-event-date__time', ['font']],
  statusStyle: ['{{selector}} .wp-seed-event-date__status', ['font']],
  calendarLinkStyle: ['{{selector}} .wp-seed-event-calendar-link', ['font']],
  occurrenceStyle: ['{{selector}} .wp-seed-event-date', ['spacing']],
};
let specificStyleFamilies = 0;
for (const [name, expected] of Object.entries(styleAttributes)) {
  const attribute = metadata.attributes[name];
  const decoration = Object.keys(attribute.settings.decoration);
  assert.strictEqual(attribute.selector, expected[0]);
  assert.deepStrictEqual(decoration, expected[1]);
  specificStyleFamilies += decoration.length;
  assert.ok(reactSource.includes(`elements.style({ attrName: '${name}' })`));
  assert.ok(phpSource.includes(`'${name}'`));
}

const nativeModuleFamilies = [
  'background', 'layout', 'sizing', 'spacing', 'border', 'boxShadow', 'filters', 'transform',
  'animation', 'overflow', 'disabledOn', 'transition', 'position', 'zIndex', 'scroll', 'sticky',
];
assert.deepStrictEqual(Object.keys(metadata.attributes.module.settings.decoration), nativeModuleFamilies);
assert.ok(reactSource.includes("attrName: 'module'"));
assert.ok(phpSource.includes("'attrName'   => 'module'"));

const typographyProperties = [
  'fontSize', 'fontWeight', 'fontStretch', 'fontVariationSettings', 'color', 'lineHeight',
  'letterSpacing', 'textAlign', 'textTransform', 'fontStyle', 'textDecorationLine',
  'textDecorationColor', 'textDecorationStyle', 'textDecorationThickness', 'textUnderlineOffset',
  'direction', 'hyphens',
];
for (const property of typographyProperties) {
  assert.ok(computedTest.includes(property), `Computed typography coverage missing: ${property}`);
}

const domContracts = [
  '<section class=', 'wp-seed-event-section--dates', 'wp-seed-event-dates__title',
  '<ul class=', '<li class=', '<time class="wp-seed-event-date__date"',
  'wp-seed-event-date__time', 'wp-seed-event-date__status', 'wp-seed-event-calendar-link',
];
for (const contract of domContracts) assert.ok(publicRenderSource.includes(contract), `DOM contract missing: ${contract}`);
for (const target of ['wp-seed-event-dates__title', 'wp-seed-event-date__date', 'wp-seed-event-date__time', 'wp-seed-event-date__status', 'wp-seed-event-calendar-link']) {
  assert.ok(css.includes(`.wp-seed-event-section--dates .${target}`));
}
assert.ok(css.includes('display: block'));
assert.ok(css.includes('inline-size: 100%'));
assert.ok(css.includes('.wp-seed-event-section--dates > .wp-seed-event-dates'));
assert.ok(css.includes('padding-block-end: 0'));
assert.ok(!css.includes('\nul {'));
assert.ok(!phpSource.includes('get_post_meta('));

const exposedFamilies = Object.keys(contentFields).length
  + Object.keys(listFields).length
  + specificStyleFamilies
  + nativeModuleFamilies.length
  + 2; // Admin label and HTML attributes are native module controls.
assert.strictEqual(exposedFamilies, 38);
assert.strictEqual(metadata.attributes.__loop_post_id.default, '');

console.log(`Divi event Dates functional inventory: ${exposedFamilies} exposed control families verified; hidden loop context is not user-facing.`);
