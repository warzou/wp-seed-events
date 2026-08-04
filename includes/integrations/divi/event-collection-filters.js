(function (root, factory) {
	'use strict';

	const api = factory();

	if (typeof module === 'object' && module.exports) {
		module.exports = api;
	}

	if (root) {
		api.register(root);
	}
})(typeof window === 'object' ? window : null, function () {
	'use strict';

	const FIELDS_FILTER = 'divi.module.options.loop.group.fields';
	const QUERY_FILTER = 'divi.module.layout.childModule.loop.resultsQueryParams';
	const NAMESPACE = 'wpSeedEvents.eventCollectionFilters';
	const EVENT_POST_TYPE = 'wp_seed_event';
	const TYPE_TAXONOMY = 'wp_seed_event_type';

	const toPlain = (value) => {
		if (value && typeof value.toJS === 'function') {
			return value.toJS();
		}

		if (value && typeof value.asMutable === 'function') {
			return value.asMutable({ deep: true });
		}

		return value;
	};

	const getLoopValues = (attrs) => {
		const plain = toPlain(attrs);

		return plain?.module?.advanced?.loop?.desktop?.value || {};
	};

	const selectedPostTypes = (loopValues) => {
		const values = toPlain(loopValues?.subTypes);
		const items = Array.isArray(values) ? values : (values ? [values] : []);

		return items.map((item) => String(toPlain(item)?.value ?? item ?? ''));
	};

	const isEventLoop = (loopValues) => {
		const postTypes = selectedPostTypes(loopValues);

		return loopValues?.queryType === 'post_types'
			&& postTypes.length === 1
			&& postTypes[0] === EVENT_POST_TYPE;
	};

	const typeOptions = (props) => {
		const options = props?.options || {};

		return Object.prototype.hasOwnProperty.call(options, TYPE_TAXONOMY)
			? { [TYPE_TAXONOMY]: options[TYPE_TAXONOMY] }
			: {};
	};

	const createFieldsFilter = () => (fields, context) => {
		if (!isEventLoop(context?.loopValues) || !fields?.includePostWithSpecificTerms) {
			return fields;
		}

		const nativeTypeField = fields.includePostWithSpecificTerms;
		const nativeProps = nativeTypeField.component?.props || {};

		return {
			...fields,
			includePostWithSpecificTerms: {
				...nativeTypeField,
				visible: false,
			},
			excludePostWithSpecificTerms: fields.excludePostWithSpecificTerms
				? { ...fields.excludePostWithSpecificTerms, visible: false }
				: fields.excludePostWithSpecificTerms,
			wpSeedEventTypes: {
				...nativeTypeField,
				subName: 'wpSeedEventTypes',
				label: "Types d’événement",
				description: 'Sélectionnez un ou plusieurs types. Plusieurs types sont combinés avec une logique OU.',
				priority: 61,
				visible: true,
				component: {
					...nativeTypeField.component,
					props: {
						...nativeProps,
						options: typeOptions(nativeProps),
						showTagsWithCategory: false,
						placeholder: '+ Sélectionner des types',
					},
				},
			},
			wpSeedEventPinned: {
				attrName: context.attrName,
				subName: 'wpSeedEventPinned',
				label: 'Épinglage',
				description: "Filtrez les événements selon l’indicateur natif Événement épinglé.",
				priority: 62,
				render: true,
				visible: true,
				features: {
					dynamicContent: false,
					hover: false,
					responsive: false,
					sticky: false,
				},
				component: {
					type: 'field',
					name: 'divi/select',
					props: {
						defaultValue: 'all',
						options: {
							all: { label: 'Tous' },
							featured_only: { label: 'Uniquement les événements épinglés' },
							exclude_featured: { label: 'Exclure les événements épinglés' },
						},
					},
				},
			},
		};
	};

	const selectionValues = (selection) => {
		const plain = toPlain(selection);
		const queue = Array.isArray(plain) ? [...plain] : (plain ? [plain] : []);
		const values = [];

		while (queue.length > 0) {
			const item = toPlain(queue.shift());

			if (item && Array.isArray(toPlain(item.selectedOptions))) {
				queue.push(...toPlain(item.selectedOptions));
				continue;
			}

			const value = String(item?.value ?? item ?? '').trim();

			if (value) {
				values.push(value);
			}
		}

		return [...new Set(values)];
	};

	const createQueryParamsFilter = () => (params, attrs, moduleId, queryType) => {
		const loopValues = getLoopValues(attrs);

		if (queryType !== 'post_types' || !isEventLoop(loopValues)) {
			return params;
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventTypes')) {
			params.set('wp_seed_event_types', selectionValues(loopValues.wpSeedEventTypes).join(','));
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventPinned')) {
			params.set('wp_seed_event_pinned', String(loopValues.wpSeedEventPinned || 'all'));
		}

		return params;
	};

	const register = (windowObject) => {
		if (windowObject.__wpSeedEventsCollectionFiltersRegistered) {
			return false;
		}

		const hooks = windowObject.vendor?.wp?.hooks;

		if (!hooks?.addFilter) {
			return false;
		}

		hooks.addFilter(FIELDS_FILTER, NAMESPACE, createFieldsFilter());
		hooks.addFilter(QUERY_FILTER, NAMESPACE, createQueryParamsFilter());
		windowObject.__wpSeedEventsCollectionFiltersRegistered = true;

		return true;
	};

	return {
		EVENT_POST_TYPE,
		FIELDS_FILTER,
		NAMESPACE,
		QUERY_FILTER,
		TYPE_TAXONOMY,
		createFieldsFilter,
		createQueryParamsFilter,
		getLoopValues,
		isEventLoop,
		register,
		selectedPostTypes,
		selectionValues,
		typeOptions,
	};
});