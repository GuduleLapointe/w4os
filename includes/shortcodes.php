<?php

function w4os_shortcodes_init()
{
	function w4os_gridinfo_shortcode($atts = [], $content = null)
	{
		if(! W4OS_DB_CONNECTED) {
			return;
		}
		// Gridinfo: http://robust.server:8002/get_grid_info
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid info");
		empty($content) ? : $content="<div>$content</div>";
		$content="<h4>$title</h4>$content";
		$info=array(
			__("Grid name", 'w4os') => get_option('w4os_grid_name'),
			__("Login URI", 'w4os') => get_option('w4os_login_uri'),
		);
		if(!empty($info)) {
			$content .= w4os_array2table($info);
		} else {
			$content .= __("OpenSimulator not configured", 'w4os');
		}
		return $content;
	}
	add_shortcode('gridinfo', 'w4os_gridinfo_shortcode');

	function w4os_gridstatus_shortcode($atts = [], $content = null)
	{
		if(! W4OS_DB_CONNECTED) {
			return;
		}
		global $w4osdb;
		global $wp_locale;
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid status");
		if(!empty($content)) $content="<div>$content</div>";

		$content="<h4>$title</h4>$content";

		// $cached="in cache";
		$status = wp_cache_get( 'gridstatus', 'w4os' );
		if (false === $status) {
			// $cached="uncached";
			if($w4osdb -> check_connection())
			{
				$lastmonth=time() - 30*86400;

				$urlinfo=explode(":", get_option('w4os_login_uri'));
				$host=$urlinfo['0'];
				$port=$urlinfo['1'];
				$fp = @fsockopen($host, $port, $errno, $errstr, 1.0);
				if ($fp) {
					$gridonline = __("Yes");
				} else {
					$gridonline=__("No");
				}
				$status = array(
					__('World online', 'w4os') => $gridonline,
					__('Citizens', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM UserAccounts" )),
					__('Citizens in world', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM Presence AS p, UserAccounts AS u
					WHERE RegionID != '00000000-0000-0000-0000-000000000000'
					AND p.UserID = u.PrincipalID;" )),
					// 'Active citizens (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					// FROM GridUser as g, UserAccounts as u WHERE g.UserID = u.PrincipalID AND Login > $lastmonth" )),
					'Users in world' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM Presence
					WHERE RegionID != '00000000-0000-0000-0000-000000000000';	")),
					'Active users (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM GridUser WHERE Login > $lastmonth" )),
					// 'Known users' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					// FROM GridUser")),
					// 'Known online users' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					// FROM GridUser WHERE Online = 'true'")),
					__('Regions', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM Regions")),
					// 'Total area (m²)' => number_format_i18n($w4osdb->get_var("SELECT sum(sizex * sizey)
					// FROM regions") . "km²", 2),
					__('Total area (km²)', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT round(sum(sizex * sizey / 1000000),2)
					FROM regions") . "km²", 2),
				);
			}
			wp_cache_add( 'gridstatus', $status, 'w4os');
		}
		$result=w4os_array2table($status);

		if(empty($result)) $result=__("No result") ;
		return $content . $result;
	}
	add_shortcode('gridstatus', 'w4os_gridstatus_shortcode');
}
add_action('init', 'w4os_shortcodes_init');
