const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..');
const read = (...parts) => fs.readFileSync(path.join(...parts), 'utf8');
const metadata = JSON.parse(read(root, 'src', 'block.json'));
const builtMetadata = JSON.parse(read(root, 'build', 'block.json'));
const source = read(root, 'src', 'index.js');
const bootstrap = read(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-dates-block.php',
);
const preview = read(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-dates-preview.php',
);
const plugin = read(pluginRoot, 'wp-seed-events.php');
const zipBuild = read(pluginRoot, 'build-dev-zip.ps1');
const packageManifest = JSON.parse(read(pluginRoot, 'package.json'));
const diviRoot = path.join(
  pluginRoot,
  'includes',
  'integrations',
  'divi',
  'event-dates-module',
  'visual-builder',
);
const diviMetadata = JSON.parse(read(diviRoot, 'src', 'module.json'));

assert.strictEqual(metadata.name, 'wp-seed-events/event-dates-block');
assert.notStrictEqual(metadata.name, diviMetadata.name);
assert.strictEqual(metadata.apiVersion, 3);
assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
assert.deepStrictEqual(metadata.attributes.heading_level.enum, ['h2', 'h3', 'h4', 'h5', 'h6']);
assert.deepStrictEqual(metadata.attributes.scope.enum, ['all', 'upcoming', 'past']);
assert.ok(!Object.prototype.hasOwnProperty.call(metadata.attributes, 'eventId'));
assert.deepStrictEqual(builtMetadata, metadata);

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
  assert.ok(source.includes(name), `Missing editor control or request attribute: ${name}`);
});

