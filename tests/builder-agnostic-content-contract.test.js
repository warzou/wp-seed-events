'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const core = [
  'includes/public/data-registry.php',
  'includes/public/descriptions.php',
  'includes/public/rendering.php',
].map(read).join('\n').toLowerCase();
const css = read('includes/public/event-lists.css');
const rendering = read('includes/public/rendering.php');

for (const builder of ['divi', 'spectra', 'astra', 'uagb']) {
  assert.ok(!core.includes(builder), `Core content contract depends on ${builder}`);
}

for (const selector of [
  '.wp-seed-event-people__roles',
  '.wp-seed-event-people__contacts',
  '.wp-seed-event-people__roles > li::before',
  '.wp-seed-event-people__contacts > li::before',
  '.wp-seed-event-people__roles > li::marker',
  '.wp-seed-event-people__contacts > li::marker',
]) {
  assert.ok(css.includes(selector), `Structural list reset missing: ${selector}`);
}

assert.ok(css.includes('list-style-type: none !important'));
assert.ok(css.includes('padding-inline-start: 0 !important'));
assert.ok(css.includes('content: none !important'));
assert.ok(!css.includes('.wp-seed-events-rich-content ul'));
assert.ok(!css.includes('.wp-seed-events-rich-content li'));

for (const htmlClass of [
  'wp-seed-event-people__list',
  'wp-seed-event-people__roles',
  'wp-seed-event-people__contacts',
  'wp-seed-event-dates',
  'wp-seed-event-visuals__list',
]) {
  assert.ok(rendering.includes(htmlClass), `Public renderer list missing: ${htmlClass}`);
}

console.log('Builder-agnostic content and structural list contract: PASS');
