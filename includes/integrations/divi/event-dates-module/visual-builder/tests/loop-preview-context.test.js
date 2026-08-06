'use strict';

const assert = require('assert');
const path = require('path');

const context = require(path.resolve(__dirname, '../src/loop-preview-context.js'));

let assertions = 0;
const check = (condition, message) => {
  assertions += 1;
  assert.ok(condition, message);
};
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
const element = (type, props = {}) => ({ $$typeof: 'test.element', type, props });
const token = '$variable({"type":"content","value":{"name":"loop_post_id","settings":{}}})$';
const moduleId = 'dates-module';
const tree = (savedLoopAttribute = true, id = moduleId) => element('EventDatesPreview', {
  id,
  attrs: savedLoopAttribute
    ? { __loop_post_id: token, untouched: 'yes' }
    : { untouched: 'yes' },
  children: element('style-component', {
    moduleId: id,
    moduleAttrs: savedLoopAttribute ? { __loop_post_id: token } : {},
    runtimeModuleAttrs: savedLoopAttribute ? { __loop_post_id: token } : {},
  }),
});
const queryData = {
  'parent-loop-a': {
    queryItems: [
      { postId: 2414, post_type: 'wp_seed_event' },
      { postId: 2417, post_type: 'wp_seed_event' },
    ],
  },
  'parent-loop-b': {
    queryItems: [
      { post_id: 3101, postType: 'wp_seed_event' },
      { id: 3102, post_type: 'wp_seed_event' },
    ],
  },
  'parent-loop-reordered': {
    queryItems: [
      { postId: 2417, post_type: 'wp_seed_event' },
      { postId: 2414, post_type: 'wp_seed_event' },
    ],
  },
  'parent-loop-duplicate': {
    queryItems: [
      { postId: 4101, post_type: 'wp_seed_event' },
      { postId: 4102, post_type: 'wp_seed_event' },
    ],
  },
  'parent-loop-mixed': {
    queryItems: [{ postId: 5101, post_type: 'page' }],
  },
};
const data = {
  select: (store) => {
    check(store === 'divi/edit-post', 'unexpected Divi store');
    return { getModuleLoopData: (ownerId) => queryData[ownerId] ?? { queryItems: [] } };
  },
};

const filter = context.createEventDatesPreviewFilter({ React, data });

const loopContext = (parentId, loopIndex, overrides = {}) => ({
  id: moduleId,
  moduleId: null,
  parentId,
  name: 'wp-seed-events/event-dates',
  isLooped: null,
  loopIndex,
  ...overrides,
});
const resolvedId = (result, key = 'attrs') => result.props[key]?.__loop_post_id
  ?? result.props.children.props[key]?.__loop_post_id;

const first = filter(tree(), loopContext('parent-loop-a', 0));
const second = filter(tree(), loopContext('parent-loop-a', 1));
check(resolvedId(first) === '2414', 'loop index 0 did not resolve from the parent store');
check(resolvedId(second) === '2417', 'loop index 1 reused the previous event');
check(first.props.children.props.moduleAttrs.__loop_post_id === '2414', 'moduleAttrs were not updated');
check(first.props.children.props.runtimeModuleAttrs.__loop_post_id === '2414', 'runtimeModuleAttrs were not updated');
check(first.props.attrs.untouched === 'yes', 'unrelated attributes changed');

const otherFirst = filter(tree(false), loopContext('parent-loop-b', 0));
const otherSecond = filter(tree(false), loopContext('parent-loop-b', 1));
check(resolvedId(otherFirst) === '3101', 'post_id property was not resolved');
check(resolvedId(otherSecond) === '3102', 'id property was not resolved');
check(resolvedId(first) === '2414', 'second loop leaked into the first loop');

const reorderedFirst = filter(tree(false), loopContext('parent-loop-reordered', 0));
const reorderedSecond = filter(tree(false), loopContext('parent-loop-reordered', 1));
check(resolvedId(reorderedFirst) === '2417', 'reordered index 0 used stale cache data');
check(resolvedId(reorderedSecond) === '2414', 'reordered index 1 used stale cache data');

const duplicateFirst = filter(tree(false), loopContext('parent-loop-duplicate', 0));
const duplicateSecond = filter(tree(false), loopContext('parent-loop-duplicate', 1));
check(resolvedId(duplicateFirst) === '4101', 'duplicated loop index 0 failed');
check(resolvedId(duplicateSecond) === '4102', 'duplicated loop index 1 failed');

const reinserted = filter(
  tree(false, 'reinserted-dates-module'),
  loopContext('parent-loop-a', 1, { id: 'reinserted-dates-module' }),
);
check(resolvedId(reinserted) === '2417', 'reinserted module did not resolve its parent item');
check(context.getEventLoopPostId(data, 'parent-loop-a', 0) === 2414, 'direct parent/index resolution failed');
check(context.getEventLoopPostId(data, 'parent-loop-a', 1) === 2417, 'direct second-item resolution failed');
check(context.getEventLoopPostId(data, 'parent-loop-a', 9) === 0, 'missing item resolved');
check(context.getEventLoopPostId(data, '', 0) === 0, 'missing parent resolved');
check(context.getEventLoopPostId(data, 'parent-loop-mixed', 0) === 0, 'non-event item resolved');
check(context.getEventLoopPostId(data, 'parent-loop-a', -1) === 0, 'negative loop index resolved');

const outside = tree();
check(filter(outside, loopContext('', 0)) === outside, 'missing parent changed the module');
check(filter(outside, loopContext('parent-loop-a', null)) === outside, 'missing loop index changed the module');
check(filter(outside, loopContext('parent-loop-a', 0, { name: 'divi/text' })) === outside, 'other module changed');
check(filter(outside, loopContext('parent-loop-mixed', 0)) === outside, 'non-event loop item changed the module');

const storeContext = context.getEventLoopItemContext(data, 'parent-loop-a', 0);
check(storeContext.storeAvailable === true, 'Divi loop store was not reported available');
check(storeContext.item.postId === 2414, 'resolved store item differs');
check(storeContext.eventId === 2414, 'resolved store event ID differs');


check(context.getDiviLoopPostId({ __loop_post_id: '44' }) === 44, 'plain loop ID failed');
check(context.getDiviLoopPostId({ __loop_post_id: token }) === 0, 'dynamic token became an ID');

['none', 'disc', 'circle', 'square'].forEach((markerType) => {
  check(
    context.getDiviDesktopFieldValue(
      { desktop: { value: { markerType } } },
      'markerType',
      'disc',
    ) === markerType,
    `nested ${markerType} marker was not resolved`,
  );
  check(
    context.getDiviDesktopFieldValue(
      { desktop: { value: markerType } },
      'markerType',
      'disc',
    ) === markerType,
    `legacy ${markerType} marker was not resolved`,
  );
});
check(
  context.getDiviDesktopFieldValue(
    { desktop: { value: { leftIndent: '3rem' } } },
    'leftIndent',
    '2.5em',
  ) === '3rem',
  'nested explicit indentation was not resolved',
);

console.log('Divi event dates parent loop context: ' + assertions + '/' + assertions + ' assertions passed.');