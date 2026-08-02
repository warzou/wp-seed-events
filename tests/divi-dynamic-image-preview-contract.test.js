'use strict';

const assert = require('assert');
const path = require('path');

const preview = require(path.resolve(__dirname, '../includes/integrations/divi/dynamic-event-image-preview.js'));

let assertions = 0;
const check = (condition, message) => {
	assertions += 1;
	assert.ok(condition, message);
};

const legacyToken = '$variable({"type":"content","value":{"name":"wp_seed_events_communication_visual","settings":{}}})$';
const loopToken = '$variable({"type":"content","value":{"name":"loop_wp_seed_events_communication_visual","settings":{}}})$';

const React = {
	Children: {
		map: (children, callback) => (
			Array.isArray(children)
				? children.map(callback)
				: (children == null ? children : callback(children))
		),
	},
	cloneElement: (element, props) => ({ ...element, props: { ...element.props, ...props } }),
	isValidElement: (element) => Boolean(element && element.$$typeof === 'test.element'),
};

const moduleUtils = {
	parseDynamicData: (value) => {
		if (typeof value !== 'string' || !value.startsWith('$variable(') || !value.endsWith(')$')) {
			return false;
		}

		return JSON.parse(value.slice(10, -2));
	},
};

const element = (type, props = {}) => ({ $$typeof: 'test.element', type, props });
const imageAttrs = (src, linkUrl = 'https://example.test/event') => ({
	image: {
		innerContent: {
			desktop: { value: { src, linkUrl, alt: 'Affiche' } },
		},
	},
});
const imageTree = (moduleId, src = legacyToken) => element('wrapper', {
	children: [
		element('other', { children: 'unchanged' }),
		element('image-element', {
			attrName: 'image',
			moduleId,
			moduleAttrs: imageAttrs(src),
			runtimeModuleAttrs: imageAttrs(src),
		}),
	],
});

const queryData = {
	'loop-a': {
		queryItems: [
			{ id: 101, wp_seed_events_communication_visual: 'https://cdn.example.test/a.jpg' },
			{ id: 102, wp_seed_events_communication_visual: 'https://cdn.example.test/b.jpg' },
			{ id: 103 },
		],
	},
	'loop-b': {
		queryItems: [
			{ id: 201, wp_seed_events_communication_visual: 'https://cdn.example.test/c.jpg' },
		],
	},
};
const data = {
	select: (store) => {
		check(store === 'divi/edit-post', 'unexpected Divi store');
		return { getModuleLoopData: (moduleId) => queryData[moduleId] || { queryItems: [] } };
	},
};

const filter = preview.createPreviewFilter({ React, data, moduleUtils });
const context = (id, loopIndex, overrides = {}) => ({
	id,
	name: 'divi/image',
	isLooped: true,
	loopIndex,
	attrs: imageAttrs(legacyToken),
	...overrides,
});
const resolvedSrc = (tree, key = 'runtimeModuleAttrs') => tree.props.children[1].props[key].image.innerContent.desktop.value.src;
const resolvedLink = (tree) => tree.props.children[1].props.runtimeModuleAttrs.image.innerContent.desktop.value.linkUrl;

check(preview.SOURCE_NAME === 'wp_seed_events_communication_visual', 'source name differs');
check(preview.FILTER_NAME === 'divi.module.wrapper.render', 'Divi filter differs');
check(preview.isLegacyImageToken(legacyToken, moduleUtils), 'historical token not recognized');
check(!preview.isLegacyImageToken(loopToken, moduleUtils), 'loop token treated as historical');
check(preview.sanitizePreviewUrl('https://cdn.example.test/a.jpg') === 'https://cdn.example.test/a.jpg', 'HTTPS URL changed');
check(preview.sanitizePreviewUrl('javascript:alert(1)') === '', 'unsafe URL accepted');

const first = filter(imageTree('loop-a'), context('loop-a', 0));
const second = filter(imageTree('loop-a'), context('loop-a', 1));
const empty = filter(imageTree('loop-a'), context('loop-a', 2));
const independent = filter(imageTree('loop-b'), context('loop-b', 0));

check(resolvedSrc(first) === 'https://cdn.example.test/a.jpg', 'first loop item differs');
check(resolvedSrc(first, 'moduleAttrs') === 'https://cdn.example.test/a.jpg', 'first source attrs differ');
check(resolvedSrc(second) === 'https://cdn.example.test/b.jpg', 'second loop item differs');
check(resolvedSrc(empty) === '', 'event without visual did not stay empty');
check(resolvedSrc(independent) === 'https://cdn.example.test/c.jpg', 'second loop leaked first loop data');
check(resolvedSrc(first) === 'https://cdn.example.test/a.jpg', 'later render mutated first loop item');
check(resolvedLink(first) === 'https://example.test/event', 'event detail link changed');
check(first.props.children[0].props.children === 'unchanged', 'unrelated child changed');

const duplicated = filter(imageTree('loop-a'), context('loop-a', 1));
check(resolvedSrc(duplicated) === 'https://cdn.example.test/b.jpg', 'duplicated loop did not resolve its item');

const outside = imageTree('loop-a');
check(filter(outside, context('loop-a', 0, { isLooped: false })) === outside, 'outside-loop module changed');
check(filter(outside, context('loop-a', 0, { name: 'divi/text' })) === outside, 'non-image module changed');
check(filter(outside, context('loop-a', 0, { loopIndex: null })) === outside, 'missing loop index used');

const canonicalLoop = imageTree('loop-a', loopToken);
check(filter(canonicalLoop, context('loop-a', 0, { attrs: imageAttrs(loopToken) })) === canonicalLoop, 'canonical loop token changed');

const registrations = [];
const windowObject = {
	vendor: {
		React,
		wp: { hooks: { addFilter: (...args) => registrations.push(args) } },
	},
	divi: { data, moduleUtils },
};

check(preview.register(windowObject), 'adapter did not register');
check(registrations.length === 1, 'adapter registered more than once');
check(registrations[0][0] === 'divi.module.wrapper.render', 'wrong hook registered');
check(registrations[0][1] === 'wpSeedEvents.dynamicEventImagePreview', 'wrong hook namespace');
check(!preview.register(windowObject), 'registration guard failed');
check(registrations.length === 1, 'duplicate registration added a filter');

console.log(`Divi dynamic event image preview: ${assertions}/${assertions} assertions passed.`);