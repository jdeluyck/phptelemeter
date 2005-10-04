<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "html");
define("_phptelemeter_publisher_version", "1");
/*

phpTelemeter - a php script to read out and display the telemeter stats.

publisher_html.inc.php - file which contains the HTML publisher

Copyright (C) 2005 Jan De Luyck  <jan -at- kcore -dot- org>

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

class telemeterPublisher
{
	var $debug = false;

	function telemeterPublisher()
	{
	}

	/* exit function for us. */
	function destroy()
	{
		/* hmmm. nothing? */
	}

	/* EXTERNAL! */
	function publishData($data)
	{

	}
}

?>
