'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');
const calendar = read('includes/public/calendar.php');
const eventData = read('includes/public/event-data.php');
const registry = read('includes/public/data-registry.php');
const datesModule = read('includes/integrations/divi/class-event-dates-module.php');
const docs = read('docs/PUBLIC-EVENT-DYNAMIC-DATA.md');

const eventUrl = calendar.match(/function wp_seed_events_event_calendar_url[\s\S]*?\n\}/);
const download = calendar.match(/function wp_seed_events_handle_event_ics_download[\s\S]*?\n\}/);
assert.ok(eventUrl && eventUrl[0].includes('count( $occurrences ) < 1'), 'single-occurrence URL is rejected');
assert.ok(download && download[0].includes('count( $occurrences ) < 1'), 'single-occurrence download is rejected');
assert.ok(calendar.includes('function wp_seed_events_render_event_calendar_link'), 'historical global link renderer was removed');
assert.ok(calendar.includes('function wp_seed_events_render_occurrence_calendar_link'), 'historical occurrence link renderer was removed');
assert.ok(datesModule.includes("'show_calendar_links'"), 'historical Dates attribute was removed');

assert.ok(eventData.includes('$calendar_all_occurrences_url = function_exists( \'wp_seed_events_event_calendar_url\' )'), 'Event Data does not derive the canonical URL');
assert.ok(eventData.includes("'calendar_all_occurrences_url' => $calendar_all_occurrences_url"), 'Event Data projection is absent');
const calendarField = registry.match(/'calendar_all_occurrences_url'\s*=>\s*array\([\s\S]*?\n\s*\),/);
assert.ok(calendarField, 'registry definition is absent');
assert.ok(calendarField[0].includes("'type'        => 'url'"), 'calendar field is not URL typed');
assert.ok(registry.includes("case 'calendar_all_occurrences_url':"), 'registry projection is absent');
assert.ok(docs.includes('loop_wp_seed_events_calendar_all_occurrences_url'), 'strict loop alias is undocumented');
assert.ok(docs.includes('au moins une occurrence future active'), 'empty/single occurrence semantics are undocumented');

for (const forbidden of ['WPSEvents', 'rich_html', 'historical_period']) {
  assert.ok(!eventUrl[0].includes(forbidden), `calendar URL contains foreign contract: ${forbidden}`);
}

console.log('Calendar Dynamic Data URL contract: 15/15 PASS');
