'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const read = (relativePath) => fs.readFileSync(path.join(root, relativePath), 'utf8');
const renderer = read('includes/public/descriptions.php');
const stylesheet = read('includes/public/event-descriptions.css');
const bootstrap = read('wp-seed-events.php');

assert.ok(renderer.includes('class="wp-seed-events-rich-content"'), 'Rich Content output must expose a stable generic wrapper');
assert.ok(stylesheet.includes('.wp-seed-events-rich-content .wp-video'), 'WordPress video wrapper rules must be scoped to Rich Content');
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+\.wp-video\s*\{[^}]*max-width:\s*100%;/s);
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+video\s*\{[^}]*height:\s*auto\s*!important;[^}]*max-width:\s*100%;/s);
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+audio\s*\{[^}]*max-width:\s*100%;/s);
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+\.mejs-container\s*\{[^}]*max-width:\s*100%;[^}]*width:\s*100%\s*!important;/s);
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+\.mejs-container\s+\.mejs-controls\s*\{[^}]*max-width:\s*100%;/s);
assert.match(stylesheet, /\.wp-seed-events-rich-content\s+\.mejs-container\s+\.mejs-inner,[\s\S]*\.mejs-mediaelement,[\s\S]*\.mejs-layers,[\s\S]*\.mejs-controls\s*\{[^}]*max-width:\s*100%;/);
assert.ok(bootstrap.includes("add_action( 'wp_enqueue_scripts', 'wp_seed_events_enqueue_public_rich_content_style' );"), 'Rich Content styles must be enqueued on regular frontend pages');
assert.ok(bootstrap.includes("'wp-seed-events-public-descriptions'"), 'Rich Content stylesheet must keep its existing frontend handle');
assert.ok(!stylesheet.includes('#post-2339'), 'Responsive rules must not target one event');
assert.ok(!stylesheet.includes('overflow-x: hidden'), 'The fix must constrain media instead of hiding overflow');

console.log('Rich Content responsive contract: video/audio scoping and frontend enqueue PASS');
