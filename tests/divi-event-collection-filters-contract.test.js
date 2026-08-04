'use strict';

const assert = require('assert');
const path = require('path');

const adapter = require(path.resolve(__dirname, '../includes/integrations/divi/event-collection-filters.js'));

let assertions = 0;
const check = (condition, message) => {
	assertions += 1;
	assert.ok(condition, message);
};

const nativeFields = {
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
				},
			},
		},
	},
	excludePostWithSpecificTerms: {
		attrName: 'module.advanced.loop',
		subName: 'excludePostWithSpecificTerms',
		visible: true,
	},
	orderBy: { visible: true },
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

check(fields.includePostWithSpecificTerms.visible === false, 'generic include control stayed visible');
check(fields.excludePostWithSpecificTerms.visible === false, 'generic exclude control stayed visible');
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