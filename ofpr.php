<?php
//
// OFP CLASS Version 2.05
//
// Version history:

// 18/08-2002 (version 2.05) by: [WKK]-EazyOne
// - lines 235 to 238 have been quoted for Win2000 compatibility (thx Mivo)
// - You can try unquoting these lines if having trouble
// - Code changed so RTSQ shows the "real" name of island (ie. Nogova insteadt of Noa)

// 06/08-2002 (Version 2.01) by: Mivo
// - Compatible with Operation Flashpoint:Resistance and new netcode versions (1.60 and above ?) servers 
//   in Sockets and DirectPlay network mode
// - corrected problem in player table if mixed players with and without squad name
// - support for TIMELEFT variable (OFP 1.42 and above)
//
// see "Mivo:" comments in code (mivo@post.cz)
//

// 25/10-2001 (Version 1.03): Got a little quick in last correction.
// Readme.txt, said that $system=0 was Linux, and $system=0 was Windows.
// - this should ofcourse have been $system=0 was Linux, and $system=1 was Windows.
// Param1 (gametime), was NOT divided by 60.
// - Param1 correction was removed, so it showed e.g. 600 minutes instead of 10 (forgot to divide by 60), after my last correction.

// 25/10-2001 (Version 1.02): Problems with IIS5.0 and PHP4 - fixed.
// No check, if param1 and param2 are 0. Just show the values, instead of trying to correct them. Funny failure in the OFP.php script, so I had to do the param1 & param2 == 0 check in the server.php file. Thanx to Vader-21VB.

// 24/10-2001 (Version 1.01): The "no-display" of squads, has now been fixed.

// 23/10-2001 (Version 1.00): Initial release.




//
//	Functions used to sort players by scores, deaths, teams or names.
//	Needs to be defined globally in order for usort to call it
// 	
function scoresort ($a, $b) {
	if ($a["score"] == $b["score"]) return 0;
	if ($a["score"] > $b["score"]) {
		return -1;
	} else {
		return 1;
	}
}

function namesort ($a, $b) {
	if (strtolower($a["name"]) == strtolower($b["name"])) return 0;
	if (strtolower($a["name"]) < strtolower($b["name"])) {
		return -1;
	} else {
		return 1;
	}
}	

function deathsort ($a, $b) {
	if ($a["deaths"] == $b["deaths"]) return 0;
	if ($a["deaths"] < $b["deaths"]) {
		return -1;
	} else {
		return 1;
	}
}	

function teamsort ($a, $b) {
	if (strtolower($a["team"]) == strtolower($b["team"])) return 0;
	if (strtolower($a["team"]) < strtolower($b["team"])) {
		return -1;
	} else {
		return 1;
	}
}	

