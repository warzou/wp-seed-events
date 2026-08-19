const assert = require('assert');
const fs = require('fs');
const path = require('path');

const pluginRoot = path.resolve(__dirname, '..');
const modules = [
  ['event-dates-module', 'wp-seed-events/event-dates', 'class-event-dates-module.php'],
  ['event-visuals-module', 'wp-seed-events/event-visuals', 'class-event-visuals-module.php'],
  ['event-people-module', 'wp-seed-events/event-people', 'class-event-people-module.php'],
  ['event-share-module', 'wp-seed-events/event-share', 'class-event-share-module.php'],
  ['occurrence-collection-module', 'wp-seed-events/divi-occurrence-collection', 'class-occurrence-collection-module.php'],
];

modules.forEach(([directory, name, phpFile]) => {
  const root = path.join(pluginRoot, 'includes', 'integrations', 'divi', directory, 'visual-builder');
  const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
  const react = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
  const php = fs.readFileSync(
    path.join(pluginRoot, 'includes', 'integrations', 'divi', phpFile),
    'utf8',
  );

  assert.strictEqual(metadata.name, name);
  assert.deepStrictEqual(
    metadata.attributes.module.settings.decoration.layout,
    {},
    `${name} must opt into Divi 5 native Layout decoration`,
  );
  assert.strictEqual(
    metadata.attributes.module.default?.decoration?.layout,
    undefined,
    `${name} must not add a persisted Layout default to existing instances`,
  );
  assert.match(
    react,
    /elements\.style\(\s*\{\s*attrName:\s*'module'/,
    `${name} must render native module styles in the Visual Builder`,
  );
  assert.match(
    php,
    /->style\(\s*array\(\s*'attrName'\s*=>\s*'module'/,
    `${name} must render native module styles on the frontend`,
  );
});

console.log(`Divi 5 native Layout contract: ${modules.length}/${modules.length} OK`);
