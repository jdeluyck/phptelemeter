<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext");
define("_phptelemeter_publisher_version", "1");
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

		$totalMax = $generalMatches[0];
		$uploadMax = $generalMatches[1];
		$totalUse = $generalMatches[2];
		$uploadUse = $generalMatches[3];
		$totalLeft = $totalMax - $totalUse;
		$uploadLeft = $uploadMax - $uploadUse;
		$totalPercent = (100 / $totalMax) * $totalUse;
		$uploadPercent = (100 / $uploadMax) * $uploadUse;

		$totalHashes = $totalPercent / 5;
		$uploadHashes = $uploadPercent / 5;

		$returnStr = "Telemeter statistics on " . date("d/m/Y") . "\n";
		$returnStr .= "----------------------------------\n";

		$returnStr .= sprintf("Volume used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $totalHashes),$totalUse, $totalPercent);
		$returnStr .= sprintf("Upload used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $uploadHashes),$uploadUse, $uploadPercent);

		if ($showRemaining == true)
		{
			if ($totalLeft <= 0)
			{
				$totalVolumeString = "\nYou have exceeded your total volume by %d MiB.";
				$totalUploadString = "";
			}
			elseif ($uploadLeft <= 0)
			{
				$totalVolumeString = "";
				$totalUploadString = "\nYou have exceeded your upload volume by %d MiB.";
			}
			else
			{
				$totalVolumeString = "\nYou can download %d MiB without exceeding your total volume.";
				$totalUploadString = "\nYou can upload %d MiB without exceeding your upload volume.";
			}

			$returnStr .= sprintf($totalVolumeString, abs($totalLeft));
			$returnStr .= sprintf($totalUploadString, abs($uploadLeft));
			$returnStr .= "\n";
		}

		if ($showDaily == true)
		{
			$returnStr .= "\n";
			$returnStr .= "Statistics for last 30 days\n";
			$returnStr .= "---------------------------\n";
			$returnStr .= "\n";
			$returnStr .= str_repeat("-", 40) . "\n";
			$returnStr .= sprintf("| %-8s | %s | %s |\n", "Date", "Volume used", "Upload used");
			$returnStr .= str_repeat("-", 40) . "\n";

			for ($i = 0; $i < count($dailyMatches); $i++)
			{
				$date = $dailyMatches[$i++];
				$total = $dailyMatches[$i++];
				$upload = $dailyMatches[$i];

				$returnStr .= sprintf("| %8s | %7d MiB | %7d MiB |\n", $date, $total, $upload);
			}

			$returnStr .= str_repeat("-", 40) . "\n\n";
		}

		return ($returnStr);
	}
}

?>
