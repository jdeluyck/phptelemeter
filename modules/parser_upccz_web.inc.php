<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_parser_upccz_web", "5");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

parser_upccz_web.inc.php - file which contains the UPC CZ web page parser module.

Copyright (C) 2005 - 2007 Jan De Luyck  <jan -at- kcore -dot- org>

This parser is written by Miroslav Suchý <miroslav -at- suchy -dot- cz> and included
with permission.

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

class telemeterParser_upccz_web extends telemeterParser_web_shared
{
	var $_ISP = "upccz";

	function telemeterParser_telemeter_web()
	{
		/* call parent constructor */
		telemeterParser_web_shared::telemeterParser_web_shared();
	}

	/* EXTERNAL! */
	function getData($userName, $password)
	{
		$data = $this->doCurl("https://kraken.dkm.cz/", $this->createPostFields(array("login" => $userName, "pass" => $password)));
		if ($this->checkForError($data) !== false)
			return (false);

		$reg = array();
		ereg("ba:</td><td><b>(.*)</b></td></tr>
<tr><td align=\"right\">Placených pevných IP:<td><b>.*</b></td></tr>
</table>
<p>
<table class=\"pxtable\" cellpadding=\"1\" width=\"400\">
<thead><tr><td colspan=\"2\">Objem pøenesených dat za sledované období</tr></thead>
<tr><td align=\"right\" width=\"50%\">Odeslaná data:</td><td width=\"50%\"><b>(.*) GB</b></td></tr>
<tr><td align=\"right\">Pøijatá data:</td><td><b>(.*) GB</b></td></tr>"
			, $data, $reg);
		$service=$reg[1];
		$upload=$reg[2]*1024;
		$download=$reg[3]*1024;

		dumpDebugInfo($this->debug, $data);

		$used = $upload < $download ? $download : $upload;

		/* in GB */
		$limit["easy"] = 10;
		$limit["light"] = 20;
		$limit["classic"] = 30;
		$limit["plus"] = 50;
		$limit["extreme"] = 100;
		$limit["professional"] = 999999999; /*no limit*/

		/* 0-used 1-remaining*/
		$generalMatches["used"] = $used;
		$generalMatches["remaining"] = $limit[$service]*1024*1.08-$used;  /* accepted is aprox. 10% over limit - let say 8% */

		$returnValue["general"] = $generalMatches;
		$returnValue["isp"] = $this->_ISP;
		/* firts day of next month */
		$returnValue["reset_date"] = date("d/m/Y", mktime(0,0,0,date("m") + 1 ,1, date("Y")));
		$returnValue["days_left"] = calculateDaysLeft($returnValue["reset_date"]);


		dumpDebugInfo($this->debug, $returnValue);

		/* we need to unlink the cookiefile here, otherwise we get 'ghost' data. */
		@unlink ($this->_cookieFile);

		return ($returnValue);
	}
}

?>
