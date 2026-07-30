const assert = require('assert');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..');
const read = (...parts) => fs.readFileSync(path.join(...parts), 'utf8');
const hash = (...parts) =>
  crypto.createHash('sha256').update(fs.readFileSync(path.join(...parts))).digest('hex').toUpperCase();
const metadata = JSON.parse(read(root, 'src', 'block.json'));
const builtMetadata = JSON.parse(read(root, 'build', 'block.json'));
const source = read(root, 'src', 'index.js');
const bootstrap = read(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-visuals-block.php',
);
const preview = read(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-visuals-preview.php',
);
const plugin = read(pluginRoot, 'wp-seed-events.php');
const zipBuild = read(pluginRoot, 'build-dev-zip.ps1');
const packageManifest = JSON.parse(read(pluginRoot, 'package.json'));
const datesRoot = path.join(
  pluginRoot,
  'includes',
  'integrations',
  'gutenberg',
  'event-dates-block',
);
const datesMetadata = JSON.parse(read(datesRoot, 'src', 'block.json'));
const diviVisualsRoot = path.join(
  pluginRoot,
  'includes',
  'integrations',
  'divi',
  'event-visuals-module',
  'visual-builder',
);
const diviVisualsMetadata = JSON.parse(read(diviVisualsRoot, 'src', 'module.json'));
let passed = 0;

function check(name, callback) {
  callback();
  passed += 1;
  process.stdout.write('OK ' + passed + ' - ' + name + '\n');
}

check('block ID is distinct', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-visuals-block');
  assert.notStrictEqual(metadata.name, datesMetadata.name);
  assert.notStrictEqual(metadata.name, diviVisualsMetadata.name);
});

check('Block API v3', () => {
  assert.strictEqual(metadata.apiVersion, 3);
  assert.strictEqual(metadata.version, packageManifest.version);
});

check('dynamic save is null', () => {
  assert.ok(source.includes('save: () => null'));
});

check('nine exact attributes', () => {
  assert.deepStrictEqual(Object.keys(metadata.attributes).sort(), [
    'heading_level',
    'image_size',
    'layout',
    'link_original',
    'show_captions',
    'show_document',
    'show_flyer',
    'show_visuals',
    'title',
  ]);
});

check('defaults match the shared renderer', () => {
  assert.deepStrictEqual(
    Object.fromEntries(
      Object.entries(metadata.attributes).map(([name, definition]) => [name, definition.default]),
    ),
    {
      title: 'Visuels de communication',
      heading_level: 'h2',
      show_flyer: true,
      show_visuals: true,
      show_document: true,
      show_captions: false,
      image_size: 'large',
      link_original: true,
      layout: 'grid',
    },
  );
  assert.deepStrictEqual(metadata.attributes.heading_level.enum, ['h2', 'h3', 'h4', 'h5', 'h6']);
  assert.deepStrictEqual(metadata.attributes.layout.enum, ['grid', 'list']);
});

check('no persisted event ID', () => {
  assert.ok(!Object.prototype.hasOwnProperty.call(metadata.attributes, 'eventId'));
  assert.ok(!source.includes('eventId'));
  assert.ok(!bootstrap.includes('eventId'));
});

check('explicit and public contexts are guarded', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
  assert.ok(bootstrap.includes("array_key_exists( 'postId', $context )"));
  assert.ok(bootstrap.includes("array_key_exists( 'postType', $context )"));
  assert.ok(bootstrap.includes('if ( $has_explicit_post_context )'));
  assert.ok(
    bootstrap.indexOf('if ( $has_explicit_post_context )') <
      bootstrap.indexOf('global $wp_seed_events_public_event_id'),
  );
  assert.ok(
    bootstrap.includes("return 0;\n\t}\n\n\tglobal $wp_seed_events_public_event_id"),
  );
});

