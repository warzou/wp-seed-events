const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../../../../..');
const source = fs.readFileSync(path.join(__dirname, '../src/index.js'), 'utf8');
const metadata = JSON.parse(fs.readFileSync(path.join(__dirname, '../src/block.json'), 'utf8'));
const php = fs.readFileSync(path.join(root, 'includes/integrations/gutenberg/event-content-block.php'), 'utf8');
const packager = fs.readFileSync(path.join(root, 'build-dev-zip.ps1'), 'utf8');

assert.strictEqual(metadata.name, 'wp-seed-events/event-content-block');
assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
assert.ok(source.includes('gutenberg-event-content-preview'));
assert.ok(source.includes('<RawHTML>{state.html}</RawHTML>'));
assert.ok(source.includes('AbortController'));
assert.ok(php.includes("wp_seed_events_render_rich_content( $event['description'] ?? '' )"));
assert.ok(php.includes("current_user_can( 'edit_post', $event_id )"));
assert.ok(!php.includes('get_post_meta('));
assert.ok(!source.includes('dangerouslySetInnerHTML'));
assert.ok(!source.toLowerCase().includes('divi'));
assert.ok(!php.toLowerCase().includes('divi'));
assert.ok(packager.includes("$gutenbergContentRuntimeRoot = 'includes/integrations/gutenberg/event-content-block'"));
assert.ok(packager.includes('$gutenbergContentBlockAsset'));
assert.ok(packager.includes('"$gutenbergContentRuntimeRoot/src/*"'));

console.log('Gutenberg event rich content block contract: 14/14 OK');
