'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const modules = {
  dates: 'event-dates-module/visual-builder/src/module.json',
  people: 'event-people-module/visual-builder/src/module.json',
  visuals: 'event-visuals-module/visual-builder/src/module.json',
  share: 'event-share-module/visual-builder/src/module.json',
  collection: 'occurrence-collection-module/visual-builder/src/module.json',
};

const businessFields = new Set([
  'date_selection',
  'show_flyer', 'show_visuals', 'show_document', 'mode', 'promotion', 'parcours_year',
  'event_id', 'type', 'status', 'pinned', 'include_cancelled', 'from', 'to', 'order',
  'page', 'per_page', 'grouped_limit', 'collection_instance_id',
]);
const legacyFields = new Set([
  'show_calendar_links', 'calendar_presentation', 'calendar_label', 'calendar_icon_position',
  'calendar_layout', 'show_all_calendar', 'all_calendar_presentation', 'all_calendar_label',
  'all_calendar_icon_position',
]);

const read = (relative) => JSON.parse(fs.readFileSync(path.join(root, 'includes/integrations/divi', relative), 'utf8'));
const inventory = {};

Object.entries(modules).forEach(([moduleName, relative]) => {
  const metadata = read(relative);
  const groups = Object.values(metadata.settings?.groups ?? {})
    .filter((group) => group.panel === 'content')
    .sort((a, b) => a.priority - b.priority);
  const items = Object.values(metadata.attributes?.content?.settings?.innerContent?.items ?? {});
  const rows = items.map((item) => ({
    control: item.subName,
    label: item.label,
    group: groups.find((group) => group.groupName === item.groupSlug)?.component?.props?.groupLabel ?? item.groupSlug,
    type: legacyFields.has(item.subName) ? 'legacy' : (businessFields.has(item.subName) ? 'business' : 'presentation'),
    decision: legacyFields.has(item.subName) ? 'HIDE' : (moduleName === 'people' ? 'GROUP' : 'KEEP'),
  }));
  assert.strictEqual(new Set(rows.map((row) => row.control)).size, rows.length, `${moduleName} has duplicate content controls.`);
  assert.ok(rows.every((row) => row.group), `${moduleName} has an ungrouped content control.`);
  inventory[moduleName] = { groups: groups.map((group) => group.component.props.groupLabel), rows };
});

assert.deepStrictEqual(inventory.people.groups, [
  'Titre', 'Filtrage', 'Nom', 'Email', 'Téléphone', 'Liens', 'Disposition', 'Séparateurs',
]);
assert.deepStrictEqual(
  inventory.people.rows.filter((row) => row.group === 'Filtrage').map((row) => row.control),
  [],
);
assert.deepStrictEqual(
  inventory.people.rows.filter((row) => row.group === 'Email').map((row) => row.control),
  ['show_email', 'email_clickable'],
);
assert.deepStrictEqual(
  inventory.people.rows.filter((row) => row.group === 'Téléphone').map((row) => row.control),
  ['show_phone', 'phone_clickable'],
);
assert.deepStrictEqual(
  inventory.people.rows.filter((row) => row.group === 'Liens').map((row) => row.control),
  ['show_link', 'site_clickable'],
);
assert.ok(!inventory.people.rows.some((row) => row.control === 'show_roles'), 'Role labels returned to the new UI.');
assert.ok(!inventory.people.rows.some((row) => row.label === 'Site cliquable'), 'Inconsistent Site wording remains.');

console.log(JSON.stringify(inventory, null, 2));
console.log('Divi Content panel inventory: PASS');
