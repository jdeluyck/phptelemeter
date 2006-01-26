<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display the telemeter stats.

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

/* we require version 4.3.0 for some functions */
if (version_compare("4.3.0", phpversion(), "<=") == 0)
	doError("PHP version too low","Please upgrade PHP to atleast 4.3.0", true);

/* -------------------------------- */
/* General settings - do not touch! */
/* -------------------------------- */
define("_version", "0.26");
define("_maxAccounts", 9);
define("_defaultModulePath", ".:/usr/share/phptelemeter:/usr/local/share/phptelemeter:" . dirname(__FILE__));
define("_configFileName", "phptelemeterrc");

$HOME = getenv("HOME");

$configFiles = array("/etc/" . _configFileName, $HOME . "/." . _configFileName);
$configuration = array();

/* keys in the general section */
$configKeys["required"]     = array("daily"  , "show_remaining", "show_graph", "file_prefix", "file_output", "parser", "publisher");
$configKeys["obsolete"]     = array("style");

/* -------------------------------- */
/* Functions, functions, functions! */
/* -------------------------------- */
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

	// by default return the file in $HOME, which is on the last spot
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

//function parseConfig()
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
			"; on the command line\n" .
			"; useing --daily to output the daily statistics,\n" .
			"; use --remaining to show the remaining quota,\n" .
			"; use --graph to show the usage graph,\n" .
			"; and use --file-output to activate file output.\n" .
			";\n" .
			"; An explanation for all parameters can be found in the README file.\n" .
			";\n" .
			"; You can specify multiple accounts by making stanza's named\n" .
			"; [account-1] through [account-" . _maxAccounts . "]. Atleast one account is REQUIRED!.\n" .
			";\n" .
			"[general]\n" .
			"daily=false\n" .
			"show_remaining=false\n" .
			"show_graph=true\n" .
			";\n" .
			"file_prefix=/tmp/phptelemeter_\n" .
			"file_output=false\n" .
			";\n" .
			"; This can either be telemeter4tools or telemeter_web, and the file\n" .
			"; needs to be present in the phptelemeter/modules directory!\n" .
			"parser=telemeter4tools\n" .
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
			"[account-1]\n" .
			"username=myuser\n" .
			"password=mypassword\n" .
			";description=My first account\n" .
			"; (the description is optional)\n" .
			";\n" .
			";[account-2]\n" .
			";username=myuser\n" .
			";password=mypassword\n" .
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
	if (array_key_exists("new", $configuration))
		quit();

	/* protection against no-i-wont-edit-the-config users */
	checkConfigurationForKeys($configuration, array("die"), true, "configuration not correct.", "Edit $configFile and remove the \n%MSG%line!", true);

	/* verify general configuration */
	checkConfigurationForKeys($configuration           , array("general")       , false, "configuration not correct.", "A configuration file was found, but it did not contain a valid\n%MSG%section.\nPlease correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["required"], false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["obsolete"], true, "obsolete key found in configuration", "The following obsolete keys were found in your configuration:\n%MSG%Please refer to the NEWS file for important changes to the \nconfiguration file.", false);

	/* look for the modulepath */
	if (! array_key_exists("modulepath", $configuration["general"]))
		$configuration["general"]["modulepath"] = _defaultModulePath;
	else
		$configuration["general"]["modulepath"] = $configuration["general"]["modulepath"] . ":" . _defaultModulePath;

	/* check for account-x stanzas. We need _ATLEAST_ 1 account. */
	for ($i = 1; $i <= _maxAccounts; $i++)
	{
		$accName = "account-" . $i;

		if (array_key_exists($accName, $configuration))
		{
			/* account found, check the validity */
			if (! array_key_exists("username", $configuration[$accName]) ||
				! array_key_exists("password", $configuration[$accName]))
					doError("configuration not correct.", "account info for " . $accName . " is not correct - ignoring.", false);
			else
			{
				$configuration["accounts"][]["username"] = $configuration[$accName]["username"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["password"] = $configuration[$accName]["password"];
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
				$configuration["general"]["daily"] = true;
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
			}

			case "--version":
			case "-V":
			{
				echo "phptelemeter - v" . _version . "\n";
				quit();
			}

			default:
			{
				echo "phptelemeter - v" . _version . "\n";
				echo "phptelemeter [options] \n";
				echo "-d,\t--daily\t\tShows statistics for last 30 days\n";
				echo "-D,\t--debug\t\tShows some debugging info\n";
				echo "-r,\t--remaining\tShows your max traffic allotment for today.\n";
				echo "-g,\t--graph\t\tShows the usage graphs.\n";
				echo "-f,\t--file-output\tActivates file output instead of screen output.\n";
				echo "-n,\t--new-config\tMakes a new dummy config file in the current directory.\n";
				echo "-V,\t--version\tShows the version and exits.\n";
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
	$fileName = $configuration["general"]["file_prefix"] . $userid;

	$fp = @fopen($fileName, "w");

	if ($fp !== FALSE)
	{
		fwrite($fp, $buffer);
		fclose ($fp);
	}
	else
		doError("error writing " . $fileName, "The output could not be written to the file.\nPlease check if you have write permissions!", true);
}

function loadParser($configuration)
{
	$parser = "modules/parser_" . $configuration["general"]["parser"] . ".inc.php";

	if ($configuration["general"]["debug"] == true)
		echo "PARSER: Trying to load " . $parser . "\n";

	require_once($parser);

	if (! defined("_phptelemeter_parser"))
		doError("Invalid parser", "The parser " . $configuration["general"]["parser"] . " is not valid!", true);

	if ($configuration["general"]["debug"] == true)
		echo "PARSER: Loaded parser " . _phptelemeter_parser . ", version " . _phptelemeter_parser_version . "\n";

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

function calculateUsage($data)
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

	return ($returnValue);
}

function removeDots($someData)
{
	return (str_replace(".", "", $someData));
}
?>
