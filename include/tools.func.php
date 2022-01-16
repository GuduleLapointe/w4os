<?php
/****************************************************************
 * tools.func.php v1.0.0
 *							by Fumi.Iseki (c) 2010 5/13
 *
 *							http://www.nsl.tuis.ac.jp/
 *
 ****************************************************************/


/****************************************************************
 * Function List

 function  isNumeric($str, $nullok=false)
 function  isAlphabetNumeric($str, $nullok=false)
 function  isAlphabetNumericSpecial($str, $nullok=false)
 function  isGUID($uuid, $nullok=false)

 function  split_key_value($str)

 function  make_random_hash()
 function  make_random_guid()

 function  j2k_to_tga($file, $iscopy=true)
 function  get_j2k_to_tga_command() 			// need j2k_to_image (OpenJpeg)
 function  get_image_size_convert_command($xsize, $ysize)

 function  find_command_path($command)

 ****************************************************************/





function  isNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[0-9\.]+$/', $str)) return false;

	return true;
}



function  isAlphabetNumeric($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^\w+$/', $str)) return false;
	return true;
}



function  isAlphabetNumericSpecial($str, $nullok=false)
{
	if ($str!='0' and $str==null) return $nullok;
	if (!preg_match('/^[_a-zA-Z0-9 &@%#\-\.]+$/', $str)) return false;
	return true;
}



function  isGUID($uuid, $nullok=false)
{
	if ($uuid==null) return $nullok;
	if (!preg_match('/^[0-9A-Fa-f]{8,8}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{4,4}-[0-9A-Fa-f]{12,12}$/', $uuid)) return false;
	return true;
}



function  make_random_hash()
{
 	$ret = sprintf('%04x%04x%04x%04x%04x%04x%04x%04x',mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),
 													  mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff),mt_rand(0,0xffff));
	return $ret;
}



function  make_random_guid()
{
	$uuid = sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
					  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
					  mt_rand( 0, 0x0fff ) | 0x4000,
					  mt_rand( 0, 0x3fff ) | 0x8000,
		   			  mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	return $uuid;
}




///////////////////////////////////////////////////////////////////////////////////////////////////
//
// String Tools
//

// parse {"key1":"value1","key2":{"key3":"value3"}}
//
//    	--> [key1] => value1
//    		[key2] => Array
//        		(
//            		[key3] => value3
//        		)
//

function  split_key_value($str)
{
	$info = array();
	$str  = trim($str);

	if (substr($str, 0, 1)=='{' and substr($str, -1)=='}') {
		$str = substr($str, 1, -1);
		$inbrkt = 0;
		$inquot = false;
		$inkkko = false;
		$isakey = true;
		$key    = "";
		$val    = "";

		for ($i=0; $i<strlen($str); $i++) {
			$cc = substr($str, $i, 1);

			if ($inbrkt==0 and !$inquot and ($cc=='"' or $cc=='\'')) {
				$inquot = true;
			}
			else if ($inbrkt==0 and $inquot and ($cc=='"' or $cc=='\'')) {
				$inquot = false;
			}
			else if ($inbrkt==0 and $isakey  and !$inquot and !$inkkko and $cc==':') {
				$isakey = false;
			}
			else if ($inbrkt==0 and !$isakey and !$inquot and !$inkkko and $cc==',') {
				if (substr($val, 0, 1)=='{' and substr($val, -1)=='}') {
					$info[$key] = split_key_value($val);
				}
				else $info[$key] = $val;

				$isakey = true;
				$key    = "";
				$val    = "";
			}
			else {
				if      ($cc=='{') $inbrkt++;
				else if ($cc=='}') $inbrkt--;
				else {
					if      ($inbrkt==0 and !$inkkko and $cc=='[') $inkkko = true;
					else if ($inbrkt==0 and $inkkko  and $cc==']') $inkkko = false;
				}

				if ($isakey) $key .= $cc;
				else         $val .= $cc;
			}
		}

		//
		if ($key!="") {
			if (substr($val, 0, 1)=='{' and substr($val, -1)=='}') {
				$info[$key] = split_key_value($val);
			}
			else $info[$key] = $val;
		}
	}

	return $info;
}




///////////////////////////////////////////////////////////////////////////////////////////////////
//
// Image
//

//
// Convert Image from JPEG2000 to TGA
//		file -> file.tga
//
function  j2k_to_tga($file, $iscopy=true)
{
	if (!file_exists($file)) return false;

	$com_totga = get_j2k_to_tga_command();
	if ($com_totga=='') return false;

	if ($iscopy) $ret = copy  ($file, $file.'.j2k');
	else 		 $ret = rename($file, $file.'.j2k');
	if (!$ret) return false;

	exec("$com_totga -i $file.j2k -o $file.tga 1>/dev/null 2>&1");
	unlink($file.'.j2k');

	return true;
}



function  get_j2k_to_tga_command()
{
	$command = find_command_path('j2k_to_image');
	return $command;
}




//
// Image Size Convert Command String
//
function  get_image_size_convert_command($xsize, $ysize)
{
	if (!isNumeric($xsize) or !isNumeric($ysize)) return '';

	$command = find_command_path('convert');
	if ($command=='') return '';

	$prog = $command.' - -geometry '.$xsize.'x'.$ysize.'! -';
	return $prog;
}




function  find_command_path($command)
{
	$path = '';
	if (file_exists('/usr/local/bin/'.$command))	  $path = '/usr/local/bin/';
	else if (file_exists('/usr/bin/'.$command))		  $path = '/usr/bin/';
	else if (file_exists('/usr/X11R6/bin/'.$command)) $path = '/usr/X11R6/bin/';
	else if (file_exists('/bin/'.$command))			  $path = '/bin/';
	else return '';

	return $path.$command;
}
