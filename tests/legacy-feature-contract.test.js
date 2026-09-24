'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const readJson = (...parts) => JSON.parse(read(...parts));

const datesMetadata = readJson(
  'includes', 'integrations', 'divi', 'event-dates-module', 'visual-builder', 'src', 'module.json',
);
const datesReact = read(
  'includes', 'integrations', 'divi', 'event-dates-module', 'visual-builder', 'src', 'index.jsx',
);
const datesPhp = read('includes', 'integrations', 'divi', 'class-event-dates-module.php');
const publicRenderer = read('includes', 'public', 'rendering.php');
const publicCss = read('includes', 'public', 'event-dates.css');
const calendar = read('includes', 'public', 'calendar.php');
const eventData = read('includes', 'public', 'event-data.php');
const registry = read('includes', 'public', 'data-registry.php');
const gutenbergPhp = read('includes', 'integrations', 'gutenberg', 'event-dates-block.php');
const gutenbergReact = read('includes', 'integrations', 'gutenberg', 'event-dates-block', 'src', 'index.js');
const gutenbergMetadata = readJson(
  'includes', 'integrations', 'gutenberg', 'event-dates-block', 'src', 'block.json',
);
const bindings = read('includes', 'integrations', 'gutenberg', 'block-bindings.php');
const dynamicText = read('includes', 'integrations', 'divi', 'class-dynamic-content-text.php');

const legacyCalendarFields = [
  'show_calendar_links',
  'calendar_presentation',
  'calendar_label',
  'calendar_icon_position',
  'calendar_layout',
  'show_all_calendar',
  'all_calendar_presentation',
  'all_calendar_label',
  'all_calendar_icon_position',
];

const contentItems = Object.values(datesMetadata.attributes.content.settings.innerContent.items);
for (const field of legacyCalendarFields) {
  const item = contentItems.find((candidate) => candidate.subName === field);
  assert.ok(item, `Legacy calendar storage removed: ${field}`);
  assert.strictEqual(item.render, false, `Legacy calendar UI exposed: ${field}`);
  assert.ok(!datesReact.includes(field), `Legacy calendar field executes in React: ${field}`);
  assert.ok(!datesPhp.includes(field), `Legacy calendar field executes in PHP: ${field}`);
  assert.ok(!publicRenderer.includes(field), `Legacy calendar field executes in public rendering: ${field}`);
}

for (const style of ['calendarLinkStyle', 'occurrenceCalendarStyle', 'allCalendarStyle']) {
  assert.ok(datesMetadata.attributes[style], `Legacy calendar style storage removed: ${style}`);
  assert.strictEqual(datesMetadata.attributes[style].settings, undefined);
  assert.ok(!datesReact.includes(`attrName: '${style}'`), `Legacy style executes in React: ${style}`);
  assert.ok(!datesPhp.includes(`'${style}'`), `Legacy style executes in PHP: ${style}`);
}

for (const token of [
  'wp-seed-event-calendar-link',
  'wp-seed-event-calendar-all',
  'has-calendar-link',
  'is-calendar-inline',
  'Ajouter cette date au calendrier',
]) {
  assert.ok(!datesReact.includes(token), `Integrated calendar residue in React: ${token}`);
  assert.ok(!datesPhp.includes(token), `Integrated calendar residue in PHP module: ${token}`);
  assert.ok(!publicRenderer.includes(token), `Integrated calendar residue in public renderer: ${token}`);
  assert.ok(!publicCss.includes(token), `Integrated calendar residue in public CSS: ${token}`);
}

for (const helper of [
  'wp_seed_events_render_calendar_link',
  'wp_seed_events_render_event_calendar_link',
  'wp_seed_events_render_occurrence_calendar_link',
]) {
  assert.ok(!calendar.includes(`function ${helper}`), `Obsolete calendar renderer remains: ${helper}`);
}

assert.ok(gutenbergMetadata.attributes.show_calendar_links, 'Gutenberg legacy storage was removed.');
assert.ok(!gutenbergReact.includes('show_calendar_links'), 'Gutenberg editor still executes the legacy action.');
assert.ok(!gutenbergPhp.includes('show_calendar_links'), 'Gutenberg renderer still executes the legacy action.');

assert.ok(calendar.includes('function wp_seed_events_event_calendar_url'));
assert.ok(eventData.includes("'calendar_all_occurrences_url' => $calendar_all_occurrences_url"));
assert.ok(registry.includes("'calendar_all_occurrences_url' => array("));
assert.ok(registry.includes("'type'        => 'url'"));
assert.ok(bindings.includes("'calendar_all_occurrences_url'"));

for (const metadata of [
  datesMetadata,
  readJson('includes', 'integrations', 'divi', 'event-people-module', 'visual-builder', 'src', 'module.json'),
  readJson('includes', 'integrations', 'divi', 'event-visuals-module', 'visual-builder', 'src', 'module.json'),
]) {
  const titleItem = Object.values(metadata.attributes.content.settings.innerContent.items)
    .find((item) => item.subName === 'title');
  assert.ok(
    Object.prototype.hasOwnProperty.call(metadata.attributes.content.default.innerContent.desktop.value, 'title'),
    `${metadata.name} legacy title storage was removed.`,
  );
  assert.ok(!titleItem || titleItem.render === false, `${metadata.name} title is exposed in the new UI.`);
}

assert.ok(dynamicText.includes("esc_html__( 'WPSEvents — Page — %s'"));
assert.ok(dynamicText.includes("esc_html__( 'WPSEvents — %s'"));
assert.ok(dynamicText.includes("esc_html__( 'WPSEvents — Page événement', 'wp-seed-events' )"));
assert.ok(dynamicText.includes("esc_html__( 'WPSEvents', 'wp-seed-events' )"));
assert.ok(dynamicText.includes("'wp_seed_events_' . $field"), 'Dynamic Data source key changed.');
assert.ok(bindings.includes("'wp-seed-events/event-field'"), 'Gutenberg binding source key changed.');
assert.ok(bindings.includes("'label'              => 'WPSEvents'"));

console.log('Legacy feature and canonical Event Data contract: OK');
