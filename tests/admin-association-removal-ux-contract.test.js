'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');

assert.ok(source.includes('function wp_seed_events_render_remove_from_event_button( $association )'));
assert.ok(source.includes("'person' => 'data-wp-seed-person-remove'"));
assert.ok(source.includes("'place'  => 'data-wp-seed-place-remove'"));
assert.ok(source.includes('class="button-link button-link-delete"'));
assert.ok(source.includes("wp_seed_events_render_remove_from_event_button( 'person' )"));
assert.ok(source.includes("wp_seed_events_render_remove_from_event_button( 'place' )"));

assert.ok(source.includes('window.wpSeedEventsAdmin.removeFromEventButton=function(attribute)'));
assert.ok(source.includes("'class':'button-link button-link-delete'"));
assert.ok(source.includes("removeFromEventButton('data-wp-seed-person-remove')"));
assert.ok(source.includes("removeFromEventButton('data-wp-seed-place-remove')"));

assert.ok(!source.includes('class="button-link-delete" data-wp-seed-person-remove'));
assert.ok(!source.includes('class="button-link" data-wp-seed-place-remove'));
assert.ok(!source.includes('class="button-link-delete" data-wp-seed-place-remove'));

const personHandler = source.slice(
  source.indexOf("$(document).on('click','[data-wp-seed-person-remove]'"),
  source.indexOf("$(document).on('click','[data-wp-seed-person-move-up]", source.indexOf("$(document).on('click','[data-wp-seed-person-remove]'")),
);
assert.ok(personHandler.includes('item.remove()'));
assert.ok(!personHandler.includes('wp_delete_post'));

const placeHandler = source.slice(
  source.indexOf("$(document).on('click','[data-wp-seed-place-remove]'"),
  source.indexOf('});', source.indexOf("$(document).on('click','[data-wp-seed-place-remove]'")) + 3,
);
assert.ok(placeHandler.includes("hiddenField(root,'place_id').val('')"));
assert.ok(!placeHandler.includes('wp_delete_post'));

console.log('Admin remove-from-event UX contract: 17 assertions PASS');
