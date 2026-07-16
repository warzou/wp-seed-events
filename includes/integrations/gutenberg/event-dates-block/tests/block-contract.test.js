const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'block.json'), 'utf8'));
const builtMetadata = JSON.parse(fs.readFileSync(path.join(root, 'build', 'block.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.js'), 'utf8');
const packageManifest = JSON.parse(
  fs.readFileSync(path.join(pluginRoot, 'package.json'), 'utf8'),
);
const legacyPackageRoot = path.join(
  pluginRoot, 'includes', 'integrations', 'divi', 'event-dates-module', 'visual-builder',
);
const bootstrap = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'gutenberg', 'event-dates-block.php'),
  'utf8',
);
const plugin = fs.readFileSync(path.join(pluginRoot, 'wp-seed-events.php'), 'utf8');
const zipBuild = fs.readFileSync(path.join(pluginRoot, 'build-dev-zip.ps1'), 'utf8');
const diviMetadata = JSON.parse(
  fs.readFileSync(
    path.join(
      pluginRoot,
      'includes',
      'integrations',
      'divi',
      'event-dates-module',
      'visual-builder',
      'src',
      'module.json',
    ),
    'utf8',
  ),
);

assert.strictEqual(metadata.name, 'wp-seed-events/event-dates-block');
assert.notStrictEqual(metadata.name, diviMetadata.name);
assert.strictEqual(metadata.apiVersion, 3);
assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
assert.deepStrictEqual(builtMetadata, metadata);
assert.strictEqual(packageManifest.devDependencies['@wordpress/scripts'], '33.0.0');
assert.ok(packageManifest.scripts['build:divi']);
assert.ok(packageManifest.scripts['build:gutenberg']);
assert.ok(packageManifest.scripts['test:divi']);
assert.ok(packageManifest.scripts['test:gutenberg']);
assert.ok(!fs.existsSync(path.join(legacyPackageRoot, 'package.json')));
assert.ok(!fs.existsSync(path.join(legacyPackageRoot, 'package-lock.json')));
assert.ok(fs.existsSync(path.join(root, 'build', 'index.js')));
assert.ok(fs.existsSync(path.join(root, 'build', 'index.asset.php')));

const defaults = {
  title: 'Dates',
  heading_level: 'h2',
  scope: 'all',
  show_cancelled: true,
  show_times: true,
  show_calendar_links: true,
};

Object.entries(defaults).forEach(([name, value]) => {
  assert.ok(metadata.attributes[name], `Missing block attribute: ${name}`);
  assert.strictEqual(metadata.attributes[name].default, value, `Unexpected default: ${name}`);
});

assert.strictEqual((source.match(/registerBlockType\s*\(/g) || []).length, 1);
assert.ok(source.includes('save: () => null'));
assert.ok(!source.includes('wp_seed_events_render_public_event_dates_section'));
assert.ok(!source.includes('[wp_seed_event_dates'));
assert.ok(!source.includes('eventId'));
assert.ok(!source.includes('914'));

assert.ok(bootstrap.includes("add_action( 'init', 'wp_seed_events_register_event_dates_block'"));
assert.strictEqual(
  (bootstrap.match(/register_block_type_from_metadata\s*\(/g) || []).length,
  1,
  'The bootstrap must call metadata registration exactly once.',
);
assert.ok(plugin.includes("require_once __DIR__ . '/includes/integrations/gutenberg/event-dates-block.php';"));
assert.ok(zipBuild.includes('$gutenbergBlockJson'));
assert.ok(zipBuild.includes('$gutenbergBlockScript'));
assert.ok(zipBuild.includes('$gutenbergBlockAsset'));

console.log('Gutenberg event dates block skeleton contract: OK');
