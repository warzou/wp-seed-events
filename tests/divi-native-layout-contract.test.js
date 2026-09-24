const assert = require('assert');
const fs = require('fs');
const path = require('path');

const pluginRoot = path.resolve(__dirname, '..');
const modules = [
  ['event-dates-module', 'wp-seed-events/event-dates', 'class-event-dates-module.php', 'module'],
  ['event-visuals-module', 'wp-seed-events/event-visuals', 'class-event-visuals-module.php', 'eventListStyle'],
  ['event-people-module', 'wp-seed-events/event-people', 'class-event-people-module.php', 'module'],
  ['event-share-module', 'wp-seed-events/event-share', 'class-event-share-module.php', 'module'],
  ['occurrence-collection-module', 'wp-seed-events/divi-occurrence-collection', 'class-occurrence-collection-module.php', 'module'],
];

modules.forEach(([directory, name, phpFile, layoutAttr]) => {
  const root = path.join(pluginRoot, 'includes', 'integrations', 'divi', directory, 'visual-builder');
  const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
  const react = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
  const php = fs.readFileSync(
    path.join(pluginRoot, 'includes', 'integrations', 'divi', phpFile),
    'utf8',
  );

  assert.strictEqual(metadata.name, name);
  assert.ok(
    metadata.attributes[layoutAttr].settings.decoration.layout
      && typeof metadata.attributes[layoutAttr].settings.decoration.layout === 'object',
    `${name} must opt into Divi 5 native Layout decoration`,
  );
  assert.strictEqual(
    metadata.attributes[layoutAttr].default?.decoration?.layout,
    undefined,
    `${name} must not add a persisted Layout default to existing instances`,
  );
  assert.match(
    react,
    new RegExp(`elements\\.style\\(\\s*\\{\\s*attrName:\\s*'${layoutAttr}'`),
    `${name} must render native module styles in the Visual Builder`,
  );
  if (layoutAttr === 'module') {
    assert.match(
      php,
      /->style\(\s*array\(\s*'attrName'\s*=>\s*'module'/,
      `${name} must render native module styles on the frontend`,
    );
  } else {
    assert.ok(
      php.includes(`'${layoutAttr}'`),
      `${name} must render native collection styles on the frontend`,
    );
  }
});

const visualsMetadata = JSON.parse(fs.readFileSync(path.join(
  pluginRoot,
  'includes/integrations/divi/event-visuals-module/visual-builder/src/module.json',
), 'utf8'));
assert.strictEqual(
  visualsMetadata.attributes.eventListStyle.selector,
  '{{selector}} .wp-seed-event-visuals__list',
  'visuals layout must target the collection whose direct children are visual items',
);

console.log(`Divi 5 native Layout contract: ${modules.length}/${modules.length} OK`);
