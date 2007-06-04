<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_edpnet_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_edpnet_web.inc.php - file which contains the EDPNet web page parser module.

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

class telemeterParser_edpnet_web extends telemeterParser_web_shared
{
	var $_ISP = "edpnet";

	function telemeterParser_edpnet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("btnCheck" => "Check traffic");

		$this->url["login"] = "http://www.edpnet.be/traffic2.aspx?R=1";
		$this->url["details"] = "http://edpnet.be/traffic2_details.aspx";

		$this->errors = array("Invalid username or password" => "Invalid username or password");
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* log in & get initial data */
//		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("tbUserName" => $userName, "tbPassword" => $password)));
		$data = file_get_contents("/var/www/phptelemeter/trunk/temp/adsllogin.htm");
		if ($this->checkForError($data) !== false)
			return (false);

		/* get historical data */
//		$historicalData = $this->docurl($this->url["details"], FALSE);
		$historicalData = file_get_contents("/var/www/phptelemeter/trunk/temp/traffic2_details.aspx.htm");
		if ($this->checkForError($historicalData) !== false)
			return (false);

		/* stats */
		$data = $this->prepareData($data);

		/* find the entry position */
		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "Totaal") !== false)
				$pos["used"] = $i + 1;
			elseif(stristr($data[$i], "Toegestaan") !== false)
				$pos["max"] = $i + 1;
		}

		dumpDebugInfo($this->debug, "DEBUG: \$data\n");
		dumpDebugInfo($this->debug, $data);

		dumpDebugInfo($this->debug, "POS:\n");
		dumpDebugInfo($this->debug, $pos);

		/* total used */
		$volume["used"] = substr($data[$pos["used"]],0,strlen($data[$pos["used"]]) - 2);

		/* remaining */
		$volume["remaining"] = substr($data[$pos["max"]],0,strlen($data[$pos["max"]]) - 2) - $volume["used"];

		/* daily historical stats */
		/* cleanout */
		$historicalData = $this->prepareData(str_replace(array("</td>","</tr>"),"\n",$historicalData));

		array_shift($historicalData);
		array_shift($historicalData);
		array_shift($historicalData);

		dumpDebugInfo($this->debug, "DEBUG; \$historicalData\n");
		dumpDebugInfo($this->debug, $historicalData);


		/* reset date */
		$reset_date = 0;

		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
