<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext");
define("_phptelemeter_publisher_version", "12");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_plaintext.inc.php - file which contains the plaintext publisher

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
		return ("phptelemeter - version " . _version . "\n");
	}

	function accountHeader($accountName)
	{
		return("Fetching information for " . $accountName . "...");
	}

	function accountFooter()
	{
		return("done!\n\n");
	}

	/* EXTERNAL! */
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

		$returnStr = "Usage statistics on " . date("d/m/Y") . "\n";
		$returnStr .= "------------------------------";

		if ($showGraph == true)
		{
			$returnStr .= "\n";
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"]);
				$returnStr .= sprintf("  Upload used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"]);
			}
			else
				$returnStr .= sprintf("Quota used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"]);

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
