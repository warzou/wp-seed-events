'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const activeFiles = [
  'wp-seed-events.php',
  'includes/public/occurrences.php',
  'includes/public/event-data.php',
  'includes/public/occurrence-context.php',
  'includes/public/occurrence-collections.php',
  'includes/integrations/gutenberg/occurrence-collection-block.php',
  'includes/integrations/gutenberg/occurrence-collection-block/src/block.json',
  'includes/integrations/gutenberg/occurrence-collection-block/src/index.js',
  'includes/integrations/divi/class-occurrence-collection-module.php',
  'includes/integrations/divi/occurrence-collection-module/visual-builder/src/module.json',
  'includes/integrations/divi/occurrence-collection-module/visual-builder/src/index.jsx',
];
const forbidden = /wp_seed_promotion|promotion_id|parcours_year|parcoursYear|query_grouped_occurrence_collection|occurrences\/grouped/i;

activeFiles.forEach((relative) => {
  assert.ok(!forbidden.test(read(relative)), `Legacy Parcours contract remains in ${relative}`);
});

[
  'includes/admin/promotions.php',
  'includes/public/promotions.php',
  'docs/PROMOTION-DOMAIN-API.md',
  'tests/promotion-domain-harness.php',
].forEach((relative) => assert.ok(!fs.existsSync(path.join(root, relative)), `${relative} still exists`));

const projection = read('includes/admin/occurrence-projection.php');
assert.ok(!/promotion_id|parcours_year/i.test(projection.split('function wp_seed_events_install_occurrence_projection_table')[0]));
assert.ok(projection.includes("array( 'promotion_year_start', 'promotion_id', 'parcours_year' )"));
assert.ok(projection.includes("array( 'promotion_id', 'parcours_year' )"));
assert.strictEqual((projection.match(/DROP COLUMN/g) || []).length, 1);

const lifecycle = read('includes/admin/lifecycle-index-backfill.php');
assert.ok(lifecycle.includes('return 6;'));

const bootstrap = read('wp-seed-events.php');
const classifications = read('includes/public/classifications.php');
assert.ok(classifications.includes("'wp_seed_event_type'"));
assert.ok(classifications.includes("'wp_seed_event_flag'"));
assert.ok(bootstrap.includes("version: 0.2.0-beta.9") || bootstrap.includes('Version: 0.2.0-beta.9'));

console.log('Legacy Parcours removal contract: PASS');
