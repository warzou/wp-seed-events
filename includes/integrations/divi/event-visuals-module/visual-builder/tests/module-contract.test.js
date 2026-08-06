const assert = require('assert');
const crypto = require('crypto');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const webpackConfig = fs.readFileSync(path.join(root, 'webpack.config.js'), 'utf8');
const phpModule = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'class-event-visuals-module.php'),
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
const packageJson = JSON.parse(fs.readFileSync(path.join(pluginRoot, 'package.json'), 'utf8'));
const buildScript = fs.readFileSync(path.join(pluginRoot, 'build-dev-zip.ps1'), 'utf8');
const { getDiviAttribute, getDiviLoopPostId } = require(path.join(root, 'src', 'loop-context.js'));
const datesBundlePath = path.join(
  pluginRoot,
  'includes',
  'integrations',
  'divi',
  'event-dates-module',
  'visual-builder',
  'build',
  'wp-seed-events-event-dates.js',
);
const resolverSource = contextHelper.slice(
  contextHelper.indexOf('function wp_seed_events_divi_resolve_event_id'),
);
const contentItems = metadata.attributes.content.settings.innerContent.items;
const defaults = metadata.attributes.content.default.innerContent.desktop.value;
const persistentFields = Object.values(contentItems).map((item) => item.subName);
let passed = 0;

const test = (name, callback) => {
  callback();
  passed += 1;
  console.log('ok ' + passed + ' - ' + name);
};

test('module ID is distinct and registered once', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-visuals');
  assert.notStrictEqual(metadata.name, 'wp-seed-events/event-dates');
  assert.strictEqual((source.match(/registerModule\(metadata, eventVisualsModule\)/g) || []).length, 1);
});

test('module uses the shared WP Seed Events folder', () => {
  assert.strictEqual(metadata.folder, 'wp-seed-events');
  assert.strictEqual((source.match(/registerFolder\(\{/g) || []).length, 1);
  assert.ok(source.includes("title: 'WP Seed Events'"));
});

test('content options are exact and image sizes are constrained', () => {
  assert.deepStrictEqual(
    persistentFields.sort(),
    [
      'heading_level',
      'image_size',
      'layout',
      'link_original',
      'show_captions',
      'show_document',
      'show_flyer',
      'show_visuals',
      'title',
    ].sort(),
  );
  assert.deepStrictEqual(
    Object.keys(contentItems.imageSize.component.props.options),
    ['thumbnail', 'medium', 'medium_large', 'large', 'full'],
  );
});

test('content defaults match the shared renderer contract', () => {
  assert.deepStrictEqual(defaults, {
    title: 'Visuels de communication',
    heading_level: 'h2',
    show_flyer: 'on',
    show_visuals: 'on',
    show_document: 'on',
    show_captions: 'off',
    image_size: 'large',
    link_original: 'on',
    layout: 'grid',
  });
});

test('context follows the validated Dates resolver', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'queryId']);
  assert.ok(phpModule.includes('wp_seed_events_divi_resolve_event_id'));
  assert.ok(resolverSource.indexOf('$loop_id =') < resolverSource.indexOf('$post_id ='));
  assert.ok(resolverSource.includes('return wp_seed_events_divi_is_event( $loop_id ) ? $loop_id : 0;'));
});
test('module opts into the native Divi Loop Builder', () => {
  assert.deepStrictEqual(metadata.attributes.module.settings.advanced.loop, {});
});
test('loop context consumes the Divi runtime loop post ID', () => {
  assert.deepStrictEqual(metadata.attributes.__loop_post_id, { type: 'string', default: '' });
  assert.ok(source.includes("name\":\"loop_post_id"));
  assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
  assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
  assert.ok(contextHelper.includes('DynamicContentUtils'));
  assert.ok(contextHelper.includes('get_loop_post_id'));
  assert.ok(contextHelper.includes("return array( 'loop_id' => $loop_post_id );"));
  assert.ok(source.includes('loop_id: loopPostId'));
  assert.ok(!phpModule.includes('get_the_ID()'));
  assert.ok(!phpModule.includes('WPSEED_V4_CONTEXT'));
});

test('Visual Builder reads plain and Immutable Divi loop attributes', () => {
  const immutableAttrs = {
    get: (key) => (key === '__loop_post_id' ? '202' : undefined),
  };

  assert.strictEqual(getDiviAttribute({ __loop_post_id: '101' }, '__loop_post_id'), '101');
  assert.strictEqual(getDiviLoopPostId({ __loop_post_id: '101' }), 101);
  assert.strictEqual(getDiviLoopPostId(immutableAttrs), 202);
});

test('Visual Builder rejects missing or invalid Divi loop IDs', () => {
  assert.strictEqual(getDiviLoopPostId({}), 0);
  assert.strictEqual(getDiviLoopPostId({ __loop_post_id: 'not-an-id' }), 0);
  assert.strictEqual(getDiviLoopPostId({ __loop_post_id: '-10' }), 0);
  assert.strictEqual(getDiviLoopPostId({ __loop_post_id: '10.5' }), 0);
});
test('Event Data API is called exactly once', () => {
  assert.strictEqual((phpModule.match(/wp_seed_events_get_event_data/g) || []).length, 1);
});

