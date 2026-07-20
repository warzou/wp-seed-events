const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..');
const read = (...parts) => fs.readFileSync(path.join(...parts), 'utf8');
const metadata = JSON.parse(read(root, 'src', 'block.json'));
const source = read(root, 'src', 'index.js');
const bootstrap = read(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-people-block.php',
);
const plugin = read(pluginRoot, 'wp-seed-events.php');
const zipBuild = read(pluginRoot, 'build-dev-zip.ps1');
const packageManifest = JSON.parse(read(pluginRoot, 'package.json'));
let passed = 0;

function check(name, callback) {
  callback();
  passed += 1;
  process.stdout.write('OK ' + passed + ' - ' + name + '\n');
}

check('canonical block identity', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-people-block');
  assert.strictEqual(metadata.apiVersion, 3);
  assert.strictEqual(metadata.title, 'WP Seed — Personnes de l’événement');
  assert.strictEqual(metadata.category, 'widgets');
  assert.strictEqual(metadata.icon, 'groups');
});

check('eight exact attributes', () => {
  assert.deepStrictEqual(Object.keys(metadata.attributes).sort(), [
    'heading_level',
    'layout',
    'role',
    'show_email',
    'show_link',
    'show_phone',
    'show_roles',
    'title',
  ]);
});

check('defaults match the people renderer', () => {
  assert.deepStrictEqual(
    Object.fromEntries(
      Object.entries(metadata.attributes).map(([name, definition]) => [name, definition.default]),
    ),
    {
      title: 'Contacts et intervenants',
      heading_level: 'h2',
      role: 'all',
      show_roles: true,
      show_email: true,
      show_phone: true,
      show_link: true,
      layout: 'list',
    },
  );
});

check('role and layout enums are exact', () => {
  assert.deepStrictEqual(metadata.attributes.role.enum, [
    'all',
    'organizer',
    'speaker',
    'registration_contact',
    'information_contact',
  ]);
  assert.deepStrictEqual(metadata.attributes.layout.enum, ['list', 'grid']);
});

check('dynamic save is null', () => assert.ok(source.includes('save: () => null')));

check('context is inherited without event ID', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
  assert.ok(!Object.prototype.hasOwnProperty.call(metadata.attributes, 'eventId'));
  assert.ok(!source.includes('eventId'));
});

check('strict explicit context precedes public fallback', () => {
  assert.ok(bootstrap.includes("array_key_exists( 'postId', $context )"));
  assert.ok(bootstrap.includes("array_key_exists( 'postType', $context )"));
  assert.ok(
    bootstrap.indexOf('if ( $has_explicit_post_context )') <
      bootstrap.indexOf('global $wp_seed_events_public_event_id'),
  );
});

