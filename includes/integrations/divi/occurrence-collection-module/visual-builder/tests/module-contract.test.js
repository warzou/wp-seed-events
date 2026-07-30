const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const php = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'class-occurrence-collection-module.php'),
  'utf8',
);
const defaults = metadata.attributes.content.default.innerContent.desktop.value;
const items = metadata.attributes.content.settings.innerContent.items;

assert.strictEqual(metadata.name, 'wp-seed-events/divi-occurrence-collection');
assert.strictEqual(metadata.folder, 'wp-seed-events');
assert.strictEqual(metadata.title, 'WP Seed Events — Collection d’occurrences');
assert.strictEqual(defaults.mode, 'flat');
assert.strictEqual(defaults.status, 'upcoming');
assert.strictEqual(defaults.pinned, 'all');
assert.strictEqual(defaults.include_cancelled, 'off');
assert.strictEqual(defaults.page, 1);
assert.strictEqual(defaults.per_page, 20);
assert.strictEqual(defaults.grouped_limit, 200);

[
  'mode', 'promotion', 'parcours_year', 'event_id', 'type', 'status', 'pinned',
  'include_cancelled', 'from', 'to', 'order', 'page', 'per_page', 'grouped_limit',
  'collection_instance_id', 'show_event_title', 'show_event_type', 'show_event_status',
  'show_event_pinned', 'show_start_date', 'show_end_date', 'show_start_time',
  'show_end_time', 'show_cancelled', 'show_promotion_name', 'show_promotion_year',
  'show_promotion_status', 'show_parcours_year', 'show_parcours_label', 'show_labels',
  'date_format', 'time_format', 'field_separator', 'date_separator', 'time_separator',
  'empty_message', 'cancelled_text',
].forEach((field) => {
  assert.ok(
    Object.values(items).some((item) => item.subName === field),
    `Missing persistent field: ${field}`,
  );
});

['from', 'to'].forEach((field) => {
  assert.strictEqual(items[field].description, 'Format : AAAA-MM-JJ');
  assert.strictEqual(items[field].component.name, 'divi/text');
});
assert.ok(source.includes('new URLSearchParams(requestData).toString()'));
assert.ok(!source.includes('toLocaleDateString'));
assert.ok(!source.includes('toLocaleString'));

assert.deepStrictEqual(Object.keys(items.mode.component.props.options), ['flat', 'grouped']);
assert.deepStrictEqual(Object.keys(items.status.component.props.options), ['upcoming', 'past', 'all']);
assert.deepStrictEqual(Object.keys(items.order.component.props.options), [
  'upcoming', 'chronological', 'chronological_desc',
]);

[
  'wp-seed-events-divi-occurrence-collection__promotion',
  'wp-seed-events-divi-occurrence-collection__year',
  'wp-seed-events-divi-occurrence-collection__theme',
  'wp-seed-events-divi-occurrence-collection__item',
  'wp-seed-events-divi-occurrence-collection__empty',
  'wp-seed-events-divi-occurrence-collection__pagination',
].forEach((selector) => assert.ok(JSON.stringify(metadata).includes(selector)));

[
  'useFetch', 'AbortController', 'URLSearchParams',
  '/wp-seed-events/v1/divi-occurrence-collection-preview?',
  'registerFolder({', 'registerModule(metadata, occurrenceCollectionModule)',
  'collection_instance_id: options.collection_instance_id || instanceId',
  'id, storeInstance, orderIndex',
].forEach((contract) => assert.ok(source.includes(contract), `Missing JS contract: ${contract}`));

[
  'wp_seed_events_query_occurrence_collection( $args )',
  'wp_seed_events_query_grouped_occurrence_collection( $args )',
  'wp_seed_events_occurrence_context_from_item',
  'wp_seed_events_with_occurrence_context',
  "'canonical_path'",
  'min( 500',
  'min( 100',
  'wpseed_divi_occurrence_page_',
  'role="status"',
  'role="alert"',
].forEach((contract) => assert.ok(php.includes(contract), `Missing PHP contract: ${contract}`));

assert.ok(!php.includes('get_post_meta'));
assert.ok(!php.includes('$wpdb'));
assert.ok(!php.includes('_wp_seed_event_'));
assert.ok(!source.includes('get_post_meta'));
assert.ok(!source.includes('eventId: 914'));
assert.strictEqual((php.match(/wp_seed_events_query_occurrence_collection\( \$args \)/g) || []).length, 1);
assert.strictEqual((php.match(/wp_seed_events_query_grouped_occurrence_collection\( \$args \)/g) || []).length, 1);

const historicalBundles = [
  'event-dates-module/visual-builder/build/wp-seed-events-event-dates.js',
  'event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js',
  'event-people-module/visual-builder/build/wp-seed-events-event-people.js',
];
historicalBundles.forEach((relative) => {
  assert.ok(fs.existsSync(path.join(pluginRoot, 'includes', 'integrations', 'divi', relative)));
});

console.log('Divi occurrence collection module contract: OK');
