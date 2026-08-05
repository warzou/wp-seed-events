'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');

const adapter = require(path.resolve(__dirname, '../includes/integrations/divi/event-collection-filters.js'));

let assertions = 0;
const check = (condition, message) => {
	assertions += 1;
	assert.ok(condition, message);
};

const nativeFields = {
	queryType: { visible: true },
	subTypes: { visible: true },
	includePostWithSpecificTerms: {
		attrName: 'module.advanced.loop',
		subName: 'includePostWithSpecificTerms',
		visible: true,
		component: {
			type: 'field',
			name: 'divi/tag-input',
			props: {
				showTagsWithCategory: true,
				options: {
					wp_seed_event_type: {
						label: "Types d’événement",
						options: {
							11: { label: 'Atelier' },
							12: { label: 'Stage' },
						},
					},
					wp_seed_event_flag: {
						label: "Indicateurs d’événement",
						options: { 21: { label: 'Événement épinglé' } },
					},
					category: {
						label: 'Catégories',
						options: { 30: { label: 'Actualités' } },
					},
				},
			},
		},
	},
	excludePostWithSpecificTerms: {
		attrName: 'module.advanced.loop',
		subName: 'excludePostWithSpecificTerms',
		visible: true,
	},
	includePosts: { visible: true },
	metaQuery: { visible: true },
	orderBy: { visible: true },
	order: { visible: true },
	postsNumber: { visible: true },
	offsetNumber: { visible: true },
};

const eventLoop = {
	queryType: 'post_types',
	subTypes: [{ value: 'wp_seed_event', label: 'Événements' }],
};
const fieldsFilter = adapter.createFieldsFilter();
const fields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: eventLoop,
});

check(fields.includePostWithSpecificTerms.visible === true, 'native include control was hidden');
check(fields.excludePostWithSpecificTerms.visible === true, 'native exclude control was hidden');
check(fields.wpSeedEventTypes.label === "Types d’événement", 'type label differs');
check(fields.wpSeedEventTypes.subName === 'wpSeedEventTypes', 'type attribute differs');
check(Object.keys(fields.wpSeedEventTypes.component.props.options).length === 1, 'flag taxonomy was not removed');
check(Boolean(fields.wpSeedEventTypes.component.props.options.wp_seed_event_type), 'type taxonomy is missing');
check(!fields.wpSeedEventTypes.component.props.options.wp_seed_event_flag, 'featured remains mixed with types');
check(fields.wpSeedEventTypes.component.props.showTagsWithCategory === false, 'dedicated type pills retain the taxonomy prefix');
check(nativeFields.includePostWithSpecificTerms.component.props.showTagsWithCategory === true, 'generic mixed-taxonomy pills were shortened');
check(fields.wpSeedEventPinned.label === 'Épinglage', 'pinned label differs');
check(Object.keys(fields.wpSeedEventPinned.component.props.options).length === 3, 'pinned choices differ');
check(fields.wpSeedEventPinned.component.props.options.featured_only.label.includes('épinglés'), 'featured-only label differs');
check(fields.wpSeedEventPinned.component.props.options.exclude_featured.label.includes('Exclure'), 'featured exclusion label differs');
check(fields.wpSeedEventEmptyBehavior.label === 'Si aucun événement n’est trouvé', 'empty behavior label differs');
check(fields.wpSeedEventEmptyBehavior.component.props.defaultValue === 'divi_default', 'missing behavior does not preserve Divi output');
check(Object.keys(fields.wpSeedEventEmptyBehavior.component.props.options).join(',') === 'hide,custom_message,divi_default', 'empty behavior choices differ');
check(fields.wpSeedEventEmptyMessage.component.name === 'divi/text', 'empty message is not a text field');
check(fields.wpSeedEventEmptyMessage.component.props.defaultValue === adapter.EMPTY_MESSAGE_DEFAULT, 'empty message example differs');
check(fields.wpSeedEventEmptyMessage.visible === false, 'message field is visible outside custom-message mode');
const expectedFieldOrder = [
	'queryType',
	'subTypes',
	'wpSeedEventTypes',
	'wpSeedEventPinned',
	'wpSeedEventEmptyBehavior',
	'wpSeedEventEmptyMessage',
	'includePostWithSpecificTerms',
	'excludePostWithSpecificTerms',
	'includePosts',
	'metaQuery',
	'orderBy',
	'order',
	'postsNumber',
	'offsetNumber',
];
check(JSON.stringify(Object.keys(fields)) === JSON.stringify(expectedFieldOrder), 'Events controls are not immediately after post type');
adapter.EVENT_FIELD_NAMES.forEach((name) => {
	check(Object.keys(fields).filter((fieldName) => fieldName === name).length === 1, name + ' was duplicated');
	check(!Object.prototype.hasOwnProperty.call(fields[name], 'priority'), name + ' received an artificial priority');
});
const customMessageFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: {
		...eventLoop,
		wpSeedEventEmptyBehavior: 'custom_message',
		wpSeedEventEmptyMessage: 'Complet pour le moment.',
	},
});
check(customMessageFields.wpSeedEventEmptyMessage.visible === true, 'custom message field was not revealed');
const filteredTwice = fieldsFilter(fields, {
	attrName: 'module.advanced.loop',
	loopValues: eventLoop,
});
check(JSON.stringify(Object.keys(filteredTwice)) === JSON.stringify(expectedFieldOrder), 'second field pass duplicated or reordered controls');

const ordinaryFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: { queryType: 'post_types', subTypes: [{ value: 'post' }] },
});
check(ordinaryFields === nativeFields, 'ordinary loop fields changed');

const mixedFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: { queryType: 'post_types', subTypes: [{ value: 'post' }, { value: 'wp_seed_event' }] },
});
check(mixedFields === nativeFields, 'mixed loop fields changed');

const compactEventLoop = {
	enable: 'on',
	loopId: 'hero-events',
	subTypes: [{ value: 'wp_seed_event', label: 'Evenements' }],
};
const compactFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: compactEventLoop,
});
check(Boolean(compactFields.wpSeedEventTypes), 'compact Divi column omitted event types control');
check(Boolean(compactFields.wpSeedEventPinned), 'compact Divi column omitted pinned control');

const categorizedCompactLoop = {
	enable: 'on',
	loopId: 'hero-events-categorized',
	subTypes: [{
		categoryId: 'post_types',
		selectedOptions: [{ value: 'wp_seed_event', label: 'Evenements' }],
	}],
};
const categorizedCompactFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: categorizedCompactLoop,
});
check(Boolean(categorizedCompactFields.wpSeedEventTypes), 'categorized compact loop omitted event types control');
check(Boolean(categorizedCompactFields.wpSeedEventPinned), 'categorized compact loop omitted pinned control');

const compactAttrs = {
	module: { advanced: { loop: { desktop: { value: {
		...compactEventLoop,
		wpSeedEventTypes: [{ value: '11', label: 'Journee decouverte' }],
		wpSeedEventPinned: 'all',
		wpSeedEventEmptyBehavior: 'hide',
		wpSeedEventEmptyMessage: '',
	} } } } },
};
const compactParams = new URLSearchParams();
adapter.createQueryParamsFilter()(compactParams, compactAttrs, 'compact-column', undefined);
check(compactParams.get('wp_seed_event_types') === '11', 'compact loop types were not sent to REST');
check(compactParams.get('wp_seed_event_pinned') === 'all', 'compact loop pinned state was not sent to REST');
check(compactParams.get('wp_seed_event_empty_behavior') === 'hide', 'compact loop empty behavior was not sent to REST');
check(compactParams.get('wp_seed_event_empty_message') === '', 'explicit empty message was not sent to REST');
const attrs = {
	module: {
		advanced: {
			loop: {
				desktop: {
					value: {
						...eventLoop,
						wpSeedEventTypes: [{
							categoryId: 'wp_seed_event_type',
							categoryName: 'Types d’événement',
							selectedOptions: [
								{ value: '11', label: 'Journée découverte' },
								{ value: '12', label: 'Réunion d’information' },
							],
						}],
						wpSeedEventPinned: 'exclude_featured',
						wpSeedEventEmptyBehavior: 'custom_message',
						wpSeedEventEmptyMessage: '<b>Aucun événement</b>',
					},
				},
			},
		},
	},
};
const params = new URLSearchParams('query_type=post_type&post_type=wp_seed_event');
const filteredParams = adapter.createQueryParamsFilter()(params, attrs, 'module-a', 'post_types');

