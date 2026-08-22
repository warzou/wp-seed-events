const assert = require('assert');
const fs = require('fs');
const path = require('path');
const root = path.resolve(__dirname, '..');
const read = (...parts) => fs.readFileSync(path.join(root, ...parts), 'utf8');
const json = (...parts) => JSON.parse(read(...parts));
const rendering = read('includes/public/rendering.php');
const media = read('includes/public/media.php');
const eventData = read('includes/public/event-data.php');
const registry = read('includes/public/data-registry.php');
const plugin = read('wp-seed-events.php');
const bootstrap = read('includes/integrations/divi/bootstrap.php');
const visualsClass = read('includes/integrations/divi/class-event-visuals-module.php');
const documentClass = read('includes/integrations/divi/class-event-document-module.php');
const visualsMeta = json('includes/integrations/divi/event-visuals-module/visual-builder/src/module.json');
const documentMeta = json('includes/integrations/divi/event-document-module/visual-builder/src/module.json');
const gutenbergVisuals = json('includes/integrations/gutenberg/event-visuals-block/src/block.json');
const gutenbergDocument = json('includes/integrations/gutenberg/event-document-block/src/block.json');
let passed = 0;
const check = (name, fn) => { fn(); passed += 1; console.log(`OK ${passed} - ${name}`); };
const visualsStart = rendering.indexOf('function wp_seed_events_render_public_event_visuals_section');
const documentStart = rendering.indexOf('function wp_seed_events_render_public_event_document_section');
const visualsRenderer = rendering.slice(visualsStart, documentStart);
const documentRenderer = rendering.slice(documentStart, rendering.indexOf('function wp_seed_events_public_event_occurrence_has_valid_date', documentStart));

check('canonical model remains one PDF attachment plus one editorial name', () => {
  assert.ok(media.includes("get_post_meta( $event_id, '_wp_seed_event_flyer_pdf_id'"));
  assert.ok(media.includes("'_wp_seed_event_document_display_name'"));
  assert.ok(!media.includes('event_documents'));
});
check('display name falls back to a cleaned filename', () => {
  assert.ok(media.includes('wp_seed_events_document_filename_display_name'));
  assert.ok(media.includes("preg_replace( '/[-_]+/u', ' ', $filename )"));
  assert.ok(media.includes("$event_document['display_name']"));
});
check('Event Data and Dynamic Data expose URL filename and display name separately', () => {
  ['event_document_url', 'event_document_filename', 'event_document_display_name'].forEach((key) => {
    assert.ok(eventData.includes(`'${key}'`)); assert.ok(registry.includes(`'${key}'`));
  });
});
check('admin keeps the editorial name beside the PDF and does not overwrite filename', () => {
  assert.ok(plugin.includes('wp_seed_event_document_display_name'));
  assert.ok(plugin.includes("Nom d'affichage (facultatif)"));
  assert.ok(!plugin.includes("update_post_meta( $attachment_id, 'post_title'"));
});
check('visual renderer is image-only and legacy document options are inert', () => {
  assert.ok(!visualsRenderer.includes('event_document'));
  assert.ok(!visualsRenderer.includes('__document'));
  assert.ok(visualsRenderer.includes('show_document'));
});
check('document renderer supports all requested presentations', () => {
  ['show_document', 'link_text', 'text_name', 'wp-seed-event-document__name'].forEach((token) => assert.ok(documentRenderer.includes(token)));
  assert.ok(rendering.includes("'next_line'"));
});
check('Divi visuals UI has one click action and no document surface', () => {
  const text = JSON.stringify(visualsMeta);
  assert.ok(text.includes('Action au clic'));
  ['none', 'lightbox', 'original'].forEach((value) => assert.ok(text.includes(`"${value}"`)));
  assert.ok(!Object.values(visualsMeta.attributes.content.settings.innerContent.items).some((item) => item.subName === 'show_document'));
  assert.ok(Object.hasOwn(visualsMeta.attributes, 'documentStyle'));
  assert.ok(!visualsMeta.attributes.documentStyle.settings);
});
check('Divi visuals delegates new layout to native controls and keeps legacy runtime support', () => {
  const values = visualsMeta.attributes.content.default.innerContent.desktop.value;
  ['layout', 'horizontal_gap', 'vertical_gap', 'columns', 'columns_tablet', 'columns_phone'].forEach((key) => assert.ok(!Object.hasOwn(values, key)));
  assert.ok(!visualsMeta.settings.groups.designVisualLayout);
  assert.ok(visualsClass.includes("'layout'         => 'grid'"));
  assert.ok(visualsClass.includes("'native-v1' === $options['layout_contract']"));
});
check('Divi document module is separately registered and built', () => {
  assert.strictEqual(documentMeta.name, 'wp-seed-events/event-document');
  assert.ok(documentClass.includes('wp_seed_events_render_public_event_document_section'));
  assert.ok(bootstrap.includes('wp_seed_events_divi_register_event_document_module'));
  assert.ok(fs.statSync(path.join(root, 'includes/integrations/divi/event-document-module/visual-builder/build/wp-seed-events-event-document.js')).size > 0);
});
check('Gutenberg visuals is image-only and document block is separate', () => {
  assert.ok(Object.hasOwn(gutenbergVisuals.attributes, 'show_document'));
  assert.strictEqual(gutenbergDocument.name, 'wp-seed-events/event-document-block');
  assert.ok(plugin.includes('event-document-block.php'));
  assert.ok(fs.statSync(path.join(root, 'includes/integrations/gutenberg/event-document-block/build/index.js')).size > 0);
});
check('technical module and binding identities remain stable', () => {
  assert.strictEqual(visualsMeta.name, 'wp-seed-events/event-visuals');
  assert.ok(registry.includes("'event_document_url'"));
  assert.ok(registry.includes("'event_document_filename'"));
  assert.ok(visualsClass.includes("const MODULE_NAME = 'wp-seed-events/event-visuals'"));
});
check('version remains beta.9 and no Content Kit dependency exists', () => {
  assert.ok(plugin.includes('Version: 0.2.0-beta.9'));
  [visualsClass, documentClass, rendering, media].forEach((source) => assert.ok(!/content[ -]kit/i.test(source)));
});
assert.strictEqual(passed, 12);
console.log('Visual/document split contract: 12/12 OK');
