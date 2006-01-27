#!/usr/bin/php4 -q
<?php

/*

phpTelemeter - a php script to read out and display the telemeter stats.

Copyright (C) 2005 - 2006 Jan De Luyck <jan -at- kcore -dot- org>

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

error_reporting(E_ERROR | E_WARNING);
define("_phptelemeter", 1);

require("phptelemeter.inc.php");

/* ----------------------- */
/* Main script starts here */
/* ----------------------- */

/*
Parse args and configuration file.
*/

// we need this to enable help to work without a config file
$configuration = parseArgs($argv, null);

/* find the config file */
$configFile = findConfigFile($configFiles, $configuration);

$configuration = readConfig($configFile);
$configuration = parseArgs($argv, $configuration);

if ($configuration["general"]["debug"] == true)
{
	error_reporting(E_ALL);
	dumpConfig($configuration);
}

$configuration = checkConfig($configuration, $configFile, $configKeys);

/* set the include path */
//echo $configuration["general"]["modulepath"];
set_include_path($configuration["general"]["modulepath"]);


/* load the necessary modules */

/* load and configure the parser */
loadParser($configuration);
$parser = new telemeterParser();
checkModules($parser->getNeededModules());

/* load and configure the publisher */
loadPublisher($configuration);
$publisher = new telemeterPublisher();
checkModules($publisher->getNeededModules());

$parser->setDebug($configuration["general"]["debug"]);
$publisher->setDebug($configuration["general"]["debug"]);

/* put the header on the screen */
if ($configuration["general"]["file_output"] == false)
	echo $publisher->mainHeader();

/* loop through all our users */
for ($i = 0; $i < count($configuration["accounts"]); $i++)
{
	// start buffering
	ob_start();

	if ($configuration["general"]["file_output"] == true)
		echo $publisher->mainHeader();

	echo $publisher->accountHeader($configuration["accounts"][$i]["description"]);

	/* run the telemeterParser getData() routine */
	$data = $parser->getData($configuration["accounts"][$i]["username"],$configuration["accounts"][$i]["password"]);

	if ($data === false)
		continue;

	echo $publisher->accountFooter();

	echo $publisher->publishData($data,$configuration["general"]["show_remaining"], $configuration["general"]["show_daily"], $configuration["general"]["show_graph"], $configuration["general"]["show_resetdate"]);

	if ($configuration["general"]["file_output"] == true)
	{
		echo $publisher->mainFooter();

		$buffer = ob_get_contents();
		ob_end_clean();
		outputData($configuration, $buffer, $configuration["accounts"][$i]["username"]);
	}
	else
		ob_end_flush();
}

if ($configuration["general"]["file_output"] == false)
	echo $publisher->mainFooter();


/* signing off. */
$parser->destroy();
$publisher->destroy();
quit();
?>
