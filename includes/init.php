<?php

$w4osdb = new WPDB(
	get_option('w4os_db_user'),
	get_option('w4os_db_pass'),
	get_option('w4os_db_database'),
	get_option('w4os_db_host')
);

function w4os_array2table($array, $class="") {
	if(empty($array)) return;
	while (list($key, $value) = each($array)) {
		$result.="<tr><td class=gridvar>" . __($key) . "</td><td class=gridvalue>$value</td></tr>";
	}
	if($result) $result="<table class='$class'>$result</table>";
	return $result;
}
