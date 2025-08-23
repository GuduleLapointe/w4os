<?php
/**
 * Robust class, currently an alias of OpenSim class.
 * 
 * This class will be designed to interact exclusively with the Robust grid server,
 * while Simulator class will interact with region simulators, and OpenSim class
 * will be the main class for all kinds of instances (grids, simulators, assets, ...).
 * 
 * Even if it's only an alias of OpenSim class at the moment, it must already
 * be used in the codebase for Robust-specific interactions to ensure
 * compatibility with future changes.
 */

if (!defined('OPENSIM_ENGINE_PATH')) {
    exit;
}

class OpenSim_Robust extends OpenSim {
    function __construct() {
        // Call parent constructor
        parent::__construct();
    }
}
