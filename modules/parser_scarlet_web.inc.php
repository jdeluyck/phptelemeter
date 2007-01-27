<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_scarlet_web", "6");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_scarlet_web.inc.php - file which contains the Scarlet web page parser module.

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

require_once("libs/phptelemeter_parser_web_shared.inc.php");

class telemeterParser_scarlet_web extends telemeterParser_web_shared
{
	var $_ISP = "scarlet";

	var $months;

	function telemeterParser_scarlet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->url["login"] = "http://customercare.scarlet.be/logon.do";
		$this->url["stats"] = "http://customercare.scarlet.be/usage/dispatch.do";
		$this->url["logout"] = "http://customercare.scarlet.be/logoff.do";

		$this->errors = array("Uw gebruikersnaam of wachtwoord is fout ingegeven." => "Incorrect login",
							"Vergeet uw wachtwoord niet in te voeren." => "No password supplied",
							"Vergeet uw gebruikersnaam niet in te voeren." =>"No username supplied",
							"index.jsp?language=nl" => "Something went wrong - check username and password?");

		$this->months = array("January" => 1, "February" => 2, "March" => 3, "April" => 4, "May" => 5, "June" => 6, "July" => 7, "August" => 8, "September" => 9, "October" => 10, "November" => 11, "December" => 12);
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* log in */
		$log = $this->doCurl($this->url["login"], $this->createPostFields(array("username" => $userName, "password" => $password)));
		if ($this->checkForError($log) !== false)
			return (false);

		/* and get the data */
		$data = $this->doCurl($this->url["stats"], FALSE);
		if ($this->checkForError($data) !== false)
			return (false);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));
		$data = explode("\n", $data);

		/* data interval */
		$pos["data_interval"] = 4;

		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = trim($data[$i]);

			if (strlen($data[$i]) != 0)
				$temp[] = $data[$i];
		}

		$data = $temp;

		if ($this->debug == true)
		{
			echo "DATA:\n";
			var_dump($data);
		}

		for ($i = 0; $i < count($data); $i++)
		{
			/* determine positions */
			if (stristr($data[$i], "Periode van") !== false)
				$pos["daterange"] = $i;
			elseif (stristr($data[$i], "Uw maandelijkse factuur wordt opgesteld op") !== false)
				$pos["resetdate"] = $i;
			elseif ($data[$i] == "Download")
				$pos["download"] = $i + $pos["data_interval"];
			elseif ($data[$i] == "Upload")
				$pos["upload"] = $i + $pos["data_interval"];
			elseif (stristr($data[$i], "limiet van uw on-line") !== false)
				$pos["total"] = $i;
		}

		if ($this->debug == true)
		{
			echo "POS:\n";
			var_dump($pos);
		}

		/* stats */

		/* reset date */
		$temp = explode(" ", $data[$pos["resetdate"]]);
		$reset_date = $temp[22] . "/" . $this->months[$temp[23]] . "/" . date("Y");

		/* date range */
		$temp = explode (" ", $data[$pos["daterange"]]);

		/* change the month */
		$temp[3] = $this->months[$temp[3]];
		$temp[7] = $this->months[$temp[7]];

		if ($this->debug == true)
			var_dump($temp);

		/* what data do we have available? */
		$start = mktime(0, 0, 0, $temp[3], $temp[2], $temp[4]);
		$end = mktime(0, 0, 0, $temp[7], $temp[6], $temp[8]);
		$realend = mktime(0,0,0, $temp[3] + 1, $temp[2] -1, $temp[4]);
		$days = intval(($end - $start) / 86400) + 1;
		$realdays = intval(($realend - $start) / 86400) + 1;

		if ($this->debug == true)
		{
			echo "start: ", $start, " ", date("Y-m-d", $start), "\n";
			echo "end: ", $end, " ", date("Y-m-d", $end), "\n";
			echo "days: ", $days, "\n";
			echo "realend: ", $realend, " ", date("Y-m-d", $realend),"\n";
			echo "realdays: ", $realdays, "\n";
		}

		$totalUsedVolume = 0;

		/* loop through it to get all the data */
		for ($i = 1; $i < $realdays; $i++)
		{
			$dailyData[] = date("d/m/y", $start + (($i - 1) * 86400));

			/* why oh why they insist on putting the data in 3 different possible weights, i don't know. */
			switch (substr($data[$pos["download"]],-2))
			{
				case "GB":
				{
					$downloadMultiplier = 1024;
					break;
				}
				case "MB":
				{
					$downloadMultiplier = 1;
					break;
				}
				default:
					$downloadMultiplier = (1/1024);
			}

			switch (substr($data[$pos["upload"]],-2))
			{
				case "GB":
				{
					$uploadMultiplier = 1024;
					break;
				}
				case "MB":
				{
					$uploadMultiplier = 1;
					break;
				}
				default:
					$uploadMultiplier = (1/1024);
			}

			$dailyData[] = round(floatval(str_replace(",",".",substr($data[$pos["download"]],0,-2))) * $downloadMultiplier);
			$dailyData[] = round(floatval(str_replace(",",".",substr($data[$pos["upload"]],0,-2))) * $uploadMultiplier);

			$totalUsedVolume += $dailyData[count($dailyData) - 2] + $dailyData[count($dailyData) - 1];

			if ($this->debug == true)
			{
				echo "DATE: ". $dailyData[count($dailyData) - 3]. "\n";
				echo "DOWNLOAD (pos: " . $pos["download"] . "): " . $data[$pos["download"]] . " - CAPTURED: " . $dailyData[count($dailyData) - 2] . "\n";
				echo "UPLOAD (pos: " . $pos["upload"] . "): " . $data[$pos["upload"]]. " - CAPTURED: " . $dailyData[count($dailyData) - 1] . "\n";
			}

			$pos["upload"] += $pos["data_interval"];
			$pos["download"] += $pos["data_interval"];

		}

		/* add necessary empty rows */
		for ($i = $days + 1; $i <= $realdays; $i++)
		{
			$dailyData[] = date("d/m/y", $start + (($i - 1) * 86500));
			$dailyData[] = 0;
			$dailyData[] = 0;
		}

		/* total used - calculated by adding up the values above*/
		$volume[] = $totalUsedVolume;

		/* remaining */
		$temp = explode(" ", $data[$pos["total"]]);
		$volume[] = $temp[10] * 1024 - $totalUsedVolume;

		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;
		$returnValue["daily"] = $dailyData;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		if ($this->debug == true)
			print_r($returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
