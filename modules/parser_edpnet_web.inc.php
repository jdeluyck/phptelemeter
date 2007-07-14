<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_edpnet_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_edpnet_web.inc.php - file which contains the EDPNet web page parser module.

Copyright (C) 2005 - 2007 Jan De Luyck  <jan -at- kcore -dot- org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

The full text of the license can be found here:
http://www.gnu.org/licenses/gpl.txt

*/

require_once("libs/phptelemeter_parser_web_shared.inc.php");

class telemeterParser_edpnet_web extends telemeterParser_web_shared
{
	var $_ISP = "edpnet";

	function telemeterParser_edpnet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array("btnCheck" => "Check traffic","__VIEWSTATE" => "");

		$this->url["login"] = "http://www.edpnet.be/traffic2.aspx?R=1";
		$this->url["details"] = "http://edpnet.be/traffic2_details.aspx";

		$this->errors = array("Invalid username or password" => "Invalid username or password");
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		/* open login page, extract value for __VIEWSTATE_ID
		<input type="hidden" name="__VIEWSTATE_ID" value="bba48a7f-be45-4694-bd6f-3be01f42f950" /> */

		$data = $this->doCurl($this->url["login"], FALSE);

		$data = explode("\n", $data);
		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "__VIEWSTATE_ID") !== false)
			{
				$this->_postFields["__VIEWSTATE_ID"] = substr($data[$i],50,-5);
				break;
			}
		}

		dumpDebugInfo($this->debug, "__VIEWSTATE_ID: " . $this->_postFields["__VIEWSTATE_ID"] . "\n");
		dumpDebugInfo($this->debug, "----------\n");

		/* log in & get initial data */
		$data = $this->doCurl($this->url["login"], $this->createPostFields(array("tbUserName" => $userName, "tbPassword" => $password)));
		if ($this->checkForError($data) !== false)
			return (false);

		/* now remove the first item from the _postFields array, and re-pass */
		array_shift($this->_postFields);

		dumpDebugInfo($this->debug, "----------\n");


		/* get historical data */
		$historicalData = $this->docurl($this->url["details"],$this->createPostFields());

		if ($this->checkForError($historicalData) !== false)
			return (false);

		/* stats */
		$data = $this->prepareData($data);

		/* find the entry position */
		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "Total") !== false)
				$pos["used"] = $i + 1;
			elseif(stristr($data[$i], "Allowed") !== false)
				$pos["max"] = $i + 1;
		}

		dumpDebugInfo($this->debug, "DEBUG: \$data\n");
		dumpDebugInfo($this->debug, $data);

		dumpDebugInfo($this->debug, "POS:\n");
		dumpDebugInfo($this->debug, $pos);

		/* total used */
		$volume["used"] = substr($data[$pos["used"]],0,-2);

		/* remaining */
		$volume["remaining"] = substr($data[$pos["max"]],0,-2) - $volume["used"];

		/* daily historical stats */
		/* cleanout */
		$historicalData = $this->prepareData(str_replace(array("</td>","</tr>"),"\n",$historicalData));
		array_shift($historicalData);
		array_shift($historicalData);
		array_shift($historicalData);

		dumpDebugInfo($this->debug, "DEBUG; \$historicalData\n");
		dumpDebugInfo($this->debug, $historicalData);

		/* loopke in vooruit van vanachter met 3 achteruit per loop :p */
		$temp = $historicalData[count($historicalData) - 3];
		$reset_date = date("d/m/y", mktime(0,0,0,substr($temp,3,2)+1,substr($temp,0,2),substr($temp,-4)));
		for ($i = count($historicalData) - 3; $i >= 0; $i--)
		{
			$dailyData[] = date("d/m/y", mktime(0,0,0,substr($historicalData[$i],3,2),substr($historicalData[$i],0,2),substr($historicalData[$i++],-4)));
			$dailyData[] = round(floatval(str_replace(",",".",substr($historicalData[$i++],0,-2)))) + round(floatval(str_replace(",",".",substr($historicalData[$i],0,-2))));

			/* correct counter */
			$i -= 4;
		}

		dumpDebugInfo($this->debug, "DEBUG; \$dailydata\n");
		dumpDebugInfo($this->debug, $dailyData);


		$returnValue["general"] = $volume;
		$returnValue["daily"] = $dailyData;
		$returnValue["isp"] = $this->_ISP;
		$returnValue["reset_date"] = $reset_date;
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);

		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
