'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const main = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');
const people = fs.readFileSync(path.resolve(__dirname, '..', 'includes', 'public', 'people.php'), 'utf8');

const personPanelStart = main.indexOf('<div data-wp-seed-person-panel hidden>');
const personPanelEnd = main.indexOf('<p class="screen-reader-text"', personPanelStart);
const personPanel = main.slice(personPanelStart, personPanelEnd);

assert.ok(main.indexOf('data-wp-seed-person-panel-home') < personPanelStart);
assert.ok(main.includes('item.after(personPanel)'));
assert.ok(main.includes("peopleRoot.find('[data-wp-seed-person-panel-home]').after(personPanel)"));
assert.ok(main.includes('function parkPanel(peopleRoot)'));
assert.ok(main.includes('parkPanel(root(this))'));

for (const legend of ['Identité', 'Coordonnées', 'Site', 'Rôles pour cet événement', 'Actions']) {
  assert.ok(personPanel.includes(`<legend>${legend}</legend>`), `Missing Person section: ${legend}`);
}

const personOrder = [
  'data-wp-seed-person-panel-field="phone"',
  'data-wp-seed-person-panel-publication="publish_phone"',
  'data-wp-seed-person-panel-field="phone_action"',
  'data-wp-seed-person-panel-field="email"',
  'data-wp-seed-person-panel-publication="publish_email"',
  'data-wp-seed-person-panel-field="link"',
  'data-wp-seed-person-panel-field="website_label"',
  'data-wp-seed-person-panel-publication="publish_link"',
];
let cursor = -1;
for (const token of personOrder) {
  const next = personPanel.indexOf(token);
  assert.ok(next > cursor, `Person field is out of order: ${token}`);
  cursor = next;
}

const placePanelStart = main.indexOf('<div data-wp-seed-place-panel hidden>');
const placePanelEnd = main.indexOf('</div>\n\t</div>\n\t<?php', placePanelStart);
const placePanel = main.slice(placePanelStart, placePanelEnd);
for (const legend of ['Identité', 'Site', 'Informations pour cet événement', 'Actions']) {
  assert.ok(placePanel.includes(`<legend>${legend}</legend>`), `Missing Place section: ${legend}`);
}

const placeOrder = [
  'data-wp-seed-place-panel-field="name"',
  'data-wp-seed-place-panel-field="address"',
  'data-wp-seed-place-panel-field="link"',
  'data-wp-seed-place-panel-field="link_label"',
  'data-wp-seed-place-panel-field="link_visible"',
  'data-wp-seed-place-panel-field="details"',
];
cursor = -1;
for (const token of placeOrder) {
  const next = placePanel.indexOf(token);
  assert.ok(next > cursor, `Place field is out of order: ${token}`);
  cursor = next;
}

assert.ok(main.includes("String(data.website_label||'').trim()&&!validLink(data.link)"));
assert.ok(main.includes("String(data.link_label||'').trim()&&!validPlaceLink(data.link)"));
assert.ok(people.includes('function wp_seed_events_person_association_for_storage( $contact )'));
assert.ok(people.includes("unset( $contact['link'], $contact['website_url'], $contact['website_label'] )"));
assert.ok(main.includes('wp_seed_events_person_association_for_storage( $stored_contact )'));
assert.ok(main.includes("'wp_seed_events_render_place_meta_box',\n\t\t'wp_seed_event',\n\t\t'normal',\n\t\t'default'"));

console.log('Admin People/Place editor UX contract: 33 assertions PASS');
