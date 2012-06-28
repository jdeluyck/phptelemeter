<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_scarlet_web", "13");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_scarlet_web.inc.php - file which contains the Scarlet web page parser module.

Copyright (C) 2004 - 2012 Jan De Luyck  <jan -at- kcore -dot- org>

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

class telemeterParser_scarlet_web extends telemeterParser_web_shared
{
	var $_ISP = "scarlet";

	var $months;
	
	var $unlimited = false;

	function telemeterParser_scarlet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->url["login"] = "http://www.scarlet.be/customercare/logon.do";
		$this->url["stats"] = "http://www.scarlet.be/customercare/usage/dispatch.do";
		$this->url["logout"] = "http://www.scarlet.be/customercare/logoff.do";

		$this->errors = array("Uw gebruikersnaam of wachtwoord is fout ingegeven." => "Incorrect login",
							"Vergeet uw wachtwoord niet in te voeren." => "No password supplied",
							"Vergeet uw gebruikersnaam niet in te voeren." =>"No username supplied",
							"index.jsp?language=nl" => "Something went wrong - check username and password?");

		$this->months = array("januari" => 1, "februari" => 2, "maart" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli" => 7, "augustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "december" => 12);
	}

	/* EXTERNAL! */
	function getData($userName, $password, $subaccount)
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

		$data = $this->prepareData($data);

		/* data interval */
		$pos["data_interval"] = 4;

		dumpDebugInfo($this->debug, "DATA:\n");
		dumpDebugInfo($this->debug, $data);

		for ($i = 0; $i < count($data); $i++)
		{
			/* determine positions */
			if (stristr($data[$i], "Periode van") !== false)
				$pos["daterange"] = $i;
			elseif (stristr($data[$i], "Uw maandelijkse factuur wordt opgesteld op") !== false)
				$pos["resetdate"] = $i;
			elseif ($data[$i] == "Periode")
				$pos["date"] = $i + $pos["data_interval"];
			elseif ($data[$i] == "Download")
				$pos["download"] = $i + $pos["data_interval"];
			elseif ($data[$i] == "Upload")
				$pos["upload"] = $i + $pos["data_interval"];
			elseif (stristr($data[$i], "limiet van uw on-line") !== false)
			{
				$pos["total"] = $i;
				if (stristr($data[$i], "onbeperkt") !== false)
				{
					dumpDebugInfo($this->debug, "Unlimited account detected...");
					$this->unlimited = true;
				}
			}				
		}

		dumpDebugInfo($this->debug, "POS:\n");
		dumpDebugInfo($this->debug, $pos);

		/* stats */

		/* reset date */
		$temp = explode(" ", $data[$pos["resetdate"]]);
		dumpDebugInfo($this->debug,"Reset date, prior to cheanup:\n");
		dumpDebugInfo($this->debug, $temp);
		$reset_date = $temp[22] . "/" . $this->months[$temp[23]] . "/" . date("Y");
		dumpDebugInfo($this->debug, "Reset date: " . $reset_date . "\n");

		/* date range - daily data */
		$temp = explode (" ", $data[$pos["daterange"]]);

		/* change the month */
		$temp[3] = $this->months[$temp[3]];
		$temp[7] = $this->months[$temp[7]];

		dumpDebugInfo($this->debug, $temp);

		/* what data do we have available? */
		$start = mktime(0, 0, 0, $temp[3], $temp[2], $temp[4]);
		$end = mktime(0, 0, 0, $temp[7], $temp[6], $temp[8]);
		$realend = mktime(0,0,0, $temp[3] + 1, $temp[2] -1, $temp[4]);
		$days = intval(($end - $start) / 86400) + 1;
		$realdays = intval(($realend - $start) / 86400) + 1;

		dumpDebugInfo($this->debug,
			"start: ", $start, " ", date("Y-m-d", $start), "\n" .
			"end: ", $end, " ", date("Y-m-d", $end), "\n" .
			"days: ", $days, "\n" .
			"realend: ", $realend, " ", date("Y-m-d", $realend),"\n" .
			"realdays: ", $realdays, "\n");

		$totalUsedVolume = 0;

		/* loop through it to get all the data */
		for ($i = 1; $i <= $days; $i++)
		{
			/* check if we're in the 'shortly after midnight' area - fixes bug 1707175 */
			if ($data[$pos["date"]] == "Totaal voor deze periode")
			{
				dumpDebugInfo($this->debug, "Total entry found, reducing date count!\n");
				$days--;
				break;
			}

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

			dumpDebugInfo($this->debug,
			    "DAY: " . $i . "\n" .
				"DATE: (pos: " . $pos["date"] . "): " . $data[$pos["date"]] . " - CAPTURED: ". $dailyData[count($dailyData) - 3]. "\n" .
				"DOWNLOAD (pos: " . $pos["download"] . "): " . $data[$pos["download"]] . " - CAPTURED: " . $dailyData[count($dailyData) - 2] . "\n" .
				"UPLOAD (pos: " . $pos["upload"] . "): " . $data[$pos["upload"]]. " - CAPTURED: " . $dailyData[count($dailyData) - 1] . "\n");

			$pos["date"] += $pos["data_interval"];
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
		$volume["used"] = $totalUsedVolume;

		/* remaining */
		if ($this->unlimited == false)
		{
			$temp = explode(" ", $data[$pos["total"]]);
			$volume["remaining"] = $temp[10] * 1024 - $totalUsedVolume;
		}
		else
		{
			/* Unlimited account, so we will put such a huge value remaining that this should stay on 0% */
			$volume["remaining"] = $totalUsedVolume * 10000;
		}
			
		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;
		$returnValue["daily"] = $dailyData;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
