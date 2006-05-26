<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_telemeter_web", "14");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter_web.inc.php - file which contains the Telemeter web page parser module.

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

class telemeterParser_telemeter_web extends telemeterParser_web_shared
{
	var $_ISP = "telenet";

	var $months;

	function getNeededModules()
	{
		return ($this->neededModules);
	}

	function telemeterParser_telemeter_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("goto" => "http://www.telenet.be/nl/mijntelenet/index.page?content=https%3A%2F%2Fwww.telenet.be%2Fsys%2Fsso%2Fjump.jsp%3Fhttps%3A%2F%2Fservices.telenet.be%2Fisps%2FMainServlet%3FACTION%3DTELEMTR");

		$this->url["login"] = "https://www.telenet.be/sys/sso/signon.jsp";
		$this->url["stats"] = "https://services.telenet.be/lngtlm/telemeter/detail.html";
		$this->url["logout"] = "https://www.telenet.be/sys/sso/signoff.jsp";

		$this->errors = array("sso.login.authfail.PasswordNOK" => "Incorrect password",
							"sso.login.authfail.LoginDoesNotExist" => "Incorrect username.",
							"sso.login.invaliduid" => "Invalid username",
							"sso.jump.nocookie" => "No cookie detected");

		$this->months = array("januari" => 1, "februari" => 2, "maart" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli" => 7, "augustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "december" => 12);
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		$log = $this->doCurl($this->url["login"], $this->createPostFields(array("uid" => $userName, "pwd" => $password)));
		$this->checkForError($log);

		/* get the data */
		$data = $this->doCurl($this->url["stats"], FALSE);
		$this->checkForError($data);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		$this->checkForError($log);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));
		$data = explode("\n", $data);

		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = trim($data[$i]);
			if (strlen($data[$i]) != 0 && stristr($data[$i], "gratis") === false)
				$data3[] = $data[$i];
		}

		if ($this->debug == true)
			var_dump($data3);

		/* download - total */
		$downCorrection = 0;

		$used      = removeDots(substr($data3[29],0,-3));
		$remaining = removeDotS(substr($data3[30],0,-3));

		$generalMatches[0] = $remaining + $used;
		$generalMatches[2] = $used;

		/* upload - total */
		$used      = removeDots(substr($data3[152],0,-3));
		$remaining = removeDots(substr($data3[153],0,-3));

 		$generalMatches[1] = $remaining + $used;
		$generalMatches[3] = $used;

		/* determine the date range */
		$dateRange = explode(" ", $data3[2]);

		/* change the month */
		$dateRange[3] = $this->months[$dateRange[3]];
		$dateRange[7] = $this->months[$dateRange[7]];

		if ($this->debug == true)
			var_dump($dateRange);

		$start = mktime(0, 0, 0, $dateRange[3], $dateRange[2], $dateRange[4]);
		$end = mktime(0, 0, 0, $dateRange[7], $dateRange[6], $dateRange[8]);

		$days = intval(($end - $start) / 86400) + 1;

		if ($this->debug == true)
		{
			echo "start: ", $start, " ", date("Y-m-d", $start), "\n";
			echo "end: ", $end, " ", date("Y-m-d", $end), "\n";
			echo "days: ", $days, " \n";
		}

		/* now do the magic for getting the values of the days */
		$downloadPos = 35;
		$uploadPos = 155;

		for ($i = 1; $i <= $days; $i++)
		{

			if ($data3[$downloadPos] == "&gt;")
			{
				$downloadPos++;
				$uploadPos++;
			}

			$dailyMatches[] = date("d/m/y", $start + (($i - 1) * 86400));
			$dailyMatches[] = removeDots($data3[++$downloadPos]) + removeDots($data3[++$downloadPos]);
			$dailyMatches[] = removeDots($data3[++$uploadPos]) + removeDots($data3[++$uploadPos]);

			/* increase pos by one, we don't care for the dates */
			$downloadPos++;
			$uploadPos++;
		}

		$endDate = $dailyMatches[count($dailyMatches) - 3];
		$resetDate = date("d/m/Y", mktime(0,0,0,substr($endDate,3,2),substr($endDate,0,2) + 1,substr($endDate,6)));


		$returnValue["general"] = $generalMatches;
		$returnValue["daily"] = $dailyMatches;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $resetDate;

		if ($this->debug == true)
			print_r($returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
