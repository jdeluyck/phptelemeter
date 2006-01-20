<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser", "telemeter_web");
define("_phptelemeter_parser_version", "8");
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

	var $months;

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
		/* do some var initialisation */
		$this->_cookieFile = tempnam("/tmp/", "phptelemeter");

		$this->url["login"] = "https://www.telenet.be/sys/sso/signon.jsp";
		$this->url["stats"] = "https://services.telenet.be/lngtlm/detail.html";
		$this->url["logout"] = "https://www.telenet.be/sys/sso/signoff.jsp";

		$this->errors = array("sso.login.authfail.PasswordNOK" => "Incorrect password",
							"sso.login.authfail.LoginDoesNotExist" => "Incorrect username.",
							"sso.login.invaliduid" => "Invalid username",
							"sso.jump.nocookie" => "No cookie detected");

		//$this->months["nl"] = array("januari" => 1, "februari" => 2, "maart" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli"    => 7, "augustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "december" => 12);
		//$this->months["en"] = array("january" => 1, "february" => 2, "march" => 3, "april" => 4, "may" => 5, "june" => 6, "july"    => 7, "august"   => 8, "september" => 9, "october" => 10, "november" => 11, "december" => 12);
		//$this->months["fr"] = array("janvier" => 1, "f�vrier"  => 2, "mars"  => 3, "avril" => 4, "mai" => 5, "juin" => 6, "juillet" => 7, "ao�t"     => 8, "septembre" => 9, "octobre" => 10, "novembre" => 11, "d�cembre" => 12);
		$this->months = array("januari" => 1, "februari" => 2, "maart" => 3, "april" => 4, "mei" => 5, "juni" => 6, "juli"    => 7, "augustus" => 8, "september" => 9, "oktober" => 10, "november" => 11, "december" => 12);
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

		/* get the data */		
		$data = $this->doCurl($this->url["stats"], FALSE);
		$this->checkForError($data);

		/* logout */
		$log = $this->doCurl($this->url["logout"], FALSE);
		$this->checkForError($log);

		/* clean out the data a bit */
		$data = str_replace("&nbsp;", " ", trim(strip_tags($data)));
		$data = explode("\n", $data);

		for ($i = 0; $i < count($data); $i++)
		{
			$data[$i] = trim($data[$i]);
			if (strlen($data[$i]) != 0)
				$data3[] = $data[$i];
		}

		if ($this->debug == true)
			var_dump($data3);

		/* download - total */
		$correction = 0;

		$start = 28;
		if (strtolower($data3[$start++]) != "totaal")
			$correction++;

		$used      = str_replace(".", "", substr($data3[$start++ + $correction],0,-3));
		$remaining = str_replace(".", "", substr($data3[$start + $correction],0,-3));

		$generalMatches[0] = $remaining + $used;
		$generalMatches[2] = $used;

		/* upload - total */
		$start = 151;
		if (strtolower($data3[$start++]) != "totaal")
			$correction++;

		$used      = str_replace(".", "", substr($data3[$start++ + $correction],0,-3));
		$remaining = str_replace(".", "", substr($data3[$start + $correction],0,-3));

 		$generalMatches[1] = $remaining + $used;
		$generalMatches[3] = $used;

		/* determine the date range */
		$dateRange = explode(" ", $data3[2]);
		
		// change the month
		$dateRange[3] = $this->months[$dateRange[3]];
		$dateRange[7] = $this->months[$dateRange[7]];

		$start = mktime(0, 0, 0, $dateRange[3], $dateRange[2], $dateRange[4]);
		$end = mktime(0, 0, 0, $dateRange[7], $dateRange[6], $dateRange[8]);

		$days = intval(($end - $start) / 86400) + 1;

		if ($this->debug == true)
		{
			echo "start: ", $start, " ", date("Y-m-d", $start), "\n";
			echo "end: ", $end, " ", date("Y-m-d", $end), "\n";
			echo "days: ", $days, " \n";
		}

		/* now do the magic for getting the values of the days */
		$downloadPos = 35 + $correction;
		$uploadPos = 158 + $correction;

		for ($i = 1; $i <= $days; $i++)
		{
	
			if ($data3[$downloadPos] == "&gt;")
			{
				$downloadPos++;
				$uploadPos++;
			}
			
			$dailyMatches[] = date("d-m-y", $start + (($i - 1) * 86400));
			$dailyMatches[] = $data3[++$downloadPos] + $data3[++$downloadPos];
			$dailyMatches[] = $data3[++$uploadPos] + $data3[++$uploadPos];

			/* increase pos by one, we don't care for the dates */
			$downloadPos++;
			$uploadPos++;
		}			

		$returnValue["general"] = $generalMatches;
		$returnValue["daily"] = $dailyMatches;

		if ($this->debug == true)
			print_r($returnValue);

		// we need to unlink the cookiefile here, otherwise we get 'ghost' data.
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
