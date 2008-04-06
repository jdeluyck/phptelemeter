<?php

if (! defined("_phptelemeter")) exit();

define("_phptelemeter_publisher", "mysql");
define("_phptelemeter_publisher_version", "1");
/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

publisher_mysql.inc.php - file which contains the mysql publisher

Copyright (C) 2005 - 2008 Jan De Luyck  <jan -at- kcore -dot- org>

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

require_once("libs/phptelemeter_publisher_shared.inc.php");

class telemeterPublisher extends telemeterPublisher_shared
{
	var $accountName;
	
	function telemeterPublisher()
	{
		/* call parent constructor */
		telemeterPublisher_shared::telemeterPublisher_shared();
		
		$this->neededConfigKeys = array("db_name","db_hostname", "db_login", "db_password", "db_tablename");
		$this->neededModules = array("mysql");
	}

	function mainHeader()
	{
		return ("");
	}

	function accountHeader($accountName)
	{
		$this->accountName = $accountName;
		return("");
	}

	function accountFooter()
	{
		return("");
	}

	/* EXTERNAL! */
	function publishData($data, $showRemaining, $showDaily, $showGraph, $showResetDate, $warnPercentage)
	{
		$data = $this->normalizeData($data);

		$generalData = $data["general"];
		$dailyData   = $data["daily"];
		$isp         = $data["isp"];
		$resetDate   = $data["reset_date"];
		$daysLeft    = $data["days_left"];

		/* general data, always shown */
		$usage = calculateUsage($generalData, $isp);

		$dbName = $this->configKey["db_name"];
		$dbHostName = $this->configKey["db_hostname"];
		$dbTableName = $this->configKey["db_tablename"];
		$dbLogin = $this->configKey["db_login"];
		$dbPassword = $this->configKey["db_password"];
		
		/* insert values into db here */
		/* fields
			id
			datetime
			accountName
			isp
			uploadUsed
			uploadMax
			downloadUsed
			downloadMax
			TotalUsed
			TotalMax
			daysLeft
			
			CREATE TABLE `phptelemeter`.`phptelemeter` (
			`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
			`date_time` DATETIME NOT NULL ,
			`account_name` TEXT NOT NULL ,
			`isp` TEXT NOT NULL ,
			`upload_used` FLOAT UNSIGNED NOT NULL ,
			`upload_max` FLOAT UNSIGNED NOT NULL ,
			`download_used` FLOAT UNSIGNED NOT NULL ,
			`download_max` FLOAT UNSIGNED NOT NULL ,
			`total_used` FLOAT UNSIGNED NOT NULL ,
			`total_max` FLOAT UNSIGNED NOT NULL ,
			`days_left` TINYINT UNSIGNED NOT NULL ,
			PRIMARY KEY ( `id` ) 
			) ENGINE = MYISAM
			
			
			
		*/
			
	}

	function newVersion($versionNr)
	{
		return ("");
	}
}
?>
