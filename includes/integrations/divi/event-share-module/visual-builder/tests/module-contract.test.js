const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const phpModule = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/class-event-share-module.php'), 'utf8');
const bootstrap = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/bootstrap.php'), 'utf8');

let passed = 0;
const test = (name, callback) => { callback(); passed += 1; console.log(`ok ${passed} - ${name}`); };

test('canonical module identity is unique', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-share');
  assert.strictEqual((source.match(/registerModule\(metadata, eventShareModule\)/g) || []).length, 1);
});
test('module uses official event context', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
  assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
  assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
});
test('Event Data API and official renderer are each called once', () => {
  assert.strictEqual((phpModule.match(/wp_seed_events_get_event_data/g) || []).length, 1);
  assert.strictEqual((phpModule.match(/wp_seed_events_render_event_share_menu/g) || []).length, 1);
});
test('empty or invalid event context renders nothing', () => {
  assert.ok(phpModule.includes("if ( 0 === $event_id )"));
  assert.ok(phpModule.includes("return array() === $event ? ''"));
});
test('no storage access or duplicate business markup exists in PHP', () => {
  ['get_post_meta', '$wpdb', 'WP_Query', 'do_shortcode', '<details', 'mailto:'].forEach((token) => assert.ok(!phpModule.includes(token)));
});
test('editor preview uses the official server renderer', () => {
  assert.ok(source.includes('/divi-event-share-preview'));
  assert.ok(source.includes('resolveCurrentEventContext'));
  assert.ok(source.includes('dangerouslySetInnerHTML'));
  assert.ok(source.includes('context.cacheKey'));
});
test('authenticated preview route and stable selectors are registered', () => {
  assert.ok(phpModule.includes('/divi-event-share-preview'));
  ['wp-seed-event-share', 'wp-seed-event-share__actions', 'data-wp-seed-event-share-copy'].forEach((token) => {
    assert.ok(JSON.stringify(metadata).includes(token));
  });
});
test('bootstrap registers dependency and app-window bundle once', () => {
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_register_event_share_module/g) || []).length, 2);
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_enqueue_event_share_module_assets/g) || []).length, 2);
  assert.ok(bootstrap.includes('wp-seed-events-event-share.js'));
});

assert.strictEqual(passed, 8);
console.log('Divi event share module contract: 8/8 OK');
