'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const json = (...parts) => JSON.parse(read(...parts));

const publicRenderer = read('includes', 'public', 'rendering.php');
const publicCss = [
  read('includes', 'public', 'event-dates.css'),
  read('includes', 'public', 'event-visuals.css'),
].join('\n');

const modules = {
  dates: {
    divi: 'event-dates-module',
    gutenberg: 'event-dates-block',
    php: 'class-event-dates-module.php',
    label: 'WPSEvents — Dates',
    titleClass: 'wp-seed-event-dates__title',
  },
  people: {
    divi: 'event-people-module',
    gutenberg: 'event-people-block',
    php: 'class-event-people-module.php',
    label: 'WPSEvents — Personnes',
    titleClass: 'wp-seed-event-people__title',
  },
  visuals: {
    divi: 'event-visuals-module',
    gutenberg: 'event-visuals-block',
    php: 'class-event-visuals-module.php',
    label: 'WPSEvents — Visuels',
    titleClass: 'wp-seed-event-visuals__title',
  },
};

for (const [name, config] of Object.entries(modules)) {
  const diviRoot = ['includes', 'integrations', 'divi', config.divi, 'visual-builder'];
  const diviMetadata = json(...diviRoot, 'src', 'module.json');
  const diviSource = read(...diviRoot, 'src', 'index.jsx');
  const diviPhp = read('includes', 'integrations', 'divi', config.php);
  const defaults = diviMetadata.attributes.content.default.innerContent.desktop.value;
  const controls = Object.values(diviMetadata.attributes.content.settings.innerContent.items || {});

  assert.strictEqual(diviMetadata.title, config.label, `${name}: Divi label is not concise`);
  assert.strictEqual(defaults.title, '', `${name}: stored title default changed`);
  assert.strictEqual(defaults.show_title, 'off', `${name}: stored title toggle default changed`);
  ['title', 'heading_level', 'show_title'].forEach((field) => {
    assert.ok(field in defaults, `${name}: historical Divi ${field} storage removed`);
    assert.ok(!controls.some((control) => control.subName === field), `${name}: ${field} remains in Divi UI`);
  });
  assert.ok(diviMetadata.attributes.titleStyle, `${name}: historical titleStyle storage removed`);
  assert.ok(diviMetadata.attributes.titleStyle.selector.includes(config.titleClass));
  assert.ok(!diviSource.includes("attrName: 'titleStyle'"), `${name}: Divi still injects legacy title styles`);
  assert.ok(!diviPhp.includes("wp_seed_events_divi_optional_title( $values"), `${name}: Divi still resolves a title`);
  assert.ok(!publicRenderer.includes(config.titleClass), `${name}: public renderer still emits a title wrapper`);
  assert.ok(!publicCss.includes(config.titleClass), `${name}: public CSS still targets a title wrapper`);

  const blockRoot = ['includes', 'integrations', 'gutenberg', config.gutenberg];
  const sourceMetadata = json(...blockRoot, 'src', 'block.json');
  const source = read(...blockRoot, 'src', 'index.js');
  const blockPhp = read('includes', 'integrations', 'gutenberg', `${config.gutenberg}.php`);

  assert.strictEqual(sourceMetadata.title, config.label, `${name}: Gutenberg label is not concise`);
  assert.strictEqual(sourceMetadata.attributes.title.default, '', `${name}: Gutenberg title storage changed`);
  assert.ok(sourceMetadata.attributes.heading_level, `${name}: Gutenberg heading storage removed`);
  assert.ok(!source.includes('attributes.title'), `${name}: Gutenberg still reads the stored title`);
  assert.ok(!source.includes('attributes.heading_level'), `${name}: Gutenberg still reads the stored heading`);
  assert.ok(!blockPhp.includes("'title'"), `${name}: Gutenberg PHP still forwards a title`);
  assert.ok(!blockPhp.includes("'heading_level'"), `${name}: Gutenberg PHP still forwards a heading`);
}

const labels = [
  ['includes', 'integrations', 'divi', 'event-share-module', 'visual-builder', 'src', 'module.json', 'WPSEvents — Partage'],
  ['includes', 'integrations', 'divi', 'occurrence-collection-module', 'visual-builder', 'src', 'module.json', 'WPSEvents — Collection'],
  ['includes', 'integrations', 'gutenberg', 'event-content-block', 'src', 'block.json', 'WPSEvents — Contenu'],
  ['includes', 'integrations', 'gutenberg', 'occurrence-collection-block', 'src', 'block.json', 'WPSEvents — Collection'],
];
for (const parts of labels) {
  const expected = parts.pop();
  assert.strictEqual(json(...parts).title, expected);
}

const technicalNames = [
  ['event-dates-module', 'wp-seed-events/event-dates'],
  ['event-people-module', 'wp-seed-events/event-people'],
  ['event-visuals-module', 'wp-seed-events/event-visuals'],
  ['event-share-module', 'wp-seed-events/event-share'],
  ['occurrence-collection-module', 'wp-seed-events/divi-occurrence-collection'],
];
for (const [module, expected] of technicalNames) {
  const metadata = json('includes', 'integrations', 'divi', module, 'visual-builder', 'src', 'module.json');
  assert.strictEqual(metadata.name, expected, `${module}: technical slug changed`);
}

const share = json('includes', 'integrations', 'divi', 'event-share-module', 'visual-builder', 'src', 'module.json');
const shareDefaults = share.attributes.content.default.innerContent.desktop.value;
['title', 'show_title', 'heading_level'].forEach((field) => assert.ok(!(field in shareDefaults)));
['show_group_label', 'group_label'].forEach((field) => assert.ok(!(field in shareDefaults)));
const shareControls = Object.values(share.attributes.content.settings.innerContent.items);
['show_group_label', 'group_label'].forEach((field) => assert.ok(!shareControls.some((control) => control.subName === field)));
assert.ok(!read('includes', 'public', 'sharing.php').includes('wp-seed-event-share__group-label'));
assert.ok(!read('includes', 'public', 'event-share.css').includes('wp-seed-event-share__group-label'));

const collection = json('includes', 'integrations', 'divi', 'occurrence-collection-module', 'visual-builder', 'src', 'module.json');
assert.ok(collection.attributes.titleStyle, 'Collection business title styles must remain available');

const docs = read('docs', 'BUILDER-MODULE-COMPOSITION.md');
['module Titre ou Texte', 'bloc Titre', 'Spectra', 'Astra', 'stockees mais inertes'].forEach((term) => {
  assert.ok(docs.includes(term), `Composition documentation missing: ${term}`);
});

console.log('Builder-owned module title contract: PASS');
