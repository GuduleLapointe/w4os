<?php if ( ! defined( 'W4OS_PLUGIN' ) ) {
	die;}
/*
 *  This file is part of WebAssets for OpenSimulator.
 *
 * WebAssets for OpenSimulator is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 *
 * WebAssets for OpenSimulator is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WebAssets for OpenSimulator.  If not, see <http://www.gnu.org/licenses/>.
 */

if ( ! $query_asset ) {
	$query_asset = $wp_query->query_vars['asset_uuid'];
}
if ( ! $query_format ) {
	$query_format = $wp_query->query_vars['asset_format'];
}
if ( ! $query_asset ) {
	die();
}

define( 'W4OS_ASSETS_CACHE_JP2', get_temp_dir() );
define( 'W4OS_ASSETS_CACHE_IMG_PATH', w4os_upload_dir( W4OS_ASSETS_CACHE_IMG_FOLDER ) . '/' );

/**
 * @brief Returns a default picture upon errors.
 *
 * @param format (string) file extension to return.
 * @return raws datas of image configured in inc/config.php
 * @author Anthony Le Mansec <a.lm@free.fr>
 */
function w4os_asset_get_zero( $format ) {
	$image = W4OS_NULL_KEY_IMG . '.png';
	if ( ! is_file( $image ) ) {
		$image = W4OS_NULL_KEY_IMG . '.' . $format;
	}
	if ( ! is_file( $image ) ) {
		die();
	}

	$h    = fopen( $image, 'rb' );
	$data = fread( $h, filesize( $image ) );
	fclose( $h );

	return ( $data );
}

/**
 * Returns raw image, in requested format. Also locally caches converted image.
 *
 * TODO : allow custom image width (resizing) with suitable caching directory
 *
 * @param integer $asset_uuid   Asset identifier, eg: "cb2052ae-d161-43e9-b11b-c834217823cd"
 * @param string  $format       Format as accepted by ImageMagick ("jpg"|"GIF"|"PNG"|...)
 * @return mixed                return image raw data in given format
 */
function w4os_asset_get( $asset_uuid, $format = W4OS_ASSETS_DEFAULT_FORMAT ) {

	/* Zero UUID : returns default pic */
	if ( empty( $asset_uuid ) || $asset_uuid == W4OS_NULL_KEY ) {
		return ( w4os_asset_get_zero( $format ) );
	}

	if ( w4os_cache_check( $asset_uuid . '.' . $format, W4OS_ASSETS_CACHE_IMG_PATH ) ) {
		$h = fopen( W4OS_ASSETS_CACHE_IMG_PATH . $asset_uuid . '.' . $format, 'rb' );
		if ( $h ) {
			$data = fread( $h, filesize( W4OS_ASSETS_CACHE_IMG_PATH . $asset_uuid . '.' . $format ) );
			fclose( $h );
			return ( $data );
		}
	}

	/*
	 * Get jp2 asset either from local cache or
	 * remote asset server :
	 */
	$is_cached = w4os_cache_check( $asset_uuid, W4OS_ASSETS_CACHE_JP2 );
	if ( ! $is_cached ) {
		$asset_url = W4OS_GRID_ASSETS_SERVER . $asset_uuid;

		$h = @fopen( $asset_url, 'rb' );
		if ( ! $h ) {
			return ( w4os_asset_get_zero( $format ) );
		}
		stream_set_timeout( $h, W4OS_ASSETS_SERVER_TIMEOUT );
		$file_content = stream_get_contents( $h );
		fclose( $h );
		try {
			$xml = new SimpleXMLElement( $file_content );
		} catch ( Exception $e ) {
			return ( w4os_asset_get_zero( $format ) );
		}

		// $ch = curl_init();
		// curl_setopt($ch, CURLOPT_URL, $asset_url);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		// $html = curl_exec($ch);
		// curl_close($ch);
		// try {
		// $xml = simplexml_load_string($html);
		// } catch (Exception $e) {
		// return (w4os_asset_get_zero($format));
		// }

		$data = base64_decode( $xml->Data );
		w4os_cache_write( $asset_uuid, $data, W4OS_ASSETS_CACHE_JP2 );
	} else {
		$h    = fopen( W4OS_ASSETS_CACHE_JP2 . $asset_uuid, 'rb' );
		$data = fread( $h, filesize( W4OS_ASSETS_CACHE_JP2 . $asset_uuid ) );
		fclose( $h );
	}

	/* Convert original jp2 image to requested format :  */
	$_img = new Imagick();
	$_img->readImageBlob( $data ); // TODO : error checking
	$_img->setImageFormat( $format ); // TODO : check for error

	if ( W4OS_ASSETS_DO_RESIZE ) {
		$original_height = $_img->getImageHeight();
		$original_width  = $_img->getImageHeight();
		$multiplier      = W4OS_ASSETS_RESIZE_FIXED_WIDTH / $original_width;
		$new_height      = $original_height * $multiplier;
		$_img->resizeImage( W4OS_ASSETS_RESIZE_FIXED_WIDTH, $new_height, Imagick::FILTER_CUBIC, 1 );
		// TODO : check for error
	}

	if ( ! $dump = $_img->getImageBlob() ) {
		$reason      = imagick_failedreason( $img );
		$description = imagick_faileddescription( $img );
		print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n";
		exit;
	}

	w4os_cache_write( $asset_uuid . '.' . $format, $dump, W4OS_ASSETS_CACHE_IMG_PATH );
	return ( $dump );
}

/**
 * @brief Checks whether given asset is locally cached in given cache directory.
 *
 * @param asset_id (string) Assetid to check
 * @param cachedir jpg2k / converted caching directory constant, as set in inc/config.php.
 * @return true if picture is cached in given directory, false otherwise.
 *
 * @author Anthony Le Mansec <a.lm@free.fr>
 */
function w4os_cache_check( $asset_uuid, $cachedir = W4OS_ASSETS_CACHE_JP2 ) {
	$cache_file   = $cachedir . $asset_uuid;
	$file_max_age = time() - W4OS_ASSETS_CACHE_TTL;
	if ( ! file_exists( $cache_file ) ) {
		return ( false );
	}
	if ( filemtime( $cache_file ) < $file_max_age ) {
		// expired, removing old file:
		unlink( $cache_file );
		return ( false );
	}

	return ( true );
}


/**
 * @brief Stores given picture to given cache directory.
 *
 * @param asset_id (string) UUID of the asset to store.
 * @param content (datas) raw image datas
 * @param cachedir local directory where to store image (as defined in inc/config.php)
 * @return false on error, true otherwise.
 */
function w4os_cache_write( $asset_uuid, $content, $cachedir = W4OS_ASSETS_CACHE_JP2 ) {
	$cache_file = $cachedir . $asset_uuid;
	$h          = fopen( $cache_file, 'wb+' );
	if ( ! $h ) {
		return ( false );
	}
	fwrite( $h, $content );
	fclose( $h );

	return ( true );
}

$format     = strtolower( preg_replace( '|^\.|', '', ( ! empty( $query_format ) ) ? $query_format : W4OS_ASSETS_DEFAULT_FORMAT ) );
$asset_uuid = preg_replace( '|/.*|', '', $query_asset );
$asset_raw  = w4os_asset_get( $asset_uuid, $format );

// TODO : set an array of mime types according to 'format' arg

Header( 'Content-type: image/' . $format );
echo $asset_raw;
die();
