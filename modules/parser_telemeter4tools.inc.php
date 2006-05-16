<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_telemeter4tools", "7");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter4tools.inc.php - file which contains the Telemeter4tools parser module.

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

/* okay, we require the nusoap and xmlparser libraries. Load them. */
require_once("modules/libs/nusoap.inc.php");
require_once("modules/libs/xmlparser.inc.php");

class telemeterParser_telemeter4tools
{
	var $url = "https://telemeter4tools.services.telenet.be/TelemeterService?WSDL";

	var $_ISP = "telenet";

	var $useEndpointUrl = false;
	var $endpointUrl = "";

	var $errors_critical;
	var $errors_normal;

	var $debug = false;
	var $neededModules = "";

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

	function telemeterParser_telemeter4tools()
	{
		/* do some var initialisation */
		$this->errors_critical = array(
			"SYSERR_00001" => "Unexpected system error.",
			"ERRTLMTLS_00001" => "Unexpected system error.",
			"ERRTLMTLS_00002" => "Invalid input. Login or password is empty."
		);

		$this->errors_normal = array(
			"ERRTLMTLS_00003" => "Maximum of logins exceeded. Please try again later.",
			"ERRTLMTLS_00004" => "Incorrect login or password."
		);
	}

	/* exit function for us. */
	function destroy()
	{
		/* hmmm. nothing? */
	}

	/* Checks output from curl for errors */
	function checkForError($text, $errors, $quit)
	{
		$returnValue = false;

		foreach($errors as $errCode => $errDesc)
		{
			if (stristr($text, $errCode) !== FALSE)
				$returnValue .= $errDesc . "\n";
		}

		if ($returnValue !== false)
			doError("problem detected", trim($returnValue), $quit);

		return($returnValue);
	}

	function checkStatus($text)
	{
		$this->checkForError($text, $this->errors_critical, true);
		$returnValue = $this->checkForError($text, $this->errors_normal, false);

		return ($returnValue);
	}


	/* EXTERNAL! */
	function getData($userName, $password)
	{
		$returnValue = false;

		if (strlen($this->proxyHost) != 0)
		{
			if ($this->proxyAuth == true)
				$client = new soapclient($this->url, true, $this->proxyHost, $this->proxyPort, $this->proxyUsername, $this->proxyPassword);
			else
				$client = new soapclient($this->url, true, $this->proxyHost, $this->proxyPort);
		}
		else
			$client = new soapclient($this->url, true);

		if ($this->debug == true)
			echo $client->getDebug();

		/* Check for an error */
		$error = $client->getError();
		if ($error)
			doError("SOAP Error", $error, true);

		/* Do we need to override the endpoint url returned by the wdsl? */
		if ($this->useEndpointUrl == true)
			$client->setEndPoint($this->endpointUrl);

		$result = $client->call('getUsage', array($userName, $password));

		if ($this->debug == true)
			echo $client->getDebug();

		/* Check for a fault */
		if ($client->fault)
			doError("SOAP Fault", $result, true);
		else
		{
			/* Check for errors */
	    		$error = $client->getError();
    			if ($error)
				doError("SOAP Error", $error, true);
			else
			{
				/* now look for error messages */
				$parser = new XMLParser($result, 'raw', 1);
				$result = $parser->getTree();

				if ($this->debug == true) var_dump($result);

				/* look at the status */
				if ($this->checkStatus($result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:STATUS"]["VALUE"]) === false)
				{
					/* split off the global usage data */
					$general[0] = $result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:DATA"]["NS1:SERVICE"]["NS1:LIMITS"]["NS1:MAX-DOWN"]["VALUE"];
					$general[1] = $result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:DATA"]["NS1:SERVICE"]["NS1:LIMITS"]["NS1:MAX-UP"]["VALUE"];
					$general[2] = $result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:DATA"]["NS1:SERVICE"]["NS1:TOTALUSAGE"]["NS1:DOWN"]["VALUE"];
					$general[3] = $result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:DATA"]["NS1:SERVICE"]["NS1:TOTALUSAGE"]["NS1:UP"]["VALUE"];

					/* split off the daily data */
					foreach ($result["NS1:TELEMETER"]["NS1:USAGE-INFO"]["NS1:DATA"]["NS1:SERVICE"]["NS1:USAGE"] as $key => $value)
					{
						$daily[] = substr($value["ATTRIBUTES"]["DAY"],6,2) . "/" . substr($value["ATTRIBUTES"]["DAY"],4,2) . "/" . substr($value["ATTRIBUTES"]["DAY"],2,2);
						$daily[] = $value["NS1:DOWN"]["VALUE"];
						$daily[] = $value["NS1:UP"]["VALUE"];

						$i++;
					}

					$endDate = $daily[count($daily) - 3];
					$resetDate = date("d/m/Y", mktime(0,0,0,substr($endDate,3,2),substr($endDate,0,2) + 1,substr($endDate,6)));

					$returnValue["general"] = $general;
					$returnValue["daily"] = $daily;
					$returnValue["isp"] = $this->_ISP;
					$returnValue["reset_date"] = $resetDate;
				}
			}
		}

		if ($this->debug == true)
			print_r($returnValue);

		return ($returnValue);
	}

}

?>
