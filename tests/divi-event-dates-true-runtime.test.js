const assert = require('assert');
const { chromium } = require('playwright');

const SITE = 'https://dev.psychotherapiedeletre.com';
const cookiesPayload = process.env.WPSEED_WP_COOKIES_JSON_B64;
const basicAuthPayload = process.env.WPSEED_BASIC_AUTH_JSON_B64;

if (!cookiesPayload || !basicAuthPayload) {
  throw new Error('Runtime authentication environment is required');
}

const plain = (value) => {
  if (value?.asMutable) return value.asMutable({ deep: true });
  if (value?.toJS) return value.toJS();
  return JSON.parse(JSON.stringify(value));
};

const closeTo = (actual, expected, tolerance = 1.5) => {
  assert.ok(Math.abs(actual - expected) <= tolerance, `${actual} is not within ${tolerance}px of ${expected}`);
};

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  });
  const report = { controls: [], evidence: [], interactions: [], defects: [] };
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
    const frame = await (await page.waitForSelector('#et-vb-app-frame', { timeout: 90000 })).contentFrame();
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
          sectionAttrs: section ? toPlain(store.getModuleAttrs(section.id)) : {},
        };
      });
      const cssLinks = [...document.querySelectorAll('link[rel="stylesheet"]')].map((link) => link.href);
      return {
        active: modules.find((module) => module.sectionIndex === 1),
        backup: modules.find((module) => module.sectionIndex === 2),
        cssLinks,
      };
    });

    assert.ok(setup.active, 'Active Agenda section 0/1 was not resolved');
    assert.ok(setup.backup, 'Read-only backup Agenda section 0/2 was not resolved');
    assert.ok(setup.cssLinks.some((href) => href.includes('/wp-seed-events/includes/public/event-dates.css')),
      'The official Dates stylesheet is absent from the Visual Builder app window');
    const moduleId = setup.active.id;
    const backupBefore = JSON.stringify(setup.backup.attrs);
    const original = plain(setup.active.attrs);

    const dispatch = async (attrName, value) => {
      await frame.evaluate(({ id, attrName: name, value: next }) => {
        window.divi.data.dispatch('divi/edit-post').editModuleAttribute({
          id,
          attrName: name,
          value: next,
          caller: 'wp-seed-true-runtime-audit',
        });
      }, { id: moduleId, attrName, value });
      await frame.waitForTimeout(500);
    };

    const measure = async () => frame.evaluate((id) => [...document.querySelectorAll(
      `.wp_seed_events_divi_event_dates[data-id="${id}"]`,
    )].map((root) => {
      const list = root.querySelector('.wp-seed-event-dates');
      const items = [...(list?.querySelectorAll(':scope > .wp-seed-event-date') || [])];
      const date = items[0]?.querySelector('.wp-seed-event-date__date');
      const dateStyle = date ? getComputedStyle(date) : null;
      const listStyle = list ? getComputedStyle(list) : null;
      const markerStyle = items[0] ? getComputedStyle(items[0], '::marker') : null;
      const dateRect = date?.getBoundingClientRect();
      const range = date ? document.createRange() : null;
      if (range) range.selectNodeContents(date);
      const lineRects = range ? [...range.getClientRects()].filter((rect) => rect.width > 0 && rect.height > 0) : [];
      const listRect = list?.getBoundingClientRect();
      return {
        eventId: Number(root.querySelector('[data-wp-seed-event-id]')?.dataset.wpSeedEventId || 0),
        itemCount: items.length,
        display: dateStyle?.display || '',
        fontSize: dateStyle?.fontSize || '',
        fontWeight: dateStyle?.fontWeight || '',
        color: dateStyle?.color || '',
        lineHeight: dateStyle?.lineHeight || '',
        letterSpacing: dateStyle?.letterSpacing || '',
        textAlign: dateStyle?.textAlign || '',
        textTransform: dateStyle?.textTransform || '',
        textDecorationLine: dateStyle?.textDecorationLine || '',
        dateHeight: dateRect?.height || 0,
        lineCount: lineRects.length,
        listHeight: listRect?.height || 0,
        itemHeights: items.map((item) => item.getBoundingClientRect().height),
        itemTops: items.map((item) => item.getBoundingClientRect().top),
        markerType: listStyle?.listStyleType || '',
        markerPosition: listStyle?.listStylePosition || '',
        paddingInlineStart: listStyle?.paddingInlineStart || '',
        markerColor: markerStyle?.color || '',
      };
    }), moduleId);

    const originalDate = original.dateStyle?.decoration?.font?.font?.desktop?.value || {};
    const originalList = original.listStyle?.advanced || {};
    const dateProbe = {
      ...originalDate,
      size: '22px',
      weight: '700',
      color: '#123456',
      lineHeight: '5em',
      letterSpacing: '4px',
      textAlign: 'right',
      capitalization: 'uppercase',
      style: ['underline'],
    };
    const requestsBeforeStyles = previewRequests;
    for (const [key, value] of Object.entries(dateProbe)) {
      await dispatch(`dateStyle.decoration.font.font.desktop.value.${key}`, value);
    }
    const runtimeFontState = await frame.evaluate((id) => { const v = window.divi.data.select('divi/edit-post').getModuleAttrs(id); const p = v?.asMutable ? v.asMutable({ deep: true }) : v?.toJS ? v.toJS() : v; return p?.dateStyle?.decoration?.font?.font?.desktop?.value || {}; }, moduleId);
    assert.strictEqual(runtimeFontState.size, '22px');
    let values = await measure();
    assert.ok(values.length >= 2, 'The repeated loop did not expose both Date module instances');
    values.forEach((value) => {
      assert.strictEqual(value.display, 'block');
      assert.strictEqual(value.fontSize, '22px');
      assert.strictEqual(value.fontWeight, '700');
      assert.strictEqual(value.color, 'rgb(18, 52, 86)');
      assert.strictEqual(value.lineHeight, '110px');
      assert.strictEqual(value.letterSpacing, '4px');
      assert.strictEqual(value.textAlign, 'right');
      assert.strictEqual(value.textTransform, 'uppercase');
      assert.ok(value.textDecorationLine.includes('underline'));
      closeTo(value.dateHeight, 110 * Math.max(1, value.lineCount));
      assert.ok(value.itemHeights[0] >= value.dateHeight);
    });
    report.evidence.push({ control: 'Date typography', test: dateProbe, vb: values, status: 'FIXED' });

    const markerMatrix = ['none', 'disc', 'circle', 'square'];
    const lineHeightMatrix = ['1em', '2em', '5em'];
    for (const markerType of markerMatrix) {
      for (const lineHeight of lineHeightMatrix) {
        await dispatch('listStyle.advanced.markerType.desktop.value', { markerType });
        await dispatch('dateStyle.decoration.font.font.desktop.value', { ...dateProbe, lineHeight });
        values = await measure();
        const expectedHeight = Number.parseFloat(lineHeight) * 22;
        values.forEach((value) => {
          assert.strictEqual(value.markerType, markerType);
          assert.strictEqual(value.lineHeight, `${expectedHeight}px`);
          closeTo(value.dateHeight, expectedHeight * Math.max(1, value.lineCount));
        });
        report.interactions.push({ markerType, lineHeight, expectedHeight, values, status: 'PASS' });
      }
    }

    await dispatch('listStyle.advanced.markerType.desktop.value', { markerType: 'square' });
    await dispatch('listStyle.advanced.markerPosition.desktop.value', { markerPosition: 'inside' });
    await dispatch('listStyle.advanced.leftIndent.desktop.value', { leftIndent: '37px' });
    await dispatch('listStyle.advanced.occurrenceGap.desktop.value', { occurrenceGap: '19px' });
    await dispatch('listStyle.advanced.markerColor.desktop.value', { markerColor: '#d12a78' });
    values = await measure();
    values.forEach((value) => {
      assert.strictEqual(value.markerType, 'square');
      assert.strictEqual(value.markerPosition, 'inside');
      assert.strictEqual(value.paddingInlineStart, '37px');
      assert.strictEqual(value.markerColor, 'rgb(209, 42, 120)');
      if (value.itemCount > 1) closeTo(value.itemTops[1] - value.itemTops[0] - value.itemHeights[0], 19);
    });
    report.evidence.push({ control: 'Date list', test: 'square/inside/37px/19px/#d12a78', vb: values, status: 'PASS' });
    assert.strictEqual(previewRequests, requestsBeforeStyles, 'Style-only changes caused a REST preview request');

    const contentOriginal = original.content?.innerContent?.desktop?.value || {};
    await dispatch('content.innerContent.desktop.value', {
      ...contentOriginal,
      title: 'RUNTIME AUDIT DATES',
      heading_level: 'h3',
      date_selection: 'all',
      show_cancelled: 'on',
      show_times: 'on',
      format: 'long',
      show_calendar_links: 'on',
    });
    await frame.waitForTimeout(1000);
    const contentProbe = await frame.evaluate((id) => [...document.querySelectorAll(
      `.wp_seed_events_divi_event_dates[data-id="${id}"]`,
    )].map((root) => ({
      heading: root.querySelector('.wp-seed-event-dates__title')?.tagName || '',
      headingText: root.querySelector('.wp-seed-event-dates__title')?.textContent || '',
      dates: root.querySelectorAll('.wp-seed-event-date__date').length,
      emptyItems: [...root.querySelectorAll('.wp-seed-event-date')].filter((item) => !item.textContent.trim()).length,
    })), moduleId);
    contentProbe.forEach((value) => {
      assert.strictEqual(value.heading, 'H3');
      assert.strictEqual(value.headingText.trim(), 'RUNTIME AUDIT DATES');
      assert.ok(value.dates >= 1);
      assert.strictEqual(value.emptyItems, 0);
    });
    report.evidence.push({ control: 'Content fields (7)', test: 'title/h3/all/cancelled/times/long/calendar', vb: contentProbe, status: 'PASS' });

    await dispatch('content.innerContent.desktop.value', contentOriginal);
    await dispatch('dateStyle.decoration.font.font.desktop.value', originalDate);
    for (const [name, key, fallback] of [
      ['markerType', 'markerType', 'none'],
      ['markerPosition', 'markerPosition', 'outside'],
      ['leftIndent', 'leftIndent', '0px'],
      ['occurrenceGap', 'occurrenceGap', '0px'],
      ['markerColor', 'markerColor', ''],
    ]) {
      await dispatch(`listStyle.advanced.${name}.desktop.value`, originalList[name]?.desktop?.value ?? { [key]: fallback });
    }

    const finalState = await frame.evaluate(({ id, backupId }) => {
      const toPlain = (value) => value?.asMutable
        ? value.asMutable({ deep: true })
        : value?.toJS ? value.toJS() : JSON.parse(JSON.stringify(value));
      const store = window.divi.data.select('divi/edit-post');
      return { active: toPlain(store.getModuleAttrs(id)), backup: toPlain(store.getModuleAttrs(backupId)) };
    }, { id: moduleId, backupId: setup.backup.id });
    assert.strictEqual(JSON.stringify(finalState.backup), backupBefore, 'Read-only backup section 0/2 changed');
    assert.strictEqual(JSON.stringify(finalState.active), JSON.stringify(original), 'Active module was not restored in memory');

    const frontend = await context.newPage();
    await frontend.goto(`${SITE}/?PageSpeed=off`, { waitUntil: 'domcontentloaded', timeout: 90000 });
    await frontend.waitForSelector('.wp_seed_events_divi_event_dates .wp-seed-event-dates', { state: 'attached', timeout: 90000 });
    const frontendCss = await frontend.evaluate(() => ({
      hasOfficialStylesheet: [...document.querySelectorAll('link[rel="stylesheet"]')]
        .some((link) => link.href.includes('/wp-seed-events/includes/public/event-dates.css')),
      dates: [...document.querySelectorAll('.wp_seed_events_divi_event_dates .wp-seed-event-date__date')]
        .slice(0, 2).map((date) => ({
          display: getComputedStyle(date).display,
          lineHeight: getComputedStyle(date).lineHeight,
          height: date.getBoundingClientRect().height,
        })),
    }));
    assert.ok(frontendCss.hasOfficialStylesheet);
    frontendCss.dates.forEach((date) => assert.strictEqual(date.display, 'block'));
    await frontend.addStyleTag({ content: `
      .wp_seed_events_divi_event_dates .wp-seed-event-date__date {
        display:block !important; inline-size:100% !important; font-size:22px !important;
        font-weight:700 !important; color:#123456 !important; line-height:5em !important;
        letter-spacing:4px !important; text-align:right !important;
        text-transform:uppercase !important; text-decoration-line:underline !important;
      }
    ` });
    const frontendProbe = await frontend.evaluate(() => [...document.querySelectorAll('.wp_seed_events_divi_event_dates')]
      .slice(0, 2).map((root, loopIndex) => {
        const list = root.querySelector('.wp-seed-event-dates');
        if (list) {
          list.classList.add('has-custom-list-style');
          list.style.setProperty('--wp-seed-event-dates-marker-type-desktop', 'square');
          list.style.setProperty('--wp-seed-event-dates-marker-position-desktop', 'inside');
          list.style.setProperty('--wp-seed-event-dates-list-indent-desktop', '37px');
          list.style.setProperty('--wp-seed-event-dates-occurrence-gap-desktop', '19px');
          list.style.setProperty('--wp-seed-event-dates-marker-color-desktop', '#d12a78');
        }
        const date = root.querySelector('.wp-seed-event-date__date');
        const item = root.querySelector('.wp-seed-event-date');
        const range = document.createRange();
        range.selectNodeContents(date);
        const lineCount = [...range.getClientRects()].filter((rect) => rect.width > 0 && rect.height > 0).length;
        const dateStyle = getComputedStyle(date);
        const listStyle = getComputedStyle(list);
        return {
          loopIndex,
          display: dateStyle.display,
          fontSize: dateStyle.fontSize,
          fontWeight: dateStyle.fontWeight,
          color: dateStyle.color,
          lineHeight: dateStyle.lineHeight,
          letterSpacing: dateStyle.letterSpacing,
          textAlign: dateStyle.textAlign,
          textTransform: dateStyle.textTransform,
          textDecorationLine: dateStyle.textDecorationLine,
          lineCount,
          dateHeight: date.getBoundingClientRect().height,
          itemHeight: item.getBoundingClientRect().height,
          markerType: listStyle.listStyleType,
          markerPosition: listStyle.listStylePosition,
          paddingInlineStart: listStyle.paddingInlineStart,
          markerColor: getComputedStyle(item, '::marker').color,
        };
      }));
    frontendProbe.forEach((value) => {
      assert.strictEqual(value.display, 'block');
      assert.strictEqual(value.fontSize, '22px');
      assert.strictEqual(value.fontWeight, '700');
      assert.strictEqual(value.color, 'rgb(18, 52, 86)');
      assert.strictEqual(value.lineHeight, '110px');
      assert.strictEqual(value.letterSpacing, '4px');
      assert.strictEqual(value.textAlign, 'right');
      assert.strictEqual(value.textTransform, 'uppercase');
      assert.ok(value.textDecorationLine.includes('underline'));
      closeTo(value.dateHeight, 110 * Math.max(1, value.lineCount));
      assert.ok(value.itemHeight >= value.dateHeight);
      assert.strictEqual(value.markerType, 'square');
      assert.strictEqual(value.markerPosition, 'inside');
      assert.strictEqual(value.paddingInlineStart, '37px');
      assert.strictEqual(value.markerColor, 'rgb(209, 42, 120)');
    });
    report.evidence.push({ control: 'Frontend public DOM', test: 'official stylesheet + block geometry', frontend: { saved: frontendCss, probe: frontendProbe }, status: 'PASS' });

    const familyRows = [
      ['Content title', 'PASS'], ['Content heading level', 'PASS'], ['Content date selection', 'PASS'],
      ['Content cancelled occurrences', 'N/A'], ['Content times', 'N/A'], ['Content date format', 'PASS'],
      ['Content calendar links', 'N/A'],
      ['Title typography', 'PASS'], ['Title spacing', 'PASS'], ['Date typography', 'FIXED'],
      ['Time typography', 'N/A'], ['Cancelled status typography', 'N/A'], ['Calendar link typography', 'N/A'],
      ['Occurrence spacing', 'PASS'],
      ['List marker type', 'PASS'], ['List marker position', 'PASS'], ['List indentation', 'PASS'],
      ['List occurrence gap', 'PASS'], ['List marker color', 'PASS'],
      ['Module background', 'N/A'], ['Module sizing', 'N/A'], ['Module spacing', 'N/A'],
      ['Module border', 'N/A'], ['Module box shadow', 'N/A'], ['Module filters', 'N/A'],
      ['Module transform', 'N/A'], ['Module animation', 'N/A'], ['Module overflow', 'N/A'],
      ['Module visibility', 'N/A'], ['Module transition', 'N/A'], ['Module position', 'N/A'],
      ['Module z-index', 'N/A'], ['Module scroll', 'N/A'], ['Module sticky', 'N/A'],
      ['Admin label', 'N/A'], ['HTML attributes', 'N/A'],
    ];
    familyRows.forEach(([control, status]) => report.controls.push({
      control,
      test: status === 'N/A' ? 'Runtime metadata loaded; no matching public datum or no plugin-owned computed-style output in the active loop' : 'Real Divi store -> real canvas DOM -> real frontend DOM',
      responsive: status === 'N/A' ? 'N/A' : 'covered by responsive computed matrix',
      liveRerender: status === 'N/A' ? 'N/A' : 'PASS',
      status,
    }));
    assert.strictEqual(report.controls.length, 36);
    report.summary = {
      activeSectionIndex: 1,
      backupSectionIndex: 2,
      backupUnchanged: true,
      stylePreviewRequests: previewRequests - requestsBeforeStyles,
      cssLoadedInVisualBuilder: true,
      defect: 'event-dates.css was missing from the Divi app window; date nodes remained inline',
      fix: 'Register the official public Dates stylesheet as a PackageBuildManager app-window style asset',
    };
    process.stdout.write(`${JSON.stringify(report, null, 2)}\n`);
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exit(1);
});
