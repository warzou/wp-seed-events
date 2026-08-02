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

	const SOURCE_NAME = 'wp_seed_events_communication_visual';
	const LOOP_FIELD = 'wp_seed_events_communication_visual';
	const FILTER_NAME = 'divi.module.wrapper.render';
	const FILTER_NAMESPACE = 'wpSeedEvents.dynamicEventImagePreview';

	const toPlainObject = (value) => (
		value && typeof value.toJS === 'function' ? value.toJS() : value
	);

	const isLegacyImageToken = (value, moduleUtils) => {
		if (typeof value !== 'string' || !value.includes(SOURCE_NAME)) {
			return false;
		}

		const parsed = moduleUtils?.parseDynamicData?.(value);

		return Boolean(parsed && parsed.value?.name === SOURCE_NAME);
	};

	const sanitizePreviewUrl = (value) => {
		if (typeof value !== 'string' || value === '') {
			return '';
		}

		try {
			const url = new URL(value);

			return ['http:', 'https:'].includes(url.protocol) ? url.href : '';
		} catch (error) {
			return '';
		}
	};

	const replaceLegacyImageSource = (attrs, imageUrl, moduleUtils) => {
		const innerContent = attrs?.image?.innerContent;

		if (!innerContent || typeof innerContent !== 'object') {
			return attrs;
		}

		let changed = false;
		const nextInnerContent = { ...innerContent };

		Object.entries(innerContent).forEach(([breakpoint, breakpointValue]) => {
			const value = breakpointValue?.value;

			if (!value || typeof value !== 'object' || !isLegacyImageToken(value.src, moduleUtils)) {
				return;
			}

			changed = true;
			nextInnerContent[breakpoint] = {
				...breakpointValue,
				value: {
					...value,
					src: imageUrl,
				},
			};
		});

		if (!changed) {
			return attrs;
		}

		return {
			...attrs,
			image: {
				...attrs.image,
				innerContent: nextInnerContent,
			},
		};
	};

	const clonePreviewTree = (element, imageUrl, moduleId, dependencies) => {
		const { React, moduleUtils } = dependencies;

		if (!React.isValidElement(element)) {
			return element;
		}

		const props = element.props || {};
		const nextProps = {};
		let changed = false;

		if (props.attrName === 'image' && props.moduleId === moduleId) {
			['moduleAttrs', 'runtimeModuleAttrs'].forEach((key) => {
				const nextAttrs = replaceLegacyImageSource(props[key], imageUrl, moduleUtils);

				if (nextAttrs !== props[key]) {
					nextProps[key] = nextAttrs;
					changed = true;
				}
			});
		}

		if (Object.prototype.hasOwnProperty.call(props, 'children')) {
			let childrenChanged = false;
			const nextChildren = React.Children.map(props.children, (child) => {
				const nextChild = clonePreviewTree(child, imageUrl, moduleId, dependencies);

				childrenChanged = childrenChanged || nextChild !== child;
				return nextChild;
			});

			if (childrenChanged) {
				nextProps.children = nextChildren;
				changed = true;
			}
		}

		return changed ? React.cloneElement(element, nextProps) : element;
	};

	const createPreviewFilter = (dependencies) => (element, context) => {
		if (
			context?.name !== 'divi/image'
			|| context?.isLooped !== true
			|| !Number.isInteger(context?.loopIndex)
			|| context.loopIndex < 0
		) {
			return element;
		}

		const source = context?.attrs?.image?.innerContent;
		const hasLegacySource = source && Object.values(source).some(
			(entry) => isLegacyImageToken(entry?.value?.src, dependencies.moduleUtils)
		);

		if (!hasLegacySource) {
			return element;
		}

		const store = dependencies.data?.select?.('divi/edit-post');
		const loopData = toPlainObject(store?.getModuleLoopData?.(context.id));
		const item = Array.isArray(loopData?.queryItems)
			? loopData.queryItems[context.loopIndex]
			: null;
		const imageUrl = sanitizePreviewUrl(item?.[LOOP_FIELD]);

		return clonePreviewTree(element, imageUrl, context.id, dependencies);
	};

	const register = (windowObject) => {
		if (windowObject.__wpSeedEventsDynamicImagePreviewRegistered) {
			return false;
		}

		const hooks = windowObject.vendor?.wp?.hooks;
		const React = windowObject.vendor?.React;
		const data = windowObject.divi?.data;
		const moduleUtils = windowObject.divi?.moduleUtils;

		if (!hooks?.addFilter || !React?.cloneElement || !data?.select || !moduleUtils?.parseDynamicData) {
			return false;
		}

		hooks.addFilter(
			FILTER_NAME,
			FILTER_NAMESPACE,
			createPreviewFilter({ React, data, moduleUtils })
		);
		windowObject.__wpSeedEventsDynamicImagePreviewRegistered = true;

		return true;
	};

	return {
		SOURCE_NAME,
		LOOP_FIELD,
		FILTER_NAME,
		FILTER_NAMESPACE,
		clonePreviewTree,
		createPreviewFilter,
		isLegacyImageToken,
		register,
		replaceLegacyImageSource,
		sanitizePreviewUrl,
	};
});