<?php
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

$query_asset = $wp_query->query_vars['asset_uuid'];
$query_format = $wp_query->query_vars['asset_format'];
if( ! $query_asset) die();

define('W4OS_ASSETS_SERVER_TIMEOUT', 8); // timeout in seconds, to wait while requesting an asset (default to 8)
define('W4OS_ASSETS_DO_RESIZE', false); // shall we resize picture to width=W4OS_ASSETS_RESIZE_FIXED_WIDTH ?
define('W4OS_ASSETS_RESIZE_FIXED_WIDTH', 256); // width in pixels

/* Will show following image if no asset was requested (malformed query) : */
// define('W4OS_ASSETS_ID_NOTFOUND', 'cb2052ae-d161-43e9-b11b-c834217823cd');
define('W4OS_ASSETS_ID_NOTFOUND', '201ce950-aa38-46d8-a8f1-4396e9d6be00');

/* will show following picture for Zero UUID (not found / malformed assets) : */
define('IMAGE_ID_ZERO', dirname(dirname(__FILE__)) . '/images/assets-no-img'); // no extension here

define('IMAGE_DEFAULT_FORMAT', 'JPEG');

/* Re-use locally cached pictures (jp2k & converted) for 1 day before re-requesting it : */
define('CACHE_MAX_AGE', 86400); // 1 day

/* where to store cached pictures ? (user running your webserver needs write-permissions there : */
define('JP2_CACHE_DIR', get_temp_dir() );
define('PIC_CACHE_DIR', get_temp_dir() );

/**
 * @brief Returns a default picture upon errors.
 *
 * @param format (string) file extension to return.
 * @return raws datas of image configured in inc/config.php
 * @author Anthony Le Mansec <a.lm@free.fr>
 */
function asset_get_zero($format) {
	$image=IMAGE_ID_ZERO . ".png";
	if(!is_file($image)) $image=IMAGE_ID_ZERO.".".$format;
	if(!is_file($image)) die();

	$h = fopen($image, "rb");
	$data = fread($h, filesize($image));
	fclose($h);

	return ($data);
}

/**
 * @brief Returns raw image, in requested format. Also locally caches converted image.
 *
 * @param asset_id (string) Asset identifier, eg: "cb2052ae-d161-43e9-b11b-c834217823cd"
 * @param format (string) Format as accepted by ImageMagick ("JPEG"|"GIF"|"PNG"|...)
 * @return image raw datas, in given format.
 * TODO : allow custom image width (resizing) with suitable caching directory
 */
function asset_get($asset_uuid, $format='jpeg') {

	/* Zero UUID : returns default pic */
	if ( empty($asset_uuid) || $asset_uuid == W4OS_NULL_KEY ) {
		return (asset_get_zero($format));
	}

	if (w4os_cache_check($asset_uuid.".".$format, PIC_CACHE_DIR)) {
		$h = fopen(PIC_CACHE_DIR.$asset_uuid.".".$format, "rb");
		if ($h) {
			$data = fread($h, filesize(PIC_CACHE_DIR.$asset_uuid.".".$format));
			fclose ($h);
			return ($data);
		}
	}

	/*
	 * Get jp2 asset either from local cache or
	 * remote asset server :
	 */
	$is_cached = w4os_cache_check($asset_uuid, JP2_CACHE_DIR);
	if (!$is_cached) {
		$asset_url = W4OS_ASSETS_SERVER . $asset_uuid;
		$h = @fopen($asset_url, "rb");
		if (!$h) {
			return (asset_get_zero($format));
		}
		stream_set_timeout($h, W4OS_ASSETS_SERVER_TIMEOUT);
		$file_content = stream_get_contents($h);
		fclose($h);
		try {
			$xml = new SimpleXMLElement($file_content);
		} catch (Exception $e) {
			return (asset_get_zero($format));
		}
		$data = base64_decode($xml->Data);
		w4os_cache_write($asset_uuid, $data, JP2_CACHE_DIR);
	} else {
		$h = fopen(JP2_CACHE_DIR.$asset_uuid, "rb");
		$data = fread($h, filesize(JP2_CACHE_DIR.$asset_uuid));
		fclose($h);
	}

	/* Convert original jp2 image to requested format :  */
	$_img = new Imagick();
	$_img->readImageBlob($data); // TODO : error checking
	$_img->setImageFormat($format); // TODO : check for error

	if (W4OS_ASSETS_DO_RESIZE) {
		$original_height = $_img->getImageHeight();
		$original_width = $_img->getImageHeight();
		$multiplier = W4OS_ASSETS_RESIZE_FIXED_WIDTH / $original_width;
		$new_height = $original_height * $multiplier;
		$_img->resizeImage(W4OS_ASSETS_RESIZE_FIXED_WIDTH, $new_height, Imagick::FILTER_CUBIC, 1);
		// TODO : check for error
	}

	if (! $dump = $_img->getImageBlob()) {
		$reason      = imagick_failedreason( $img ) ;
		$description = imagick_faileddescription( $img ) ;
		print "handle failed!<BR>\nReason: $reason<BR>\nDescription: $description<BR>\n" ;
		exit ;
	}

	w4os_cache_write($asset_uuid.".".$format, $dump, PIC_CACHE_DIR);
	return ($dump);
}

/**
 * @brief Checks whether given asset is locally cached in given cache directory.
 *
 * @param asset_id (string) Assetid to check
 * @param cachedir jpeg2k / converted caching directory constant, as set in inc/config.php.
 * @return true if picture is cached in given directory, false otherwise.
 *
 * @author Anthony Le Mansec <a.lm@free.fr>
 */
function w4os_cache_check($asset_uuid, $cachedir=JP2_CACHE_DIR) {
	$cache_file = $cachedir.$asset_uuid;
	$file_max_age = time() - CACHE_MAX_AGE;
	if (!file_exists($cache_file))
		return (false);
	if (filemtime($cache_file) < $file_max_age) {
		// expired, removing old file:
		unlink($cache_file);
		return (false);
	}

	return (true);
}


/**
 * @brief Stores given picture to given cache directory.
 *
 * @param asset_id (string) UUID of the asset to store.
 * @param content (datas) raw image datas
 * @param cachedir local directory where to store image (as defined in inc/config.php)
 * @return false on error, true otherwise.
 */
function w4os_cache_write($asset_uuid, $content, $cachedir=JP2_CACHE_DIR) {
	$cache_file = $cachedir.$asset_uuid;
	$h = fopen($cache_file, "wb+");
	if (!$h)
		return (false);
	fwrite($h, $content);
	fclose($h);

	return (true);
}

$format = strtolower(preg_replace('|^\.|', '', (!empty($query_format)) ? $query_format : IMAGE_DEFAULT_FORMAT));
$asset_uuid = preg_replace('|/.*|', '', $query_asset);
$asset_raw = asset_get($asset_uuid, $format);

// TODO : set an array of mime types according to 'format' arg

Header("Content-type: image/" . $format);
echo $asset_raw;
die();
