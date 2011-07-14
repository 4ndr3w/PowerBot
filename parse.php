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

class irc_parse 
{
	function text($input)
	{
		unset($input[0]);
		unset($input[1]);
		unset($input[2]);
		
		$text = implode(" ", $input);
		$text = substr($text, 1);
		return $text;
	}
	
	function nick($user)
	{
		$nick = explode("!", $user);
		
		$nick = $nick[0];
		$nick = substr($nick, 1);
		
		return $nick;
	}
	
	function username($nick)
	{
	switch ( $nick[0] )
	{
		case '~':
			return array("nick" => substr($nick, 1), "level"=> 6);
			break;
			
		case '&':
			return array("nick" => substr($nick, 1), "level"=> 5);
			break;
		
		case '@':
			return array("nick" => substr($nick, 1), "level"=> 4);
			break;
		
		case '%':
			return array("nick" => substr($nick, 1), "level"=> 3);
			break;
		case '+':
			return array("nick" => substr($nick, 1), "level"=> 2);
			break;
		default:
			return array("nick" => $nick, "level"=> 1);
			break;
		}
	}
	
	function getafter($array, $after, $string = true)
	{
		$i=0;
		while ( $i <= $after )
		{
			unset($array[$i]);
			$i++;
		
		}
	
		if ( $string )
		{
			return trim(implode(" ", $array));
		}
		else {
			$output = trim(implode(" ", $array));
			$output = explode(" ", $output);
	
		return $output;
		}
	}
	
}
?>
