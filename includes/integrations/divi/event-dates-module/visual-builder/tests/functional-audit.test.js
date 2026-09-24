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
  dateSelection: ['date_selection', 'divi/select', false, false],
  showCancelled: ['show_cancelled', 'divi/toggle', false, false],
  showDates: ['show_dates', 'divi/toggle', false, false],
  format: ['format', 'divi/select', false, false],
  showTimes: ['show_times', 'divi/toggle', false, false],
  timeLayout: ['time_layout', 'divi/select', true, false],
  showSeparator: ['show_separator', 'divi/toggle', false, false],
  separatorCharacter: ['separator_character', 'divi/text', false, false],
  showCalendarLinks: ['show_calendar_links', 'divi/toggle', false, false],
  calendarPresentation: ['calendar_presentation', 'divi/select', false, false],
  calendarLabel: ['calendar_label', 'divi/text', false, false],
  calendarIconPosition: ['calendar_icon_position', 'divi/select', false, false],
  calendarLayout: ['calendar_layout', 'divi/select', false, false],
  showAllCalendar: ['show_all_calendar', 'divi/toggle', false, false],
  allCalendarPresentation: ['all_calendar_presentation', 'divi/select', false, false],
  allCalendarLabel: ['all_calendar_label', 'divi/text', false, false],
  allCalendarIconPosition: ['all_calendar_icon_position', 'divi/select', false, false],
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
const legacyCalendarContentItems = [
  'showCalendarLinks', 'calendarPresentation', 'calendarLabel', 'calendarIconPosition',
  'calendarLayout', 'showAllCalendar', 'allCalendarPresentation', 'allCalendarLabel',
  'allCalendarIconPosition',
];
for (const name of legacyCalendarContentItems) {
  assert.strictEqual(contentItems[name].render, false, `Legacy calendar control is visible: ${name}`);
}
assert.deepStrictEqual(Object.keys(contentItems.dateSelection.component.props.options), ['next', 'first', 'last', 'all_upcoming', 'all_past', 'all']);
assert.deepStrictEqual(Object.keys(contentItems.format.component.props.options), ['long', 'short']);
assert.deepStrictEqual(Object.keys(contentItems.showCancelled.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.showDates.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.showTimes.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.timeLayout.component.props.options), ['inline', 'below']);
assert.deepStrictEqual(Object.keys(contentItems.showSeparator.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.showCalendarLinks.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.calendarPresentation.component.props.options), ['text', 'icon', 'icon_text', 'button']);
assert.deepStrictEqual(Object.keys(contentItems.calendarIconPosition.component.props.options), ['left', 'right']);
assert.deepStrictEqual(Object.keys(contentItems.calendarLayout.component.props.options), ['inline', 'below']);
assert.deepStrictEqual(Object.keys(contentItems.showAllCalendar.component.props.options), ['off', 'on']);
assert.deepStrictEqual(Object.keys(contentItems.allCalendarPresentation.component.props.options), ['text', 'icon', 'icon_text', 'button']);
assert.deepStrictEqual(Object.keys(contentItems.allCalendarIconPosition.component.props.options), ['left', 'right']);

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
  sectionStyle: ['{{selector}} .wp-seed-event-section--dates', []],
  titleStyle: ['{{selector}} .wp-seed-event-dates__title', []],
  dateStyle: ['{{selector}} .wp-seed-event-date__date', ['font']],
  timeStyle: ['{{selector}} .wp-seed-event-date__time', ['font']],
  separatorStyle: ['{{selector}} .wp-seed-event-date__separator', []],
  statusStyle: ['{{selector}} .wp-seed-event-date__status', ['font']],
  calendarLinkStyle: ['{{selector}} .wp-seed-event-calendar-link', []],
  occurrenceCalendarStyle: ['{{selector}} .wp-seed-event-date > .wp-seed-event-calendar-link', []],
  allCalendarStyle: ['{{selector}} .wp-seed-event-calendar-all .wp-seed-event-calendar-link', []],
  occurrenceStyle: ['{{selector}} .wp-seed-event-date', []],
  listStyle: ['{{selector}} .wp-seed-event-dates', []],
};
let specificStyleFamilies = 0;
for (const [name, expected] of Object.entries(styleAttributes)) {
  const attribute = metadata.attributes[name];
  const decoration = Object.keys(attribute.settings?.decoration || {});
  assert.strictEqual(attribute.selector, expected[0]);
  assert.deepStrictEqual(decoration, expected[1]);
  specificStyleFamilies += decoration.length;
  if (['titleStyle', 'calendarLinkStyle', 'occurrenceCalendarStyle', 'allCalendarStyle'].includes(name)) {
    assert.ok(!reactSource.includes(`elements.style({ attrName: '${name}' })`));
    assert.ok(!phpSource.includes(`'${name}'`));
  } else {
    assert.ok(reactSource.includes(`elements.style({ attrName: '${name}' })`));
    assert.ok(phpSource.includes(`'${name}'`));
  }
}

