<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "machine");
define("_phptelemeter_publisher_version", "1");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

publisher_machine.inc.php - file which contains the machine-readable publisher

Copyright (C) 2005 Jan De Luyck  <jan -at- kcore -dot- org>

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
		return ("");
	}

	function mainFooter()
	{
		return ("");
	}

	/* EXTERNAL */
	function accountHeader($accountName)
	{
		return($accountName . "\n");
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

		$totalMax = $generalMatches[0];
		$uploadMax = $generalMatches[1];
		$totalUse = $generalMatches[2];
		$uploadUse = $generalMatches[3];
		$totalLeft = $totalMax - $totalUse;
		$uploadLeft = $uploadMax - $uploadUse;
		$totalPercent = (100 / $totalMax) * $totalUse;
		$uploadPercent = (100 / $uploadMax) * $uploadUse;

		$returnStr = "#DownlMax,DownlUsed,DownlPercent,DownlLeft\n";
		$returnStr .= sprintf("%d,%d,%d,%d\n", $totalMax, $totalUse, $totalPercent, $totalLeft);
		$returnStr .= "#UplMax,UplUsed,UplPercent,UplLeft\n";
		$returnStr .= sprintf("%d,%d,%d,%d\n", $uploadMax, $uploadUse, $uploadPercent, $uploadLeft);

		$returnStr .= "\n";

		if ($showDaily == true)
		{
			$returnStr .= "#Date,DownlUsed,UplUsed\n";

			for ($i = 0; $i < count($dailyMatches); $i++)
			{
				$date = $dailyMatches[$i++];
				$total = $dailyMatches[$i++];
				$upload = $dailyMatches[$i];

				$returnStr .= sprintf("%s,%d,%d\n",$date, $total, $upload);
			}
		}

		return ($returnStr);
	}
}

?>
