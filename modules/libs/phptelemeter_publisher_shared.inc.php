<?php

if (! defined("_phptelemeter")) exit();
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

phptelemeter_publisher_shared.inc.php - file which contains the shared publisher infrastructure

Copyright (C) 2004 - 2012 Jan De Luyck  <jan -at- kcore -dot- org>

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

class telemeterPublisher_shared
{
	var $debug = false;
	var $ignoreErrors = false;
	var $neededModules = "";

	var $dataParts;

	var $neededConfigKeys = "";
	var $configKeys = "";
	
	function telemeterPublisher_shared()
	{
		$this->dataParts = array("general", "daily", "isp", "reset_date", "days_left");
	}

	function setDebug($debug)
	{
		$this->debug = $debug;
	}

	function setIgnoreErrors($ignoreErrors)
	{
		$this->ignoreErrors = $ignoreErrors;
	}

	function getNeededModules()
	{
		return ($this->neededModules);
	}
	
	function getNeededConfigKeys()
	{
		return($this->neededConfigKeys);
	}
	
	function setConfigKey($key, $value)
	{
		$this->configKey[$key] = $value;
	}

	/* exit function for us. */
	function destroy()
	{
	}

	function mainHeader()
	{
		return ("");
	}

	function mainFooter()
	{
		return ("");
	}

	/* EXTERNAL */
	function accountHeader($accountName)
	{
		return ("");
	}

	/* EXTERNAL */
	function accountFooter()
	{
		return("");
	}

	function newVersion($versionNr)
	{
		return ("");
	}

	function normalizeData($data)
	{
		/* create any parts necessary if they don't exist (because not supported by a parser) to avoid warnings */
		foreach ($this->dataParts as $key => $value)
		{
			if (! array_key_exists($value, $data))
			{
				dumpDebugInfo ($this->debug, "DEBUG: Adding ". $value . " to the data array.\n");

				$data[$value] = "";
			}
		}

		return ($data);
	}
}

?>
