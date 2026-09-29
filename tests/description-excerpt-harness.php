<?php
define('ABSPATH',__DIR__);
function add_action(...$args){}
function absint($v){return abs((int)$v);}
function sanitize_key($v){return $v;}
function wp_strip_all_tags($v){return strip_tags($v);}
function strip_shortcodes($v){return preg_replace('/\[[^\]]+\]/','',$v);}
function wp_seed_events_get_event_data($id){return $GLOBALS['events'][$id]??array();}
require __DIR__.'/../includes/public/descriptions.php';
require __DIR__.'/../includes/public/data-registry.php';
function check($condition,$label){if(!$condition)throw new RuntimeException($label);echo "PASS $label\n";}
$GLOBALS['events'][1]=array('description'=>'<script>secret</script><p>Texte <strong>éditorial</strong> &amp; sensible.</p>[shortcode]', 'excerpt'=>'Dates du résumé manuel');
$before=$GLOBALS['events'];
check(wp_seed_events_dynamic_data_get_value('description_excerpt',1)==='Texte éditorial & sensible.','excerpt strips active markup and decodes entities');
check(wp_seed_events_dynamic_data_get_value('excerpt',1)==='Dates du résumé manuel','existing manual excerpt untouched');
check($before===$GLOBALS['events'],'no data mutation');
$GLOBALS['events'][2]=array('description'=>implode(' ',array_fill(0,30,'mot')));
check(wp_seed_events_dynamic_data_get_value('description_excerpt',2)===implode(' ',array_fill(0,28,'mot')).'…','excerpt limited to 28 words');
check(wp_seed_events_dynamic_data_get_value('description_excerpt',99)==='','invalid event stays empty');
check(wp_seed_events_dynamic_data_field_format('description_excerpt')==='plain_text','new field declares plain text');
