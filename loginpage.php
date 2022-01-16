<?php
//
//
//

require_once(realpath(dirname(__FILE__).'/include/env_interface.php'));


$status = opensim_check_db();

$GRID_STATUS	  = $status['grid_status'];
$NOW_ONLINE 	  = $status['now_online'];
$LASTMONTH_ONLINE = $status['lastmonth_online'];
$USER_COUNT 	  = $status['user_count'];
$REGION_COUNT 	  = $status['region_count'];

header('pragma: no-cache');
include('./loginscreen.php');

