const assert = require('assert');
const { chromium } = require('playwright');

const SITE = 'https://dev.psychotherapiedeletre.com';
const EVENT_URL = process.env.WPSEED_VISUALS_EVENT_URL
  || `${SITE}/rencontre/journee-decouverte-psychotherapie-etre-2026/`;
const basicAuthPayload = process.env.WPSEED_BASIC_AUTH_JSON_B64;
const auditOnly = process.env.WPSEED_AUDIT_ONLY === '1';

if (!basicAuthPayload) {
  throw new Error('Runtime Basic Auth environment is required');
}

(async () => {
  const browser = await chromium.launch({
    headless: true,
    executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe',
  });

  try {
    const context = await browser.newContext({
      httpCredentials: JSON.parse(Buffer.from(basicAuthPayload, 'base64').toString('utf8')),
      viewport: { width: 1440, height: 1000 },
    });
    const page = await context.newPage();
    const consoleErrors = [];
    page.on('console', (message) => {
      if (message.type() === 'error') consoleErrors.push(message.text());
    });
    page.on('pageerror', (error) => consoleErrors.push(error.message));

    await page.goto(`${EVENT_URL}?PageSpeed=off&wpseed_visuals_audit=${Date.now()}`, {
      waitUntil: 'networkidle',
      timeout: 90000,
    });
    await page.waitForSelector('.wp_seed_events_divi_event_visuals', {
      state: 'attached',
      timeout: 90000,
    });

    if (process.env.WPSEED_INITIALIZE_LIGHTBOX === '1') {
      await page.evaluate(() => {
        if (typeof window.et_pb_image_lightbox_init === 'function' && typeof window.jQuery === 'function') {
          window.et_pb_image_lightbox_init(
            window.jQuery('.wp_seed_events_divi_event_visuals .et_pb_lightbox_image'),
          );
        }
      });
      await page.waitForTimeout(1000);
    }

    const report = await page.evaluate(() => {
      const rect = (node) => {
        const value = node.getBoundingClientRect();
        return {
          top: value.top,
          left: value.left,
          right: value.right,
          bottom: value.bottom,
          width: value.width,
          height: value.height,
        };
      };

      return ({
      url: window.location.href,
      lightbox: {
        initializer: typeof window.et_pb_image_lightbox_init,
        initializerSource: typeof window.et_pb_image_lightbox_init === 'function'
          ? String(window.et_pb_image_lightbox_init).slice(0, 500)
          : '',
        magnificPopup: typeof window.jQuery?.fn?.magnificPopup,
      },
      scripts: [...document.scripts]
        .map((script) => script.src)
        .filter(Boolean)
        .filter((src) => /divi|builder|script-library|wp-seed-events/i.test(src)),
      styles: [...document.querySelectorAll('link[rel="stylesheet"]')]
        .map((link) => link.href)
        .filter((href) => /magnific|divi|builder|wp-seed-events/i.test(href)),
      modules: [...document.querySelectorAll('.wp_seed_events_divi_event_visuals')].map((module, moduleIndex) => {
        const inner = module.querySelector('.et_pb_module_inner');
        const section = module.querySelector('.wp-seed-event-visuals');
        const list = module.querySelector('.wp-seed-event-visuals__list');
        const column = module.closest('.et_pb_column');
        return {
          moduleIndex,
          moduleId: module.dataset.id || '',
          module: rect(module),
          column: column ? rect(column) : null,
          inner: inner ? rect(inner) : null,
          section: section ? rect(section) : null,
          list: list ? {
            rect: rect(list),
            style: {
              display: getComputedStyle(list).display,
              flexDirection: getComputedStyle(list).flexDirection,
              flexWrap: getComputedStyle(list).flexWrap,
              gap: getComputedStyle(list).gap,
              width: getComputedStyle(list).width,
            },
          } : null,
          items: [...module.querySelectorAll('.wp-seed-event-visuals__item')].map((item) => ({
            rect: rect(item),
            style: {
              display: getComputedStyle(item).display,
              width: getComputedStyle(item).width,
              maxWidth: getComputedStyle(item).maxWidth,
              minWidth: getComputedStyle(item).minWidth,
              flex: getComputedStyle(item).flex,
              flexBasis: getComputedStyle(item).flexBasis,
              flexGrow: getComputedStyle(item).flexGrow,
              flexShrink: getComputedStyle(item).flexShrink,
            },
          })),
          links: [...module.querySelectorAll('.wp-seed-event-visuals__image-link')].map((link) => ({
            classes: link.className,
            rect: rect(link),
            hasMagnificData: Boolean(window.jQuery?.data(link, 'magnificPopup')),
          })),
          images: [...module.querySelectorAll('.wp-seed-event-visuals__image')].map((image) => ({
            rect: rect(image),
            naturalWidth: image.naturalWidth,
            naturalHeight: image.naturalHeight,
            currentSrc: image.currentSrc,
            style: {
              display: getComputedStyle(image).display,
              width: getComputedStyle(image).width,
              maxWidth: getComputedStyle(image).maxWidth,
              minWidth: getComputedStyle(image).minWidth,
              height: getComputedStyle(image).height,
              objectFit: getComputedStyle(image).objectFit,
            },
          })),
        };
      }),
      });
    });

    const multiModuleIndex = report.modules.findIndex((module) => module.items.length >= 3);
    if (multiModuleIndex >= 0) {
      await page.evaluate((moduleIndex) => {
        const modules = document.querySelectorAll('.wp_seed_events_divi_event_visuals');
        modules[moduleIndex]?.setAttribute('data-wpseed-native-sizing-fixture', '1');
      }, multiModuleIndex);
      await page.addStyleTag({
        content: `
          .wp_seed_events_divi_event_visuals[data-wpseed-native-sizing-fixture="1"] .wp-seed-event-visuals__item {
            inline-size: 300px !important;
            max-inline-size: 100% !important;
          }
          .wp_seed_events_divi_event_visuals[data-wpseed-native-sizing-fixture="1"] .wp-seed-event-visuals__image {
            inline-size: 100% !important;
            max-inline-size: 100% !important;
            block-size: auto !important;
          }
        `,
      });
      await page.waitForTimeout(100);

      report.nativeSizingFixture = await page.evaluate((moduleIndex) => {
        const module = document.querySelectorAll('.wp_seed_events_divi_event_visuals')[moduleIndex];
        const list = module.querySelector('.wp-seed-event-visuals__list');
        const items = [...module.querySelectorAll('.wp-seed-event-visuals__item')];
        const toRect = (node) => {
          const value = node.getBoundingClientRect();
          return { top: value.top, left: value.left, width: value.width, height: value.height };
        };
        return {
          moduleIndex,
          list: toRect(list),
          gap: getComputedStyle(list).gap,
          items: items.map((item) => ({
            rect: toRect(item),
            width: getComputedStyle(item).width,
            flexBasis: getComputedStyle(item).flexBasis,
          })),
        };
      }, multiModuleIndex);

      await page.setViewportSize({ width: 390, height: 844 });
      await page.waitForTimeout(100);
      report.mobileSizingFixture = await page.evaluate((moduleIndex) => {
        const module = document.querySelectorAll('.wp_seed_events_divi_event_visuals')[moduleIndex];
        const list = module.querySelector('.wp-seed-event-visuals__list');
        const items = [...module.querySelectorAll('.wp-seed-event-visuals__item')];
        const toRect = (node) => {
          const value = node.getBoundingClientRect();
          return { top: value.top, left: value.left, width: value.width, height: value.height };
        };
        return {
          list: toRect(list),
          wrap: getComputedStyle(list).flexWrap,
          items: items.map(toRect),
        };
      }, multiModuleIndex);
      await page.setViewportSize({ width: 1440, height: 1000 });
      await page.waitForTimeout(100);
    }

    const lightboxModule = multiModuleIndex >= 0
      ? page.locator('.wp_seed_events_divi_event_visuals').nth(multiModuleIndex)
      : page.locator('.wp_seed_events_divi_event_visuals').first();
    const lightboxLink = lightboxModule.locator(
      '.wp-seed-event-visuals__image-link.et_pb_lightbox_image',
    ).first();
    if (await lightboxLink.count()) {
      await lightboxLink.click();
      const popup = page.locator('.mfp-wrap');
      const popupOpened = await popup.isVisible().catch(() => false);
      report.lightbox.opened = popupOpened;
      report.lightbox.previous = await page.locator('.mfp-arrow-left').isVisible().catch(() => false);
      report.lightbox.next = await page.locator('.mfp-arrow-right').isVisible().catch(() => false);
      if (popupOpened) {
        await page.keyboard.press('Escape');
        await page.waitForTimeout(600);
        report.lightbox.closedWithEscape = !(await popup.isVisible().catch(() => false));
      } else {
        report.lightbox.closedWithEscape = false;
      }
    }

    report.consoleErrors = consoleErrors;
    process.stdout.write(`${JSON.stringify(report, null, 2)}\n`);

    if (!auditOnly) {
      assert.strictEqual(report.lightbox.initializer, 'function');
      assert.strictEqual(report.lightbox.magnificPopup, 'function');
      assert.ok(report.modules.length >= 2, 'Both real Visuals instances are required');
      assert.ok(report.modules.some((module) => module.items.length >= 2), 'A multi-image instance is required');
      report.modules.forEach((module) => {
        module.items.forEach((item) => {
          assert.notStrictEqual(item.style.flexBasis, '100%', 'A visual item still forces a 100% flex basis');
        });
        module.links.forEach((link) => {
          assert.ok(link.classes.includes('et_pb_lightbox_image'));
          assert.ok(link.hasMagnificData, 'Divi native lightbox was not initialized');
        });
      });
      assert.ok(report.nativeSizingFixture, 'The native sizing target fixture was not applied');
      assert.ok(
        report.nativeSizingFixture.items.slice(0, 3)
          .every((item) => Math.abs(item.rect.top - report.nativeSizingFixture.items[0].rect.top) < 1),
        'Three explicitly sized visual items do not share one row',
      );
      assert.ok(
        new Set(report.mobileSizingFixture.items.slice(0, 3).map((item) => Math.round(item.top))).size > 1,
        'Visual items do not wrap in the narrow viewport',
      );
      assert.strictEqual(report.lightbox.opened, true, 'Divi native lightbox did not open');
      assert.strictEqual(report.lightbox.closedWithEscape, true, 'Divi native lightbox did not close with Escape');
      assert.deepStrictEqual(consoleErrors, []);
    }
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error.stack || error.message || error);
  process.exit(1);
});
