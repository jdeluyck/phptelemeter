<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext");
define("_phptelemeter_publisher_version", "2");
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

		$returnStr = "Telemeter statistics on " . date("d/m/Y") . "\n";
		$returnStr .= "----------------------------------\n";

		$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $downloadHashes),$downloadUse, $downloadPercent);
		$returnStr .= sprintf("  Upload used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $uploadHashes),$uploadUse, $uploadPercent);

		if ($showRemaining == true)
		{
			if ($downloadLeft <= 0)
			{
				$totalDownloadString = "\nYou have exceeded your download volume by %d MiB.";
				$totalUploadString = "";
			}
			elseif ($uploadLeft <= 0)
			{
				$totalDownloadString = "";
				$totalUploadString = "\nYou have exceeded your upload volume by %d MiB.";
			}
			else
			{
				$totalDownloadString = "\nYou can download %d MiB without exceeding your download volume.";
				$totalUploadString = "\nYou can upload %d MiB without exceeding your upload volume.";
			}

			$returnStr .= sprintf($totalDownloadString, abs($downloadLeft));
			$returnStr .= sprintf($totalUploadString, abs($uploadLeft));
			$returnStr .= "\n";
		}

		if ($showDaily == true)
		{
			$returnStr .= "\n";
			$returnStr .= "Statistics for last 30 days\n";
			$returnStr .= "---------------------------\n";
			$returnStr .= "\n";
			$returnStr .= str_repeat("-", 42) . "\n";
			$returnStr .= sprintf("| %-8s | %s | %s |\n", "Date", "Download used", "Upload used");
			$returnStr .= str_repeat("-", 42) . "\n";

			for ($i = 0; $i < count($dailyMatches); $i++)
			{
				$date = $dailyMatches[$i++];
				$download = $dailyMatches[$i++];
				$upload = $dailyMatches[$i];

				$returnStr .= sprintf("| %8s | %9d MiB | %7d MiB |\n", $date, $download, $upload);
			}

			$returnStr .= str_repeat("-", 42) . "\n\n";
		}

		return ($returnStr);
	}
}

?>
