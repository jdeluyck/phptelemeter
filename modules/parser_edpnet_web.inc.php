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
		/* log in & get data */
		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("tbUserName" => $userName, "tbPassword" => $password)));
		if ($this->checkForError($data) !== false)
			return (false);

		
		/* go to the packages page, and get the serv_id and client_id */
		$data2 = $this->docurl($this->url["details"], FALSE);
		if ($this->checkForError($data2) !== false)
			return (false);

		$log = explode("\n", $log);

echo "THIS DOES NOT WORK YET!!!";
exit (-1);

		/* figure out the stats exact url */
		for ($i = 0; $i < count($log); $i++)
		{
			if ($log2 = strstr($log[$i], $this->url["stats"]))
				break;
		}

		$this->url["stats"] = substr($log2,0,strpos($log2,"'"));

		dumpDebugInfo($this->debug, "DEBUG: STATS URL: " . $this->url["stats"] . "\n");

		/* and get the data */
		$data = $this->doCurl($this->url["stats"], FALSE);
		if ($this->checkForError($data) !== false)
			return (false);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		if ($this->checkForError($log) !== false)
			return (false);

		$data = explode("\n", $data);

		/* find the entry position */
		for ($i = 0; $i < count($data); $i++)
		{
			if ($data2 = stristr($data[$i], "total traffic downloaded in broadband"))
				break;
		}

		$data2 = explode("<br>", $data2);

		/* set some default positions */
		$pos["remaining"] = false;

		dumpDebugInfo($this->debug, "DEBUG: \$data2, pre cleanup!\n");
		dumpDebugInfo($this->debug, $data2);

		/* position finding & data cleanup */
		for ($i = 0; $i < count($data2); $i++)
		{
			$data2[$i] = strip_tags($data2[$i]);

			if (stristr($data2[$i], "total traffic downloaded") !== false)
				$pos["traffic"] = $i;
			elseif (stristr($data2[$i], "next counter reset") !== false)
				$pos["reset_date"] = $i;
			elseif (stristr($data2[$i], "remaining") !== false)
				$pos["remaining"] = $i;

			/* data cleanup */
			$data2[$i] = substr($data2[$i], strpos($data2[$i], ":") + 2);
		}

		dumpDebugInfo($this->debug, "DEBUG: \$data2\n");
		dumpDebugInfo($this->debug, $data2);

		dumpDebugInfo($this->debug, "POS:\n");
		dumpDebugInfo($this->debug, $pos);

		/* stats */
		/* total used */
		$volume["used"] = substr($data2[$pos["traffic"]],0,-3) * 1024;

		/* remaining, if exists? */
		if ($pos["remaining"] !== false)
			$volume["remaining"] = substr($data2[$pos["remaining"]],0,-3) * 1024;
		else
			$volume["remaining"] = 0;

		/* reset date */
		$reset_date = substr($data2[$pos["reset_date"]],0,10);

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
