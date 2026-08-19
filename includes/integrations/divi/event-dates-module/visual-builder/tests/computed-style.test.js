'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const pluginRoot = path.resolve(__dirname, '..', '..', '..', '..', '..', '..');
const stylesheet = fs.readFileSync(path.join(pluginRoot, 'includes', 'public', 'event-dates.css'), 'utf8');
const { previewListStyleCss, previewListStyleScope } = require(path.join(pluginRoot, 'includes', 'integrations', 'divi', 'event-dates-module', 'visual-builder', 'src', 'divi-style-values.js'));
const executablePath = process.env.WPSEED_BROWSER_PATH
  || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

const moduleMarkup = (id, variables) => `
  <section id="${id}" class="wp-seed-event-section wp-seed-event-section--dates native-matrix">
    <h2 class="wp-seed-event-dates__title">Dates</h2>
    <ul class="wp-seed-event-dates has-custom-list-style" style="${variables}">
      <li class="wp-seed-event-date">
        <time class="wp-seed-event-date__date">10/10/2026</time>
        <span class="wp-seed-event-date__status">Annulee</span>
        <span class="wp-seed-event-date__time">10:00</span>
        <a class="wp-seed-event-calendar-link" href="#">Calendrier</a>
      </li>
      <li class="wp-seed-event-date">
        <time class="wp-seed-event-date__date">03/11/2026</time>
      </li>
    </ul>
  </section>`;

const variables = [
  '--wp-seed-event-dates-marker-type-desktop:disc',
  '--wp-seed-event-dates-marker-position-desktop:outside',
  '--wp-seed-event-dates-list-indent-desktop:40px',
  '--wp-seed-event-dates-occurrence-gap-desktop:11px',
  '--wp-seed-event-dates-marker-color-desktop:#aa0000',
  '--wp-seed-event-dates-marker-type-tablet:circle',
  '--wp-seed-event-dates-marker-position-tablet:inside',
  '--wp-seed-event-dates-list-indent-tablet:30px',
  '--wp-seed-event-dates-occurrence-gap-tablet:7px',
  '--wp-seed-event-dates-marker-color-tablet:#008000',
  '--wp-seed-event-dates-marker-type-phone:square',
  '--wp-seed-event-dates-marker-position-phone:outside',
  '--wp-seed-event-dates-list-indent-phone:20px',
  '--wp-seed-event-dates-occurrence-gap-phone:3px',
  '--wp-seed-event-dates-marker-color-phone:#0000ff',
].join(';');
const noneVariables = variables
  .replace(/marker-type-(desktop|tablet|phone):(disc|circle|square)/g, 'marker-type-$1:none')
  .replace(/list-indent-(desktop|tablet|phone):\d+px/g, 'list-indent-$1:0px');
const markerTypes = ['none', 'disc', 'circle', 'square'];
const lineHeights = ['default', '1em', '2em', '5em'];
const matrixStyles = (markerType) => Object.fromEntries(['desktop', 'tablet', 'phone'].map((breakpoint) => [
  breakpoint,
  { markerType, markerPosition: 'outside', leftIndent: markerType === 'none' ? '0px' : '32px', occurrenceGap: '6px', markerColor: '#654321' },
]));
const matrixVariables = (styles) => Object.entries(styles).flatMap(([breakpoint, values]) => [
  `--wp-seed-event-dates-marker-type-${breakpoint}:${values.markerType}`,
  `--wp-seed-event-dates-marker-position-${breakpoint}:${values.markerPosition}`,
  `--wp-seed-event-dates-list-indent-${breakpoint}:${values.leftIndent}`,
  `--wp-seed-event-dates-occurrence-gap-${breakpoint}:${values.occurrenceGap}`,
  `--wp-seed-event-dates-marker-color-${breakpoint}:${values.markerColor}`,
]).join(';');
const matrixList = (count) => Array.from({ length: count }, (_, index) => `
  <li class="wp-seed-event-date"><time class="wp-seed-event-date__date">Date ${index + 1}</time></li>`).join('');
