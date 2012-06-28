<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "no_output");
define("_phptelemeter_publisher_version", "2");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_plaintext_graphonly.inc.php - file which contains the plaintext publisher, graph only version

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

require_once("libs/phptelemeter_publisher_shared.inc.php");

class telemeterPublisher extends telemeterPublisher_shared
{
	function telemeterPublisher()
	{
		/* call parent constructor */
		telemeterPublisher_shared::telemeterPublisher_shared();
	}

	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate, $warnPercentage)
	{
		return ("");
	}

	function newVersion($versionNr)
	{
		return("");
	}
}

?>
