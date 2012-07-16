<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_telemeter_web", "25");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter_web.inc.php - file which contains the Telemeter web page parser module.

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

class telemeterParser_telemeter_web extends telemeterParser_web_shared
{
	var $_ISP = "telenet";

	var $months;

	var $fup = false;

	function telemeterParser_telemeter_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("goto" => "https://www.telenet.be/mijntelenet/telemeter.do");
		$this->url["login"] = "https://www.telenet.be/sso/ext/signon.do";
		$this->url["telemeter"] = "https://www.telenet.be/mijntelenet/telemeter/telemeter.do?identifier="; 
/*		$this->url["telemeter_fup"] = "https://www.telenet.be/mijntelenet/telemeter/showFupUsage.do?identifier=";
		$this->url["telemeter_volume"] = "https://www.telenet.be/mijntelenet/telemeter/showUsage.do?identifier=";*/
		$this->url["cookie"] = false;
		$this->url["logout"] = "https://www.telenet.be/sso/ext/signoff.do";

		$this->errors = array("sso.login.authfail.PasswordNOK" => "Incorrect password",
							"sso.login.authfail.LoginDoesNotExist" => "Incorrect username.",
							"sso.login.invaliduid" => "Invalid username",
							"sso.jump.nocookie" => "No cookie detected");

	}

	/* EXTERNAL! */
	function getData($userName, $password, $subaccount)
	{
		$log = $this->doCurl($this->url["login"], $this->createPostFields(array("uid" => $userName, "pwd" => $password)));
		if ($this->checkForError($log) !== false)
			return (false);

		/* log on */
		$log = $this->doCurl($this->url["telemeter"] . $userName, FALSE);
		if ($this->checkForError($log) !== false)
			return (false);

		/* there's a <meta http-equiv="Refresh"> here that we need.  */
		$log = explode("\n", str_replace("&nbsp;", " ", trim($log)));
		for ($i = 0; $i < count($log); $i++)
		{
			$log[$i] = trim($log[$i]);
			if (stristr($log[$i], '<meta http-equiv="Refresh" content="0; URL=') !== false)
			{
				$this->url["cookie"] = substr($log[$i], 43,-4);
				break;
			}
		}
		if (! $this->url["cookie"])
		{	
			echo "Could not detect refresh url!\n";
			return (false);
		}

		dumpDebugInfo($this->debug,"REFRESH URL: " . $this->url["cookie"] . "\n");
		
		/* cookie monster! */
		$data = $this->doCurl($this->url["cookie"], FALSE);
		if ($this->checkForError($data) !== false)
			return (false);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		if ($this->checkForError($log) !== false)
			return (false);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));

		/* fup counter? */
		if (stristr($data, "grootverbruiker") !== false)
		{
			$this->fup = true;
			$this->_ISP = "telenet_fup";
		}

		/* explode! carnage! */
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
			if ($this->fup)
			{
				if (stristr($data[$i], "Telemeter-info over aanrekeningsperiode") !== false)
				{
					$pos["daterange"] = $i;
					$pos["trafficused"] = $i + 2;
					$pos["trafficleft"] = $i + 5;
				}
			}
			else
			{
				if (stristr($data[$i], "Herinneringen instellen") !== false)
					$pos["daterange"] = $i + 1;
				elseif (stristr($data[$i], "Je verbruikte volume wordt op 0 gezet op") !== false)
				{
					$pos["trafficused"] = $i - 3;
					$pos["trafficleft"] = $i - 2;
					$pos["trafficdetail"] = $i + 49;
				}
			}
		}

		dumpDebugInfo($this->debug,"POS:\n");
		dumpDebugInfo($this->debug,$pos);
		dumpDebugInfo($this->debug,"DATA:\n");
		dumpDebugInfo($this->debug,$data);

		if ($this->fup)
		{
			preg_match('"(\d+),(\d+)"', $data[$pos["trafficused"]], $used);
			$used = ($used[1] + ($used[2]/1024)) * 1024;
			preg_match_all('"(\d+),(\d+)"', $data[$pos["trafficleft"]], $remaining);
			$remaining = (($remaining[1][1] + ($remaining[2][1]/1024)) * 1024) - $used;
		}
		else
		{
			/* traffic - total */
			$downCorrection = 0;
	
			$used      = removeDots(substr($data[$pos["trafficused"]],0,-3));
			$remaining = removeDotS(substr($data[$pos["trafficleft"]],0,-3));
	
			/* determine the date range */
			$dateRange = explode(" ", $data[$pos["daterange"]]);
			dumpDebugInfo($this->debug, "DATERANGE:");
			dumpDebugInfo($this->debug, $dateRange);
	
			/* seems / in dates is interpreted als US dates, - is EU dates... go figure */
			$start = strtotime(str_replace("/","-",$dateRange[0]));
			$end   = strtotime(str_replace("/","-",$dateRange[4]));
	
			$days = intval(($end - $start) / 86400) + 1;
	
			dumpDebugInfo($this->debug,
				"start: ". $start. " (". date("Y-m-d", $start). ") -- " .
				"end: ". $end. " (". date("Y-m-d", $end). ") -- " .
				"days: ". $days);
	
			/* now do the magic for getting the values of the days */
			for ($i = 1; $i <= $days; $i++)
			{
				$dailyMatches[] = date("d/m/y", $start + (($i - 1) * 86400));
				$dailyMatches[] = substr(removeDots($data[$pos["trafficdetail"]]),16,-2) + substr(removeDots($data[$pos["trafficdetail"]++ + 30]),16,-2);
			}
	
			$endDate = $dailyMatches[count($dailyMatches) - 2];
			$resetDate = date("d/m/Y", $end + 86400);
		}

		$generalMatches["used"] = $used;
		$generalMatches["remaining"] = $remaining;

		$returnValue["general"] = $generalMatches;
		$returnValue["daily"] = $dailyMatches;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $resetDate;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
