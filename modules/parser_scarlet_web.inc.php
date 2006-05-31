<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_scarlet_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_scarlet_web.inc.php - file which contains the Scarlet web page parser module.

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
		$this->_postFields = array("op" => "login", "new_language" => "english", "submit" => "login");

		$this->url["login"] = "http://customercare.scarlet.be/index.jsp?language=nl";
		$this->url["stats"] = "http://customercare.scarlet.be/usage/detail.do";
		$this->url["logout"] = "http://customercare.scarlet.be/logoff.do";

		$this->errors = array("Uw gebruikersnaam of wachtwoord is fout ingegeven." => "Incorrect login",
							"Vergeet uw wachtwoord niet in te voeren.", "No password supplied",
							"Vergeet uw gebruikersnaam niet in te voeren.", "No username supplied");

		$this->months = array("januari" => 1, "februari" => 2, "maart" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli" => 7, "augustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "december" => 12);
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* log in */
		//$log = $this->doCurl($this->url["login"], $this->createPostFields(array("username" => $userName, "password" => $password)));
		//$this->checkForError($log);

		/* and get the data */
		//$data = $this->doCurl($this->url["stats"], FALSE);
		//$this->checkForError($data);
		$data = file_get_contents("/tmp/scarletmeter.txt");

		/* logout */
		//$log = $this->doCurl($this->url["logout"], FALSE);
		//$this->checkForError($log);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));
		$data = explode("\n", $data);

		/* data interval */
		$pos["data_interval"] = 4;

		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = trim($data[$i]);

			if (strlen($data[$i]) != 0)
				$data2[] = $data[$i];

			/* determine positions */
			if (stristr($data[$i], "Periode van") !== false)
				$pos["daterange"] = count($data2) - 1;
			elseif (stristr($data[$i], "Uw maandelijkse factuur wordt opgesteld op") !== false)
				$pos["resetdate"] = count($data2) - 1;
			elseif ($data[$i] == "Download")
				$pos["download"] = count($data2) - 1 + $pos["data_interval"];
			elseif ($data[$i] == "Upload")
				$pos["upload"] = count($data2) - 1 + $pos["data_interval"];
			elseif (stristr($data[$i], "limiet van uw on-line") !== false)
				$pos["total"] = count($data2) - 1;
		}

		$data = $data2;
		unset($data2);

		if ($this->debug == true)
		{
			echo "DATA:\n";
			var_dump($data);

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
		for ($i = 1; $i <= $days; $i++)
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

			$str = str_replace(",", ".", $str);
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

		if ($this->debug == true)
			print_r($returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
