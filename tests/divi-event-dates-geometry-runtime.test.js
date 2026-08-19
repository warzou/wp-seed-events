const assert = require('assert');
const { chromium } = require('playwright');

const SITE = 'https://dev.psychotherapiedeletre.com';
const cookiesPayload = process.env.WPSEED_WP_COOKIES_JSON_B64;
const basicAuthPayload = process.env.WPSEED_BASIC_AUTH_JSON_B64;

if (!cookiesPayload || !basicAuthPayload) {
  throw new Error('Runtime authentication environment is required');
}

const rect = (value) => ({
  top: value.top,
  bottom: value.bottom,
  height: value.height,
  centerY: value.top + (value.height / 2),
});

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  });
  let previewRequests = 0;

  try {
    const context = await browser.newContext({
      httpCredentials: JSON.parse(Buffer.from(basicAuthPayload, 'base64').toString('utf8')),
      viewport: { width: 1440, height: 1000 },
    });
    await context.addCookies(JSON.parse(Buffer.from(cookiesPayload, 'base64').toString('utf8')).cookies);
    const page = await context.newPage();
    page.on('request', (request) => {
      if (request.url().includes('/wp-json/wp-seed-events/v1/divi/event-dates-preview')) previewRequests += 1;
    });
    await page.goto(`${SITE}/?et_fb=1&PageSpeed=off`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    const iframeElement = await page.waitForSelector('#et-vb-app-frame', { timeout: 90000 });
    const frame = await iframeElement.contentFrame();
    await frame.waitForSelector('.wp_seed_events_divi_event_dates .wp-seed-event-dates', {
      state: 'attached',
      timeout: 90000,
    });
    await frame.waitForTimeout(4000);

    const setup = await frame.evaluate(() => {
      const toPlain = (value) => value?.asMutable
        ? value.asMutable({ deep: true })
        : value?.toJS ? value.toJS() : JSON.parse(JSON.stringify(value));
      const store = window.divi.data.select('divi/edit-post');
      const rootIds = store.getRootModuleIds();
      const ids = [...new Set([...document.querySelectorAll('.wp_seed_events_divi_event_dates')]
        .map((node) => node.dataset.id))];
      const modules = ids.map((id) => {
        const section = store.getAncestorModules(id).find((module) => module.name === 'divi/section');
        return {
          id,
          attrs: toPlain(store.getModuleAttrs(id)),
          sectionId: section?.id || '',
          sectionIndex: section ? rootIds.indexOf(section.id) : -1,
          ancestors: store.getAncestorModules(id).map((ancestor) => ({ id: ancestor.id, name: ancestor.name, attrs: toPlain(store.getModuleAttrs(ancestor.id)) })),
        };
      });
      return {
        active: modules.find((module) => module.sectionIndex === 1),
        backup: modules.find((module) => module.sectionIndex === 2),
      };
    });
    assert.ok(setup.active && setup.backup, 'Active/backup section guard failed');
    const moduleId = setup.active.id;
    const originalDate = setup.active.attrs.dateStyle?.decoration?.font?.font?.desktop?.value || {};
    const backupBefore = JSON.stringify(setup.backup.attrs);
    const requestsBefore = previewRequests;

    const dispatchLineHeight = async (lineHeight) => {
      const value = { ...originalDate };
      if (lineHeight === null) delete value.lineHeight;
      else value.lineHeight = lineHeight;
      await frame.evaluate(({ id, value: next }) => {
        window.divi.data.dispatch('divi/edit-post').editModuleAttribute({
          id,
          attrName: 'dateStyle.decoration.font.font.desktop.value',
          value: next,
          caller: 'wp-seed-dates-geometry-audit',
        });
      }, { id: moduleId, value });
      await frame.waitForTimeout(650);
    };

    const measure = async (label) => frame.evaluate(({ id, label: step }) => {
      const makeRect = (node) => {
        if (!node) return null;
        const value = node.getBoundingClientRect();
        return { top: value.top, bottom: value.bottom, height: value.height, centerY: value.top + (value.height / 2) };
      };
      const styleFields = (node) => {
        if (!node) return null;
        const style = getComputedStyle(node);
        return {
          display: style.display,
          position: style.position,
          height: style.height,
          minHeight: style.minHeight,
          maxHeight: style.maxHeight,
          lineHeight: style.lineHeight,
          fontSize: style.fontSize,
          paddingTop: style.paddingTop,
          paddingBottom: style.paddingBottom,
          marginTop: style.marginTop,
          marginBottom: style.marginBottom,
          flex: style.flex,
          flexGrow: style.flexGrow,
          flexShrink: style.flexShrink,
          flexBasis: style.flexBasis,
          alignSelf: style.alignSelf,
          alignItems: style.alignItems,
          verticalAlign: style.verticalAlign,
          transform: style.transform,
          overflow: style.overflow,
          outline: style.outline,
          border: style.border,
          boxShadow: style.boxShadow,
        };
      };
      const roots = [...document.querySelectorAll(`.wp_seed_events_divi_event_dates[data-id="${id}"]`)];
      return roots.map((module, loopIndex) => {
        const inner = module.querySelector(':scope > .et_pb_module_inner') || module.querySelector('.et_pb_module_inner');
        const section = module.querySelector('.wp-seed-event-section--dates');
        const list = module.querySelector('.wp-seed-event-dates');
        const items = [...module.querySelectorAll('.wp-seed-event-dates > .wp-seed-event-date')];
        const dates = items.map((item) => item.querySelector('.wp-seed-event-date__date'));
        let group = module.parentElement;
        while (group && group !== document.body) {
          const siblings = [...group.querySelectorAll('.et_pb_icon, [class*="et_pb_icon"], svg')]
            .filter((node) => !module.contains(node) && node.getBoundingClientRect().height > 0);
          if (siblings.length) break;
          group = group.parentElement;
        }
        const iconCandidates = group
          ? [...group.querySelectorAll('.et_pb_icon, [class*="et_pb_icon"], svg')]
            .filter((node) => !module.contains(node) && node.getBoundingClientRect().height > 0)
          : [];
        const icon = iconCandidates.sort((a, b) => {
          const dateTop = dates[0]?.getBoundingClientRect().top || 0;
          return Math.abs(a.getBoundingClientRect().top - dateTop) - Math.abs(b.getBoundingClientRect().top - dateTop);
        })[0] || null;
        const ancestors = [];
        let ancestor = group?.parentElement || null;
        for (let depth = 0; ancestor && ancestor !== document.body && depth < 10; depth += 1, ancestor = ancestor.parentElement) {
          ancestors.push({
            depth,
            tag: ancestor.tagName,
            className: typeof ancestor.className === 'string' ? ancestor.className : '',
            dataId: ancestor.dataset?.id || '',
            rect: makeRect(ancestor),
            style: styleFields(ancestor),
          });
        }
        const moduleRect = module.getBoundingClientRect();
        const overlayMatches = [...document.querySelectorAll('body *')].filter((node) => {
          if (node === module || module.contains(node) || node.contains(module)) return false;
          const value = node.getBoundingClientRect();
          return value.width > 0 && value.height > 0
            && Math.abs(value.top - moduleRect.top) <= 2
            && Math.abs(value.bottom - moduleRect.bottom) <= 2
            && Math.abs(value.left - moduleRect.left) <= 2
            && Math.abs(value.right - moduleRect.right) <= 2;
        }).slice(0, 8).map((node) => ({
          tag: node.tagName,
          className: typeof node.className === 'string' ? node.className : '',
          dataId: node.dataset?.id || '',
          rect: makeRect(node),
          style: styleFields(node),
        }));
        const firstDateRect = makeRect(dates[0]);
        const iconRect = makeRect(icon);
        const lastItemRect = makeRect(items[items.length - 1]);
        const listRect = makeRect(list);
        return {
          label: step,
          loopIndex,
          expectedEventId: loopIndex === 0 ? 2414 : 2417,
          itemCount: items.length,
          group: group ? {
            tag: group.tagName,
            className: typeof group.className === 'string' ? group.className : '',
            dataId: group.dataset?.id || '',
            rect: makeRect(group),
            style: styleFields(group),
          } : null,
          icon: icon ? {
            tag: icon.tagName,
            className: typeof icon.className === 'string' ? icon.className : '',
            rect: iconRect,
            style: styleFields(icon),
          } : null,
          module: { rect: makeRect(module), style: styleFields(module) },
          inner: { rect: makeRect(inner), style: styleFields(inner) },
          section: { rect: makeRect(section), style: styleFields(section) },
          list: { rect: listRect, style: styleFields(list) },
          items: items.map((item, itemIndex) => ({
            index: itemIndex,
            rect: makeRect(item),
            style: styleFields(item),
            date: {
              rect: makeRect(dates[itemIndex]),
              style: styleFields(dates[itemIndex]),
            },
          })),
          deltaCenter: firstDateRect && iconRect ? firstDateRect.centerY - iconRect.centerY : null,
          trailingDelta: listRect && lastItemRect ? listRect.bottom - lastItemRect.bottom : null,
          overlayMatches,
          ancestors: step === '5em' || step.startsWith('convergence') ? ancestors : [],
        };
      });
    }, { id: moduleId, label });

    const selectedRoot = frame.locator(`.wp_seed_events_divi_event_dates[data-id="${moduleId}"]`).first();
    await selectedRoot.click({ force: true });
    await frame.waitForTimeout(500);

    const matrix = [];
    const lineHeights = [
      ['default', null],
      ['0.6em', '0.6em'],
      ['1em', '1em'],
      ['1.7em', '1.7em'],
      ['2em', '2em'],
      ['5em', '5em'],
    ];
    for (const [label, value] of lineHeights) {
      await dispatchLineHeight(value);
      const measurements = await measure(label);
      assert.strictEqual(measurements.length, 2);
      assert.strictEqual(measurements[0].itemCount, 1);
      assert.strictEqual(measurements[1].itemCount, 2);
      measurements.forEach((measurement) => {
        measurement.deltas = {
          moduleToInner: measurement.module.rect.height - measurement.inner.rect.height,
          innerToSection: measurement.inner.rect.height - measurement.section.rect.height,
          sectionToList: measurement.section.rect.height - measurement.list.rect.height,
          trailing: measurement.trailingDelta,
          itemToDate: measurement.items.map((item) => item.rect.height - item.date.rect.height),
        };
      });
      matrix.push(...measurements);
    }

    await dispatchLineHeight('0.6em');
    const smallAgain = await measure('live-0.6em');
    await dispatchLineHeight('5em');
    const tallAgain = await measure('live-5em');
    await dispatchLineHeight('1em');
    const normalAgain = await measure('live-1em');
    const convergence = [];
    await dispatchLineHeight('5em');
    convergence.push({ elapsedMs: 650, values: await measure('convergence-5em-650') });
    for (const waitMs of [500, 1000, 2000]) {
      await frame.waitForTimeout(waitMs);
      convergence.push({ elapsedMs: convergence[convergence.length - 1].elapsedMs + waitMs, values: await measure(`convergence-5em-${waitMs}`) });
    }
    assert.strictEqual(previewRequests, requestsBefore, 'Line-height changes caused REST requests');

    await frame.evaluate(({ id, value }) => {
      window.divi.data.dispatch('divi/edit-post').editModuleAttribute({
        id,
        attrName: 'dateStyle.decoration.font.font.desktop.value',
        value,
        caller: 'wp-seed-dates-geometry-audit-restore',
      });
    }, { id: moduleId, value: originalDate });
    await frame.waitForTimeout(650);
    const backupAfter = await frame.evaluate((id) => {
      const value = window.divi.data.select('divi/edit-post').getModuleAttrs(id);
      return JSON.stringify(value?.asMutable ? value.asMutable({ deep: true }) : value?.toJS ? value.toJS() : value);
    }, setup.backup.id);
    assert.strictEqual(backupAfter, backupBefore, 'Read-only backup section changed');

    const frontend = await context.newPage();
    await frontend.goto(`${SITE}/?PageSpeed=off`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await frontend.waitForSelector('.wp_seed_events_divi_event_dates .wp-seed-event-dates', { state: 'attached', timeout: 90000 });
    await frontend.addStyleTag({ content: '.wp_seed_events_divi_event_dates .wp-seed-event-date__date { line-height: var(--wpseed-audit-line-height) !important; }' });
    const frontendMatrix = [];
    for (const lineHeight of ['0.6em', '1em', '1.7em', '5em']) {
      const values = await frontend.evaluate((nextLineHeight) => {
        const makeRect = (node) => {
          const value = node.getBoundingClientRect();
          return { top: value.top, bottom: value.bottom, height: value.height, centerY: value.top + (value.height / 2) };
        };
        document.documentElement.style.setProperty('--wpseed-audit-line-height', nextLineHeight);
        return [...document.querySelectorAll('.wp_seed_events_divi_event_dates')].slice(0, 2).map((module, loopIndex) => {
          const inner = module.querySelector('.et_pb_module_inner');
          const section = module.querySelector('.wp-seed-event-section--dates');
          const list = module.querySelector('.wp-seed-event-dates');
          const items = [...module.querySelectorAll('.wp-seed-event-dates > .wp-seed-event-date')];
          const date = items[0].querySelector('.wp-seed-event-date__date');
          let group = module.parentElement;
          while (group && group !== document.body && ![...group.querySelectorAll('.et_pb_icon, [class*="et_pb_icon"], svg')].some((node) => !module.contains(node))) group = group.parentElement;
          const icon = group ? [...group.querySelectorAll('.et_pb_icon, [class*="et_pb_icon"], svg')].find((node) => !module.contains(node) && node.getBoundingClientRect().height > 0) : null;
          const last = items[items.length - 1];
          const dateRect = makeRect(date);
          const iconRect = icon ? makeRect(icon) : null;
          return {
            lineHeight: nextLineHeight,
            expectedEventId: loopIndex === 0 ? 2414 : 2417,
            itemCount: items.length,
            computedLineHeight: getComputedStyle(date).lineHeight,
            fontSize: getComputedStyle(date).fontSize,
            group: makeRect(group), icon: iconRect, module: makeRect(module), inner: makeRect(inner),
            section: makeRect(section), list: makeRect(list),
            items: items.map((item) => ({ rect: makeRect(item), date: makeRect(item.querySelector('.wp-seed-event-date__date')) })),
            deltaCenter: iconRect ? dateRect.centerY - iconRect.centerY : null,
            trailingDelta: makeRect(list).bottom - makeRect(last).bottom,
          };
        });
      }, lineHeight);
      frontendMatrix.push(...values);
    }
    for (const frontendValue of frontendMatrix) {
      const vbValue = matrix.find((value) => value.label === frontendValue.lineHeight && value.expectedEventId === frontendValue.expectedEventId);
      assert.ok(vbValue, `Missing VB pair for ${frontendValue.lineHeight}/${frontendValue.expectedEventId}`);
      assert.strictEqual(vbValue.items[0].date.style.lineHeight, frontendValue.computedLineHeight);
      assert.strictEqual(vbValue.items.length, frontendValue.items.length);
      assert.ok(Math.abs(vbValue.trailingDelta) <= 0.75);
      assert.ok(Math.abs(frontendValue.trailingDelta) <= 0.75);
      vbValue.items.forEach((item, index) => {
        assert.ok(Math.abs(item.date.rect.height - frontendValue.items[index].date.height) <= 0.75);
        assert.ok(Math.abs(item.rect.height - item.date.rect.height) <= 0.75);
      });
      if (frontendValue.expectedEventId === 2414) {
        assert.ok(Math.abs(vbValue.inner.rect.centerY - vbValue.items[0].date.rect.centerY) <= 0.75);
      }
    }
    const fixedAncestorHeights = setup.active.ancestors
      .map((ancestor) => ancestor.attrs?.module?.decoration?.sizing?.desktop?.value?.height)
      .filter(Boolean);
    assert.ok(fixedAncestorHeights.includes('520px'));
    assert.ok(fixedAncestorHeights.includes('400px'));
    const singleSmall = matrix.find((value) => value.label === '0.6em' && value.expectedEventId === 2414);
    assert.ok(singleSmall.module.rect.height > singleSmall.inner.rect.height);
    assert.strictEqual(singleSmall.group.style.display, 'flex');
    assert.strictEqual(singleSmall.icon.style.alignSelf, 'center');
    process.stdout.write(`${JSON.stringify({
      activeSectionIndex: 1,
      backupSectionIndex: 2,
      moduleId,
      activeAncestorAttrs: setup.active.ancestors,
      previewRequestsDuringStyles: previewRequests - requestsBefore,
      matrix,
      live: { smallAgain, tallAgain, normalAgain },
      convergence,
      frontendMatrix,
      classification: {
        pluginInternalGeometry: 'MATCH',
        outerConstraint: 'Divi rows fixed at 520px and 400px; flex items shrink while inner content remains overflow-visible',
        selectionBounds: 'Divi selection chrome follows the compressed module wrapper; no plugin overlay, outline, border, or shadow exists',
      },
    }, null, 2)}\n`);
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exit(1);
});
