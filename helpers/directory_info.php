<?php
/**
 * OpenSimulator Helpers installationn script
 * 
 * This script will scan Robust configuration file to get your grid settings and generate the helpers configuration file.
 * 
 * It is only needed to run this tool once, after that you delete this install.php file.
 * 
 * @package		magicoli/opensim-helpers
**/

if ( __FILE__ !== $_SERVER['SCRIPT_FILENAME'] ) {
    // The file must only be called directly.
    http_response_code(403);
    exit( "I'm not that kind of girl, I don't want to be included." );
}

require_once( __DIR__ . '/classes/init.php' ); // Common to all main scripts
require_once( __DIR__ . '/classes/class-page.php' ); // Specific, because we generate a page
// require_once( __DIR__ . '/classes/class-form.php' ); // Specific, because we use forms
require_once( __DIR__ . '/includes/search.php' );

$site_title = "2do directory";
$page = new OpenSim_Page( 'Directory Info' );
$content = "";

$query = $SearchDB->prepareAndExecute(
    'SELECT * FROM parcels
    INNER JOIN ' . SEARCH_REGION_TABLE . ' AS r ON parcels.regionUUID = r.regionUUID',
);
$registered_parcels = $query->fetchAll(PDO::FETCH_ASSOC);
$grids_info = osdb_cache_get( 'grids_info', array() );

// Calculate stats per gatekeeperURL
$stats = [];
foreach ($registered_parcels as $host) {
    $gatekeeperURL = $host['gatekeeperURL'];
    $grids_info[$gatekeeperURL] = $grids_info[$gatekeeperURL] ?? OpenSim_Grid::get_grid_info($gatekeeperURL);
    if (!isset($stats[$gatekeeperURL])) {
        $stats[$gatekeeperURL] = [
            'regions' => [],
            'parcels' => 0,
        ];
    }
    $stats[$gatekeeperURL]['parcels']++;
    $region = $host['regionUUID'];
    $stats[$gatekeeperURL]['regions'][$region] = true;
}

osdb_cache_set( 'grids_info', $grids_info, 86400 );

$grid_count = count($stats);
$allregionscount = 0;
$allparcelscount = 0;
foreach ($stats as $gatekeeperURL => $data) {
    $regionCount = count($data['regions']);
    $allregionscount += $regionCount;
    $parcelCount = $data['parcels'];
    $allparcelscount += $parcelCount;
    $gridname = $grids_info[$gatekeeperURL]['gridname'] ?? $gatekeeperURL;
    $stats_display .= sprintf(
        '<a href="?gatekeeperURL=%s">%s</a> (%d regions, %d parcels) &nbsp;&nbsp;',
        $gatekeeperURL,
        $gridname,
        $regionCount,
        $parcelCount
    );
    
    // "$gatekeeperURL ($regionCount regions, $parcelCount parcels) &nbsp;&nbsp;";
}

$content = sprintf( 
    '<strong>%d grids</strong>, %d regions, %d parcels ',
    $grid_count,
    $allregionscount,
    $allparcelscount
);
$content .= $stats_display;
// $content .= "<p>Grid info cache: <pre>" . print_r($grids_info, true) . "</pre></p>";

if(! empty($debug)) {
    $content .= '<div class=debug>&nbsp;<p><strong>Debug data:</strong></p> <pre>' . print_r( $debug, true ) . '</pre></div>';
}

$sidebar_right= "Right";
$sidebar_left = "Left";

// $content = $page->get_content();

// Last step is to load template to display the page.
require( 'templates/templates.php' );
