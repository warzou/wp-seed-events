const assert = require('assert');
const { getButtonIcon, getButtonIconSetting, normalizeOptions } = require('../src/share-options');

const values = {
  display_mode: 'text_icon',
	action_order: 'email_copy_share',
  label: 'TEST PARTAGE',
  show_share: 'on',
  copy_label: 'TEST COPIE',
  show_copy: 'on',
  email_label: 'TEST EMAIL',
  show_email: 'on',
};
const icons = {
  shareActionStyle: { decoration: { button: { desktop: { value: { icon: { enable: 'on', settings: { unicode: '&#xe001;' }, placement: 'left', onHover: 'off' } } } } } },
  copyActionStyle: { decoration: { button: { desktop: { value: { icon: { enable: 'off', settings: { unicode: '&#xe002;' } } } } } } },
  emailActionStyle: { decoration: { button: { desktop: { value: { icon: { enable: 'on', settings: { unicode: '&#xe003;' } } } } } } },
};
const plainAttrs = {
  content: { innerContent: { desktop: { value: values } } },
  ...icons,
};
const immutableValue = (value) => ({ toJS: () => value });
const immutableAttrs = { get: (key) => immutableValue(plainAttrs[key]) };

assert.deepStrictEqual(normalizeOptions(plainAttrs), {
  display_mode: 'text_icon',
	action_order: 'email_copy_share',
  label: 'TEST PARTAGE',
  show_share: 'on',
  share_icon: '&#xe001;',
  share_icon_placement: 'left',
  share_icon_on_hover: 'off',
  show_copy: 'on',
  copy_label: 'TEST COPIE',
  copy_icon: '',
  copy_icon_placement: 'right',
  copy_icon_on_hover: 'on',
  show_email: 'on',
  email_label: 'TEST EMAIL',
  email_icon: '&#xe003;',
  email_icon_placement: 'right',
  email_icon_on_hover: 'on',
});
assert.deepStrictEqual(normalizeOptions(immutableAttrs), normalizeOptions(plainAttrs));
assert.strictEqual(getButtonIcon(plainAttrs, 'copyActionStyle'), '', 'Icon OFF must beat its stored Unicode value.');
assert.strictEqual(getButtonIconSetting(plainAttrs, 'shareActionStyle', 'placement', 'right'), 'left');

const liveValues = { ...values, label: 'LIVE PARTAGE', copy_label: 'LIVE COPIE', email_label: 'LIVE EMAIL' };
const liveAttrs = { ...plainAttrs, content: { innerContent: { desktop: { value: liveValues } } } };
assert.deepStrictEqual(
  [normalizeOptions(liveAttrs).label, normalizeOptions(liveAttrs).copy_label, normalizeOptions(liveAttrs).email_label],
  ['LIVE PARTAGE', 'LIVE COPIE', 'LIVE EMAIL'],
  'Live attributes must win without a saved-state cache.',
);

const untouched = normalizeOptions({ content: { innerContent: { desktop: { value: {} } } } });
assert.strictEqual(untouched.share_icon, '');
assert.strictEqual(untouched.copy_icon, '');
assert.strictEqual(untouched.email_icon, '');
assert.strictEqual(untouched.share_icon_placement, 'right');
assert.strictEqual(untouched.share_icon_on_hover, 'on');
assert.strictEqual(untouched.action_order, 'share_copy_email');
assert.strictEqual(normalizeOptions({ content: { innerContent: { desktop: { value: { action_order: 'share_share_email' } } } } }).action_order, 'share_copy_email');

console.log('Divi event share live options: 12/12 PASS');