check('Event Data API is called once', () => {
  assert.strictEqual((bootstrap.match(/wp_seed_events_get_event_data\s*\(/g) || []).length, 1);
});

check('shared visuals renderer is called once', () => {
  assert.strictEqual(
    (bootstrap.match(/wp_seed_events_render_public_event_visuals_section\s*\(/g) || []).length,
    1,
  );
});

check('shortcodes are not used', () => {
  [bootstrap, preview, source].forEach((content) => {
    assert.ok(!content.includes('do_shortcode'));
    assert.ok(!content.includes('[wp_seed_event_visuals'));
  });
});

check('private post meta is not read', () => {
  [bootstrap, preview, source].forEach((content) => assert.ok(!content.includes('get_post_meta')));
});

check('SQL is not queried', () => {
  [bootstrap, preview, source].forEach((content) => {
    assert.ok(!content.includes('$wpdb'));
    assert.ok(!content.includes('SELECT '));
  });
});

check('JavaScript contains no media business markup', () => {
  [
    'dangerouslySetInnerHTML',
    'wp_seed_events_render_public_event_visuals_section',
    'wp-seed-event-visuals__item',
    '<figure',
    '<img',
    '<figcaption',
    'featured_image',
    'communication_visuals',
  ].forEach((contract) => assert.ok(!source.includes(contract), 'Forbidden JS contract: ' + contract));
  assert.ok(source.includes('RawHTML'));
});

check('inspector labels and controls are exact', () => {
  [
    'Titre',
    'Niveau du titre',
    'Afficher le recto',
    'Afficher les autres visuels',
    'Afficher le document',
    'Afficher les légendes',
    'Taille d’image',
    'Lier vers le fichier original',
    'Disposition',
  ].forEach((label) => assert.ok(source.includes(label), 'Missing inspector label: ' + label));
  assert.strictEqual((source.match(/<TextControl\b/g) || []).length, 1);
  assert.strictEqual((source.match(/<SelectControl\b/g) || []).length, 3);
  assert.strictEqual((source.match(/<ToggleControl\b/g) || []).length, 5);
});

check('preview route is protected and read-only', () => {
  assert.strictEqual((preview.match(/register_rest_route\s*\(/g) || []).length, 1);
  [
    "'/gutenberg-event-visuals-preview'",
    'WP_REST_Server::CREATABLE',
    'is_user_logged_in()',
    "current_user_can( 'edit_posts' )",
    "current_user_can( 'edit_post', $post_id )",
    "'html'",
    "'empty'",
    "'message'",
  ].forEach((contract) => assert.ok(preview.includes(contract), 'Missing REST contract: ' + contract));
  [
    'update_post_meta',
    'delete_post_meta',
    'update_option',
    'delete_option',
    'wp_insert_post',
    'wp_update_post',
    'wp_delete_post',
  ].forEach((contract) => assert.ok(!preview.includes(contract), 'Forbidden write: ' + contract));
});

check('loading empty and error states are present', () => {
  [
    "status: 'loading'",
    "empty ? 'empty' : 'ready'",
    "status: 'error'",
    'Spinner',
    'Notice',
    'Aucun visuel à afficher dans ce contexte.',
  ].forEach((contract) => assert.ok(source.includes(contract), 'Missing state contract: ' + contract));
});

check('preview is debounced by 250 ms', () => {
  assert.ok(source.includes('const PREVIEW_DELAY = 250'));
  assert.ok(source.includes('window.setTimeout'));
  assert.ok(source.includes('window.clearTimeout'));
});

check('in-flight previews are aborted', () => {
  assert.ok(source.includes('AbortController'));
  assert.ok(source.includes('controller.abort()'));
  assert.ok(source.includes("error?.name === 'AbortError'"));
});

check('stale preview responses are ignored', () => {
  assert.ok(source.includes('requestSequence'));
  assert.ok(source.includes('requestId !== requestSequence.current'));
  assert.ok(source.includes('! active'));
});

check('instances keep independent state', () => {
  assert.ok(source.includes('function Edit('));
  assert.ok(source.includes('const [ preview, setPreview ] = useState'));
  assert.ok(source.includes('const requestSequence = useRef( 0 )'));
  assert.strictEqual((source.match(/registerBlockType\s*\(/g) || []).length, 1);
});

check('native block supports are declared', () => {
  assert.strictEqual(metadata.supports.anchor, true);
  assert.strictEqual(metadata.supports.customClassName, true);
  assert.strictEqual(metadata.supports.html, false);
  assert.strictEqual(metadata.supports.color.text, true);
  assert.strictEqual(metadata.supports.color.background, true);
  assert.strictEqual(metadata.supports.color.link, true);
  assert.strictEqual(metadata.supports.spacing.margin, true);
  assert.strictEqual(metadata.supports.spacing.padding, true);
  assert.strictEqual(metadata.supports.typography.fontSize, true);
  assert.strictEqual(metadata.supports.typography.lineHeight, true);
  assert.strictEqual(metadata.supports.__experimentalBorder.radius, true);
  assert.strictEqual(metadata.supports.shadow, true);
  assert.ok(bootstrap.includes('get_block_wrapper_attributes'));
  assert.ok(
    bootstrap.indexOf("if ( '' === trim( $html )") <
      bootstrap.indexOf('get_block_wrapper_attributes('),
  );
});

check('Core Query Loop context remains card-local', () => {
  assert.ok(source.includes('context.postId'));
  assert.ok(source.includes('context.postType'));
  assert.ok(source.includes('context.queryId'));
  assert.ok(!bootstrap.includes("array_key_exists( 'queryId', $context )"));
});

check('no Astra or Spectra adapter dependency', () => {
  [bootstrap, preview, source, JSON.stringify(metadata)].forEach((content) => {
    assert.ok(!/spectra|ultimate-addons|astra/i.test(content));
  });
});

check('build metadata and scripts are wired', () => {
  assert.deepStrictEqual(builtMetadata, metadata);
  assert.ok(fs.existsSync(path.join(root, 'build', 'index.js')));
  assert.ok(fs.existsSync(path.join(root, 'build', 'index.asset.php')));
  assert.ok(packageManifest.scripts['build:gutenberg:visuals']);
  assert.ok(packageManifest.scripts['test:gutenberg'].includes('event-visuals-block'));
  assert.ok(plugin.includes("require_once __DIR__ . '/includes/integrations/gutenberg/event-visuals-block.php';"));
  assert.ok(bootstrap.includes("add_action( 'init', 'wp_seed_events_register_event_visuals_block'"));
  assert.strictEqual((bootstrap.match(/register_block_type_from_metadata\s*\(/g) || []).length, 1);
});

check('packaging includes runtime assets and excludes sources', () => {
  [
    '$gutenbergVisualsBlockJson',
    '$gutenbergVisualsBlockScript',
    '$gutenbergVisualsBlockAsset',
    '"$gutenbergVisualsRuntimeRoot/tests/*"',
    '"$gutenbergVisualsRuntimeRoot/src/*"',
  ].forEach((contract) => assert.ok(zipBuild.includes(contract), 'Missing ZIP contract: ' + contract));
});

check('Gutenberg Dates matches the expanded controls build', () => {
  assert.strictEqual(
    hash(datesRoot, 'build', 'index.js'),
    'EF6751EF1E9415C55EF73A01EEE200F25577726A861E1D441DB5BE8BFE13D3BE',
  );
});

check('Divi modules match the validated Loop Builder context builds', () => {
  assert.strictEqual(
    hash(
      pluginRoot,
      'includes',
      'integrations',
      'divi',
      'event-dates-module',
      'visual-builder',
      'build',
      'wp-seed-events-event-dates.js',
    ),
    'F8E1DA0A85A16776A20FA7AB19C28468993561E57DAB17A3F5B64DC71A2BC37D',
  );
  assert.strictEqual(
    hash(diviVisualsRoot, 'build', 'wp-seed-events-event-visuals.js'),
    'C82A7AB7A185D8373C35225390C4DA97956F7B414FD6D35CA2A9F558F1355F5F',
  );
});

assert.strictEqual(passed, 27);
console.log('Gutenberg event visuals block contract: 27/27 OK');
