<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

phptelemeter_compatibility_matrix.inc.php - file which contains the compatibility matrix

Copyright (C) 2005 - 2009 Jan De Luyck  <jan -at- kcore -dot- org>

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

/*
reset_date			can show the reset date
separate_quota		has separate quota's for upstream and downstream
separate_day_info	has separate per-day info for upstream and downstream (eg. scarlet has) 
history				has a displayable history for the period
*/

/* TELENET */
$isp_compatibility_matrix["telenet"]["reset_date"] = true;
$isp_compatibility_matrix["telenet"]["separate_quota"] = false;
$isp_compatibility_matrix["telenet"]["separate_day_info"] = false;
$isp_compatibility_matrix["telenet"]["history"] = true;

/* DOMMEL */
$isp_compatibility_matrix["dommel"]["reset_date"] = true;
$isp_compatibility_matrix["dommel"]["separate_quota"] = false;
$isp_compatibility_matrix["dommel"]["separate_day_info"] = false;
$isp_compatibility_matrix["dommel"]["history"] = false;

/* SKYNET */
$isp_compatibility_matrix["skynet"]["reset_date"] = true;
$isp_compatibility_matrix["skynet"]["separate_quota"] = false;
$isp_compatibility_matrix["skynet"]["separate_day_info"] = false;
$isp_compatibility_matrix["skynet"]["history"] = false;

/* SCARLET */
$isp_compatibility_matrix["scarlet"]["reset_date"] = true;
$isp_compatibility_matrix["scarlet"]["separate_quota"] = false;
$isp_compatibility_matrix["scarlet"]["separate_day_info"] = true;
$isp_compatibility_matrix["scarlet"]["history"] = true;

/* UPC CZ */
$isp_compatibility_matrix["upccz"]["reset_date"] = true;
$isp_compatibility_matrix["upccz"]["separate_quota"] = false;
$isp_compatibility_matrix["upccz"]["separate_day_info"] = false;
$isp_compatibility_matrix["upccz"]["history"] = false;

/* EDPNET */
$isp_compatibility_matrix["edpnet"]["reset_date"] = false;
$isp_compatibility_matrix["edpnet"]["separate_quota"] = false;
$isp_compatibility_matrix["edpnet"]["separate_day_info"] = false;
$isp_compatibility_matrix["edpnet"]["history"] = false;

/* SIMULATOR */
$isp_compatability_matrix["simulator_single"]["reset_date"] = true;
$isp_compatibility_matrix["simulator_single"]["separate_quota"] = false;
$isp_compatibility_matrix["simulator_single"]["separate_day_info"] = false;
$isp_compatibility_matrix["simulator_single"]["history"] = true;

$isp_compatability_matrix["simulator_separate"]["reset_date"] = true;
$isp_compatibility_matrix["simulator_separate"]["separate_quota"] = true;
$isp_compatibility_matrix["simulator_separate"]["separate_day_info"] = true;
$isp_compatibility_matrix["simulator_separate"]["history"] = true;

/* MOBILE VIKINGS */
$isp_compatibility_matrix["mobilevikings"]["reset_date"] = false;
$isp_compatibility_matrix["mobilevikings"]["separate_quota"] = false;
$isp_compatibility_matrix["mobilevikings"]["separate_day_info"] = false;
$isp_compatibility_matrix["mobilevikings"]["history"] = false;

?>
