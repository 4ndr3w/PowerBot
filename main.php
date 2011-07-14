<?php
/*
 * Copyright 2011 Andrew ( http://4ndr3w.me )
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 * 	http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
*/

// module hook types
define('TYPE_PRIVMSG', 1);
define('TYPE_RAW', 2);
define('TYPE_HOOK', 3);



echo "Starting PowerBot\n\n";

echo "Loading config...\n";
if ( !file_exists("config.xml") )
{
	echo "Error! config.xml does not exist.";
	die();
}

$_config = file_get_contents("config.xml");
if ( !$_config )
{
	echo "Error! Unable to open config.xml, or file is empty";
	die();
}

$config = new SimpleXMLElement($_config);
if ( !$config )
{
	echo "Error! Could not parse XML file.";
	die();
}


$module_config = array();
foreach ( $config->module as $mod )
{
	if ( file_exists("modules/".$mod['name']."/".$mod['name'].".php") )
	{
		include("modules/".$mod['name']."/".$mod['name'].".php");
		$module_config = array_merge(call_user_func($mod['name']."_config"), $module_config);
	}
}

include "parse.php";
include "modemgr.php";

include "irc.php";


$phpirc = new ircbot;

$phpirc->init($config, $module_config);
$phpirc->mainloop();

?>