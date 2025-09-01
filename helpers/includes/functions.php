<?php
/**
 * economy.php
 *
 * Provides functions required by helpers
 *
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 */


/**
 * Format destination uri as a valid local or hypergrid link url
 *
 * @param  string  $uri      Destination uri, as "host:port:Region Name" or already formatted URL
 * @param  integer $format  The desired format as binary flags. Several values can be specified with an addition
 *                          e.g. TPLINK_V3HG + TPLINK_APPTP
 *                          TPLINK_LOCAL or 1:   secondlife://Region Name/x/y/z
 *                          TPLINK_HG or 2:      original HG format (obsolete?)
 *                          TPLINK_V3HG or 4:    v3 HG format (Singularity)
 *                          TPLINK_HOP or 8:     hop:// format (FireStorm)
 *                          TPLINK_TXT or 16:    host:port Region Name
 *                          TPLINK_APPTP or 32:  secondlife:///app/teleport link
 *                          TPLINK_MAP or 64:    (not implemented)
 *                          127:                      output all formats
 * @param  string  $sep      Separator for multiple formats, default new line
 * @return string
 */

/**
 * Use xmlrpc link_region method to request link_region data from robust
 *
 * @param  mixed  $args   region uri or sanitized region array
 * @param  string $var      output a single variable value
 * @return array (or string if var specified)
 */

/**
 * Build region URL from array
 *
 * @param  array $region sanitized region array
 * @return string
 */

/**
 * [oxXmlRequest description]
 *
 * @param  string $gatekeeper               [description]
 * @param  string $method                   [description]
 * @param  array  $request                  [description]
 * @return array             received xml response
 */

if( ! defined( 'NULL_KEY') ) define( 'NULL_KEY', '00000000-0000-0000-0000-000000000000' );
if( ! defined( 'TPLINK_LOCAL') ) {
	define( 'TPLINK_LOCAL', 1 ); // seconlife://Region/x/y/z
	define( 'TPLINK_HG', 2 ); // seconlife://yourgrid.org:8002 Region/x/y/z
	define( 'TPLINK_V3HG', 4 ); // the overcomplicated stuff!
	define( 'TPLINK_HOP', 8 ); // hop://yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_TXT', 16 ); // yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_APPTP', 32 ); // secondlife:///app/teleport/yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK_MAP', 64 ); // secondlife:///app/map/yourgrid.org:8002:Region/x/y/z
	define( 'TPLINK', pow( 2, 8 ) - 1 ); // all formats
	define( 'TPLINK_DEFAULT', TPLINK_HOP ); // default
}

function os_cache_get( $key, $default = null ) {
	global $oshelpers_cache;
	return isset( $oshelpers_cache[$key] ) ? $oshelpers_cache[$key] : $default;
}

function os_cache_set( $key, $value, $expire = 0 ) {
	global $oshelpers_cache;
	$oshelpers_cache[$key] = $value;
}

/**
 * OpenSim source to help further attempts to allow Hypergrid search results.

/**
 * OpenSim source to help further attempts to allow Hypergrid search results.
 * Infouuid is a fake parcelid resolving to region handle and (region-level?)
 * pos which might (or not) give enough information to allow hg results.
 * 1. Link region locally with link-region (or directly in db?)
 * 2. Use local link region handle (instead of remote one) to generate infouuid
 * 3. Use local link Global pos instead of remote one
 */
//
// public static UUID BuildFakeParcelID(ulong regionHandle, uint x, uint y)
// {
// byte[] bytes =
// {
// (byte)regionHandle, (byte)(regionHandle >> 8), (byte)(regionHandle >> 16), (byte)(regionHandle >> 24),
// (byte)(regionHandle >> 32), (byte)(regionHandle >> 40), (byte)(regionHandle >> 48), (byte)(regionHandle >> 56),
// (byte)x, (byte)(x >> 8), 0, 0,
// (byte)y, (byte)(y >> 8), 0, 0 };
// return new UUID(bytes, 0);
// }
//
// public static UUID BuildFakeParcelID(ulong regionHandle, uint x, uint y, uint z)
// {
// byte[] bytes =
// {
// (byte)regionHandle, (byte)(regionHandle >> 8), (byte)(regionHandle >> 16), (byte)(regionHandle >> 24),
// (byte)(regionHandle >> 32), (byte)(regionHandle >> 40), (byte)(regionHandle >> 48), (byte)(regionHandle >> 56),
// (byte)x, (byte)(x >> 8), (byte)z, (byte)(z >> 8),
// (byte)y, (byte)(y >> 8), 0, 0 };
// return new UUID(bytes, 0);
// }