const matrixMarkup = markerTypes.flatMap((markerType) => lineHeights.flatMap((lineHeight) => [1, 2].flatMap((count) => {
  const styles = matrixStyles(markerType);
  const scope = previewListStyleScope(styles);
  const caseName = `${markerType}-${lineHeight}-${count}`;
  const typography = lineHeight === 'default' ? '' : `#frontend-${caseName} .wp-seed-event-date__date, #preview-${caseName} .wp-seed-event-date__date { line-height:${lineHeight}; }`;
  const list = matrixList(count);
  return `
    <style>${typography}</style>
    <section id="frontend-${caseName}" class="wp-seed-event-section--dates">
      <ul class="wp-seed-event-dates has-custom-list-style" style="${matrixVariables(styles)}">${list}</ul>
    </section>
    <section id="preview-${caseName}" class="wp-seed-event-section--dates">
      <style data-wp-seed-event-dates-preview-style="${scope}">${previewListStyleCss(scope)}</style>
      <ul class="wp-seed-event-dates has-custom-list-style ${scope}" style="${matrixVariables(styles)}">${list}</ul>
    </section>`;
}))).join('');
(async () => {
  const browser = await chromium.launch({ executablePath, headless: true });
  try {
    const page = await browser.newPage({ viewport: { width: 1200, height: 800 } });
    await page.setContent(`<!doctype html><html><head><style>
      ${stylesheet}
      .entry-content ul { padding: 0 0 23px 1em; }
      .entry-content li::before { content: "global-marker"; }
      .entry-content time, .entry-content a, .entry-content span { display: inline; text-align: left; }
      .style-matrix .wp-seed-event-dates__title,
      .style-matrix .wp-seed-event-date__date,
      .style-matrix .wp-seed-event-date__time,
      .style-matrix .wp-seed-event-date__status,
      .style-matrix .wp-seed-event-calendar-link {
        font-family: Arial, sans-serif;
        font-size: 22px;
        font-weight: 700;
        font-stretch: 125%;
        font-variation-settings: "wght" 650, "wdth" 110;
        color: rgb(12, 34, 56);
        line-height: 34px;
        letter-spacing: 2px;
        text-align: right;
        text-transform: uppercase;
        font-style: italic;
        text-decoration-line: underline;
        text-decoration-color: rgb(90, 80, 70);
        text-decoration-style: wavy;
        text-decoration-thickness: 3px;
        text-underline-offset: 5px;
        direction: rtl;
        hyphens: auto;
      }
      @media (max-width: 980px) {
        .style-matrix .wp-seed-event-dates__title,
        .style-matrix .wp-seed-event-date__date,
        .style-matrix .wp-seed-event-date__time,
        .style-matrix .wp-seed-event-date__status,
        .style-matrix .wp-seed-event-calendar-link { font-size: 18px; line-height: 28px; text-align: center; }
      }
      @media (max-width: 767px) {
        .style-matrix .wp-seed-event-dates__title,
        .style-matrix .wp-seed-event-date__date,
        .style-matrix .wp-seed-event-date__time,
        .style-matrix .wp-seed-event-date__status,
        .style-matrix .wp-seed-event-calendar-link { font-size: 15px; line-height: 22px; text-align: left; }
      }
      .style-matrix .wp-seed-event-dates__title {
        margin-block-end: 17px;
        padding-block-start: 5px;
      }
      .style-matrix .wp-seed-event-date {
        padding-block: 6px;
        margin-inline: 4px;
      }
      .style-matrix .native-matrix {
        background-color: rgb(240, 241, 242);
        width: 320px;
        max-width: 400px;
        min-height: 120px;
        margin-block: 9px;
        padding: 13px;
        border: 2px dashed rgb(20, 40, 60);
        border-radius: 6px;
        box-shadow: 3px 4px 5px rgb(1, 2, 3);
        filter: opacity(0.8);
        transform: translateX(6px) scale(1.05);
        animation: wpseed-audit 2s linear;
        overflow: hidden;
        position: relative;
        z-index: 7;
        transition: opacity 1s ease;
      }
      @keyframes wpseed-audit { from { opacity: .9; } to { opacity: 1; } }
    </style></head><body>
      <main class="entry-content style-matrix">
        ${moduleMarkup('frontend', variables)}
        ${moduleMarkup('preview', variables)}
        ${moduleMarkup('none', noneVariables)}
        ${matrixMarkup}
      </main>
    </body></html>`);

    const typographySelectors = [
      '.wp-seed-event-dates__title',
      '.wp-seed-event-date__date',
      '.wp-seed-event-date__time',
      '.wp-seed-event-date__status',
      '.wp-seed-event-calendar-link',
    ];
    for (const moduleId of ['frontend', 'preview']) {
      for (const selector of typographySelectors) {
        const style = await page.locator(`#${moduleId} ${selector}`).first().evaluate((element) => {
          const computed = getComputedStyle(element);
          return {
            display: computed.display,
            width: computed.width,
            fontSize: computed.fontSize,
            fontWeight: computed.fontWeight,
            fontStretch: computed.fontStretch,
            fontVariationSettings: computed.fontVariationSettings,
            color: computed.color,
            lineHeight: computed.lineHeight,
            letterSpacing: computed.letterSpacing,
            textAlign: computed.textAlign,
            textTransform: computed.textTransform,
            fontStyle: computed.fontStyle,
            textDecorationLine: computed.textDecorationLine,
            textDecorationColor: computed.textDecorationColor,
            textDecorationStyle: computed.textDecorationStyle,
            textDecorationThickness: computed.textDecorationThickness,
            textUnderlineOffset: computed.textUnderlineOffset,
            direction: computed.direction,
            hyphens: computed.hyphens,
          };
        });
        assert.strictEqual(style.display, 'block', `${moduleId}/${selector}: display`);
        assert.notStrictEqual(style.width, 'auto', `${moduleId}/${selector}: width`);
        assert.strictEqual(style.fontSize, '22px', `${moduleId}/${selector}: font-size`);
        assert.strictEqual(style.fontWeight, '700', `${moduleId}/${selector}: font-weight`);
        assert.strictEqual(style.fontStretch, '125%', `${moduleId}/${selector}: font-stretch`);
        assert.ok(style.fontVariationSettings.includes('"wght" 650'), `${moduleId}/${selector}: variable weight`);
        assert.ok(style.fontVariationSettings.includes('"wdth" 110'), `${moduleId}/${selector}: variable width`);
        assert.strictEqual(style.color, 'rgb(12, 34, 56)', `${moduleId}/${selector}: color`);
        assert.strictEqual(style.lineHeight, '34px', `${moduleId}/${selector}: line-height`);
        assert.strictEqual(style.letterSpacing, '2px', `${moduleId}/${selector}: letter-spacing`);
        assert.strictEqual(style.textAlign, 'right', `${moduleId}/${selector}: text-align`);
        assert.strictEqual(style.textTransform, 'uppercase', `${moduleId}/${selector}: capitalization`);
        assert.strictEqual(style.fontStyle, 'italic', `${moduleId}/${selector}: font-style`);
        assert.strictEqual(style.textDecorationLine, 'underline', `${moduleId}/${selector}: decoration`);
        assert.strictEqual(style.textDecorationColor, 'rgb(90, 80, 70)', `${moduleId}/${selector}: decoration color`);
        assert.strictEqual(style.textDecorationStyle, 'wavy', `${moduleId}/${selector}: decoration style`);
        assert.strictEqual(style.textDecorationThickness, '3px', `${moduleId}/${selector}: decoration thickness`);
        assert.strictEqual(style.textUnderlineOffset, '5px', `${moduleId}/${selector}: underline offset`);
        assert.strictEqual(style.direction, 'rtl', `${moduleId}/${selector}: direction`);
        assert.strictEqual(style.hyphens, 'auto', `${moduleId}/${selector}: hyphens`);
      }
    }

    for (const moduleId of ['frontend', 'preview']) {
      const native = await page.locator(`#${moduleId}`).evaluate((element) => {
        const computed = getComputedStyle(element);
        const title = getComputedStyle(element.querySelector('.wp-seed-event-dates__title'));
        const occurrence = getComputedStyle(element.querySelector('.wp-seed-event-date'));
        return {
          backgroundColor: computed.backgroundColor,
          width: computed.width,
          maxWidth: computed.maxWidth,
          minHeight: computed.minHeight,
          marginTop: computed.marginTop,
          paddingTop: computed.paddingTop,
          borderTopWidth: computed.borderTopWidth,
          borderTopStyle: computed.borderTopStyle,
          borderTopColor: computed.borderTopColor,
          borderRadius: computed.borderTopLeftRadius,
          boxShadow: computed.boxShadow,
          filter: computed.filter,
          transform: computed.transform,
          animationName: computed.animationName,
          overflow: computed.overflow,
          position: computed.position,
          zIndex: computed.zIndex,
          transitionProperty: computed.transitionProperty,
          titleMargin: title.marginBlockEnd,
          titlePadding: title.paddingBlockStart,
          occurrencePadding: occurrence.paddingBlockStart,
          occurrenceMargin: occurrence.marginInlineStart,
        };
      });
      assert.strictEqual(native.backgroundColor, 'rgb(240, 241, 242)');
      assert.strictEqual(native.width, '320px');
      assert.strictEqual(native.maxWidth, '400px');
      assert.strictEqual(native.minHeight, '120px');
      assert.strictEqual(native.marginTop, '9px');
      assert.strictEqual(native.paddingTop, '13px');
      assert.deepStrictEqual([native.borderTopWidth, native.borderTopStyle, native.borderTopColor, native.borderRadius], ['2px', 'dashed', 'rgb(20, 40, 60)', '6px']);
      assert.ok(native.boxShadow.includes('rgb(1, 2, 3)'));
      assert.strictEqual(native.filter, 'opacity(0.8)');
      assert.notStrictEqual(native.transform, 'none');
      assert.strictEqual(native.animationName, 'wpseed-audit');
      assert.deepStrictEqual([native.overflow, native.position, native.zIndex, native.transitionProperty], ['hidden', 'relative', '7', 'opacity']);
      assert.deepStrictEqual([native.titleMargin, native.titlePadding, native.occurrencePadding, native.occurrenceMargin], ['17px', '5px', '6px', '4px']);
    }
    const listCases = [
      { width: 1200, marker: 'disc', position: 'outside', indent: '40px', gap: '11px', color: 'rgb(170, 0, 0)', fontSize: '22px', lineHeight: '34px', textAlign: 'right' },
      { width: 820, marker: 'circle', position: 'inside', indent: '30px', gap: '7px', color: 'rgb(0, 128, 0)', fontSize: '18px', lineHeight: '28px', textAlign: 'center' },
      { width: 390, marker: 'square', position: 'outside', indent: '20px', gap: '3px', color: 'rgb(0, 0, 255)', fontSize: '15px', lineHeight: '22px', textAlign: 'left' },
    ];
    for (const testCase of listCases) {
      await page.setViewportSize({ width: testCase.width, height: 800 });
      for (const moduleId of ['frontend', 'preview']) {
        const style = await page.locator(`#${moduleId} .wp-seed-event-dates`).evaluate((list) => {
          const item = list.querySelectorAll('.wp-seed-event-date')[1];
          return {
            marker: getComputedStyle(list).listStyleType,
            position: getComputedStyle(list).listStylePosition,
            indent: getComputedStyle(list).paddingInlineStart,
            gap: getComputedStyle(item).marginBlockStart,
            markerColor: getComputedStyle(item, '::marker').color,
            before: getComputedStyle(item, '::before').content,
            fontSize: getComputedStyle(item.querySelector('.wp-seed-event-date__date')).fontSize,
            lineHeight: getComputedStyle(item.querySelector('.wp-seed-event-date__date')).lineHeight,
            textAlign: getComputedStyle(item.querySelector('.wp-seed-event-date__date')).textAlign,
          };
        });
        assert.deepStrictEqual(style, {
          marker: testCase.marker,
          position: testCase.position,
          indent: testCase.indent,
          gap: testCase.gap,
          markerColor: testCase.color,
          before: 'none',
          fontSize: testCase.fontSize,
          lineHeight: testCase.lineHeight,
          textAlign: testCase.textAlign,
        }, `${moduleId}: responsive list and typography style at ${testCase.width}px`);
      }
    }

    const none = await page.locator('#none .wp-seed-event-dates').evaluate((list) => {
      const item = list.querySelector('.wp-seed-event-date');
      return {
        marker: getComputedStyle(list).listStyleType,
        itemMarker: getComputedStyle(item).listStyleType,
        indent: getComputedStyle(list).paddingInlineStart,
        before: getComputedStyle(item, '::before').content,
      };
    });
    assert.strictEqual(none.marker, 'none');
    assert.strictEqual(none.itemMarker, 'none');
    assert.strictEqual(none.indent, '0px');
    assert.ok(none.before === 'none' || none.before === 'normal');

    let matrixCases = 0;
    const readMatrixGeometry = async (selector) => page.locator(selector).evaluate((section) => {
      const list = section.querySelector('.wp-seed-event-dates');
      const item = list.querySelector('.wp-seed-event-date');
      const date = item.querySelector('.wp-seed-event-date__date');
      const listStyle = getComputedStyle(list);
      const itemStyle = getComputedStyle(item);
      const dateStyle = getComputedStyle(date);
      return {
        list: { type: listStyle.listStyleType, position: listStyle.listStylePosition, paddingInlineStart: listStyle.paddingInlineStart, paddingBlockEnd: listStyle.paddingBlockEnd, lineHeight: listStyle.lineHeight, display: listStyle.display, height: list.getBoundingClientRect().height },
        item: { display: itemStyle.display, type: itemStyle.listStyleType, position: itemStyle.listStylePosition, lineHeight: itemStyle.lineHeight, marginBlockStart: itemStyle.marginBlockStart, paddingBlockStart: itemStyle.paddingBlockStart, markerContent: getComputedStyle(item, '::marker').content, markerColor: getComputedStyle(item, '::marker').color, height: item.getBoundingClientRect().height },
        date: { display: dateStyle.display, lineHeight: dateStyle.lineHeight, fontSize: dateStyle.fontSize, height: date.getBoundingClientRect().height, minHeight: dateStyle.minHeight, marginBlockStart: dateStyle.marginBlockStart, paddingBlockStart: dateStyle.paddingBlockStart, textAlign: dateStyle.textAlign },
      };
    });
    await page.setViewportSize({ width: 1200, height: 800 });
    for (const markerType of markerTypes) {
      for (const lineHeight of lineHeights) {
        for (const count of [1, 2]) {
          const caseName = `${markerType}-${lineHeight}-${count}`;
          const frontend = await readMatrixGeometry(`#frontend-${caseName}`);
          const preview = await readMatrixGeometry(`#preview-${caseName}`);
          assert.deepStrictEqual(preview, frontend, `frontend/preview divergence: ${caseName}`);
          assert.strictEqual(preview.list.type, markerType, `wrong list marker: ${caseName}`);
          assert.strictEqual(preview.item.type, markerType, `wrong item marker: ${caseName}`);
          const expectedLineHeight = { default: '34px', '1em': '22px', '2em': '44px', '5em': '110px' }[lineHeight];
          assert.strictEqual(preview.date.lineHeight, expectedLineHeight, `wrong line-height: ${caseName}`);
          matrixCases += 1;
        }
      }
    }
    assert.strictEqual(matrixCases, 32);

    console.log(`Divi event dates computed styles: ${matrixCases} marker/line-height occurrence cases plus responsive typography/list parity passed.`);
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
