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


/*
 *	Does not work anymore due to changes in the armory page.
 *  Leaving it as an example of pulling data from another site
 */
function wowarmory_config()
{
	$commands = array();
	$commands[] = array("type" => TYPE_PRIVMSG, "trig" => "!wow", "handler" => "wowarmory_request");
	return $commands;
}

function wowarmory_request($irc, $userdata, $chan, $args)
{
	$realm = $args[1];
	$character = $args[2];
	$data = file_get_contents("http://www.wowarmory.com/character-sheet.xml?r=".$realm."&n=".$character."&rhtml=n");
	if ( !$data )
	{
		$irc->privmsg($chan, "Player not found.");
		return;
	}
	$data = new SimpleXMLElement($data);
	$irc->privmsg($chan, $data->characterInfo->character['name']." Level ".$data->characterInfo->character['level']." ".$data->characterInfo->character['race']." ".$data->characterInfo->character['class']);
}
?>