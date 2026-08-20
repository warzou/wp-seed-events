'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { chromium } = require('playwright');

const root = path.resolve(__dirname, '..');
const stylesheet = fs.readFileSync(path.join(root, 'includes/public/event-lists.css'), 'utf8');
const executablePath = process.env.WPSEED_BROWSER_PATH
  || 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';

(async () => {
  const browser = await chromium.launch({ executablePath, headless: true });
  try {
    const page = await browser.newPage();
    await page.setContent(`<!doctype html><html><head><style>
      .astra-like .entry-content ul,
      .divi-like .et_pb_module ul { list-style: disc; padding-inline-start: 42px; }
      .astra-like .entry-content li::before,
      .divi-like .et_pb_module li::before { content: "theme-marker"; display: inline; }
      ${stylesheet}
    </style></head><body>
      <main class="astra-like"><article class="entry-content">
        <ul class="wp-seed-event-people__roles"><li>Organisateur</li></ul>
        <ul class="wp-seed-event-people__contacts"><li>Telephone</li></ul>
        <div class="wp-seed-events-rich-content"><ul><li>Editorial</li></ul></div>
      </article></main>
      <main class="divi-like"><div class="et_pb_module">
        <ul class="wp-seed-event-people__roles"><li>Intervenant</li></ul>
        <ul class="wp-seed-event-people__contacts"><li>Email</li></ul>
        <section class="wp-seed-event-people-section is-people-composable is-contact-layout-desktop-with_name is-contact-layout-tablet-inline is-contact-layout-phone-stacked">
          <ul class="wp-seed-event-list has-custom-list-style wp-seed-event-people__list">
            <li class="wp-seed-event-people__item">
              <div class="wp-seed-event-people__identity-contact-flow">
                <span class="wp-seed-event-people__name">Helene Magard</span>
                <span class="wp-seed-event-people__contact-separator wp-seed-event-people__contact-separator--name">:</span>
                <ul class="wp-seed-event-people__contacts"><li>Email</li><li><span class="wp-seed-event-people__contact-separator">—</span>Telephone</li></ul>
              </div>
            </li>
          </ul>
        </section>
      </div></main>
    </body></html>`);

    for (const scope of ['.astra-like', '.divi-like']) {
      for (const className of ['roles', 'contacts']) {
        const result = await page.locator(`${scope} .wp-seed-event-people__${className}`).first().evaluate((list) => {
          const item = list.firstElementChild;
          return {
            listType: getComputedStyle(list).listStyleType,
            itemType: getComputedStyle(item).listStyleType,
            indent: getComputedStyle(list).paddingInlineStart,
            before: getComputedStyle(item, '::before').content,
            marker: getComputedStyle(item, '::marker').content,
          };
        });
        assert.strictEqual(result.listType, 'none');
        assert.strictEqual(result.itemType, 'none');
        assert.strictEqual(result.indent, '0px');
        assert.strictEqual(result.before, 'none');
        assert.ok(result.marker === '""' || result.marker === 'none');
      }
    }

    const editorial = await page.locator('.wp-seed-events-rich-content ul').evaluate((list) => ({
      type: getComputedStyle(list).listStyleType,
      indent: getComputedStyle(list).paddingInlineStart,
      before: getComputedStyle(list.firstElementChild, '::before').content,
    }));
    assert.strictEqual(editorial.type, 'disc');
    assert.strictEqual(editorial.indent, '42px');
    assert.strictEqual(editorial.before, '"theme-marker"');

    const withName = await page.locator('.wp-seed-event-people__identity-contact-flow').evaluate((flow) => {
      const name = flow.querySelector('.wp-seed-event-people__name');
      const contacts = flow.querySelector('.wp-seed-event-people__contacts');
      const nameSeparator = flow.querySelector('.wp-seed-event-people__contact-separator--name');
      const contactSeparator = flow.querySelector('.wp-seed-event-people__contact-separator:not(.wp-seed-event-people__contact-separator--name)');
      return {
        flowDisplay: getComputedStyle(flow).display,
        flowWidth: getComputedStyle(flow).width,
        itemDisplay: getComputedStyle(flow.parentElement).display,
        nameWidth: getComputedStyle(name).width,
        contactsWidth: getComputedStyle(contacts).width,
        nameSeparatorDisplay: getComputedStyle(nameSeparator).display,
        contactSeparatorDisplay: getComputedStyle(contactSeparator).display,
        nameTop: name.getBoundingClientRect().top,
        nameSeparatorTop: nameSeparator.getBoundingClientRect().top,
        contactsTop: contacts.getBoundingClientRect().top,
      };
    });
    assert.strictEqual(withName.itemDisplay, 'list-item');
    assert.strictEqual(withName.flowDisplay, 'inline-flex');
    assert.notStrictEqual(withName.flowWidth, '100%');
    assert.notStrictEqual(withName.nameWidth, '100%');
    assert.notStrictEqual(withName.contactsWidth, '100%');
    assert.notStrictEqual(withName.nameSeparatorDisplay, 'none');
    assert.strictEqual(withName.contactSeparatorDisplay, 'inline');
    assert.ok(Math.abs(withName.nameTop - withName.nameSeparatorTop) < 2);
    assert.ok(Math.abs(withName.nameTop - withName.contactsTop) < 2);

    console.log('Structural lists under Astra/Divi-like CSS: PASS; editorial list preserved');
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exit(1);
});
