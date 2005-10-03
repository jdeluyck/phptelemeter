#!/usr/bin/php4 -q
<?php

/*

phpTelemeter - a php script to read out and display the telemeter stats.

Copyright (C) 2005 Jan De Luyck <jan -at- kcore -dot- org>

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

define("_phptelemeter", 1);

require("phptelemeter.inc.php");

/* ----------------------- */
/* Main script starts here */
/* ----------------------- */

/* check for needed modules */
checkModules($neededModules);

/*
Parse args and configuration file.
*/


// we need this to enable help to work without a config file
$configuration = parseArgs($argv, null);

/* find the config file */
$configFile = findConfigFile($configFiles, $configuration);

$configuration = readConfig($configFile);
$configuration = parseArgs($argv, $configuration);
$configuration = checkConfig($configuration);

/* set the include path */
//echo $configuration["general"]["modulepath"];
set_include_path($configuration["general"]["modulepath"]);

if ($configuration["general"]["debug"] == true) dumpConfig($configuration);

/* load the necessary module */
loadParser($configuration);

$parser = new telemeterParser();
$parser->debug = $configuration["general"]["debug"];


if ($configuration["general"]["style"] == "human" && $configuration["general"]["file_output"] == false)
	echo "phptelemeter - version " . _version . "\n";

/* loop through all our users */
for ($i = 0; $i < count($configuration["accounts"]); $i++)
{
	// start buffering
	ob_start();

	if ($configuration["general"]["style"] == "human")
		echo "Fetching information for account " . $configuration["accounts"][$i]["username"] . "...";

	/* run the telemeterParser getData() routine */
	$data = $parser->getData($configuration["accounts"][$i]["username"],$configuration["accounts"][$i]["password"]);

	if ($data === false)
		continue;

	if ($configuration["general"]["style"] == "human")
		echo "done!\n\n";

	displayData($data["general"], $data["daily"]);

	if ($configuration["general"]["file_output"] == true)
	{
		$buffer = ob_get_contents();
		ob_end_clean();
		outputData($configuration, $buffer, $configuration["accounts"][$i]["username"]);
	}
	else
		ob_end_flush();
}
/* signing off. */
$parser->destroy();
quit();
?>
