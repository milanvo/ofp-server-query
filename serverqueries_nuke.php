<?php
if (!isset($mainfile)) { include("mainfile.php"); }
global $prefix;
include("header.php");
require("ofpr.php");






echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
echo '<HTML><HEAD><TITLE>OFP Server Real Time Query</TITLE>';
echo '</HEAD><BODY>';
echo '<FONT COLOR="ededed">';



echo "<center><H3>Real Time Server Query:</H3></center>";

// You can set server address and port to fixed value by edit and uncomment following line

// $server="IP:PORT";

if (!isset($server) && !isset($doquery)) {
	echo"
	<form method=POST action=$PHP_SELF?doquery=yes>
	<table>
		<tr>
			<td>SERVER:PORT</td>
			<td><input type=text name=server></td>
		</tr>
	</table>
	<input type=submit value=\"Query Server\" name=submit> <input type=reset value=Clear name=reset>
	</form>
	";
/*
		<tr>
			<td>Alternate port-number to do Gamespy query,<br>if running more than 1 server on same server-address/IP:</b></i></td>
			<td><input type=text name=gsport size=6></td>
		</tr> 
*/

	exit;
} else {

  $arguments=explode(":",$server);
  $serveradr=$arguments[0];
  $serverport=$arguments[1];
  if (!isset($serverport2) && $gsport=="") {
  	$serverport2=$serverport;
  } else {
  	$serverport2=$gsport;		
  }
}

$ofp=new Ofp;
// *************************************************************************************************
// * Syntax:                                                                                       *
// * getServerinfo($serveradr,$serverport,$serverport2,2000,"scores"), where:                      *
// *                                                                                               *
// * $serveradr=IP or FQDN (Fully qualified domain-name of server,                                 *
// * $serverport=portnumber, where game is hosted (default 2234), has to be set - even if default, *
// * $serverport2=portnumber to do extended GSA query on, default ($serverport+1=2235)             *
// *                                                                                               *
// * WARNING WARNING WARNING:                                                                      *
// * ------------------------                                                                      *
// * -- IF RUNNING MULTIPLE SERVERS ON SAME IP, BE SURE TO LEAVE 1 PORT FOR EXTENDED QUERY BETWEEN *
// * -- THE SERVERS HOSTED. E.G. NEVER HOST 1 GAME ON 2234 AND ANOTHER ON 2235, THEN THE FOLLOWING *
// * -- WILL HAPPEN: EXTENDED QUERY FOR GAME ON 2234,WILL BE ON 2236 AND EXTENDED FOR GAME ON 2235 *
// * -- WILL BE ON 2237                                                                            *
// * -- THIS IS MESSY, AND DONE BY MANY GAME HOSTERS                                               *
// *                                                                                               *
// * -- IF BEHIND A FIREWALL, BE SURE TO OPEN THE PORT FOR EXTENDED QUERIES AS WELL, OTHERWISE     *
// * -- NO PLAYERINFO, WORLDINFO, GAMELENGTH AND CAPS/FRAGS TO WIN, WILL BE QUERIED                *
// *                                                                                               *
// * 2000=timeout, leave this @ 2000 if querying, against servers with large ping.                 *
// * "scores"=sortby scores, other values to sort by: "names", "teams", "deaths"                   *
// *************************************************************************************************

