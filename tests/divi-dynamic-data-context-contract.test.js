'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const textProvider = fs.readFileSync(path.join(root, 'includes/integrations/divi/class-dynamic-content-text.php'), 'utf8');
const imageProvider = fs.readFileSync(path.join(root, 'includes/integrations/divi/class-dynamic-content-image.php'), 'utf8');
const context = fs.readFileSync(path.join(root, 'includes/integrations/divi/context.php'), 'utf8');

assert.ok(textProvider.includes("'wp_seed_events_' . $field"), 'historical source ID changed');
assert.ok(textProvider.includes("return 'loop_' . $this->get_name();"), 'loop source ID changed');
assert.ok(textProvider.includes('wp_seed_events_divi_resolve_loop_event_id( $data_args )'), 'text and URL loop aliases are not strict');
assert.ok(imageProvider.includes('wp_seed_events_divi_resolve_loop_event_id( $data_args )'), 'image loop alias is not strict');
assert.ok(context.includes('function wp_seed_events_divi_resolve_loop_event_id'), 'strict loop resolver is absent');
assert.ok(context.includes("$loop_id = absint( $context['loop_id'] ?? 0 );"), 'strict loop resolver does not use loop_id');

const strictLoopResolver = context.match(/function wp_seed_events_divi_resolve_loop_event_id[\s\S]*?\n\}/);
assert.ok(strictLoopResolver, 'strict loop resolver body is unreadable');
for (const forbidden of ['get_queried_object_id(', 'current_post_id', 'wp_seed_events_public_event_id']) {
  assert.ok(!strictLoopResolver[0].includes(forbidden), `strict loop resolver contains fallback: ${forbidden}`);
}

const generalResolver = context.match(/function wp_seed_events_divi_resolve_event_id[\s\S]*?\n\}/);
assert.ok(generalResolver, 'general event resolver body is unreadable');
assert.ok(generalResolver[0].includes("$context['loop_id']"), 'historical explicit loop resolution was removed');
assert.ok(generalResolver[0].includes('get_queried_object_id('), 'event page fallback was removed');
assert.ok(context.includes("'/divi/v1/loop/query-results'"), 'Visual Builder loop response adapter is absent');

console.log('Divi Dynamic Data strict loop/page context contract OK');
