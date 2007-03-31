<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

phptelemeter_compatibility_matrix.inc.php - file which contains the compatibility matrix

Copyright (C) 2005 - 2007 Jan De Luyck  <jan -at- kcore -dot- org>

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

/*
reset_date			can show the reset date
seperate_quota		has seperate quota's for upstream and downstream
history				has a displayable history for the period
*/

/* TELENET */
$isp_compatibility_matrix["telenet"]["reset_date"] = true;
$isp_compatibility_matrix["telenet"]["seperate_quota"] = false;
$isp_compatibility_matrix["telenet"]["history"] = true;

/* DOMMEL */
$isp_compatibility_matrix["dommel"]["reset_date"] = true;
$isp_compatibility_matrix["dommel"]["seperate_quota"] = false;
$isp_compatibility_matrix["dommel"]["history"] = false;

/* SKYNET */
$isp_compatibility_matrix["skynet"]["reset_date"] = true;
$isp_compatibility_matrix["skynet"]["seperate_quota"] = false;
$isp_compatibility_matrix["skynet"]["history"] = false;

/* SCARLET */
$isp_compatibility_matrix["scarlet"]["reset_date"] = true;
$isp_compatibility_matrix["scarlet"]["seperate_quota"] = false;
$isp_compatibility_matrix["scarlet"]["history"] = true;

/* UPC CZ */
$isp_compatibility_matrix["upccz"]["reset_date"] = true;
$isp_compatibility_matrix["upccz"]["seperate_quota"] = false;
$isp_compatibility_matrix["upccz"]["history"] = false;

?>
