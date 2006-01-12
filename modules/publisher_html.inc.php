<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "html");
define("_phptelemeter_publisher_version", "1");
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
	function publishData($data, $showRemaining, $showDaily)
	{
		$generalMatches = $data["general"];
		$dailyMatches   = $data["daily"];

		// general data, always shown

		$downloadMax = $generalMatches[0];
		$uploadMax = $generalMatches[1];
		$downloadUse = $generalMatches[2];
		$uploadUse = $generalMatches[3];
		$downloadLeft = $downloadMax - $downloadUse;
		$uploadLeft = $uploadMax - $uploadUse;
		$downloadPercent = (100 / $downloadMax) * $downloadUse;
		$uploadPercent = (100 / $uploadMax) * $uploadUse;

		$downloadHashes = $downloadPercent / 5;
		$uploadHashes = $uploadPercent / 5;

		$returnStr = "<h2>Telemeter statistics on " . date("d/m/Y") . "</h2>\n";

		$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $downloadHashes) . str_repeat("&nbsp", 20 - $uploadHashes),$downloadUse, $downloadPercent);
		$returnStr .= sprintf("&nbsp;&nbsp;Upload used: [%-20s] - %5d MiB (%2d%%)<br>\n", str_repeat("#", $uploadHashes) . str_repeat("&nbsp", 20 - $uploadHashes),$uploadUse, $uploadPercent);

		if ($showRemaining == true)
		{
			if ($totalLeft <= 0)
			{
				$totaldownloadString = "\n<br>You have exceeded your download volume by %d MiB.";
				$totalUploadString = "";
			}
			elseif ($uploadLeft <= 0)
			{
				$totaldownloadString = "";
				$totalUploadString = "\n<br>You have exceeded your upload volume by %d MiB.";
			}
			else
			{
				$totaldownloadString = "\n<br>You can download %d MiB without exceeding your download volume.";
				$totalUploadString = "\n<br>You can upload %d MiB without exceeding your upload volume.";
			}

			$returnStr .= sprintf($totaldownloadString, abs($downloadLeft));
			$returnStr .= sprintf($totalUploadString, abs($uploadLeft));
			$returnStr .= "<br>";
		}

		if ($showDaily == true)
		{
			$returnStr .= "<h2>Statistics for last 30 days</h2>\n";
			$returnStr .= "
			<table border='1'>
			<tr>
				<th>Date</th>
				<th>Download used</th>
				<th>Upload used</th>
			</tr>";

			for ($i = 0; $i < count($dailyMatches); $i++)
			{
				$date = $dailyMatches[$i++];
				$download = $dailyMatches[$i++];
				$upload = $dailyMatches[$i];

				$returnStr .= sprintf("<tr>\n<td> %8s </td>\n<td> %7d MiB </td>\n<td> %7d MiB </td>\n</tr>\n", $date, $download, $upload);
			}

			$returnStr .= "</table>\n";
		}

		return ($returnStr);
	}
}

?>
