<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_telemeter_web", "17");
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

	function telemeterParser_telemeter_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("goto" => "http://www.telenet.be/sys/mijntelenet/index.page?locale=nl");
		$this->url["login"] = "https://www.telenet.be/sys/sso/signon.jsp";
		$this->url["telemeter"] = "https://services.telenet.be/lngtlm/telemeter/overview.html?identifier=";
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

		$log = $this->doCurl($this->url["telemeter"] . $userName, FALSE);
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

		$data = $data3;

		/* determine positions */
		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "Detail facturatieperiode") !== false)
				$pos["daterange"] = $i;
			elseif (stristr($data[$i], "Ontvangen gegevens (download)") !== false)
			{
				$pos["downloadused"] = $i + 17;
				$pos["downloadleft"] = $i + 18;
				$pos["downloaddetail"] = $i + 23;
			}
			elseif (stristr($data[$i], "Verstuurde gegevens (upload)") !== false)
			{
				$pos["uploadused"] = $i + 17;
				$pos["uploadleft"] = $i + 18;
				$pos["uploaddetail"] = $i + 23;
			}
		}

		if ($this->debug == true)
		{
			echo "POS:\n";
			var_dump($pos);
			echo "DATA:\n";
			var_dump($data);
		}

		/* download - total */
		$downCorrection = 0;

		$used      = removeDots(substr($data[$pos["downloadused"]],0,-3));
		$remaining = removeDotS(substr($data[$pos["downloadleft"]],0,-3));

		$generalMatches[0] = $remaining + $used;
		$generalMatches[2] = $used;

		/* upload - total */
		$used      = removeDots(substr($data[$pos["uploadused"]],0,-3));
		$remaining = removeDots(substr($data[$pos["uploadleft"]],0,-3));

 		$generalMatches[1] = $remaining + $used;
		$generalMatches[3] = $used;

		/* determine the date range */
		$dateRange = explode(" ", $data[$pos["daterange"]]);

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
		for ($i = 1; $i <= $days; $i++)
		{

			if ($data[$pos["downloaddetail"]] == "&gt;")
			{
				$pos["downloaddetail"]++;
				$pos["uploaddetail"]++;
			}

			$dailyMatches[] = date("d/m/y", $start + (($i - 1) * 86400));
			$dailyMatches[] = removeDots($data[++$pos["downloaddetail"]]) + removeDots($data[++$pos["downloaddetail"]]);
			$dailyMatches[] = removeDots($data[++$pos["uploaddetail"]]) + removeDots($data[++$pos["uploaddetail"]]);

			/* increase pos by one, we don't care for the dates */
			$pos["downloaddetail"]++;
			$pos["uploaddetail"]++;
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
