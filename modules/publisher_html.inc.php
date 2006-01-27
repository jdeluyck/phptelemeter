<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "html");
define("_phptelemeter_publisher_version", "4");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

publisher_html.inc.php - file which contains the HTML publisher

Copyright (C) 2005 - 2006 Jan De Luyck  <jan -at- kcore -dot- org>

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

class telemeterPublisher
{
	var $debug = false;
	var $neededModules = "";

	function setDebug($debug)
	{
		$this->debug = $debug;
	}

	function getNeededModules()
	{
		return ($this->neededModules);
	}

	function telemeterPublisher()
	{
	}

	/* exit function for us. */
	function destroy()
	{
	}

	/* EXTERNAL */
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

	/* EXTERNAL */
	function accountHeader($accountName)
	{
		$returnStr = "<h1>" . $accountName . "</h1><hr>\n";

		return($returnStr);
	}

	/* EXTERNAL */
	function accountFooter()
	{
		return("");
	}

	/* EXTERNAL! */
	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate)
	{
		$generalData = $data["general"];
		$dailyData   = $data["daily"];

		// general data, always shown
		$usage = calculateUsage($generalData);

		$returnStr = "<h2>Telemeter statistics on " . date("d/m/Y") . "</h2>\n";

		if ($showGraph == true)
		{
			$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["download"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"]);
			$returnStr .= sprintf("&nbsp;&nbsp;Upload used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $usage["upload"]["hashes"]) . str_repeat("&nbsp", 20 - $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"]);
		}

		if ($showRemaining == true)
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
			$returnStr .= "<br>";
		}

		if ($showResetDate)
		{
			$endDate = $dailyData[count ($dailyData) - 3];
			$resetDate = date("d/m/Y", mktime(0,0,0,substr($endDate,3,2),substr($endDate,0,2) + 1,substr($endDate,6)));

			$returnStr .= "\n<br>";
			$returnStr .= "Your quota will reset on " . $resetDate . ".<br>\n";

		}

		if ($showDaily == true)
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
}

?>
