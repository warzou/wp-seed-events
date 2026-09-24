const assert = require('assert');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '..');
const pluginRoot = path.resolve(root, '..', '..', '..', '..', '..');
const metadata = JSON.parse(fs.readFileSync(path.join(root, 'src', 'module.json'), 'utf8'));
const source = fs.readFileSync(path.join(root, 'src', 'index.jsx'), 'utf8');
const optionSource = fs.readFileSync(path.join(root, 'src', 'share-options.js'), 'utf8');
const phpModule = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/class-event-share-module.php'), 'utf8');
const renderer = fs.readFileSync(path.join(pluginRoot, 'includes/public/sharing.php'), 'utf8');
const publicScript = fs.readFileSync(path.join(pluginRoot, 'includes/public/event-share.js'), 'utf8');
const publicCss = fs.readFileSync(path.join(pluginRoot, 'includes/public/event-share.css'), 'utf8');
const bootstrap = fs.readFileSync(path.join(pluginRoot, 'includes/integrations/divi/bootstrap.php'), 'utf8');

let passed = 0;
const test = (name, callback) => { callback(); passed += 1; console.log(`ok ${passed} - ${name}`); };

test('canonical module identity is unique', () => {
  assert.strictEqual(metadata.name, 'wp-seed-events/event-share');
  assert.strictEqual(metadata.title, 'WPSEvents — Partage');
  assert.strictEqual((source.match(/registerModule\(metadata, eventShareModule\)/g) || []).length, 1);
});
test('module uses official loop and page event context', () => {
  assert.deepStrictEqual(metadata.usesContext, ['postId', 'postType', 'queryId']);
  assert.ok(source.includes('__loop_post_id: loopPostIdContext'));
  assert.ok(source.includes('resolveCurrentEventContext'));
  assert.ok(source.includes('context.cacheKey'));
  assert.ok(phpModule.includes('wp_seed_events_divi_get_module_event_context'));
});
test('Event Data API and official renderer are each called once', () => {
  assert.strictEqual((phpModule.match(/wp_seed_events_get_event_data/g) || []).length, 1);
  assert.strictEqual((phpModule.match(/wp_seed_events_render_event_share_menu/g) || []).length, 1);
});
test('new instances have no integrated presentation label and expose three safe display modes', () => {
  const values = metadata.attributes.content.default.innerContent.desktop.value;
  const display = metadata.attributes.content.settings.innerContent.items.displayMode;
  assert.ok(!Object.hasOwn(values, 'show_group_label'));
  assert.ok(!Object.hasOwn(values, 'group_label'));
  assert.ok(!Object.values(metadata.attributes.content.settings.innerContent.items).some((item) => ['show_group_label', 'group_label'].includes(item.subName)));
  assert.ok(!Object.values(metadata.settings.groups).some((group) => group.groupName === 'contentPresentation'));
  assert.strictEqual(values.display_mode, 'text_icon');
	assert.strictEqual(values.action_order, 'share_copy_email');
  assert.deepStrictEqual(Object.keys(display.component.props.options), ['text_icon', 'text', 'icon']);
	assert.deepStrictEqual(Object.keys(metadata.attributes.content.settings.innerContent.items.actionOrder.component.props.options), [
		'share_copy_email', 'share_email_copy', 'copy_share_email', 'copy_email_share', 'email_share_copy', 'email_copy_share',
	]);
  assert.ok(renderer.includes("array( 'text_icon', 'text', 'icon' )"));
});
test('content UI is compact and action oriented', () => {
  const items = metadata.attributes.content.settings.innerContent.items;
  assert.deepStrictEqual(Object.values(items).map((item) => item.subName), [
	'display_mode', 'action_order', 'show_share', 'label', 'show_copy', 'copy_label', 'show_email', 'email_label',
  ]);
	assert.ok(!Object.values(items).some((item) => /target|onglet/i.test(`${item.subName} ${item.label}`)), 'A meaningless target control is exposed.');
  ['show_icon', 'layout'].forEach((legacy) => assert.ok(!Object.values(items).some((item) => item.subName === legacy)));
});
test('legacy presentation attributes remain readable but hidden', () => {
  ['shareStyle', 'summaryStyle', 'buttonStyle', 'linkStyle', 'actionStyle'].forEach((name) => {
    assert.ok(metadata.attributes[name]);
    assert.strictEqual(metadata.attributes[name].settings, undefined);
  });
  ['shareStyle', 'summaryStyle', 'buttonStyle', 'linkStyle'].forEach((name) => {
    assert.ok(!source.includes(`attrName: '${name}'`));
    assert.ok(!phpModule.includes(`'${name}'`));
  });
});
test('Divi owns layout and each action has an independent native button contract', () => {
  assert.deepStrictEqual(Object.keys(metadata.attributes.actionsStyle.settings.decoration), ['layout']);
  ['shareActionStyle', 'copyActionStyle', 'emailActionStyle'].forEach((name) => {
    const attribute = metadata.attributes[name];
    assert.strictEqual(attribute.elementType, 'button');
	assert.deepStrictEqual(Object.keys(attribute.settings.decoration), ['button']);
	assert.strictEqual(attribute.styleProps.spacing.important, true);
	assert.strictEqual(attribute.styleProps.font.important.font.desktop.value.color, true);
	assert.ok(attribute.selector.startsWith('body #page-container .et_pb_section {{baseSelector}}'));
	assert.ok(attribute.customPostTypeSelector.startsWith('body.et-db #page-container #et-boc .et-l {{baseSelector}}'));
    assert.ok(source.includes(`attrName: '${name}'`));
    assert.ok(phpModule.includes(`'${name}'`));
  });
});
test('selected Divi icons feed the REST preview and PHP renderer', () => {
  assert.ok(source.includes("import { normalizeOptions } from './share-options'"));
  ['shareActionStyle', 'copyActionStyle', 'emailActionStyle'].forEach((name) => assert.ok(optionSource.includes(`getButtonIcon(attrs, '${name}')`)));
  ['share_icon', 'copy_icon', 'email_icon'].forEach((name) => {
    assert.ok(optionSource.includes(name));
    assert.ok(phpModule.includes(`'${name}'`));
    assert.ok(renderer.includes(`'${name}'`));
  });
	['share_icon_placement', 'copy_icon_placement', 'email_icon_placement', 'share_icon_on_hover', 'copy_icon_on_hover', 'email_icon_on_hover'].forEach((name) => {
		assert.ok(optionSource.includes(name));
		assert.ok(phpModule.includes(`'${name}'`));
		assert.ok(renderer.includes(`'${name}'`));
	});
	assert.ok(phpModule.includes('IconFontUtils::process_font_icon'));
	assert.ok(renderer.includes("'data-icon' => $has_rendered_icon ? $icon : ''"));
	assert.ok(renderer.includes("$classes[] = 'et_pb_button'"));
	assert.ok(renderer.includes("$classes[] = 'et_pb_custom_button_icon'"));
	assert.ok(renderer.includes("$classes[] = 'has-icon-' . $icon_placement"));
	assert.ok(!renderer.includes('wp-seed-event-share__icon'));
});
test('live labels support plain and Immutable Divi attributes without saved-state priority', () => {
  assert.ok(optionSource.includes("typeof attrs.get === 'function'"));
  assert.ok(optionSource.includes("typeof value.toJS === 'function'"));
  assert.ok(source.includes('const options = normalizeOptions(attrs)'));
  assert.ok(source.includes('optionsKey = JSON.stringify(options)'));
  assert.ok(source.includes('[context.cacheKey, optionsKey]'));
});
test('new instances contain no implicit icon or Unicode fallback', () => {
  ['shareActionStyle', 'copyActionStyle', 'emailActionStyle'].forEach((name) => {
    assert.strictEqual(metadata.attributes[name].default, undefined);
  });
  ['is-fallback-icon', "wp_seed_events_share_icon( $options['share_icon'],", "wp_seed_events_share_icon( $options['copy_icon'],", "wp_seed_events_share_icon( $options['email_icon'],"].forEach((token) => {
    assert.ok(!renderer.includes(token), `Hidden icon fallback remains: ${token}`);
  });
  assert.ok(phpModule.includes("array_key_exists( 'share_icon', $icons )"));
  assert.ok(phpModule.includes("array_key_exists( 'copy_icon', $icons )"));
  assert.ok(phpModule.includes("array_key_exists( 'email_icon', $icons )"));
});
test('shared renderer exposes immediate accessible actions without a dropdown', () => {
  ['data-wp-seed-event-share-native', 'data-wp-seed-event-share-copy', 'role="group"', 'aria-live="polite"', 'mailto:'].forEach((token) => {
    assert.ok(renderer.includes(token), `Missing renderer token: ${token}`);
  });
  ['<details', '<summary', 'wp-seed-event-share__menu', 'alert('].forEach((token) => {
    assert.ok(!renderer.includes(token), `Legacy dropdown residue: ${token}`);
  });
});
test('native share carries UTF-8 title text and canonical URL', () => {
  assert.ok(renderer.includes('data-share-title'));
  assert.ok(renderer.includes('data-share-text'));
  assert.ok(renderer.includes('data-share-url'));
  assert.ok(publicScript.includes('window.navigator.share({ title: title, text: text, url: url })'));
  [renderer, publicScript, source, JSON.stringify(metadata)].forEach((value) => {
    ['Ã', 'Â', 'â€™', 'â€”', 'â€¦'].forEach((token) => assert.ok(!value.includes(token), `Mojibake token remains: ${token}`));
  });
});
test('browser-native share Clipboard and resilient copy fallback are wired once', () => {
  assert.ok(publicScript.includes('window.navigator.share'));
  assert.ok(publicScript.includes('window.navigator.clipboard.writeText'));
  assert.ok(publicScript.includes("document.execCommand('copy')"));
  assert.strictEqual((publicScript.match(/document\.addEventListener\('click'/g) || []).length, 1);
  assert.ok(publicScript.includes('window.wpSeedEventsShareActions'));
});
test('public CSS leaves native Divi button normal hover and focus decoration untouched', () => {
  assert.ok(publicCss.includes('.wp-seed-event-share__action'));
	assert.ok(publicCss.includes('.wp-seed-event-share__action:not(.et_pb_button)'));
	['padding-block:', 'padding-inline:', 'border:', 'border-radius:', 'background:', 'color-mix(', 'transition:'].forEach((token) => {
		assert.ok(!publicCss.includes(token), `WP Seed visual default still fights Divi: ${token}`);
	});
	assert.ok(!/\.wp-seed-event-share__action:focus-visible\s*\{/.test(publicCss));
	assert.ok(publicCss.includes('.wp_seed_events_divi_event_share .wp-seed-event-share__action.et_pb_button:hover'));
	assert.ok(publicCss.includes('background-color: transparent'));
	assert.ok(publicCss.includes('border-color: currentColor'));
	assert.ok(publicCss.includes('padding: 0.3em 1em'));
	assert.ok(!/wp_seed_events_divi_event_share[^}]+:hover[^}]+!important/s.test(publicCss), 'Default bridge would beat explicit Divi hover values.');
	assert.ok(publicCss.includes('.wp-seed-event-share__action.has-no-rendered-icon::before'));
	assert.ok(publicCss.includes('display: none !important'));
	assert.ok(publicCss.includes('content: none !important'));
	assert.ok(publicCss.includes('.wp-seed-event-share__action.has-icon-left::after'));
	assert.ok(publicCss.includes('.wp-seed-event-share__action.has-icon-right::before'));
	assert.ok(publicCss.includes('display: inline-flex !important'));
	assert.ok(publicCss.includes('position: static !important'));
	assert.ok(publicCss.includes('body.et-db #page-container #et-boc .et-l .wp_seed_events_divi_event_share'));
	assert.ok(publicCss.includes('margin-inline: 0 !important'));
	assert.ok(publicCss.includes('margin-inline-end: 0.3em !important'));
	assert.ok(publicCss.includes('margin-inline-start: 0.3em !important'));
	assert.ok(!publicCss.includes('min-block-size'));
	assert.ok(!publicCss.includes('.wp-seed-event-share__action::before,'));
  assert.ok(!publicCss.includes('@media'));
  assert.ok(!/(^|[;{]\s*)height\s*:/m.test(publicCss));
});
test('preview route receives every current option and invalid context remains empty', () => {
	['display_mode', 'action_order', 'show_share', 'show_copy', 'show_email', 'label', 'copy_label', 'email_label'].forEach((option) => {
    assert.ok(source.includes(option));
    assert.ok(phpModule.includes(`'${option}'`));
  });
	['show_group_label', 'group_label'].forEach((legacy) => {
		assert.ok(!source.includes(legacy));
		assert.ok(!phpModule.includes(`'${legacy}'`));
	});
  assert.ok(source.includes('/divi-event-share-preview'));
  assert.ok(source.includes('dangerouslySetInnerHTML'));
  assert.ok(phpModule.includes("if ( 0 === $event_id )"));
  assert.ok(renderer.includes('! $show_share && ! $show_copy && ! $show_email'));
});
test('bootstrap registers dependency and hash-versioned app-window bundle once', () => {
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_register_event_share_module/g) || []).length, 2);
  assert.strictEqual((bootstrap.match(/wp_seed_events_divi_enqueue_event_share_module_assets/g) || []).length, 2);
  assert.ok(bootstrap.includes('wp-seed-events-event-share.js'));
});

assert.strictEqual(passed, 16);
console.log('Divi event share module contract: 16/16 OK');
