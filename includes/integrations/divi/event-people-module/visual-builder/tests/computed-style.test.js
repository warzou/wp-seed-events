'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const metadata = JSON.parse(fs.readFileSync(path.resolve(__dirname, '..', 'src', 'module.json'), 'utf8'));
const dom = new JSDOM(`<!doctype html><html><head></head><body>
  <div class="people-module">
    <h2 class="wp-seed-event-people__title">Contacts</h2>
    <ul class="wp-seed-event-people__list">
      <li class="wp-seed-event-people__item">
        <span class="wp-seed-event-people__name">Personne</span>
        <ul class="wp-seed-event-people__contacts">
          <li><span class="wp-seed-event-people__phone-text">01 02 03 04 05</span></li>
          <li><a class="wp-seed-event-people__phone-link" href="tel:0102030405">01 02 03 04 05</a></li>
          <li><span class="wp-seed-event-people__email-text">contact@example.test</span></li>
          <li><a class="wp-seed-event-people__email-link" href="mailto:contact@example.test">contact@example.test</a></li>
          <li><span class="wp-seed-event-people__link-text">Site</span></li>
          <li><a class="wp-seed-event-people__link-anchor" href="https://example.test/">Site</a></li>
        </ul>
      </li>
    </ul>
  </div>
</body></html>`);

const { document, getComputedStyle } = dom.window;
const style = document.createElement('style');
document.head.append(style);

const scope = (selector) => selector.replaceAll('{{selector}}', '.people-module');
const commonDeclarations = [
  'font-family: Arial',
  'font-weight: 700',
  'font-size: 21px',
  'color: rgb(18, 52, 86)',
  'line-height: 31px',
  'letter-spacing: 2px',
  'text-align: right',
  'text-transform: uppercase',
  'text-decoration: underline',
].join(';');

style.textContent = `
  .people-module a { color: rgb(200, 0, 0); font-weight: 400; }
  ${scope(metadata.attributes.contactsStyle.selector)} { ${commonDeclarations}; }
`;

const targets = [
  ['phone text', '.wp-seed-event-people__phone-text'],
  ['phone link', '.wp-seed-event-people__phone-link'],
  ['email text', '.wp-seed-event-people__email-text'],
  ['email link', '.wp-seed-event-people__email-link'],
  ['site text', '.wp-seed-event-people__link-text'],
  ['site link', '.wp-seed-event-people__link-anchor'],
];

const expected = {
  fontFamily: 'Arial',
  fontWeight: '700',
  fontSize: '21px',
  color: 'rgb(18, 52, 86)',
  lineHeight: '31px',
  letterSpacing: '2px',
  textAlign: 'right',
  textTransform: 'uppercase',
};

const matrix = [];
targets.forEach(([label, selector]) => {
  const computed = getComputedStyle(document.querySelector(selector));
  Object.entries(expected).forEach(([property, value]) => {
    assert.strictEqual(computed[property], value, `${label}: ${property} did not use the common coordinate style.`);
  });
  assert.ok(
    `${computed.textDecorationLine} ${computed.textDecoration}`.includes('underline'),
    `${label}: text decoration did not use the common coordinate style.`,
  );
  matrix.push({ group: 'Coordonnees', control: 'typography', target: label, status: 'PASS' });
});

style.textContent += `
  ${scope(metadata.attributes.emailLinkStyle.selector)} { color: rgb(101, 67, 33); font-weight: 500; }
`;
const legacyEmail = getComputedStyle(document.querySelector('.wp-seed-event-people__email-link'));
const unaffectedPhone = getComputedStyle(document.querySelector('.wp-seed-event-people__phone-link'));
assert.strictEqual(legacyEmail.color, 'rgb(101, 67, 33)');
assert.strictEqual(legacyEmail.fontWeight, '500');
assert.strictEqual(unaffectedPhone.color, expected.color);
assert.strictEqual(unaffectedPhone.fontWeight, expected.fontWeight);
matrix.push({ group: 'Coordonnees', control: 'legacy explicit email override', target: 'email link', status: 'PASS' });

assert.ok(metadata.attributes.titleStyle.selector.includes('wp-seed-event-people__title'));
assert.ok(metadata.attributes.nameStyle.selector.includes('wp-seed-event-people__name'));
assert.ok(metadata.attributes.itemStyle.selector.includes('wp-seed-event-people__item'));
assert.ok(metadata.attributes.eventListStyle.selector.includes('wp-seed-event-people__list'));

console.log(JSON.stringify(matrix, null, 2));
console.log('Divi event people computed styles: common text/link typography and legacy precedence PASS');
