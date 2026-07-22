const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const phpModule = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'class-event-dates-module.php'),
  'utf8',
);
const contextHelper = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'context.php'),
  'utf8',
);
const bootstrap = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'bootstrap.php'),
  'utf8',
);
const resolverSource = contextHelper.slice(
  contextHelper.indexOf('function wp_seed_events_divi_resolve_event_id'),
);
const contentItems = metadata.attributes.content.settings.innerContent.items;

assert.strictEqual(metadata.name, 'wp-seed-events/event-dates');
assert.strictEqual(metadata.folder, 'wp-seed-events');
assert.strictEqual(metadata.attributes.content.default.innerContent.desktop.value.scope, 'all');

[
  'title',
  'heading_level',
  'scope',
  'show_cancelled',
  'show_times',
  'show_calendar_links',
].forEach((field) => {
  assert.ok(
    Object.values(contentItems).some((item) => item.subName === field),
    `Missing persistent field: ${field}`,
  );
});

[
  'wp-seed-event-dates__title',
  'wp-seed-event-date__date',
  'wp-seed-event-date__time',
  'wp-seed-event-date__status',
  'wp-seed-event-calendar-link',
  'wp-seed-event-date',
].forEach((selector) => {
  assert.ok(JSON.stringify(metadata).includes(selector), `Missing design selector: ${selector}`);
});

assert.ok(source.includes('useFetch'));
assert.ok(source.includes('AbortController'));
assert.ok(source.includes('URLSearchParams'));
assert.ok(source.includes('/wp-seed-events/v1/divi-event-dates-preview?'));
assert.deepStrictEqual(metadata.attributes.__loop_post_id, { type: 'string', default: '' });
assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
assert.ok(source.includes('loop_id: loopPostId'));
assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
assert.ok(contextHelper.includes('DynamicContentUtils'));
assert.ok(contextHelper.includes('get_loop_post_id'));
assert.ok(source.includes("addFilter('divi.moduleLibrary.moduleMapping'"));
assert.ok(source.includes('registerFolder({'));
assert.ok(!source.includes('[wp_seed_event_dates'));
assert.ok(!source.includes('914'));

assert.strictEqual(
  (phpModule.match(/wp_seed_events_render_public_event_dates_section/g) || []).length,
  1,
  'The shared renderer must be called exactly once by the module class.',
);
assert.strictEqual(
  (phpModule.match(/wp_seed_events_get_event_data/g) || []).length,
  1,
  'Event Data API must be resolved at most once by the module class.',
);
assert.ok(phpModule.includes("current_user_can( 'edit_posts' )"));
assert.ok(!phpModule.includes('get_post_meta'));
assert.ok(!phpModule.includes('_wp_seed_event_'));
assert.ok(phpModule.includes("array( '0', 'off' )"));
assert.ok(phpModule.includes('return ! in_array('));
assert.ok(resolverSource.indexOf('$loop_id =') < resolverSource.indexOf('$post_id ='));
assert.ok(resolverSource.includes('return wp_seed_events_divi_is_event( $loop_id ) ? $loop_id : 0;'));
assert.ok(bootstrap.includes("function_exists( 'et_builder_d5_enabled' )"));
assert.ok(bootstrap.includes('PackageBuildManager::register_package_build'));

console.log('Divi event dates module contract: OK');
