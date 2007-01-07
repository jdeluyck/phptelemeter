<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "html");
define("_phptelemeter_publisher_version", "9");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_html.inc.php - file which contains the HTML publisher

Copyright (C) 2005 - 2007 Jan De Luyck  <jan -at- kcore -dot- org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

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
				$returnStr .= sprintf("&nbsp;&nbsp;Quota used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["total"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"]);
		}

		if ($showRemaining == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				if ($usage["download"]["left"] <= 0)
				{
					$totaldownloadString = "\n<br>You have exceeded your download volume by %d MiB.";
					$totalUploadString = "";
				}
				elseif ($usage["upload"]["left"] <= 0)
				{
					$totaldownloadString = "";
					$totalUploadString = "\n<br>You have exceeded your upload volume by %d MiB.";
				}
				else
				{
					$totaldownloadString = "\n<br>You can download %d MiB without exceeding your download volume.";
					$totalUploadString = "\n<br>You can upload %d MiB without exceeding your upload volume.";
				}

				$returnStr .= sprintf($totaldownloadString, abs($usage["download"]["left"]));
				$returnStr .= sprintf($totalUploadString, abs($usage["upload"]["left"]));
			}
			else
			{
				if ($usage["total"]["left"] <= 0)
				{
					$totalString = "\n<br>You have exceeded your volume by %d MiB.";
				}
				else
				{
					$totaldownloadString = "\n<br>You can transfer %d MiB without exceeding your volume.";
				}

				$returnStr .= sprintf($totaldownloadString, abs($usage["total"]["left"]));
			}
			$returnStr .= "<br>";
		}

		if ($showResetDate && checkISPCompatibility($isp, "reset_date") == true)
		{
			$returnStr .= "\n<br>";
			$returnStr .= "Your quota will be reset on " . $resetDate . ".<br>\n";

		}

		if ($showDaily == true && checkISPCompatibility($isp, "history") == true)
		{
			$returnStr .= "<h2>Statistics from " . $dailyData[0] . " to " . $dailyData[count ($dailyData) - 3] . "</h2>";
			$returnStr .= "
			<table border='1'>
			<tr>
				<th>Date</th>
				<th>Download used</th>
				<th>Upload used</th>
			</tr>";

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];
				$download = $dailyData[$i++];
				$upload = $dailyData[$i];

				$returnStr .= sprintf("<tr>\n<td> %8s </td>\n<td> %7d MiB </td>\n<td> %7d MiB </td>\n</tr>\n", $date, $download, $upload);
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
