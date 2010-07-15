<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_telemeter4tools", "16");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_telemeter4tools.inc.php - file which contains the Telemeter4tools parser module.

Copyright (C) 2004 - 2010 Jan De Luyck  <jan -at- kcore -dot- org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The full text of the license can be found here:
http://www.gnu.org/licenses/gpl2.txt

*/

class telemeterParser_telemeter4tools
{
	var $url = "https://t4t.services.telenet.be/TelemeterService.wsdl";

	var $_ISP = "telenet";

	var $useEndpointUrl = false;
	var $endpointUrl = "";

	var $errors_critical;
	var $errors_normal;

	var $debug = false;
	var $ignoreErrors = false;
	var $neededModules = array("soap");

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
			"ERRTLMTLS_00002" => "Invalid input. Login or password is empty.",
			"ERRLNGMGT_00017" => "Error occurred while fetching customer OID."
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
			doError("problem detected", trim($returnValue), $quit, $this->ignoreErrors);

		return($returnValue);
	}

	function checkStatus($text)
	{
		$returnValue = $this->checkForError($text, $this->errors_critical, true);

		if ($returnValue === false)
			$returnValue = $this->checkForError($text, $this->errors_normal, false);

		return ($returnValue);
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		$returnValue = false;
		$params["trace"] = 0;
		$client = "";

		if (strlen($this->proxyHost) != 0)
		{
			$params["proxy_host"] = $this->proxyHost;
			$params["proxy_port"] = $this->proxyPort;

			if ($this->proxyAuth == true)
			{
				$params["proxy_login"] = $this->proxyUsername;
				$params["proxy_password"] = $this->proxyPassword;
			}
		}

		try 
		{
			$client = new soapclient($this->url, $params);
		} 
		catch (Exception $e)
		{
			doError("SOAP Error during object creation", $e->getMessage(), true, false);
		}

		/* Do we need to override the endpoint url returned by the wdsl? */
		if ($this->useEndpointUrl == true)
			$client->__setLocation($this->endpointUrl);


		/* Call the retrieveUsage function */
		try
		{
			$result = $client->retrieveUsage(new SoapParam(array("UserId" => $userName, "Password" => $password), "RetrieveUsageRequestType"));
		}
		catch (Exception $e)
		{
			$returnValue = $this->checkStatus($e->getMessage());
		}

		/* now look for error messages */
		if ($returnValue === false)
		{

			dumpDebugInfo($this->debug, $result);

			/* split off the global usage data */
			$general["used"] = $result->Volume->TotalUsage;
			$general["remaining"] = $result->Volume->Limit - $general["used"];

			/* split off the daily data */
			foreach ($result->Volume->DailyUsageList->DailyUsage as $key => $value)
			{
				$daily[] = date("d/m/y", strtotime($value->Day));
				$daily[] = $value->Usage;
			}

			$endDate = $daily[count($daily) - 2];
			$resetDate = date("d/m/Y", mktime(0,0,0,substr($endDate,3,2),substr($endDate,0,2) + 1,substr($endDate,6)));

			$returnValue["general"] = $general;
			$returnValue["daily"] = $daily;
			$returnValue["isp"] = $this->_ISP;
			$returnValue["reset_date"] = $resetDate;
			$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);
		}
		else
			$returnValue = false;

		dumpDebugInfo($this->debug, $returnValue);

		return ($returnValue);
	}
}

?>
