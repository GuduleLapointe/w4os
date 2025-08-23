<?php
/**
 * guide.php
 *
 * Provide a destination guide for V3 viewers.
 *
 * Requires OpenSimulator Search module
 *   [OpenSimSearch](https://github.com/kcozens/OpenSimSearch)
 * Events need to be fetched with a separate script, from an HYPEvents server
 *
 * @package    magicoli/opensim-helpers
 * @subpackage    magicoli/opensim-helpers/guide
 * @author     Gudule Lapointe <gudule@speculoos.world>
 * @link       https://github.com/magicoli/opensim-helpers
 * @license    AGPLv3
 */

require_once __DIR__ . '/bootstrap.php';

if($destinations_guide = new OpenSim_Helpers_Guide()) {
    // If the guide is enabled, we can proceed
    $destinations_guide->output_page();
} else {
    // If the guide is not enabled, we return a 404 error
    header('HTTP/1.0 404 Not Found');
    echo 'Destination guide is not enabled.';
    exit;
}