check(filteredParams === params, 'query params object was replaced');
check(params.get('wp_seed_event_types') === '11,12', 'multiple type IDs differ');
check(params.get('wp_seed_event_pinned') === 'exclude_featured', 'pinned value differs');
check(params.get('wp_seed_event_empty_behavior') === 'custom_message', 'empty behavior differs');
check(params.get('wp_seed_event_empty_message') === '<b>Aucun événement</b>', 'empty message differs');
check(
	JSON.stringify(adapter.selectionValues([{ value: '11' }, { value: '12' }])) === JSON.stringify(['11', '12']),
	'legacy flat selection differs'
);

const emptyAttrs = JSON.parse(JSON.stringify(attrs));
emptyAttrs.module.advanced.loop.desktop.value.wpSeedEventTypes = [];
emptyAttrs.module.advanced.loop.desktop.value.wpSeedEventPinned = 'all';
const emptyParams = new URLSearchParams();
adapter.createQueryParamsFilter()(emptyParams, emptyAttrs, 'module-b', 'post_types');
check(emptyParams.has('wp_seed_event_types'), 'empty type override was omitted');
check(emptyParams.get('wp_seed_event_types') === '', 'empty type override differs');
check(emptyParams.get('wp_seed_event_pinned') === 'all', 'all pinned value differs');

const ordinaryParams = new URLSearchParams();
adapter.createQueryParamsFilter()(ordinaryParams, {
	module: { advanced: { loop: { desktop: { value: { queryType: 'post_types', subTypes: [{ value: 'post' }] } } } } },
}, 'module-c', 'post_types');
check(!ordinaryParams.has('wp_seed_event_types'), 'ordinary query received event types');
check(!ordinaryParams.has('wp_seed_event_pinned'), 'ordinary query received pinned state');
check(!ordinaryParams.has('wp_seed_event_empty_behavior'), 'ordinary query received empty behavior');
check(!ordinaryParams.has('wp_seed_event_empty_message'), 'ordinary query received empty message');

const untouchedEventLoop = adapter.migrateLoopValues(eventLoop, nativeFields.includePostWithSpecificTerms.component.props.options);
check(!Object.prototype.hasOwnProperty.call(untouchedEventLoop, 'wpSeedEventTypes'), 'new event loop received automatic types');
check(!Object.prototype.hasOwnProperty.call(untouchedEventLoop, 'wpSeedEventPinned'), 'new event loop received automatic featured state');
check(!Object.prototype.hasOwnProperty.call(untouchedEventLoop, 'wpSeedEventEmptyBehavior'), 'existing loop received an implicit empty behavior');
check(!Object.prototype.hasOwnProperty.call(untouchedEventLoop, 'wpSeedEventEmptyMessage'), 'existing loop received an implicit empty message');

const savedCustomLoop = adapter.migrateLoopValues({
	...eventLoop,
	wpSeedEventEmptyBehavior: 'custom_message',
	wpSeedEventEmptyMessage: 'Bientôt disponible.',
}, nativeFields.includePostWithSpecificTerms.component.props.options);
const duplicatedHiddenLoop = adapter.migrateLoopValues({
	...savedCustomLoop,
	wpSeedEventEmptyBehavior: 'hide',
	wpSeedEventEmptyMessage: '',
}, nativeFields.includePostWithSpecificTerms.component.props.options);
check(savedCustomLoop.wpSeedEventEmptyBehavior === 'custom_message', 'saved behavior did not survive reopening');
check(savedCustomLoop.wpSeedEventEmptyMessage === 'Bientôt disponible.', 'saved message did not survive reopening');
check(duplicatedHiddenLoop.wpSeedEventEmptyBehavior === 'hide', 'duplicated loop behavior was not independent');
check(savedCustomLoop.wpSeedEventEmptyBehavior !== duplicatedHiddenLoop.wpSeedEventEmptyBehavior, 'two loops cannot retain different behaviors');


check(
	JSON.stringify(Object.keys(fields.includePostWithSpecificTerms.component.props.options)) === JSON.stringify(['category']),
	'native include options still expose Events taxonomies'
);

