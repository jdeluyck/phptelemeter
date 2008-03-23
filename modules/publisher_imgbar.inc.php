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
		imagettftext($imageHandler, $fontSize, 0, $xPos, $yPos, $theColor, $fontFile, $theText); 

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
?>
