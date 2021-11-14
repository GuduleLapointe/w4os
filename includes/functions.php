<?php if ( ! defined( 'WPINC' ) ) die;

function w4os_array2table($array, $class="") {
	if(empty($array)) return;
	$result="";
	foreach($array as $key => $value) {
		$result.="<tr><td class=gridvar>" . __($key, 'w4os') . "</td><td class=gridvalue>$value</td></tr>";
	}
	if(!empty($result)) {
		$result="<table class='$class'>$result</table>";
	}
	return $result;
}

function w4os_notice ($message, $status="") {
  echo "<div class='notice notice-$status'><p>$message</p></div>";
}

function w4os_gen_uuid() {
 $uuid = array(
  'time_low'  => 0,
  'time_mid'  => 0,
  'time_hi'  => 0,
  'clock_seq_hi' => 0,
  'clock_seq_low' => 0,
  'node'   => array()
 );

 $uuid['time_low'] = mt_rand(0, 0xffff) + (mt_rand(0, 0xffff) << 16);
 $uuid['time_mid'] = mt_rand(0, 0xffff);
 $uuid['time_hi'] = (4 << 12) | (mt_rand(0, 0x1000));
 $uuid['clock_seq_hi'] = (1 << 7) | (mt_rand(0, 128));
 $uuid['clock_seq_low'] = mt_rand(0, 255);

 for ($i = 0; $i < 6; $i++) {
  $uuid['node'][$i] = mt_rand(0, 255);
 }

 $uuid = sprintf('%08x-%04x-%04x-%02x%02x-%02x%02x%02x%02x%02x%02x',
  $uuid['time_low'],
  $uuid['time_mid'],
  $uuid['time_hi'],
  $uuid['clock_seq_hi'],
  $uuid['clock_seq_low'],
  $uuid['node'][0],
  $uuid['node'][1],
  $uuid['node'][2],
  $uuid['node'][3],
  $uuid['node'][4],
  $uuid['node'][5]
 );

 return $uuid;
}

function w4os_admin_notice($notice, $class='info', $dismissible=true ) {
  if(empty($notice)) return;
  // $class="success";
  if($dismissible) $is_dismissible = 'is-dismissible';
  add_action( 'admin_notices', function() use ($notice, $class, $is_dismissible) {
    ?>
    <div class="notice notice-<?=$class?> <?=$is_dismissible?>">
        <p><strong><?php echo W4OS_PLUGIN_NAME; ?></strong>: <?php _e( $notice, 'band-tools' ); ?></p>
    </div>
    <?php
  } );
}

function w4os_fast_xml($url) {
	// Exit silently if required php modules are missing
	if ( ! function_exists('curl_init') ) return NULL;
	if ( ! function_exists('simplexml_load_string') ) return NULL;

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$html = curl_exec($ch);
	curl_close($ch);
	$xml = simplexml_load_string($html);
	return $xml;
}

function w4os_update_grid_info() {
	if(defined('W4OS_GRID_INFO_CHECKED')) return;
	define('W4OS_GRID_INFO_CHECKED', true);
	$local_uri = 'http://localhost:8002';
	$check_login_uri = ( get_option('w4os_login_uri') ) ? 'http://' . get_option('w4os_login_uri') : $local_uri ;
	$check_login_uri = preg_replace('+http://http+', 'http', $check_login_uri);
	// $xml = simplexml_load_file($check_login_uri . '/get_grid_info');
	$xml = w4os_fast_xml($check_login_uri . '/get_grid_info');

	if(!$xml) return false;
	if($check_login_uri == $local_uri) w4os_admin_notice(__('A local Robust server has been found. Please check Login URI and Grid name configuration.', 'w4os'), 'success');

	$grid_info = (array) $xml;
	if ( ! empty($grid_info['login']) ) update_option('w4os_login_uri', preg_replace('+/*$+', '', preg_replace('+https*://+', '', $grid_info['login'])));
	if ( ! empty($grid_info['gridname']) ) update_option('w4os_grid_name', $grid_info['gridname']);
}
