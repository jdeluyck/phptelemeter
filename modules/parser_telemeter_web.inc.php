<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser", "telemeter_web");
define("_phptelemeter_parser_version", "6");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

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

class telemeterParser
{
	var $_userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
	var $_postFields = "goto=http://www.telenet.be/nl/mijntelenet/index.page?content=https%3A%2F%2Fwww.telenet.be%2Fsys%2Fsso%2Fjump.jsp%3Fhttps%3A%2F%2Fservices.telenet.be%2Fisps%2FMainServlet%3FACTION%3DTELEMTR";
	var $_cookieFile;
	var $url;
	var $errors;
	var $debug = false;
	var $neededModules = array("curl");

	function setDebug($debug)
	{
		$this->debug = $debug;
	}

	function getNeededModules()
	{
		return ($this->neededModules);
	}

	function telemeterParser()
	{
		//$this->_userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
		//$this->_postFields = "goto=http://www.telenet.be/nl/mijntelenet/index.page?content=https%3A%2F%2Fwww.telenet.be%2Fsys%2Fsso%2Fjump.jsp%3Fhttps%3A%2F%2Fservices.telenet.be%2Fisps%2FMainServlet%3FACTION%3DTELEMTR";
		/* do some var initialisation */
		$this->_cookieFile = tempnam("/tmp/", "phptelemeter");

		$this->url["login"] = "https://www.telenet.be/sys/sso/signon.jsp";
		$this->url["generalStats"] = "https://www.telenet.be/sys/sso/jump.jsp?https://services.telenet.be/isps/MainServlet?ACTION=TELEMTR&SSOSID=\$SSOSID\$";

		$this->url["dailyStats"] = "https://services.telenet.be/isps/be/telenet/ebiz/ium/Histogram.jsp";
		$this->url["logout"] = "https://www.telenet.be/sys/sso/signoff.jsp";

		$this->errors = array("sso.login.authfail.PasswordNOK" => "Incorrect password",
							"sso.login.authfail.LoginDoesNotExist" => "Incorrect username.",
							"sso.login.invaliduid" => "Invalid username",
							"sso.jump.nocookie" => "No cookie detected");
	}

	/* exit function for us. Destroys the cookiefile */
	function destroy()
	{
		@unlink ($this->_cookieFile);
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

	/* Returns the postfields string with the authentication fields intact */
	function createAuthPostFields($uid, $password)
	{
		return ($this->_postFields . "&uid=" . $uid . "&pwd=" . $password);
	}

	/* Does some CURLing (no, not that strange sport on ice that l... I disgress. */
	function doCurl($URL, $postFields)
	{
		//global $configuration;

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

		$output = curl_exec($ch);
		if (curl_errno($ch) != 0)
			doError("curl error occurred", curl_error($ch), true);

		curl_close($ch);

		return ($output);
	}


	/* EXTERNAL! */
	function getData($userName, $password)
	{
		$log = $this->doCurl($this->url["login"], $this->createAuthPostFields($userName, $password));
		$this->checkForError($log);

		/* main statistics */
		$generalData = $this->doCurl($this->url["generalStats"], FALSE);
		$this->checkForError($generalData);

		/* Now, try to find the GB indicators. */
		$wordList = preg_split("/[\s]+/", trim(strip_tags($generalData)));

		for ($j = 0; $j < count($wordList); $j++)
		{
			if (strpos($wordList[$j], "GB") !== false)
				$temp[] = $wordList[$j-1];
		}

		$generalMatches = array_merge($temp, $generalMatches[1]);

		$generalMatches[1] = str_replace(",",".",$generalMatches[1]);

		/* daily view data */
		$dailyData = $this->doCurl($this->url["dailyStats"], FALSE);
		$this->checkForError($dailyData);

		/* parse the dailyData. */
		preg_match_all("{\t{6,7}(\d\d/\d\d/\d\d|\d+)}", $dailyData, $dailyMatches);
		$dailyMatches = $dailyMatches[1];

		/* logout */
		$this->doCurl($this->url["logout"], FALSE);

		/* reformat to MB */
		$generalMatches[0] = $generalMatches[0] * 1024;
		$generalMatches[1] = $generalMatches[1] * 1024;

		/* calculate the used amounts */
		$generalMatches[2] = $generalMatches[3] = 0;
		for ($i = 0; $i < count($dailyMatches); $i++)
		{
			$i++;
			$generalMatches[2] += $dailyMatches[$i++];
			$generalMatches[3] += $dailyMatches[$i];
		}

		$returnValue["general"] = $generalMatches;
		$returnValue["daily"] = $dailyMatches;

		if ($this->debug == true)
			print_r($returnValue);

		return ($returnValue);
	}
}

?>
