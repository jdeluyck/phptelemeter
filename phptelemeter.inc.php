<?php

if (! defined("_phptelemeter")) exit();

/*

phpTelemeter - a php script to read out and display ISP's usage-meter stats.

Copyright (C) 2004 - 2011 Jan De Luyck  <jan -at- kcore -dot- org>

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

/* -------------------------------- */
/* General settings - do not touch! */
/* -------------------------------- */
define("_version", "1.36-beta3");
define("_maxAccounts", 99);
define("_configFileName", "phptelemeterrc");
define("_cacheFileName", "phptelemeter.cache");
define("_versionURL", "http://www.kcore.org/software/phptelemeter/VERSION");
define("_phptelemeterURL", "http://phptelemeter.kcore.org/");
define("_key", "b?S3jLT+AB+SwQ,l2@0DrX}b!mL6}OeoDLHjiFKEGNxM}K*/dPbd4}.|");

$configuration = array();

/* keys in the general section */
$configKeys["general"]["required"] = array("show_resetdate", "show_daily", "show_remaining", "show_graph", "file_prefix", "file_output", "file_extension", "check_version", "ignore_errors", "email", "encrypt_passwords", "use_cache","timezone");
$configKeys["general"]["obsolete"] = array("style", "daily", "parser", "publisher");
$configKeys["proxy"]["required"]   = array("proxy_host", "proxy_port", "proxy_authenticate", "proxy_username", "proxy_password");
$configKeys["publisher"]["required"] = array("publisher");

/* -------------------------------- */
/* Functions, functions, functions! */
/* -------------------------------- */
/* define some constants, based on the OS */
function checkOS($configuration, &$configFiles, &$cacheFiles)
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
	$cacheFiles = array($systemDir . "/" . _cacheFileName, _homedir . "/." . _cacheFileName);

	dumpDebugInfo($configuration["general"]["debug"],
		"OS     : " . _os . "\n" .
		"HOME   : " . _homedir . "\n" .
		"TEMP   : " . _tempdir . "\n" .
		"MODPATH: " . _defaultModulePath . "\n");

	dumpDebugInfo($configuration["general"]["debug"], "CONFIG FILES:\n");
	dumpDebugInfo($configuration["general"]["debug"], $configFiles);

	dumpDebugInfo($configuration["general"]["debug"], "CACHE FILES:\n");
	dumpDebugInfo($configuration["general"]["debug"], $cacheFiles);
}

/* we require version >= 5.0.0 */
function checkPhpVersion()
{
	if (version_compare("5.0.0", phpversion()) >= 0)
		doError("PHP version too low","Please upgrade PHP to atleast 5.0.0", true);
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

	dumpDebugInfo($configuration, "CONFIG: $returnValue\n");

	return ($returnValue);
}

function checkModules($neededModules)
{
	if (! is_array($neededModules))
		return (0);

	foreach ($neededModules as $moduleName)
	{
		if (! extension_loaded($moduleName))
			doError("module " . $moduleName . " not loaded", "The " . $moduleName . " module for PHP was not found in memory. Please check the PHP documentation for installation instructions.", true);
	}
}

/* Throws an error at the user, and optionally bombs back to the cli */
function doError($errorMsg, $errorDescription, $exit, $ignoreErrors=false)
{
	echo "\n";
	echo "phptelemeter: error: " . $errorMsg . "\n";
	echo $errorDescription . "\n";

	if ($exit == true)
	{
		if ($ignoreErrors == true)
			echo "\n";
		else
			quit();
	}
}

/* DUH. What do you think? Quits. */
function quit()
{
	exit (0);
}

/* Parses the config file and does some checking on the contents */

function readConfig($configFile, $tempConfiguration)
{
	if (! isset($tempConfiguration["general"]["no-config"]))
	{
		if (! file_exists($configFile))
		{
			writeDummyConfig($configFile,true);

			$configuration = parse_ini_file($configFile, true);
			$configuration["new"] = "temporary trigger value";
		}
		else
			$configuration = parse_ini_file($configFile, true);

		/* if debugging parameter isn't set, set it. we want debugging ASAP activated. */
		if (! array_key_exists("debug", $configuration["general"]))
			$configuration["general"]["debug"] = false;
	}
	else
		$configuration = "";

	return ($configuration);
}

