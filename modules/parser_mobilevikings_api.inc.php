<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_mobilevikings_api", "3");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_mobilevikings_api.inc.php - file which contains the mobilevikings api module.

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

class telemeterParser_mobilevikings_api extends telemeterParser_web_shared
{
	var $_ISP = "mobilevikings";

	var $protocol = "https";
	var $maxTransfer = 2048;

	function telemeterParser_mobilevikings_api()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->url["sim_balance"] = "mobilevikings.com/api/2.0/basic/sim_balance.json";
		$this->errors = array("Authorization Required" => "Incorrect username/password",
				      "503 SERVICE UNAVAILABLE" => "Maximum of logins exceeded. Please try again later.");
	}

	/* EXTERNAL! */
	function getData($userName, $password, $subaccount)
	{
		/* this should return a json */
		$log = @file_get_contents($this->protocol . "://" . $userName . ":" . $password . "@" . $this->url["sim_balance"] . ($subaccount != ""?"?msisdn=" . $subaccount:""));
  
		/* check that we haven't been throttled */
		if (isset($http_response_header[0]) && $this->checkForError($http_response_header[0]) !== false)
			return (false);

		if ($this->checkForError($log) !== false)
			return (false);

		dumpDebugInfo($this->debug, $log);

		$json = json_decode($log,true);
		dumpDebugInfo($this->debug, $json);

		if ($json !== false)
		{
			/* check if it's expired, then set volume to 100% */
			if ($json["is_expired"] == true)
			{
				dumpDebugInfo($this->debug, "bundle expired! Setting used to 100%...");
				$returnValue["general"]["used"] = $this->maxTransfer;
				$returnValue["general"]["remaining"] = 0;
			}
			else
			{
				$returnValue["general"]["remaining"] = ($json["data"] / 1048576);
				$returnValue["general"]["used"] = $this->maxTransfer - $returnValue["general"]["remaining"];
			}

			$returnValue["reset_date"] = substr($json["valid_until"],8,2) . "/" . substr($json["valid_until"],5,2) . "/" . substr($json["valid_until"],0,4);
			$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);
			$returnValue["isp"] = $this->_ISP;
		}
		else
		{
			$returnValue = false;
		}
		dumpDebugInfo($this->debug, $returnValue);

		return ($returnValue);
	}
}

?>
