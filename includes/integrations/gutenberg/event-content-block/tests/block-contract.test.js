const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../../../../..');
const source = fs.readFileSync(path.join(__dirname, '../src/index.js'), 'utf8');
const metadata = JSON.parse(fs.readFileSync(path.join(__dirname, '../src/block.json'), 'utf8'));
const php = fs.readFileSync(path.join(root, 'includes/integrations/gutenberg/event-content-block.php'), 'utf8');
const packager = fs.readFileSync(path.join(root, 'build-dev-zip.ps1'), 'utf8');
const gitignore = fs.readFileSync(path.join(root, '.gitignore'), 'utf8');

assert.strictEqual(metadata.name, 'wp-seed-events/event-content-block');
assert.strictEqual(metadata.apiVersion, 3);
assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
assert.ok(source.includes('gutenberg-event-content-preview'));
assert.ok(source.includes('<RawHTML>{state.html}</RawHTML>'));
assert.ok(source.includes('AbortController'));
assert.ok(source.includes('save: () => null'));
assert.ok(php.includes("wp_seed_events_render_rich_content( $description )"));
assert.ok(php.includes("current_user_can( 'edit_post', $event_id )"));
assert.ok(php.includes('wp_seed_events_gutenberg_event_content_contains_self'));
assert.ok(php.includes('static $rendering = array()'));
assert.ok(!php.includes('wp_seed_events_gutenberg_event_people_'));
assert.ok(!source.toLowerCase().includes('divi'));
assert.ok(!php.toLowerCase().includes('divi'));
assert.ok(packager.includes("$gutenbergContentRuntimeRoot = 'includes/integrations/gutenberg/event-content-block'"));
assert.ok(gitignore.includes('!includes/integrations/gutenberg/event-content-block/build/index.js'));

console.log('Gutenberg event rich content block contract: 17/17 OK');
