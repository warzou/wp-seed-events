const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const {execFileSync} = require('node:child_process');
const {JSDOM,VirtualConsole} = require('jsdom');
const virtualConsole=new VirtualConsole();virtualConsole.on('jsdomError',error=>{throw error;});
const output=JSON.parse(execFileSync(process.env.PHP_BINARY || 'php',[path.join(__dirname,'share-compatibility-fixture.php')],{encoding:'utf8'}));
const script=fs.readFileSync(path.join(__dirname,'../includes/public/event-share.js'),'utf8');
const css=fs.readFileSync(path.join(__dirname,'../includes/public/event-share.css'),'utf8');
const normalize=s=>s.replace(/wp-seed-event-share-panel-\d+/g,'PANEL');
assert.equal(normalize(output.default),normalize(output.legacy));
assert.equal(normalize(output.default),normalize(output.layout));
assert.equal(normalize(output.default),normalize(output.override));
assert.equal(output.legacy_footer,'','legacy bootstrap must not load a second engine');
assert.equal((output.footer.match(/navigator.share\(/g)||[]).length,1);
assert.ok(output.hooks.wp_footer.includes('wp_seed_events_render_public_share_script'));
assert.ok(!output.malformed.includes('onclick'));
assert.ok(!output.malformed.includes('https://invalid.test'));
assert.ok(output.malformed.includes('A &quot;quote&quot;'));
assert.deepEqual(Object.keys(output.assets).sort(),['wp-seed-events-divi-share','wp-seed-events-public-share']);
assert.ok(output.assets['wp-seed-events-public-share'].endsWith('includes/public/event-share.js'));
const flush=()=>new Promise(r=>setImmediate(r));
(async()=>{
 for(const name of ['default','legacy','layout','divi','decorated']){
  const dom=new JSDOM('<style>'+css+'</style>'+output[name],{url:'https://example.org',runScripts:'outside-only',virtualConsole});
  const w=dom.window,d=w.document;w.isSecureContext=true;
  w.eval(script);w.eval(script);
  const root=d.querySelector('[data-wp-seed-event-share]'),primary=root.querySelector('[data-wp-seed-event-share-native]'),panel=root.querySelector('[data-wp-seed-event-share-panel]');
  assert.equal(root.querySelector('.wp-seed-event-share__actions').children.length,name==='decorated'?3:1);
  assert.equal(primary.hidden,false);assert.equal(panel.hidden,true);
  if(name==='divi'||name==='decorated') assert.ok(primary.classList.contains('et_pb_button'));
  if(name==='decorated'){assert.ok(primary.classList.contains('has-icon-left'));assert.equal(primary.dataset.icon,'\ue001');}
  primary.click();assert.equal(panel.hidden,false);assert.equal(primary.getAttribute('aria-expanded'),'true');
  const copy=panel.querySelector('button');assert.equal(d.activeElement,copy);
  let copied='';w.navigator.clipboard={writeText:async text=>{copied=text;}};
  copy.click();await flush();assert.equal(copied,'https://example.org/event/?a=1&b=2');
  assert.ok(copy.querySelector('[data-wp-seed-event-share-label]'),'copy label does not destroy decoration');
  const mail=new URL(panel.querySelector('a').href);assert.equal(mail.searchParams.get('subject'),'Été & rencontre');
  copy.dispatchEvent(new w.KeyboardEvent('keydown',{key:'Escape',bubbles:true}));assert.equal(panel.hidden,true);assert.equal(d.activeElement,primary);
  let calls=0;w.navigator.share=()=>{calls++;return Promise.reject({name:'AbortError'});};
  primary.click();await flush();assert.equal(calls,1);assert.equal(panel.hidden,true);assert.equal(root.querySelector('[role=status]').textContent,'');
  // REST / builder fragments inserted after startup receive progressive enhancement.
  const holder=d.createElement('div');holder.innerHTML=output.default;d.body.appendChild(holder);await flush();
  assert.equal(holder.querySelector('[data-wp-seed-event-share-native]').hidden,false);
  dom.window.close();
 }
 console.log('Sharing compatibility: real Divi/common renderer, historical signature, presets, options, assets, fallback, focus, Escape, AbortError and dynamic preview PASS');
})().catch(e=>{console.error(e);process.exitCode=1;});
