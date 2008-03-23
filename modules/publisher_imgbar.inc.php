<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "imgbar");
define("_phptelemeter_publisher_version", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_imgbar.inc.php - file which contains the graphbar publisher

Copyright (C) 2005 - 2008 Jan De Luyck  <jan -at- kcore -dot- org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The full text of the license can be found here:
http://www.gnu.org/licenses/gpl.txt

*/

require_once("libs/phptelemeter_publisher_shared.inc.php");

class telemeterPublisher extends telemeterPublisher_shared
{
	function telemeterPublisher()
	{
		/* call parent constructor */
		telemeterPublisher_shared::telemeterPublisher_shared();
		
		$this->neededConfigKeys = array("image","font_file", "font_color", "font_size", "x_coordinate", "y_coordinate");
		$this->neededModules = array("gd");
	}

	function mainHeader()
	{
		return ("");
	}

	function accountHeader($accountName)
	{
		return("");
	}

	function accountFooter()
	{
		return("");
	}

	/* EXTERNAL! */
	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate, $warnPercentage)
	{
		$data = $this->normalizeData($data);

		$generalData = $data["general"];
		$dailyData   = $data["daily"];
		$isp         = $data["isp"];
		$resetDate   = $data["reset_date"];
		$daysLeft    = $data["days_left"];

		/* general data, always shown */
		$usage = calculateUsage($generalData, $isp);

		$sourceImage = $this->configKey["image"];
		$fontFile = $this->configKey["font_file"];
		$fontColor = $this->configKey["font_color"];
		$xPos = $this->configKey["x_coordinate"];
		$yPos = $this->configKey["y_coordinate"];
		$fontSize = $this->configKey["font_size"];
		
		/* check if the files exist... */
		checkForFile($sourceImage, true);
		checkForFile($fontFile, true);
		
		/* check if the font color is in RRGGBB style, if not, fall back to black. */
		if ((strlen($fontColor) != 6) || (ctype_xdigit(substr($fontColor,0,2)) !== true 
			&& ctype_xdigit(substr($fontColor,2,2)) !== true && ctype_xdigit(substr($fontColor,4))) !== true)
			$fontColor = "000000";
			
		/* prepare text */
		if (checkISPCompatibility($isp, "seperate_quota") == true)
		{
			$theText  = "D: " . $usage["download"]["percent"] . "% / U:" . $usage["upload"]["percent"] . "%";
		}
		else
			$theText = $usage["total"]["percent"] . "%";
	
		header("Content-type: image/png"); 

		/* initialize */
		$imageHandler = imagecreatefrompng($sourceImage); 

		$theColor = imagecolorallocate($imageHandler, hexdec(substr($fontColor,0,2)), hexdec(substr($fontColor,2,2)), hexdec(substr($fontColor,4,2)));

		/* add text */
		imagettftext($imageHandler, 10, 0, $xPos, $yPos, $theColor, $fontFile, $theText); 

		/* show */ 
		$returnStr = imagepng($imageHandler);
	
		/* cleanup */ 
		imagedestroy($imageHandler);

		return ($returnStr);
	}

	function newVersion($versionNr)
	{
		return ("");
	}
}

/* 


function filter($string) { 
   $items = array('<br>','</br>','<b>','</b>','<i>','</i>',"onClick=\"MM_openBrWindow2('","','webadmin','schedom')\"><img"); 
   foreach ($items as $item) { 
      $string = str_replace($item,' ',$string); 
   } 

   return $string; 
} 
function clean($array) { 
   $newarray = array(); 
   foreach ($array as $item) { 
      if ($item!=' '&&$item!='') { 
         array_push($newarray,$item); 
      } 
   } 
   return $newarray; 
} 

function calculate() { 
   global $username,$password,$clientid,$servid; 

   $ch = curl_init(); 
   // login 
   $url = "https://crm.schedom-europe.net/login.php?username=$username&password=$password&op=login"; 
   curl_setopt($ch, CURLOPT_URL, $url); 
   curl_setopt($ch, CURLOPT_HEADER, 1); 
   curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
   curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); 
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
   curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar); 
   curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiejar); 
   curl_setopt($ch, CURLOPT_POST, 1); 
   curl_setopt($ch, CURLOPT_POSTFIELDS, $vars); 
   curl_exec($ch); 
    
   // get servid & clientid 
   $newurl = 'https://crm.schedom-europe.net/user.php?op=view&tile=mypackages'; 
   curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar); 
   curl_setopt($ch, CURLOPT_URL, $newurl); 
   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)'); // IE6 
   curl_setopt($ch, CURLOPT_HEADER, false); 
   curl_setopt($ch, CURLOPT_COOKIESESSION, true); 
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
   $buffer = curl_exec($ch); 
    
   $buffer = explode("\n",$buffer); 
   foreach ($buffer as $line) { 
      if (strstr($line,'dslinfo')) { 
         $line = filter($line); 
         $line = explode(' ',$line); 
         $line = clean($line); 
         $url = parse_url($line[2]); 
         $query = $url['query']; 
         $query = explode('&',$query); 
         $query[0] = explode('=',$query[0]); 
         $query[2] = explode('=',$query[2]); 
         $servid = $query[0][1]; 
         $clientid = $query[2][1]; 
         break; 
      } 
   } 
    
   // count 
   $newurl = "https://crm.schedom-europe.net/include/scripts/linked/dslinfo/dslinfo.php?servid=$servid&password=$password&client_id=$clientid"; 
   curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiejar); 
   curl_setopt($ch, CURLOPT_URL, $newurl); 
   curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)'); // IE6 
   curl_setopt($ch, CURLOPT_HEADER, false); 
   curl_setopt($ch, CURLOPT_COOKIESESSION, true); 
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
   $buffer = curl_exec($ch); 
    
   $buffer = explode("\n",$buffer); 
   foreach ($buffer as $line) { 
      if (strstr($line,'downloaded in broadband:')) { 
         $line = filter($line); 
         $line = explode(' ',$line); 
         $line = clean($line); 
         $downloaded = $line[5]; 
         $total = $line[21]+$downloaded; 
         break; 
      } 
   } 
    
   $fp = fopen('traffic.txt','w'); 
   fputs($fp,time()." $downloaded $total"); 
   fclose($fp); 
} 

function lastupdated() { 
   if (file_exists('traffic.txt')) { 
      $fp = fopen('traffic.txt','r'); 
      $buffer = fgets($fp); 
      $buffer = explode(' ',$buffer); 
      return $buffer[0]; 
      fclose($fp); 
   } else { 
      return 0; 
   } 
} 

if ((time()-lastupdated())>600) { 
   calculate(); 
} 

// ------------- show image 

header("Content-type: image/png"); 

// initialize 
$im = imagecreatefrompng("./sig.png"); 

$white = imagecolorallocate($im, 255, 255, 255); 
$black = imagecolorallocate($im, 0, 0, 0); 

// add text 
$buffer = file_get_contents("./traffic.txt"); 
$buffer = explode(' ',$buffer); 
imagettftext($im,10,0,140,14,$black,"./visitor1.ttf",$buffer[1]." / ".$buffer[2]); 

// show 
imagepng($im); 
imagedestroy($im); 
*/
?>
