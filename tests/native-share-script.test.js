const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');
const source = fs.readFileSync(require('node:path').join(__dirname, '../includes/public/sharing.php'), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
function fixture({support=true, secure=true, error, sync=false, pending=false}={}) {
  const dom = new JSDOM(`<button id="outside">Outside</button><div data-wp-seed-event-share>
    <button type="button" hidden data-wp-seed-event-share-native aria-expanded="false" aria-controls="panel" data-share-title="Été &amp; rencontre" data-share-url="https://example.org/events/été/?a=1&amp;b=2">Partager</button>
    <div id="panel" hidden data-wp-seed-event-share-panel><button disabled data-wp-seed-event-share-copy data-share-url="https://example.org/events/été/?a=1&amp;b=2">Copier le lien</button><a href="mailto:?subject=Example">E-mail</a></div>
    <p data-wp-seed-event-share-feedback></p></div>`, {runScripts:'outside-only', url:'https://example.org'});
  const {window}=dom, {document}=window, calls=[];
  let finish;
  window.isSecureContext=secure;
  if(support) window.navigator.share=data=>{calls.push(data);if(error&&sync)throw {name:error};if(error)return Promise.reject({name:error});if(pending)return new Promise(resolve=>finish=resolve);return Promise.resolve();};
  window.eval(script);
  return {window,document,calls,button:document.querySelector('[data-wp-seed-event-share-native]'),panel:document.getElementById('panel'),copy:document.querySelector('[data-wp-seed-event-share-copy]'),feedback:document.querySelector('[data-wp-seed-event-share-feedback]'),finish:()=>finish()};
}
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
  let r=fixture();assert.equal(r.button.hidden,false);assert.equal(r.panel.hidden,true);assert.equal(r.copy.disabled,false);
  r.button.click();assert.equal(r.calls.length,1,'share invoked synchronously during click');assert.equal(r.calls[0].title,'Été & rencontre');assert.equal(r.calls[0].url,'https://example.org/events/été/?a=1&b=2');assert.deepEqual(Object.keys(r.calls[0]).sort(),['title','url']);await flush();assert.equal(r.button.disabled,false);assert.equal(r.panel.hidden,true);r.window.close();
  for(const config of [{support:false},{secure:false}]){
    r=fixture(config);assert.equal(r.button.hidden,false);r.button.click();assert.equal(r.calls.length,0);assert.equal(r.panel.hidden,false);assert.equal(r.button.getAttribute('aria-expanded'),'true');assert.equal(r.document.activeElement,r.copy);
    r.copy.dispatchEvent(new r.window.KeyboardEvent('keydown',{key:'Escape',bubbles:true,cancelable:true}));assert.equal(r.panel.hidden,true);assert.equal(r.button.getAttribute('aria-expanded'),'false');assert.equal(r.document.activeElement,r.button);
    r.button.click();r.document.getElementById('outside').click();assert.equal(r.panel.hidden,true,'outside click closes panel');
    r.button.click();r.button.click();assert.equal(r.panel.hidden,true,'primary toggles fallback');r.window.close();
  }
  for(const sync of [true,false]){r=fixture({error:'AbortError',sync});r.button.click();await flush();assert.equal(r.feedback.textContent,'','cancellation is silent');assert.equal(r.panel.hidden,true,'cancellation does not open fallback');assert.equal(r.button.disabled,false);r.window.close();}
  for(const error of ['NotAllowedError','DataError','TypeError'])for(const sync of [true,false]){r=fixture({error,sync});r.button.click();await flush();assert.equal(r.panel.hidden,false);assert.equal(r.feedback.textContent,'');assert.equal(r.button.disabled,false);assert.equal(r.document.activeElement,r.copy);r.window.close();}
  r=fixture({pending:true});r.button.click();r.button.click();assert.equal(r.calls.length,1,'no duplicate native panel');r.finish();await flush();assert.equal(r.button.disabled,false);r.window.close();
  r=fixture();r.window.eval(script);r.button.click();assert.equal(r.calls.length,1,'one handler after duplicate initialization');await flush();r.window.close();
  assert.ok(!/et_builder|spectra|astra|therapsy|psychotherapiedeletre/i.test(source),'service has no builder/site dependency');
  console.log('Single sharing: default, native payload, unavailable API, AbortError, real errors, focus, Escape, outside click, toggle and duplicate initialization PASS');
})().catch(error=>{console.error(error);process.exitCode=1;});
