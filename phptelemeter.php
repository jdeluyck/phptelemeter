#!/usr/bin/php -q
<?php

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

Copyright (C) 2005 - 2007 Jan De Luyck <jan -at- kcore -dot- org>

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

error_reporting(E_ALL);
define("_phptelemeter", 1);

require("phptelemeter.inc.php");


/* ----------------------- */
/* Main script starts here */
/* ----------------------- */

/* check php version */
checkPhpVersion();

/* Parse args and configuration file. */

/* we need this to enable help to work without a config file */
$configuration = parseArgs($argv, null);

/* create defines according to the OS */
checkOS($configuration, &$configFiles, &$cacheFiles);

/* find the config file */
$configFile = findConfigFile($configFiles, $configuration["general"]["debug"]);

$configuration = readConfig($configFile);
$configuration = parseArgs($argv, $configuration);

if ($configuration["general"]["debug"] == true)
	error_reporting(E_ALL);

dumpDebugInfo($configuration["general"]["debug"], $configuration);

/* check for include files and include them */

$configuration = checkConfigIncludes($configuration);

dumpDebugInfo($configuration["general"]["debug"], "Config after include parsing:\n");
dumpDebugInfo($configuration["general"]["debug"], $configuration);

$configuration = checkConfig($configuration, $configFile, $configKeys);

/* needed for debug log obfuscation */
$credentialInfo = getAllCredentials($configuration);

/* do a version check if it's asked */
$newVersion = checkVersion($configuration["general"]["check_version"], $configuration["proxy"], $configuration["general"]["encrypt_passwords"]);

/* set the include path */
set_include_path($configuration["general"]["modulepath"]);

/* load the compatibility matrix */
require_once("modules/libs/phptelemeter_compatibility_matrix.inc.php");

/* load the cache file */
if ($configuration["general"]["use_cache"] == true)
{
	if (isset($configuration["general"]["cache_file"]))
		$cacheFile = $configuration["general"]["cache_file"];
	else
		$cacheFile = findConfigFile($cacheFiles, $configuration["general"]["debug"]);

	dumpDebugInfo($configuration["general"]["debug"], "Cache file: " . $cacheFile . "\n");
	dumpDebugInfo($configuration["general"]["debug"], "Loading cache file, data:\n");
	$cache = loadCacheFile($configuration["general"]["debug"], $cacheFile);
	dumpDebugInfo($configuration["general"]["debug"], $cache);
}

/* load and configure the publisher */
loadPublisher($configuration);
$publisher = new telemeterPublisher();
checkModules($publisher->getNeededModules());

/* set the debugging flag if needed */
$publisher->setDebug($configuration["general"]["debug"]);

/* set the ignoreErrorsflag if needed */
$publisher->setIgnoreErrors($configuration["general"]["ignore_errors"]);

/* put the header on the screen */
if ($configuration["general"]["file_output"] == false)
{
	echo $publisher->mainHeader();

	/* if there's a new version, publish it */
	if ($newVersion !== false)
		echo $publisher->newVersion($newVersion);
}

/* loop through all our users */
foreach ($configuration["accounts"] as $key => $account)
{
	/* start buffering */
	ob_start();

	if ($configuration["general"]["file_output"] == true)
		echo $publisher->mainHeader();

	echo $publisher->accountHeader($account["description"]);

	/* load and configure the parser */
	loadParser($account["parser"], $configuration);
	$parserClassName = "telemeterParser_" . $account["parser"];
	$parser = new $parserClassName;
	checkModules($parser->getNeededModules());
	$parser->setDebug($configuration["general"]["debug"]);
	$parser->setIgnoreErrors($configuration["general"]["ignore_errors"]);

	/* pipe through the proxy info */
	$parser->setProxy($configuration["proxy"]["proxy_host"], $configuration["proxy"]["proxy_port"],
						$configuration["proxy"]["proxy_authenticate"], $configuration["proxy"]["proxy_username"],
						cryptPassword($configuration["proxy"]["proxy_password"],"decrypt", $configuration["general"]["encrypt_passwords"]));

	/* run the parser getData() routine */
	$data = $parser->getData($account["username"], cryptPassword($account["password"], "decrypt", $configuration["general"]["encrypt_passwords"]));

	if ($data === false)
		continue;

	/* send a mail? */
	if ($account["warn_percentage"] > 0)
	{
		if ($configuration["general"]["use_cache"] == true)
		{

			/* check if the account exists in the cache, if not, add */
			if (! array_key_exists($account["username"], $cache))
				$cache[$account["username"]]["mail_sent"] = false;

			$usage = calculateUsage($data["general"], $data["isp"]);

			$sendMail = "crap";
			$setSentFalse = false;

			if (array_key_exists("total", $usage))
			{
				if ($usage["total"]["percent"] > $account["warn_percentage"])
					$sendMail = true;
				else
					$sendMail = false;
			}
			else
			{
				if ($usage["download"]["percent"] > $percentage || $usage["upload"]["percent"] > $account["warn_percentage"])
					$sendMail = true;
				else
					$sendMail = false;
			}

			dumpDebugInfo($configuration["general"]["debug"], "Send mail? : " . var_dump($sendMail) . "\n");

			if ($sendMail == true && $cache[$account["username"]]["mail_sent"] == false)
			{
				sendWarnEmail($configuration["general"]["debug"], $usage, $account["description"], $account["warn_percentage"], $configuration["general"]["email"], $account["warn_email"]);
				$cache[$account["username"]]["mail_sent"] = true;
			}
			elseif ($sendMail == false && $cache[$account["username"]]["mail_sent"] == true)
				$cache[$account["username"]]["mail_sent"] = false;
		}
		else
			sendWarnEmail($configuration["general"]["debug"], $usage, $account["description"], $account["warn_percentage"], $configuration["general"]["email"], $account["warn_email"]);
	}

	/* publish the info */
	echo $publisher->accountFooter();

	echo $publisher->publishData($data,$configuration["general"]["show_remaining"], $configuration["general"]["show_daily"], $configuration["general"]["show_graph"], $configuration["general"]["show_resetdate"]);

	if ($configuration["general"]["file_output"] == true)
	{
		echo $publisher->mainFooter();

		$buffer = ob_get_contents();
		ob_end_clean();
		outputData($configuration, $buffer, $account["username"]);
	}
	else
		ob_end_flush();
}

if ($configuration["general"]["use_cache"] == true)
	saveCacheFile($configuration["general"]["debug"], $cacheFile, $cache);

if ($configuration["general"]["file_output"] == false)
	echo $publisher->mainFooter();


/* signing off. */
$parser->destroy();
$publisher->destroy();
quit();
?>
