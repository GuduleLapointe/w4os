<?php
/**
 * textgen.php
 *
 * This script generates an image with the specified text.
 * Main purpose is to generate images for prim textures in Second Life or OpenSimulator.
 * OpenSimulator now allows dynamic textures, which deprecated the need for this script.
 * However it is still usefull
 * - for simulator/grids that do not allow dynamic textures functionality
 * - to lighten the load on the simulator by pre-generating the textures
 * - to ensure texture consistency (dynamic textures tend to disappear sometimes)
 *
 * Version 2.0 use Imagick instead of GD, but is functionally equivalent to the previous versions.
 *
 * Usage :
 * - http://example.com/textgen.php?string=Hello%20World
 * - http://example.com/textgen.php?string=Hello%20World&font=Impact&size=120&color=white&background=black&download=true
 *
 * The fonts available on your server can be listed by calling the script with the parameter 'json' or 'list'
 *
 * URL parameters :
 *
 * @param string    $list               If set, the script will output a text list of available fonts and exit
 * @param string    $json               If set, the script will output a JSON-formatted list of available fonts and exit
 * @param string    $string             The text to be rendered
 * @param string    $font               The font to be used
 * @param int       $fontsize           The font size
 * @param string    $textcolor          The text color
 * @param string    $backgroundcolor    The background color
 * @param bool      $download           If set, the image will be downloaded, otherwise it will be displayed in the browser
 * @param bool      $inpage             (deprecated) If set, the image will be displayed in the browser, otherwise it will be downloaded
 *
 * @version 2.0
 **/

$fontsize        = isset( $_REQUEST['fontsize'] ) ? $_REQUEST['size'] : 120;
$textcolor       = isset( $_REQUEST['color'] ) ? $_REQUEST['color'] : 'white';
$backgroundcolor = isset( $_REQUEST['background'] ) ? $_REQUEST['background'] : 'black';
$font            = isset( $_REQUEST['font'] ) ? $_REQUEST['font'] : 'Impact';
$download        = isset( $_REQUEST['inpage'] ) ? false : true;
$download        = isset( $_REQUEST['download'] ) ? true : false;
$string          = isset( $_REQUEST['string'] ) ? urldecode( preg_replace( ":\\\':", '\'', $_REQUEST['string'] ) ) : $font;

$finalwidth      = 1024;
$finalheight     = 1024;
$workheight      = 1024;
$workwidth       = 1024;

$queryFonts = \Imagick::queryFonts( '*' );

if ( isset( $_REQUEST['json'] ) ) {
	header( 'Content-Type: application/json' );
	echo json_encode( $queryFonts );
	exit;
} elseif ( isset( $_REQUEST['list'] ) ) {
	header( 'Content-Type: text/plain' );
	echo implode( "\n", $queryFonts );
	exit;
}

if ( ! in_array( $font, $queryFonts ) ) {
	$font = 'DejaVu-Sans'; // Use a default font if the specified font is not available
}

$draw = new ImagickDraw();
$draw->setFont( $font );
$draw->setFontSize( $fontsize );
$draw->setFillColor( $textcolor );
$draw->setTextAlignment( Imagick::ALIGN_CENTER );

$imagick = new Imagick();
$imagick->newImage( $workwidth, $workheight, $backgroundcolor );
$imagick->setImageFormat( 'png' );

$metrics    = $imagick->queryFontMetrics( $draw, $string );
$textwidth  = $metrics['textWidth'];
$textheight = $metrics['textHeight'];

$xcord = ( $workwidth / 2 );
$ycord = ( $workheight / 2 ) + ( $textheight / 4 );

$imagick->annotateImage( $draw, $xcord, $ycord, 0, $string );

$imagick->resizeImage( $finalwidth, $finalheight, Imagick::FILTER_LANCZOS, 1 );

header( 'Content-type: image/png' );
if ( $download ) {
	header( "Content-Disposition: attachment; filename=\"${string}.png\"" );
}

echo $imagick;
$imagick->clear();
$imagick->destroy();
