const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const textProvider = fs.readFileSync(path.join(root, 'includes/integrations/divi/class-dynamic-content-text.php'), 'utf8');
const imageProvider = fs.readFileSync(path.join(root, 'includes/integrations/divi/class-dynamic-content-image.php'), 'utf8');
const context = fs.readFileSync(path.join(root, 'includes/integrations/divi/context.php'), 'utf8');
const bootstrap = fs.readFileSync(path.join(root, 'includes/integrations/divi/bootstrap.php'), 'utf8');

assert.ok(textProvider.includes("'wp_seed_events_' . $field"), 'historical source ID changed');
assert.ok(textProvider.includes("return 'loop_' . $this->get_name();"), 'loop source ID changed');
assert.ok(textProvider.includes('WPSEvents — Page — %s'), 'page label is not explicit');
assert.ok(textProvider.includes('WPSEvents — %s'), 'loop label is not shortened');
assert.ok(textProvider.includes("'WPSEvents — Page événement'"), 'page group is not explicit');
assert.ok(textProvider.includes("'WPSEvents'"), 'loop group is not shortened');
assert.ok(!textProvider.includes('WPSEvents — Événement — %s'), 'legacy event label remains exposed');
assert.ok(!textProvider.includes('WPSEvents — Boucle — %s'), 'verbose loop label remains exposed');
assert.ok(textProvider.includes('wp_seed_events_divi_resolve_loop_event_id( $data_args )'), 'text and URL loop aliases are not strict');
assert.ok(imageProvider.includes('wp_seed_events_divi_resolve_loop_event_id( $data_args )'), 'image loop alias is not strict');
assert.ok(context.includes('function wp_seed_events_divi_resolve_loop_event_id'), 'strict loop resolver is absent');
assert.ok(context.includes("$loop_id = absint( $context['loop_id'] ?? 0 );"), 'strict loop resolver does not use loop_id');
const strictLoopResolver = context.match(/function wp_seed_events_divi_resolve_loop_event_id[\s\S]*?\n\}/);
assert.ok(strictLoopResolver, 'strict loop resolver body is unreadable');
assert.ok(!strictLoopResolver[0].includes('get_queried_object_id('), 'strict loop resolver falls back to the queried event');
assert.ok(bootstrap.includes('$option->configure( $field )'), 'bootstrap no longer registers canonical provider fields');

console.log('Divi Dynamic Data context contract OK');
