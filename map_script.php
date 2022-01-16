<?php
/////////////////////////////////////////////////////////////////////////////////
//
// Modified from OpenSim WebInterface Redux v0.28
//													by Fumi.Iseki
//
//
// $size, $centerX, $centerY, $world_map_url, ENV_HELPER_PATH, $course_id are needed
//

//
require_once('include/env_interface.php');
require_once('include/opensim.mysql.php');

//
$display_marker = 'dr';	// infomation marker

if ($size==16){
	$minuszoom = 0;   $pluszoom = 32;  $infosize = 8;
}
else if ($size==32){
	$minuszoom = 16;  $pluszoom = 64;  $infosize = 10;
}
else if ($size==64){
	$minuszoom = 32;  $pluszoom = 128; $infosize = 12;
}
else if ($size==128) {
	$minuszoom = 64;  $pluszoom = 256; $infosize = 20;
}
else if ($size==256) {
	$minuszoom = 128; $pluszoom = 512; $infosize = 40;
}
else if ($size==512) {
	$minuszoom = 256; $pluszoom = 0;   $infosize = 60;
}

?>

function regionwin(uuid) {
	window.open("<?php echo CMS_MODULE_URL.'/helper/sim.php?course='.$course_id.'&region='?>"+uuid, null,
					'toolbar=no,location=no,directories=no,status=no,menubar=no,scrollbars=yes,resizable=no,copyhistory=no,width=800,height=450');
}


function loadmap() {
	mapInstance = new ZoomSize(<?php echo $size?>);
	mapInstance = new WORLDMap(document.getElementById('map-container'), {hasZoomControls: false, hasPanningControls: true});
	mapInstance.centerAndZoomAtWORLDCoord(new XYPoint(<?php echo $centerX?>, <?php echo $centerY?>), 1);
<?php
	$infos = opensim_get_regions_infos();
	foreach($infos as $info)
	{
		$regionName = $info['regionName'];
		$serverURI  = $info['serverURI'];
		$serverIP   = $info['serverIP'];
		$serverPort = $info['serverPort'];
		$locX 		= $info['locX'];
		$locY 		= $info['locY'];
		$sizeX 		= $info['sizeX'];
		$sizeY 		= $info['sizeY'];
		$uuid 		= $info['UUID'];

		$dx = 0.00; $dy = 0.00;
		if ($display_marker=='tl') {
			$dx = -0.40; 	$dy = 0.40;
		}
		else if ($display_marker=='tr') {
			$dx = 0.40; 	$dy = 0.40;
		}
		else if ($display_marker=='dl') {
			$dx = -0.40; 	$dy = -0.40;
		}
		else if ($display_marker=='dr') {
			$dx = 0.40; 	$dy = -0.40;
		}

		$rgnX = $size*($sizeX/256);
		$rgnY = $size*($sizeY/256);
		$locX = $locX/256;
		$locY = $locY/256;
		$crdX = $locX + ($sizeX/256-1)*0.5;
		$crdY = $locY + ($sizeY/256-1)*0.5;
		$mrkcrdX = $crdX + $dx*($sizeX/256);
		$mrkcrdY = $crdY + $dy*($sizeY/256);

		$server = '';
		if ($serverURI!='') {
    		$dec = explode(':', $serverURI);
    		if (!strncasecmp($dec[0], 'http', 4)) $server = $dec[0].':'.$dec[1];
		}
		if ($server=='') {
    		$server = 'http://'.$serverIP;
		}
		$server = $server.':'.$serverPort;

		//
		$imageuuid = str_replace('-', '', $uuid);
	  	$imageURL = $server.'/index.php?method=regionImage'.$imageuuid;

		$windowHTML = 'Name: <a style=\"cursor:pointer\" onClick=\"regionwin(\''.$uuid.'\')\"><b><u>'.$regionName.'</u></b></a><br /><br />';
		/*if ($hasPermit) {
			$windowHTML.= 'UUID: <b>'.$uuid.'</b><br /><br />';
			$windowHTML.= 'IP address: <b>'.$serverIP.'</b><br /><br />';
		}*/
		$windowHTML.= 'Coordinates: <b>'. $locX.', '.$locY.'</b><br /><br />';
		$windowHTML.= 'Estate Name : <b>'.$info['estate_name'].'</b><br /><br />';
		$windowHTML.= 'Estate Owner: <b>'.$info['est_fullname'].'</b><br />';

?>
	  	var tmp_region_image = new Img("<?php echo $imageURL?>", <?php echo $rgnX?>, <?php echo $rgnY?>);
		var region_loc = new Icon(tmp_region_image);
		var all_images = [region_loc, region_loc, region_loc, region_loc, region_loc, region_loc];
		var marker = new Marker(all_images, new XYPoint(<?php echo $crdX?>, <?php echo $crdY?>));
		mapInstance.addMarker(marker);

		var map_marker_img = new Img("images/info.gif", <?php echo $infosize?>, <?php echo $infosize?>);
		var map_marker_icon = new Icon(map_marker_img);
		var mapWindow = new MapWindow("<?php echo $windowHTML?>", {closeOnMove: true});
		var all_images = [map_marker_icon, map_marker_icon, map_marker_icon, map_marker_icon, map_marker_icon, map_marker_icon];
		var marker = new Marker(all_images, new XYPoint(<?php echo $mrkcrdX?>, <?php echo $mrkcrdY?>));
		mapInstance.addMarker(marker, mapWindow);
<?php
	}
?>
}


function setZoom(size) {
	var cord = mapInstance.getMapCenter();
	window.location.href = "<?php echo $world_map_url?>?size="+size+"&ctX="+cord.x+"&ctY="+cord.y+"";
}
