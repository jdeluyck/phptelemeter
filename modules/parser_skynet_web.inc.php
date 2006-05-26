<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_skynet_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter_web.inc.php - file which contains the Skynet web page parser module.

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

class telemeterParser_skynet_web extends telemeterParser_web_shared
{
	var $_ISP = "skynet";

	function telemeterParser_skynet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("Submit" => "Inloggen");

		/* because skynet uses a non-common CA, disable the CA check */
		$this->_curlParams = array(CURLOPT_SSL_VERIFYPEER => 0);

		$this->url["login"] = "https://e-care.skynet.be/index.cfm?function=connection.getVolume";
		//$this->url["stats"] = "https://services.telenet.be/lngtlm/telemeter/detail.html";
		$this->url["logout"] = "https://e-care.skynet.be/index.cfm?function=login.logoff";

		$this->errors = array("sso.login.authfail.PasswordNOK" => "Incorrect password",
							"sso.login.authfail.LoginDoesNotExist" => "Incorrect username.",
							"sso.login.invaliduid" => "Invalid username",
							"sso.jump.nocookie" => "No cookie detected");
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{

		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("form_login" => $userName, "form_password" => $password)));
		$this->checkForError($data);

		/* get the data */
		//$data = $this->doCurl($this->url["stats"], FALSE);
		//$this->checkForError($data);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		$this->checkForError($log);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));
		$data = explode("\n", $data);

		for ($i = 0; $i < count($data); $i++)
			$data[$i] = trim($data[$i]);

		if ($this->debug == true)
			var_dump($data);

		$usedPos = 795;
		$remainingPos = 859;

		/* stats */
		/* total used */
		$temp = explode(" ", $data[$usedPos]);
		$volume[] = ($temp[0] * 1024) + $temp[2];

		/* remaining */
		$temp = explode(" ", $data[$remainingPos]);
		$volume[] = (substr($temp[0],1) * 1024) + $temp[2];

		/* reset date */
		//$reset_date = substr($data2[5],0,10);

		$returnValue["general"] = $volume;
		$returnValue["isp"] = $this->_ISP;
		//$returnValue["reset_date"] = $reset_date;

		if ($this->debug == true)
			print_r($returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
