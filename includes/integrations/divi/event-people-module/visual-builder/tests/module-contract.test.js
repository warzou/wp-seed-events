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
const publicCss = fs.readFileSync(path.join(pluginRoot, 'includes/public/event-lists.css'), 'utf8');
const publicRenderer = fs.readFileSync(path.join(pluginRoot, 'includes/public/rendering.php'), 'utf8');
const items = metadata.attributes.content.settings.innerContent.items;
const defaults = metadata.attributes.content.default.innerContent.desktop.value;
const fields = Object.values(items).map((item) => item.subName).sort();
let passed = 0;
const test = (name, callback) => { callback(); passed += 1; console.log(`ok ${passed} - ${name}`); };

const hashFile = (file) => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex').toUpperCase();

test('canonical module identity is unique', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-people');
  assert.strictEqual((source.match(/registerModule\(/g) || []).length, 1);
});
test('French label and shared folder are configured', () => {
  assert.strictEqual(metadata.title, 'WP Seed — Personnes de l’événement');
  assert.strictEqual(metadata.folder, 'wp-seed-events');
  assert.strictEqual((source.match(/registerFolder\(\{/g) || []).length, 1);
});
test('content fields expose only the final composable people controls', () => {
  assert.deepStrictEqual(fields, ['contact_layout', 'contact_separator', 'email_clickable', 'heading_level', 'name_contact_separator', 'phone_clickable', 'show_contact_separator', 'show_email', 'show_link', 'show_name', 'show_name_contact_separator', 'show_phone', 'show_title', 'site_clickable', 'title']);
  ['show_roles', 'layout'].forEach((field) => assert.ok(!fields.includes(field)));
});
test('renderer defaults are preserved', () => {
  assert.deepStrictEqual(defaults, {
	title: 'Contacts et intervenants', heading_level: 'h2', role: 'all', people_contract: 'composable-v3', show_name: 'on', show_roles: 'off', show_title: 'on',
	show_email: 'on', show_phone: 'on', show_link: 'on', email_clickable: 'on', phone_clickable: 'on', site_clickable: 'on',
	contact_layout: 'stacked', show_contact_separator: 'off', contact_separator: '\u2014',
	show_name_contact_separator: 'off', name_contact_separator: '\u2014', layout: 'list',
  });
});
test('canonical role filter is loaded dynamically and keeps historical aliases', () => {
  assert.ok(source.includes('loadCanonicalPersonTypes'));
  assert.ok(source.includes('canonicalPersonTypeFields'));
  assert.ok(source.includes('wp-seed-events/v1/person-types'));
  assert.ok(source.includes("cache: 'no-store'"));
  assert.ok(!Object.values(items).some((item) => /^role_/.test(item.subName ?? '')));
  assert.ok(phpModule.includes('$has_role_toggles'));
  assert.ok(phpModule.includes("'role_registration_contact' => 'contact'"));
  assert.ok(phpModule.includes("'role_information_contact'  => 'contact'"));
  assert.ok(phpModule.includes("'roles'         => $roles"));
  assert.ok(defaults.role === 'all');
});
test('coordinates are independently composable', () => {
  assert.ok(!items.phoneAction);
  assert.strictEqual(items.phoneClickable.subName, 'phone_clickable');
  assert.ok(items.phoneClickable.description.includes('respecte l’action'));
  assert.deepStrictEqual(Object.keys(items.contactLayout.component.props.options), ['stacked', 'inline', 'with_name']);
  assert.strictEqual(items.contactLayout.features.responsive, true);
  assert.strictEqual(items.emailClickable.subName, 'email_clickable');
  assert.strictEqual(items.siteClickable.subName, 'site_clickable');
  assert.strictEqual(items.siteClickable.label, 'Liens cliquables');
  assert.ok(!items.siteLabel);
  assert.ok(phpModule.includes("'composable-v1' === $people_contract"));
  assert.ok(phpModule.includes("array( 'composable-v2', 'composable-v3' )"));
  assert.ok(phpModule.includes("'composable-v3'"));
  assert.ok(phpModule.includes("'site_label'"));
});
test('Content UI groups related controls in a stable business order', () => {
  const contentGroups = Object.values(metadata.settings.groups)
    .filter((group) => group.panel === 'content')
    .sort((a, b) => a.priority - b.priority);
  assert.deepStrictEqual(contentGroups.map((group) => group.component.props.groupLabel), [
    'Titre', 'Filtrage', 'Nom', 'Email', 'Téléphone', 'Liens', 'Disposition', 'Séparateurs',
  ]);
  const groupedFields = Object.fromEntries(contentGroups.map((group) => [
    group.component.props.groupLabel,
    Object.values(items).filter((item) => item.groupSlug === group.groupName).map((item) => item.subName),
  ]));
  assert.deepStrictEqual(groupedFields.Email, ['show_email', 'email_clickable']);
  assert.deepStrictEqual(groupedFields['Téléphone'], ['show_phone', 'phone_clickable']);
  assert.deepStrictEqual(groupedFields.Liens, ['show_link', 'site_clickable']);
  assert.deepStrictEqual(groupedFields.Filtrage, []);
});
test('contact separators are independent, lightweight and responsive', () => {
  assert.ok(metadata.attributes.contactSeparatorStyle);
  assert.ok(metadata.attributes.nameContactSeparatorStyle);
  assert.ok(metadata.attributes.contactSeparatorStyle.selector.includes(':not('));
  assert.ok(metadata.attributes.nameContactSeparatorStyle.selector.includes('--name'));
  assert.strictEqual(metadata.attributes.contactSeparatorStyle.settings, undefined);
  assert.deepStrictEqual(Object.keys(metadata.attributes.nameContactSeparatorStyle.settings.advanced), ['color', 'fontSize', 'spaceBefore', 'spaceAfter']);
  Object.values(metadata.attributes.nameContactSeparatorStyle.settings.advanced).forEach(({ item }) => assert.strictEqual(item.features.responsive, true));
  assert.ok(source.includes('stylePeoplePreviewHtml'));
  assert.ok(source.includes('--wp-seed-event-people-${variablePrefix}-'));
});

test('contact layout can place coordinates with the name', () => {
  const options = metadata.attributes.content.settings.innerContent.items.contactLayout.component.props.options;
  assert.deepStrictEqual(Object.keys(options), ['stacked', 'inline', 'with_name']);
  assert.strictEqual(options.with_name.label, 'Avec le nom');
  assert.ok(publicRenderer.includes("'with-name' === $value"));
  assert.ok(publicRenderer.includes("array( 'inline', 'with_name' )"));
  assert.ok(publicCss.includes('is-contact-layout-desktop-with_name'));
  assert.ok(publicCss.includes('wp-seed-event-people__contact-separator--name'));
});
test('historical role and list layout values stay runtime-only', () => {
  assert.ok(!items.showRoles);
  assert.ok(!items.layout);
  assert.strictEqual(defaults.layout, 'list');
  assert.strictEqual(defaults.show_roles, 'off');
  assert.ok(phpModule.includes("$values['layout'] ?? 'list'"));
  assert.ok(phpModule.includes("$values['show_roles'] ?? ( 'legacy' === $people_contract )"));
  assert.ok(metadata.attributes.rolesStyle.selector.includes('wp-seed-event-people__roles'));
  assert.ok(metadata.attributes.roleStyle.selector.includes('wp-seed-event-people__role'));
  assert.ok(!metadata.attributes.rolesStyle.settings);
  assert.ok(!metadata.attributes.roleStyle.settings);
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
  assert.ok(source.includes('dangerouslySetInnerHTML'));
  assert.ok(source.includes('/divi-event-people-preview'));
  ['<section', '<ul', '<li', 'mailto:', 'tel:'].forEach((token) => assert.ok(!source.includes(token)));
});
test('no private or storage access exists', () => {
  ['get_post_meta', '_wp_seed_event_contacts', 'wp_seed_events_people', 'publish_email', 'publish_phone', 'publish_link', 'person_key', '$wpdb', 'WP_Query', 'wp_remote_'].forEach((token) => {
    assert.ok(!phpModule.includes(token), `Forbidden PHP token: ${token}`);
    assert.ok(!source.includes(token), `Forbidden JS token: ${token}`);
  });
});
test('authenticated People preview uses the canonical renderer', () => {
  assert.ok(phpModule.includes('register_rest_route'));
  assert.ok(phpModule.includes('/divi-event-people-preview'));
  assert.ok(source.includes('/wp-seed-events/v1/divi-event-people-preview'));
});
test('editor renders the server response without a generic empty message', () => {
  assert.ok(source.includes('Chargement des personnes'));
  assert.ok(!source.includes('Aucune personne'));
});
test('editor request is keyed by the common event context', () => {
  assert.ok(source.includes('resolveCurrentEventContext'));
  assert.ok(source.includes('context.cacheKey'));
  assert.ok(source.includes('AbortController'));
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
    'wp-seed-event-people__phone-link', 'wp-seed-event-people__link-anchor', 'wp-seed-event-people__email-text', 'wp-seed-event-people__phone-text', 'wp-seed-event-people__link-text'].forEach((selector) => {
    assert.ok(JSON.stringify(metadata).includes(selector), `Missing selector: ${selector}`);
  });
});
test('alignment controls target full-width rendered containers', () => {
  ['listStyle', 'nameStyle', 'rolesStyle', 'contactsStyle'].forEach((attribute) => {
    assert.ok(metadata.attributes[attribute], `Missing alignment target: ${attribute}`);
  });
  assert.ok(metadata.attributes.nameStyle.settings.decoration.font);
  assert.ok(metadata.attributes.contactsStyle.settings.decoration.font);
  const css = fs.readFileSync(path.join(pluginRoot, 'includes/public/event-lists.css'), 'utf8');
  assert.ok(css.includes('.wp-seed-event-people__name {\n\tdisplay: block;'));
  assert.ok(css.includes('width: 100%;'));
  assert.ok(css.includes('.wp-seed-event-people__contacts > li {\n\ttext-align: inherit;'));
});
test('common coordinate typography targets plain text and links before legacy overrides', () => {
  const commonSelector = metadata.attributes.contactsStyle.selector;
  [
    'wp-seed-event-people__contacts',
    'wp-seed-event-people__email-link',
    'wp-seed-event-people__email-text',
    'wp-seed-event-people__phone-link',
    'wp-seed-event-people__phone-text',
    'wp-seed-event-people__link-anchor',
    'wp-seed-event-people__link-text',
  ].forEach((className) => assert.ok(commonSelector.includes(className), `Common coordinate selector misses ${className}.`));
  const commonIndex = source.indexOf("attrName: 'contactsStyle'");
  ['emailLinkStyle', 'phoneLinkStyle', 'publicLinkStyle'].forEach((attribute) => {
    assert.ok(source.indexOf(`attrName: '${attribute}'`) > commonIndex, `${attribute} must remain after the common coordinate style.`);
  });
});
test('legacy decorations remain renderable but hidden from the new Style UI', () => {
  assert.deepStrictEqual(Object.keys(metadata.attributes.module.settings.decoration), ['layout']);
  assert.strictEqual(metadata.attributes.sectionStyle.settings, undefined);
  ['listStyle', 'emailLinkStyle', 'phoneLinkStyle', 'publicLinkStyle', 'contactSeparatorStyle'].forEach((attribute) => {
    assert.ok(metadata.attributes[attribute]);
    assert.strictEqual(metadata.attributes[attribute].settings, undefined);
    assert.ok(source.includes(`attrName: '${attribute}'`));
    assert.ok(phpModule.includes(`'${attribute}'`));
  });
});
test('Style UI uses the concise people groups', () => {
  const groups = [];
  Object.values(metadata.attributes).forEach((attribute) => {
    Object.values(attribute?.settings?.decoration ?? {}).forEach((feature) => groups.push(feature?.component?.props?.groupLabel));
  });
  Object.values(metadata.settings.groups).forEach((group) => {
    if (group.panel === 'design') groups.push(group.component.props.groupLabel);
  });
  assert.deepStrictEqual(groups, ['Module', 'Titre', 'Personne', 'Nom', 'Coordonnées', 'Liste', 'Séparateur Nom / Coordonnées']);
  assert.strictEqual(new Set(groups).size, 7);
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
test('Divi event bundles exist and are non-empty', () => {
  ['event-dates-module/visual-builder/build/wp-seed-events-event-dates.js', 'event-visuals-module/visual-builder/build/wp-seed-events-event-visuals.js'].forEach((relative) => {
    assert.ok(fs.statSync(path.join(pluginRoot, 'includes/integrations/divi', relative)).size > 0);
  });
});

assert.strictEqual(passed, 31);
console.log('Divi event people module contract: 31/31 OK');