test('shared visuals renderer is called exactly once', () => {
  assert.strictEqual(
    (phpModule.match(/wp_seed_events_render_public_event_visuals_section/g) || []).length,
    1,
  );
});

test('module does not use a shortcode', () => {
  assert.ok(!phpModule.includes('do_shortcode'));
  assert.ok(!phpModule.includes('[wp_seed_event_visuals'));
  assert.ok(!source.includes('[wp_seed_event_visuals'));
});

test('module does not access post meta', () => {
  assert.ok(!phpModule.includes('get_post_meta'));
  assert.ok(!phpModule.includes('_wp_seed_event_'));
});

test('module adds no SQL or HTTP request', () => {
  assert.ok(!phpModule.includes('$wpdb'));
  assert.ok(!phpModule.includes('WP_Query'));
  assert.ok(!phpModule.includes('wp_remote_'));
});

test('module contains no fixed fixture or event ID', () => {
  ['914', '1011', '1022', '1031', '1048', '1057', '976', '1205', '1295'].forEach((id) => {
    assert.ok(!phpModule.includes(id));
    assert.ok(!source.includes(id));
  });
});

test('React does not duplicate media business markup', () => {
  assert.ok(source.includes('dangerouslySetInnerHTML'));
  assert.ok(!source.includes('<figure'));
  assert.ok(!source.includes('<img'));
  assert.ok(!source.includes('<figcaption'));
  assert.ok(!source.includes('featured_image'));
  assert.ok(!source.includes('communication_visuals'));
});

test('preview route is read-only and permission protected', () => {
  assert.ok(phpModule.includes('WP_REST_Server::READABLE'));
  assert.ok(phpModule.includes('edit_posts'));
  assert.ok(phpModule.includes('edit_post'));
  assert.ok(phpModule.includes('$context_id'));
  assert.ok(!phpModule.includes('update_post_meta'));
  assert.ok(!phpModule.includes('delete_post_meta'));
});

test('preview exposes loading, empty and error states', () => {
  assert.ok(source.includes('Chargement des visuels'));
  assert.ok(source.includes('Aucun visuel à afficher dans ce contexte.'));
  assert.ok(source.includes('L’aperçu des visuels est indisponible.'));
  assert.ok(source.includes("role='status'"));
  assert.ok(source.includes("role='alert'"));
});

test('stale preview requests are aborted', () => {
  assert.ok(source.includes('AbortController'));
  assert.ok(source.includes('abortRef.current.abort()'));
  assert.ok(source.includes('signal: controller.signal'));
});

test('stable Design selectors cover the shared renderer', () => {
  [
    'wp-seed-event-visuals',
    'wp-seed-event-visuals__title',
    'wp-seed-event-visuals__list',
    'is-layout-grid',
    'is-layout-list',
    'wp-seed-event-visuals__item',
    'wp-seed-event-visuals__figure',
    'wp-seed-event-visuals__image',
    'wp-seed-event-visuals__caption',
    'wp-seed-event-visuals__document',
    'wp-seed-event-visuals__image-link',
    'wp-seed-event-visuals__document-link',
  ].forEach((selector) => {
    assert.ok(JSON.stringify(metadata).includes(selector), 'Missing selector: ' + selector);
  });
});

test('preview state and cancellation are instance-local', () => {
  assert.ok(source.includes('const abortRef = useRef()'));
  assert.ok(source.includes('const [hasError, setHasError] = useState(false)'));
  assert.ok(!source.includes('window.wpSeedEventsEventId'));
});

test('a distinct visuals build is wired into the root workspace', () => {
  assert.ok(webpackConfig.includes("filename: 'wp-seed-events-event-visuals.js'"));
  assert.ok(packageJson.scripts['build:divi'].includes('build:divi:visuals'));
  assert.ok(packageJson.scripts['test:divi'].includes('event-visuals-module'));
  assert.ok(packageJson.scripts['lint:divi'].includes('event-visuals-module'));
});

test('packaging requires runtime assets and excludes sources', () => {
  assert.ok(buildScript.includes('event-visuals-module/visual-builder'));
  assert.ok(buildScript.includes('wp-seed-events-event-visuals.js'));
  assert.ok(buildScript.includes('src/index.jsx'));
  assert.ok(buildScript.includes('src/loop-context.js'));
  assert.ok(buildScript.includes('tests/*'));
  assert.ok(buildScript.includes('webpack.config.js'));
});

test('Dates bundle matches the validated Loop Builder context build', () => {
  const hash = crypto.createHash('sha256').update(fs.readFileSync(datesBundlePath)).digest('hex').toUpperCase();
  assert.strictEqual(
    hash,
    '567828045E60A9C1875C674366DEAD0C8262B5FC1E3DDC7DBF2F9C0DE9145441',
  );
});

assert.strictEqual(passed, 24);
console.log('Divi event visuals module contract: 24/24 OK');
