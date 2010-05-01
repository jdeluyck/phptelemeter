<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_mobilevikings_api", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_mobilevikings_api.inc.php - file which contains the mobilevikings api module.

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

class telemeterParser_mobilevikings_api extends telemeterParser_web_shared
{
	var $_ISP = "mobilevikings";

	var $protocol = "https";

	function telemeterParser_mobilevikings_api()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->url["sim_balance"] = "mobilevikings.com/api/1.0/rest/mobilevikings/sim_balance.json";
		$this->errors = array("Authorization Required" => "Incorrect username/password");
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* this should return a json */
		$log = file_get_contents($this->protocol . "://" . $userName . ":" . $password . "@" .$this->url["sim_balance"]);

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
				$returnValue["general"]["used"] = 1024;
				$returnValue["general"]["remaining"] = 0;
			}
			else
			{
				$returnValue["general"]["used"] = $json["data"];
				$returnValue["general"]["remaining"] = 1024 - $returnValue["general"]["used"];
			}
		

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
