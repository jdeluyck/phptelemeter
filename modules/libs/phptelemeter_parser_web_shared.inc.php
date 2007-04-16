<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

phptelemeter_parser_web_shared.inc.php - file which contains the general class for phptelemeter webbased parsers

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

class telemeterParser_web_shared
{
	var $_userAgent = "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)";
	var $_cookieFile;
	var $_postFields;
	var $_curlParams = false;

	var $url;
	var $errors;
	var $debug = false;
	var $ignoreErrors = false;
	var $neededModules = array("curl");


	var $proxyHost;
	var $proxyPort;
	var $proxyAuth;
	var $proxyUsername;
	var $proxyPassword;

	function setIgnoreErrors($ignoreErrors)
	{
		$this->ignoreErrors = $ignoreErrors;
	}

	function setDebug($debug)
	{
		$this->debug = $debug;
	}

	function createPostFields($additionalFields=false)
	{
		$returnValue = "";

		if (is_array($this->_postFields))
		{
			foreach ($this->_postFields as $key => $value)
				$returnValue .= $key . "=" . $value . "&";
		}

		if ($additionalFields != false)
		{
			foreach ($additionalFields as $key => $value)
				$returnValue .= $key . "=" . $value . "&";
		}

		$returnValue = substr($returnValue,0,-1);

		dumpDebugInfo($this->debug, "Postfields: ". $returnValue."\n");
		return ($returnValue);
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

	function telemeterParser_web_shared()
	{
		/* do some var initialisation */
		$this->_cookieFile = tempnam(_tempdir, "phptelemeter");
	}

	/* exit function for us. Destroys the cookiefile */
	function destroy()
	{
		@unlink ($this->_cookieFile);
	}

	/* checks output from Curl for errors */
	function checkForCurlError($log)
	{
		$returnValue = false;

		if (is_array($log) && array_key_exists("curl_error", $log))
			$returnValue = $log["curl_error"];

		return ($returnValue);
	}

	/* Checks output for errors */
	function checkForError($log)
	{
		/* check for any curl errors */
		$returnValue = $this->checkForCurlError($log);

		dumpDebugInfo($this->debug, "CURL error check:\n" . $returnValue);

		if ($returnValue === false)
		{
			$errorTitle = "problem detected";

			/* nope, no curl errors. Check for other errors. */
			if (is_array($this->errors))
			{
				dumpDebugInfo($this->debug, "Error checking in: \n" . $log . "\n");

				foreach($this->errors as $errCode => $errDesc)
				{
					dumpDebugInfo($this->debug, "Matching against: $errCode\n");

					if (stristr($log, $errCode) !== false)
						$returnValue .= $errDesc . "\n";
				}
			}
		}
		else
			$errorTitle = "curl error";

		if ($returnValue !== false)
				doError($errorTitle, trim($returnValue), true, $this->ignoreErrors);

		return($returnValue);
	}

	/* Does some CURLing (no, not that strange sport on ice that l... I disgress. */
	function doCurl($URL, $postFields)
	{
		dumpDebugInfo($this->debug, "CURL: " . $URL . "\n");

		$ch = curl_init($URL);

		/* any extra curl parameters to pass around? */
		if ($this->_curlParams !== false)
		{
			dumpDebugInfo($this->debug, "CURL: Extra settings:\n");
			dumpDebugInfo($this->debug, $this->_curlParams);

			foreach ($this->_curlParams as $key => $value)
				curl_setopt($ch, $key, $value);
		}

		if ($postFields !== false)
		{
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
			dumpDebugInfo($this->debug, "CURL: POST: $postFields\n");
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
			dumpDebugInfo($this->debug, "CURL: Enabling proxy: " . $this->proxyHost . ":" . $this->proxyPort . "\n");

			curl_setopt($ch, CURLOPT_PROXY, $this->proxyHost . ":" . $this->proxyPort);

			if ($this->proxyAuth == true)
			{
				dumpDebugInfo($this->debug, "CURL: Enabling proxy AUTH\n");
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUsername . ":" . $this->proxyPassword);
			}
		}

		$output = curl_exec($ch);
		if (curl_errno($ch) != 0)
			$output["curl_error"] = curl_error($ch);

		curl_close($ch);

		return ($output);
	}
}

?>
