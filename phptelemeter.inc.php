<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

Copyright (C) 2005 - 2006 Jan De Luyck  <jan -at- kcore -dot- org>

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

/* -------------------------------- */
/* General settings - do not touch! */
/* -------------------------------- */
define("_version", "1.24");
define("_maxAccounts", 9);
define("_configFileName", "phptelemeterrc");
define("_versionURL", "http://www.kcore.org/software/phptelemeter/VERSION");
define("_phptelemeterURL", "http://www.kcore.org/?menumain=3&menusub=3");

$configuration = array();

/* keys in the general section */
$configKeys["general"]["required"] = array("show_resetdate", "show_daily"  , "show_remaining", "show_graph", "file_prefix", "file_output", "file_extension", "publisher", "check_version");
$configKeys["general"]["obsolete"] = array("style", "daily", "parser");
$configKeys["proxy"]["required"]   = array("proxy_host", "proxy_port", "proxy_authenticate", "proxy_username", "proxy_password");

/* -------------------------------- */
/* Functions, functions, functions! */
/* -------------------------------- */
/* define some constants, based on the OS - initial try for win32 compatability */
function checkOS($configuration, &$configFiles)
{
	$os = strtoupper(substr(PHP_OS, 0, 3));

	switch ($os)
	{
		case "WIN":
		{
			$home = getenv("USERPROFILE");
			$temp = getenv("TEMP");
			$modulePath = "";
			$pathSeperator = ";";
			$systemDir = getenv("WINDIR");
			break;
		}

		default:	/* we assume unixes */
		{
			$home = getenv("HOME");
			$temp = "/tmp";
			$modulePath = "/usr/share/phptelemeter:/usr/local/share/phptelemeter";
			$pathSeperator = ":";
			$systemDir = "/etc";
		}
	}

	define("_os", $os);
	define("_tempdir", $temp);
	define("_defaultModulePath", "." . $pathSeperator . $modulePath . $pathSeperator . dirname(__FILE__) . $pathSeperator);
	define("_homedir", $home);

	$configFiles = array($systemDir . "/" . _configFileName, _homedir . "/." . _configFileName);

	if ($configuration["general"]["debug"] == true)
	{
		echo "OS     : " . _os . "\n";
		echo "HOME   : " . _homedir . "\n";
		echo "TEMP   : " . _tempdir . "\n";
		echo "MODPATH: " . _defaultModulePath . "\n";

		echo "CONFIG FILES:\n";
		var_dump($configFiles);
	}
}

/* we require version >= 4.3.0 */
function checkPhpVersion()
{
	if (version_compare("4.3.0", phpversion()) >= 0)
		doError("PHP version too low","Please upgrade PHP to atleast 4.3.0", true);
}

function findConfigFile($configFiles, $configuration)
{
	$found = false;

	foreach ($configFiles as $aConfigFile)
	{
		if (file_exists($aConfigFile))
		{
			$found = true;
			$returnValue = $aConfigFile;
			break;
		}
	}

	/* by default return the file in $HOME, which is on the last spot */
	if ($found == false)
		$returnValue = $aConfigFile;

	if ($configuration["general"]["debug"] == true)
		echo "CONFIG: $returnValue\n";

	return ($returnValue);
}

function checkModules($neededModules)
{
	if (! is_array($neededModules))
		return 0;

	foreach ($neededModules as $moduleName)
	{
		if (! extension_loaded($moduleName))
			doError("module " . $moduleName . " not loaded", "The " . $moduleName . " module for PHP was not found in memory. Please check the PHP documentation for installation instructions.", true);
	}
}

/* Throws an error at the user, and optionally bombs back to the cli */
function doError($errorMsg, $errorDescription, $exit)
{
	echo "\n";
	echo "phptelemeter: error: " . $errorMsg . "\n";
	echo $errorDescription . "\n";

	if ($exit == true)
		quit();
}

/* DUH. What do you think? Quits. */
function quit()
{
	exit (-1);
}

/* Parses the config file and does some checking on the contents */

function readConfig($configFile)
{
	if (! file_exists($configFile))
	{
		writeDummyConfig($configFile,true);

		$configuration = parse_ini_file($configFile, true);
		$configuration["new"] = "temporary trigger value";
	}
	else
		$configuration = parse_ini_file($configFile, true);

	return $configuration;
}

