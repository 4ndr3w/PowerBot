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

class ircbot {
	private $config;
	private $userlist;
	private $socket;
	private $version = "1.0";

	
	function init($config, $module_config)
	{
		if ( !$config )
			die("\nConfig was not received from loader!\n");

		$this->module_config = $module_config;
		$this->config = $config;
		

		
		$this->config = array("irc" => array(), "database"=>array());
		$this->config['irc']['server'] = strval($config->irc['server']);
		$this->config['irc']['port'] = strval($config->irc['port']);
		$this->config['irc']['nick'] = strval($config->irc['nick']);
		$this->config['irc']['nickserv'] = strval($config->irc['nickserv']);
		$this->config['irc']['serverpass'] = strval($this->config['serverpass']);
		$this->config['irc']['channel'] = strval($config->irc['channel']);
		
		// Remove unused variables
		unset($_config);
		unset($config);
		unset($module_config);
		
		$this->parse = new irc_parse;
		$this->modemgr = new powerbot_modemgr($this->config['irc']['server']);
		$this->connect();
	}
	
	
	function reboot()
	{
		unset($this->modemgr->userlist);
		unset($this->config);
		$this->send("QUIT :Reboot");
		fclose($this->socket);
		$this->init();
		return;
	}
	
	function shutdown()
	{
		unset($this->modemgr->userlist);
		unset($this->config);
		$this->send("QUIT :Shutdown");
		fclose($this->socket);
		die();
		return;
	}
	
	function connect()
	{
		$this->socket = fsockopen($this->config['irc']['server'], intval($this->config['irc']['port']));

		if ( !$this->socket ) {
			echo "FAIL";
			sleep(10);
			die();
		}
		$this->send("NICK :".$this->config['irc']['nick']);
		$this->send("USER bot bot bot :bot");
		
	}
	
	
	function joinchannel()
	{
		$this->send("JOIN :".$this->config['irc']['channel']);
		return;
	}
	
	function send($text)
	{
		fwrite($this->socket, $text."\n");
		//echo $text."\n";
		return;
	}
	
	function getdata()
	{
		return fgets($this->socket);
	}
	
	function identify()
	{
		if ( !empty($this->config['irc']['nickserv']) )
		{
			$this->privmsg("nickserv", "identify ".$this->config['irc']['nickserv']);
		}
	}
	
	function parse($data)
	{
		$data = trim($data);
		$chan = $this->config['irc']['channel'];
		$data = explode(" ", $data);
		
		//print_r($data);
		
		switch ($data[0]) 
		{
			case "PING":
				$this->send("PONG ".$data[1]);
				return;
			break;
		}
		foreach ( $this->module_config as $moduleconf )
		{
			if ( $moduleconf['type'] == TYPE_HOOK && $data[1] == $moduleconf['trig'] )
			{
				call_user_func($moduleconf['handler'], $this, $data);
			}
		}

		switch ($data[1])
		{
			case "PRIVMSG":
				 $this->parse_privmsg($data);
				break;

			case "001":
				// Onjoin scripts here
				echo "done\n\n";
				$this->identify();
				$this->joinchannel();
				break;
			
			case "353":
				$this->parse_userlist($data);
				break;
				
			case "JOIN":
				$nick = $this->parse->nick($data[0]);
				$this->modemgr->addnick($nick, $chan);
				break;
			
			case "MODE":
				$this->modemgr->parse_mode($data);
				break;
			
			case "KICK":
				$this->modemgr->delnick($data[3], $data[2]);
				break;
				
			case "PART":
				$nick = $this->parse->nick($data[0]);
				$this->modemgr->delnick($nick,$chan);
				break;
				
			case "QUIT":
				$nick = $this->parse->nick($data[0]);
				$this->modemgr->delnick($nick,$chan);
				break;
			case "NICK":
				$oldnick = $this->parse->nick($data[0]);
				$newnick = $data[2];
				echo $oldnick." -> ".$newnick."\n";
				$this->modemgr->movenick($newnick,$oldnick,$chan);
				break;
		}
		return;
	}
	
	function isop($chan, $nick)
	{
		return ($this->modemgr->userlist[$chan][$nick]["level"] <= 3);
	}
	
	function parse_privmsg($data)
	{
		$channel = $data[2];
		$nick = $this->parse->nick($data[0]);
		$text = trim($this->parse->text($data));
		$text_explode = explode(" ", $text);
		$args = $this->parse->getafter($text_explode, 1);
		if ( $channel == (string)$this->config['irc']['channel'] ) { 
			echo "<".$channel." - ".$this->get_levelsymbol($nick, $channel)."".$nick."> ".$text."\n";
		}
		
		switch ( $text_explode[0] ) 
		{
				
			case '!shutdown':
				if ( $this->modemgr->userlist[$nick]['level'] != 6 )
					$this->shutdown();	
				break;
				
			case '!getlevel':
				if ( !$this->debug )
					return;
				if ( !empty($text_explode[1]) )
					$this->privmsg($channel, $text_explode[1]." on ".$channel." is authlevel ".$this->modemgr->userlist[$channel][$text_explode[1]]["level"]);
				else
					$this->privmsg($channel, $nick." on ".$channel." is authlevel ".$this->modemgr->userlist[$channel][$nick]["level"]);
				break;
			case '!reboot':
				if ( $this->modemgr->userlist[$nick]['level'] != 6 )
					return;
				$this->reboot();
				break;

			default:
				foreach ( $this->module_config as $moduleconf )
				{
					if ( $moduleconf['type'] == TYPE_PRIVMSG && $text_explode[0] == $moduleconf['trig'] )
						call_user_func($moduleconf['handler'], $this, $this->modemgr->userlist[$channel][$nick]['level'], $channel, $text_explode);
				}
				break;
			}

	}


	function privmsg($chan, $text)
	{
		echo "<".$chan." - ".$this->get_levelsymbol($this->config['irc']['nick'], $chan)."".$this->config['irc']['nick']."> ".$text."\n";
		$this->send("PRIVMSG ".$chan." :".$text);
	}
	
	function action($chan, $text)
	{
		$this->send('PRIVMSG ' . $chan . ' :' . chr(1) . 'ACTION ' .$text . chr(1));
		echo "<".$chan." - ".$this->get_levelsymbol($this->config['irc']['nick'], $chan)."".$this->config['irc']['nick']."> /me ".$text."\n";
	}

	function mainloop()
	{
		while(1)
		{
			$newdata = $this->getdata();
			if ( $newdata )
				$this->parse($newdata);
		}
	}
	
	function parse_userlist($data)
	{
		
		$array = $this->parse->getafter($data, 4, false);
		$array[0] = substr($array[0], 1);
		$userlist = array();
		foreach ( $array as $user )
		{
			$user_output = $this->parse->username($user);

			$userlist[$user_output["nick"]] = array("level"=>$user_output["level"]);
		}

		$this->modemgr->userlist[$data[4]] = $userlist;
		$this->modemgr->import_userlist();
	}

	function get_levelsymbol($nick, $chan)
	{
		// needs to be looked at
		return "";
		$chan = $this->config['irc']['channel'];
		$thisuserlevel = $this->modemgr->userlist[$chan][$nick]["level"];
		if ( !$thisuserlevel )
			return "";
	
		switch ( $thisuserlevel ) 
		{
			case 6:
			return "~";
			break;
		case 5:
			return "&";
			break;
		case 4:
			return "@";
			break;
		case 3:
			return "%";
			break;
		case 2:
			return "+";
			break;
		case 1:
			return "";
			break;
		}
	}
	

}
?>
