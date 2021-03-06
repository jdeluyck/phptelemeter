<?php
if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_edpnet_web", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_edpnet_web.inc.php - file which contains the EDPNet web page parser module.

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

/* This parser is disabled for now. */
echo "Sorry, the edpnet_web parser is disabled for now.\n";
exit();

require_once("libs/phptelemeter_parser_web_shared.inc.php");

class telemeterParser_edpnet_web extends telemeterParser_web_shared
{
	var $_ISP = "edpnet";

	function telemeterParser_edpnet_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();

		/* do some var initialisation */
		$this->_postFields = array('ctl00$MainContent$btnLogin' => "Login �");// "__VIEWSTATE" => "");
		$this->viewstate = "";

		$this->url["login"]   = "http://extra.edpnet.net/src/Login.aspx";
		$this->url["dslinfo"] = "http://extra.edpnet.net/src/list_dslconnections.aspx";
		$this->url["dsldetails"] = "http://extra.edpnet.be/maint_dslconnection.aspx?ID=";
		$this->url["traffic"] = "http://extra.edpnet.net/TrafficDetail3.aspx";

		$this->errors = array("Invalid username or password" => "Invalid username or password");
	}

	/* EXTERNAL! */
	function getData($userName, $password, $subaccount)
	{
		/* open login page, extract value for __VIEWSTATE_ID
		<input type="hidden" name="__VIEWSTATE_ID" value="bba48a7f-be45-4694-bd6f-3be01f42f950" /> */

		$data = $this->doCurl($this->url["login"], FALSE);

		$data = explode("\n", $data);

		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "__VIEWSTATE") !== false)
			{
				$temp = strpos($data[$i], "value=");
				$this->viewstate = substr($data[$i], $temp + 7, -4);
				break;
			}
		}

		dumpDebugInfo($this->debug, "__VIEWSTATE: " . $this->viewstate . "\n");


		/* log in & get initial data */
		$data = $this->doCurl($this->url["login"], $this->createPostFields(array('ctl00$MainContent$tbUserID' => $userName, 'ctl00$MainContent$tbPassword' => $password)));
		if ($this->checkForError($data) !== false)
			return (false);


		/* now drop the Login button identifier from the array */
		array_shift($this->_postFields);

		/* now get the dsl connection link */
		$data = $this->doCurl($this->url["dslinfo"], $this->createPostFields(array('__VIEWSTATE' => $this->viewstate)));
		$data = explode("\n", $data);

		dumpDebugInfo($this->debug, "DSL CONNECTION INFO:\n");
		dumpDebugInfo($this->debug, $data);

		for ($i = 0; $i < count($data); $i++)
		{
			if (stristr($data[$i], "maint_dslconnection.aspx") !== false)
			{
				$this->url["dsldetails"] .= substr(strrchr($data[$i],"="),1,-3);
				break;
			}
		}
		
		/* now get the dsl connection info */
		$data = $this->doCurl($this->url["dsldetails"], FALSE);
		dumpDebugInfo($this->debug, $data);

		/* try to get the start date for this period out */
		$data = explode("\n", $data);

		dumpDebugInfo($this->debug, "DATA:\n");
		dumpDebugInfo($this->debug, $data);

		/* find where the line might be */
		for ($i = 0; $i < count($data); $i++)
		{
			if ($data2 = stristr($data[$i], "LblTitleUseTable"))
				break;
		}

		$data2 = strip_tags($data2);
		$data2 = explode(" ", $data2);
		
		$datePos = -1;
		/* clean that out a bitty */
		for ($i = 0; $i < count($data2); $i++)
		{
			$data2[$i] = trim($data2[$i]);
			if (stristr($data2[$i], "depuis") !== false)
				$datePos = $i;
		}
		
		dumpDebugInfo($this->debug, "DATA2:\n");
		dumpDebugInfo($this->debug, $data2);

		if ($datePos < 0)
		{
			dumpDebugInfo($this->debug, "Date string not found!\n");
			return (false);
		}
		
		
		$resetDate = date("d/m/Y", mktime(0,0,0,substr($data2[8],-7,2), substr($data2[8],-10,2),substr($data2[8],-4)));
		
		dumpDebugInfo($this->debug, "STARTDATE: " . $resetDate . "\n");
		
		/* let's have a look at traffic */
		$data = $this->doCurl($this->url["traffic"], FALSE);
		dumpDebugInfo($this->debug, $data);
		
		$data = explode("\n", $data);

		dumpDebugInfo($this->debug, $data);

		/* total used */
		$volume["used"] = substr($data2[25],14);

		/* remaining */
		$volume["remaining"] = substr($data[$pos["max"]],0,-2) - $volume["used"];

		/* daily historical stats */
		/* cleanout */
/*		 $historicalData = $this->prepareData(str_replace(array("</td>","</tr>"),"\n",$historicalData));
		array_shift($historicalData);
		array_shift($historicalData);
		array_shift($historicalData);

		dumpDebugInfo($this->debug, "DEBUG; \$historicalData\n");
		dumpDebugInfo($this->debug, $historicalData);
*/
		/* loopke in vooruit van vanachter met 3 achteruit per loop :p */
/*		$temp = $historicalData[count($historicalData) - 3];
		$reset_date = date("d/m/y", mktime(0,0,0,substr($temp,3,2)+1,substr($temp,0,2),substr($temp,-4)));
		for ($i = count($historicalData) - 3; $i >= 0; $i--)
		{
			$dailyData[] = date("d/m/y", mktime(0,0,0,substr($historicalData[$i],3,2),substr($historicalData[$i],0,2),substr($historicalData[$i++],-4)));
			$dailyData[] = round(floatval(str_replace(",",".",substr($historicalData[$i++],0,-2)))) + round(floatval(str_replace(",",".",substr($historicalData[$i],0,-2))));
*/
			/* correct counter */
/*			$i -= 4;
		}

		dumpDebugInfo($this->debug, "DEBUG; \$dailydata\n");
		dumpDebugInfo($this->debug, $dailyData);
*/

		$returnValue["general"] = $volume;
//		$returnValue["daily"] = $dailyData;
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
