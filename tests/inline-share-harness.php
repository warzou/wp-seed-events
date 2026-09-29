<?php
define('ABSPATH', __DIR__);
function esc_url_raw($s) { return $s; }
function esc_url($s) { return htmlspecialchars($s, ENT_QUOTES); }
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
$legacy = wp_seed_events_render_event_share_menu($event);
check(str_contains($legacy, '<details') && str_contains($legacy, 'Envoyer par email'), 'default Divi menu preserved');
check(wp_seed_events_render_event_share_menu($event, 'invalid')===$legacy, 'unknown layout falls back to menu');
$inline = wp_seed_events_render_event_share_menu($event, 'inline');
check(!str_contains($inline, '<details') && str_contains($inline, '<button type="button"'), 'inline actions directly visible and native keyboard controls');
check(str_contains($inline, 'role="status"') && str_contains($inline, 'aria-live="polite"'), 'accessible copy feedback');
check(str_contains($inline, 'x=1&amp;y=2') && str_contains($inline, 'aria-hidden="true"'), 'escaped URL and decorative icons');
check(wp_seed_events_render_event_share_menu(array(), 'inline')==='', 'empty event renders nothing');
$GLOBALS['wp_seed_events_template_share_context'] = array('event_id'=>42,'rendered'=>false);
check(wp_seed_events_event_share_shortcode(array())===$inline && $GLOBALS['wp_seed_events_template_share_context']['rendered'], 'shortcode claims automatic share for matching template event');
$GLOBALS['wp_seed_events_template_share_context'] = array('event_id'=>99,'rendered'=>false);
wp_seed_events_event_share_shortcode(array('id'=>42));
check(!$GLOBALS['wp_seed_events_template_share_context']['rendered'], 'unrelated event cannot suppress template share');
wp_seed_events_event_share_shortcode(array('id'=>99));
check(!$GLOBALS['wp_seed_events_template_share_context']['rendered'], 'empty share cannot suppress automatic menu');
