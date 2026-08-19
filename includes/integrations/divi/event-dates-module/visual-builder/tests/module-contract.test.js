const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const styleValues = fs.readFileSync(path.join(root, 'src', 'divi-style-values.js'), 'utf8');
const publicCss = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'event-dates.css'), 'utf8');
const renderer = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'rendering.php'), 'utf8');
const phpModule = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'class-event-dates-module.php'),
  'utf8',
);
const contextHelper = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'context.php'),
  'utf8',
);
const bootstrap = fs.readFileSync(
  path.join(pluginRoot, 'includes', 'integrations', 'divi', 'bootstrap.php'),
  'utf8',
);
const resolverSource = contextHelper.slice(
  contextHelper.indexOf('function wp_seed_events_divi_resolve_event_id'),
);
const contentItems = metadata.attributes.content.settings.innerContent.items;

assert.strictEqual(metadata.name, 'wp-seed-events/event-dates');
assert.strictEqual(metadata.folder, 'wp-seed-events');
const defaults = metadata.attributes.content.default.innerContent.desktop.value;
assert.strictEqual(defaults.mode, 'all');
assert.strictEqual(defaults.scope, 'all');
assert.strictEqual(defaults.format, 'long');
const dateSelection = contentItems.dateSelection;
assert.ok(dateSelection);
assert.strictEqual(dateSelection.subName, 'date_selection');
assert.deepStrictEqual(Object.keys(dateSelection.component.props.options), [
  'next',
  'first',
  'last',
  'all_upcoming',
  'all_past',
  'all',
]);
[
  'Prochaine date',
  'Première date',
  'Dernière date',
  'Toutes les prochaines dates',
  'Toutes les dates passées',
  'Toutes les dates',
].forEach((label) => assert.ok(JSON.stringify(dateSelection).includes(label)));

[
  'title',
  'heading_level',
  'date_selection',
  'show_cancelled',
  'show_times',
  'format',
  'show_calendar_links',
].forEach((field) => {
  assert.ok(
    Object.values(contentItems).some((item) => item.subName === field),
    `Missing persistent field: ${field}`,
  );
});
assert.ok(!Object.values(contentItems).some((item) => item.subName === 'scope'));

[
  'wp-seed-event-dates__title',
  'wp-seed-event-date__date',
  'wp-seed-event-date__time',
  'wp-seed-event-date__status',
  'wp-seed-event-calendar-link',
  'wp-seed-event-date',
].forEach((selector) => {
  assert.ok(JSON.stringify(metadata).includes(selector), `Missing design selector: ${selector}`);
});

assert.ok(source.includes('useFetch'));
assert.ok(source.includes('AbortController'));
assert.ok(source.includes('URLSearchParams'));
assert.ok(source.includes('/wp-seed-events/v1/divi-event-dates-preview?'));
[
  "selection === 'next'",
  "selection === 'first' || selection === 'last'",
  "scope = 'all'",
  "selection === 'all_upcoming'",
  "selection === 'all_past'",
  "selection === 'all'",
  "date_selection: 'all'",
].forEach((mapping) => assert.ok(source.includes(mapping), `Missing Divi mapping: ${mapping}`));
[
  "in_array( $choice, array( 'first', 'last' ), true )",
  "$scope = 'all'",
  "'all_upcoming' === $choice",
  "'all_past' === $choice",
  "'all' === $choice",
].forEach((mapping) => assert.ok(phpModule.includes(mapping), `Missing PHP mapping: ${mapping}`));
assert.deepStrictEqual(metadata.attributes.__loop_post_id, { type: 'string', default: '' });
assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
assert.ok(source.includes('loop_id: loopPostId'));
assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
assert.ok(contextHelper.includes('DynamicContentUtils'));
assert.ok(contextHelper.includes('get_loop_post_id'));
assert.ok(source.includes("addFilter('divi.moduleLibrary.moduleMapping'"));
assert.ok(source.includes('registerFolder({'));
assert.ok(!source.includes('[wp_seed_event_dates'));
assert.ok(!source.includes('914'));