// *************************************************************************************************
// * If $status gets defined, Success querying server, otherwise failure                           *
// *************************************************************************************************
if ($status=$ofp->getServerinfo($serveradr,$serverport,$serverport2,2000,"score")) {

	// **********************************************************************************
	// * Print out server-values, e.g. $ofp->m_servervars["hostname"], for servername   *
	// **********************************************************************************
	echo "<center><h2><b>".$ofp->m_servervars["hostname"]."</b></h2></center>";
	opentable2();
	echo "<table border=0 align=top>";
	echo "<tr><td>";

	echo "<h4><b>Server info:</b></h4>\n\n";
	echo "<table border=0>";
	echo "<tr><td>Game:</td><td>";
	if ($ofp->m_servervars["gamename"]=="opflashr") {
		echo "Operation Flashpoint Resistance";
	} elseif ($ofp->m_servervars["gamename"]=="opflash") {
		echo "Operation Flashpoint";
	}	
	echo "<tr><td>Version:</td><td>".$ofp->m_servervars["gamever"]."</td></tr>\n";
	echo "<tr><td>Required version:</td><td>".$ofp->m_servervars["reqver"]."</td></tr>\n";

// Mivo: Resistance only
	if ($status=="extnew") {
	}

	echo "<tr><td>Server type:</td><td>".$ofp->m_servervars["servertype"]."</td></tr>\n";
	echo "<tr><td>Game status:</td><td>".$ofp->m_servervars["gamestatus"]."</td></tr>\n";
	echo "<tr><td># players:</td><td>".$ofp->m_servervars["numplayers"]."</td></tr>\n";
	echo "<tr><td>Max. players:</td><td>".($ofp->m_servervars["maxplayers"])."</td></tr>\n";

	if ($ofp->m_servervars["maxplayers"]=="Not set") {
		$serverload="Not set";
	} else {
		$serverload=floor($ofp->m_servervars["numplayers"]/$ofp->m_servervars["maxplayers"]*100);		
	}
	if ($serverload<>"Not set") {
		echo "<tr><td>Server load</td><td>0%|<img src=\"pixel.gif\" height=8 width=$serverload border=0><img src=\"pixel1.gif\" height=8 width=".(100-$serverload)." border=0>|100% (<B>$serverload%</B>)</td></tr>\n";
	} 

	// **********************************************************************************
	// * End of Basic info table                                                        *
	// **********************************************************************************


	// **********************************************************************************
	// * If $status == "extended" AND players>0, print out extended info of server      *
	// **********************************************************************************
	if ($status<>"basic" && $ofp->m_servervars["numplayers"]<>0) {
		// **********************************************************************************
		// * If Mission is loaded, show Extended info, otherwise nobody is @ the server     *
		// **********************************************************************************
		if ($ofp->m_servervars["gamestatus"]=="Mission loaded" | (int)$ofp->m_servervars["gstate"]>2) {

			echo "<tr><td>Mission:</td><td>".$ofp->m_servervars["gametype"]."</td></tr>\n";

//**********************************************
//EazyOne start: Fixed so "real" mapname is showed.
//**********************************************

echo "<tr><td>World:</td><td>";
	if ($ofp->m_servervars["mapname"]=="Abel") {
		echo "Malden";
	} elseif ($ofp->m_servervars["mapname"]=="ABEL") {
		echo "Malden";
	} elseif ($ofp->m_servervars["mapname"]=="abel") {
		echo "Malden";
	} elseif ($ofp->m_servervars["mapname"]=="Eden") {
		echo "Everon";
	} elseif ($ofp->m_servervars["mapname"]=="EDEN") {
		echo "Everon";
	} elseif ($ofp->m_servervars["mapname"]=="eden") {
		echo "Everon";
	} elseif ($ofp->m_servervars["mapname"]=="Cain") {
		echo "Kolgujev";
	} elseif ($ofp->m_servervars["mapname"]=="CAIN") {
		echo "Kolgujev";
	} elseif ($ofp->m_servervars["mapname"]=="cain") {
		echo "Kolgujev";
	} elseif ($ofp->m_servervars["mapname"]=="Intro") {
		echo "Desert Island";
	} elseif ($ofp->m_servervars["mapname"]=="INTRO") {
		echo "Desert Island";
	} elseif ($ofp->m_servervars["mapname"]=="intro") {
		echo "Desert Island";
	} elseif ($ofp->m_servervars["mapname"]=="Noe") {
		echo "Nogova";
	} elseif ($ofp->m_servervars["mapname"]=="NOE") {
		echo "Nogova";
	} elseif ($ofp->m_servervars["mapname"]=="noe") {
		echo "Nogova";
	}	
	echo "</td></tr>\n";

//Eazy end

			echo "<tr><td>Time limit:</td><td>";
			if ($ofp->m_servervars["param1"]<>"0") {
				echo ,$ofp->m_servervars["param1"]," minutes";
			} else {
				echo "Not set";
			}
			echo "</td></tr>\n";
			if ($ofp->m_servervars["timeleft"]<>"0") {
				echo "<tr><td>Time left:</td><td>".$ofp->m_servervars["timeleft"]." minutes</td></tr\n>";
			}
			echo "<tr><td>Captures to win:</td><td>";
			if ($ofp->m_servervars["param2"]<>"0") {
				echo $ofp->m_servervars["param2"];
			} else {
				echo "Not set";
			
			echo "</td></tr>\n";
			}
			
			// **********************************************************************************
			// * End of general Extended info table                                             *
			// **********************************************************************************

			// **********************************************************************************
			// * Print out player-info for ALL players on server | Only if Extended query       *
			// **********************************************************************************

		}

			}
				echo "</table><br><br><table>";
				echo "<tr><td><h4><b>Players on our server:</b></h4></td></tr>\n";
				echo "<tr><center><td>Playername:<hr></td><td>Squadname:<hr></td><td><left>Score:<hr></left></td><td>Deaths:<hr></td></center></tr>\n";

				if (is_array($ofp->m_playerinfo)) {
				while (list(,$player) = each ($ofp->m_playerinfo)) {
					echo "<tr>\n";
			 		

					if ($player["team"]=="WKK"){
					echo "<td><font color=#00ff00><b>".$player["name"]."</b></font></td>\n";
					echo "<td><font color=#00ff00><b>".$player["team"]."</b></font></td>\n";
				} else {
					echo "<td>".$player["name"]."</td>\n";
					echo "<td>".str_replace(" ","&nbsp",$player["team"])."</td>\n";
					}
					echo "<td>".$player["score"]."</td>\n";
					echo "<td>".$player["deaths"]."</td>\n";
					echo "</tr>\n";
				}
				
				// **********************************************************************************
				// * End of player-info table                                                       *
				// **********************************************************************************
			
			
			}
		
			echo "</table>\n";
			echo "</td></tr></table>\n";
		// **********************************************************************************
		// * End of Extended info table                                                     *
		// **********************************************************************************
	
	} else { 
	echo "<FONT COLOR=red>Unable to contact server <B>".$server."</B>. , probably down.</FONT>"."<br>\n";
}
CloseTable();
?>
<center>
Made by <a href="mailto:eazyone@wkk.dk">[WKK]-EazyOne</a> // <a href="http://www.wkk.dk">[Who Killed Kenny]</a><br>
Original PHP source by <A HREF='http://www.wkk.dk' target='_blank'>[WKK]</A>-LordNam RealTime Server Query (RTSQ) for OFP<BR>
Thanx to:<a href="mailto:mivo@post.cz">Mivo</a> for making the RTSQ-code OFPR-compatible<br>
</center>

</BODY>
</HTML>
<?php
include("footer.php");
?>