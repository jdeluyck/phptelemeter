<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "machine");
define("_phptelemeter_publisher_version", "14");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_machine.inc.php - file which contains the machine-readable publisher

Copyright (C) 2005 - 2010 Jan De Luyck  <jan -at- kcore -dot- org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The full text of the license can be found here:
http://www.gnu.org/licenses/gpl2.txt

*/

require_once("libs/phptelemeter_publisher_shared.inc.php");

class telemeterPublisher extends telemeterPublisher_shared
{
	function telemeterPublisher()
	{
		/* call parent constructor */
		telemeterPublisher_shared::telemeterPublisher_shared();
		
		$this->neededConfigKeys = array("separator");
	}

	function accountHeader($accountName)
	{
		return("\n#AccountName\n" . $accountName . "\n");
	}

	function accountFooter()
	{
		return("\n");
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
		$separator = $this->configKey["separator"];
		
		if (checkISPCompatibility($isp, "separate_quota") == true)
		{
			$returnStr = sprintf("#DownlMax%sDownlUsed%sDownlPercent%sDownlLeft\n", $separator, $separator, $separator);
			$returnStr .= sprintf("%d%s%d%s%d%s%d\n", $usage["download"]["max"], $separator, $usage["download"]["use"], $separator, $usage["download"]["percent"], $separator, $usage["download"]["left"]);
			$returnStr .= sprintf("#UplMax%sUplUsed%sUplPercent%sUplLeft\n", $separator, $separator, $separator);
			$returnStr .= sprintf("%d%s%d%s%d%s%d\n", $usage["upload"]["max"], $separator, $usage["upload"]["use"], $separator, $usage["upload"]["percent"], $separator, $usage["upload"]["left"]);
		}
		else
		{
			$returnStr = sprintf("#TotMax%sTotUsed%sTotPercent%sTotLeft\n", $separator, $separator, $separator);
			$returnStr .= sprintf("%d%s%d%s%d%s%d\n", $usage["total"]["max"], $separator, $usage["total"]["use"], $separator, $usage["total"]["percent"], $separator, $usage["total"]["left"]);
		}

		$returnStr .= "\n";

		if ($showDaily == true && checkISPCompatibility($isp, "history") == true)
		{
			if (checkISPCompatibility($isp, "separate_day_info") == true)
				$returnStr .= sprintf("#Date%sDownlUsed%sUplUsed\n", $separator, $separator);
			else
				$returnStr .= sprintf("#Date%sQuotaUsed\n", $separator);

			for ($i = 0; $i < count($dailyData); $i++)
			{
				$date = $dailyData[$i++];

				if (checkISPCompatibility($isp, "separate_day_info") == true)
				{
					$download = $dailyData[$i++];
					$upload = $dailyData[$i];

					$returnStr .= sprintf("%s%s%d%s%d\n",$date, $separator, $download, $separator, $upload);
				}
				else
				{
					$traffic = $dailyData[$i];

					$returnStr .= sprintf("%s%s%d\n", $date, $separator, $traffic);
				}
			}
		}

		return ($returnStr);
	}
}

?>
