<?php
/**
 * OpenSimulator Helpers
 *
 * The index file is only intended to disallow directory listing.
 * 
 * @package		magicoli/opensim-helpers
 * @author 		Gudule Lapointe <gudule@speculoos.world>
 * @link 			https://github.com/magicoli/opensim-helpers
 * @license		AGPLv3
 */

// Get version from .version file.
if( file_exists( '.version' ) ) {
    $version = file_get_contents( '.version' );
}

// Get version from .git/HEAD file.
if( file_exists( '.git/HEAD' ) ) {
    $version .= ' (git ' . trim(preg_replace('%.*/%', '', file_get_contents( '.git/HEAD' ) ) ) . ')';
}
echo $version;
die();
