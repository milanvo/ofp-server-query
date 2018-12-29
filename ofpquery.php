<?php
require("ofpr.php");

echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
echo '<HTML><HEAD><TITLE>OFP Server Real Time Query</TITLE>';
echo '</HEAD><BODY>';
echo "<H3>OFP Server Query</H3>";

// You can set server address and port to fixed value by edit and uncomment following line

// $server="IP:port";


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
	echo "<b>Basic server info</b>\n<br>\n";
	echo "<table border=1>";
	echo "<tr><td>Game</td><td>";
	if ($ofp->m_servervars["gamename"]=="opflashr") {
		echo "Operation Flashpoint Resistance";
	} elseif ($ofp->m_servervars["gamename"]=="opflash") {
		echo "Operation Flashpoint";
	}	
	echo "</td></tr>\n";
	echo "<tr><td>Hostname</td><td>".$ofp->m_servervars["hostname"]."</td></tr>\n";
	echo "<tr><td>Version</td><td>".$ofp->m_servervars["gamever"]."</td></tr>\n";
	echo "<tr><td>Required version</td><td>".$ofp->m_servervars["reqver"]."</td></tr>\n";

// Mivo: Resistance only
	if ($status=="extnew") {
		echo "<tr><td>Net implementation</td><td>".$ofp->m_servervars["impl"]."</td></tr>\n";
	}

	echo "<tr><td>Server type</td><td>".$ofp->m_servervars["servertype"]."</td></tr>\n";
	echo "<tr><td>Game status</td><td>".$ofp->m_servervars["gamestatus"]."</td></tr>\n";
	echo "<tr><td># players</td><td>".$ofp->m_servervars["numplayers"]."</td></tr>\n";
	echo "<tr><td>Max. players</td><td>".($ofp->m_servervars["maxplayers"])."</td></tr>\n";

	if ($ofp->m_servervars["maxplayers"]=="Not set") {
		$serverload="Not set";
	} else {
		$serverload=floor($ofp->m_servervars["numplayers"]/$ofp->m_servervars["maxplayers"]*100);		
	}
	if ($serverload<>"Not set") {
		echo "<tr><td>Server load</td><td>0%|<img src=\"pixel.gif\" height=8 width=$serverload border=0><img src=\"pixel2.gif\" height=8 width=".(100-$serverload)." border=0>|100% (<B>$serverload%</B>)</td></tr>\n";
	} 
	echo "</table>";
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
			echo "<P><b>Details</b>\n<br>\n";
			echo "<table border=1>\n";
			echo "</td></tr>\n";
			echo "<tr><td>Mission</td><td>".$ofp->m_servervars["gametype"]."</td></tr>\n";
			echo "<tr><td>Wold</td><td>".$ofp->m_servervars["mapname"]."</td></tr>\n";
			echo "<tr><td>Time limit (minutes)</td><td>";
			if ($ofp->m_servervars["param1"]<>"0") {
				echo $ofp->m_servervars["param1"];
			} else {
				echo "Not set";
			}
			echo "</td></tr>\n";
			if ($ofp->m_servervars["timeleft"]<>"0") {
				echo "<tr><td>Time left (minutes)</td><td>".$ofp->m_servervars["timeleft"]."</td></tr\n>";
			}
			echo "<tr><td>Captures to win</td><td>";
			if ($ofp->m_servervars["param2"]<>"0") {
				echo $ofp->m_servervars["param2"];
			} else {
				echo "Not set";
			}
			echo "</td></tr\n>";
			echo "</table>\n";
			// **********************************************************************************
			// * End of general Extended info table                                             *
			// **********************************************************************************

			// **********************************************************************************
			// * Print out player-info for ALL players on server | Only if Extended query       *
			// **********************************************************************************
			if (is_array($ofp->m_playerinfo)) {
				echo "<p><b>Player table</b><br>\n";
				echo "<table border=1>\n";
				echo "<tr BGCOLOR=#B0B0B0><td>Nick</td><td>Squad</td><td>Score</td><td>Deaths</td></tr>\n";
				while (list(,$player) = each ($ofp->m_playerinfo)) {
					echo "<tr>\n";
			    echo "<td>".$player["name"]."</td>\n";
					echo "<td>".str_replace(" ","&nbsp",$player["team"])."</td>\n";
					echo "<td>".$player["score"]."</td>\n";
					echo "<td>".$player["deaths"]."</td>\n";
					echo "</tr>\n";
				}
				echo "</table>\n";
				// **********************************************************************************
				// * End of player-info table                                                       *
				// **********************************************************************************
			}
		}
		// **********************************************************************************
		// * End of Extended info table                                                     *
		// **********************************************************************************
	}
} else { 
	echo "<FONT COLOR=red>Unable to contact server <B>".$server."</B>. , probably down.</FONT>"."<br>\n";
}
?>

<P>Based on PHP code from <A HREF='http://www.wkk.dk' target='_blank'>[WKK]</A> RealTime Server Query (RTSQ) for OFP<BR>
Changes for OFP:Resistance compatibility by <a href="mailto:mivo@post.cz">Mivo</a>
</BODY>
</HTML>