check('Event Data is loaded once', () => {
  assert.strictEqual((bootstrap.match(/wp_seed_events_get_event_data\s*\(/g) || []).length, 1);
});

check('shared people renderer is called once', () => {
  assert.strictEqual(
    (bootstrap.match(/wp_seed_events_render_public_event_people_section\s*\(/g) || []).length,
    1,
  );
});

check('no business HTML is built in JavaScript', () => {
  [
    'wp-seed-event-people__item',
    'wp-seed-event-people__contacts',
    '<section',
    '<ul',
    '<li',
    'dangerouslySetInnerHTML',
  ].forEach((value) => assert.ok(!source.includes(value), 'Forbidden JS markup: ' + value));
});

check('Inspector exposes the eight French controls', () => {
  [
    'Titre',
    'Niveau du titre',
    'Rôle affiché',
    'Afficher les rôles',
    'Afficher les emails autorisés',
    'Afficher les téléphones autorisés',
    'Afficher les liens autorisés',
    'Mise en page',
  ].forEach((label) => assert.ok(source.includes(label), 'Missing label: ' + label));
  assert.strictEqual((source.match(/<TextControl\b/g) || []).length, 1);
  assert.strictEqual((source.match(/<SelectControl\b/g) || []).length, 3);
  assert.strictEqual((source.match(/<ToggleControl\b/g) || []).length, 4);
});

check('privacy help is explicit', () => {
  [
    'Les coordonnées sont privées par défaut',
    'Seuls les emails autorisés',
    'ne modifie pas son autorisation',
    'ne peut jamais publier une coordonnée non autorisée',
  ].forEach((value) => assert.ok(source.includes(value), 'Missing privacy help: ' + value));
});

check('standard WordPress block renderer is used', () => {
  assert.ok(source.includes("from '@wordpress/server-side-render'"));
  assert.ok(source.includes('<ServerSideRender'));
  assert.ok(source.includes('urlQueryArgs'));
  assert.ok(!source.includes('/wp-seed-events/v1/'));
});

check('editor states are neutral', () => {
  ['LoadingPreview', 'EmptyPreview', 'ErrorPreview', 'Aucune personne à afficher dans ce contexte.']
    .forEach((value) => assert.ok(source.includes(value), 'Missing editor state: ' + value));
});

check('native block supports match existing collection blocks', () => {
  assert.strictEqual(metadata.supports.anchor, true);
  assert.strictEqual(metadata.supports.customClassName, true);
  assert.strictEqual(metadata.supports.html, false);
  assert.strictEqual(metadata.supports.color.text, true);
  assert.strictEqual(metadata.supports.spacing.margin, true);
  assert.strictEqual(metadata.supports.typography.fontSize, true);
  assert.strictEqual(metadata.supports.__experimentalBorder.radius, true);
  assert.strictEqual(metadata.supports.shadow, true);
});

check('no private people contract is exposed', () => {
  [source, bootstrap, JSON.stringify(metadata)].forEach((content) => {
    ['publish_email', 'publish_phone', 'publish_link', 'person_key', '_wp_seed_event_contacts']
      .forEach((value) => assert.ok(!content.includes(value), 'Private key exposed: ' + value));
  });
});

check('no direct storage or SQL access', () => {
  [source, bootstrap].forEach((content) => {
    ['get_post_meta', '$wpdb', 'SELECT ', 'wp_seed_events_people'].forEach((value) => {
      assert.ok(!content.includes(value), 'Forbidden storage access: ' + value);
    });
  });
});

check('no people-specific REST route exists', () => {
  assert.ok(!bootstrap.includes('register_rest_route'));
  assert.ok(!plugin.includes('gutenberg-event-people-preview'));
  assert.ok(!source.includes('gutenberg-event-people-preview'));
});

check('registration is unique and server-rendered', () => {
  assert.strictEqual((bootstrap.match(/register_block_type_from_metadata\s*\(/g) || []).length, 1);
  assert.ok(bootstrap.includes("'render_callback' => 'wp_seed_events_render_gutenberg_event_people_block'"));
  assert.ok(bootstrap.includes("add_action( 'init', 'wp_seed_events_register_event_people_block', 20 )"));
});

check('empty renderer output has no wrapper', () => {
  assert.ok(
    bootstrap.indexOf("if ( '' === trim( $html )") <
      bootstrap.indexOf('get_block_wrapper_attributes('),
  );
});

check('plugin bootstrap loads the block once', () => {
  const needle = "require_once __DIR__ . '/includes/integrations/gutenberg/event-people-block.php';";
  assert.strictEqual((plugin.match(new RegExp(needle.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g')) || []).length, 1);
});

check('workspace scripts include people block', () => {
  assert.ok(packageManifest.scripts['build:gutenberg:people']);
  assert.ok(packageManifest.scripts['build:gutenberg'].includes('build:gutenberg:people'));
  assert.ok(packageManifest.scripts['test:gutenberg'].includes('event-people-block'));
  assert.ok(packageManifest.scripts['lint:gutenberg'].includes('event-people-block'));
});

check('packaging includes runtime and excludes sources', () => {
  [
    '$gutenbergPeopleBlockJson',
    '$gutenbergPeopleBlockScript',
    '$gutenbergPeopleBlockAsset',
    '"$gutenbergPeopleRuntimeRoot/tests/*"',
    '"$gutenbergPeopleRuntimeRoot/src/*"',
  ].forEach((value) => assert.ok(zipBuild.includes(value), 'Missing ZIP contract: ' + value));
});

check('no Divi or shortcode dependency', () => {
  [source, bootstrap].forEach((content) => {
    ['do_shortcode', '[wp_seed_event_people', 'divi.'].forEach((value) => {
      assert.ok(!content.includes(value), 'Forbidden adapter dependency: ' + value);
    });
  });
});

check('block metadata description promises filtered contacts only', () => {
  assert.ok(metadata.description.includes('uniquement les coordonnées autorisées'));
});

assert.strictEqual(passed, 25);
console.log('Gutenberg event people block contract: 25/25 OK');
