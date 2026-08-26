'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const publicContract = [
  'includes/public/data-registry.php',
  'includes/public/descriptions.php',
  'includes/public/rendering.php',
  'includes/integrations/gutenberg/event-content-block.php',
  'includes/integrations/gutenberg/event-content-block/src/index.js',
].map(read).join('\n').toLowerCase();

for (const builder of ['divi', 'et_builder', 'spectra', 'astra', 'uagb']) {
  assert.ok(!publicContract.includes(builder), `Rich content contract depends on ${builder}`);
}

const registry = read('includes/public/data-registry.php');
assert.ok(registry.includes("'description'                    => 'rich_html'"));
assert.ok(registry.includes("return wp_seed_events_render_rich_content( $event['description'] ?? '' )"));

const eventData = read('includes/public/event-data.php');
assert.ok(eventData.includes("'description'                 => $description"));
assert.ok(!eventData.includes("wp_seed_events_render_rich_content( $description"));

console.log('Builder-agnostic rich content contract: 9/9 OK');
