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

class powerbot_modemgr
{
	public $modelist;
	public $userlist;
	private $usertemplate;
	
	function __construct($mainchan)
	{
		$this->usertemplate = array("q" => 0, "a" => 0, "o" => 0, "h" => 0, "v"=> 0 );
		$this->modelist=array();
		$this->userlist = array();
		$this->mainchan = $mainchan;
	}

	function addnick($nick, $chan )
	{
		if ( $this->mainchan )
		{
			$chan = $this->mainchan;
		}
		if ( !array_key_exists($chan, $this->userlist) )
		{
			$this->userlist[$chan] = array();
			$this->modelist[$chan] = array();
		}
		$this->userlist[$chan][$nick] = array("level" => 1);
		$this->modelist[$chan][$nick] = $this->usertemplate;
	}
	
	
	function delnick($nick, $chan)
	{
		if ( $this->mainchan )
		{
			$chan = $this->mainchan;
		}
		unset($this->modelist[$chan][$nick]);
		unset($this->userlist[$chan][$nick]);

	}
	
	function movenick($newnick, $oldnick, $chan)
	{
		if ( $this->mainchan )
		{
			$chan = $this->mainchan;
		}
		$this->userlist[$chan][$newnick] = $this->userlist[$chan][$oldnick];
		$this->modelist[$chan][$newnick] = $this->modelist[$chan][$oldnick];
		unset($this->userlist[$chan][$oldnick]);
	}
	
	function parse_mode($input)
	{
		$chan = $input[2];
		$modes = $input[3];
		$output = array();
		preg_match_all("/[+,-][v,h,o,a,q]+/",$modes, $out);
		$numsorted = 0;
		foreach ( $out[0] as $data )
		{
			$action = $data[0];
			$modes = str_split(substr($data, 1));
			foreach ( $modes as $mode)
			{
				$nick = $input[4+$numsorted];
				echo "Set ".$action.$mode." on ".$nick."\n";
				if ( $action == "+" )
				{
					$this->modelist[$chan][$nick][$mode] = 1;
				}
				else if ( $action == "-" )
				{
					$this->modelist[$chan][$nick][$mode] = 0;
				}
				$numsorted++;
			}
		}
		$this->regen_userlist();
	}
	
	function import_userlist()
	{
		foreach ($this->userlist as $chan => $data)
		{
			$this->modelist[$chan] = array();
			foreach ( $this->userlist[$chan] as $nick => $data )
			{
				$this->modelist[$chan][$nick] = $this->usertemplate;
				if ( $data['level'] == 1 )
				{
					// Nothing required
				}
				else if ( $data['level'] == 2 )
				{
					// +v
					$this->modelist[$chan][$nick]['v'] = 1;
				}
				else if ( $data['level'] == 3 )
				{
					// +h
					$this->modelist[$chan][$nick]['h'] = 1;
				}
				else if ( $data['level'] == 4 )
				{
					// +o
					$this->modelist[$chan][$nick]['o'] = 1;
				}
				else if ( $data['level'] == 5 )
				{
					// +a
					$this->modelist[$chan][$nick]['a'] = 1;
				}
				else if ( $data['level'] == 6 )
				{
					// +q
					$this->modelist[$chan][$nick]['q'] = 1;
				}
			}
		}
	}
	
	function regen_userlist()
	{
		$this->userlist = array();
		foreach ( $this->modelist as $chan => $data )
		{
			$this->userlist[$chan] = array();
			foreach ( $this->modelist[$chan] as $nick => $data )
			{
				$this->userlist[$chan][$nick] = array();
				
				if ( $this->modelist[$chan][$nick]['q'] == 1 )
				{
					$this->userlist[$chan][$nick] = array("level"=>6);
				}
				
				else if ( $this->modelist[$chan][$nick]['a'] == 1 )
				{
					$this->userlist[$chan][$nick] = array("level" => 5);
				}
				
				else if ( $this->modelist[$chan][$nick]['o'] == 1 )
				{
					$this->userlist[$chan][$nick] = array("level" => 4);
				}
				
				else if ( $this->modelist[$chan][$nick]['h'] == 1 )
				{
					$this->userlist[$chan][$nick] = array("level" => 3);
				}

				else if ( $this->modelist[$chan][$nick]['v'] == 1 )
				{
					$this->userlist[$chan][$nick] = array("level"=>2);
				}

				else
				{
					$this->userlist[$chan][$nick] = array("level"=>1);
				}
			}
		}
	}
	

}