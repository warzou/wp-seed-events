'use strict';

const assert = require('assert');
const fs = require('fs');
const path = require('path');
const { JSDOM } = require('jsdom');

const source = fs.readFileSync(path.join(__dirname, '../includes/public/event-share.js'), 'utf8');
const markup = `
  <div data-wp-seed-event-share>
    <div role="group" aria-label="Partager cet événement">
      <button type="button" aria-label="Partager" data-wp-seed-event-share-native data-share-title="Événement test" data-share-text="Découvrez cet événement : Événement test" data-share-url="https://example.test/event/"><span aria-hidden="true">↗</span><span data-wp-seed-event-share-action-label>Partager</span></button>
      <button type="button" aria-label="Copier le lien" data-wp-seed-event-share-copy data-share-url="https://example.test/event/"><span aria-hidden="true">⧉</span><span data-wp-seed-event-share-action-label>Copier le lien</span></button>
      <a href="mailto:?subject=%C3%89v%C3%A9nement">Par email</a>
    </div>
    <p role="status" aria-live="polite" data-wp-seed-event-share-feedback></p>
  </div>`;

const iconOnlyMarkup = `
  <div data-wp-seed-event-share>
    <button type="button" aria-label="Copier le lien" data-icon="&#xe002;" data-wp-seed-event-share-copy data-share-url="https://example.test/event/"></button>
    <p role="status" aria-live="polite" data-wp-seed-event-share-feedback></p>
  </div>`;

const tick = () => new Promise((resolve) => setImmediate(resolve));
const setup = ({ share, clipboard, execCommand = () => true, html = markup } = {}) => {
  const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'https://example.test/' });
  Object.defineProperty(dom.window, 'isSecureContext', { configurable: true, value: true });
  Object.defineProperty(dom.window.navigator, 'clipboard', { configurable: true, value: clipboard });
  Object.defineProperty(dom.window.navigator, 'share', { configurable: true, value: share });
  dom.window.document.execCommand = execCommand;
  dom.window.setTimeout = () => 1;
  dom.window.clearTimeout = () => {};
  dom.window.eval(source);
  return dom;
};

(async () => {
  let copied = '';
  const copyDom = setup({ clipboard: { writeText: async (value) => { copied = value; } } });
  copyDom.window.document.querySelector('[data-wp-seed-event-share-copy]').click();
  await tick();
  assert.strictEqual(copied, 'https://example.test/event/');
  assert.strictEqual(copyDom.window.document.querySelector('[data-wp-seed-event-share-copy] [data-wp-seed-event-share-action-label]').textContent, 'Copié');
  assert.strictEqual(copyDom.window.document.querySelector('[role="status"]').textContent, 'Le lien de l’événement a été copié.');

  let shared = null;
  const shareDom = setup({ share: async (payload) => { shared = payload; }, clipboard: { writeText: async () => {} } });
  shareDom.window.document.querySelector('[data-wp-seed-event-share-native]').click();
  await tick();
  assert.strictEqual(JSON.stringify(shared), JSON.stringify({
    title: 'Événement test',
    text: 'Découvrez cet événement : Événement test',
    url: 'https://example.test/event/',
  }));
  assert.strictEqual(shareDom.window.document.querySelector('[data-wp-seed-event-share-native] [data-wp-seed-event-share-action-label]').textContent, 'Partagé');

  copied = '';
  const fallbackDom = setup({ clipboard: { writeText: async (value) => { copied = value; } } });
  fallbackDom.window.document.querySelector('[data-wp-seed-event-share-native]').click();
  await tick();
  assert.strictEqual(copied, 'https://example.test/event/');
  assert.strictEqual(fallbackDom.window.document.querySelector('[data-wp-seed-event-share-native] [data-wp-seed-event-share-action-label]').textContent, 'Copié');

  let fallbackCalls = 0;
  const legacyDom = setup({ clipboard: undefined, execCommand: () => { fallbackCalls += 1; return true; } });
  legacyDom.window.document.querySelector('[data-wp-seed-event-share-copy]').click();
  await tick();
  assert.strictEqual(fallbackCalls, 1);

  let cancelCopies = 0;
  const cancel = new Error('cancelled');
  cancel.name = 'AbortError';
  const cancelDom = setup({ share: async () => { throw cancel; }, clipboard: { writeText: async () => { cancelCopies += 1; } } });
  cancelDom.window.document.querySelector('[data-wp-seed-event-share-native]').click();
  await tick();
  assert.strictEqual(cancelCopies, 0);
  assert.strictEqual(cancelDom.window.document.querySelector('[data-wp-seed-event-share-native] [data-wp-seed-event-share-action-label]').textContent, 'Partager');

  const iconDom = setup({ clipboard: { writeText: async () => {} }, html: iconOnlyMarkup });
  const iconButton = iconDom.window.document.querySelector('[data-wp-seed-event-share-copy]');
  iconButton.click();
  await tick();
	assert.strictEqual(iconButton.getAttribute('data-icon'), '\ue002');
  assert.strictEqual(iconButton.getAttribute('aria-label'), 'Copier le lien');
  assert.strictEqual(iconDom.window.document.querySelector('[role="status"]').textContent, 'Le lien de l’événement a été copié.');

  assert.strictEqual(copyDom.window.document.querySelectorAll('button').length, 2);
  assert.strictEqual(copyDom.window.document.querySelectorAll('a[href^="mailto:"]').length, 1);
  assert.strictEqual(copyDom.window.document.querySelectorAll('details, summary').length, 0);
  ['Ã', 'Â', 'â€™', 'â€”', 'â€¦'].forEach((token) => assert.ok(!source.includes(token), `Public script contains mojibake: ${token}`));

  console.log('Share actions browser contract: 25/25 PASS');
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