assert.strictEqual(
  (phpModule.match(/wp_seed_events_render_public_event_dates_section/g) || []).length,
  1,
  'The shared renderer must be called exactly once by the module class.',
);
assert.strictEqual(
  (phpModule.match(/wp_seed_events_get_event_data/g) || []).length,
  1,
  'Event Data API must be resolved at most once by the module class.',
);
assert.ok(phpModule.includes("current_user_can( 'edit_posts' )"));
assert.ok(!phpModule.includes('get_post_meta'));
assert.ok(!phpModule.includes('_wp_seed_event_'));
assert.ok(phpModule.includes("array( '0', 'off' )"));
assert.ok(phpModule.includes('return ! in_array('));
assert.ok(resolverSource.indexOf('$loop_id =') < resolverSource.indexOf('$post_id ='));
assert.ok(resolverSource.includes('return wp_seed_events_divi_is_event( $loop_id ) ? $loop_id : 0;'));
assert.ok(bootstrap.includes("function_exists( 'et_builder_d5_enabled' )"));
assert.ok(bootstrap.includes('PackageBuildManager::register_package_build'));
assert.ok(bootstrap.includes("'style'   => array("));
assert.ok(bootstrap.includes("plugins_url( 'includes/public/event-dates.css'"));
assert.ok(bootstrap.includes("'enqueue_top_window' => false"));
assert.ok(bootstrap.includes("'enqueue_app_window' => true"));
assert.ok(bootstrap.includes("array( $script_path, $style_path )"));
const buildScript = fs.readFileSync(path.join(pluginRoot, 'build-dev-zip.ps1'), 'utf8');
assert.ok(buildScript.includes('$moduleRuntimeRoot/src/divi-style-values.js'));