function checkConfigIncludes($configuration)
{
	$newConfig = array();

	foreach ($configuration as $key => $value)
	{
		if (strpos($key, "include ") === false)
			$newConfig[$key] = $value;
		else
		{
			$includeFile = substr($key, 8);

			if (! file_exists($includeFile))
				doError ("problem detected", "include file " . $includeFile . " does not exist!", true, $configuration["general"]["ignore_errors"]);

			$temp = parse_ini_file($includeFile, true);

			dumpDebugInfo($configuration["general"]["debug"], $temp);
			$newConfig = array_merge($newConfig, $temp);
		}
	}

	return ($newConfig);
}

function writeDummyConfig($configFile, $writeNewConfig=false)
{
	$config = @fopen ($configFile, "w");
	if ($config)
	{
		$configData = "; This is a sample configuration file for phptelemeter
; Comments start with ';'

; The options show_daily, show_remaining and file_output can be overridden
; on the command line. Use --help to see them all, or look in the README.

; An explanation for all parameters can be found in the README file.

; You can specify multiple accounts by making stanza's named
; [account-1] through [account-" . _maxAccounts . "]. Atleast one account is REQUIRED!

[general]
show_daily=false
show_remaining=false
show_graph=true
show_resetdate=false

file_prefix=\"/tmp/phptelemeter_\"
file_extension=\"txt\"
file_output=false

check_version=false

; You can set this path if phptelemeter has trouble finding its modules.
; Point it to the directory that contains the modules directory.
;modulepath=\"/usr/local/share/phptelemeter\"

; Do you want to ignore any runtime errors that occur and continue instead?
ignore_errors=false

; What email address to use as the From: address when sending warning mails:
email=\"youremail@domain.tld\"

; Enable password encryption? Reminder: This is NOT SECURE!
; (to get the encrypted value of a password, use --encrypt)
encrypt_passwords=false

; Do you want phptelemeter to use a cache file for state tracking?
; phptelemeter will look for / try to create it's cache file in
; either your home directory, or the system directory.
; You can override the path with the optional cache_file parameter.
; This file has to be writeable by phptelemeter!
use_cache=true
;cache_file=

; Timezone to use. Check http://www.php.net/manual/en/timezones.php
; for a list of supported timezones
timezone=Europe/Brussels

; Proxy configuration. Leave proxy_host blank to not use a proxy.
; If you set proxy_authenticate to true, you must fill the username
; and password too.
[proxy]
proxy_host=
proxy_port=8080
proxy_authenticate=false
proxy_username=
proxy_password=

; publisher configuration. This section can require additional parameters,
; which are publisher-dependent.
[publisher]
; This can be set to either plaintext, plaintext_graphonly, machine, no_output
; or html, and the file needs to be present in the modules directory!
publisher=\"plaintext\"
; The separator for the machine publisher
separator=\",\"
;



[account-1]
username=\"myuser\"
password=\"mypassword\"
; The parser can either be telemeter4tools, telemeter_web, dommel_web,
; skynet_web, scarlet_web, edpnet_web, upccz_web or mobilevikings_api,
;  and the file needs to be present in the phptelemeter/modules
;  directory!
parser=\"aparser\"
; The subaccount selects the actual account in case there are multiple.
; If this parameter is not set, phptelemeter will take the first one.
;subaccount=\"The actual account identifier\"
; The description is optional
;description=\"My first account\"
; The percentage when, if crossed, the publishers should mark the quota
; 'red', and optionally send an email. If you don't want an email, leave
; warn_email blank. To disable both, set warn_percentage to 0.
warn_percentage=90
warn_email=\"youraddress@domain.tld\"

;[account-2]
;username=\"myuser\"
;password=\"mypassword\"
;parser=\"aparser\"
;subaccount=\"subaccount\"
;description=\"My second account\"
;warn_percentage=90
;warn_email=\"youraddress@domain.tld\"

[die]\n";

		fwrite ($config, $configData);
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
	checkConfigurationForKeys($configuration           , array("general", "proxy", "publisher"), false, "configuration not correct.", "A configuration file was found, but it did not contain a valid\n%MSG%section.\nPlease check the README and NEWS, correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["general"]["required"]    , false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please check the README and NEWS, correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["general"], $configKeys["general"]["obsolete"]    , true, "obsolete key found in configuration", "The following obsolete keys were found in your configuration:\n%MSG%Please refer to the NEWS file for important changes to the \nconfiguration file.", false);
	checkConfigurationForKeys($configuration["proxy"]  , $configKeys["proxy"]["required"]      , false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please check the README and NEWS, correct and rerun phptelemeter.", true);
	checkConfigurationForKeys($configuration["publisher"], $configKeys["publisher"]["required"]  , false, "configuration not correct.", "A configuration file was found, but it was missing the\n%MSG%fields. Please check the README and NEWS, correct and rerun phptelemeter.", true);

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
				! array_key_exists("parser",   $configuration[$accName]) ||
				! array_key_exists("warn_percentage", $configuration[$accName]) ||
				! array_key_exists("warn_email", $configuration[$accName]))
					doError("configuration not correct.", "account info for " . $accName . " is not correct - ignoring.", false);
			else
			{
				$configuration["accounts"][]["username"] = $configuration[$accName]["username"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["password"] = $configuration[$accName]["password"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["parser"] = $configuration[$accName]["parser"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["warn_percentage"] = $configuration[$accName]["warn_percentage"];
				$configuration["accounts"][count($configuration["accounts"]) - 1]["warn_email"] = $configuration[$accName]["warn_email"];

				if (! array_key_exists("subaccount", $configuration[$accName]))
					$configuration["accounts"][count($configuration["accounts"]) - 1]["subaccount"] = "";
				else
					$configuration["accounts"][count($configuration["accounts"]) - 1]["subaccount"] = $configuration[$accName]["subaccount"];

				if (array_key_exists("description", $configuration[$accName]))
					$configuration["accounts"][count($configuration["accounts"]) - 1]["description"] = $configuration[$accName]["description"];
				else
					$configuration["accounts"][count($configuration["accounts"]) - 1]["description"] = $configuration[$accName]["username"] . ($configuration["accounts"][count($configuration["accounts"]) - 1]["subaccount"] != "" ? " (subaccount: " . $configuration["accounts"][count($configuration["accounts"]) - 1]["subaccount"] . ")":"");
					
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

function checkConfigurationForPublisherKeys($configuration, $keys)
{
	/* if this ain't an array, we assume nothing was set. */
	if (is_array($keys))
		checkConfigurationForKeys($configuration["publisher"], $keys, false, "configuration not correct.", "The selected publisher requires the following extra fields:\n%MSG%in the [publisher] section. Please check the README and NEWS, correct and rerun phptelemeter.", true);
}

/* Parses the command-line arguments and fits them in the configuration array */
function parseArgs($argv=array(), $configuration)
{
	
	/* short options in use:
	-a -c -d -e -f -g -h -i -n -o -p -r -t -x -z
	-C -D -N -V
	*/
	 
	array_shift($argv);

	/* set debug to false, we can correct it later if needed */
	$configuration["general"]["debug"] = false;

	for($i = 0; $i < count($argv); $i++)
	{
		switch ($argv[$i])
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
				writeDummyConfig(getcwd() . "/" . _configFileName);
				quit();
				break;
			}

			case "--version":
			case "-V":
			{
				showVersion();
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

			case "--ignore-errors":
			case "-i":
			{
				$configuration["general"]["ignore_errors"] = true;
				break;
			}

			case "--encrypt":
			case "-e":
			{
				/* do encrypt */
				$password = $argv[++$i];
				$encryptedPw = cryptPassword($password, "encrypt", true, true);
				showVersion();
				echo "Encrypted password: " . $encryptedPw . "\n";
				quit();
				break;
			}

			case "--decrypt":
			case "-x":
			{
				/* do decrypt */
				$password = $argv[++$i];
				$decryptedPw = cryptPassword($password, "decrypt", true, true);
				showVersion();
				echo "Decrypted password: " . $decryptedPw . "\n";
				quit();
				break;
			}

			case "--publisher":
			case "-p":
			{
				$configuration["publisher"]["publisher"] = $argv[++$i];
				break;
			}

			case "--cache-file":
			case "-C":
			{
				$configuration["general"]["cache_file"] = $argv[++$i];
				break;
			}

			case "--enable-cache":
			case "-a":
			{
				$configuration["general"]["use_cache"] = true;
				break;
			}

			case "--disable-cache":
			case "-o":
			{
				$configuration["general"]["use_cache"] = false;
				break;
			}
			
			case "--add-option":
			case "-t":
			{
				$configuration[$argv[++$i]][$argv[++$i]] = $argv[++$i];
				break;
			}
			
			case "--no-config":
			case "-N":
			{
				$configuration["general"]["no-config"] = true;
				break;
			}

			case "--help":
			case "-h":
			default:
			{
				showVersion();
				echo <<<EOM
phptelemeter [options]

General options:
-c,	--check-version		Checks if your phptelemeter is the latest
-D,	--debug			Shows lots of debugging info
-h,	--help			Shows this help message
-i,	--ignore-errors		Ignores any errors that might occur and continue
-n,	--new-config		Makes a new dummy config file in the current dir
-N,	--no-config		Don't create a config file if none exists
-t,	--add-option <section> <key> <value>
				Adds the key=value to the [section] of the configuration
-V,	--version		Shows the version and exits

Encryption/decryption:
-e,	--encrypt <password>	Encrypts the supplied password
-x,	--decrypt <password>	Decrypts the supplied password

Cache file:
-C,	--cache-file <name>	Where to look for the cache file
-a,	--enable-cache		Enables the cache file
-o,	--disable-cache		Disables the cache file

Output modifiers:
-d,	--daily			Shows statistics for current period
-f,	--file-output		Activates file output instead of screen output
-g,	--graph			Shows the usage graphs
-p,	--publisher <name>	Uses the supplied publisher
-r,	--remaining		Shows your max traffic allotment for today
-z,	--resetdate		Shows the quota reset date

Options specified here override the configuration file.

EOM;
				quit();
			}
		}
		dumpDebugInfo($configuration["general"]["debug"], "ARG: " . $argv[$i] . "\n");
	}

	return ($configuration);
}

function showVersion()
{
	echo "phptelemeter - v" . _version . "\n";
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

	dumpDebugInfo($configuration["general"]["debug"], "PARSER: Trying to load " . $parser . "\n");

	require_once($parser);

	if (! defined($parserID))
		doError("Invalid parser", "The parser " . $aParser . " is not valid!", true);

	dumpDebugInfo($configuration["general"]["debug"], "PARSER: Loaded parser " . $aParser . ", version " . constant($parserID) . "\n");
}

function loadPublisher($configuration)
{
	$publisher = "modules/publisher_" . $configuration["publisher"]["publisher"] . ".inc.php";

	dumpDebugInfo($configuration["general"]["debug"], "PUBLISHER: Trying to load " . $publisher . "\n");

	require_once($publisher);

	if (! defined("_phptelemeter_publisher"))
		doError("Invalid publisher", "The publisher " . $configuration["publisher"]["publisher"] . " is not valid!", true);

	dumpDebugInfo($configuration["general"]["debug"], "PUBLISHER: Loaded publisher " . _phptelemeter_publisher . ", version " . _phptelemeter_publisher_version . "\n");
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
	if (checkISPCompatibility($isp, "separate_quota") == true)
	{
		$returnValue["download"]["max"]     = $data["download_used"];
		$returnValue["download"]["use"]     = $data["download_remaining"];
		$returnValue["download"]["left"]    = $returnValue["download"]["max"] - $returnValue["download"]["use"];
		$returnValue["download"]["percent"] = (100 / $returnValue["download"]["max"]) * $returnValue["download"]["use"];
		$returnValue["download"]["hashes"]  = $returnValue["download"]["percent"] / 5;

		$returnValue["upload"]["max"]     = $data["upload_used"];
		$returnValue["upload"]["use"]     = $data["upload_remaining"];
		$returnValue["upload"]["left"]    = $returnValue["upload"]["max"] - $returnValue["upload"]["use"];
		$returnValue["upload"]["percent"] = (100 / $returnValue["upload"]["max"]) * $returnValue["upload"]["use"];
		$returnValue["upload"]["hashes"]  = $returnValue["upload"]["percent"] / 5;
	}
	else
	{
		$returnValue["total"]["use"] = $data["used"];
		$returnValue["total"]["left"] = $data["remaining"];
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

function checkVersion($doCheck, $proxyInfo, $cryptEnabled)
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
				curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyInfo["proxy_username"] . ":" . cryptPassword($proxyInfo["proxy_password"], "decrypt", $cryptEnabled));
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

/* expects DAY/MONTH/YEAR notation => DD/MM/YY */
function calculateDaysLeft($resetDate)
{
	$temp = strpos($resetDate, "/");
	$day = substr($resetDate, 0, $temp++);
	$temp2 = strpos($resetDate, "/", $temp);
	$month = substr($resetDate, $temp, ($temp2 - $temp));
	$year = substr($resetDate, ++$temp2);

	$returnValue = round(((mktime (0,0,0, $month, $day, $year) - mktime(0,0,0, date("m"), date("d"), date("Y"))) / 86400),0);
	return ($returnValue);
}

function dumpDebugInfo($debug, $data)
{
	/* sadly, a global, but it's going to be hard to get that data passed around otherwise. */
	global $credentialInfo;

	if ($debug == true)
	{
		$data = obfuscateLog($data, $credentialInfo);

		print_r($data);
	}
}

function obfuscateLog($data, $credentialInfo)
{
	if (is_array($data))
	{
		$newdata = "";
		
		/* if it's an array, recurse */
		foreach ($data as $key => $value)
		{
			/* special cases, we need to remove the username/pw from the config dump */
			if (strpos($key, "username") !== false || (strpos($key, "password") !== false && strpos($key, "passwords") === false))
				$newdata[$key] = "-HIDDEN-";
			else
				$newdata[$key] = obfuscateLog($value, $credentialInfo);
		}
		$data = $newdata;
	}
	else
		$data=$data;//$data = str_replace($credentialInfo, "-HIDDEN-", $data);

	return ($data);
}

function getAllCredentials($configuration)
{
	$returnValue = array();

	for ($i=0; $i < count($configuration["accounts"]); $i++)
	{
		$returnValue[] = $configuration["accounts"][$i]["username"];
		$returnValue[] = $configuration["accounts"][$i]["password"];
	}

	return ($returnValue);
}

function sendWarnEmail($debug, $usage, $description, $percentage, $fromAddress, $toAddress, $resetDate, $daysLeft)
{
	$sendMail = false;

	if (array_key_exists("total", $usage))
	{
		/* handle 'total' quota */
		if ($usage["total"]["percent"] > $percentage)
		{
			$sendMail = true;
			$text = "You have used " . round($usage["total"]["percent"],2) . "% (" . $usage["total"]["use"] . " MiB) of your total transfer quota of " . $usage["total"]["max"] . " MiB.";
		}
	}
	else
	{
		/* handle separate quotas */
		if ($usage["download"]["percent"] > $percentage || $usage["upload"]["percent"] > $percentage)
		{
			$sendMail = true;
			$text  = "You have used " . round($usage["download"]["percent"],2) . "% (" . $usage["download"]["use"] . " MiB) of your download quota of " . $usage["download"]["max"] . " MiB.";
			$text .= "You have used " . round($usage["upload"]["percent"],2) . "% (" . $usage["upload"]["use"] . " MiB) of your upload quota of " . $usage["upload"]["max"] . " MiB.";
		}
	}


	if ($sendMail == true)
	{
		$subject = "phptelemeter warning for " . $description . " - usage exceeded " . $percentage . "%";

		$message  = "Hello,\n\n";
		$message .= "This is a phptelemeter warning email for account: " . $description . ".\n\n";
		$message .= $text;
		$message .= "\n";
		$message .= "Your quota will be reset on " . $resetDate . " (" . $daysLeft . " days left).";
		$message .= "\n\n";
		$message .= "This is a generated message - please do not reply to it.\n";

		$headers  = "From: " . $fromAddress . "\r\n";
		$headers .= "Reply-To: " . $fromAddress . "\r\n";
		$headers .= "X-Mailer: phptelemeter " . _version;

		$parameters = "-r " . $fromAddress;

		dumpDebugInfo($debug, "Sending email:\n" .
			"Headers: " . $headers . "\n" .
			"To: " . $toAddress . "\n" .
			"Subject: " . $subject . "\n" .
			"Message: " . $message . "\n" .
			"Params: " . $parameters . "\n");

		mail($toAddress, $subject, $message, $headers, $parameters);
	}
}

function cryptPassword($input, $mode, $cryptEnabled, $cliCall=false)
{
	if ($cryptEnabled == true)
	{
		checkModules(array("mcrypt"));

		if (strlen($input) == 0)
		{
			/* only error out when we're called from the cli.
  			   since the proxy pw can be blank, it's not good to quit then. */
			if ($cliCall == true)
				doError("crypt string missing","You did not supply a string to " . $mode . "!", true);

			$returnValue = $input;
		}
		else
		{
			$td = mcrypt_module_open('blowfish', '', 'ecb', '');
			$iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td, _key, $iv);

			if ($mode == "encrypt")
				$returnValue = base64_encode(mcrypt_generic($td, $input));
			elseif ($mode == "decrypt")
				$returnValue = mdecrypt_generic($td, base64_decode($input));
		}
	}
	else
		$returnValue = $input;

	return ($returnValue);
}

function loadCacheFile($debug, $cacheFile)
{
	/* if the file doesn't exist, create a dummy */
	if (! file_exists($cacheFile))
	{
		dumpDebugInfo($debug, "Cache file does not exist, creating empty file...");
		$fp = fopen($cacheFile, "w");
		if ($fp !== false)
		{
			fwrite($fp, "; Empty cache file for phptelemeter\n");
			fclose($fp);
		}
		else
			doError("error while writing initial cache file", "Could not create " . $cacheFile . ". Permission problem?", true);
	}

	/* read the config */
	$cache = parse_ini_file($cacheFile, true);

	return ($cache);
}

function saveCacheFile($debug, $cacheFile, $cache)
{
	dumpDebugInfo($debug, "Saving cache data:\n");
	dumpDebugInfo($debug, $cache);

	if (write_ini_file($cacheFile, $cache) === false)
		doError ("error writing cache", "Error while writing cache " . $cacheFile. "\nPermission problem?", true);
}

/* function taken from php online manual */
function write_ini_file($path, $assoc_array)
{
    $content = '';
    $sections = '';

    foreach ($assoc_array as $key => $item)
    {
        if (is_array($item))
        {
            $sections .= "\n[{$key}]\n";
            foreach ($item as $key2 => $item2)
            {
                if (is_numeric($item2) || is_bool($item2))
                    $sections .= "{$key2} = {$item2}\n";
                else
                    $sections .= "{$key2} = \"{$item2}\"\n";
            }
        }
        else
        {
            if(is_numeric($item) || is_bool($item))
                $content .= "{$key} = {$item}\n";
            else
                $content .= "{$key} = \"{$item}\"\n";
        }
    }

    $content .= $sections;

	$handle = @fopen($path, "w");
	if ($handle === false)
		return false;

    if (fwrite($handle, $content) === false)
        return false;

    fclose($handle);

    return true;
}

function setPublisherParameters(&$publisher, $configuration)
{
	dumpDebugInfo($configuration["general"]["debug"], "Setting keys in publisher:\n");
	$keys = $publisher->getNeededConfigKeys();
	
	if (is_array($keys))
	{
		foreach ($keys as $aKey)
		{
			dumpDebugInfo($configuration["general"]["debug"], $aKey . " => " . $configuration["publisher"][$aKey] . "\n");
			$publisher->setConfigKey($aKey, $configuration["publisher"][$aKey]);
		}
	}
}

function checkForFile($aFile, $doExit)
{
		if (! file_exists($aFile))
			doError("file not found", "Could not find file " . $aFile . "...", $doExit);
}
?>
