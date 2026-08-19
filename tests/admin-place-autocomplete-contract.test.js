'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const source = fs.readFileSync(path.resolve(__dirname, '..', 'wp-seed-events.php'), 'utf8');
const placeSource = source.slice(source.indexOf('function wp_seed_events_render_place_meta_box('), source.indexOf('function wp_seed_events_add_place_address_meta_box('));
const eventSaveSource = source.slice(source.indexOf('function wp_seed_events_save_event_place('), source.indexOf('function wp_seed_events_save_place_address('));
const placesAdminSource = source.slice(source.indexOf('function wp_seed_events_render_places_admin_page('), source.indexOf('function wp_seed_events_add_place_meta_box('));

assert.strictEqual((source.match(/data-wp-seed-place-autocomplete/g) || []).length, 2);
assert.ok(source.includes('type="search" data-wp-seed-place-panel-field="name" data-wp-seed-place-autocomplete'));
assert.ok(!placeSource.includes('data-wp-seed-place-form-title'));
assert.ok(!placeSource.includes('<p>Suggestions</p>'));
assert.ok(source.includes("'posts_per_page' => -1"));
assert.ok(source.includes("$usage_counts[ $place->ID ] = wp_seed_events_place_usage_count( $place->ID )"));
assert.ok(source.includes("static function ( $left, $right ) use ( $usage_counts )"));
assert.ok(source.includes('remove_accents( (string) $left->post_title )'));
assert.ok(source.includes("var query=normalizePlaceSearch(panelField(panel,'name').val())"));
assert.ok(source.includes("panel.data('wpSeedPlaceId','')"));
assert.ok(source.includes("panel.data('wpSeedSelectedPlaceName','')"));
assert.ok(source.includes("$suggestion_search  = implode( ' ', array( $place->post_title, $suggestion_address ) )"));
for (const field of ['name', 'address', 'link']) {
  assert.ok(source.includes(`panelField(panel,'${field}').val(${field === 'name' ? 'selectedName' : "$(this).attr('data-wp-seed-place-" + field + "')||''"})`));
}
assert.ok(source.includes("hiddenField(root,'new_name').val(data.name)"));
assert.ok(source.includes('Informations complémentaires pour cet événement'));
assert.ok(placeSource.includes('URL (facultative)'));
assert.ok(placeSource.includes('<strong>Affichage</strong>'));
assert.ok(placeSource.includes('data-wp-seed-place-panel-field="link_visible"'));
assert.ok(placeSource.includes('data-wp-seed-place-link-visible'));
assert.ok(!placeSource.includes('data-wp-seed-place-delete'));
assert.ok(!placeSource.includes('Supprimer ce lieu'));
assert.ok(!eventSaveSource.includes('wp_delete_post'));
assert.ok(!eventSaveSource.includes('wp_seed_delete_place_id'));
assert.ok(placesAdminSource.includes("'delete' === $admin_action"), 'Global deletion must remain on All Places.');
assert.ok(source.includes("panelField(panel,'link_visible').prop('checked','1'===$(this).attr('data-wp-seed-place-link-visible'))"));
assert.ok(source.includes("panelField(panel,'details').val($(this).attr('data-wp-seed-place-details')||'')"));
assert.ok(source.includes("get_post_meta( $place->ID, '_wp_seed_place_details', true )"));

console.log('Admin place single autocomplete and deletion-boundary contract: 29 assertions PASS');
