'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');
const peopleStart = source.indexOf('function wp_seed_events_render_contacts_meta_box(');
const peopleEnd = source.indexOf('function wp_seed_events_save_contacts(', peopleStart);
const peopleSource = source.slice(peopleStart, peopleEnd);

for (const label of ['Monter', 'Descendre', 'Modifier']) {
  assert.ok(peopleSource.includes(label), `Missing event person action: ${label}`);
}
assert.ok(peopleSource.includes("wp_seed_events_render_remove_from_event_button( 'person' )"));
assert.ok(source.includes("esc_html__( 'Retirer de cet événement', 'wp-seed-events' )"));
assert.ok(!peopleSource.includes('>Supprimer</button>'), 'The event association UI still says Supprimer.');
assert.ok(source.includes('function refreshPeopleOrder(peopleRoot)'));
assert.ok(source.includes("field.attr('name','wp_seed_events_contacts['+index+']['+key+']')"));
assert.ok(source.includes("item.find('[data-wp-seed-person-role]').attr('name','wp_seed_events_contacts['+index+'][roles][]')"));
assert.ok(source.includes("item.find('[data-wp-seed-person-move-up]').prop('disabled',0===index)"));
assert.ok(source.includes("item.find('[data-wp-seed-person-move-down]').prop('disabled',items.length-1===index)"));
assert.ok(source.includes("$(document).on('click','[data-wp-seed-person-move-up],[data-wp-seed-person-move-down]'"));
assert.ok(source.includes('item.insertBefore(sibling)'));
assert.ok(source.includes('item.insertAfter(sibling)'));
assert.ok(source.includes('refreshPeopleOrder(peopleRoot);\n\t\tmarkChanged(peopleRoot);'));
assert.ok(peopleSource.includes('Action du téléphone'));
assert.ok(peopleSource.includes('data-wp-seed-person-panel-field="phone_action"'));
assert.ok(peopleSource.includes('data-wp-seed-person-field="phone_action_explicit"'));
assert.ok(source.includes("phone_action:'none'"), 'New associations must default to none.');
assert.ok(source.includes("personPanel.data('wpSeedPhoneActionTouched',false)"));
assert.ok(source.includes('wp_seed_events_contact_phone_action_for_storage('));

console.log('Admin event-scoped person order and phone action contract: 22 assertions PASS');
