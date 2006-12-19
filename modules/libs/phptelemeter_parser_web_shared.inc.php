<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

phptelemeter_parser_web_shared.inc.php - file which contains the general class for phptelemeter webbased parsers

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

		if ($this->debug == true)
			echo "Postfields: $returnValue\n";

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

		if ($returnValue === false)
		{
			$errorTitle = "problem detected";

			/* nope, no curl errors. Check for other errors. */
			if (is_array($this->errors))
			{
				if ($this->debug)
					echo "\n" . $log . "\n";

				foreach($this->errors as $errCode => $errDesc)
				{
					if (stristr($log, $errCode) !== FALSE)
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
		if ($this->debug == true) echo "CURL: $URL\n";

		$ch = curl_init($URL);

		/* any extra curl parameters to pass around? */
		if ($this->_curlParams !== false)
		{
			if ($this->debug == true)
			{
				echo "CURL: Extra settings:\n";
				var_dump($this->_curlParams);
			}

			foreach ($this->_curlParams as $key => $value)
				curl_setopt($ch, $key, $value);
		}

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
		{
			//doError("curl error occurred", curl_error($ch), true, $this->ignoreErrors);
			$output["curl_error"] = curl_error($ch);
		}

		curl_close($ch);

		return ($output);
	}
}

?>
