<?php

namespace ET\Builder\Framework\DependencyManagement\Interfaces {
	interface DependencyInterface {
		public function load();
	}
}

namespace ET\Builder\Framework\Utility {
	class HTMLUtility {
		public static function render( $args ) {
			return '<' . $args['tag'] . ' class="et_pb_module_inner">' . $args['children'] . '</' . $args['tag'] . '>';
		}
	}
}

namespace ET\Builder\FrontEnd\Module {
	class Style {
		public static function add( $args ) {
			$GLOBALS['share_styles'][] = $args;
		}
	}
}

namespace ET\Builder\Packages\Module {
	class Module {
		public static function render( $args ) {
			$GLOBALS['share_render_args'] = $args;
			return $args['children'];
		}
	}
}

namespace ET\Builder\Packages\Module\Options\Element {
	class ElementClassnames {
		public static function classnames( $args ) {
			return 'decorated';
		}
	}
}

namespace ET\Builder\Packages\ModuleLibrary {
	class ModuleRegistration {
		public static $registrations = array();

		public static function register_module( $path, $args ) {
			self::$registrations[] = array( $path, $args );
		}
	}
}

namespace ET\Builder\Packages\IconLibrary\IconFont { class Utils { public static function process_font_icon($s) { return html_entity_decode($s['unicode']); } } }
namespace {
define('ABSPATH', __DIR__);
function plugins_url($p,$f) { return 'https://example.org/wp-content/plugins/wp-seed-events/'.$p; }
function wp_enqueue_style($h,$u,$d,$v) { $GLOBALS['assets'][$h]=$u; }
function wp_enqueue_script($h,$u,$d,$v,$footer) { $GLOBALS['assets'][$h]=$u; }
function add_action($h,$c) { $GLOBALS['hooks'][$h][]=$c; }
function esc_attr($s) { return htmlspecialchars((string)$s,ENT_QUOTES); }
function esc_url($s) { return esc_attr($s); }
function esc_url_raw($s) { return $s; }
function esc_html__($s,$d) { return esc_attr($s); }
function __($s,$d='') { return $s; }
function wp_strip_all_tags($s) { return strip_tags($s); }
function sanitize_key($s) { return preg_replace('/[^a-z0-9_-]/','',strtolower($s)); }
function sanitize_html_class($s) { return preg_replace('/[^a-zA-Z0-9_-]/','',$s); }
function sanitize_text_field($s) { return trim(strip_tags($s)); }
function absint($s) { return abs((int)$s); }
function wp_seed_events_public_boolean_option($v,$default=true) { return !in_array($v,array(false,0,'off','0','no'),true); }
function wp_seed_events_divi_get_module_event_context($a,$b) { return array(); }
function wp_seed_events_divi_resolve_event_id($c) { return 41; }
function wp_seed_events_get_event_data($id) { return $GLOBALS['event']; }
function wp_script_is($h,$s) { return $GLOBALS['enqueued']; }
function wp_style_is($h,$s) { return $GLOBALS['enqueued']; }
require __DIR__.'/../includes/public/sharing.php';
require __DIR__.'/../includes/integrations/divi/class-event-share-module.php';
$event=array('id'=>41,'title'=>'Été & rencontre','url'=>'https://example.org/event/?a=1&b=2');
$GLOBALS['event']=$event;
$block=(object)array('parsed_block'=>array(),'block_type'=>(object)array('name'=>'wp-seed-events/event-share','category'=>'module'));
$elements=new class { public function style_components($a) { return ''; } };
$attrs=array('content'=>array('innerContent'=>array('desktop'=>array('value'=>array('label'=>'Partager','copy_label'=>'Copier le lien','email_label'=>'E-mail','show_copy'=>'on','show_email'=>'on')))));
$render=static function($attrs) use($block,$elements) { return WP_Seed_Events_Divi_Event_Share_Module::render_callback($attrs,'',$block,$elements); };
$out=array('default'=>wp_seed_events_render_event_share_menu($event),'legacy'=>wp_seed_events_render_event_share_menu($event,array()),'layout'=>wp_seed_events_render_event_share_menu($event,'inline'),'divi'=>$render($attrs));
$attrs['shareActionStyle']['decoration']['button']['desktop']['value']['icon']=array('enable'=>'on','settings'=>array('unicode'=>'&#xe001;'),'placement'=>'left','onHover'=>'off');
$attrs['content']['innerContent']['desktop']['value']['secondary_actions']='both';
$out['decorated']=$render($attrs);
$out['override']=wp_seed_events_render_event_share_menu($event,array('secondary_actions'=>'both'),array('secondary_actions'=>'none'));
$out['malformed']=wp_seed_events_render_event_share_menu($event,array('label'=>array(),'action_order'=>array(),'action_attributes'=>array('share'=>array('class'=>'custom','onclick'=>'alert(1)','data-share-url'=>'https://invalid.test','aria-label'=>'A "quote"'))));
$GLOBALS['enqueued']=false; ob_start(); wp_seed_events_render_public_share_script(); $out['footer']=ob_get_clean();
$GLOBALS['enqueued']=true; ob_start(); wp_seed_events_render_public_share_script(); $out['legacy_footer']=ob_get_clean();
WP_Seed_Events_Divi_Event_Share_Module::enqueue_styles();
$out['assets']=$GLOBALS['assets'];
$out['hooks']=$GLOBALS['hooks'];
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
}
