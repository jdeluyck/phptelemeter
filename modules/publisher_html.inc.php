<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "html");
define("_phptelemeter_publisher_version", "12");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_html.inc.php - file which contains the HTML publisher

Copyright (C) 2005 - 2007 Jan De Luyck  <jan -at- kcore -dot- org>

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
	}

	function mainHeader()
	{
		$returnStr = "<!DOCTYPE HTML PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN' 'http://www.w3.org/TR/html4/loose.dtd'>
		<html>
		<head>
			<META http-equiv='Content-Type' content='text/html; charset=iso-8859-15'>
			<title>phptelemeter - version " . _version . "</title>
		</head>
		<body>";

		return ($returnStr);
	}

	function mainFooter()
	{
		$returnStr = "</body>
		</html>";
		return ($returnStr);
	}

	function accountHeader($accountName)
	{
		$returnStr = "<h1>" . $accountName . "</h1><hr>\n";

		return($returnStr);
	}

	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate)
	{
		$data = $this->normalizeData($data);

		$generalData = $data["general"];
		$dailyData   = $data["daily"];
		$isp         = $data["isp"];
		$resetDate   = $data["reset_date"];
		$daysLeft    = $data["days_left"];

		/* general data, always shown */
		$usage = calculateUsage($generalData, $isp);

		$returnStr = "<h2>Usage statistics on " . date("d/m/Y") . "</h2>\n";

		if ($showGraph == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["download"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"]);
				$returnStr .= sprintf("&nbsp;&nbsp;Upload used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["upload"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"]);
			}
			else
				$returnStr .= sprintf("Quota used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["total"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"]);
		}

		if ($showRemaining == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$totaldownloadString = $totalUploadString = "";
				
				if ($usage["download"]["left"] == 0)
				{
					$totaldownloadString = "\n<br>You have used up your complete download volume.";
					$returnStr .= $totaldownloadString;
				}
				else
				{
					if($usage["download"]["left"] < 0)	
						$totaldownloadString = "\n<br>You have exceeded your download volume by %d MiB.";
					elseif($usage["download"]["left"] > 0)
						$totaldownloadString = "\n<br>You can download %d MiB without exceeding your download volume.";
						
					$returnStr .= sprintf($totaldownloadString, abs($usage["download"]["left"]));
				}
				
				if ($usage["upload"]["left"] == 0)
				{	
					$totalUploadString = "\n<br>You have used up your complete upload volume.";
					$returnStr .= $totalUploadString;
				}
				else
				{
					if ($usage["upload"]["left"] < 0)
						$totalUploadString = "\n<br>You have exceeded your upload volume by %d MiB.";
					elseif($usage["upload"]["left"] > 0)
						$totalUploadString = "\n<br>You can upload %d MiB without exceeding your upload volume.";
						
					$returnStr .= sprintf($totalUploadString, abs($usage["upload"]["left"]));
				}
			}
			else
			{
				if ($usage["total"]["left"] == 0)
				{
					$totalString = "\n<br>You have used up your complete volume.";
					$returnStr .= $totalString;
				}
				else
				{
					if ($usage["total"]["left"] < 0)
						$totalString = "\n<br>You have exceeded your volume by %d MiB.";
					elseif($usage["total"]["left"] > 0)
						$totalString = "\n<br>You can transfer %d MiB without exceeding your volume.";
						
					$returnStr .= sprintf($totalString, abs($usage["total"]["left"]));
				}
			}
			$returnStr .= "<br>";
		}

		if ($showResetDate && checkISPCompatibility($isp, "reset_date") == true)
		{
			$returnStr .= "\n<br>";
			$returnStr .= "Your quota will be reset on " . $resetDate . " (" . $daysLeft . " days left)<br>\n";

		}

		if ($showDaily == true && checkISPCompatibility($isp, "history") == true)
		{
			if (checkISPCompatability($isp, "seperate_quota") == true)
				$dateDiff = 3;
			else
				$dateDiff = 2;

			$returnStr .= "<h2>Statistics from " . $dailyData[0] . " to " . $dailyData[count ($dailyData) - $dateDiff] . "</h2>";
			$returnStr .= "
			<table border='1'>
			<tr>
				<th>Date</th>";
				if (checkISPCompatibility($isp, "seperate_quota") == true)
				{
					$returnStr .= "	<th>Download used</th>
									<th>Upload used</th>";
				}
				else
					$returnStr .= "	<th>Quota used</th>";
					
			$returnStr .= "</tr>";

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];
				
				if (checkISPCompatibility($isp, "seperate_quota") == true)
				{
					$download = $dailyData[$i++];
					$upload = $dailyData[$i];
					
					$returnStr .= sprintf("<tr>\n<td> %8s </td>\n<td> %7d MiB </td>\n<td> %7d MiB </td>\n</tr>\n", $date, $download, $upload);
				}
				else
				{
					$traffic = $dailyData[$i];

					$returnStr .= sprintf("<tr>\n<td> %8s </td>\n<td> %7d MiB </td>\n</tr>\n", $date, $traffic);
				}
			}

			$returnStr .= "</table>\n";
		}

		return ($returnStr);
	}

	function newVersion($versionNr)
	{
		return("<br>There's a new version available: v" . $versionNr . "<br>You can get it at <a href='" . _phptelemeterURL . "' target='_blank'>" . _phptelemeterURL . "</a>\n");
	}
}

?>
