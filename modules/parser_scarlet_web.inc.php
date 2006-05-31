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

	function telemeterParser_scarlet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("op" => "login", "new_language" => "english", "submit" => "login");

		$this->url["login"] = "http://customercare.scarlet.be/logon.jsp";
		$this->url["stats"] = "http://customercare.scarlet.be/usage/detail.do";
		//$this->url["logout"] = "https://crm.schedom-europe.net/index.php?op=logout";

		$this->errors = array("your login is incorrect." => "Incorrect login");
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* log in */
		$log = $this->doCurl($this->url["login"], $this->createPostFields(array("username" => $userName, "password" => $password)));
		$this->checkForError($log);

		/* and get the data */
		$data = $this->doCurl($this->url["stats"], FALSE);
		$this->checkForError($data);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		$this->checkForError($log);

		$data = explode("\n", $data);

		for ($i = 0; $i < count($data); $i++)
		{
			if ($data2 = strstr($data[$i], "download :"))
				break;
		}

		$data2 = explode("<br>", $data2);

		for ($i = 0; $i < count($data2); $i++)
		{
			$data2[$i] = strip_tags($data2[$i]);
			$data2[$i] = substr($data2[$i], strpos($data2[$i], ":") + 2);
		}

		if ($this->debug == true)
		{
			echo "DEBUG: \$data2\n";
			var_dump($data2);
		}

		/* stats */
		/* total used */
		$volume[] = substr($data2[2],0,-3) * 1024;

		/* remaining */
		$volume[] = substr($data2[4],0,-3) * 1024;

		/* reset date */
		$reset_date = substr($data2[5],0,10);

		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;

		if ($this->debug == true)
			print_r($returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