function writeDummyConfig($configFile, $writeNewConfig=false)
{
	$config = @fopen ($configFile, "w");
	if ($config)
	{
		fwrite ($config,
			"; This is a sample configuration file for phptelemeter\n" .
			"; Comments start with ';'\n" .
			";\n" .
			"; The options daily, show_remaining and file_output can be overridden\n" .
			"; on the command line. Use --help to see them all, or look in the README.\n" .
			";\n" .
			"; An explanation for all parameters can be found in the README file.\n" .
			";\n" .
			"; You can specify multiple accounts by making stanza's named\n" .
			"; [account-1] through [account-" . _maxAccounts . "]. Atleast one account is REQUIRED!\n" .
			";\n" .
			"[general]\n" .
			"show_daily=false\n" .
			"show_remaining=false\n" .
			"show_graph=true\n" .
			"show_resetdate=false\n" .
			";\n" .
			"file_prefix=/tmp/phptelemeter_\n" .
			"file_extension=txt\n" .
			"file_output=false\n" .
			";\n" .
			"check_version=false\n" .
			";\n" .
			"; This can be set to either plaintext, machine or html, and the file\n" .
			"; needs to be present in the phptelemeter/modules directory!\n" .
			"publisher=plaintext\n" .
			";\n" .
			"; You can set this path if phptelemeter has trouble finding\n" .
			"; it's modules. Point it to the directory that contains the\n" .
			"; modules directory.\n" .
			";modulepath=/usr/local/share/phptelemeter\n" .
			";\n" .
			"; Proxy configuration. Leave proxy_host blank to not use a proxy.\n" .
			"; If you set proxy_authenticate to true, you must fill the username\n" .
			"; and password too.\n" .
			"[proxy]\n" .
			"proxy_host=\n" .
			"proxy_port=8080\n" .
			"proxy_authenticate=false\n" .
			"proxy_username=\n" .
			"proxy_password=\n" .
			";\n" .
			"[account-1]\n" .
			"username=myuser\n" .
			"password=mypassword\n" .
			"parser=aparser\n" .
			"; (the parser can either be telemeter4tools, telemeter_web, dommel_web,\n" .
			"; skynet_web, scarlet_web or upccz_web, and the file needs to be present\n" .
			"; in the phptelemeter/modules directory!)\n" .
			";description=My first account\n" .
			"; (the description is optional)\n" .
			";\n" .
			";\n" .
			";[account-2]\n" .
			";username=myuser\n" .
			";password=mypassword\n" .
			";parser=aparser\n" .
			";description=My second account\n" .
			";\n" .
			"[die]\n"
		);
		fclose($config);

		if ($writeNewConfig == true)
			doError("no configuration file found.", "A dummy config file has been created in \n$configFile.\nPlease fill in the details and rerun phptelemeter.\n", false);
		else
			doError("new config file written.", "A new dummy configuration file has been written to $configFile.\n", false);
	}
	else
		doError("no write permissions", "No configuration file was found, and I was unable to create the dummy\nconfiguration file in $configFile.\nPlease check the permissions and rerun phptelemeter.\n", true);
}
function checkConfig($configuration, $configFile, $configKeys)
{
	/* ERROR CHECKING */

	/* check for the "new" section, if it's present the config has just been generated and we just bail out here. */
	if (array_key_exists("new", &$configuration))
		quit();

	/* protection against no-i-wont-edit-the-config users */
	checkConfigurationForKeys($configuration, array("die"), true, "configuration not correct.", "Edit $configFile and remove the \n%MSG%line!", true);

	/* verify general configuration */
	checkConfigurationForKeys($configuration           , array("general", "proxy")         , false, "configuration not correct.", "A configuration file was found, but it did not contain a valid\n%MSG%section.\nPlease correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["general"]["required"], false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["general"]["obsolete"], true, "obsolete key found in configuration", "The following obsolete keys were found in your configuration:\n%MSG%Please refer to the NEWS file for important changes to the \nconfiguration file.", false);
	checkConfigurationForKeys($configuration["proxy"]  , $configKeys["proxy"]["required"]  , false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please correct and rerun phptelemeter.", true);

	/* look for the modulepath */
	if (! array_key_exists("modulepath", $configuration["general"]))
		$configuration["general"]["modulepath"] = _defaultModulePath;
	else
		$configuration["general"]["modulepath"] = _defaultModulePath . $configuration["general"]["modulepath"];

	/* check for account-x stanzas. We need _ATLEAST_ 1 account. */
	for ($i = 1; $i <= _maxAccounts; $i++)
	{
		$accName = "account-" . $i;

		if (array_key_exists($accName, $configuration))
		{
			/* account found, check the validity */
			if (! array_key_exists("username", $configuration[$accName]) ||
				! array_key_exists("password", $configuration[$accName]) ||
				! array_key_exists("parser",   $configuration[$accName]))
					doError("configuration not correct.", "account info for " . $accName . " is not correct - ignoring.", false);
			else
			{
				$configuration["accounts"][]["username"] = $configuration[$accName]["username"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["password"] = $configuration[$accName]["password"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["parser"] = $configuration[$accName]["parser"];
				if (array_key_exists("description", $configuration[$accName]))
					$configuration["accounts"][count($configuration["accounts"]) - 1]["description"] =  $configuration[$accName]["description"];
				else
					$configuration["accounts"][count($configuration["accounts"]) - 1]["description"] =  $configuration[$accName]["username"];
			}
			unset($configuration[$accName]);
		}
	}

	if (count($configuration["accounts"]) == 0)
		doError("configuration not correct.", "A configuration file was found, but it did not contain any valid account sections.\nPlease correct and rerun phptelemeter.", true);

	/* if debug mode is active, disable file output mode */
	if ($configuration["general"]["debug"] == true)
		$configuration["general"]["file_output"] = false;

	return ($configuration);
}

/* checks if certain keys exist in the configuration section given,
   displays an error message and optionally quits
   NOTE: requires %MSG% in the $errorMsg string to insert the actual generated message*/
function checkConfigurationForKeys($configurationSection, $keys, $keyThere, $errorTitle, $errorMsg, $quit)
{
	$msg = "";
	for($i = 0; $i < count($keys); $i++)
	{
		if (array_key_exists($keys[$i], $configurationSection) === $keyThere)
			$msg .= "- " . $keys[$i]. "\n";
	}

	if (strlen($msg) > 0)
	{
		$errorMsg = str_replace("%MSG%", $msg, $errorMsg);
		doError($errorTitle, $errorMsg, $quit);
	}
}

/* Debugging: does a var_dump of the configfile */
function dumpConfig($configuration)
{
	var_dump($configuration);
}

/* Parses the command-line arguments and fits them in the configuration array */
function parseArgs($argv, $configuration)
{
	array_shift($argv);

	foreach ($argv as $flag)
	{
		switch ($flag)
		{
			case "--daily":
			case "-d":
			{
				$configuration["general"]["show_daily"] = true;
				break;
			}

			case "--debug":
			case "-D":
			{
				$configuration["general"]["debug"] = true;
				break;
			}

			case "--remaining":
			case "-r":
			{
				$configuration["general"]["show_remaining"] = true;
				break;
			}

			case "--graph":
			case "-g":
			{
				$configuration["general"]["show_graph"] = true;
				break;
			}

			case "--file-output":
			case "-f":
			{
				$configuration["general"]["file_output"] = true;
				break;
			}

			case "--new-config":
			case "-n":
			{
				writeDummyConfig($getcwd . _configFileName);
				quit();
				break;
			}

			case "--version":
			case "-V":
			{
				echo "phptelemeter - v" . _version . "\n";
				quit();
				break;
			}

			case "--resetdate":
			case "-z":
			{
				$configuration["general"]["show_resetdate"] = true;
				break;
			}

			case "--check-version":
			case "-c":
			{
				$configuration["general"]["check_version"] = true;
				break;
			}

			case "--help":
			case "-h":
			default:
			{
				echo "phptelemeter - v" . _version . "\n";
				echo "phptelemeter [options] \n";
				echo "-c\t--check-version\tChecks if your phptelemeter is the latest version\n";
				echo "-d,\t--daily\t\tShows statistics for last 30 days\n";
				echo "-D,\t--debug\t\tShows some debugging info\n";
				echo "-f,\t--file-output\tActivates file output instead of screen output.\n";
				echo "-g,\t--graph\t\tShows the usage graphs.\n";
				echo "-h,\t--help\t\tShows this help message.\n";
				echo "-n,\t--new-config\tMakes a new dummy config file in the current directory.\n";
				echo "-r,\t--remaining\tShows your max traffic allotment for today.\n";
				echo "-V,\t--version\tShows the version and exits.\n";
				echo "-z,\t--resetdate\tShows the quota reset date.\n";
				echo "\n";
				echo "Options specified here override the configuration file.\n\n";
				quit();
			}
		}
		if ($configuration["general"]["debug"] == true) echo "ARG: $flag\n";
	}

	return $configuration;
}



function outputData($configuration, $buffer, $userid)
{
	$fileName = $configuration["general"]["file_prefix"] . $userid . "." . $configuration["general"]["file_extension"];

	$fp = @fopen($fileName, "w");

	if ($fp !== FALSE)
	{
		fwrite($fp, $buffer);
		fclose ($fp);
	}
	else
		doError("error writing " . $fileName, "The output could not be written to the file.\nPlease check if you have write permissions!", true);
}

function loadParser($aParser, $configuration)
{
	$parser = "modules/parser_" . $aParser . ".inc.php";
	$parserID = "_phptelemeter_parser_" . $aParser;

	if ($configuration["general"]["debug"] == true)
		echo "PARSER: Trying to load " . $parser . "\n";

	require_once($parser);

	if (! defined($parserID))
		doError("Invalid parser", "The parser " . $aParser . " is not valid!", true);

	if ($configuration["general"]["debug"] == true)
		echo "PARSER: Loaded parser " . $aParser . ", version " . constant($parserID) . "\n";

}

function loadPublisher($configuration)
{
	$publisher = "modules/publisher_" . $configuration["general"]["publisher"] . ".inc.php";

	if ($configuration["general"]["debug"] == true)
		echo "PUBLISHER: Trying to load " . $publisher . "\n";

	require_once($publisher);

	if (! defined("_phptelemeter_publisher"))
		doError("Invalid publisher", "The publisher " . $configuration["general"]["publisher"] . " is not valid!", true);

	if ($configuration["general"]["debug"] == true)
		echo "PUBLISHER: Loaded publisher " . _phptelemeter_publisher . ", version " . _phptelemeter_publisher_version . "\n";

}

function checkISPCompatibility($isp, $function)
{
	global $isp_compatibility_matrix;

	if (array_key_exists($function, $isp_compatibility_matrix[$isp]) && $isp_compatibility_matrix[$isp][$function] == true)
		$returnValue = true;
	else
		$returnValue = false;

	return ($returnValue);
}


function calculateUsage($data, $isp)
{
	if (checkISPCompatibility($isp, "seperate_quota") == true)
	{
		$returnValue["download"]["max"]     = $data[0];
		$returnValue["download"]["use"]     = $data[2];
		$returnValue["download"]["left"]    = $returnValue["download"]["max"] - $returnValue["download"]["use"];
		$returnValue["download"]["percent"] = (100 / $returnValue["download"]["max"]) * $returnValue["download"]["use"];
		$returnValue["download"]["hashes"]  = $returnValue["download"]["percent"] / 5;

		$returnValue["upload"]["max"]     = $data[1];
		$returnValue["upload"]["use"]     = $data[3];
		$returnValue["upload"]["left"]    = $returnValue["upload"]["max"] - $returnValue["upload"]["use"];
		$returnValue["upload"]["percent"] = (100 / $returnValue["upload"]["max"]) * $returnValue["upload"]["use"];
		$returnValue["upload"]["hashes"]  = $returnValue["upload"]["percent"] / 5;
	}
	else
	{
		/*	0 = total used
			1 = remaining
		*/
		$returnValue["total"]["use"] = $data[0];
		$returnValue["total"]["left"] = $data[1];
		$returnValue["total"]["max"] = $returnValue["total"]["use"] + $returnValue["total"]["left"];
		$returnValue["total"]["percent"] = (100 / $returnValue["total"]["max"]) * $returnValue["total"]["use"];
		$returnValue["total"]["hashes"] = $returnValue["total"]["percent"] / 5;
	}

	return ($returnValue);
}

function removeDots($someData)
{
	return (str_replace(".", "", $someData));
}

function checkVersion($doCheck, $proxyInfo)
{
	checkModules(array("curl"));

	$returnValue = false;

	if ($doCheck == true)
	{
		$ch = curl_init(_versionURL);

		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_ENCODING , "gzip");
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);

		/* check proxy */
		if (strlen($proxyInfo["proxy_host"]) != 0)
		{
			curl_setopt($ch, CURLOPT_PROXY, $proxyInfo["proxy_host"] . ":" . $proxyInfo["proxy_port"]);

			if ($proxyInfo["proxy_authenticate"] == true)
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyInfo["proxy_username"] . ":" . $proxyInfo["proxy_password"]);
		}

		$upstreamVersion = trim(curl_exec($ch));
		if (curl_errno($ch) != 0)
			doError("curl error occurred", curl_error($ch), true);

		curl_close($ch);

		/* if we didn't get a version for whatever reason, say it's the same as ours */
		if ($upstreamVersion === false)
			$upstreamVersion = _version;

		if (version_compare($upstreamVersion, _version) > 0)
			$returnValue = $upstreamVersion;
	}

	return ($returnValue);
}
?>
