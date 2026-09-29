<?php
define('ABSPATH', __DIR__);
function esc_url_raw($s) { return $s; }
function esc_url($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_attr($s) { return htmlspecialchars($s, ENT_QUOTES); }
function esc_html__($s, $domain) { return htmlspecialchars($s, ENT_QUOTES); }
function wp_strip_all_tags($s) { return strip_tags($s); }
function shortcode_atts($defaults, $atts, $name) { return array_merge($defaults, $atts); }
function wp_seed_events_public_shortcode_event_id($id) { return (int) ($id ?: 42); }
function wp_seed_events_public_event_data($id) { return $id === 42 ? $GLOBALS['event'] : array(); }
require __DIR__ . '/../includes/public/sharing.php';
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); echo "PASS $message\n"; }
$GLOBALS['event'] = array('id'=>42, 'title'=>'Été & rencontre', 'url'=>'https://example.org/event/?x=1&y=2');
$event = $GLOBALS['event'];
$data = wp_seed_events_event_share_data($event);
parse_str(parse_url($data['email_url'], PHP_URL_QUERY), $mail);
check($mail['subject']===$event['title'] && $mail['body']===$event['title']."\r\n\r\n".$event['url'], 'mailto exact title and public URL');
function normalize_ids($html) { return preg_replace('/wp-seed-event-share-panel-\d+/', 'wp-seed-event-share-panel-ID', $html); }
$default = wp_seed_events_render_event_share_menu($event);
check(!str_contains($default, '<details'), 'default common renderer uses compact fallback, not details');
foreach (array('menu','inline','invalid') as $layout) {
 check(normalize_ids(wp_seed_events_render_event_share_menu($event, $layout))===normalize_ids($default), 'compatible layout call uses primary share only: '.$layout);
}
$inline = wp_seed_events_render_event_share_menu($event, 'inline');
check(!str_contains($inline, '<details') && str_contains($inline, '<button type="button"'), 'native keyboard controls');
check(str_contains($inline, 'type="button" hidden data-wp-seed-event-share-native'), 'SHARE_NATIVE hidden before progressive enhancement');
preg_match('/aria-controls="([^"]+)"/', $inline, $control);
check(str_contains($inline, 'id="'.$control[1].'" hidden data-wp-seed-event-share-panel') && str_contains($inline, 'aria-expanded="false"'), 'unique associated hidden fallback');
check(!str_contains($default, $control[1]), 'multiple component instances have unique panel IDs');
foreach (array('none'=>0, 'copy'=>1, 'email'=>1, 'both'=>2, 'invalid'=>0) as $option=>$count) {
 $html=wp_seed_events_render_event_share_menu($event, 'inline', array('secondary_actions'=>$option));
 $primary=explode('</div>', $html)[0];
 check(substr_count($primary, '<button ')+substr_count($primary, '<a ')===1+$count, 'explicit secondary actions: '.$option);
 check(str_contains($primary, 'data-wp-seed-event-share-copy')===in_array($option,array('copy','both'),true), 'copy option: '.$option);
 check(str_contains($primary, 'mailto:')===in_array($option,array('email','both'),true), 'email option: '.$option);
}
check(str_contains($inline, 'data-share-title="Été &amp; rencontre"') && str_contains($inline, 'disabled data-wp-seed-event-share-copy'), 'real escaped title and identifiable no-JS copy control');
check(str_contains($inline, 'role="status"') && str_contains($inline, 'aria-live="polite"'), 'accessible copy feedback');
check(str_contains($inline, 'x=1&amp;y=2') && str_contains($inline, 'aria-hidden="true"'), 'escaped URL and decorative icons');
check(wp_seed_events_render_event_share_menu(array(), 'inline')==='', 'empty event renders nothing');
$GLOBALS['wp_seed_events_template_share_context'] = array('event_id'=>42,'rendered'=>false);
check(normalize_ids(wp_seed_events_event_share_shortcode(array()))===normalize_ids($inline) && $GLOBALS['wp_seed_events_template_share_context']['rendered'], 'shortcode claims automatic share for matching template event');
check(substr_count(explode('</div>',wp_seed_events_event_share_shortcode(array('secondary_actions'=>'both')))[0], '<button ')===2, 'shortcode forwards secondary options');
check(str_contains($inline, '<noscript><a href="mailto:'), 'no-JS retains functional email instead of dead primary button');
$GLOBALS['wp_seed_events_template_share_context'] = array('event_id'=>99,'rendered'=>false);
wp_seed_events_event_share_shortcode(array('id'=>42));
check(!$GLOBALS['wp_seed_events_template_share_context']['rendered'], 'unrelated event cannot suppress template share');
wp_seed_events_event_share_shortcode(array('id'=>99));
check(!$GLOBALS['wp_seed_events_template_share_context']['rendered'], 'empty share cannot suppress automatic menu');
