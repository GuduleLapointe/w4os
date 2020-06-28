<?php
/**
 * Shortcodes
 *
 * @package	w4os
 * @author Olivier van Helden <olivier@van-helden.net>
 */

/**
 * Initialize w4os shortcodes
 * @return [type] [description]
 */
function w4os_shortcodes_init()
{
	/**
	 * Grid info shortcode
	 * @param  array  $atts    [description]
	 * @param  string $content html
	 * @return string          html
	 */
	function w4os_gridinfo_shortcode($atts = [], $content = null)
	{
		if(! W4OS_DB_CONNECTED) {
			return;
		}
		// Gridinfo: http://robust.server:8002/get_grid_info
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid info", 'w4os');
		empty($content) ? : $content="<div>$content</div>";
		$content="<h4>$title</h4>$content";
		$info=array(
			__("Grid name", 'w4os') => get_option('w4os_grid_name'),
			__("Login URI", 'w4os') => get_option('w4os_login_uri'),
		);
		if(!empty($info)) {
			$content .= w4os_array2table($info, 'gridinfo');
		} else {
			$content .= __("OpenSimulator not configured", 'w4os');
		}
		return $content;
	}
	add_shortcode('gridinfo', 'w4os_gridinfo_shortcode');

	/**
	 * Grid status shortcode
	 * @param  array  $atts    [description]
	 * @param  string $content html
	 * @return string          html
	 */
	function w4os_gridstatus_shortcode($atts = [], $content = null)
	{
		if(! W4OS_DB_CONNECTED) {
			return;
		}
		global $w4osdb;
		global $wp_locale;
		isset($atts['title']) ? $title=$atts['title'] : $title=__("Grid status", 'w4os');
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
				if(get_option('w4os_exclude_models')) {
					$filter .= "u.FirstName != '" . get_option('w4os_model_firstname') . "'"
					. " AND u.LastName != '" . get_option('w4os_model_lastname') . "'";
				}
				if(get_option('w4os_exclude_nomail')) {
					$filter .= " AND u.Email != ''";
				}
				if($filter) $filter = "$filter AND ";
				$status = array(
					__('Grid online', 'w4os') => $gridonline,
					__('Members', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM UserAccounts as u WHERE $filter active=1" )),
					__('Members in world', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					FROM Presence AS p, UserAccounts AS u
					WHERE $filter RegionID != '00000000-0000-0000-0000-000000000000'
					AND p.UserID = u.PrincipalID;" )),
					// 'Active citizens (30 days)' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
					// FROM GridUser as g, UserAccounts as u WHERE g.UserID = u.PrincipalID AND Login > $lastmonth" )),
					'Total users in world' => number_format_i18n($w4osdb->get_var("SELECT COUNT(*)
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
					__('Total area', 'w4os') => number_format_i18n($w4osdb->get_var("SELECT round(sum(sizex * sizey / 1000000),2)
					FROM regions"), 2)  . "&nbsp;km²",
				);
			}
			wp_cache_add( 'gridstatus', $status, 'w4os');
		}
		$result=w4os_array2table($status, 'gridstatus');

		if(empty($result)) $result=__("No result", 'w4os') ;
		return $content . $result;
	}
	add_shortcode('gridstatus', 'w4os_gridstatus_shortcode');

	function w4os_newusers_shortcode($atts = [], $content = null)
	{
		return w4os_newusers();
	}
	add_shortcode('w4os_newusers_shortcode', 'w4os_newusers');
}
add_action('init', 'w4os_shortcodes_init');

function w4os_newusers() {
	if(! W4OS_DB_CONNECTED) {
		return;
	}
	global $wpdb;
	$recentusers = '<ul class="recent-users">';
	$usernames = $wpdb->get_results("SELECT user_nicename, user_url, user_email FROM $wpdb->users ORDER BY ID DESC LIMIT 5");
	foreach ($usernames as $username) {
		$user = $wpdb->get_row($wpdb->prepare("select * from ".$wpdb->prefix."users where user_email = %s", $username->user_email));
		$uuid = get_the_author_meta( 'w4os_uuid', $user->ID );
		if($uuid) {
			$recentusers .= '<li><span class=profilepic>' .get_avatar($username->user_email, 32) . "</span>"
			. " <span class=avatar-name>" . get_the_author_meta( 'w4os_firstname', $user->ID ) . " " . get_the_author_meta( 'w4os_lastname', $user->ID ) . "</span>"
			. " <span class=nicename> ($username->user_nicename)</span>"
			. " <span class=email>$username->user_email</span>"
			 ."</li>";
		} else if (!$username->user_url) {
			$recentusers .= '<li>' .get_avatar($username->user_email, 32) . "&nbsp;" . $username->user_nicename."</a></li>";
		} else {
			$recentusers .= '<li>' .get_avatar($username->user_email, 32) . "&nbsp;" . '<a href="'.$username->user_url.'">'.$username->user_nicename."</a></li>";
		}
	}
	$recentusers .= '</ul>';
	return $recentusers;
}