assert.strictEqual((source.match(/registerBlockType\s*\(/g) || []).length, 1);
assert.ok(source.includes('save: () => null'));
assert.ok(source.includes('InspectorControls'));
assert.strictEqual((source.match(/<TextControl\b/g) || []).length, 1);
assert.strictEqual((source.match(/<SelectControl\b/g) || []).length, 2);
assert.strictEqual((source.match(/<ToggleControl\b/g) || []).length, 3);
[
  'apiFetch',
  "method: 'POST'",
  'AbortController',
  'controller.abort()',
  'requestSequence',
  'window.setTimeout',
  'window.clearTimeout',
  "status: 'loading'",
  "empty ? 'empty' : 'ready'",
  "status: 'error'",
  'Spinner',
  'Notice',
  'RawHTML',
].forEach((contract) => assert.ok(source.includes(contract), `Missing editor contract: ${contract}`));
[
  'dangerouslySetInnerHTML',
  'wp_seed_events_render_public_event_dates_section',
  '[wp_seed_event_dates',
  'eventId',
  '914',
  'wp-seed-event-date__date',
  'ServerSideRender',
].forEach((contract) => assert.ok(!source.includes(contract), `Forbidden editor contract: ${contract}`));

assert.strictEqual(
  (bootstrap.match(/wp_seed_events_get_event_data\s*\(/g) || []).length,
  1,
  'The Event Data API must be called exactly once in the shared Gutenberg path.',
);
assert.strictEqual(
  (bootstrap.match(/wp_seed_events_render_public_event_dates_section\s*\(/g) || []).length,
  1,
  'The shared dates renderer must be called exactly once in the shared Gutenberg path.',
);
[
  'wp_seed_events_gutenberg_event_dates_render',
  'wp_seed_events_public_heading_level_option',
  'wp_seed_events_public_date_scope_option',
  "array_key_exists( 'postId', $context )",
  "array_key_exists( 'postType', $context )",
  'if ( $has_explicit_post_context )',
  "'' === $post_type || 'wp_seed_event' === $post_type",
  "'class' => 'wp-seed-events-event-dates-block'",
  "'render_callback' => 'wp_seed_events_render_gutenberg_event_dates_block'",
  "require_once __DIR__ . '/event-dates-preview.php';",
].forEach((contract) => assert.ok(bootstrap.includes(contract), `Missing PHP contract: ${contract}`));
assert.ok(
  bootstrap.indexOf('if ( $has_explicit_post_context )')
    < bootstrap.indexOf('global $wp_seed_events_public_event_id'),
);
assert.ok(
  bootstrap.includes("return 0;\n\t}\n\n\tglobal $wp_seed_events_public_event_id"),
  'An explicit incompatible post context must return before the public event fallback.',
);
assert.ok(!bootstrap.includes("array_key_exists( 'queryId', $context )"));
assert.ok(bootstrap.indexOf("if ( '' === trim( $html )") < bootstrap.indexOf('get_block_wrapper_attributes('));
['get_post_meta', '$wpdb', 'do_shortcode', '[wp_seed_event_dates', '914', 'register_rest_route']
  .forEach((contract) => assert.ok(!bootstrap.includes(contract), `Forbidden PHP contract: ${contract}`));

assert.strictEqual((preview.match(/register_rest_route\s*\(/g) || []).length, 1);
[
  "'wp-seed-events/v1'",
  "'/gutenberg-event-dates-preview'",
  'WP_REST_Server::CREATABLE',
  "'permission_callback' => 'wp_seed_events_gutenberg_event_dates_preview_permissions'",
  'is_user_logged_in()',
  "current_user_can( 'edit_posts' )",
  "current_user_can( 'edit_post', $post_id )",
  "'html'",
  "'empty'",
  "'message'",
].forEach((contract) => assert.ok(preview.includes(contract), `Missing REST contract: ${contract}`));
assert.strictEqual(
  (preview.match(/wp_seed_events_gutenberg_event_dates_render\s*\(/g) || []).length,
  1,
  'The preview endpoint must call the shared Gutenberg renderer exactly once.',
);
[
  'wp_seed_events_get_event_data',
  'wp_seed_events_render_public_event_dates_section',
  'get_post_meta',
  '$wpdb',
  'do_shortcode',
  '[wp_seed_event_dates',
  '914',
  'update_post_meta',
  'delete_post_meta',
  'update_option',
  'delete_option',
  'wp_insert_post',
  'wp_update_post',
  'wp_delete_post',
].forEach((contract) => assert.ok(!preview.includes(contract), `Forbidden REST contract: ${contract}`));

assert.strictEqual(packageManifest.devDependencies['@wordpress/scripts'], '33.0.0');
['build:divi', 'build:gutenberg', 'test:divi', 'test:gutenberg']
  .forEach((script) => assert.ok(packageManifest.scripts[script]));
assert.ok(!fs.existsSync(path.join(diviRoot, 'package.json')));
assert.ok(!fs.existsSync(path.join(diviRoot, 'package-lock.json')));
assert.ok(fs.existsSync(path.join(root, 'build', 'index.js')));
assert.ok(fs.existsSync(path.join(root, 'build', 'index.asset.php')));
assert.ok(bootstrap.includes("add_action( 'init', 'wp_seed_events_register_event_dates_block'"));
assert.strictEqual((bootstrap.match(/register_block_type_from_metadata\s*\(/g) || []).length, 1);
assert.ok(plugin.includes("require_once __DIR__ . '/includes/integrations/gutenberg/event-dates-block.php';"));
const eventRegistrationStart = plugin.indexOf("register_post_type(\n\t\t'wp_seed_event'");
const placeRegistrationStart = plugin.indexOf("register_post_type(\n\t\t'wp_seed_place'");
const eventRegistration = plugin.slice(eventRegistrationStart, placeRegistrationStart);
const placeRegistration = plugin.slice(
  placeRegistrationStart,
  plugin.indexOf('wp_seed_events_add_event_rewrite_rules();', placeRegistrationStart),
);
assert.ok(eventRegistrationStart > -1 && placeRegistrationStart > eventRegistrationStart);
assert.ok(eventRegistration.includes("'show_in_rest'       => true"));
assert.ok(placeRegistration.includes("'show_in_rest' => false"));
['rest_base', 'rest_controller_class', 'register_post_meta', 'register_rest_field']
  .forEach((contract) => assert.ok(
    !eventRegistration.includes(contract),
    `Forbidden event REST contract: ${contract}`,
  ));
assert.ok(zipBuild.includes('$gutenbergBlockJson'));
assert.ok(zipBuild.includes('$gutenbergBlockScript'));
assert.ok(zipBuild.includes('$gutenbergBlockAsset'));

console.log('Gutenberg event dates block editor and preview contract: OK');
