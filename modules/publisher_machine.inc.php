<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "machine");
define("_phptelemeter_publisher_version", "8");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_machine.inc.php - file which contains the machine-readable publisher

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
	function accountHeader($accountName)
	{
		return("\n#AccountName\n" . $accountName . "\n");
	}

	function accountFooter()
	{
		return("\n");
	}

	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate)
	{
		$generalData = $data["general"];
		$dailyData   = $data["daily"];
		$isp         = $data["isp"];
		$resetDate   = $data["reset_date"];

		/* general data, always shown */

		$usage = calculateUsage($generalData, $isp);

		if (checkISPCompatibility($isp, "seperate_quota") == true)
		{
			$returnStr = "#DownlMax,DownlUsed,DownlPercent,DownlLeft\n";
			$returnStr .= sprintf("%d,%d,%d,%d\n", $usage["download"]["max"], $usage["download"]["use"], $usage["download"]["percent"], $usage["download"]["left"]);
			$returnStr .= "#UplMax,UplUsed,UplPercent,UplLeft\n";
			$returnStr .= sprintf("%d,%d,%d,%d\n", $usage["upload"]["max"], $usage["upload"]["use"], $usage["upload"]["percent"], $usage["upload"]["left"]);
		}
		else
		{
			$returnStr .= "#TotMax,TotUsed,TotPercent,TotLeft\n";
			$returnStr .= sprintf("%d,%d,%d,%d\n", $usage["total"]["max"], $usage["total"]["use"], $usage["total"]["percent"], $usage["total"]["left"]);
		}

		$returnStr .= "\n";

		if ($showDaily == true && checkISPCompatibility($isp, "history") == true)
		{
			$returnStr .= "#Date,DownlUsed,UplUsed\n";

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];
				$download = $dailyData[$i++];
				$upload = $dailyData[$i];

				$returnStr .= sprintf("%s,%d,%d\n",$date, $download, $upload);
			}
		}

		return ($returnStr);
	}
}

?>
