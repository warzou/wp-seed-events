<?php
define('ABSPATH', __DIR__);
function absint($v){return abs((int)$v);}
function wp_strip_all_tags($v){return strip_tags($v);}
function esc_url_raw($v){return $v;}
function esc_url($v){return htmlspecialchars($v,ENT_QUOTES);}
function esc_html__($v,$domain){return htmlspecialchars($v,ENT_QUOTES);}
function shortcode_atts($defaults,$atts,$name){return array_merge($defaults,$atts);}
function wp_seed_events_get_event_data($id){return array('id'=>(int)$id,'title'=>'Titre','url'=>'https://example.org/event/'.$id);}
function get_option($key,$default){return 9;}
function get_post_type($id){return 'page';}
function get_post($id){return (object)array('post_type'=>'page','post_content'=>'CONTENT');}
function apply_filters($name,$value){
 if(!empty($GLOBALS['fail']))throw new RuntimeException('render failed');
 return $value.(!empty($GLOBALS['inline'])?wp_seed_events_event_share_shortcode(array()):'');
}
require __DIR__.'/../includes/public/rendering.php';
require __DIR__.'/../includes/public/sharing.php';
function check($condition,$label){if(!$condition)throw new RuntimeException($label);echo "PASS $label\n";}
$GLOBALS['inline']=true;
$html=wp_seed_events_render_public_event_single(42,true);
check(substr_count($html,'data-wp-seed-event-share>')===1 && !str_contains($html,'<details'),'template inline share rendered exactly once');
check(!isset($GLOBALS['wp_seed_events_template_share_context']),'temporary share context cleaned');
$GLOBALS['inline']=false;
$html=wp_seed_events_render_public_event_single(42,true);
check(str_contains($html,'<details'),'unconfigured templates preserve automatic legacy menu');
$GLOBALS['inline']=true;
$html=wp_seed_events_render_public_event_single(42,true);
check(substr_count($html,'data-wp-seed-event-share>')===1,'repeated render does not suppress explicit share');
$previous=array('event_id'=>7,'rendered'=>false);
$GLOBALS['wp_seed_events_template_share_context']=$previous;
$GLOBALS['fail']=true;
try{wp_seed_events_render_public_event_single(42,true);}catch(RuntimeException $e){}
check($GLOBALS['wp_seed_events_template_share_context']===$previous,'outer share context restored on exception');
