<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser", "telemeter4tools");
define("_phptelemeter_parser_version", "6");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

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

class telemeterParser
{
	var $url = "https://telemeter4tools.telenet.be/TelemeterService?wsdl";

	var $useEndpointUrl = true;
	var $endpointUrl = "https://telemeter4tools.services.telenet.be/TelemeterService";

	var $errors_critical;
	var $errors_normal;
	var $debug = false;
	var $neededModules = "";

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
		$this->errors_critical = array(
			"ERRTM4TLS_00001" => "Unexpected system error.",
			"ERRTM4TLS_00002" => "Invalid input"
		);

		$this->errors_normal = array(
			"ERRTM4TLS_00003" => "Login does not exist.",
			"ERRTM4TLS_00004" => "Login is not active.",
			"ERRTM4TLS_00005" => "Password incorrect",
			"ERRTM4TLS_00006" => "Maximum number of logins exceeded - try again later.",

			"SYSERR_00001" => "Unexpected system error."
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

		$client = new soapclient($this->url, true);
		// Check for an error
		$error = $client->getError();
		if ($error)
			doError("SOAP Error", $error, true);

		/* Do we need to override the endpoint url returned by the wdsl? */
		if ($this->useEndpointUrl == true)
			$client->setEndPoint($this->endpointUrl);

		$result = $client->call('getUsage', array($userName, $password));

		// Check for a fault
		if ($client->fault)
			doError("SOAP Fault", $result, true);
		else
		{
			// Check for errors
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

					$returnValue["general"] = $general;
					$returnValue["daily"] = $daily;
				}
			}
		}

		if ($this->debug == true)
			print_r($returnValue);

		return ($returnValue);
	}

}

?>