Class Ofp {
	var $m_playerinfo		="";		// Info about players
	var $m_servervars		="";		// Info about the server 
	var $serverquerytype		="";
	var $errmsg			="";

	//
	// System definition. Which system is PHP running @ 0 (default) = Linux/Unix, 1 = Windows
	// This HAS to be defined, as Windows does NOT support socket_timeout functions in PHP.
	//
	var $system			=1;

	//
	// Get exact time, used for timeout counting
	//
	function timenow() {
		return doubleval(ereg_replace('^0\.([0-9]*) ([0-9]*)$','\\2.\\1',microtime()));
	}	

	//
	// Read raw data from server
	//
	function getServerData($query,$command,$serveraddress,$portnumber,$waittime) {
		$serverdata		="";
		$serverdatalen=0;
		
		if ($waittime< 500) $waittime= 500;
		if ($waittime>2000) $waittime=2000;
		$waittime=doubleval($waittime/1000.0);

		if (!$ofpsocket=fsockopen("udp://".$serveraddress,$portnumber,$errnr)) {
			$this->errmsg="No connection";
			return false;
		}
		
		socket_set_blocking($ofpsocket,true);
		if ($this->system==0) socket_set_timeout($ofpsocket,0,500000);
		fwrite($ofpsocket,$command,strlen($command));	

		$starttime=$this->timenow();
		if ($query=="ofpinfo") {
			do {
				$serverdata.=fgetc($ofpsocket);
				$serverdatalen++;
				$socketstatus=socket_get_status($ofpsocket);
				if ($this->timenow()>($starttime+$waittime)) {
					$this->errmsg="Connection timed out";
					fclose($ofpsocket);
					return false;
				}
			} while ($socketstatus["unread_bytes"]);
		} else {
			do {
				$serverdata.=fgetc($ofpsocket);
				$serverdatalen++;
				$socketstatus=socket_get_status($ofpsocket);
				if ($this->timenow()>($starttime+$waittime)) {
					$this->errmsg="Connection timed out";
					fclose($ofpsocket);
					return false;
				}
			} while (substr($serverdata,strlen($serverdata)-7)!="\\final\\");
			// Is data complete ?
			// This strange doublecheck is needed since OFP serverdata might arrive out of order
			if ((substr($serverdata,strlen($serverdata)-7)!="\\final\\") || (substr($serverdata,1,8)!="gamename")) {
				$this->errmsg="Data incomplete";
				return false;
			}
		}
		fclose($ofpsocket);
		return $serverdata;		
	}	


	function correctdata ($serverdata2) {
		//
		// Set m_servervars
		//
		if (ord(substr($serverdata2,24,1))-2<0) {
			$this->m_servervars["numplayers"]=0;
		} else {
			$this->m_servervars["numplayers"]=(ord(substr($serverdata2,24,1))-2);
		}

		$this->m_servervars["hostname"]=(str_replace(chr(0),"",substr($serverdata2,144,strlen($serverdata2))));
	
		if (ord(substr($serverdata2,100,1)) <> 0 && $this->serverquerytype=="basic") {
			$this->m_servervars["gametype"]=(str_replace(chr(0),"",substr($serverdata2,100,40)));
		} elseif(ord(substr($serverdata2,100,1)) == 0) {
			$this->m_servervars["gametype"]="No mission loaded";
		}
			
		//
		// Set m_servervars
		// Correct:	maxplayers, gamever
		// Add:		servertype (Private/Public), reqver, gamestatus, 
		// gamever, reqver, 
		// 
		// 

		//
		// Correct maxplayers, this is ALWAYS 32 in Gamespy
		//
		if ((ord(substr($serverdata2,20,1))-2)<=0) {
			$maxplayers="Not set";
		} else {
			$maxplayers=(ord(substr($serverdata2,20,1))-2);
		}
		$this->m_servervars["maxplayers"]=$maxplayers;

		//
		// Correct gamever, get rid of . between version numbers
		//
		$this->m_servervars["gamever"]=ord(substr($serverdata2,92,1));

		//
		// Add servertype, (Private / Public - NO Password / Password)
		//
		if (ord(substr($serverdata2,16,1))==129) {
			$this->m_servervars["servertype"]="Private";
		} elseif (ord(substr($serverdata2,16,1))==1) {
			$this->m_servervars["servertype"]="Public";
		} else {
			$this->m_servervars["servertype"]="Unknown";
		}

		//
		// Add reqver, gamestatus (mission loaded OR not)
		//
		$this->m_servervars["reqver"]=ord(substr($serverdata2,96,1));
		if (ord(substr($serverdata2,100,1))<>0) {
			$this->m_servervars["gamestatus"]="Mission loaded";
		} else {
			$this->m_servervars["gamestatus"]="No mission loaded";
		}

		//
		// Add reqver, gamestatus (mission loaded OR not)
		//
		$this->m_servervars["param1"]=$this->m_servervars["param1"]/60;
	}

	// **********************************************************************
	// getServerStatus
	// Read rules/setup from the gameserver into servervars
	// Return true if successful
	// **********************************************************************	
	function getServerinfo ($serveraddress,$portnumber,$portnumber2,$timeout, $sortby) {

		$cmd="\x00\x02\x12\x00\x01\x60\x98\x24\xF7\xBE\xD0\xD2\x11\x95\xEA\x00\xA0\xC9\xA5\x7F\x0B";
		$cmd2="\\status\\";
		$this->serverquerytype=false;
// ********************************************
// ** EazyOne:                               **
// ** Thx to Mivo for providing me with info **
// ** Next lines are quoted for making OFPR  **
// ** Work on Win2000                        **
// ********************************************
//		if ($serverdata2=$this->getServerData("ofpinfo",$cmd,$serveraddress,$portnumber,$timeout)) {
//			$this->serverquerytype="basic";
//			$this->correctdata ($serverdata2);
//		}

		//
		// Whether or NOT to run the GS query, to get extended server-info.
		// IF portnumber2=portnumber OR portnumber2 <> 0, this means to RUN the extended server-query.
		// (maxplayers is corrected, and servertype-is added).
		//
		// Mivo: Run extended query even basic query unsuccessfull - OFP:Resistance
		// doesn't support basic query
		
		
		if ($portnumber2==$portnumber) $portnumber2=$portnumber+1;

		if ($portnumber2<>0) {
			if ($serverdata=$this->getServerData("gsinfo",$cmd2,$serveraddress,$portnumber2,$timeout)) {
				$serverdata=substr($serverdata,1,strlen($serverdata)-7);

				$this->serverquerytype="extended";


				// Split data and fill into array

				// Mivo:	Added STR_REPLACE to solve problem with empty TEAM string, now replaced by space character
				//	Originally was :
				//	$name_tok = strtok ($serverdata,"\\");
				//
				
				$name_tok = strtok (str_replace("\\\\","\\ \\",$serverdata),"\\");
				$val_tok  = strtok ("\\");
				while (strlen($name_tok)) {
					if ($name_tok!="final") {
						$vars[$name_tok]=$val_tok;
					}	
					$name_tok = strtok ("\\");
					$val_tok  = strtok ("\\");
				}

				while (list($key,$data) = each ($vars)) {
					if (!strncmp($key,"player_",7)) {
						$player[substr($key,7)]["name"]=$data;
					} elseif (!strncmp($key,"team_",5)) {
						$player[substr($key,5)]["team"]=$data;
					} elseif (!strncmp($key,"score_",6)) { 
						$player[substr($key,6)]["score"]=$data;
					} elseif (!strncmp($key,"deaths_",7)) {
						$player[substr($key,7)]["deaths"]=$data;
					} else {
						$this->m_servervars[$key]=$data;
					}
				}

				// Mivo: Server type test - if new netcode (variable IMPL) set variable to "extnew"

				if (isset($this->m_servervars["impl"])) {
					$this->serverquerytype="extnew";
				}

				// Move playerinfo to membervariable
				for ($i=0;$i<$this->m_servervars["numplayers"];$i++) {
					$this->m_playerinfo[$i]=array("name"=>$player[$i]["name"],"team"=>$player[$i]["team"],"score"=>$player[$i]["score"],"deaths"=>$player[$i]["deaths"]);
				}		

				// If there are players on the server we sort them by frags		
				if ($this->m_servervars["numplayers"]>0 && $sortby=="scores") {
					usort($this->m_playerinfo,"scoresort");
				} elseif ($this->m_servervars["numplayers"]>0 && $sortby=="deaths") {
					usort($this->m_playerinfo,"deathsort");
				} elseif ($this->m_servervars["numplayers"]>0 && $sortby=="teams") {
					usort($this->m_playerinfo,"teamsort");
				} elseif ($this->m_servervars["numplayers"]>0 && $sortby=="names") {
					usort($this->m_playerinfo,"namesort");
				} else {
					// If NO or INCORRECT sortvalue given - players will be sorted by score
					if ($this->m_servervars["numplayers"]>0) usort($this->m_playerinfo,"scoresort");
				}

				if ($this->serverquerytype=="extnew") {
				
				// Mivo: For OFP:Res (new netcode) - correct values of variables

					// max players minus 2
					if ($this->m_servervars["maxplayers"]-2<=0) {
						$maxplayers="Not set";
					} else {
						$maxplayers=$this->m_servervars["maxplayers"]-2;
					}
					$this->m_servervars["maxplayers"]=$maxplayers;

					// Password protected

					if ($this->m_servervars["password"]==0) {
						$this->m_servervars["servertype"]="Public";
      		} else {
						$this->m_servervars["servertype"]="Private";
      		}

					// game status

      		switch ($this->m_servervars["gstate"]) {
      			case "2":
      				$this->m_servervars["gamestatus"]="No mission selected";
      				break;
      			case "6":
      				$this->m_servervars["gamestatus"]="Players assignment";
      				break;
      			case "9":
      				$this->m_servervars["gamestatus"]="Debriefing";
      				break;
      			case "13":
      				$this->m_servervars["gamestatus"]="Briefing";
      				break;
      			case "14":
      				$this->m_servervars["gamestatus"]="Game in progress";
      		}
	
					// time limit
					$this->m_servervars["param1"]=$this->m_servervars["param1"]/60;

				} else {

				// Mivo: For OFP, call original function

					$this->correctdata ($serverdata2);
				}
			}
		}
		return $this->serverquerytype;
	}
}
?>