const listStyle = metadata.attributes.listStyle;
const listItems = listStyle.settings.advanced;
assert.strictEqual(metadata.settings.groups.designDateList.panel, 'design');
assert.strictEqual(metadata.settings.groups.designDateList.component.props.groupLabel, 'Liste des dates');
assert.deepStrictEqual(Object.keys(listItems.markerType.item.component.props.options), [
  'none',
  'disc',
  'circle',
  'square',
]);
assert.deepStrictEqual(Object.keys(listItems.markerPosition.item.component.props.options), [
  'outside',
  'inside',
]);
[
  'markerType',
  'markerPosition',
  'leftIndent',
  'occurrenceGap',
  'markerColor',
].forEach((field) => assert.ok(listItems[field], `Missing list style field: ${field}`));
assert.strictEqual(listStyle.default.advanced.markerType.desktop.value, 'none');
Object.values(listItems).forEach((field) => assert.strictEqual(field.item.features.responsive, true));
assert.ok(source.includes("markerType: { desktop: { value: 'none' } }"));
assert.ok(source.includes("leftIndent: { desktop: { value: '0px' } }"));
assert.ok(source.includes('normalizeListStyles(attrs)'));
assert.ok(!source.includes('listRequestOptions(listStyles)'));
assert.ok(!source.includes('...listOptions'));
assert.ok(source.includes('const optionsKey = JSON.stringify(options)'));
assert.ok(styleValues.includes('resolveDiviStyleValue'));
assert.ok(source.includes('applyListStyleVariables,'));
assert.ok(source.includes('previewListStyleCss,'));
assert.ok(source.includes('previewListStyleScope,'));
assert.ok(source.includes('normalizeListStyles,'));
assert.ok(source.includes("from './divi-style-values'"));
assert.ok(styleValues.includes("desktop: ['desktop']"));
assert.ok(styleValues.includes("tablet: ['tablet', 'desktop']"));
assert.ok(styleValues.includes("phone: ['phone', 'tablet', 'desktop']"));
assert.ok(source.includes("'divi.module.wrapper.render'"));
assert.ok(source.includes('createEventDatesPreviewFilter'));
assert.ok(source.includes('const EventDatesEditRenderer = (props) => <EventDatesPreview {...props} />;'));
assert.ok(source.includes('edit: EventDatesEditRenderer'));
assert.ok(source.includes('resolveCurrentEventContext({ data, attrs, parentId, loopIndex, currentPage })'));
assert.ok(source.includes('const postId = eventContext.eventId'));
assert.ok(source.includes('const loopContextKey = eventContext.cacheKey'));
assert.ok(source.includes('parentId,'));
assert.ok(source.includes('loopIndex,'));
assert.ok(source.includes('[postId, loopPostId, loopContextKey, optionsKey]'));
[
  'list_marker_type',
  'list_marker_position',
  'list_indent',
  'occurrence_gap',
  'marker_color',
].forEach((field) => {
  assert.ok(styleValues.includes(field), `List style contract omits ${field}`);
  assert.ok(phpModule.includes(field), `PHP preview omits ${field}`);
});
[
  "documentRef.createElement('template')",
  'applyListStyleVariables(list, listStyles)',
  'list.classList.add(',
  "'has-custom-list-style'",
  'previewStyle.textContent = previewListStyleCss(scope)',
  "previewStyle.setAttribute('data-wp-seed-event-dates-preview-style', scope)",
  "list.style.setProperty('list-style-type', 'var(--wp-seed-event-dates-current-marker-type)', 'important')",
  "list.style.setProperty('padding-block-end', '0', 'important')",
  "item.style.setProperty('display', 'list-item', 'important')",
  'className="et_pb_module_inner" dangerouslySetInnerHTML={{ __html: previewHtml }}',
].forEach((contract) => assert.ok(source.includes(contract), 'Missing Visual Builder marker contract: ' + contract));
[
  'titleStyle',
  'dateStyle',
  'timeStyle',
  'statusStyle',
  'calendarLinkStyle',
  'occurrenceStyle',
].forEach((attrName) => {
  assert.ok(source.includes("elements.style({ attrName: '" + attrName + "' })"), 'React style pipeline omits ' + attrName);
  assert.ok(phpModule.includes("'" + attrName + "'"), 'PHP style pipeline omits ' + attrName);
});
[
  'wp-seed-event-dates__title',
  'wp-seed-event-date__date',
  'wp-seed-event-date__time',
  'wp-seed-event-date__status',
  'wp-seed-event-calendar-link',
].forEach((target) => assert.ok(publicCss.includes('.wp-seed-event-section--dates .' + target), 'Block style target missing: ' + target));
assert.ok(!renderer.includes('<br /><span class="wp-seed-event-date__time">'));
assert.ok(!renderer.includes('<br /><?php echo wp_kses_post( $calendar_link ); ?>'));
assert.ok(phpModule.includes('resolve_divi_style_value'));
assert.ok(phpModule.includes('get_responsive_list_values'));
assert.ok(phpModule.includes('WP_HTML_Tag_Processor'));
assert.ok(phpModule.includes('apply_responsive_list_styles( $html, self::get_responsive_list_values( $attrs ) )'));
assert.ok(publicCss.includes('@media (max-width: 980px)'));
assert.ok(publicCss.includes('@media (max-width: 767px)'));assert.ok(source.includes("const previewContent = !isLoading && !hasError && previewHtml !== '';"));
assert.ok(
  !source.includes('<div dangerouslySetInnerHTML={{ __html: previewHtml }} />'),
  'The REST renderer must not be wrapped in an extra anonymous preview row.',
);

assert.ok(bootstrap.includes("foreach ( array( $script_path, $style_path ) as $asset_path )"));
assert.ok(bootstrap.includes("hash_file( 'sha256', $asset_path )"));
assert.ok(bootstrap.includes("hash( 'sha256', implode( '|', $asset_hashes ) )"));
assert.ok(bootstrap.includes("'version' => $script_version"));
console.log('Divi event dates module contract: OK');
