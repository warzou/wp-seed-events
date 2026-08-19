'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');

assert.strictEqual((source.match(/data-wp-seed-person-autocomplete/g) || []).length, 2);
assert.ok(source.includes('type="search" data-wp-seed-person-panel-field="name" data-wp-seed-person-autocomplete'));
assert.ok(!source.includes('data-wp-seed-person-suggestion-search'));
assert.ok(!source.includes('Rechercher une personne'));

assert.ok(source.includes("var query=normalizeSuggestionText(field(personPanel,'name').val())"));
for (const attribute of ['data-wp-seed-suggestion-name', 'data-wp-seed-suggestion-email', 'data-wp-seed-suggestion-phone']) {
  assert.ok(source.includes(attribute), `Missing exhaustive suggestion field: ${attribute}`);
}

for (const field of ['person_key', 'name', 'phone', 'email', 'link']) {
  assert.ok(source.includes(`field(personPanel,'${field}').val(data.${field}||'')`), `Suggestion does not fill ${field}`);
}

assert.ok(source.includes("field(personPanel,'person_key').val('')"));
assert.ok(source.includes("personPanel.data('wpSeedSelectedPersonName','')"));
assert.ok(source.includes("String($(this).val()).trim()!==selectedName.trim()"));

console.log('Admin person single autocomplete contract: 15 assertions PASS');