const legacyLoop = {
	...eventLoop,
	includePostWithSpecificTerms: [
		{
			categoryId: 'wp_seed_event_type',
			categoryName: "Types d’événement",
			selectedOptions: [
				{ value: '11', label: 'Journée découverte' },
				{ value: '12', label: 'Réunion d’information' },
			],
		},
		{
			categoryId: 'wp_seed_event_flag',
			categoryName: "Indicateurs d’événement",
			selectedOptions: [{ value: '21', label: 'Événement épinglé' }],
		},
		{
			categoryId: 'category',
			categoryName: 'Catégories',
			selectedOptions: [{ value: '30', label: 'Actualités' }],
		},
	],
};
const migratedLegacy = adapter.migrateLoopValues(
	legacyLoop,
	nativeFields.includePostWithSpecificTerms.component.props.options
);
check(adapter.selectionValues(migratedLegacy.wpSeedEventTypes).join(',') === '11,12', 'legacy types were not migrated');
check(migratedLegacy.wpSeedEventPinned === 'featured_only', 'legacy featured inclusion was not migrated');
check(migratedLegacy.includePostWithSpecificTerms.length === 1, 'unrelated native taxonomy was not preserved');
check(migratedLegacy.includePostWithSpecificTerms[0].categoryId === 'category', 'wrong native taxonomy survived');

const legacyFields = fieldsFilter(nativeFields, {
	attrName: 'module.advanced.loop',
	loopValues: legacyLoop,
});
check(
	adapter.selectionValues(legacyFields.wpSeedEventTypes.defaultAttr.desktop.value).join(',') === '11,12',
	'legacy types are not visible in the dedicated control'
);
check(legacyFields.wpSeedEventPinned.defaultAttr.desktop.value === 'featured_only', 'legacy featured is not visible in the dedicated control');

const legacyParams = new URLSearchParams();
adapter.createQueryParamsFilter()(legacyParams, {
	module: { advanced: { loop: { desktop: { value: legacyLoop } } } },
}, 'legacy-loop', 'post_types');
check(legacyParams.get('wp_seed_event_types') === '11,12', 'legacy types were not sent to REST');
check(legacyParams.get('wp_seed_event_pinned') === 'featured_only', 'legacy featured was not sent to REST');

const excludedFeatured = adapter.migrateLoopValues({
	...eventLoop,
	excludePostWithSpecificTerms: [{
		categoryId: 'wp_seed_event_flag',
		selectedOptions: [{ value: '21', label: 'Événement épinglé' }],
	}],
}, nativeFields.includePostWithSpecificTerms.component.props.options);
check(excludedFeatured.wpSeedEventPinned === 'exclude_featured', 'legacy featured exclusion was not migrated');
check(excludedFeatured.excludePostWithSpecificTerms.length === 0, 'featured remained in native exclusion');

const explicitControls = adapter.migrateLoopValues({
	...legacyLoop,
	wpSeedEventTypes: [{ value: '12', label: 'Stage' }],
	wpSeedEventPinned: 'all',
}, nativeFields.includePostWithSpecificTerms.component.props.options);
check(adapter.selectionValues(explicitControls.wpSeedEventTypes).join(',') === '12', 'explicit types lost priority');
check(explicitControls.wpSeedEventPinned === 'all', 'explicit pinned state lost priority');
check(explicitControls.includePostWithSpecificTerms.length === 1, 'native Events terms survived explicit controls');

const bootstrapSource = fs.readFileSync(
	path.resolve(__dirname, '../includes/integrations/divi/bootstrap.php'),
	'utf8'
);
check(bootstrapSource.includes("hash_file( 'sha256', $asset_path )"), 'collection asset version is not content-derived');
check(!bootstrapSource.includes("'version' => WP_SEED_EVENTS_VERSION,\n\t\t\t'script'  => array(\n\t\t\t\t'src'                => plugins_url( 'event-collection-filters.js'"), 'collection asset still uses the plugin version cache key');

const registrations = [];
const windowObject = {
	vendor: { wp: { hooks: { addFilter: (...args) => registrations.push(args) } } },
};
check(adapter.register(windowObject), 'adapter did not register');
check(registrations.length === 2, 'adapter hook count differs');
check(registrations[0][0] === adapter.FIELDS_FILTER, 'fields hook differs');
check(registrations[1][0] === adapter.QUERY_FILTER, 'query hook differs');
check(!adapter.register(windowObject), 'registration guard failed');
check(registrations.length === 2, 'duplicate filters were registered');

console.log(`Divi event collection filters: ${assertions}/${assertions} assertions passed.`);