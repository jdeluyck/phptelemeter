<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext");
define("_phptelemeter_publisher_version", "5");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

publisher_plaintext.inc.php - file which contains the plaintext publisher

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
		return ("phptelemeter - version " . _version . "\n");
	}

	function mainFooter()
	{
		return ("");
	}

	/* EXTERNAL */
	function accountHeader($accountName)
	{
		return("Fetching information for " . $accountName . "...");
	}

	/* EXTERNAL */
	function accountFooter()
	{
		return("done!\n\n");
	}

	/* EXTERNAL! */
	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate)
	{
		$generalData = $data["general"];
		$dailyData   = $data["daily"];

		/* general data, always shown */
		$usage = calculateUsage($generalData);

		$returnStr = "Telemeter statistics on " . date("d/m/Y") . "\n";
		$returnStr .= "----------------------------------";

		if ($showGraph == true)
		{
			$returnStr .= "\n";
			$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"]);
			$returnStr .= sprintf("  Upload used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"]);
		}

		if ($showRemaining == true)
		{
			if ($usage["download"]["left"] <= 0)
			{
				$totalDownloadString = "\nYou have exceeded your download volume by %d MiB.";
				$totalUploadString = "";
			}
			elseif ($usage["upload"]["left"] <= 0)
			{
				$totalDownloadString = "";
				$totalUploadString = "\nYou have exceeded your upload volume by %d MiB.";
			}
			else
			{
				$totalDownloadString = "\nYou can download %d MiB without exceeding your download volume.";
				$totalUploadString = "\nYou can upload %d MiB without exceeding your upload volume.";
			}

			$returnStr .= sprintf($totalDownloadString, abs($usage["download"]["left"]));
			$returnStr .= sprintf($totalUploadString, abs($usage["upload"]["left"]));
			$returnStr .= "\n";
		}

		if ($showResetDate)
		{
			$endDate = $dailyData[count ($dailyData) - 3];
			$resetDate = date("d/m/Y", mktime(0,0,0,substr($endDate,3,2),substr($endDate,0,2) + 1,substr($endDate,6)));

			$returnStr .= "\n";
			$returnStr .= "Your quota will be reset on " . $resetDate . ".\n";
		}

		if ($showDaily == true)
		{
			$returnStr .= "\n";
			$returnStr .= "Statistics from " . $dailyData[0] . " to " . $dailyData[count ($dailyData) - 3] . "\n";
			$returnStr .= "------------------------------------\n";
			$returnStr .= "\n";
			$returnStr .= str_repeat("-", 42) . "\n";
			$returnStr .= sprintf("| %-8s | %s | %s |\n", "Date", "Download used", "Upload used");
			$returnStr .= str_repeat("-", 42) . "\n";

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];
				$download = $dailyData[$i++];
				$upload = $dailyData[$i];

				$returnStr .= sprintf("| %8s | %9d MiB | %7d MiB |\n", $date, $download, $upload);
			}

			$returnStr .= str_repeat("-", 42) . "\n\n";
		}

		return ($returnStr);
	}
}

?>
