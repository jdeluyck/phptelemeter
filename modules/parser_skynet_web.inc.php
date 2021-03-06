<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_skynet_web", "11");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter_web.inc.php - file which contains the Skynet web page parser module.

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

class telemeterParser_skynet_web extends telemeterParser_web_shared
{
	var $_ISP = "skynet";

	function telemeterParser_skynet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("Submit2" => "Connect");

		/* because skynet uses a non-common CA, disable the CA check */
		$this->_curlParams = array(CURLOPT_SSL_VERIFYPEER => 0);

		$this->url["login"] = "https://admit.belgacom.be/ecare-slf/index.cfm?function=connection.getVolume#oldtimer";
		$this->url["logout"] = "https://admit.belgacom.be/ecare-slf/index.cfm?function=login.logoff";

		$this->errors = array("ese21Z-3" => "Technical problem or non-existant username.",
					"ese21Z-2" => "Password incorrect.",
					"forbidden access" => "Access denied - try again later.");
	}

	/* EXTERNAL! */
	function getData($userName, $password, $subaccount)
	{
		/* login and get the data */
		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("form_login" => $userName, "form_password" => $password)));
		if ($this->checkForError($data) !== false)
			return (false);

		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("form_login" => $userName, "form_password" => $password)));
		if ($this->checkForError($data) !== false)
			return (false);


		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		if ($this->checkForError($log) !== false)
			return (false);

		$data = $this->prepareData($data);

		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "out of") !== false)
				$usedPos = $i;
			elseif(stristr($data[$i], "remaining") !== false)
				$remainingPos = $i;
		}

		dumpDebugInfo($this->debug, "DATA:\n");
		dumpDebugInfo($this->debug, $data);

		/* stats */
		/* total used */
		$temp = explode(" ", $data[$usedPos]);
		if ($temp[1] == "MB")
			$volume["used"] = $temp[0] + $temp[2];
		else
			$volume["used"] = ($temp[0] * 1024) + $temp[2];

		/* remaining */
		$temp = explode(" ", $data[$remainingPos]);
		$volume["remaining"] = (substr($temp[0],1) * 1024) + $temp[2];

		/* resetdate. It's reset on the first of the next month */
		$resetDate = date("d/m/Y", mktime(0,0,0,date("m") + 1 ,1, date("Y")));

		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $resetDate;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		//@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
