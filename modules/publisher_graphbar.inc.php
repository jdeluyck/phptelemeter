<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "graphbar");
define("_phptelemeter_publisher_version", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_graphbar.inc.php - file which contains the graphbar publisher

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
		
		$this->neededConfigKeys = array("image");
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

		$returnStr = "Usage statistics on " . date("d/m/Y") . "\n";
		$returnStr .= "------------------------------";

		if ($showGraph == true)
		{
			$returnStr .= "\n";
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				if ($usage["download"]["percent"] > $warnPercentage && $warnPercentage != 0)
				{
					$downloadColour["pre"] = chr(27) . '[01;31m';
					$downloadColour["post"] = chr(27) . '[00m';
				}
				else
					$downloadColour["pre"] = $downloadColour["post"] = "";

				if( $usage["upload"]["percent"] > $warnPercentage && $warnPercentage != 0)
				{
					$uploadColour["pre"] = chr(27) . '[01;31m';
					$uploadColour["post"] = chr(27) . '[00m';

				}
				else
					$uploadColour["pre"] = $uploadColour["post"] = "";

				$returnStr .= sprintf("%sDownload used: [%-20s] - %5d MiB (%2d%%)%s\n", $downloadColour["pre"], str_repeat("#", $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"], $downloadColour["post"]);
				$returnStr .= sprintf("%s  Upload used: [%-20s] - %5d MiB (%2d%%)%s\n", $uploadColour["pre"], str_repeat("#", $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"], $uploadColour["post"]);
			}
			else
			{
				if ($usage["total"]["percent"] > $warnPercentage && $warnPercentage != 0)
				{
					$totalColour["pre"] = chr(27) . '[01;31m';
					$totalColour["post"] = chr(27) . '[00m';
				}
				else
					$totalColour["pre"] = $totalColour["post"] = "";

				$returnStr .= sprintf("%sQuota used: [%-20s] - %5d MiB (%2d%%)%s\n", $totalColour["pre"], str_repeat("#", $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"], $totalColour["post"] );
			}

		}

		if ($showRemaining == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$totalDownloadString = $totalUploadString = "";

				if ($usage["download"]["left"] == 0)
				{
					$totalDownloadString = "\nYou have used up your complete download volume.";
					$returnStr .= $totalDownloadString;
				}
				else
				{
					if ($usage["download"]["left"] < 0)
						$totalDownloadString = "\nYou have exceeded your download volume by %d MiB.";
					elseif ($usage["download"]["left"] > 0)
						$totalDownloadString = "\nYou can download %d MiB without exceeding your download volume.";

					$returnStr .= sprintf($totalDownloadString, abs($usage["download"]["left"]));
				}

				if ($usage["upload"]["left"] == 0)
				{
					$totalUploadString = "\nYou have used up your complete upload volume.";
					$returnStr .= $totalUploadString;
				}
				else
				{
					if ($usage["upload"]["left"] < 0)
						$totalUploadString = "\nYou have exceeded your upload volume by %d MiB.";
					elseif ($usage["upload"]["left"] > 0)
						$totalUploadString = "\nYou can upload %d MiB without exceeding your upload volume.";

					$returnStr .= sprintf($totalUploadString, abs($usage["upload"]["left"]));
				}
			}
			else
			{
				if($usage["total"]["left"] == 0)
				{
					$totalString = "\nYou have used up your complete volume.";
					$returnStr .= $totalString;
				}
				else
				{
					if ($usage["total"]["left"] < 0)
						$totalString = "\nYou have exceeded your volume by %d MiB.";
					elseif ($usage["total"]["left"] > 0)
						$totalString = "\nYou can transfer %d MiB without exceeding your volume.";

					$returnStr .= sprintf($totalString, abs($usage["total"]["left"]));
				}
			}

			$returnStr .= "\n";
		}

		if ($showResetDate && checkISPCompatibility($isp, "reset_date") == true)
		{
			$returnStr .= "\n";
			$returnStr .= "Your quota will be reset on " . $resetDate . " (" . $daysLeft . " days left)\n";
			$returnStr .= "\n";
		}

		if ($showDaily == true && checkISPCompatibility($isp, "history") == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
				$dateDiff = 3;
			else
				$dateDiff = 2;

			$returnStr .= "\n";
			$returnStr .= "Statistics from " . $dailyData[0] . " to " . $dailyData[count ($dailyData) - $dateDiff] . "\n";
			$returnStr .= "------------------------------------\n";
			$returnStr .= "\n";

			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$returnStr .= str_repeat("-", 42) . "\n";
				$returnStr .= sprintf("| %-8s | %s | %s |\n", "Date", "Download used", "Upload used");
				$returnStr .= str_repeat("-", 42) . "\n";
			}
			else
			{
				$returnStr .= str_repeat("-", 25) . "\n";
				$returnStr .= sprintf("| %-8s | %s |\n", "Date", "Quota used");
				$returnStr .= str_repeat("-", 25) . "\n";
			}

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];

				if (checkISPCompatibility($isp, "seperate_quota") == true)
				{
					$download = $dailyData[$i++];
					$upload = $dailyData[$i];

					$returnStr .= sprintf("| %8s | %9d MiB | %7d MiB |\n", $date, $download, $upload);
				}
				else
				{
					$traffic = $dailyData[$i];

					$returnStr .= sprintf("| %8s | %6d MiB |\n", $date, $traffic);
				}
			}

			if (checkISPCompatibility($isp, "seperate_quota") == true)
				$returnStr .= str_repeat("-", 42) . "\n\n";
			else
				$returnStr .= str_repeat("-", 25) . "\n\n";

		}

		return ($returnStr);
	}

	function newVersion($versionNr)
	{
		return("\nThere's a new version available: v" . $versionNr . "\nYou can get it at " . _phptelemeterURL . "\n");
	}
}

?>

<?php 
/* 
    Copyright (C) 2007 Koen Vandenabeele 

    This program is free software: you can redistribute it and/or modify 
    it under the terms of the GNU General Public License as published by 
    the Free Software Foundation, either version 3 of the License, or 
    (at your option) any later version. 

    This program is distributed in the hope that it will be useful, 
    but WITHOUT ANY WARRANTY; without even the implied warranty of 
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
    GNU General Public License for more details. 

    You should have received a copy of the GNU General Public License 
    along with this program.  If not, see <http://www.gnu.org/licenses/>. 
    
   Credits: 
   - curl fetching code by Devil_Kin 
*/ 
// Configuration 
$username = 'username'; // aanpassen 
$password = 'pass'; // aanpassen 

// Don't touch 
$clientid = 0; 
$servid = 0; 

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

?>
