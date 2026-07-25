<?php
/** Static checks for the durable alpha feedback issue form. */
declare(strict_types=1);
$form=file_get_contents(dirname(__DIR__).'/.github/ISSUE_TEMPLATE/alpha-feedback.yml');$cases=0;
function alpha_form_assert($c,$m){if(!$c){throw new RuntimeException($m);}} function alpha_form_case($l,$cb){global $cases;$cases++;$cb();echo '[OK] '.$cases.' '.$l.PHP_EOL;}
alpha_form_assert(false!==$form,'Unable to read form.');
alpha_form_case('no fixed alpha version',function()use($form){alpha_form_assert(0===preg_match('/0[.]2[.]0-alpha[.][0-9]+/',$form),'fixed alpha version remains');});
alpha_form_case('version-neutral description',function()use($form){alpha_form_assert(false!==strpos($form,'avec une préversion de WP Seed Events'),'neutral description missing');});
alpha_form_case('tested plugin version field',function()use($form){alpha_form_assert(false!==strpos($form,'id: plugin_version'),'field missing');alpha_form_assert(false!==strpos($form,'label: "Version testée de WP Seed Events"'),'label differs');});
alpha_form_case('plugin version required',function()use($form){$s=strpos($form,'id: plugin_version');$e=strpos($form,"\n  - type:",$s);$f=substr($form,$s,$e-$s);alpha_form_assert(false!==strpos($f,'required: true'),'version optional');});
alpha_form_case('environment complete',function()use($form){foreach(array('id: browser','id: wordpress','id: php','id: builder_version','id: theme')as$f){alpha_form_assert(false!==strpos($form,$f),'missing '.$f);}});
alpha_form_case('durable labels',function()use($form){alpha_form_assert(false!==strpos($form,'- "alpha-feedback"'),'alpha label missing');alpha_form_assert(false!==strpos($form,'- "status: needs-triage"'),'triage label missing');alpha_form_assert(false===strpos($form,'target: alpha.'),'target hard-coded');});
alpha_form_case('privacy required',function()use($form){alpha_form_assert(false!==strpos($form,"Je confirme qu'aucun secret"),'privacy missing');});
alpha_form_case('UTF-8 clean',function()use($form){alpha_form_assert("\xEF\xBB\xBF"!==substr($form,0,3),'BOM');alpha_form_assert(1===preg_match('//u',$form),'invalid UTF-8');foreach(array('c383','c382','c3a2e282ac')as$s){alpha_form_assert(false===strpos($form,hex2bin($s)),'mojibake');}});
echo '[OK] '.$cases.'/'.$cases.' alpha feedback form cases passed.'.PHP_EOL;
