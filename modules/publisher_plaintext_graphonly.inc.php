<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "plaintext_graphonly");
define("_phptelemeter_publisher_version", "2");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_plaintext_graphonly.inc.php - file which contains the plaintext publisher, graph only version

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

require_once("libs/phptelemeter_publisher_shared.inc.php");

class telemeterPublisher extends telemeterPublisher_shared
{
	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate)
	{
		$generalData = $data["general"];
		$dailyData   = $data["daily"];
		$isp         = $data["isp"];
		$resetDate   = $data["reset_date"];

		/* general data, always shown */
		$usage = calculateUsage($generalData, $isp);

		if ($showGraph == true)
		{
			if (checkISPCompatibility($isp, "seperate_quota") == true)
			{
				$returnStr .= sprintf("Download used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["download"]["hashes"]),$usage["download"]["use"], $usage["download"]["percent"]);
				$returnStr .= sprintf("  Upload used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["upload"]["hashes"]),$usage["upload"]["use"], $usage["upload"]["percent"]);
			}
			else
				$returnStr .= sprintf("Quota used: [%-20s] - %5d MiB (%2d%%)\n", str_repeat("#", $usage["total"]["hashes"]),$usage["total"]["use"], $usage["total"]["percent"]);

		}
		return ($returnStr);
	}

	function newVersion($versionNr)
	{
		return("\nThere's a new version available: v" . $versionNr . "\nYou can get it at " . _phptelemeterURL . "\n");
	}
}

?>
