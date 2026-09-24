'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');

assert.ok(source.includes('Pour une nouvelle association, chaque coordonnée valide est proposée publique.'));
assert.ok(source.includes('defaultPublicationForCoordinates(suggestion)'));
assert.ok(source.includes("personPanel.data('wpSeedPublicationTouched',{})"));
assert.ok(source.includes("$(document).on('change','[data-wp-seed-person-panel-publication]'"));
assert.ok(source.includes("!changed&&''!==currentCoordinate&&!touched[publicationKey]"));
assert.ok(source.includes("''!==originalCoordinate&&currentCoordinate!==originalCoordinate"));
assert.ok(source.includes("publicationField(personPanel,publicationKey).prop('checked',false)"));

console.log('Admin contact publication default contract: 7/7 PASS');
