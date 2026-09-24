'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (relative) => fs.readFileSync(path.join(root, relative), 'utf8');
const renderer = read('includes/public/rendering.php');
const lightbox = read('includes/public/lightbox.php');
const divi = read('includes/integrations/divi/class-event-visuals-module.php');
const diviInitializer = read('includes/public/event-visuals-divi.js');
const gutenberg = read('includes/integrations/gutenberg/event-visuals-block.php');
const shareCss = read('includes/public/event-share.css');

assert.ok(renderer.includes("'lightbox'       => false"));
assert.ok(renderer.includes('wp_seed_events_render_wordpress_lightbox_figure'));
assert.ok(!renderer.toLowerCase().includes('divi'));
assert.ok(lightbox.includes('block_core_image_render_lightbox'));
assert.ok(lightbox.includes("'lightbox'        => array( 'enabled' => true )"));
assert.ok(lightbox.includes("'galleryId'"));
assert.ok(!lightbox.includes('<script'));
assert.ok(!lightbox.includes('jquery'));
assert.ok(divi.includes("$processor->add_class( 'et_pb_lightbox_image' )"));
assert.ok(divi.includes("$options['click_action'] = 'original'"));
assert.ok(divi.includes("'lightbox' === $click_action"));
assert.ok(divi.includes('DynamicAssetsUtils::enqueue_magnific_popup_script()'));
assert.ok(diviInitializer.includes('window.et_pb_image_lightbox_init(links)'));
assert.ok(!diviInitializer.includes('.magnificPopup({'));
assert.ok(gutenberg.includes("'lightbox'       => wp_seed_events_gutenberg_event_visuals_boolean_option"));
assert.ok(shareCss.includes('.wp-seed-event-share__actions'));
assert.ok(!shareCss.includes('details'));

console.log('Builder-agnostic native lightbox and share layout contract: PASS');
