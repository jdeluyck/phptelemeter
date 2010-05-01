<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_simulator_single", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_simulator_single.inc.php - file which contains the unified quota simulator module.

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

require_once("libs/phptelemeter_parser_web_shared.inc.php");

class telemeterParser_simulator_single extends telemeterParser_web_shared
{
	var $_ISP = "simulator_single";

	function telemeterParser_simulator_single()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* min - max values for data randomisation */
		$day_min = 1;	
		$day_max = 2048;
		
		$days_negative = mt_rand(1,31);
		$days_positive = mt_rand(1,(31 - $days_negative));
		$days_total = $days_negative + $days_positive + 1;
		
		dumpDebugInfo($this->debug, "DATERANGE: " . date("d/m/y", strtotime("-" . $days_negative . " days")) . " to " . date("d/m/y", strtotime("+" . $days_positive . " days")));

		$volume["used"] = 0;

		for ($i = $days_negative; $i > 0; $i--)
		{	
			$dailyData[] = date("d/m/y", strtotime("-" . $i . " days"));
			$temp = mt_rand($day_min, $day_max);
			$volume["used"] += $temp;
			$dailyData[] = $temp;
		}

		for ($i = 1; $i <= $days_positive; $i++)
		{
			$dailyData[] = date("d/m/y", strtotime("+" . $i . " days"));
			$temp = mt_rand($day_min, $day_max);
			$volume["used"] += $temp;
			$dailyData[] = $temp;
		}

		dumpDebugInfo($this->debug, $dailyData);
		
		$volume["remaining"] = mt_rand(-($day_max * $days_total), ($day_max * $days_total));
		
		$reset_date = date("d/m/Y", strtotime("+" . ($days_negative + $days_positive) . " days"));
		
		$returnValue["general"] = $volume;
		$returnValue["daily"] = $dailyData;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		return ($returnValue);
	}
}

?>
