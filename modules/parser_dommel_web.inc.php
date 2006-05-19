<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_dommel_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_dommel_web.inc.php - file which contains the Dommel web page parser module.

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

class telemeterParser_dommel_web
{
	var $_userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
	var $url;
	//var $_url = "https://crm.schedom-europe.net/include/scripts/linked/dslinfo/dslinfo.php?servid=19039&";
	var $_ISP = "dommel";
	var $_postFields = "op=login&new_language=english&submit=login";

	var $_cookieFile;
	var $errors;
	var $debug = false;
	var $neededModules = array("curl");

	//var $months;

	var $proxyHost;
	var $proxyPort;
	var $proxyAuth;
	var $proxyUsername;
	var $proxyPassword;

	function setDebug($debug)
	{
		$this->debug = $debug;
	}

	function setProxy($proxyHost, $proxyPort, $proxyAuth, $proxyUsername, $proxyPassword)
	{
		$this->proxyHost = $proxyHost;
		$this->proxyPort = $proxyPort;
		$this->proxyAuth = $proxyAuth;
		$this->proxyUsername = $proxyUsername;
		$this->proxyPassword = $proxyPassword;
	}

	function getNeededModules()
	{
		return ($this->neededModules);
	}

	function telemeterParser_dommel_web()
	{
		/* do some var initialisation */
		$this->_cookieFile = tempnam(_tempdir, "phptelemeter");

		$this->url["login"] = "https://crm.schedom-europe.net/index.php";
		$this->url["packages"] = "https://crm.schedom-europe.net/user.php?op=view&tile=mypackages";
		$this->url["stats"] = "https://crm.schedom-europe.net/include/scripts/linked/dslinfo/dslinfo.php";
		$this->url["logout"] = "https://crm.schedom-europe.net/index.php?op=logout";
		$this->errors = array("your login is incorrect." => "Incorrect login");
	}

	/* exit function for us. Destroys the cookiefile */
	function destroy()
	{
		@unlink ($this->_cookieFile);
	}

	/* Returns the postfields string with the authentication fields intact */
	function createAuthPostFields($username, $password)
	{
		return ($this->_postFields . "&username=" . $username . "&password=" . $password);
	}

	/* Checks output from curl for errors */
	function checkForError($log)
	{
		if ($this->debug)
			echo "\n" . $log . "\n";

		$returnValue = false;

		foreach($this->errors as $errCode => $errDesc)
		{
			if (stristr($log, $errCode) !== FALSE)
				$returnValue .= $errDesc . "\n";
		}

		if ($returnValue !== false)
			doError("problem detected", trim($returnValue), true);
	}

	/* Does some CURLing (no, not that strange sport on ice that l... I disgress. */
	function doCurl($URL, $postFields)
	{
		if ($this->debug == true) echo "CURL: $URL\n";

		$ch = curl_init($URL);

		if ($postFields !== false)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			if ($this->debug == true) echo "CURL: POST: $postFields\n";
		}

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->_cookieFile);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->_cookieFile);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_userAgent);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);

		/* check proxy */
		if (strlen($this->proxyHost) != 0)
		{
			if ($this->debug == true)
				echo "CURL: Enabling proxy: " .$this->proxyHost . ":" . $this->proxyPort . "\n";

			curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ":" . $this->proxyPort);

			if ($this->proxyAuth == true)
			{
				if ($this->debug == true)
					echo "CURL: Enabling proxy AUTH\n";

				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername . ":" . $this->proxyPassword);
			}
		}

		$output = curl_exec($ch);
		if (curl_errno($ch) != 0)
			doError("curl error occurred", curl_error($ch), true);

		curl_close($ch);

		return ($output);
	}


	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* log in */
		$log = $this->doCurl($this->url["login"], $this->createAuthPostFields($userName, $password));
		$this->checkForError($log);

		/* go to the packages page, and get the serv_id and client_id */
		$log = $this->docurl($this->url["packages"], FALSE);
		$this->checkforError($log);

		$log = explode("\n", $log);

		/* figure out the stats exact url */
		for ($i = 0; $i < count($log); $i++)
		{
			if ($log2 = strstr($log[$i], $this->url["stats"]))
				break;
		}

		$this->url["stats"] = substr($log2,0,strpos($log2,"'"));

		if ($this->debug == true)
			echo "DEBUG: STATS URL: " . $this->url["stats"] . "\n";

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
