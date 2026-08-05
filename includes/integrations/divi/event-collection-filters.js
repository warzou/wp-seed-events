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
	const FLAG_TAXONOMY = 'wp_seed_event_flag';
	const MANAGED_TAXONOMIES = [TYPE_TAXONOMY, FLAG_TAXONOMY];
	const EMPTY_BEHAVIOR_DEFAULT = 'divi_default';
	const EMPTY_MESSAGE_DEFAULT = 'Aucun événement à venir pour le moment.';
	const EVENT_FIELD_NAMES = [
		'wpSeedEventTypes',
		'wpSeedEventPinned',
		'wpSeedEventEmptyBehavior',
		'wpSeedEventEmptyMessage',
	];

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
		const queue = Array.isArray(values) ? [...values] : (values ? [values] : []);
		const postTypes = [];

		while (queue.length > 0) {
			const item = toPlain(queue.shift());
			const selectedOptions = toPlain(item?.selectedOptions);

			if (Array.isArray(selectedOptions)) {
				queue.push(...selectedOptions);
				continue;
			}

			const value = String(item?.value ?? item ?? '').trim();

			if (value) {
				postTypes.push(value);
			}
		}

		return [...new Set(postTypes)];
	};

	const isEventLoop = (loopValues) => {
		const postTypes = selectedPostTypes(loopValues);
		const queryType = String(loopValues?.queryType || 'post_types');

		return queryType === 'post_types'
			&& postTypes.length === 1
			&& postTypes[0] === EVENT_POST_TYPE;
	};

	const typeOptions = (props) => {
		const options = props?.options || {};

		return Object.prototype.hasOwnProperty.call(options, TYPE_TAXONOMY)
			? { [TYPE_TAXONOMY]: options[TYPE_TAXONOMY] }
			: {};
	};

	const unmanagedOptions = (props) => Object.fromEntries(
		Object.entries(props?.options || {}).filter(([taxonomy]) => !MANAGED_TAXONOMIES.includes(taxonomy))
	);

	const taxonomyForValue = (value, options) => {
		const needle = String(value ?? '');

		return Object.entries(options || {}).find(([, category]) => (
			Object.prototype.hasOwnProperty.call(category?.options || {}, needle)
		))?.[0] || '';
	};

	const splitNativeSelection = (selection, options = {}) => {
		const plain = toPlain(selection);
		const items = Array.isArray(plain) ? plain : (plain ? [plain] : []);
		const result = { types: [], featured: false, unrelated: [] };

		items.forEach((rawItem) => {
			const item = toPlain(rawItem);

			if (item && Array.isArray(toPlain(item.selectedOptions))) {
				const taxonomy = String(item.categoryId || '');
				const kept = [];

				toPlain(item.selectedOptions).forEach((rawOption) => {
					const option = toPlain(rawOption);
					const value = String(option?.value ?? option ?? '');
					const optionTaxonomy = taxonomy || taxonomyForValue(value, options);

					if (optionTaxonomy === TYPE_TAXONOMY) {
						result.types.push(option);
					} else if (optionTaxonomy === FLAG_TAXONOMY) {
						result.featured = true;
					} else {
						kept.push(option);
					}
				});

				if (kept.length > 0) {
					result.unrelated.push({ ...item, selectedOptions: kept });
				}

				return;
			}

			const value = String(item?.value ?? item ?? '');
			const taxonomy = taxonomyForValue(value, options);

			if (taxonomy === TYPE_TAXONOMY) {
				result.types.push(item);
			} else if (taxonomy === FLAG_TAXONOMY) {
				result.featured = true;
			} else if (value) {
				result.unrelated.push(item);
			}
		});

		return result;
	};

	const categorizedTypes = (types, options) => {
		if (types.length === 0) {
			return [];
		}

		return [{
			categoryId: TYPE_TAXONOMY,
			categoryName: options?.[TYPE_TAXONOMY]?.label || "Types d’événement",
			selectedOptions: types,
		}];
	};

	const migrateLoopValues = (rawLoopValues, options = {}) => {
		const loopValues = { ...(toPlain(rawLoopValues) || {}) };
		const include = splitNativeSelection(loopValues.includePostWithSpecificTerms, options);
		const exclude = splitNativeSelection(loopValues.excludePostWithSpecificTerms, options);

		if (!Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventTypes') && include.types.length > 0) {
			loopValues.wpSeedEventTypes = categorizedTypes(include.types, options);
		}

		if (
			!Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventPinned')
			&& (include.types.length > 0 || include.featured || exclude.featured)
		) {
			loopValues.wpSeedEventPinned = exclude.featured
				? 'exclude_featured'
				: (include.featured ? 'featured_only' : 'all');
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'includePostWithSpecificTerms')) {
			loopValues.includePostWithSpecificTerms = include.unrelated;
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'excludePostWithSpecificTerms')) {
			loopValues.excludePostWithSpecificTerms = exclude.unrelated;
		}

		return loopValues;
	};

	const insertFieldsAfter = (fields, anchor, additions) => {
		const result = {};
		const additionNames = new Set(Object.keys(additions));
		let inserted = false;

		Object.entries(fields || {}).forEach(([name, field]) => {
			if (additionNames.has(name)) {
				return;
			}

			result[name] = field;

			if (name === anchor) {
				Object.assign(result, additions);
				inserted = true;
			}
		});

		if (!inserted) {
			Object.assign(result, additions);
		}

		return result;
	};

	const createFieldsFilter = () => (fields, context) => {
		if (!isEventLoop(context?.loopValues) || !fields?.includePostWithSpecificTerms) {
			return fields;
		}

		const nativeTypeField = fields.includePostWithSpecificTerms;
		const nativeProps = nativeTypeField.component?.props || {};
		const migrated = migrateLoopValues(context.loopValues, nativeProps.options);
		const nativeUnmanagedProps = {
			...nativeProps,
			options: unmanagedOptions(nativeProps),
		};

		const normalizedFields = {
			...fields,
			includePostWithSpecificTerms: {
				...nativeTypeField,
				visible: true,
				component: {
					...nativeTypeField.component,
					props: nativeUnmanagedProps,
				},
			},
			excludePostWithSpecificTerms: fields.excludePostWithSpecificTerms
				? {
					...fields.excludePostWithSpecificTerms,
					visible: true,
					component: {
						...fields.excludePostWithSpecificTerms.component,
						props: {
							...(fields.excludePostWithSpecificTerms.component?.props || {}),
							options: unmanagedOptions(fields.excludePostWithSpecificTerms.component?.props || nativeProps),
						},
					},
				}
				: fields.excludePostWithSpecificTerms,
		};
		const eventFields = {
			wpSeedEventTypes: {
				...nativeTypeField,
				subName: 'wpSeedEventTypes',
				label: "Types d’événement",
				description: 'Sélectionnez un ou plusieurs types. Plusieurs types sont combinés avec une logique OU.',
				visible: true,
				defaultAttr: { desktop: { value: migrated.wpSeedEventTypes || [] } },
				component: {
					...nativeTypeField.component,
					props: {
						...nativeProps,
						defaultValue: migrated.wpSeedEventTypes || [],
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
				render: true,
				visible: true,
				defaultAttr: { desktop: { value: migrated.wpSeedEventPinned || 'all' } },
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
						defaultValue: migrated.wpSeedEventPinned || 'all',
						options: {
							all: { label: 'Tous' },
							featured_only: { label: 'Uniquement les événements épinglés' },
							exclude_featured: { label: 'Exclure les événements épinglés' },
						},
					},
				},
			},
			wpSeedEventEmptyBehavior: {
				attrName: context.attrName,
				subName: 'wpSeedEventEmptyBehavior',
				label: 'Si aucun événement n’est trouvé',
				render: true,
				visible: true,
				defaultAttr: { desktop: { value: EMPTY_BEHAVIOR_DEFAULT } },
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
						defaultValue: EMPTY_BEHAVIOR_DEFAULT,
						options: {
							hide: { label: 'Masquer l’élément de boucle' },
							custom_message: { label: 'Afficher un message personnalisé' },
							divi_default: { label: 'Utiliser le comportement Divi par défaut' },
						},
					},
				},
			},
			wpSeedEventEmptyMessage: {
				attrName: context.attrName,
				subName: 'wpSeedEventEmptyMessage',
				label: 'Message si aucun événement',
				render: true,
				visible: String(migrated.wpSeedEventEmptyBehavior || EMPTY_BEHAVIOR_DEFAULT) === 'custom_message',
				defaultAttr: { desktop: { value: EMPTY_MESSAGE_DEFAULT } },
				features: {
					dynamicContent: false,
					hover: false,
					responsive: false,
					sticky: false,
				},
				component: {
					type: 'field',
					name: 'divi/text',
					props: {
						defaultValue: EMPTY_MESSAGE_DEFAULT,
					},
				},
			},
		};

		return insertFieldsAfter(normalizedFields, 'subTypes', eventFields);
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
		const loopValues = migrateLoopValues(getLoopValues(attrs));

		const effectiveQueryType = String(queryType || loopValues?.queryType || 'post_types');

		if (effectiveQueryType !== 'post_types' || !isEventLoop(loopValues)) {
			return params;
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventTypes')) {
			params.set('wp_seed_event_types', selectionValues(loopValues.wpSeedEventTypes).join(','));
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventPinned')) {
			params.set('wp_seed_event_pinned', String(loopValues.wpSeedEventPinned || 'all'));
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventEmptyBehavior')) {
			params.set('wp_seed_event_empty_behavior', String(loopValues.wpSeedEventEmptyBehavior || EMPTY_BEHAVIOR_DEFAULT));
		}

		if (Object.prototype.hasOwnProperty.call(loopValues, 'wpSeedEventEmptyMessage')) {
			params.set('wp_seed_event_empty_message', String(loopValues.wpSeedEventEmptyMessage || ''));
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
		EMPTY_BEHAVIOR_DEFAULT,
		EMPTY_MESSAGE_DEFAULT,
		EVENT_FIELD_NAMES,
		FIELDS_FILTER,
		FLAG_TAXONOMY,
		MANAGED_TAXONOMIES,
		NAMESPACE,
		QUERY_FILTER,
		TYPE_TAXONOMY,
		categorizedTypes,
		createFieldsFilter,
		createQueryParamsFilter,
		getLoopValues,
		insertFieldsAfter,
		isEventLoop,
		migrateLoopValues,
		register,
		selectedPostTypes,
		selectionValues,
		splitNativeSelection,
		taxonomyForValue,
		typeOptions,
		unmanagedOptions,
	};
});
