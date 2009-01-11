<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext_graphonly");
define("_phptelemeter_publisher_version", "6");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_plaintext_graphonly.inc.php - file which contains the plaintext publisher, graph only version

Copyright (C) 2005 - 2009 Jan De Luyck  <jan -at- kcore -dot- org>

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

		$returnStr = "";

		if ($showGraph == true)
		{
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
				if ($usage["total"]["percent"] > $warnPercentage && $warnPercentage != 0)
				{
					$totalColour["pre"] = chr(27) . '[01;31m';
					$totalColour["post"] = chr(27) . '[00m';
				}
				else
					$totalColour["pre"] = $totalColour["post"] = "";

				$returnStr .= sprintf("%sQuota used: [%-20s] - %5d MiB (%2d%%)%s\n", $totalColour["pre"], str_repeat("#", $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"], $totalColour["post"] );

		}
		return ($returnStr);
	}

	function newVersion($versionNr)
	{
		return("\nThere's a new version available: v" . $versionNr . "\nYou can get it at " . _phptelemeterURL . "\n");
	}
}

?>