const nativeModuleFamilies = ['layout'];
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
  '<section class=', 'wp-seed-event-section--dates',
  '<ul class=', '<li class=', '<time class="wp-seed-event-date__date"',
  'wp-seed-event-date__time', 'wp-seed-event-date__status',
];
for (const contract of domContracts) assert.ok(publicRenderSource.includes(contract), `DOM contract missing: ${contract}`);
for (const target of ['wp-seed-event-date__date', 'wp-seed-event-date__time', 'wp-seed-event-date__status']) {
  assert.ok(css.includes(`.wp-seed-event-section--dates .${target}`));
}
assert.ok(!publicRenderSource.includes('wp-seed-event-calendar-link'));
assert.ok(!css.includes('wp-seed-event-calendar-link'));
assert.ok(!publicRenderSource.includes('wp-seed-event-dates__title'));
assert.ok(!css.includes('wp-seed-event-dates__title'));
assert.ok(css.includes('display: block'));
assert.ok(css.includes('inline-size: 100%'));
assert.ok(css.includes('.wp-seed-event-section--dates > .wp-seed-event-dates'));
assert.ok(css.includes('padding-block-end: 0'));
assert.ok(!css.includes('\nul {'));
assert.ok(!phpSource.includes('get_post_meta('));

const styleGroups = [];
Object.values(metadata.attributes).forEach((attribute) => {
  Object.values(attribute?.settings?.decoration || {}).forEach((decoration) => {
    if (decoration?.render === false) return;
    const label = decoration?.component?.props?.groupLabel;
    if (label) styleGroups.push(label);
  });
});
Object.values(metadata.settings.groups).forEach((group) => {
  if (group.panel === 'design') styleGroups.push(group.component.props.groupLabel);
});
assert.deepStrictEqual(styleGroups, [
  'Module',
  'Date',
  'Horaire',
  'État annulé',
  'Séparateur Date / Horaire',
  'Liste',
]);
assert.strictEqual(metadata.settings.groups.designDateList.priority, 15);
for (const attrName of ['sectionStyle', 'occurrenceStyle']) {
  assert.deepStrictEqual(
    Object.keys(metadata.attributes[attrName].settings?.decoration || {}),
    [],
    `${attrName} must remain runtime-readable without exposing technical decoration groups`,
  );
}
assert.strictEqual(metadata.attributes.titleStyle.settings, undefined);
for (const attrName of ['dateStyle', 'timeStyle', 'statusStyle']) {
  assert.strictEqual(
    metadata.attributes[attrName].settings.decoration.font.component.props.dynamicSubgroupHost,
    true,
    `${attrName} must expose advanced typography on demand`,
  );
}
assert.strictEqual(
  metadata.attributes.module.settings.decoration.layout.component.props.dynamicSubgroupHost,
  true,
);
for (const attrName of ['occurrenceCalendarStyle', 'allCalendarStyle']) {
  assert.strictEqual(metadata.attributes[attrName].elementType, 'button');
  assert.strictEqual(metadata.attributes[attrName].settings, undefined);
  assert.ok(metadata.attributes[attrName].default.decoration.button);
}
const exposedFamilies = Object.values(contentItems).filter((field) => field.render !== false).length
  + Object.keys(listFields).length
  + styleGroups.length;
assert.strictEqual(exposedFamilies, 19);
assert.strictEqual(metadata.attributes.__loop_post_id.default, '');

console.log(`Divi event Dates functional inventory: ${exposedFamilies} exposed control families verified; hidden loop context is not user-facing.`);
