const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(require('node:path').join(__dirname, '../includes/public/sharing.php'), 'utf8');
const script = source.match(/<script>([\s\S]*?)<\/script>/)[1];
async function scenario({secure=true, reject=false, fallback=true, nested=true, url='https://example.org/event/'}={}) {
  let handler, copied, restored=false;
  const timers=[];
  const feedback={textContent:''}, label={textContent:'Copier le lien'};
  const attrs={'data-share-url':url};
  const root={querySelector:()=>feedback};
  const button={textContent:'Copier le lien', closest:()=>root, querySelector:()=>nested?label:null, getAttribute:k=>attrs[k], setAttribute:(k,v)=>attrs[k]=v};
  const input={style:{},setAttribute(){},select(){}};
  const document={activeElement:{focus(){restored=true;}},createElement:()=>input,body:{appendChild(){},removeChild(){}},execCommand(){if(fallback)copied=input.value;return fallback;},addEventListener:(event,fn)=>handler=fn};
  const navigator={clipboard:{writeText:async text=>{if(reject)throw Error('denied');copied=text;}}};
  vm.runInNewContext(script,{document,navigator,window:{isSecureContext:secure,setTimeout:fn=>timers.push(fn)}});
  handler({target:{}}); // A non-element event target must not throw.
  handler({target:{closest:()=>button}});
  await new Promise(resolve=>setImmediate(resolve));
  return {copied,feedback,label: nested?label:button,restored,timers};
}
(async()=>{
  for(const nested of [true,false]) {
    const r=await scenario({nested});assert.equal(r.copied,'https://example.org/event/');assert.equal(r.label.textContent,'Lien copié');assert.match(r.feedback.textContent,/a été copié/);r.timers[0]();assert.equal(r.label.textContent,'Copier le lien');
  }
  for(const settings of [{secure:false},{reject:true}]){const r=await scenario(settings);assert.equal(r.copied,'https://example.org/event/');assert.ok(r.restored);}
  const denied=await scenario({reject:true,fallback:false});assert.equal(denied.label.textContent,'Copie impossible');assert.match(denied.feedback.textContent,/n’a pas pu/);
  const empty=await scenario({url:''});assert.equal(empty.copied,undefined);assert.equal(empty.label.textContent,'Copie impossible');
  console.log('Inline / legacy sharing: clipboard success, denied fallback, failure, focus restoration and label reset PASS');
})().catch(error=>{console.error(error);process.exitCode=1;});
