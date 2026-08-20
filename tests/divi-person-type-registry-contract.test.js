'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'includes/integrations/divi/event-people-module/visual-builder/src/module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'includes/integrations/divi/event-people-module/visual-builder/src/index.jsx'), 'utf8');
const php = fs.readFileSync(path.join(root, 'includes/integrations/divi/class-event-people-module.php'), 'utf8');
const registry = fs.readFileSync(path.join(root, 'includes/public/person-types.php'), 'utf8');
const items = metadata.attributes.content.settings.innerContent.items;

assert.ok(!Object.values(items).some((item) => /^role_/.test(item.subName ?? '')), 'module.json still contains a static role choice list.');
const staticFilterItems = Object.values(items).filter((item) => item.groupSlug === 'contentPeopleFilter');
assert.deepStrictEqual(staticFilterItems, [], 'module.json still contains static person-type choices.');
assert.ok(source.includes('wp-seed-events/v1/person-types'));
assert.ok(source.includes("cache: 'no-store'"));
assert.ok(source.includes("route.searchParams.set('_wpseed_registry'"));
assert.ok(source.includes('canonicalPersonTypeFields'));
assert.ok(source.includes("subName = personTypeFieldName(key)"));
assert.ok(source.includes('const registerEventPeopleModule'));
assert.strictEqual((source.match(/registerModule\(/g) || []).length, 1);
assert.ok(registry.includes("register_rest_route("));
assert.ok(registry.includes("'/person-types'"));
assert.ok(registry.includes("get_option( 'wp_seed_events_person_types', null )"));
assert.ok(registry.includes('wp_seed_events_retired_person_types'));
assert.ok(php.includes("0 !== strpos( $field, 'role_' )"));
assert.ok(php.includes('$has_role_toggles = true'));
assert.ok(php.includes("'role_registration_contact' => 'contact'"));
assert.ok(php.includes("'role_information_contact'  => 'contact'"));

console.log('Divi person type registry contract: PASS');
