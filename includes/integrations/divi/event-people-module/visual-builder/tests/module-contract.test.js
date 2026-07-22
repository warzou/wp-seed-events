const assert = require('assert');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const webpackConfig = fs.readFileSync(path.join(root, 'webpack.config.js'), 'utf8');
const phpModule = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/class-event-people-module.php'), 'utf8');
const context = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/context.php'), 'utf8');
const bootstrap = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/bootstrap.php'), 'utf8');
const packageJson = JSON.parse(fs.readFileSync(path.join(pluginRoot, 'package.json'), 'utf8'));
const buildScript = fs.readFileSync(path.join(pluginRoot, 'build-dev-zip.ps1'), 'utf8');
const items = metadata.attributes.content.settings.innerContent.items;
const defaults = metadata.attributes.content.default.innerContent.desktop.value;
const fields = Object.values(items).map((item) => item.subName).sort();
let passed = 0;
const test = (name, callback) => { callback(); passed += 1; console.log(`ok ${passed} - ${name}`); };

const hashFile = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex').toUpperCase();

test('canonical module identity is unique', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-people');
  assert.strictEqual((source.match(/registerModule\(metadata, eventPeopleModule\)/g) || []).length, 1);
});
test('French label and shared folder are configured', () => {
  assert.strictEqual(metadata.title, 'WP Seed — Personnes de l’événement');
  assert.strictEqual(metadata.folder, 'wp-seed-events');
  assert.strictEqual((source.match(/registerFolder\(\{/g) || []).length, 1);
});
test('exact content fields are exposed', () => {
  assert.deepStrictEqual(fields, ['heading_level', 'layout', 'role', 'show_email', 'show_link', 'show_phone', 'show_roles', 'title']);
});
test('renderer defaults are preserved', () => {
  assert.deepStrictEqual(defaults, {
    title: 'Contacts et intervenants', heading_level: 'h2', role: 'all', show_roles: 'on',
    show_email: 'on', show_phone: 'on', show_link: 'on', layout: 'list',
  });
});
test('role options are canonical and complete', () => {
  assert.deepStrictEqual(Object.keys(items.role.component.props.options), [
    'all', 'organizer', 'speaker', 'registration_contact', 'information_contact',
  ]);
});
test('layout options are list and grid only', () => {
  assert.deepStrictEqual(Object.keys(items.layout.component.props.options), ['list', 'grid']);
});
test('heading levels are h2 through h6', () => {
  assert.deepStrictEqual(Object.keys(items.headingLevel.component.props.options), ['h2', 'h3', 'h4', 'h5', 'h6']);
});
test('publication help is explicit without private flags', () => {
  const serialized = JSON.stringify(metadata);
  assert.ok(serialized.includes('privées par défaut'));
  assert.ok(serialized.includes('ne modifie pas son autorisation'));
  ['publish_email', 'publish_phone', 'publish_link', 'person_key'].forEach((key) => assert.ok(!serialized.includes(key)));
});
test('context uses post ID, post type and query ID', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
  assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
  assert.ok(/'post_type'\s*=>\s*sanitize_key/.test(context));
  assert.ok(context.includes('DynamicContentUtils'));
  assert.ok(context.includes('get_loop_post_id'));
  assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
});
test('explicit incompatible post context is guarded', () => {
  assert.ok(context.includes("$strict_post && '' !== $post_type"));
  assert.ok(context.includes('$strict_post && 0 !== $post_id'));
  assert.ok(context.includes("'strict_post' => true"));
});
test('Event Data API is called exactly once', () => {
  assert.strictEqual((phpModule.match(/wp_seed_events_get_event_data/g) || []).length, 1);
});
test('shared people renderer is called exactly once', () => {
  assert.strictEqual((phpModule.match(/wp_seed_events_render_public_event_people_section/g) || []).length, 1);
});
test('no shortcode or business markup is duplicated', () => {
  assert.ok(!phpModule.includes('do_shortcode'));
  assert.ok(!source.includes('[wp_seed_event_people'));
  assert.ok(!source.includes('dangerouslySetInnerHTML'));
  assert.ok(source.includes('disponible sur le frontend'));
  ['<section', '<ul', '<li', 'mailto:', 'tel:'].forEach((token) => assert.ok(!source.includes(token)));
});
test('no private or storage access exists', () => {
  ['get_post_meta', '_wp_seed_event_contacts', 'wp_seed_events_people', 'publish_email', 'publish_phone', 'publish_link', 'person_key', '$wpdb', 'WP_Query', 'wp_remote_'].forEach((token) => {
    assert.ok(!phpModule.includes(token), `Forbidden PHP token: ${token}`);
    assert.ok(!source.includes(token), `Forbidden JS token: ${token}`);
  });
});
test('no People-specific REST route is introduced', () => {
  assert.ok(!phpModule.includes('register_rest_route'));
  assert.ok(!phpModule.includes('/divi-event-people-preview'));
  assert.ok(!source.includes('/wp-seed-events/v1/'));
});
test('editor uses a local context-neutral empty state', () => {
  assert.ok(source.includes('disponible sur le frontend dans un contexte'));
  assert.ok(!source.includes('Chargement des personnes'));
  assert.ok(!source.includes('Aucune personne'));
});
test('editor performs no preview network request', () => {
  assert.ok(!source.includes('useFetch'));
  assert.ok(!source.includes('AbortController'));
  assert.ok(!source.includes('fetch('));
});
test('native loop support is enabled without fixed IDs', () => {
  assert.deepStrictEqual(metadata.attributes.module.settings.advanced.loop, {});
  ['914', '1031', '1036', '1011', '1048', '976', '1205', '1295'].forEach((id) => {
    assert.ok(!phpModule.includes(id)); assert.ok(!source.includes(id));
  });
});
test('stable design selectors use renderer classes', () => {
  ['wp-seed-event-people-section', 'wp-seed-event-people__title', 'wp-seed-event-people__list',
    'wp-seed-event-people__item', 'wp-seed-event-people__name', 'wp-seed-event-people__roles',
    'wp-seed-event-people__role', 'wp-seed-event-people__contacts', 'wp-seed-event-people__email-link',
    'wp-seed-event-people__phone-link', 'wp-seed-event-people__link-anchor'].forEach((selector) => {
    assert.ok(JSON.stringify(metadata).includes(selector), `Missing selector: ${selector}`);
  });
});
test('module uses standard Divi decoration controls', () => {
  ['background', 'sizing', 'spacing', 'border', 'boxShadow'].forEach((control) => {
    assert.ok(metadata.attributes.module.settings.decoration[control]);
  });
});
test('bootstrap registers dependency and app-window bundle once', () => {
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_register_event_people_module/g) || []).length, 2);
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_enqueue_event_people_module_assets/g) || []).length, 2);
  assert.ok(bootstrap.includes("'enqueue_app_window' => true"));
});
test('root workspace wires test lint and build', () => {
  assert.ok(packageJson.scripts['build:divi'].includes('build:divi:people'));
  assert.ok(packageJson.scripts['test:divi'].includes('event-people-module'));
  assert.ok(packageJson.scripts['lint:divi'].includes('event-people-module'));
  assert.ok(webpackConfig.includes("filename: 'wp-seed-events-event-people.js'"));
});
test('packaging includes runtime and excludes sources', () => {
  assert.ok(buildScript.includes('event-people-module/visual-builder'));
  assert.ok(buildScript.includes('wp-seed-events-event-people.js'));
  assert.ok(buildScript.includes("'/src/index.jsx'"));
  assert.ok(buildScript.includes("'/tests/*'"));
});
test('Dates bundle matches the validated Loop Builder context build', () => {
  assert.strictEqual(hashFile(path.join(pluginRoot, 'includes/integrations/divi/event-dates-module/visual-builder/build/wp-seed-events-event-dates.js')), 'D22280E8346F324AB7568816EB02B357A192DD1032AA17771EDBFAD5D3F917D6');
});
test('Visuals bundle matches the validated Loop Builder context build', () => {
  assert.strictEqual(hashFile(path.join(pluginRoot, 'includes/integrations/divi/event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js')), 'C82A7AB7A185D8373C35225390C4DA97956F7B414FD6D35CA2A9F558F1355F5F');
});

assert.strictEqual(passed, 25);
console.log('Divi event people module contract: 25/25 OK');
